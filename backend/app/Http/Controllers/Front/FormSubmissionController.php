<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FormSubmissionController extends Controller
{
    // Bitrix inbound webhook (replace if you have a different webhook)
    private $bitrixWebhookUrl = 'https://acr.bitrix24.in/rest/1/cn2fepdgaocduawr/crm.lead.add.json';

    // Keep sourceId for internal reference if needed
    private $sourceId = 'WEB';

    public function submit(Request $request)
    {
        try {
            Log::info('Form submission received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer')
            ]);

            // 1. ENHANCED RATE LIMITING - Check IP-based submissions
            if (!$this->checkRateLimit($request)) {
                Log::warning('Rate limit exceeded', [
                    'ip' => $request->ip(),
                    'recent_submissions' => $this->getRecentSubmissions($request->ip())
                ]);
                return $this->jsonResponse(false, 'Too many submissions from your IP. Please wait before submitting again.');
            }

            // 2. SPAM PROTECTION - Honeypot Check
            if ($request->filled('website') || $request->filled('url')) {
                Log::warning('Honeypot triggered', ['ip' => $request->ip()]);
                return $this->jsonResponse(false, 'Invalid submission detected.');
            }

            // 3. CAPTCHA VALIDATION
            if (!$this->validateCaptcha($request)) {
                return $this->jsonResponse(false, 'Captcha verification failed. Please try again.');
            }

            // 4. ENHANCED FORM DATA VALIDATION
            $validator = $this->validateFormData($request);
            if ($validator->fails()) {
                $errors = $validator->errors();
                Log::error('Validation failed', [
                    'errors' => $errors->toArray(),
                    'input_data' => $request->except(['captcha', 'correct_answer']),
                    'ip' => $request->ip()
                ]);
                
                return $this->jsonResponse(false, $validator->errors()->first());
            }

            // 5. ADVANCED SPAM CONTENT DETECTION
            if ($this->isSpamContent($request)) {
                Log::warning('Spam content detected', [
                    'ip' => $request->ip(),
                    'name' => $request->input('name'),
                    'message' => $request->input('message')
                ]);
                return $this->jsonResponse(false, 'Invalid content detected. Please use proper name and message.');
            }

            // 6. IMPROVED DUPLICATE SUBMISSION CHECK
            if ($this->isDuplicateSubmission($request)) {
                Log::warning('Duplicate submission detected', [
                    'ip' => $request->ip(),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone')
                ]);
                return $this->jsonResponse(false, 'This exact information has already been submitted recently. Please wait a few minutes before submitting again.');
            }

            // 7. ORIGIN VALIDATION (just logging; not blocking)
            if (!$this->validateOrigin($request)) {
                Log::warning('Invalid origin', ['ip' => $request->ip(), 'referer' => $request->header('referer')]);
            }

            // 8. PROCESS FORM SUBMISSION
            $enquiryData = $this->prepareEnquiryData($request);
            
            // Save to database
            $enquiryId = $this->saveToDatabase($enquiryData);
            
            // Send to Bitrix (replaces Freshworks)
            $bitrixResponse = $this->sendToBitrix($enquiryData);
            
            // Log the submission
            $this->logSubmission($enquiryData, $bitrixResponse);

            // Update DB flag if needed (mark sent)
            try {
                DB::table('enquires')->where('id', $enquiryId)->update([
                    'is_sent_to_freshworks' => $bitrixResponse['success'] ? 1 : 0, // keeping column name same to avoid DB changes
                    'updated_at' => now()
                ]);
            } catch (\Exception $ex) {
                Log::error('Could not update enquiry send flag', ['error' => $ex->getMessage(), 'enquiry_id' => $enquiryId]);
            }

            Log::info('Form submitted successfully', [
                'enquiry_id' => $enquiryId,
                'ip' => $request->ip()
            ]);

            return $this->jsonResponse(true, 'Form submitted successfully!');

        } catch (\Exception $e) {
            Log::error('Form submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'data' => $request->except(['captcha', 'correct_answer'])
            ]);

            return $this->jsonResponse(false, 'Something went wrong. Please try again.');
        }
    }

    private function checkRateLimit(Request $request): bool
    {
        $ip = $request->ip();
        $timeWindow = now()->subMinutes(5); // Reduced to 5-minute window
        
        // Count submissions from this IP in the last 5 minutes
        $recentSubmissions = DB::table('enquires')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', $timeWindow)
            ->count();

        // Allow maximum 5 submissions per IP in 5 minutes (increased from 3)
        return $recentSubmissions < 5;
    }

    private function getRecentSubmissions(string $ip): int
    {
        $timeWindow = now()->subMinutes(5);
        return DB::table('enquires')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', $timeWindow)
            ->count();
    }

    private function validateOrigin(Request $request): bool
    {
        $referer = $request->header('referer');
        $allowedDomains = [
            'autocarrepair.in',
            'www.autocarrepair.in',
            'localhost',
            '127.0.0.1',
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
        
        Log::info('Captcha validation', [
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'match' => $userAnswer === $correctAnswer
        ]);
        
        return $userAnswer === $correctAnswer;
    }

    private function validateFormData(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z\s\-\'\.]+$/', // Only letters, spaces, hyphens, apostrophes, dots
                function ($attribute, $value, $fail) {
                    if (preg_match('/(.)\1{3,}/', $value)) {
                        $fail('Name contains invalid repeated characters.');
                    }
                    if (preg_match_all('/\d/', $value) > 2) {
                        $fail('Name cannot contain numbers.');
                    }
                    $spamPatterns = [
                        '/test/i',
                        '/testing/i',
                        '/^[0-9]+$/',
                        '/^[a-z]+[0-9]+$/i',
                        '/^[0-9]+[a-z]+$/i',
                    ];
                    foreach ($spamPatterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            $fail('Please enter a valid name.');
                        }
                    }
                    $words = array_filter(explode(' ', trim($value)));
                    if (count($words) < 1) {
                        $fail('Please enter your full name.');
                    }
                    foreach ($words as $word) {
                        if (strlen($word) < 2) {
                            $fail('Each part of your name must be at least 2 characters.');
                        }
                    }
                }
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $disposableDomains = [
                        '10minutemail.com',
                        'tempmail.org',
                        'guerrillamail.com',
                        'mailinator.com',
                        'yopmail.com'
                    ];
                    $domain = substr(strrchr($value, "@"), 1);
                    if (in_array(strtolower($domain), $disposableDomains)) {
                        $fail('Please use a valid email address.');
                    }
                }
            ],
            'phone' => [
    'required',
    'regex:/^(?:\+91[-\s]?)?[789]\d{9}$/',
    function ($attribute, $value, $fail) {
        // Normalize: remove +91, spaces, dashes for validation checks
        $cleanValue = preg_replace('/^\+91[-\s]?/', '', $value);
        $cleanValue = preg_replace('/\s+|-+/', '', $cleanValue);

        // Block repeated digits (7+ same)
        if (preg_match('/(\d)\1{6,}/', $cleanValue)) {
            $fail('Please enter a valid phone number.');
        }

        // Block sequential numbers
        $sequential = ['1234567890', '0123456789', '9876543210'];
        if (in_array($cleanValue, $sequential)) {
            $fail('Please enter a valid phone number.');
        }
    }
],

            'location' => 'required|string|max:100',
            'message' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $spamKeywords = [
                            'viagra', 'casino', 'lottery', 'winner', 'congratulations',
                            'click here', 'free money', 'make money fast', 'work from home',
                            'http://', 'https://', 'www.', '.com', '.net', '.org'
                        ];
                        $lowerMessage = strtolower($value);
                        foreach ($spamKeywords as $keyword) {
                            if (strpos($lowerMessage, $keyword) !== false) {
                                $fail('Message contains inappropriate content.');
                            }
                        }
                        if (preg_match('/(.)\1{5,}/', $value)) {
                            $fail('Message contains invalid repeated characters.');
                        }
                    }
                }
            ],
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'captcha' => 'required|numeric',
            'correct_answer' => 'required|numeric',
            'agree' => 'accepted',
        ], [
            'name.required' => 'Name is required.',
            'name.regex' => 'Name can only contain letters, spaces, hyphens, and apostrophes.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name cannot exceed 50 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number starting with 7, 8, or 9.',
            'location.required' => 'Please select a location.',
            'captcha.required' => 'Please solve the captcha.',
            'agree.accepted' => 'You must agree to the terms and conditions.',
        ]);
    }

    private function isSpamContent(Request $request): bool
    {
        $name = strtolower(trim($request->input('name')));
        $message = strtolower(trim($request->input('message', '')));
        
        $spamPatterns = [
            'viagra', 'casino', 'lottery', 'winner', 'congratulations',
            'click here', 'free money', 'make money fast', 'work from home',
            'test', 'testing', 'dummy', 'sample',
            'aaaa', 'bbbb', 'cccc', '1111', '2222', '3333',
            'http', 'www.', '.com', '.net', '.org', '.co',
        ];

        $content = $name . ' ' . $message;
        
        foreach ($spamPatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                return true;
            }
        }

        if (preg_match('/(.)\1{4,}/', $content)) {
            return true;
        }

        if (preg_match('/^\d+/', $name) || preg_match_all('/\d/', $name) > strlen($name) / 2) {
            return true;
        }

        return false;
    }

    private function isDuplicateSubmission(Request $request): bool
    {
        $email = $request->input('email');
        $phone = $request->input('phone');
        $name = $request->input('name');
        $ip = $request->ip();
        
        $exactDuplicateWindow = now()->subMinutes(10); // 10 minutes for exact matches
        $ipWindow = now()->subMinutes(5); // 5 minutes for IP-based check

        $exactDuplicate = DB::table('enquires')
            ->where('email', $email)
            ->where('phone', $phone)
            ->where('name', $name)
            ->where('created_at', '>=', $exactDuplicateWindow)
            ->exists();

        if ($exactDuplicate) {
            Log::info('Exact duplicate found', [
                'email' => $email,
                'phone' => $phone,
                'name' => $name
            ]);
            return true;
        }

        $ipSubmissions = DB::table('enquires')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', $ipWindow)
            ->count();

        if ($ipSubmissions >= 3) {
            Log::info('Too many IP submissions', [
                'ip' => $ip,
                'count' => $ipSubmissions
            ]);
            return true;
        }

        $emailWindow = now()->subMinutes(15); // 15 minutes for email check
        $emailSubmissions = DB::table('enquires')
            ->where('email', $email)
            ->where('created_at', '>=', $emailWindow)
            ->count();

        if ($emailSubmissions >= 2) {
            Log::info('Multiple email submissions', [
                'email' => $email,
                'count' => $emailSubmissions
            ]);
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
            'mobile' => preg_match('/^\+91/', $request->input('phone'))      ? $request->input('phone')      : '+91' . $request->input('phone'),
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

    /**
     * Map readable location name to Bitrix enumeration ID.
     * Per your mapping:
     * Motinagar => 75
     * Okhla => 77
     * Gurgaon => 79
     * Noida => 81
     */
    private function mapLocationToBitrixEnumId(string $location): ?int
    {
        $loc = strtolower(trim($location));
        $map = [
            'motinagar' => 75,
            'motinagar ' => 75,
            'okhla' => 77,
            'gurgaon' => 79,
            'noida' => 81,
            // try some common variants
            'gurugram' => 79,
            'moti nagar' => 75,
        ];

        foreach ($map as $k => $id) {
            if (strpos($loc, $k) !== false) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Send to Bitrix using inbound webhook.
     * Returns an array similar to previous sendToFreshworks response structure.
     */
    private function sendToBitrix(array $data): array
    {
        try {
            // split name
            $nameParts = explode(' ', trim($data['name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Map location to UF enumeration ID (if possible)
            $locationEnumId = $this->mapLocationToBitrixEnumId($data['location']);

            // Build fields for Bitrix lead
            $fields = [
                'TITLE' => $data['form_name'] . ' - ' . ($data['brand'] ?: 'Enquiry'),
                'NAME' => $firstName,
                'LAST_NAME' => $lastName,
                // Comments can hold message + model info
                'COMMENTS' => $data['message'] . ($data['model'] ? "\nModel: " . $data['model'] : ''),
                // Source - set generic WEB (adjust if you have specific sources)
                'SOURCE_ID' => 'WEB',
            ];

            // Add phone in Bitrix format
            if (!empty($data['mobile'])) {
                $fields['PHONE'] = [
                    ['VALUE' => $data['mobile'], 'VALUE_TYPE' => 'WORK']
                ];
            }

            // Add email if present
            if (!empty($data['email'])) {
                $fields['EMAIL'] = [
                    ['VALUE' => $data['email'], 'VALUE_TYPE' => 'WORK']
                ];
            }

            // Add custom enumerated location field if mapped
            if ($locationEnumId) {
                $fields['UF_CRM_1759294115701'] = $locationEnumId;
            } else {
                // If not mapped, optionally pass raw text into a comments or custom text field
                // Uncomment if you have another UF field for raw location
                // $fields['UF_CRM_RAW_LOCATION'] = $data['location'];
            }

            // Add UTM fields as custom fields if your Bitrix has them (use actual UF names if available)
            if (!empty($data['utm_source'])) {
                $fields['UTM_SOURCE'] = $data['utm_source'];
            }
            if (!empty($data['utm_medium'])) {
                $fields['UTM_MEDIUM'] = $data['utm_medium'];
            }
            if (!empty($data['utm_campaign'])) {
                $fields['UTM_CAMPAIGN'] = $data['utm_campaign'];
            }

            // Final payload for Bitrix inbound webhook: 'fields' param
            $payload = [
                'fields' => $fields,
                'params' => ['REGISTER_SONET_EVENT' => 'Y'] // optional; registers activity in social network
            ];

            // Send as form POST
            $response = Http::asForm()->timeout(30)->post($this->bitrixWebhookUrl, $payload);

            // bitrix returns JSON; capture body/status
            $body = $response->body();
            $status = $response->status();
            $successful = $response->successful();

            return [
                'success' => $successful,
                'status_code' => $status,
                'response' => $body,
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

    private function logSubmission(array $data, array $bitrixResponse): void
    {
        $logMessage = date('Y-m-d H:i:s') . " - Form Submission\n";
        $logMessage .= "Data: " . json_encode($data) . "\n";
        $logMessage .= "Bitrix Response: " . json_encode($bitrixResponse) . "\n\n";
        
        file_put_contents(storage_path('logs/bitrix_log.txt'), $logMessage, FILE_APPEND);
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
