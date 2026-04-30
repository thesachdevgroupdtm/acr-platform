<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\faqcontent;
use App\Constant;

class faqcontentController extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['site_title'] = trans('faqcontents');
        $return_data['faqcontents'] = faqcontent::select('id','name','description')->where('is_archive','0')->orderBy('updated_at','desc')->get();
        return view('front/faqcontent/index',array_merge($this->data,$return_data));
    }
}
