<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailTemplates;
use App\Constant;
use Auth;
use Session;
use DB;

class EmailTemplatesController extends MainController
{
    public function index(){
        $return_data = array();
        $return_data['site_title'] = trans('Email Templates');
        $return_data['email_templates'] = EmailTemplates::orderby('id','desc')->get();
        return view('backend.setting.email', array_merge($this->data, $return_data));
    }

    public function update(Request $request){
        $label = $request->id;
        if($label){
            $email =  EmailTemplates::select('value')->where('label', $label)->first();
            $template = isset($email->value) && $email->value ? $email->value : NULL;

            if($request->$label) {
                DB::table('email_templates')
                    ->where('label', $label)
                    ->update(['template' => $request->$label, 'updated_by' => Auth::guard('admin')->user()->_id]);
            }
            return redirect('backend/email-templates')->with('success', trans($template.' '. trans('Email Template Updated Successfully!')));
        }
        return redirect('backend/email-templates')->with('error', trans('Something went wrong, please try again later.'));
    }
}
