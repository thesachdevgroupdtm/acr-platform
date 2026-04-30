<?php



namespace App\Http\Controllers\Front;



use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;

use App\Constant;

use App\Models\CarBrand;

use App\Models\CarModel;

use App\Models\ScheduledPackage;

use App\Models\ScheduledPackageDetail;

use App\Models\FuelType;

use Session;

use App\Models\Product;

use App\Models\ServiceCategory;

use App\Models\ShopCategory;

use DB;

class SearchController extends MainController

{

    public function storePhoneInSession(Request $request)

    {

        $phone = $request->input('phone');

        // Cache::put('phone', $phone);

        return response()->json(['message' => 'Phone number stored in session']);

    }

public function detail(Request $request, $slug = null, $brand_model = null, $fuel = null)
{
    // Clear session if clear_car_session parameter is present
    if ($request->has('clear_car_session')) {
        Session::forget('brand_id');
        Session::forget('model_id');
        Session::forget('fuel_id');
        Session::save();
        
        // Redirect to same page without the parameter to avoid infinite reload
        return redirect()->route('front_regular-car-service', ['slug' => $slug]);
    }
}

    public function getCurrentModel(request $request)

    {

        if($request->ajax()){

            $slug = getDefualtServiceSlug();

            echo json_encode(array('slug' => $slug));

            exit;

        } else {

            return redirect('/');

        }

    }

    

    public function brands(request $request)

