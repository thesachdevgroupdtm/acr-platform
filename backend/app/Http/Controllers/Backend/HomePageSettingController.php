<?php

namespace App\Http\Controllers\Backend;

use DB;
use Session;
use Illuminate\Http\Request;
use App\Models\BrandLogoSlider;
use App\Models\HomePageSetting;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class HomePageSettingController extends MainController
{
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Home Page Content');
        $return_data['record'] = HomePageSetting::first();
        return view('backend.homepagesetting.index', array_merge($this->data, $return_data));
    }

    public function update(request $request)
    {
        $content = HomePageSetting::first();
        if(empty($content)){
            $content = new HomePageSetting();
        }
        $slidersImages= $request->sliders??array();
        $oldImages=!empty($content->section1_image)?json_decode($content->section1_image):array();
        $oldImages=(array)$oldImages;
        if($request->hasFile('section1_image')) {
            $sliderIndex=array_flip($slidersImages);
            $oldImagesDiff = array_diff_key($oldImages, $sliderIndex);
            foreach($oldImagesDiff as $index=>$oImage) {
                $old_image = isset($oImage) ? $oImage : NULL;
                if($old_image){
                    removeFile('uploads/content/'.$old_image);
                    unset($oldImages[$index]);
                }
            }
                $newImages=array();
                foreach($request->file('section1_image') as $fileIndex=>$file){
                    $newImages[$fileIndex]= fileUpload($request, 'section1_image.'.$fileIndex, 'uploads/content');
                }
                $oldImages=array_merge($oldImages,$newImages);
                ksort($oldImages);
            $content->section1_image = json_encode($oldImages);  
        }
        elseif(count($slidersImages)){
            foreach($oldImages as $index=>$oImage) {
                if(!in_array($index,$slidersImages)){
                    $old_image = isset($oImage) ? $oImage : NULL;
                    if($old_image){
                        removeFile('uploads/content/'.$old_image);
                        unset($oldImages[$index]);
                    }
                }
            }
            ksort($oldImages);
            $content->section1_image = json_encode($oldImages);
        }
        else{
            $content->section1_image = json_encode(array());  
        }
        $content->section1_title1 = $request->section1_title1;
        $content->section1_title2 = $request->section1_title2;
        $content->footer_description = $request->footer_description;
        $content->button_title = $request->button_title;
        $content->button_link = $request->button_link;
        $content->section1_description = $request->section1_description;
        $content->price_list = $request->price_list;
        $content->meta_title = $request->meta_title;
        $content->image_title = $request->image_title;
        $content->meta_keywords = $request->meta_keywords;
        $content->meta_description = $request->meta_description;
         $content->canonical_tag = $request->canonical_tag;
        $content->extra_meta_tag = $request->extra_meta_tag;
        $content->updated_by = Auth::guard('admin')->user()->id;
        $content->save();
        if($content) {
            return redirect('backend/home-page-content')->with('success', trans('Content Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function brandLogoSlider()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Brand Logo Slider');
        $return_data['brandslider'] = BrandLogoSlider::orderBy('id', 'asc')->get();
        return view('backend.homepagesetting.brand_logo_slider',array_merge($this->data,$return_data));
    }

    public function slideupdate(Request $request)
    {
        $return_data = array();       
        $total = $request->last_id;
        if($total){
            for($i = 0; $i < $total; $i++){
                $id = 'id_'.$i;
                $image = 'image_'.$i;
                $image_title = 'image_title_'.$i;
                if(isset($request->$id)){
                    $image = 'image_'.$i;
                    $image_title = 'image_title_'.$i;
                    if($request->$id){
                        $id_val = Crypt::decrypt($request->$id);
                        $brand_slider = BrandLogoSlider::find($id_val);
                        $brand_slider->updated_by = Auth::guard('admin')->user()->id;
                    } else {
                        $brand_slider = new BrandLogoSlider();
                        $brand_slider->created_by = Auth::guard('admin')->user()->id;
                    }
                    if($request->hasFile($image)) {
                        if($request->$id){
                            $old_image = $brand_slider->image;
                            if($old_image){
                                removeFile('uploads/brandlogoslider/'.$old_image);
                            }
                        }
                        $newName = fileUpload($request, $image, 'uploads/brandlogoslider/');
                        $brand_slider->image = $newName;
                    }
                    // $brand_slider->image = $request->$image ? $request->$image : NULL;
                    $brand_slider->image_title = $request->$image_title ? $request->$image_title : NULL;
                    $brand_slider->save();
                }
            }   
            return redirect()->back()->with('success', trans('Brand Logo Slider Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function slideDelete(request $request)
    {
        $brand_slider = BrandLogoSlider::where('id', $request->id)->first();
        $old_image = $brand_slider->image;
        if($old_image){
            removeFile('uploads/brandlogoslider/'.$old_image);
        }
        BrandLogoSlider::where('id', $request->id)->delete();
    }
}