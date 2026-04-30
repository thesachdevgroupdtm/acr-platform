<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\HomePageSetting;

class MainController extends Controller
{
    public function __construct(){
        $this->data = array();
        $setting_list = array();
        $setting_list = getSettingDetail();
        $footer_detail = HomePageSetting::select('footer_description')->where('id', 1)->first();
        $footer_description = isset($footer_detail->footer_description) ? $footer_detail->footer_description : NULL;
        $setting_list['footer_description'] = $footer_description;
        $setting_list['contact_cookie_id'] = request()->cookie('contact_id');
        
        $this->data = $setting_list;
    }
}
