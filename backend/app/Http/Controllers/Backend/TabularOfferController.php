<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Models\OfferSlider;
use App\Constant;
use App\Models\TabularOffer;
use Auth;

class TabularOfferController extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['site_title'] =trans('Tabular Offer');
        $return_data['slider'] = TabularOffer::orderBy('id', 'asc')->get();
        return view('backend.tabular_offer.index',array_merge($this->data,$return_data));
    }

    public function tabsUpdate(Request $request)
    {
        // $return_data = array();       
        $total = $request->last_id;

        if(!empty($request->title)){
            foreach($request->title as $index=>$value){
                if(isset($value)){
                    if($request->id[$index]){
                        $id_val = Crypt::decrypt($request->id[$index]);
                        $tabular_offer = TabularOffer::find($id_val);
                        $tabular_offer->updated_by = Auth::guard('admin')->user()->id;
                    } else {
                        $tabular_offer = new TabularOffer();
                        $tabular_offer->created_by = Auth::guard('admin')->user()->id;
                    }
                    $isChecked = $request->has("status.$index");

                    $tabular_offer->title = $request->title[$index] ? $request->title[$index] : 'Title Name';
                    $tabular_offer->status = $isChecked? 1 : 0;
                    // $tabular_offer->image_url = $request->image_url[$index] ? $request->image_url[$index] : NULL;
                    $tabular_offer->link = $request->link[$index] ? $request->link[$index] : '#';
                    $tabular_offer->reorder = $request->reorder[$index] ? $request->reorder[$index] :'1';
                    if($request->hasFile('image.'.$index)) {
                        if($request->id[$index]){
                            $old_image = $tabular_offer->image_url[$index];
                            if($old_image){
                                removeFile('uploads/tabularoffer/'.$old_image);
                            }
                        }
                        $newName = fileUpload($request, "image.$index", 'uploads/tabularoffer/');
                        $tabular_offer->image_url = $newName;
                    }
                    $tabular_offer->save();
                }    
            }   
            return redirect()->back()->with('success', trans('Offer Slider Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function tabularOfferDelete(request $request)
    {
        $tabular_offer = TabularOffer::where('id', $request->id)->first();
        $old_image = $tabular_offer->image_url;
        if($old_image){
            removeFile('uploads/tabularoffer/'.$old_image);
        }
        TabularOffer::where('id', $request->id)->delete();
    }
}
