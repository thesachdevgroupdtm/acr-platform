<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;

class insuranceController extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['settings'] = $this->data;
        $return_data['site_title'] = trans('insurance');

        return view('front/insurance/index',array_merge($this->data,$return_data));
    }
}