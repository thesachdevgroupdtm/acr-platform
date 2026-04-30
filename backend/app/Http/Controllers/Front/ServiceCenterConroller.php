<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCenterDetail;
use App\Models\Seo;
use App\Constant;
use App\Models\ServiceCategory;

class ServiceCenterConroller extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['settings'] = $this->data;
        $return_data['site_title'] = trans('Service Center');
        $service_center = ServiceCenterDetail::orderBy('id','desc')->get();
        $return_data['service_center'] = $service_center;
        
        // Add service categories
        $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
            ->where([
                ['is_archive', Constant::NOT_ARCHIVE],
                ['status', Constant::ACTIVE]
            ])
            ->orderBy('order_by', 'asc')
            ->get();
            
        $service_center = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::SERVICE_CENTER_SEO_ID)
            ->first();
            
        $return_data['meta_keywords'] = $service_center->meta_keyword ?? null;
        $return_data['meta_description'] = $service_center->meta_description ?? null;
        $return_data['canonical_tag'] = $service_center->canonical_tag ?? null;
        $return_data['extra_meta_description'] = $service_center->extra_meta_description ?? null;
        $return_data['meta_title'] = $service_center->meta_title ?? null;
        
        return view('front/servicecenter/index', array_merge($this->data, $return_data));
    }
    
    public function locations()
    {
        $seoData = [
            'title' => 'Auto Car Repair in Gurgaon – Multi-Brand Car Service',
            'description' => 'Auto Car Repair in Gurugram offers multi-brand car services. Our experts use genuine parts & top-quality products. Call us to book your car service now.',
            'keywords' => 'car repair gurgaon, auto service gurgaon, car maintenance gurgaon, multi-brand car service gurgaon'
        ];
        
        return $this->locationPage('gurgaon', 'Auto Car Repair Gurgaon', $seoData);
    }

    public function motinagar()
    {
        $seoData = [
            'title' => 'Auto Car Repair Moti Nagar – Multi-Brand Car Service in Delhi',
            'description' => 'Visit Auto Car Repair in Moti Nagar for multi-brand car services and repairs. Our certified technicians use quality parts & products for the best results.',
            'keywords' => 'car repair moti nagar, auto service delhi, car maintenance moti nagar, multi-brand car service delhi'
        ];
        
        return $this->locationPage('motinagar', 'Auto Car Repair Moti Nagar', $seoData);
    }

    public function noida()
    {
        $seoData = [
            'title' => 'Car Repair and Detailing in Noida- Auto Car Repair',
            'description' => 'Looking for car repair services in Noida? Auto Car Repair offers professional detailing, maintenance, and repairs. Book your service today for quality care.',
            'keywords' => 'car repair noida, auto service noida, car maintenance noida, car detailing noida'
        ];
        
        return $this->locationPage('noida', 'Auto Car Repair Noida', $seoData);
    }

    public function okhla()
    {
        $seoData = [
            'title' => 'Car Repair and Detailing Near Okhla - Auto Car Repair',
            'description' => 'Get professional car repair services near Okhla with Auto Car Repair. From routine maintenance to advanced repairs, we ensure your car stays in top condition.',
            'keywords' => 'car repair okhla, auto service okhla, car maintenance delhi, car detailing okhla'
        ];
        
        return $this->locationPage('okhla', 'Auto Car Repair Okhla', $seoData);
    }
    
    // Helper method to handle location pages
    private function locationPage($location, $title, $seoData = null)
    {
        $return_data = array();
        $return_data['site_title'] = trans($title);
        $service_center = ServiceCenterDetail::orderBy('id','desc')->get();
        $return_data['auto-car-repair-'.$location] = $service_center;
        
        // Add service categories
        $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
            ->where([
                ['is_archive', Constant::NOT_ARCHIVE],
                ['status', Constant::ACTIVE]
            ])
            ->orderBy('order_by', 'asc')
            ->get();
            
        // Set SEO data
        if ($seoData) {
            $return_data['meta_keywords'] = $seoData['keywords'] ?? null;
            $return_data['meta_description'] = $seoData['description'] ?? null;
            $return_data['meta_title'] = $seoData['title'] ?? $title;
        } else {
            // Default values if no SEO data provided
            $return_data['meta_keywords'] = "car repair, auto service, " . $location;
            $return_data['meta_description'] = "Visit Auto Car Repair in " . ucfirst($location) . " for multi-brand car services and repairs.";
            $return_data['meta_title'] = "Auto Car Repair " . ucfirst($location) . " – Multi-Brand Car Service";
        }
        
        return view('front/servicecenter/auto-car-repair-'.$location, array_merge($this->data, $return_data));
    }

    /* === API SIBLINGS === */

    public function indexApi(Request $request)
    {
        $centers = ServiceCenterDetail::orderBy('id','desc')->get();
        $seoRow  = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::SERVICE_CENTER_SEO_ID)->first();

        return response()->json([
            'success'         => true,
            'service_centers' => $centers,
            'seo'             => \App\Helpers\SeoHelper::fromModel($seoRow, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }

    public function showApi(Request $request, $id)
    {
        $center = ServiceCenterDetail::find($id);
        if (!$center) return response()->json(['success'=>false,'message'=>'Service center not found.'], 404);

        return response()->json([
            'success'        => true,
            'service_center' => $center,
            'seo' => \App\Helpers\SeoHelper::build([
                'title'       => $center->name ?? 'Service Center',
                'description' => $center->address ?? null,
                'site_name'   => $this->data['site_name'] ?? 'ACR',
                'url'         => $request->url(),
            ]),
        ]);
    }
}