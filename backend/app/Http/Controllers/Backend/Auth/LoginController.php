<?php

namespace App\Http\Controllers\Backend\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Route;
use App\Models\Admin;
use Mail;
use DB;
use App\Constant;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }
    
    public function showLoginForm()
    {
        $return_data = array();
        $setting_list = getSettingDetail();
        $this->data = $setting_list;
        return view('backend.auth.login', array_merge($return_data, $this->data));
    }

    public function login(Request $request)
    {
        // Validate the form data
        $this->validate($request, [
                'email'   => 'required|email',
                'password' => 'required|min:6',
            ]
        );
        // Attempt to log the user in
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password, 'is_active' => '0', 'status' => Constant::ACTIVE], $request->remember)) {
            // if successful, then redirect to their intended location
            return redirect()->intended(route('admin_dashboard'));
        }
        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->back()->withInput($request->only('email', 'remember'))->withErrors(['message' => trans('These credentials do not match our records.')]);
    }
    
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/backend/login');
    }

    public function showForgetForm()
    {
        $setting_list = getSettingDetail();
        $this->data = $setting_list;
        return view('backend.auth.forgot', $this->data);
    }

    public function sendForgetLink(Request $request){
        $setting_list = getSettingDetail();
        $this->data = $setting_list;
        $uData = Admin::select('id', 'remember_token', 'is_active')->where([['email', '=', $request->email]])->first();
        $is_active = isset($uData->is_active) && $uData->is_active ? $uData->is_active : NULL;

        if($is_active == 1){
            $user_id = isset($uData->id) && $uData->id ? $uData->id : NULL;
            if($user_id){
                $token = generateRandomString();
                DB::table('admins')->where('id', $user_id)->update(['remember_token' => $token]);

                $url = route('admin_reset-password', array($token));
                $link = '<a href="'.$url.'" target="_blank">Reset Password</a>';

                $templateStr = array('[RESET-PASSWORD]');
                $data = array($link);
                $ndata = EmailTemplates::select('template')->where('label', 'forgot_password')->first();
                $html = isset($ndata->template) ? $ndata->template : NULL;
                $mailHtml = str_replace($templateStr, $data, $html);
                \Mail::to($request->email)->send(new \App\Mail\CommonMail($mailHtml, 'Forgot Password '.$this->data['site_name']));

                return redirect()->back()->with('success', trans('A reset link has been sent to your email address.'));
            }
        }
        return redirect()->back()->withInput($request->only('email'))->withErrors(['message' => 'Please enter registered email address.']);
    }

    public function showResetPasswordForm(Request $request){
        $token = $request->token;
        if($token){
            $uData = Admin::select('id', 'remember_token', 'is_active')->where([['remember_token', $token]])->first();
            $is_active = isset($uData->is_active) && $uData->is_active ? $uData->is_active : NULL;

            if($is_active == 1){
                $user_id = isset($uData->id) ? $uData->id : NULL;
                if($user_id){
                    $setting_list = getSettingDetail();
                    $this->data = $setting_list;
                    $data = array();
                    $data['user_id'] = $user_id;
                    return view('backend.auth.reset', array_merge($this->data, $data));
                }
            }
        }
        return redirect()->intended(route('admin_forgot-password'));
    }

    public function resetPassword(Request $request){
        $user_id = \Crypt::decrypt($request->user_id);
        $password = \Hash::make($request->password);
        DB::table('admins')->where('id', $user_id)->update(['password' => $password, 'remember_token' => NULL]);
        return redirect('backend/login')->with('success', trans('Your password updated successfully!'));
    }
}
