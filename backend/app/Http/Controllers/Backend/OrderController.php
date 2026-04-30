<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PickUpSlotSetting;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use Auth;
use Session;
use DataTables;
use DB;
use PDF;

class OrderController extends MainController
{
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Orders');
        return view('backend.order.list', array_merge($this->data, $return_data));
    }

    public function orderDatatable(request $request)
    {
        if($request->ajax()){
            $query = Order::select('id','invoice_no','is_complete', 'user_id','name','email','phone','address','zip','city','total','order_date')->with('userData')->orderBy('id', 'DESC');
            if($request->user_id){
                $query->where('user_id', $request->user_id);
            }
            if($request->status!='all') {
                if($request->status=='0') {
                    $query->where([['is_complete', '=', $request->status]]);
                }
                if($request->status=='1') {
                    $query->where([['is_complete', '=', $request->status]]);
                }
                if($request->status=='2') {
                    $query->where([['is_complete', '=', $request->status]]);
                }
            }
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('id', function($row) {
                    $html = "";
                    $html .= '<label class="form-check">
                                <input class="form-check-input checkSingle" type="checkbox" value="'.$row->id.'">
                                <span class="form-check-label">
                                    '.$row->id.'
                                </span>
                            </label>';
                    return $html;
                })
                ->addColumn('invoice_no', function ($row) {
                    $html = "";
                    $html .= "<span class='text-nowrap'>";
                    $html .= "#";
                    $html .=isset($row->invoice_no) ? $row->invoice_no : NULL;
                    $html .= "</span>";
                    return $html;
                })
                ->addColumn('name', function($row) {
                    $user_id = $row->user_id;
                    $html = $user_id ? "<a href='".route('admin_user-detail', array(Crypt::encrypt($user_id)))."' target='blank'>".$row->name."</a>" : $row->name;
                    return $html;
                })
                ->addColumn('odate', function($row) {
                    $order_date = $row->order_date ? date("d/m/Y", strtotime($row->order_date)) : NULL;
                    $html = "<span class='text-nowrap'>";
                    $html .= $order_date;
                    $html .= "</span>";
                    return $html;
                })
                ->addColumn('status', function ($row) {
                    $html = $row->is_complete == Constant::YES ? '<span class="text-success">Complete</span>' : '';
                    $html .= $row->is_complete == Constant::NO ? '<span class="text-primary">Pending</span>': '';
                    $html .= $row->is_complete == Constant::CANCEL ? '<span class="text-danger">Cancelled </span>': '';
                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    // $html .= "<a href='javascript:void(0);' data-href='".route('admin_order-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm mr-20 delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    $html .= "<a href='".route('admin_order-detail',array($id))."' rel='tooltip' title='Detail' class='btn btn-info btn-sm'>Detail</a><br><br>";
                    $html .= "<a href='".url('backend/invoice/'.$row->invoice_no)."' title='View Invoice' class='badge bg-success' target='blank'>View Invoice</a><br>";
                    if($row->is_complete == Constant::NO) {
                        $html .= "<a href='javascript:void(0);' class='btn btn-warning btn-sm complete' data-status='".Constant::YES."' data-id='".$id."' rel='tooltip' title='complete'>Is omplete?</a>";
                    } 
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['invoice_no','name','status','action','odate','id'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $order = Order::where('id', $id)->delete();
        if($order) {
            return redirect()->back()->with('success', trans('Order Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function alldelete(Request $request)
    {
        if($request->ajax()){
            $orderdelete = $request->orderdelete;
            $return = array();
            $return['result'] = 'error';
            if($orderdelete){
                foreach($orderdelete as $order){
                    $orders = Order::where('id',$order)->delete();
                }
                if(isset($orders)){
                    $return['result'] = 'success';
                }
                echo json_encode($return);
                exit;
            } else {
                return redirect('/');
            }
        }
    }

    public function detail(request $request,$id)
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Order Detail');
        $id = Crypt::decrypt($id);
        $detail = Order::find($id);
        if(!isset($detail->id)){
            return redirect()->back()->with('error', 'Something went wrong, please try again later!');
        }
        $return_data['detail'] = $detail;
        return view('backend.order.detail', array_merge($this->data, $return_data));
    }

    public function orderDetailDatatable(request $request)
    {
        if($request->ajax()){
            $query = OrderDetails::with('packageDetail', 'productDetail','orderDetail')->select('id','order_id','product_id', 'service_id','price','qty','subtotal')->where('order_id', $request->order_id);
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('gst', function($row) {
                    if($row->service_id) {
                        $html = $row->orderDetail->service_gst_rate;
                    } else if($row->product_id) {
                        $html = $row->orderDetail->product_gst_rate;
                    }else{
                        $html = '';
                    }
                    return $html;
                })
                ->addColumn('invoice_no', function ($row) {
                    $html = "";
                    $html .= "<span class='text-nowrap'>";
                    $html .= "#";
                    $html .=isset($row->orderDetail->invoice_no) ? $row->orderDetail->invoice_no : NULL;
                    $html .= "</span>";
                    return $html;
                })
                ->addColumn('item', function($row) {
                    if($row->service_id){
                        $service_category = isset($row->packageDetail->packageDetail->categoryDetail->title) ? $row->packageDetail->packageDetail->categoryDetail->title : NULL;
                        $scheduled_title = isset($row->packageDetail->packageDetail->title) ? $row->packageDetail->packageDetail->title : NULL;
                        $brand = isset($row->packageDetail->brandDetail->title) ? $row->packageDetail->brandDetail->title : NULL;
                        $model = isset($row->packageDetail->modelDetail->title) ? $row->packageDetail->modelDetail->title : NULL;
                        $fuel_type = isset($row->packageDetail->fuelTypeDetail->title) ? $row->packageDetail->fuelTypeDetail->title : NULL;
                        $vehicle_number = isset($row->orderDetail->vehicle_number) ? $row->orderDetail->vehicle_number : NULL;
                        $item = "<span class='text-nowrap'>".$service_category.'/'.$scheduled_title."</br>".$brand.' - '.$model.' - '.$fuel_type."</br>".$vehicle_number."</span>";
                    } else {
                        $item = isset($row->productDetail->name) ? $row->productDetail->name : '';
                    }
                    return $item;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $user_id = isset($row->orderDetail->user_id) ? $row->orderDetail->user_id : '';
                    $html .= "<span class='text-nowrap'>";
                    if($row->service_id){
                        $html .= "<a href='".route('admin_booked-services')."?od_id=".$id."' class='badge bg-primary me-1 my-1' target='blank'>Slot Detail</a>";
                    }
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_order-detail-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm mr-20 delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['invoice_no','item','gst','action'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    // public function detailDestroy($id)
    // {
    //     $id = Crypt::decrypt($id);
    //     $orderdetail = OrderDetails::where('id', $id)->delete();
    //     if($orderdetail) {
    //         return redirect()->back()->with('success', trans('Order Detail Deleted Successfully!'));
    //     } else {
    //         return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
    //     }
    // }

    public function orderComplete(request $request){
        if($request->ajax()){
            $id = Crypt::decrypt($request->id);
            $message = $request->is_complete == Constant::NO ? 'order not completed' : 'Order Completed Successfully!';
            Order::where([['id', $id]])->update(['is_complete' => Constant::YES]); 
            echo json_encode(array('message' => $message));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function invoice(request $request,$id){
        $return_data = array();       
        $return_data['site_title'] = trans('Invoice');
        $user_id = auth()->user()->id;
        $invoice = $request->id;
        $return_data['order'] = Order::with('detail', 'slotDetail')->where('invoice_no', $invoice)->orderBy('id', 'desc')->first();
        $aslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::AFTERNOON)->orderBy('id')->get();
        $eslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::EVENING)->orderBy('id')->get();
        $mslots = PickUpSlotSetting::select('id', 'time', 'slot')->where('slot', Constant::MORNING)->orderBy('id')->get();
        $return_data['aslots'] = $aslots;
        $return_data['eslots'] = $eslots;
        $return_data['mslots'] = $mslots;
        $filename = $invoice.'.pdf';
        $pdf = PDF::loadView('front.user.pdf',array_merge($this->data, $return_data));
        return $pdf->stream($filename);
    }
}
