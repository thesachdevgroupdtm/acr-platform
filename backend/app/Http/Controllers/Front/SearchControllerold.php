<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\ScheduledPackage;
use App\Models\ScheduledPackageDetail;
use App\Models\FuelType;
use Session;

class SearchController extends MainController
{
    public function brands(request $request)
    {
        if($request->ajax()){
            $brand = $request->brand ? strtolower(str_replace(' ', '', $request->brand)) : NULL;
            $query = CarBrand::select('id', 'image')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            if($brand){
                $query->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$brand."%'");
            }
            $brands = $query->orderBy('title', 'asc')->get();
            $html = '';
            if($brands->count()){
                foreach($brands as $brand){
                    $html .= '<div class="col-4 brand-logo-center">
                                <a href="javascript:void(0);" class="amodal-brand" data-id="'.$brand->id.'"><img src="'. $brand->image.'" class="img-fluid" alt=""></a>
                            </div>';
                }
            } else {
                $html = '<div class="col-12 p-5"></div>';
            }

            echo json_encode(array('html' => $html));
            exit;
        } else {
            return redirect('/');
        }
    }

    public function modelFromBrandModal(request $request){
        if($request->ajax()){
            $model = $request->model ? strtolower(str_replace(' ', '', $request->model)) : NULL;
            $brand_id = $request->brand_id;
            if(empty($brand_id)){
                $brand_id = Session::get('brand_id');
            } else {
                Session::put('brand_id', $brand_id);
                Session::put('model_id', '');
                Session::put('fuel_id', '');
                Session::save();
            }
            $query = CarModel::select('id', 'image', 'title')->where([['carbrand_id', $brand_id], ['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            if($model){
                $query->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$model."%'");
            }
            $models = $query->orderBy('title', 'asc')->get();
            $html = '';
            if($models->count()){
                foreach($models as $model){
                    $html .= '<div class="col-4 brand-logo-center">
                                <a href="javascript:void(0);" class="amodal-model" data-id="'.$model->id.'"><img src="'. $model->image.'" class="img-fluid" alt="">
                                    <p class="select-modal-name">'.$model->title.'</p>
                                </a>
                            </div>';
                }
            } else {
                $html = '<div class="col-12 p-5"></div>';
            }

            echo json_encode(array('html' => $html));
            exit;
        } else {
            return redirect('/');
        }
    }

    public function fuelFromModel(request $request){
        if($request->ajax()){
            $fuel = $request->fuel ? strtolower(str_replace(' ', '', $request->fuel)) : NULL;
            $brand_id = Session::get('brand_id');
            $model_id = $request->model_id;
            if(empty($model_id)){
                $model_id = Session::get('model_id');
            } else {
                Session::put('model_id', $model_id);
                Session::save();
            }

            $fquery = ScheduledPackageDetail::with('packageDetail')->select('fuel_type_id')->where([['brand_id', $brand_id], ['model_id', $model_id]]);
            $fquery->whereHas('packageDetail', function($q) use($request) {
                $q->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            });
            $fuels = $fquery->groupBy('fuel_type_id')->get();
            $farray = array();
            if($fuels->count()){
                foreach($fuels as $fval){
                    array_push($farray, $fval->fuel_type_id);
                }
            }

            $query = FuelType::select('id', 'title', 'image')->whereIn('id', $farray)->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            if($fuel){
                $query->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$fuel."%'");
            }
            $fuel_data = $query->orderBy('title', 'asc')->get();
            $html = '';
            if($fuel_data->count()){
                foreach($fuel_data as $fval){
                    $html .= '<div class="col-4 brand-logo-center">
                                <a href="javascript:void(0);" class="amodal-fuel" data-id="'.$fval->id.'"><img src="'. asset("public/uploads/fueltype/".$fval->image).'" class="img-fluid" alt="">
                                    <p class="select-modal-name">'.$fval->title.'</p>
                                </a>
                            </div>';
                }
            } else {
                $html = '<div class="col-12 p-5"></div>';
            }

            echo json_encode(array('html' => $html));
            exit;
        } else {
            return redirect('/');
        }
    }

    public function appoitmentNumberModel(request $request){
        if($request->ajax()){
            $fuel_id = $request->fuel_id;
            $brand_id = Session::get('brand_id');
            $model_id = Session::get('model_id');
            if(empty($fuel_id)){
                $fuel_id = Session::get('fuel_id');
            } else {
                Session::put('fuel_id', $fuel_id);
                Session::save();
            }

            $brandInfo = CarBrand::select('id', 'image')->where([['id', $brand_id]])->first();
            $modelInfo = CarModel::select('id', 'title', 'image')->where([['id', $model_id]])->first();
            $fuelInfo = FuelType::select('id', 'title', 'image')->where([['id', $fuel_id]])->first();

            $return = array('result' => 'error');
            $html = '';
            if(isset($brandInfo->id) && $brandInfo->id && isset($modelInfo->id) && $modelInfo->id && isset($fuelInfo->id) && $fuelInfo->id){
                $html .= '<div class="col-4 brand-logo-center">
                           <a href="javascript:void(0);"><img src="'.asset("public/uploads/carbrand/".$brandInfo->image) .'" class="img-fluid" alt=""></a>
                        </div>
                        <div class="col-4 brand-logo-center">
                            <a href="javascript:void(0);">
                                <img src="'.asset("public/uploads/carmodel/".$modelInfo->image) .'" class="img-fluid" alt="">
                                <p class="select-modal-name">'.$modelInfo->title.'</p>
                            </a>
                        </div>
                        <div class="col-4 brand-logo-center">
                            <a href="javascript:void(0);">
                                <img src="'.asset("public/uploads/fueltype/".$fuelInfo->image) .'" class="img-fluid" alt="">
                                <p class="select-modal-name">'.$fuelInfo->title.'</p>
                            </a>
                        </div>';
                $return = array('result' => 'success', 'type' => 'number', 'html' => $html);
            } elseif( isset($brandInfo->id) && $brandInfo->id && isset($modelInfo->id) && $modelInfo->id){
                $return = array('result' => 'success', 'type' => 'fuel');
            } elseif( isset($brandInfo->id) && $brandInfo->id){
                $return = array('result' => 'success', 'type' => 'model');
            }
            echo json_encode($return);
            exit;
        } else {
            return redirect('/');
        }
    }

    public function search(request $request)
    {
        $search = '';
        if ($request->has('search')) {
            $search = $request->input('search');
            $search = strtolower(str_replace(' ', '', $search));
            dd($search);
            
            $query = ScheduledPackageDetail::with(['packageDetail', 'brandDetail', 'modelDetail', 'carTypeDetail', 'offerDetail'])->select('id', 'slug', 'image_alt_text', 'maker_id', 'model_id', 'fuel_type', 'price', 'transmission', 'location', 'image', 'year', 'car_type_id', 'car_status', 'offer_id')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            $query->whereHas('makerDetail', function( $q ) use ( $search ){
                $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$search."%'");
            })->orWhereHas('modelDetail', function( $q ) use ( $search ){
                $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$search."%'");
            })->orWhereHas('carTypeDetail', function( $q ) use ( $search ){
                $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$search."%'");
            })->orWhereHas('variantDetail', function( $q ) use ( $search ){
                $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$search."%'");
            })->orWhereRaw("LOWER(REPLACE(fuel_type, ' ', '')) LIKE '%".$search."%'")
            ->orWhereRaw("LOWER(REPLACE(transmission, ' ', '')) LIKE '%".$search."%'");;
            $cardetails = $query->orderBy('id', 'desc')->paginate(18);
//            $sql = $query->toSql(); 
//            dd($sql);

            $return_data = array();
            $return_data['settings'] = $this->data;
            $return_data['car_details'] = $cardetails;
            $return_data['makers'] = Maker::select('id', 'title')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]])->orderBy('id', 'DESC')->get();
            $return_data['car_types'] = CarType::select('id', 'title')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]])->orderBy('id', 'DESC')->get();
            $return_data['site_title'] = trans('Search');
//            dd($return_data);
            return view('front/search/list',array_merge($this->data,$return_data));
        } else {
            return redirect('/');
        }
    }
}