<?php

namespace App\Http\Controllers\Front;

use DB;
use Auth;
use Cookie;
use App\Constant;
use Carbon\Carbon;
use App\Models\Faq;
use App\Models\Enquiry;
use App\Models\Product;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\OfferSlider;
use App\Models\TabularOffer;
use Illuminate\Http\Request;
use App\Models\EmailTemplates;
use App\Models\BrandLogoSlider;
use App\Models\HomePageSetting;
use App\Models\ServiceCategory;
use App\Models\ScheduledPackage;
use App\Models\ServiceCenterDetail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;

class HomeController extends MainController
{
    public function index(Request $request)
    {
        $return_data = array();
        $return_data['settings'] = $this->data;
        
        $hsetting = HomePageSetting::select('section1_title1', 'section1_title2', 'section1_image', 'section1_description','image_title', 'meta_title', 'meta_keywords', 'meta_description','canonical_tag', 'extra_meta_tag','price_list')->first();
        $hsetting['section1_image'] = !empty($hsetting['section1_image']) ? json_decode($hsetting['section1_image']) : '';
        $return_data['hsetting'] = $hsetting;

        $return_data['offer_slider'] = OfferSlider::select('id', 'title1', 'title2', 'image','image_url','image_title', 'btn_link', 'btn_title','background','title_color','subtitle_color')->where('membership_package', 0)->orderBy('reorder', 'ASC')->get();

        $return_data['brand_logo_slider'] = BrandLogoSlider::select('id', 'image','image_title')->orderBy('id', 'ASC')->get();
        $return_data['car_brands'] = CarBrand::select('title', 'image')->orderBy('title', 'ASC')->get();

        $meta_title = isset($hsetting->meta_title) && $hsetting->meta_title ? $hsetting->meta_title : NULL;

        $return_data['meta_keywords'] = isset($hsetting->meta_keywords) && $hsetting->meta_keywords ? $hsetting->meta_keywords : NULL;
        $return_data['meta_description'] = isset($hsetting->meta_description) && $hsetting->meta_description ? $hsetting->meta_description : NULL;
        $return_data['canonical_tag'] = isset($hsetting->canonical_tag) && $hsetting->canonical_tag ? $hsetting->canonical_tag : NULL;
        $return_data['extra_meta_tag'] = isset($hsetting->extra_meta_tag) && $hsetting->extra_meta_tag ? $hsetting->extra_meta_tag : NULL;
        $return_data['site_title'] = $meta_title ? $meta_title : trans('Home');

        $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title','description', 'image','image_1','icon_image')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]])->orderBy('order_by', 'asc')->get();

        $return_data['service_center'] = ServiceCenterDetail::orderBy('id','asc')->get();
        $return_data['tabular_offers'] = TabularOffer::orderBy('reorder', 'asc')->get();
        $return_data['membership_package'] = OfferSlider::select('id', 'title1', 'title2', 'image','image_url','image_title', 'btn_link', 'btn_title','background','title_color','subtitle_color')->where('membership_package', 1)->orderBy('reorder', 'ASC')->get();
        $return_data['service_package'] = ScheduledPackage::select('image','title')->where('featured', 1)->where('status',1)->get();
        $return_data['car_models'] = CarModel::select('id','title')->orderBy('title', 'ASC')->get();
        
        $popup_detail = ServiceCenterDetail::select('id','image','address','phone_number','image_title')->get();
        $return_data['popup_detail'] = $popup_detail;
        $return_data['products'] = Product::where('featured',1)->where('status',1)->get();
        
        // Add FAQs to the return data
        $return_data['faqs'] = Faq::take(6)->get(); // Adjust number as needed
        
        return view('front/index', array_merge($this->data, $return_data));
    }

