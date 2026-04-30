<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use App\Models\Page;
use App\Models\CompnyCmsPage;
use App\Models\Enquiry;
use App\Models\ServiceCategory;
use App\Models\EmailTemplates;
use App\Models\BrandLogoSlider;
use App\Models\Seo;

class CmsPagesController extends MainController
{
    public function index()
    {
        $segment = request()->segment(1);

        if ($segment) {
            $pageInfo = Page::where([['slug', $segment]])->first();
            if ($pageInfo) {
                $return_data = array();
                $return_data['site_title'] = trans(ucwords($pageInfo->name));
                $return_data['pageInfo'] = $pageInfo;
                $return_data['meta_keywords'] = $pageInfo->meta_keyword;
                $return_data['meta_description'] = $pageInfo->meta_description;
                $return_data['meta_title'] = $pageInfo->meta_title;
                $return_data['extra_meta_tag'] = $pageInfo->extra_meta_tag;
                $return_data['canonical_tag'] = $pageInfo->canonical_tag;

                // Add service categories for dropdown
                $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
                    ->where([
                        ['is_archive', Constant::NOT_ARCHIVE],
                        ['status', Constant::ACTIVE]
                    ])
                    ->orderBy('order_by', 'asc')
                    ->get();

                return view('front.cms.index', array_merge($this->data, $return_data));
            } else {
                return redirect('/');
            }
        } else {
            return redirect('/');
        }
    }

    public function aboutUs()
    {
        $return_data = array();
        $return_data['site_title'] = trans('About Us');
        $return_data['brand_logo_slider'] = BrandLogoSlider::select('id', 'image', 'image_title')->orderBy('id', 'ASC')->get();

        // Add service categories for dropdown
        $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
            ->where([
                ['is_archive', Constant::NOT_ARCHIVE],
                ['status', Constant::ACTIVE]
            ])
            ->orderBy('order_by', 'asc')
            ->get();

        $about_us = Seo::select('meta_title', 'meta_keyword', 'meta_description', 'extra_meta_description', 'canonical_tag')
            ->where('id', Constant::ABOUT_US_SEO_ID)
            ->first();

        $return_data['meta_keywords'] = $about_us->meta_keyword ?? null;
        $return_data['meta_description'] = $about_us->meta_description ?? null;
        $return_data['canonical_tag'] = $about_us->canonical_tag ?? null;
        $return_data['extra_meta_description'] = $about_us->extra_meta_description ?? null;
        $return_data['meta_title'] = $about_us->meta_title ?? null;

        return view('front.cms.about_us', array_merge($this->data, $return_data));
    }

    public function cmsPage()
    {
        $segment = request()->segment(1);

        if ($segment) {
            $compnypageInfo = CompnyCmsPage::where([['slug', $segment]])->first();
            if ($compnypageInfo) {
                $return_data = array();
                $return_data['site_title'] = trans(ucwords($compnypageInfo->meta_title));
                $return_data['compnypageInfo'] = $compnypageInfo;
                $return_data['meta_keywords'] = $compnypageInfo->meta_keywords;
                $return_data['meta_description'] = $compnypageInfo->meta_description;
                $return_data['meta_title'] = $compnypageInfo->meta_title;
                $return_data['extra_meta_tag'] = $compnypageInfo->extra_meta_tag;
                $return_data['canonical_tag'] = $compnypageInfo->canonical_tag;

                // Add service categories for dropdown
                $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
                    ->where([
                        ['is_archive', Constant::NOT_ARCHIVE],
                        ['status', Constant::ACTIVE]
                    ])
                    ->orderBy('order_by', 'asc')
                    ->get();

                return view('front.cms.company', array_merge($this->data, $return_data));
            } else {
                return redirect('/');
            }
        } else {
            return redirect('/');
        }
    }

    public function compnyStore(Request $request)
    {
        $this->validate($request, [
            'name' => ['required'],
            'email' => ['required'],
            'phone' => ['required'],
            'location' => ['required'],
            'message' => [''],
        ], [
            'required' => trans('The :attribute field is required.')
        ]);

        // Set Enquiry Type Value
        $enquiry_type = "ACR Service";

        $compny = Enquiry::create([
            'name' => $request->name ? strip_tags($request->name) : null,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'cf_enquiry_type' => $enquiry_type,
            'message' => $request->message,
        ]);

        if ($compny) {
            HomeController::sendDataToFreshFork($request);

            $scategories = ServiceCategory::select('id', 'title')->where('id', $request->service)->first();
            $name = $request->name;
            $email = $request->email;
            $phone = $request->phone;
            $location = $request->location;
            $message = $request->message;

            $templateStr = array('[NAME]', '[EMAIL]', '[PHONE]', '[Message]', '[Location]', '[ENQUIRY_TYPE]');
            $data = array($name, $email, $phone, $message, $location, $enquiry_type);

            $ndata = EmailTemplates::select('template')->where('label', 'request_appointment')->first();
            $html = isset($ndata->template) ? $ndata->template : null;
            $mailHtml = str_replace($templateStr, $data, $html);

            return redirect()->back()->with('success', trans('Our executive will contact you shortly'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /* === API SIBLINGS === */

    public function pageApi(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)->first();
        if (!$page) return response()->json(['success'=>false,'message'=>'Page not found.'], 404);

        return response()->json([
            'success' => true,
            'page'    => $page,
            'seo'     => \App\Helpers\SeoHelper::fromModel($page, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
                'type'      => 'article',
            ]),
        ]);
    }

    public function companyPageApi(Request $request, $slug)
    {
        $page = CompnyCmsPage::where('slug', $slug)->first();
        if (!$page) return response()->json(['success'=>false,'message'=>'Page not found.'], 404);

        return response()->json([
            'success' => true,
            'page'    => $page,
            'seo'     => \App\Helpers\SeoHelper::fromModel($page, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
                'type'      => 'article',
            ]),
        ]);
    }

    public function aboutUsApi(Request $request)
    {
        $brandLogo = BrandLogoSlider::select('id','image','image_title')->orderBy('id','ASC')->get();
        $aboutSeo  = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::ABOUT_US_SEO_ID)->first();

        return response()->json([
            'success'           => true,
            'brand_logo_slider' => $brandLogo,
            'seo'               => \App\Helpers\SeoHelper::fromModel($aboutSeo, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }
}