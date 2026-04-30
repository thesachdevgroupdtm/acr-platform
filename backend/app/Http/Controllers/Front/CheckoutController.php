<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\UserAddress;
use App\Models\PickUpSlotSetting;
use App\Models\BookedSlot;
use App\Models\ScheduledPackage;
use App\Models\ScheduledPackageDetail;
use App\Models\OrderLog;
use App\Models\OrderDetailLog;
use App\Models\BookedSlotLog;
use App\Models\EmailTemplates;
use App\Models\User;
use App\Models\ServiceCategory;
use App\Constant;
use PDF;
use Auth;
use Session;

class CheckoutController extends MainController
{
    public function index(request $request)
    {
        $return_data = array();
        $return_data['site_title'] = trans('Checkout');
        $pcart = Session::get('pcart');
        $scart = Session::get('scart');
        
        if(empty($pcart) && empty($scart)){
            return redirect('/');
        }

        $cart_ids = array();
        if(isset($scart['cart_id']) && $scart['cart_id']){
            array_push($cart_ids, $scart['cart_id']);
        }

        if($pcart && is_array($pcart)){
            foreach($pcart as $pval){
                if(isset($pval['cart_id']) && $pval['cart_id']){
                    array_push($cart_ids, $pval['cart_id']);
                }
            }
        }

        $cart_data = Cart::whereIn('id', $cart_ids)->get();
        
        $return_data['cart_data'] = $cart_data;

        $user_id = Auth::guard('user')->check() ? Auth::guard('user')->user()->id : NULL;

        if($user_id){
            $addresses = UserAddress::where('user_id', $user_id)->get();
            $return_data['addresses'] = $addresses;
        }

        $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
        $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
        $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
        $return_data['aslots'] = $aslots;
        $return_data['eslots'] = $eslots;
        $return_data['mslots'] = $mslots;

        return view('front/checkout/index',array_merge($this->data,$return_data));
    }