    public function appointmentStore(Request $request)
    {
        $this->validate($request, [
            'name' => ['required'],
            'email' => ['required'],
            'message' => ['required'],
            'g-recaptcha-response' => ['required']
        ],[
            'required'  => trans('The :attribute field is required.'),
            'g-recaptcha-response.required' => 'Please complete the captcha',
        ]);

        $appointment = Enquiry::create([
            'name' => $request->name ? strip_tags($request->name) : NULL,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'message' => $request->message,
        ]);

        if($appointment) {
            $this->sendDataToFreshFork($request);
            $scategories = ServiceCategory::select('id', 'title')->where('id',$request->service)->first();

            $name = $request->name;
            $email = $request->email;
            $phone = $request->phone;
            $location = $request->location;
            $message = $request->message;

            $templateStr = array('[NAME]','[EMAIL]','[PHONE]','[Message]','[Service]','[Location]');
            $data = array($name, $email,$phone, $message,$location);
            $ndata = EmailTemplates::select('template')->where('label', 'request_appointment')->first();
            $html = isset($ndata->template) ? $ndata->template : NULL;
            $mailHtml = str_replace($templateStr, $data, $html);
            
            \Mail::to([$request->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Request An Appointment - ' . $this->data['site_name']));
            
            return redirect('/')->with('success', trans('Our executive will contact you shortly'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function contactStore(Request $request)
    {
        $this->validate($request, [
            'name' => ['required'],
            'email' => ['required'],
            'message' => ['required']
        ],[
            'required'  => trans('The :attribute field is required.')
        ]);

        $appointment = Enquiry::create([
            'name' => $request->name ? strip_tags($request->name) : NULL,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'message' => $request->message,
        ]);

        if($appointment) {
            Cookie::queue('contact_id', $appointment->id, 21600);
            $this->sendDataToFreshFork($request);
            return redirect('/')->with('success', trans('Our executive will contact you shortly'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }
  
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|min:10|max:10',
            'location' => 'required',
            'message' => 'required',
            'consent' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('GOOGLE_RECAPTCHA_SECRET'),
            'response' => $request->input('g-recaptcha-response')
        ]);

        if (!$response->json()['success']) {
            return back()->withErrors(['captcha' => 'reCAPTCHA verification failed. Please try again.']);
        }

        $enquiry_type = "ACR Service";

        Enquiry::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'message' => $request->message,
            'cf_enquiry_type' => $enquiry_type
        ]);

        return redirect()->back()->with('success', 'Your enquiry has been submitted successfully!');
    }

    public static function sendDataToFreshFork($request)
    {
        $name = $request->name ?? '';
        $email = $request->email ?? '';
        $mobile = $request->phone ?? $request->mobile ?? '';
        $visit_date = date('Y-m-d');
        $model = $request->model ?? '';
        $location = $request->location ?? '';
        $enquiry_type = "ACR Service";
        $timestamp = time();
        $formname = "Web form";

        $sources = $request->utm_source ?? '';
        $medium = $request->utm_medium ?? '';
        $campaign = $request->utm_campaign ?? '';
        $term = $request->utm_term ?? '';
        $content = $request->utm_content ?? '';
        $sourceid = "70001109499";

        $jsonobj = [
            "contact" => [
                "first_name" => $name . "-" . $timestamp,
                "last_name" => ".",
                "email" => $email,
                "mobile_number" => $mobile,
                "lead_source_id" => $sourceid,
                "custom_field" => [
                    "cf_enquiry_type" => $enquiry_type,
                    "lead_source_id" => $sourceid,
                    "cf_acr_service_model" => $model,
                    "cf_utm_source" => $sources,
                    "cf_utm_medium" => $medium,
                    "cf_utm_campaign" => $campaign,
                    "cf_utm_term" => $term,
                    "cf_utm_content" => $content,
                    "cf_form_name" => $formname,
                    "cf_acr_service_location" => $location
                ]
            ]
        ];

        $objPass = json_encode($jsonobj);

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/search.json?include=contact&q=" . $mobile . "&qf=mobile",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Authorization: Token token=FJTFKzaJwH2lpV7UeKKuYw",
                    "Content-Type: application/json"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $result = json_decode($response);

            if (!empty($result)) {
                $responseid = $result[0]->id;
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/contacts/" . $responseid,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS => $objPass,
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Token token=FJTFKzaJwH2lpV7UeKKuYw",
                        "Content-Type: application/json"
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return ['success' => false, 'message' => 'Error updating contact: ' . $err];
                } else {
                    return ['success' => true, 'message' => 'Contact updated successfully'];
                }
            } else {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/contacts",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $objPass,
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Token token=FJTFKzaJwH2lpV7UeKKuYw",
                        "Content-Type: application/json"
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return ['success' => false, 'message' => 'Error creating contact: ' . $err];
                } else {
                    return ['success' => true, 'message' => 'New contact created successfully'];
                }
            }
        } catch (Exception $ex) {
            return ['success' => false, 'message' => 'Exception occurred: ' . $ex->getMessage()];
        }
    }

    /* ===================================================================
     * API SIBLINGS — additive, do not modify the methods above.
     * Consumed by the React frontend via routes/api.php.
     * =================================================================== */

    public function indexApi(Request $request)
    {
        $hsetting = HomePageSetting::select(
            'section1_title1', 'section1_title2', 'section1_image',
            'section1_description', 'image_title', 'meta_title',
            'meta_keywords', 'meta_description', 'canonical_tag',
            'extra_meta_tag', 'price_list', 'footer_description'
        )->first();

        if ($hsetting && !empty($hsetting->section1_image)) {
            $hsetting->section1_image = json_decode($hsetting->section1_image);
        }

        return response()->json([
            'success'            => true,
            'settings'           => $this->data,
            'home_page_setting'  => $hsetting,
            'offer_slider'       => OfferSlider::select('id','title1','title2','image','image_url','image_title','btn_link','btn_title','background','title_color','subtitle_color')->where('membership_package',0)->orderBy('reorder','ASC')->get(),
            'membership_package' => OfferSlider::select('id','title1','title2','image','image_url','image_title','btn_link','btn_title','background','title_color','subtitle_color')->where('membership_package',1)->orderBy('reorder','ASC')->get(),
            'brand_logo_slider'  => BrandLogoSlider::select('id','image','image_title')->orderBy('id','ASC')->get(),
            'car_brands'         => CarBrand::select('id','slug','title','image')->where([['is_archive',\App\Constant::NOT_ARCHIVE],['status',\App\Constant::ACTIVE]])->orderBy('title','ASC')->get(),
            'car_models'         => CarModel::select('id','slug','title')->where([['is_archive',\App\Constant::NOT_ARCHIVE],['status',\App\Constant::ACTIVE]])->orderBy('title','ASC')->get(),
            'service_categories' => ServiceCategory::select('id','slug','title','description','image','image_1','icon_image')->where([['is_archive',\App\Constant::NOT_ARCHIVE],['status',\App\Constant::ACTIVE]])->orderBy('order_by','asc')->get(),
            'service_centers'    => ServiceCenterDetail::orderBy('id','asc')->get(),
            'tabular_offers'     => TabularOffer::orderBy('reorder','asc')->get(),
            'service_packages'   => ScheduledPackage::select('id','slug','title','image')->where('featured',1)->where('status',\App\Constant::ACTIVE)->get(),
            'featured_products'  => Product::with('primaryImage')->where('featured',1)->where('status',\App\Constant::ACTIVE)->get(),
            'faqs'               => Faq::where('is_archive',\App\Constant::NOT_ARCHIVE)->take(6)->get(),
            'seo'                => \App\Helpers\SeoHelper::fromModel($hsetting, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }
}