    {

        if($request->ajax()){

            $brand = $request->brand ? strtolower(str_replace(' ', '', $request->brand)) : NULL;

            $query = CarBrand::select('id', 'image', 'title')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);

            if($brand){

                $query->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE '%".$brand."%'");

            }

            $brands = $query->orderBy('title', 'asc')->get();

            $html = '';

            if($brands->count()){

                foreach($brands as $brand){

                    $html .= '<div class="selection-item amodal-brand" data-id="'.$brand->id.'" data-name="'.$brand->title.'">
                                <img src="'.asset("public/uploads/carbrand/".$brand->image).'" alt="'.$brand->title.'" onerror="this.src=\''.asset('front/img/default-brand.png').'\'">
                                <p class="item-name">'.$brand->title.'</p>
                            </div>';

                }

            } else {

                $html = '<div class="col-12 p-5"><p class="text-center text-muted">No brands found</p></div>';

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

                    $html .= '<div class="selection-item amodal-model" data-id="'.$model->id.'" data-name="'.$model->title.'">
                                <img src="'.asset("public/uploads/carmodel/".$model->image).'" alt="'.$model->title.'" onerror="this.src=\''.asset('front/img/default-model.png').'\'">
                                <p class="item-name">'.$model->title.'</p>
                            </div>';

                }

            } else {

                $html = '<div class="col-12 p-5"><p class="text-center text-muted">No models found</p></div>';

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

                    $html .= '<div class="selection-item amodal-fuel" data-id="'.$fval->id.'" data-name="'.$fval->title.'">
                                <img src="'. asset("public/uploads/fueltype/".$fval->image).'" alt="'.$fval->title.'" onerror="this.src=\''.asset('front/img/default-fuel.png').'\'">
                                <p class="item-name">'.$fval->title.'</p>
                            </div>';

                }

            } else {

                $html = '<div class="col-12 p-5"><p class="text-center text-muted">No fuel types found</p></div>';

            }

            echo json_encode(array('brand_id'=>$brand_id, 'html' => $html));

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

            $brandInfo = CarBrand::select('id', 'title', 'image')->where([['id', $brand_id]])->first();

            $modelInfo = CarModel::select('id', 'title', 'image')->where([['id', $model_id]])->first();

            $fuelInfo = FuelType::select('id', 'title', 'image')->where([['id', $fuel_id]])->first();

            $return = array('result' => 'error');

            $html = '';

            if(isset($brandInfo->id) && $brandInfo->id && isset($modelInfo->id) && $modelInfo->id && isset($fuelInfo->id) && $fuelInfo->id){

                $html .= '<div class="row align-items-center">
                           <div class="col-4">
                               <div class="car-info-card">
                                   <img src="'.asset('public/uploads/carbrand/'.$brandInfo->image).'" class="car-info-img" alt="'.$brandInfo->title.'" title="'.$brandInfo->title.'">
                                   <p class="car-info-title">'.$brandInfo->title.'</p>
                               </div>
                           </div>
                           <div class="col-4">
                               <div class="car-info-card">
                                   <img src="'.asset('public/uploads/carmodel/'.$modelInfo->image).'" class="car-info-img" alt="'.$modelInfo->title.'" title="'.$modelInfo->title.'">
                                   <p class="car-info-title">'.$modelInfo->title.'</p>
                               </div>
                           </div>
                           <div class="col-4">
                               <div class="car-info-card">
                                   <img src="'.asset("public/uploads/fueltype/".$fuelInfo->image) .'" class="car-info-img" alt="'.$fuelInfo->title.'" title="'.$fuelInfo->title.'">
                                   <p class="car-info-title">'.$fuelInfo->title.'</p>
                               </div>
                           </div>
                       </div>';

                $return = array('result' => 'success', 'type' => 'number', 'html' => $html, 'brand_id' => $brandInfo->id, 'model_id' => $modelInfo->id);

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



    /* ===================================================================
     * API SIBLINGS — return clean JSON, no HTML.
     * =================================================================== */

    public function brandsApi(Request $request)
    {
        $brand = $request->brand ? strtolower(str_replace(' ', '', $request->brand)) : null;

        $q = CarBrand::select('id','slug','image','title')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]]);

        if ($brand) {
            $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE ?", ['%'.$brand.'%']);
        }

        return response()->json(['success'=>true,'brands'=>$q->orderBy('title','asc')->get()]);
    }

    public function modelsApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'brand_id' => 'required|integer',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $needle = $request->model ? strtolower(str_replace(' ', '', $request->model)) : null;

        $q = CarModel::select('id','slug','image','title')
            ->where([
                ['carbrand_id', $request->brand_id],
                ['is_archive', Constant::NOT_ARCHIVE],
                ['status', Constant::ACTIVE],
            ]);
        if ($needle) {
            $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE ?", ['%'.$needle.'%']);
        }

        return response()->json(['success'=>true,'models'=>$q->orderBy('title','asc')->get()]);
    }

    public function fuelsApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'brand_id' => 'required|integer',
            'model_id' => 'required|integer',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $fuelIds = ScheduledPackageDetail::with('packageDetail')
            ->where([['brand_id', $request->brand_id], ['model_id', $request->model_id]])
            ->whereHas('packageDetail', function($q){
                $q->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            })
            ->groupBy('fuel_type_id')->pluck('fuel_type_id')->all();

        $needle = $request->fuel ? strtolower(str_replace(' ', '', $request->fuel)) : null;

        $q = FuelType::select('id','slug','title','image')
            ->whereIn('id', $fuelIds)
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]]);
        if ($needle) {
            $q->whereRaw("LOWER(REPLACE(title, ' ', '')) LIKE ?", ['%'.$needle.'%']);
        }

        return response()->json(['success'=>true,'fuels'=>$q->orderBy('title','asc')->get()]);
    }

    public function vehicleSummaryApi(Request $request)
    {
        $brand = $request->brand_id ? CarBrand::select('id','title','slug','image')->find($request->brand_id) : null;
        $model = $request->model_id ? CarModel::select('id','title','slug','image')->find($request->model_id) : null;
        $fuel  = $request->fuel_id  ? FuelType::select('id','title','slug','image')->find($request->fuel_id)   : null;

        if ($brand && $model && $fuel) {
            return response()->json(['success'=>true,'type'=>'complete','brand'=>$brand,'model'=>$model,'fuel'=>$fuel]);
        }
        if ($brand && $model) return response()->json(['success'=>true,'type'=>'fuel','brand'=>$brand,'model'=>$model]);
        if ($brand)          return response()->json(['success'=>true,'type'=>'model','brand'=>$brand]);
        return response()->json(['success'=>false,'message'=>'No vehicle context'], 404);
    }

    public function searchApi(Request $request)
    {
        $term = trim((string) $request->input('q', $request->input('search')));
        if ($term === '') {
            return response()->json([
                'success' => true,
                'products' => [],
                'service_categories' => [],
                'scheduled_packages' => [],
            ]);
        }

        $shopCategoryIds = ShopCategory::where('status', Constant::ACTIVE)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->where('name', 'LIKE', '%'.$term.'%')->pluck('id')->all();

        $products = Product::with('shopCategoryDetail','primaryImage')
            ->where('status', Constant::ACTIVE)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->where(function($q) use ($term, $shopCategoryIds){
                $q->where('name','LIKE','%'.$term.'%');
                if ($shopCategoryIds) $q->orWhereIn('shop_category_id', $shopCategoryIds);
            })
            ->orderBy('id','desc')->limit(40)->get();

        $serviceCategories = ServiceCategory::where('title','LIKE','%'.$term.'%')
            ->where('status', Constant::ACTIVE)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->orderBy('id','desc')->get();

        $serviceCategoryIds = $serviceCategories->pluck('id')->all();

        $packages = ScheduledPackage::with('categoryDetail','specifications')
            ->where('status', Constant::ACTIVE)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->where(function($q) use ($term, $serviceCategoryIds){
                $q->where('title','LIKE','%'.$term.'%');
                if ($serviceCategoryIds) $q->orWhereIn('sc_id', $serviceCategoryIds);
            })
            ->orderBy('id','desc')->limit(40)->get();

        return response()->json([
            'success'            => true,
            'products'           => $products,
            'service_categories' => $serviceCategories,
            'scheduled_packages' => $packages,
        ]);
    }

    public function search(request $request)

    {

        $search = '';

        if ($request->has('search')) {

            $search = $request->input('search');

            // $search = strtolower(str_replace(' ', '', $search));



            $category= ShopCategory::where('status', 1)

                                    ->where('is_archive', 1)

                                    ->where('name', 'LIKE', '%' . $search . '%')

                                    ->get();

        

            if($category)

            {   

                $categoryIds = $category->pluck('id')->toArray();

                $product = Product::with('shopCategoryDetail', 'primaryImage')

                                        ->where('status', 1)

                                        ->where('is_archive', 1)

                                        ->where('name', 'LIKE', '%' . $search . '%')

                                        ->orWhere('shop_category_id' , $categoryIds)

                                        ->orderBy('id', 'DESC')

                                        ->get();

            }

            else

            {

              $product = Product::with('shopCategoryDetail', 'primaryImage')

                                        ->where('status', 1)

                                        ->where('is_archive', 1)

                                        ->where('name', 'LIKE', '%' . $search . '%')

                                        ->orderBy('id', 'DESC')

                                        ->get();

            }

           

           $servicecategory=ServiceCategory::where('title', 'LIKE', '%' . $search . '%')

                                             ->where('status', 1)

                                             ->where('is_archive', 1)

                                             ->orderBy('id', 'DESC')

                                             ->get();

            

            if($servicecategory)

            {

                $servicecategoryIds = $servicecategory->pluck('id')->toArray();

                // dd($servicecategoryIds);

                $schedulepackage = ScheduledPackage::where('status', 1)

                    ->where('is_archive', 1)

                    ->where('title', 'LIKE', '%' . $search . '%')

                    ->orWhere('sc_id' , $servicecategoryIds)

                    ->orderBy('id', 'DESC')

                    ->get();

            }

            else

            {

                $schedulepackage = ScheduledPackage::with('categoryDetail','specifications')->where('status', 1)

                    ->where('is_archive', 1)

                    ->where('title', 'LIKE', '%' . $search . '%')

                    ->orderBy('id', 'DESC')

                    ->get();

            }







            

            $return_data = array();

            $return_data['settings'] = $this->data;

            $return_data['car_details'] = $product;

            $return_data['products']=$product;

            $return_data['servicecategory']=$servicecategory;

            $return_data['schedulepackage']=$schedulepackage;

            $return_data['site_title'] = trans('Search');

            return view('front/search/list',array_merge($this->data,$return_data));

        } else {

            return redirect('/');

        }

    }

}
