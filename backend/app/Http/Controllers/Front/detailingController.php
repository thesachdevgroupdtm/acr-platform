<?php

namespace App\Http\Controllers\Front;
use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;

class detailingController extends MainController
{
public function index()
{
    $return_data = array();
    $return_data['settings'] = $this->data;
    $return_data['site_title'] = trans('detailing');

    // Add service categories
    $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
        ->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])
        ->orderBy('order_by', 'asc')
        ->get();

    // Set SEO meta data for Detailing page
    $return_data['meta_title'] = 'Professional Car Detailing Services Near You - Auto car Repair';
    $return_data['meta_description'] = 'Make your car look and feel new again with professional detailing. Interior deep clean, exterior polish, and complete care to restore its shine in Delhi NCR.';
    $return_data['meta_keywords'] = 'car detailing services, professional car cleaning, auto detailing near me, vehicle deep clean, car polish service, detailing Delhi NCR';

    return view('front.detailing.index', array_merge($this->data, $return_data));
}

}