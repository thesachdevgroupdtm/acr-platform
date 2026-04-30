<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Models\OfferSlider;
use App\Constant;
use Auth;

class OfferSliderController extends MainController
{
    public function index()
    {
        $return_data = array();
        $return_data['site_title'] =trans('Offer Slider');
        $return_data['slider'] = OfferSlider::orderBy('id', 'asc')->get();
        return view('backend.offer_slider.index',array_merge($this->data,$return_data));
    }

    public function slideupdate(Request $request)
    {
        $return_data = array();       
        $total = $request->last_id;
        if($total){
            for($i = 0; $i < $total; $i++){
                $id = 'id_'.$i;
                $btn_title = 'btn_title_'.$i;
                if(isset($request->$btn_title)){
                    $image = 'image_'.$i;
                    $image_title = 'image_title_'.$i;
                    $membership_package = 'membership_package_'.$i;
                    $title1 = 'title1_'.$i;
                    $title2 = 'title2_'.$i;
                    $btn_title = 'btn_title_'.$i;
                    $btn_link = 'btn_link_'.$i;
                    $img_url = 'image_url_'.$i;
                    $reorder = 'reorder_'.$i;
                    $background = 'background_'.$i;
                    $title_color = 'title_color_'.$i;
                    $subtitle_color = 'subtitle_color_'.$i;
                    if($request->$id){
                        $id_val = Crypt::decrypt($request->$id);
                        $offer_slider = OfferSlider::find($id_val);
                        $offer_slider->updated_by = Auth::guard('admin')->user()->id;
                    } else {
                        $offer_slider = new OfferSlider();
                        $offer_slider->created_by = Auth::guard('admin')->user()->id;
                    }
                    
                    $isChecked = $request->has('membership_package_'.$i);


                    // $offer_slider->image = $request->$image ? $request->$image : NULL;
                    $offer_slider->image_title = $request->$image_title ? $request->$image_title : NULL;
                    $offer_slider->membership_package = $isChecked ? 1 : 0;
                    $offer_slider->title1 = $request->$title1 ? $request->$title1 : NULL;
                    $offer_slider->title2 = $request->$title2 ? $request->$title2 : NULL;
                    $offer_slider->btn_title = $request->$btn_title ? $request->$btn_title : NULL;
                    $offer_slider->btn_link = $request->$btn_link ? $request->$btn_link : NULL;
                    $offer_slider->image_url = $request->$img_url ? $request->$img_url : NULL;
                    $offer_slider->reorder = $request->$reorder ? $request->$reorder : 1;
                    $offer_slider->background = $request->$background ? $request->$background : '#fff';
                    $offer_slider->title_color = $request->$title_color ? $request->$title_color : '#000';
                    $offer_slider->subtitle_color = $request->$subtitle_color ? $request->$subtitle_color :'#000';
                    if($request->hasFile($image)) {
                        if($request->$id){
                            $old_image = $offer_slider->image;
                            if($old_image){
                                removeFile('uploads/offerslider/'.$old_image);
                            }
                        }
                        $newName = fileUpload($request, $image, 'uploads/offerslider/');
                        $offer_slider->image = $newName;
                    }
                    $offer_slider->save();
                    // print_r($offer_slider);exit;
                }    
            }   
            return redirect()->back()->with('success', trans('Offer Slider Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function offerSliderDelete(request $request)
    {
        $offer_slider = OfferSlider::where('id', $request->id)->first();
        $old_image = $offer_slider->image;
        if($old_image){
            removeFile('uploads/offerslider/'.$old_image);
        }
        OfferSlider::where('id', $request->id)->delete();
    }
}
