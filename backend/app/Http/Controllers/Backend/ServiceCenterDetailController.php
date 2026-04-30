<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCenterDetail;
use Illuminate\Support\Facades\Crypt;
use Auth;

class ServiceCenterDetailController extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Service Center Detail');
        $return_data['scdetail'] =  ServiceCenterDetail::orderBy('id','asc')->get();
        return view('backend.service_center_detail.index',array_merge($this->data,$return_data));
    }

    public function update(request $request)
    {
        $return_data = array();       
        $total = $request->last_id;
        if($total){
            for($i = 0; $i < $total; $i++){
                $id = 'id_'.$i;
                $name = 'name_'.$i;
                if(isset($request->$name)){
                    $image = 'image_'.$i;
                    $image_title = 'image_title_'.$i;
                    $name = 'name_'.$i;
                    $address = 'address_'.$i;
                    $phone_number = 'phone_number_'.$i;
                    if($request->$id){
                        $id_val = Crypt::decrypt($request->$id);
                        $scdetail = ServiceCenterDetail::find($id_val);
                        $scdetail->updated_by = Auth::guard('admin')->user()->id;
                    } else {
                        $scdetail = new ServiceCenterDetail();
                        $scdetail->created_by = Auth::guard('admin')->user()->id;
                    }

                    $scdetail->name = $request->$name ? $request->$name : NULL;
                    $scdetail->image = $request->$image ? $request->$image : NULL;
                    $scdetail->image_title = $request->$image_title ? $request->$image_title : NULL;
                    $scdetail->address = $request->$address ? $request->$address : NULL;
                    $scdetail->phone_number = $request->$phone_number ? $request->$phone_number : NULL;
                    // if($request->hasFile($image)) {
                    //     if($request->$id){
                    //         $old_image = $scdetail->image;
                    //         if($old_image){
                    //             removeFile('uploads/servicecenterdetail/'.$old_image);
                    //         }
                    //     }
                    //     $newName = fileUpload($request, $image, 'uploads/servicecenterdetail/');
                    //     $scdetail->image = $newName;
                    // }
                    $scdetail->save();
                }
            }
        }
        if($scdetail){
            return redirect()->back()->with('success ',trans('Service Center Detail Updated Uccessfully'));
        }else{
            return redirect()->back()->with('error ',trans('Something went wrong, please try again later!'));
        }
    }

    public function serviceCenterDelete(request $request)
    {
        // $scdetail = ServiceCenterDetail::where('id', $request->id)->first();
        // $old_image = $scdetail->image;
        // if($old_image){
        //     removeFile('uploads/servicecenterdetail/'.$old_image);
        // }
        ServiceCenterDetail::where('id', $request->id)->delete();
    }
}
