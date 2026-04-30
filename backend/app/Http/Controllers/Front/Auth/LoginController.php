<?php



namespace App\Http\Controllers\Front\Auth;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Auth;

use App\Models\User;

use App\Models\EmailTemplates;
use App\Models\ServiceCategory;
use App\Models\HomePageSetting;

use App\Constant;

use Cookie;

use Session;



class LoginController extends Controller

{

    protected $data;

    public function __construct()

    {

        $this->data = [

            'site_name' => 'AUTOGINIE-SERVICES-PRIVATE-LIMITED',

        ];

        $this->middleware('guest:user', ['except' => ['logout']]);

    }



    public function showLoginForm()

    {

        $return_data = array();

        $setting_list = getSettingDetail();

        $footer_description = isset($footer_detail->footer_desc) ? $footer_detail->footer_desc : NULL;

        $setting_list['footer_description'] = $footer_description;

        $footer_detail = HomePageSetting::select('footer_description')->where('id', 1)->first();

        $this->data = $setting_list;

        $return_data['settings'] = $this->data;
  // Add service categories
  $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
  ->where([
      ['is_archive', Constant::NOT_ARCHIVE],
      ['status', Constant::ACTIVE]
  ])
  ->orderBy('order_by', 'asc')
  ->get();
        $return_data['site_title'] = trans('Login');

        return view('front.auth.login', array_merge($return_data, $this->data));

    }



    public function login(Request $request)

    {

        // Validate the form data

        $this->validate($request, [

            'email'   => 'required',

            'password' => 'required',

            'g-recaptcha-response' => 'required|recaptcha'

        ], [

            'g-recaptcha-response.required' => 'Please complete the captcha',

            'g-recaptcha-response.recaptcha' => 'Captcha verification failed',

        ]);

// 

        // Attempt to log the user in

        if (Auth::guard('user')->attempt(['email' => $request->email, 'password' => $request->password])) {

            $user_id = Auth::guard('user')->user()->id;

            $user_detail = User::where([['id', '=', $user_id], ['is_archive', Constant::NOT_ARCHIVE]])->first();

            if ($user_detail) {

                // if($user_detail->password_active == 0){

                // User::where('email', $user_detail->email)

                //         ->update([

                //                     'password' =>'',

                //                     'password_active'=>1

                //             ]);

                // }

                Cookie::queue(Cookie::forget('email'));

                Cookie::queue(Cookie::forget('password'));

                Session::put('phone', $user_detail->phone);

                session()->put('userInfo', $user_detail);

            } else {

                Auth::guard('user')->logout();

                return redirect()->back()->withInput($request->only('email', 'remember'))->withErrors(['Your account is not active.']);

            }

//            return redirect()->intended(route('front_/'));

            return redirect('/')->with('success', trans('Your Account Login Successfully!'));

        }

        // if unsuccessful, then redirect back to the login with the form data

        return redirect()->back()->withInput($request->only('email'))->withErrors(['login_error' => 'These credentials do not match our records.']);

    }



    public function logout()

    {

        Auth::guard('user')->logout();

        return redirect('/');

    }



    public function showForgetForm()

    {

        $return_data = array();

        $return_data['site_title'] = trans('Forgot Password');

        $setting_list = getSettingDetail();

        $footer_description = isset($footer_detail->footer_desc) ? $footer_detail->footer_desc : NULL;

        $setting_list['footer_description'] = $footer_description;

        $this->data = $setting_list;

        $return_data['settings'] = $this->data;



        return view('front/auth/forgot', array_merge($this->data, $return_data));

    }



    public function sendForgetLink(Request $request)

    {

        $uData = User::select('id', 'firstname', 'remember_token', 'is_archive')->where([['email', $request->email]])->first();

        $is_active = isset($uData->is_archive) ? $uData->is_archive : NULL;



        if ($is_active == Constant::NOT_ARCHIVE) {

            $user_id = isset($uData->id) && $uData->id ? $uData->id : NULL;

            if ($user_id) {

                $token = generateRandomString();

                User::where([['id', $user_id]])->update(['remember_token' => $token]);



                $uname = isset($uData->firstname) ? $uData->firstname : NULL;

                $url = route('front_reset-password', array($token));

                $link = '<a href="' . $url . '" target="_blank">Reset Password</a>';



                $templateStr = array('[USER]','[RESET-PASSWORD]');

                $data = array($uname, $link);

                $ndata = EmailTemplates::select('template')->where('label', 'forgot_password')->first();

                $html = isset($ndata->template) ? $ndata->template : NULL;

                $mailHtml = str_replace($templateStr, $data, $html);

                \Mail::to($request->email)->send(new \App\Mail\CommonMail($mailHtml, 'Forgot Password ' . $this->data['site_name']));

                return redirect()->back()->with('success', trans('Reset link has been sent to your email address.'));

            }

        }

        return redirect()->back()->withInput($request->only('email'))->withErrors(['message' => 'Please enter registered email address.']);

    }



