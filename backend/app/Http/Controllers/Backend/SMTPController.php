<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SMTP;
use App\Constant;
use Auth;

class SMTPController extends MainController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $return_data = array();
        $return_data['smtps'] = SMTP::orderBy('id', 'asc')->get();

        $env_files = [
            'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
            'MAIL_MAILER' => env('MAIL_MAILER'),
            'MAIL_HOST' => env('MAIL_HOST'),
            'MAIL_PORT' => env('MAIL_PORT'),
            'MAIL_USERNAME' => env('MAIL_USERNAME'),
            'MAIL_PASSWORD' => env('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
          ];

        $return_data['env_files'] = $env_files;

        $return_data['site_title'] = trans('SMTP Mail Settings');
        return view('backend/smtp/smtpchanges', array_merge($this->data, $return_data));
    }

    public function update(Request $request)
    {
        $mailsetting = SMTP::first();
        if(empty($mailsetting)){
            $mailsetting = new SMTP();
        }
        $mailsetting->sender_name = $request->MAIL_FROM_NAME;
        $mailsetting->mail_address = $request->MAIL_FROM_ADDRESS;
        $mailsetting->mail_mailer = $request->MAIL_MAILER;
        $mailsetting->mail_username = $request->MAIL_USERNAME;
        $mailsetting->mail_host = $request->MAIL_HOST;
        $mailsetting->mail_password = $request->MAIL_PASSWORD;
        $mailsetting->mail_port = $request->MAIL_PORT;
        $mailsetting->mail_enc = $request->MAIL_ENCRYPTION;
        $mailsetting->save();

        $input = $request->all();
        $env_update = $this->changeEnv([
                'MAIL_FROM_NAME' => str_replace(' ', '-', $input['MAIL_FROM_NAME']),
                'MAIL_FROM_ADDRESS' => $input['MAIL_FROM_ADDRESS'],
                'MAIL_MAILER' => $input['MAIL_MAILER'],
                'MAIL_HOST' => $input['MAIL_HOST'],
                'MAIL_PORT' => $input['MAIL_PORT'],
                'MAIL_USERNAME'=> $input['MAIL_USERNAME'],
                'MAIL_PASSWORD'=> $input['MAIL_PASSWORD'],
                'MAIL_ENCRYPTION'=> $input['MAIL_ENCRYPTION']
            ]);
    
        if($env_update) {
            return redirect()->back()->with('success', trans('SMTP Mail Settings Updated Successfully!'));
        } else {
            return redirect()->back()->with('errors', trans('SMTP Mail Settings not Updated'));
        }
    }
    protected function changeEnv($data = array()){{
            if ( count($data) > 0 ) {
                $env = file_get_contents(base_path() . '/.env');
                $env = preg_split('/\s+/', $env);;
                foreach((array)$data as $key => $value){
                    foreach($env as $env_key => $env_value){
                        $entry = explode("=", $env_value, 2);
                        if($entry[0] == $key){
                            $env[$env_key] = $key . "=" . $value;
                        } else {
                            $env[$env_key] = $env_value;
                        }
                    }
                }

                $env = implode("\n\n", $env);
                file_put_contents(base_path() . '/.env', $env);

                return true;
            } else {
                return false;
            }
        }
    }
}