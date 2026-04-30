<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FormSubmissionController extends Controller
{
    private $freshworksApiUrl = 'https://harpreet-ford.myfreshworks.com/crm/sales/api/contacts';
    private $freshworksToken = 'FJTFKzaJwH2lpV7UeKKuYw';
    private $sourceId = '70001109499';

    public function submit(Request $request)
    {
        try {
            // 1. SPAM PROTECTION - Honeypot Check
            if ($request->filled('website') || $request->filled('url')) {
                Log::warning('Honeypot triggered', ['ip' => $request->ip()]);
                return $this->jsonResponse(false, 'Invalid submission detected.');
            }

            // 2. ORIGIN VALIDATION - Check if request came from valid page
            if (!$this->validateOrigin($request)) {
                Log::warning('Invalid origin', ['ip' => $request->ip(), 'referer' => $request->header('referer')]);
                return $this->jsonResponse(false, 'Invalid request origin.');
            }

            // 3. CAPTCHA VALIDATION
            if (!$this->validateCaptcha($request)) {
                return $this->jsonResponse(false, 'Captcha verification failed. Please try again.');
            }

            // 4. FORM DATA VALIDATION
            $validator = $this->validateFormData($request);
            if ($validator->fails()) {
                return $this->jsonResponse(false, $validator->errors()->first());
            }

            // 5. ADDITIONAL SPAM CHECKS
            if ($this->isSpamContent($request)) {
                Log::warning('Spam content detected', ['ip' => $request->ip()]);
                return $this->jsonResponse(false, 'Invalid content detected.');
            }

            // 6. PROCESS FORM SUBMISSION
            $enquiryData = $this->prepareEnquiryData($request);
            
            // Save to database
            $enquiryId = $this->saveToDatabase($enquiryData);
            
            // Send to Freshworks
            $freshworksResponse = $this->sendToFreshworks($enquiryData);
            
            // Log the submission
            $this->logSubmission($enquiryData, $freshworksResponse);

            return $this->jsonResponse(true, 'Form submitted successfully!');

        } catch (\Exception $e) {
            Log::error('Form submission error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'data' => $request->except(['captcha', 'correct_answer'])
            ]);

            return $this->jsonResponse(false, 'Something went wrong. Please try again.');
        }
    }

    private function validateOrigin(Request $request): bool
    {
        $referer = $request->header('referer');
        $allowedDomains = [
            'autocarrepair.in',
            'www.autocarrepair.in',
            'localhost', // For development
        ];

        if (!$referer) {
            return false;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        return in_array($refererHost, $allowedDomains);
    }

    private function validateCaptcha(Request $request): bool
    {
        $userAnswer = (int) $request->input('captcha');
        $correctAnswer = (int) $request->input('correct_answer');

        return $userAnswer === $correctAnswer;
    }

    private function validateFormData(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|max:255',
            'phone' => [
                'required',
                'regex:/^[789]\d{9}$/', // Must start with 7, 8, or 9 and be exactly 10 digits
                'numeric'
            ],
            'location' => 'required|string|max:100',
            'message' => 'nullable|string|max:1000',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'captcha' => 'required|numeric',
            'correct_answer' => 'required|numeric',
            'agree' => 'accepted', // Must be checked
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number starting with 7, 8, or 9.',
            'agree.accepted' => 'You must agree to the terms and conditions.',
        ]);
    }

    private function isSpamContent(Request $request): bool
    {
        $spamKeywords = [
            'viagra', 'casino', 'lottery', 'winner', 'congratulations',
            'click here', 'free money', 'make money fast', 'work from home',
            'http://', 'https://', 'www.', '.com', '.net', '.org'
        ];

        $content = strtolower($request->input('name') . ' ' . $request->input('message'));
        
        foreach ($spamKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                return true;
            }
        }

        // Check for excessive repeated characters
        if (preg_match('/(.)\1{4,}/', $content)) {
            return true;
        }

        return false;
    }

    private function prepareEnquiryData(Request $request): array
    {
        $pageUrl = $request->header('referer', '');
        $formName = 'ACR WEB - ' . basename(parse_url($pageUrl, PHP_URL_PATH));

        return [
            'name' => trim($request->input('name')),
            'email' => strtolower(trim($request->input('email'))),
            'mobile' => $request->input('phone'),
            'location' => $request->input('location'),
            'message' => $request->input('message', ''),
            'brand' => $request->input('brand', ''),
            'model' => $request->input('model', ''),
            'enquiry_type' => 'ACR Service',
            'form_name' => $formName,
            'utm_source' => $request->input('utm_source', ''),
            'utm_medium' => $request->input('utm_medium', ''),
            'utm_campaign' => $request->input('utm_campaign', ''),
            'utm_term' => $request->input('utm_term', ''),
            'utm_content' => $request->input('utm_content', ''),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => time(),
        ];
    }

    private function saveToDatabase(array $data): int
    {
        return DB::table('enquires')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['mobile'],
            'service' => $data['brand'],
            'location' => $data['location'],
            'message' => $data['message'],
            'model' => $data['model'],
            'enquiry_type' => $data['enquiry_type'],
            'form_name' => $data['form_name'],
            'utm_source' => $data['utm_source'],
            'utm_medium' => $data['utm_medium'],
            'utm_campaign' => $data['utm_campaign'],
            'utm_term' => $data['utm_term'],
            'utm_content' => $data['utm_content'],
            'is_sent_to_freshworks' => 0,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sendToFreshworks(array $data): array
    {
        try {
            // Split name into first and last name
            $nameParts = explode(' ', trim($data['name']), 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            $customFields = [
                'cf_enquiry_type' => $data['enquiry_type'],
                'cf_acr_service_model' => $data['model'],
                'cf_utm_source' => $data['utm_source'],
                'cf_utm_medium' => $data['utm_medium'],
                'cf_utm_campaign' => $data['utm_campaign'],
                'cf_utm_term' => $data['utm_term'],
                'cf_utm_content' => $data['utm_content'],
                'cf_form_name' => $data['form_name'],
                'cf_acr_service_location' => $data['location'],
                'cf_message' => $data['message'],
                'cf_actual_mobile' => $data['mobile'],
                'cf_submission_timestamp' => $data['timestamp'],
            ];

            $payload = [
                'contact' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $data['email'],
                    'mobile_number' => $data['mobile'],
                    'lead_source_id' => $this->sourceId,
                    'custom_field' => $customFields,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $this->freshworksToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->freshworksApiUrl, $payload);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'payload' => $payload ?? null,
            ];
        }
    }

    private function logSubmission(array $data, array $freshworksResponse): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'form_data' => $data,
            'freshworks_response' => $freshworksResponse,
        ];

        Log::info('Form submission processed', $logData);

        // Also log to file for debugging (similar to your original approach)
        $logMessage = date('Y-m-d H:i:s') . " - Form Submission\n";
        $logMessage .= "Data: " . json_encode($data) . "\n";
        $logMessage .= "Freshworks Response: " . json_encode($freshworksResponse) . "\n\n";
        
        file_put_contents(storage_path('logs/freshworks_log.txt'), $logMessage, FILE_APPEND);
    }

    private function jsonResponse(bool $success, string $message): \Illuminate\Http\JsonResponse
    {
        $status = $success ? 200 : 400;
        return response()->json([
            'success' => $success,
            'message' => $message,
        ], $status);
    }
}