    public function cartAjaxHtml(request $request)
    {
        if($request->ajax()){
            $status = 'success';
            $pcart = Session::get('pcart');
            $scart = Session::get('scart');

            if(empty($pcart) && empty($scart)){
                $status = 'error';
            }

            $cart_ids = array();

            if($scart && is_array($scart)){
                foreach($scart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            if($pcart && is_array($pcart)){
                foreach($pcart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            $cart_data = Cart::with('productDetail', 'serviceDetail')->whereIn('id', $cart_ids)->get();
            $return_data = array();
            $return_data['cart_data'] = $cart_data;
            $html = view('front/checkout/cart_ajax', array_merge($this->data, $return_data))->render();
            echo json_encode(array('status' => $status, 'html' => $html));
            exit;
        } else {
            return redirect('/');
        }
    }

    public function createOrder(request $request)
    {
        $this->validate($request, [
            'city' => 'required|regex:/^[\pL\s\-]+$/u',
        ],['required'  => trans('city allowed only character')]);

        $pcart = Session::get('pcart');
        $scart = Session::get('scart');

        if(empty($pcart) && empty($scart)){
            return redirect('/');
        }

        $payment_type = $request->payment_type;
        $usercheck = User::where('email',$request->email)->first();

        if($usercheck !== null) {
            $user_id = $usercheck->id;
        } elseif (Auth::guard('user')->check()) {
            $user_id = Auth::guard('user')->user()->id;
        } else {
            $randomString = Str::random(8);
            $randomString .= rand(0, 9);
            $randomString .= Str::random(1, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $randomString .= Str::random(1, 'abcdefghijklmnopqrstuvwxyz');
            $specialChars = '!@#$%&*';
            $randomString .= $specialChars[rand(0, strlen($specialChars) - 1)];
            $genratepassword = str_shuffle($randomString);

            $user = new User();
            $user->firstname = $request->input('name');
            $user->phone = $request->input('mobile');
            $user->email = $request->input('email');
            $user->password = Hash::make($genratepassword);
            $user->visible_password = $genratepassword;
            $user->password_active = 1;
            $user->save();

            $templateStr = array('[USER]', '[Your Company Name]', '[PASSWORD]');
            $data = array($request->name, $this->data['site_name'], $genratepassword);
            $ndata = EmailTemplates::select('template')->where('label', 'password')->first();
            $html = isset($ndata->template) ? $ndata->template : NULL;
            $mailHtml = str_replace($templateStr, $data, $html);
            \Mail::to($request->email)->send(new \App\Mail\CommonMail($mailHtml, 'Welcome '.$this->data['site_name']));
            $user_id = $user->id;
        }

        $checkout_type = $user_id ? Constant::USER_CHECKOUT : Constant::GUEST_CHECKOUT;

        if($payment_type == Constant::OFFLINE){
            $order = new Order();
            $order->user_id = $user_id;
            $order->is_guest_chekout = $checkout_type;
            $order->payment_type = $payment_type;
            $order->name = $request->name;
            $order->email = $request->email;
            $order->phone = $request->mobile;
            $order->address = $request->address;
            $order->zip = $request->zip;
            $order->city = $request->city;
            $order->subtotal = $request->subtotal;
            $order->product_gst_rate = isset($this->data['product_gst']) ? $this->data['product_gst'] : 0;
            $order->service_gst_rate = isset($this->data['service_gst']) ? $this->data['service_gst'] : 0;
            $order->product_gst = $request->product_gst;
            $order->service_gst = $request->service_gst;
            $order->total = $request->order_total;
            $order->order_date = date('Y-m-d');
            $order->vehicle_number = $request->vehicle_number;
            $order->save();

            HomeController::sendDataToFreshFork($request);

            $cart_ids = array();

            if($scart && is_array($scart)){
                foreach($scart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            if($pcart && is_array($pcart)){
                foreach($pcart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            $cart_data = Cart::with('productDetail')->whereIn('id', $cart_ids)->get();
            if($cart_data->count() && $order){
                $order_id = $order->id;
                $id_length = strlen((string)$order_id);
                $iorder_id = $id_length == 1 ? '0'.$order_id : $order_id;
                $invoice_no = 'ACR'.date('y').$iorder_id;

                Order::where('id', $order_id)->update(array('invoice_no' => $invoice_no));

                foreach($cart_data as $cdata){
                    $price = 0;
                    if($cdata->product_id){
                        $price = isset($cdata->productDetail->price) ? $cdata->productDetail->price : 0;
                    }
                    if($cdata->service_id){
                        $price = isset($cdata->serviceDetail->price) ? $cdata->serviceDetail->price : 0;
                    }
                    $qty = $cdata->qty;
                    $subtotal = $qty*$price;

                    $odetail = new OrderDetails();
                    $odetail->order_id = $order_id;
                    $odetail->product_id = $cdata->product_id;
                    $odetail->service_id = $cdata->service_id;
                    $odetail->price = $price;
                    $odetail->qty = $qty;
                    $odetail->subtotal = $subtotal;
                    $odetail->save();

                    if($cdata->service_id){
                        $slot_time = $request->slot_time;
                        $slot_time = str_replace(' ', '', $slot_time);
                        $slotarray = explode('-', $slot_time);
                        $slot2 = isset($slotarray[1]) ? $slotarray[1] : NULL;
                        $time_type = 1;
                        if($slot2 && strpos($slot2, "AM") !== false) {
                            $time_type = 0;
                        }
                        $slot2 = str_replace('AM', '', $slot2);
                        $slot2 = str_replace('PM', '', $slot2);
                        
                        $slot = new BookedSlot();
                        $slot->user_id = $user_id;
                        $slot->order_id = $order_id;
                        $slot->order_detail_id = $odetail->id;
                        $slot->slot_date = $request->slot_date;
                        $slot->pick_up_time1 = isset($slotarray[0]) ? $slotarray[0] : NULL;
                        $slot->pick_up_time2 = $slot2;
                        $slot->time_type = $time_type;
                        $slot->time_takes = isset($request->time_takes) ? $request->time_takes : NULL;
                        $slot->service_id = $cdata->service_id;
                        $slot->save();
                        
                        $package_data = ScheduledPackageDetail::with('modelDetail', 'brandDetail', 'fuelTypeDetail', 'packageDetail')->Select('id','brand_id', 'model_id', 'fuel_type_id', 'sp_id')->where('id', $cdata->service_id)->first();
                        $package = isset($package_data->packageDetail->title) && $package_data->packageDetail->title ? $package_data->packageDetail->title : NULL;
                        $model = isset($package_data->modelDetail->title) ? $package_data->modelDetail->title : NULL;
                        $brand = isset($package_data->brandDetail->title) ? $package_data->brandDetail->title : NULL;
                        $fuelType = isset($package_data->fuelTypeDetail->title) ? $package_data->fuelTypeDetail->title : NULL;
                        $service_info = $package.' - '.$brand. ' - '.$model.' - '.$fuelType;

                        $sdate = $request->slot_date ? date('d/m/Y', strtotime($request->slot_date)) : '';
                        $templateStr = array('[USER]', '[SERVICE]', '[DATE]', '[TIME]');
                        $data = array($request->name, $service_info, $sdate, $request->slot_time);
                        $ndata = EmailTemplates::select('template')->where('label', 'booked_service')->first();
                        $html = isset($ndata->template) ? $ndata->template : NULL;
                        $mailHtml = str_replace($templateStr, $data, $html);
                        \Mail::to([$request->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Service Booked - '.$this->data['site_name']));
                    }
                    Cart::where('id', $cdata->id)->delete();
                }

                $pdf_data = array();
                $pdf_data['order'] = Order::with('detail', 'slotDetail')->where('invoice_no', $invoice_no)->orderBy('id', 'desc')->first();
                $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
                $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
                $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
                $pdf_data['aslots'] = $aslots;
                $pdf_data['eslots'] = $eslots;
                $pdf_data['mslots'] = $mslots;
                $filename = $invoice_no.'.pdf';
                $pdf = PDF::loadView('front.user.pdf',array_merge($this->data, $pdf_data));

                $templateStr = array('[USER]', '[Company Name]', '[INVOICE]');
                $data = array($request->name, $this->data['site_name'], $invoice_no);
                $ndata = EmailTemplates::select('template')->where('label', 'create_order')->first();
                $html = isset($ndata->template) ? $ndata->template : NULL;
                $mailHtml = str_replace($templateStr, $data, $html);
                \Mail::to([$request->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Order - '.$this->data['site_name'],array(), $pdf->output(), $filename)); 
                
                Session::put('scart', array());
                Session::put('pcart', array());
                Session::put('cart_service_info', array());
                $address_radio = $request->address_radio;
                if(empty($address_radio) && $user_id){
                    $address = new UserAddress();
                    $address->address = $request->address;
                    $address->zip = $request->zip;
                    $address->city = $request->city;
                    $address->user_id = $user_id;
                    $address->save();
                }
                return redirect('thank-you')->with('success', 'Your order created successfully!');
            }
        } else {
            $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);

            $order = new OrderLog();
            $order->user_id = $user_id;
            $order->txn_id = $txnid;
            $order->is_guest_chekout = $checkout_type;
            $order->payment_type = $payment_type;
            $order->name = $request->name;
            $order->email = $request->email;
            $order->phone = $request->mobile;
            $order->address = $request->address;
            $order->zip = $request->zip;
            $order->city = $request->city;
            $order->subtotal = $request->subtotal;
            $order->product_gst_rate = isset($this->data['product_gst']) ? $this->data['product_gst'] : 0;
            $order->service_gst_rate = isset($this->data['service_gst']) ? $this->data['service_gst'] : 0;
            $order->product_gst = $request->product_gst;
            $order->service_gst = $request->service_gst;
            $order->total = $request->order_total;
            $order->order_date = date('Y-m-d');
            $order->vehicle_number = $request->vehicle_number;
            $order->save();

            $cart_ids = array();

            if($scart && is_array($scart)){
                foreach($scart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            if($pcart && is_array($pcart)){
                foreach($pcart as $pval){
                    if(isset($pval['cart_id']) && $pval['cart_id']){
                        array_push($cart_ids, $pval['cart_id']);
                    }
                }
            }

            $cart_data = Cart::with('productDetail')->whereIn('id', $cart_ids)->get();
            if($cart_data->count() && $order){
                $order_id = $order->id;
                $id_length = strlen((string)$order_id);
                $iorder_id = $id_length == 1 ? '0'.$order_id : $order_id;
                $invoice_no = 'ACR'.date('y').$iorder_id;

                OrderLog::where('id', $order_id)->update(array('invoice_no' => $invoice_no));

                foreach($cart_data as $cdata){
                    $price = 0;
                    if($cdata->product_id){
                        $price = isset($cdata->productDetail->price) ? $cdata->productDetail->price : 0;
                    }
                    if($cdata->service_id){
                        $price = isset($cdata->serviceDetail->price) ? $cdata->serviceDetail->price : 0;
                    }
                    $qty = $cdata->qty;
                    $subtotal = $qty*$price;

                    $odetail = new OrderDetailLog();
                    $odetail->order_id = $order_id;
                    $odetail->product_id = $cdata->product_id;
                    $odetail->service_id = $cdata->service_id;
                    $odetail->price = $price;
                    $odetail->qty = $qty;
                    $odetail->subtotal = $subtotal;
                    $odetail->save();

                    if($cdata->service_id){
                        $slot_time = $request->slot_time;
                        $slot_time = str_replace(' ', '', $slot_time);
                        $slotarray = explode('-', $slot_time);
                        $slot2 = isset($slotarray[1]) ? $slotarray[1] : NULL;
                        $time_type = 1;
                        if($slot2 && strpos($slot2, "AM") !== false) {
                            $time_type = 0;
                        }
                        $slot2 = str_replace('AM', '', $slot2);
                        $slot2 = str_replace('PM', '', $slot2);
                        
                        $slot = new BookedSlotLog();
                        $slot->user_id = $user_id;
                        $slot->order_id = $order_id;
                        $slot->order_detail_id = $odetail->id;
                        $slot->slot_date = $request->slot_date;
                        $slot->pick_up_time1 = isset($slotarray[0]) ? $slotarray[0] : NULL;
                        $slot->pick_up_time2 = $slot2;
                        $slot->time_type = $time_type;
                        $slot->time_takes = isset($request->time_takes) ? $request->time_takes : NULL;
                        $slot->service_id = $cdata->service_id;
                        $slot->save();
                    }
                    Cart::where('id', $cdata->id)->delete();
                }
                
                $address_radio = $request->address_radio;
                if(empty($address_radio) && $user_id){
                    $address = new UserAddress();
                    $address->address = $request->address;
                    $address->zip = $request->zip;
                    $address->city = $request->city;
                    $address->user_id = $user_id;
                    $address->save();
                }
            }
            
            $MERCHANT_KEY = env('PAYU_MERCHANT_KEY');
            $SALT = env('PAYU_SALT_KEY2');
            $PAYU_BASE_URL = "https://secure.payu.in";
            $action = '';
            $posted = array();
            $posted = array(
                'key' => $MERCHANT_KEY,
                'txnid' => $txnid,
                'amount' => $request->order_total,
                'firstname' => $request->name,
                'email' => $request->email,
                'productinfo' => 'PHP Project Subscribe',
                'surl' => route('payu-success-callback'),
                'furl' => route('payu-fail-callback'),
                'service_provider' => 'payu_paisa',
            );
            
            if(empty($posted['txnid'])) {
                $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
            } else {
                $txnid = $posted['txnid'];
            }
            
            $hash = '';
            $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
            
            if(empty($posted['hash']) && sizeof($posted) > 0) {
                $hashVarsSeq = explode('|', $hashSequence);
                $hash_string = '';  
                foreach($hashVarsSeq as $hash_var) {
                    $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
                    $hash_string .= '|';
                }
                $hash_string .= $SALT;
            
                $hash = strtolower(hash('sha512', $hash_string));
                $action = $PAYU_BASE_URL . '/_payment';
            } elseif(!empty($posted['hash'])) {
                $hash = $posted['hash'];
                $action = $PAYU_BASE_URL . '/_payment';
            }

            return view('front.payment', compact('posted', 'hash', 'action'));
        }
    }

    public function payuCallback(Request $request)
    {
        dd($request->all());
    }

    public function payuSuccessCallback(Request $request)
    {
        try {
            Log::info('Success Response From PayU:  ');
            Log::info($request->all());
            $txnId = $request['txnid'];
            $orderLog = OrderLog::where('txn_id', $txnId)->first();
            
            if ($orderLog) {
                $order = new Order();
                $order->user_id = $orderLog->user_id;
                $order->txn_id = $orderLog->txn_id;
                $order->invoice_no = $orderLog->invoice_no;
                $order->is_guest_chekout = $orderLog->is_guest_chekout;
                $order->payment_type = $orderLog->payment_type;
                $order->name = $orderLog->name;
                $order->email = $orderLog->email;
                $order->phone = $orderLog->phone;
                $order->address = $orderLog->address;
                $order->zip = $orderLog->zip;
                $order->city = $orderLog->city;
                $order->subtotal = $orderLog->subtotal;
                $order->product_gst_rate = $orderLog->product_gst_rate;
                $order->service_gst_rate = $orderLog->service_gst_rate;
                $order->product_gst = $orderLog->product_gst;
                $order->service_gst = $orderLog->service_gst;
                $order->total = $orderLog->total;
                $order->order_date = date('Y-m-d');
                $order->vehicle_number = $orderLog->vehicle_number;
                $order->save();
            
                $orderDetailLog = OrderDetailLog::where('order_id', $orderLog->id)->get();
            
                foreach ($orderDetailLog as $value) {
                    $odetail = new OrderDetails();
                    $odetail->order_id = $order->id;
                    $odetail->product_id = $value->product_id;
                    $odetail->service_id = $value->service_id;
                    $odetail->price = $value->price;
                    $odetail->qty = $value->qty;
                    $odetail->subtotal = $value->subtotal;
                    $odetail->save();
            
                    if ($value->service_id && $value->service_id != '') {
                        $bookedSlotLog = BookedSlotLog::where('order_id', $orderLog->id)->where('order_detail_id', $value->id)->first();
                    
                        $slot = new BookedSlot();
                        $slot->user_id = $bookedSlotLog->user_id;
                        $slot->order_id = $order->id;
                        $slot->order_detail_id = $odetail->id;
                        $slot->slot_date = $bookedSlotLog->slot_date;
                        $slot->pick_up_time1 = $bookedSlotLog->pick_up_time1;
                        $slot->pick_up_time2 = $bookedSlotLog->pick_up_time2;
                        $slot->time_type = $bookedSlotLog->time_type;
                        $slot->time_takes = $bookedSlotLog->time_takes;
                        $slot->service_id = $bookedSlotLog->service_id;
                        $slot->save();
            
                        $package_data = ScheduledPackageDetail::with('modelDetail', 'brandDetail', 'fuelTypeDetail', 'packageDetail')->Select('id','brand_id', 'model_id', 'fuel_type_id', 'sp_id')->where('id', $slot->service_id)->first();
                        $package = isset($package_data->packageDetail->title) && $package_data->packageDetail->title ? $package_data->packageDetail->title : NULL;
                        $model = isset($package_data->modelDetail->title) ? $package_data->modelDetail->title : NULL;
                        $brand = isset($package_data->brandDetail->title) ? $package_data->brandDetail->title : NULL;
                        $fuelType = isset($package_data->fuelTypeDetail->title) ? $package_data->fuelTypeDetail->title : NULL;
                        $service_info = $package.' - '.$brand. ' - '.$model.' - '.$fuelType;
            
                        $sdate = $slot->slot_date ? date('d/m/Y', strtotime($slot->slot_date)) : '';
                        $templateStr = array('[USER]', '[SERVICE]', '[DATE]', '[TIME]');
                        $slotTime = $slot->pick_up_time1 . '-' . $slot->pick_up_time2 . ($slot->time_type == 1) ? 'PM' : 'AM';
                        $data = array($order->name, $service_info, $sdate, $slotTime);
                        $ndata = EmailTemplates::select('template')->where('label', 'booked_service')->first();
                        $html = isset($ndata->template) ? $ndata->template : NULL;
                        $mailHtml = str_replace($templateStr, $data, $html);
                        \Mail::to([$order->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Service Booked - '.$this->data['site_name']));
                    }
                }
                
                $pdf_data = array();
                $pdf_data['order'] = Order::with('detail', 'slotDetail')->where('invoice_no', $order->invoice_no)->orderBy('id', 'desc')->first();
                $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
                $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
                $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
                $pdf_data['aslots'] = $aslots;
                $pdf_data['eslots'] = $eslots;
                $pdf_data['mslots'] = $mslots;
                $filename = $order->invoice_no.'.pdf';
                $pdf = PDF::loadView('front.user.pdf',array_merge($this->data, $pdf_data));
            
                $templateStr = array('[USER]', '[Company Name]', '[INVOICE]');
                $data = array($order->name, $this->data['site_name'], $order->invoice_no);
                $ndata = EmailTemplates::select('template')->where('label', 'create_order')->first();
                $html = isset($ndata->template) ? $ndata->template : NULL;
                $mailHtml = str_replace($templateStr, $data, $html);
                \Mail::to([$order->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Order - '.$this->data['site_name'],array(), $pdf->output(), $filename)); 
            
                Session::put('scart', array());
                Session::put('pcart', array());
                Session::put('cart_service_info', array());
                return redirect('thank-you')->with('success', 'Your order created successfully!');
            }
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return redirect('/')->with('error', 'There was an issue processing your order. Please try again.');
        }
    }

    public function payuFailCallback(Request $request)
    {
        Log::error('payuFailCallback method: ' . $e->getMessage());
        return redirect('/')->with('error', 'Payment failed. Please try again.');
    }

    public function thankYou(Request $request)
    {
        $return_data = array();
        $return_data['site_title'] = trans('Thank you');
        
        // Load service categories for dropdown - same as other controllers
        $return_data['scategories'] = ServiceCategory::select(
            'id', 
            'slug', 
            'title', 
            'image', 
            'icon_image', 
            'description'
        )
        ->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])
        ->orderBy('order_by', 'asc')
        ->get();
        
        return view('front/thank_you', array_merge($this->data, $return_data));
    }

    public function getAvailableSlot(request $request)
    {
        if($request->ajax()){
            $date = $request->date;
            $today = date('Y-m-d');
            $slot_ids = array();

            if($date == $today){
                $slots = PickUpSlotSetting::select('id', 'time', 'slot')->orderBy('id')->get();
                foreach($slots as $slot){
                    $slot_time = $slot->time;
                    $slot_time = str_replace(' ', '', $slot_time);
                    $slotarray = explode('-', $slot_time);
                    $slot1 = isset($slotarray[0]) ? $slotarray[0] : NULL;
                    $slot2 = isset($slotarray[1]) ? $slotarray[1] : NULL;
                    $slot2 = str_replace('PM', '', $slot2);
                    $time_type = 'PM';

                    if($slot2 && strpos($slot2, "AM") !== false) {
                        $time_type = 'AM';
                    }

                    $slot2 = str_replace('AM', '', $slot2);
                    $stime1 = $today.' '.$slot1.':00 '.$time_type;
                    $stime2 = $today.' '.$slot2.':00 '.$time_type;
                    $current = time();

                    if($current <= strtotime($stime1)){
                        array_push($slot_ids, $slot->id);
                    } else if($current <= strtotime($stime2)){
                        array_push($slot_ids, $slot->id);
                    }
                }

                $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->whereIn('id',$slot_ids)->orderBy('id')->get();
                $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->whereIn('id',$slot_ids)->orderBy('id')->get();
                $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->whereIn('id',$slot_ids)->orderBy('id')->get();
            } else {
                $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
                $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
                $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
            }

            $data = array('aslots' => $aslots, 'eslots' => $eslots, 'mslots' => $mslots);
            $html = view('front/checkout/slot_ajax', array_merge($this->data, $data))->render();
            $total_slots = $aslots->count() + $eslots->count() + $mslots->count();
            echo json_encode(array('html' => $html, 'total_slots' => $total_slots));
            exit;
        } else {
            return redirect('/');
        }
    }

    /* ===================================================================
     * API SIBLINGS — token-auth, no PHP session usage.
     * =================================================================== */

    public function summaryApi(Request $request)
    {
        $userId = $request->user()->id;
        $cart = Cart::with('productDetail','serviceDetail')
            ->where('user_id', $userId)->get();

        if ($cart->isEmpty()) {
            return response()->json(['success'=>false,'message'=>'Cart is empty.'], 400);
        }

        return response()->json([
            'success'   => true,
            'items'     => $cart,
            'totals'    => $this->cartTotalsApi($cart),
            'addresses' => UserAddress::where('user_id',$userId)->get(),
        ]);
    }

    public function availableSlotsApi(Request $request)
    {
        $v = \Validator::make($request->all(), ['date' => 'required|date_format:Y-m-d']);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $date  = $request->date;
        $today = date('Y-m-d');

        $base = PickUpSlotSetting::select('id','time','slot');

        if ($date === $today) {
            $allSlots = (clone $base)->orderBy('id')->get();
            $now = time();
            $okIds = [];
            foreach ($allSlots as $s) {
                $clean = str_replace(' ','', (string) $s->time);
                $parts = explode('-', $clean);
                $start = $parts[0] ?? null;
                $end   = $parts[1] ?? null;
                $endType = (stripos((string)$end, 'AM') !== false) ? 'AM' : 'PM';
                $end = str_ireplace(['AM','PM'],'',(string)$end);
                $sTs = strtotime("$today $start:00 $endType");
                $eTs = strtotime("$today $end:00 $endType");
                if ($now <= $sTs || $now <= $eTs) $okIds[] = $s->id;
            }
            $morning   = (clone $base)->where('slot', Constant::MORNING)->whereIn('id',$okIds)->orderBy('id')->get();
            $afternoon = (clone $base)->where('slot', Constant::AFTERNOON)->whereIn('id',$okIds)->orderBy('id')->get();
            $evening   = (clone $base)->where('slot', Constant::EVENING)->whereIn('id',$okIds)->orderBy('id')->get();
        } else {
            $morning   = (clone $base)->where('slot', Constant::MORNING)->orderBy('id')->get();
            $afternoon = (clone $base)->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
            $evening   = (clone $base)->where('slot', Constant::EVENING)->orderBy('id')->get();
        }

        return response()->json([
            'success' => true,
            'slots'   => [
                'morning'   => $morning,
                'afternoon' => $afternoon,
                'evening'   => $evening,
            ],
            'total' => $morning->count() + $afternoon->count() + $evening->count(),
        ]);
    }

    public function createOfflineApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email',
            'mobile'         => 'required|digits:10',
            'address'        => 'required|string|max:1000',
            'city'           => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'zip'            => 'required|string|max:20',
            'subtotal'       => 'required|numeric|min:0',
            'order_total'    => 'required|numeric|min:0',
            'product_gst'    => 'nullable|numeric',
            'service_gst'    => 'nullable|numeric',
            'vehicle_number' => 'nullable|string|max:50',
            'slot_date'      => 'nullable|date_format:Y-m-d',
            'slot_time'      => 'nullable|string|max:50',
            'time_takes'     => 'nullable|string|max:50',
            'address_id'     => 'nullable|integer',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $user = $request->user();
        $userId = $user->id;
        $cart = Cart::with('productDetail','serviceDetail')->where('user_id',$userId)->get();
        if ($cart->isEmpty()) return response()->json(['success'=>false,'message'=>'Cart is empty.'], 400);

        $settings = function_exists('getSettingDetail') ? getSettingDetail() : [];

        $order = \DB::transaction(function() use ($request,$cart,$userId,$settings) {
            $order = new Order();
            $order->user_id          = $userId;
            $order->is_guest_chekout = Constant::USER_CHECKOUT;
            $order->payment_type     = Constant::OFFLINE;
            $order->name             = $request->name;
            $order->email            = $request->email;
            $order->phone            = $request->mobile;
            $order->address          = $request->address;
            $order->zip              = $request->zip;
            $order->city             = $request->city;
            $order->subtotal         = $request->subtotal;
            $order->product_gst_rate = $settings['product_gst'] ?? 0;
            $order->service_gst_rate = $settings['service_gst'] ?? 0;
            $order->product_gst      = $request->product_gst ?? 0;
            $order->service_gst      = $request->service_gst ?? 0;
            $order->total            = $request->order_total;
            $order->order_date       = date('Y-m-d');
            $order->vehicle_number   = $request->vehicle_number;
            $order->save();

            $invoiceNo = 'ACR'.date('y').str_pad((string)$order->id,2,'0',STR_PAD_LEFT);
            $order->invoice_no = $invoiceNo;
            $order->save();

            foreach ($cart as $row) {
                $price = 0;
                if ($row->product_id) $price = $row->productDetail->price ?? 0;
                elseif ($row->service_id) $price = $row->serviceDetail->price ?? 0;

                $od = new OrderDetails();
                $od->order_id   = $order->id;
                $od->product_id = $row->product_id;
                $od->service_id = $row->service_id;
                $od->price      = $price;
                $od->qty        = $row->qty;
                $od->subtotal   = $price * $row->qty;
                $od->save();

                if ($row->service_id && $request->slot_date && $request->slot_time) {
                    $this->bookSlotApi(BookedSlot::class, $userId, $order->id, $od->id, $request, $row->service_id);
                }
                $row->delete();
            }

            if (!$request->address_id) {
                $a = new UserAddress();
                $a->user_id = $userId;
                $a->address = $request->address;
                $a->city    = $request->city;
                $a->zip     = $request->zip;
                $a->save();
            }
            return $order->fresh(['detail','slotDetail']);
        });

        return response()->json(['success'=>true,'order'=>$order]);
    }

    public function createOnlineApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email',
            'mobile'         => 'required|digits:10',
            'address'        => 'required|string|max:1000',
            'city'           => 'required|string|max:255',
            'zip'            => 'required|string|max:20',
            'subtotal'       => 'required|numeric|min:0',
            'order_total'    => 'required|numeric|min:0',
            'product_gst'    => 'nullable|numeric',
            'service_gst'    => 'nullable|numeric',
            'vehicle_number' => 'nullable|string|max:50',
            'slot_date'      => 'nullable|date_format:Y-m-d',
            'slot_time'      => 'nullable|string|max:50',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $user = $request->user();
        $userId = $user->id;
        $cart = Cart::with('productDetail','serviceDetail')->where('user_id',$userId)->get();
        if ($cart->isEmpty()) return response()->json(['success'=>false,'message'=>'Cart is empty.'], 400);

        $settings = function_exists('getSettingDetail') ? getSettingDetail() : [];
        $txnId = substr(hash('sha256', mt_rand().microtime()), 0, 20);

        $log = \DB::transaction(function() use ($request,$cart,$userId,$settings,$txnId) {
            $log = new OrderLog();
            $log->user_id          = $userId;
            $log->txn_id           = $txnId;
            $log->is_guest_chekout = Constant::USER_CHECKOUT;
            $log->payment_type     = Constant::ONLINE;
            $log->name             = $request->name;
            $log->email            = $request->email;
            $log->phone            = $request->mobile;
            $log->address          = $request->address;
            $log->zip              = $request->zip;
            $log->city             = $request->city;
            $log->subtotal         = $request->subtotal;
            $log->product_gst_rate = $settings['product_gst'] ?? 0;
            $log->service_gst_rate = $settings['service_gst'] ?? 0;
            $log->product_gst      = $request->product_gst ?? 0;
            $log->service_gst      = $request->service_gst ?? 0;
            $log->total            = $request->order_total;
            $log->order_date       = date('Y-m-d');
            $log->vehicle_number   = $request->vehicle_number;
            $log->save();

            $invoiceNo = 'ACR'.date('y').str_pad((string)$log->id,2,'0',STR_PAD_LEFT);
            $log->invoice_no = $invoiceNo;
            $log->save();

            foreach ($cart as $row) {
                $price = 0;
                if ($row->product_id) $price = $row->productDetail->price ?? 0;
                elseif ($row->service_id) $price = $row->serviceDetail->price ?? 0;

                $od = new OrderDetailLog();
                $od->order_id   = $log->id;
                $od->product_id = $row->product_id;
                $od->service_id = $row->service_id;
                $od->price      = $price;
                $od->qty        = $row->qty;
                $od->subtotal   = $price * $row->qty;
                $od->save();

                if ($row->service_id && $request->slot_date && $request->slot_time) {
                    $this->bookSlotApi(BookedSlotLog::class, $userId, $log->id, $od->id, $request, $row->service_id);
                }
                $row->delete();
            }
            return $log->fresh();
        });

        $merchantKey = env('PAYU_MERCHANT_KEY');
        $salt        = env('PAYU_SALT_KEY2');
        $payuBase    = 'https://secure.payu.in';

        $payload = [
            'key'              => $merchantKey,
            'txnid'            => $txnId,
            'amount'           => $request->order_total,
            'firstname'        => $request->name,
            'email'            => $request->email,
            'productinfo'      => 'ACR Order',
            'surl'             => url('/payu-success-callback'),
            'furl'             => url('/payu-fail-callback'),
            'service_provider' => 'payu_paisa',
        ];

        $hashSeq = 'key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10';
        $hashStr = '';
        foreach (explode('|', $hashSeq) as $k) $hashStr .= ($payload[$k] ?? '').'|';
        $hashStr .= $salt;
        $hash = strtolower(hash('sha512', $hashStr));

        return response()->json([
            'success' => true,
            'order'   => $log,
            'payu'    => [
                'action' => $payuBase.'/_payment',
                'fields' => array_merge($payload, ['hash' => $hash]),
            ],
        ]);
    }

    protected function bookSlotApi(string $modelClass, $userId, $orderId, $detailId, Request $request, $serviceId)
    {
        $clean = str_replace(' ','', (string)$request->slot_time);
        $parts = explode('-', $clean);
        $end   = $parts[1] ?? null;
        $type  = (stripos((string)$end,'AM') !== false) ? 0 : 1;
        $end   = str_ireplace(['AM','PM'],'',(string)$end);

        /** @var \Illuminate\Database\Eloquent\Model $slot */
        $slot = new $modelClass();
        $slot->user_id         = $userId;
        $slot->order_id        = $orderId;
        $slot->order_detail_id = $detailId;
        $slot->slot_date       = $request->slot_date;
        $slot->pick_up_time1   = $parts[0] ?? null;
        $slot->pick_up_time2   = $end;
        $slot->time_type       = $type;
        $slot->time_takes      = $request->time_takes;
        $slot->service_id      = $serviceId;
        $slot->save();
    }

    protected function cartTotalsApi($cart)
    {
        $subtotal = 0;
        foreach ($cart as $row) {
            $price = 0;
            if ($row->product_id && isset($row->productDetail->price)) {
                $price = (float) $row->productDetail->price;
            } elseif ($row->service_id && isset($row->serviceDetail->price)) {
                $price = (float) $row->serviceDetail->price;
            }
            $subtotal += $price * (int)$row->qty;
        }
        return ['subtotal'=>$subtotal,'count'=>$cart->count()];
    }
}