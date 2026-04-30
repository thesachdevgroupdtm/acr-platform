<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use Msg91;
use Illuminate\Support\Facades\Validator;

class OtpController extends MainController
{
    public function send(request $request)
    {
        if($request->ajax()){
            $mobile = $request->mobile;
            $template = env("MSG91_TEMPLATE_ID");
            try {
                $response = Msg91::otp()
                    ->to('91'.$mobile) // phone number with country code
                    ->template($template) // set the otp template
                    ->send();
                $info = $response->getData();
                if(isset($info['type']) && $info['type'] == 'success'){
                    $return['result'] = 'success';
                }
            } catch (\Craftsys\Msg91\Exceptions\ValidationException $e) {
                // issue with the request e.g. token not provided
                $return = array('result' => 'error');
            } catch (\Craftsys\Msg91\Exceptions\ResponseErrorException $e) {
                // error thrown by msg91 apis or by http client
                $return = array('result' => 'error');
            } catch (\Exception $e) {
                // something else went wrong
                // plese report if this happens :)
                $return = array('result' => 'error');
            }

            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }

    public function verify(request $request)
    {
        if($request->ajax()){
            $mobile = $request->mobile;
            $otp = (int)$request->otp;
            $template = env("MSG91_TEMPLATE_ID");
            try {
                $response = Msg91::otp($otp) // OTP to be verified
                        ->to('91'.$mobile) // phone number with country code
                        ->verify();
                $info = $response->getData();
                if(isset($info['type']) && $info['type'] == 'success'){
                    $return['result'] = 'success';
                }
            } catch (\Craftsys\Msg91\Exceptions\ValidationException $e) {
                // issue with the request e.g. token not provided
                $return = array('result' => 'error');
            } catch (\Craftsys\Msg91\Exceptions\ResponseErrorException $e) {
                // error thrown by msg91 apis or by http client
                $return = array('result' => 'error');
            } catch (\Exception $e) {
                // something else went wrong
                // plese report if this happens :)
                $return = array('result' => 'error');
            }

            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }

    public function resend(request $request)
    {
        if($request->ajax()){
            $mobile = $request->mobile;
            $template = env("MSG91_TEMPLATE_ID");
            try {
                $response = Msg91::otp()
                        ->to('91'.$mobile) // phone number with country code
                        ->template($template) // set the otp template
                        ->resend();
                $info = $response->getData();
                if(isset($info['type']) && $info['type'] == 'success'){
                    $return['result'] = 'success';
                }
            } catch (\Craftsys\Msg91\Exceptions\ValidationException $e) {
                // issue with the request e.g. token not provided
                $return = array('result' => 'error');
            } catch (\Craftsys\Msg91\Exceptions\ResponseErrorException $e) {
                // error thrown by msg91 apis or by http client
                $return = array('result' => 'error');
            } catch (\Exception $e) {
                // something else went wrong
                // plese report if this happens :)
                $return = array('result' => 'error');
            }
            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }
    
    public function bookAppointmentSend(request $request)
    {
        if($request->ajax()){
            $this->validate($request, [
                'gresponse' => 'required|recaptcha'
            ], [
                'gresponse.required' => 'Please complete the captcha',
                'gresponse.recaptcha' => 'Captcha verification failed',
            ]);
            $mobile = $request->mobile;
            $template = env("MSG91_TEMPLATE_ID");
            try {
                $response = Msg91::otp()
                    ->to('91'.$mobile) // phone number with country code
                    ->template($template) // set the otp template
                    ->send();
                $info = $response->getData();
                if(isset($info['type']) && $info['type'] == 'success'){
                    $return['result'] = 'success';
                }
            } catch (\Craftsys\Msg91\Exceptions\ValidationException $e) {
                // issue with the request e.g. token not provided
                $return = array('result' => 'error');
            } catch (\Craftsys\Msg91\Exceptions\ResponseErrorException $e) {
                // error thrown by msg91 apis or by http client
                $return = array('result' => 'error');
            } catch (\Exception $e) {
                // something else went wrong
                // plese report if this happens :)
                $return = array('result' => 'error');
            }

            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }
    
    public function bookAppointmentResend(request $request)
    {
        if($request->ajax()){
            $this->validate($request, [
                'gresponse' => 'required|recaptcha'
            ], [
                'gresponse.required' => 'Please complete the captcha',
                'gresponse.recaptcha' => 'Captcha verification failed',
            ]);
            $mobile = $request->mobile;
            $template = env("MSG91_TEMPLATE_ID");
            try {
                $response = Msg91::otp()
                        ->to('91'.$mobile) // phone number with country code
                        ->template($template) // set the otp template
                        ->resend();
                $info = $response->getData();
                if(isset($info['type']) && $info['type'] == 'success'){
                    $return['result'] = 'success';
                }
            } catch (\Craftsys\Msg91\Exceptions\ValidationException $e) {
                // issue with the request e.g. token not provided
                $return = array('result' => 'error');
            } catch (\Craftsys\Msg91\Exceptions\ResponseErrorException $e) {
                // error thrown by msg91 apis or by http client
                $return = array('result' => 'error');
            } catch (\Exception $e) {
                // something else went wrong
                // plese report if this happens :)
                $return = array('result' => 'error');
            }
            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }

    /* === API SIBLINGS — proper response()->json() versions === */

    public function sendApi(Request $request)
    {
        $v = Validator::make($request->all(), ['mobile' => 'required|digits:10']);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $template = env('MSG91_TEMPLATE_ID');
        try {
            $resp = Msg91::otp()->to('91'.$request->mobile)->template($template)->send();
            $info = $resp->getData();
            $ok = isset($info['type']) && $info['type'] === 'success';
            return response()->json(['success'=>$ok,'message'=>$ok?'OTP sent':'Could not send OTP'], $ok?200:400);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>'Could not send OTP'], 500);
        }
    }

    public function verifyApi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'otp'    => 'required',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        try {
            $resp = Msg91::otp((int)$request->otp)->to('91'.$request->mobile)->verify();
            $info = $resp->getData();
            $ok = isset($info['type']) && $info['type'] === 'success';
            return response()->json(['success'=>$ok,'message'=>$ok?'OTP verified':'Invalid OTP'], $ok?200:400);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>'Verification failed'], 400);
        }
    }

    public function resendApi(Request $request)
    {
        $v = Validator::make($request->all(), ['mobile' => 'required|digits:10']);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $template = env('MSG91_TEMPLATE_ID');
        try {
            $resp = Msg91::otp()->to('91'.$request->mobile)->template($template)->resend();
            $info = $resp->getData();
            $ok = isset($info['type']) && $info['type'] === 'success';
            return response()->json(['success'=>$ok,'message'=>$ok?'OTP resent':'Could not resend OTP'], $ok?200:400);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>'Could not resend OTP'], 500);
        }
    }
}