    public function showResetPasswordForm(Request $request){

        $token = $request->token;

        if($token){

            $uData = User::select('id', 'remember_token', 'is_archive')->where([['remember_token', $token]])->first();

            $is_active = isset($uData->is_archive) ? $uData->is_archive : NULL;



            if($is_active == Constant::NOT_ARCHIVE){

                $user_id = isset($uData->id) ? $uData->id : NULL;

                if($user_id){

                    $return_data = array();

                    $setting_list = getSettingDetail();

                    $footer_description = isset($footer_detail->footer_description) ? $footer_detail->footer_description : NULL;

                    $setting_list['footer_description'] = $footer_description;

                    $this->data = $setting_list;

                    $return_data['settings'] = $this->data;

                    $return_data['user_id'] = $user_id;

                    $return_data['site_title'] = trans('Reset Password');

                    $return_data['settings'] = $this->data;

                    return view('front.auth.reset', array_merge($this->data, $return_data));

                }

            }

        }

        return redirect()->intended(route('front_forgot-password'));

    }



    public function resetPassword(Request $request){

        $user_id = \Crypt::decrypt($request->user_id);

        $visible_password = $request->password;

        $password = \Hash::make($request->password);

        User::where([['id', $user_id]])->update(['password' => $password,'visible_password' => $visible_password, 'remember_token' => NULL]);

        return redirect()->route('front_login')->with('success', trans('Your password updated successfully!'));

    }

    /* ===================================================================
     * API SIBLINGS — token-based via Sanctum.
     * Web flow above is unchanged.
     * =================================================================== */

    public function loginApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $id = strtolower(trim($request->identifier));

        $user = User::where('is_archive', Constant::NOT_ARCHIVE)
            ->where(function($q) use ($id){
                $q->where('email', $id)->orWhere('phone', $id);
            })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        $token = $user->createToken('acr-spa')->plainTextToken;

        return response()->json([
            'success' => true,
            'user'    => [
                'id'        => $user->id,
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'image'     => $user->image,
            ],
            'token' => $token,
        ]);
    }

    public function logoutApi(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success'=>true,'message'=>'Logged out']);
    }

    public function meApi(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'success' => true,
            'user'    => [
                'id'        => $u->id,
                'firstname' => $u->firstname,
                'lastname'  => $u->lastname,
                'email'     => $u->email,
                'phone'     => $u->phone,
                'image'     => $u->image,
            ],
        ]);
    }

    public function forgotApi(Request $request)
    {
        $v = \Validator::make($request->all(), ['email' => 'required|email']);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $u = User::where('email',$request->email)->where('is_archive', Constant::NOT_ARCHIVE)->first();
        if (!$u) return response()->json(['success'=>false,'message'=>'Please enter a registered email address.'], 404);

        $token = function_exists('generateRandomString') ? generateRandomString() : bin2hex(random_bytes(20));
        $u->remember_token = $token;
        $u->save();

        return response()->json(['success'=>true,'message'=>'Reset link generated.','reset_token'=>$token]);
    }

    public function resetApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'token'    => 'required|string',
            'password' => 'required|string|min:6',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $u = User::where('remember_token',$request->token)->where('is_archive', Constant::NOT_ARCHIVE)->first();
        if (!$u) return response()->json(['success'=>false,'message'=>'Invalid or expired reset token.'], 400);

        $u->password         = Hash::make($request->password);
        $u->visible_password = $request->password;
        $u->remember_token   = null;
        $u->save();

        return response()->json(['success'=>true,'message'=>'Password updated successfully.']);
    }

    public function changePasswordApi(Request $request)
    {
        $u = $request->user();
        $v = \Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|different:current_password',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        if (!Hash::check($request->current_password, $u->password)) {
            return response()->json(['success'=>false,'message'=>'Current password is incorrect.'], 400);
        }

        $u->password         = Hash::make($request->new_password);
        $u->visible_password = $request->new_password;
        $u->save();

        return response()->json(['success'=>true,'message'=>'Password updated.']);
    }

}

