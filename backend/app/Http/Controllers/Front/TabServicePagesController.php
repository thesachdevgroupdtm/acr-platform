<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use App\Models\Page;
use App\Models\TabServiceCmsPage;
use App\Models\Enquiry;
use App\Models\ServiceCategory;
use App\Models\EmailTemplates;
use App\Models\BrandLogoSlider;
use App\Models\Seo;
use Auth;
use DB;

class TabServicePagesController extends MainController
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

        return view('front.cms.about_us', array_merge($this->data, $return_data));
    }

 public function cmsPage()
{
    $segment = request()->segment(1);

    if ($segment) {
        $allTabServices = TabServiceCmsPage::where('is_archive', 0)->get();
        $tabServiceInfo = TabServiceCmsPage::where([['slug', $segment]])->first();

        if ($tabServiceInfo) {
            $return_data = array();
            $return_data['site_title'] = trans(ucwords($tabServiceInfo->meta_title));
            $return_data['tabServiceInfo'] = $tabServiceInfo;
            $return_data['banner_image'] = empty($tabServiceInfo->banner_image) ? asset('img/default.jpg') : asset('uploads/tabservicecms/' . $tabServiceInfo->banner_image);
            $return_data['service_list'] = $allTabServices;
            
            // ADD THESE LINES TO PASS SEO FIELDS
            $return_data['meta_keywords'] = $tabServiceInfo->meta_keywords;
            $return_data['meta_description'] = $tabServiceInfo->meta_description;
            $return_data['meta_title'] = $tabServiceInfo->meta_title;
            $return_data['extra_meta_tag'] = $tabServiceInfo->extra_meta_tag;
            $return_data['canonical_tag'] = $tabServiceInfo->canonical_tag;

            // Add service categories for dropdown
            $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
                ->where([
                    ['is_archive', Constant::NOT_ARCHIVE],
                    ['status', Constant::ACTIVE]
                ])
                ->orderBy('order_by', 'asc')
                ->get();

            return view('front.cms.tab_service', array_merge($this->data, $return_data));
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
            'message' => ['required'],
        ], [
            'required' => trans('The :attribute field is required.')
        ]);

        $company = Enquiry::create([
            'name' => $request->name ? strip_tags($request->name) : null,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'message' => $request->message,
        ]);

        if ($company) {
            HomeController::sendDataToFreshFork($request);

            $scategories = ServiceCategory::select('id', 'title')->where('id', $request->service)->first();
            $name = $request->name;
            $email = $request->email;
            $phone = $request->phone;
            $message = $request->message;

            $templateStr = array('[NAME]', '[EMAIL]', '[PHONE]', '[Message]');
            $data = array($name, $email, $phone, $message);
            $ndata = EmailTemplates::select('template')->where('label', 'request_appointment')->first();
            $html = isset($ndata->template) ? $ndata->template : null;
            $mailHtml = str_replace($templateStr, $data, $html);

            return redirect()->back()->with('success', trans('Our executive will contact you shortly'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }
}