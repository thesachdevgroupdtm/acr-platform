<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Models\EmailTemplates;
use App\Models\PickUpSlotSetting;
use App\Models\BookedSlot;
use Illuminate\Support\Facades\Validator;
use App\Constant;
use Auth;
use Crypt;
use DB;

class OrderController extends MainController
{
    public function list(Request $request)
    {
        $return_data = array();
        $return_data['settings'] = $this->data;
        $return_data['site_title'] = trans('My Orders');
        $user_id = auth()->user()->id;
        $return_data['orders'] = Order::with('detail', 'slotDetail')->where('user_id', $user_id)->orderBy('id', 'desc')->get();
        $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
        $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
        $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
        $return_data['aslots'] = $aslots;
        $return_data['eslots'] = $eslots;
        $return_data['mslots'] = $mslots;
        return view('front.user.orders',array_merge($this->data,$return_data)); 
    } 

    public function cancel(request $request)
    {
        $user_id = auth()->user()->id;
        $order_id = $request->id;
        $order_id = Crypt::decrypt($order_id);
        $order_data = Order::select('id', 'name', 'email', 'invoice_no')->where('id', $order_id)->first();
        if(isset($order_data->id) && $order_data->id){
            Order::where('id',$order_id)->update(array('is_complete' => 2));
            // Send email for Cancel Order - Start
            $templateStr = array('[USER]', '[INVOICE]');
            $data = array(auth()->user()->firstname.' '.auth()->user()->lastname, $order_data->invoice_no);
            $ndata = EmailTemplates::select('template')->where('label', 'cancel_order')->first();
            $html = isset($ndata->template) ? $ndata->template : NULL;
            $mailHtml = str_replace($templateStr, $data, $html);
//            print_r($mailHtml);exit;
            \Mail::to([auth()->user()->email, $this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Service Booked - '.$this->data['site_name']));
            // Send email for Cancel Order - End
            return redirect()->back()->with('success', trans('Order cancelled successfully.'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function changeSlot(request $request){
        $bookingInfo = BookedSlot::select('id')->where([['order_id', $request->order_id], ['user_id', auth()->user()->id]])->first();
        $booking_id = isset($bookingInfo->id) && $bookingInfo->id ? $bookingInfo->id : NULL;
        if($booking_id){
            $slot_time = $request->slot_time;
            $slot_time = str_replace(' ', '', $slot_time);
            $slotarray = explode('-', $slot_time);
            $slot2 = isset($slotarray[1]) ? $slotarray[1] : NULL;
            $time_type = 1;
            if( $slot2 && strpos( $slot2, "AM" ) !== false) {
                $time_type = 0;
            }
            $slot2 = str_replace('AM', '', $slot2);
            $slot2 = str_replace('PM', '', $slot2);

            $slot = BookedSlot::find($booking_id);
            $slot->slot_date = $request->slot_date;
            $slot->pick_up_time1 = isset($slotarray[0]) ? $slotarray[0] : NULL;
            $slot->pick_up_time2 = $slot2;
            $slot->time_type = $time_type;
            $slot->save();

            if($slot){

                // Send email for Time Rearrange - Start
                $serviceInfo = BookedSlot::with('order')->select('*')->where('id', $booking_id)->first();
                $user = isset($serviceInfo->order->name) && $serviceInfo->order->name ? $serviceInfo->order->name : NULL;
                $invoice_no = isset($serviceInfo->order->invoice_no) && $serviceInfo->order->invoice_no ? $serviceInfo->order->invoice_no : NULL;
                $email = isset($serviceInfo->order->email) && $serviceInfo->order->email ? $serviceInfo->order->email : NULL;
                $date = $request->slot_date ? date('d/m/Y', strtotime($request->slot_date)) : NULL;

                $templateStr = array('[USER]', '[Your Company Name]', '[INVOICE-NO]', '[DATE]', '[TIME]');
                $data = array($user, $this->data['site_name'], $invoice_no, $date, $request->slot_time);
                $ndata = EmailTemplates::select('template')->where('label', 'time_rearrange')->first();
                $html = isset($ndata->template) ? $ndata->template : NULL;
                $mailHtml = str_replace($templateStr, $data, $html);
                //print_r($mailHtml);exit;
                \Mail::to([$email,$this->data['email']])->send(new \App\Mail\CommonMail($mailHtml, 'Time Rearrange - '.$this->data['site_name']));
                // Send email for Time Rearrange - End
                return redirect()->back()->with('success', 'Slot Information updated successfully!');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again later!');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again later!');
        }
    }

    /* === API SIBLINGS === */

    public function listApi(Request $request)
    {
        $orders = Order::with('detail','slotDetail')
            ->where('user_id', $request->user()->id)
            ->orderBy('id','desc')->get();

        return response()->json(['success'=>true,'orders'=>$orders]);
    }

    public function showApi(Request $request, $id)
    {
        $order = Order::with('detail','slotDetail')
            ->where('id',$id)->where('user_id',$request->user()->id)->first();
        if (!$order) return response()->json(['success'=>false,'message'=>'Order not found.'], 404);
        return response()->json(['success'=>true,'order'=>$order]);
    }

    public function cancelApi(Request $request, $id)
    {
        $order = Order::where('id',$id)->where('user_id',$request->user()->id)->first();
        if (!$order) return response()->json(['success'=>false,'message'=>'Order not found.'], 404);
        $order->is_complete = 2;
        $order->save();
        return response()->json(['success'=>true,'message'=>'Order cancelled.']);
    }

    public function rescheduleApi(Request $request, $id)
    {
        $v = \Validator::make($request->all(), [
            'slot_date' => 'required|date_format:Y-m-d',
            'slot_time' => 'required|string|max:50',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $booking = BookedSlot::where('order_id',$id)->where('user_id',$request->user()->id)->first();
        if (!$booking) return response()->json(['success'=>false,'message'=>'Booking not found.'], 404);

        $clean = str_replace(' ','', (string)$request->slot_time);
        $parts = explode('-', $clean);
        $end   = $parts[1] ?? null;
        $type  = (stripos((string)$end,'AM') !== false) ? 0 : 1;
        $end   = str_ireplace(['AM','PM'],'',(string)$end);

        $booking->slot_date     = $request->slot_date;
        $booking->pick_up_time1 = $parts[0] ?? null;
        $booking->pick_up_time2 = $end;
        $booking->time_type     = $type;
        $booking->save();

        return response()->json(['success'=>true,'booking'=>$booking]);
    }
}
