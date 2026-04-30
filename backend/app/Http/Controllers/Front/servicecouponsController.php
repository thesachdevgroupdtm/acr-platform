<?php



namespace App\Http\Controllers\Front;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Constant;



class servicecouponsController extends MainController

{

public function index()
{
    $return_data = array();
    $return_data['settings'] = $this->data;
    $return_data['site_title'] = trans('acrcoupons');

    // Add service categories
    $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
        ->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])
        ->orderBy('order_by', 'asc')
        ->get();

    // Set SEO meta data for Service Coupons page
    $return_data['meta_title'] = 'Service Coupons for Car Repair & Maintenance – Grab Offers';
    $return_data['meta_description'] = 'Save on car services with our exclusive coupons. Discounts on AC service, battery replacement, denting & painting, and more at Auto Car Repair. Book now';
    $return_data['meta_keywords'] = 'car service coupons, car repair discounts, maintenance offers, auto service deals, battery replacement offers, denting and painting discounts';

    return view('front.service-coupons.index', array_merge($this->data, $return_data));
}


}