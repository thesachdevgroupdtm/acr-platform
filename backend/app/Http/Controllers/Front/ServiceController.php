<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Constant;
use App\Models\ServiceCategory;
use App\Models\Faq;
use App\Models\faqcontent;
use App\Models\ScheduledPackage;
use App\Models\ScheduledPackageDetail;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Enquiry;
use App\Models\FuelType;
use App\Models\Cart;
use App\Models\Seo;


class ServiceController extends MainController
{
    public function services()
    {
        $return_data = array();
        $return_data['site_title'] = 'Our Services';
        $brand_id = Session::get('brand_id');
        $model_id = Session::get('model_id');
        $fuel_id = Session::get('fuel_id');
    
        $carray = array();
        $brandInfo = CarBrand::select('id', 'slug')->where('id', $brand_id)->first();
        $modelInfo = CarModel::select('id', 'slug')->where('id', $model_id)->first();
        $fuelInfo = FuelType::select('id', 'slug')->where('id', $fuel_id)->first();
    
        if ($brandInfo && $modelInfo && $fuelInfo) {
            $squery = ScheduledPackageDetail::with('packageDetail')->select('id', 'sp_id')->where([
                ['brand_id', $brand_id],
                ['model_id', $model_id],
                ['fuel_type_id', $fuel_id]
            ])->get();
    
            if ($squery && $squery->count()) {
                foreach ($squery as $record) {
                    if (isset($record->packageDetail->sc_id)) {
                        array_push($carray, $record->packageDetail->sc_id);
                    }
                }
            }
    
            $return_data['brand'] = $brandInfo->slug ?? null;
            $return_data['model'] = $modelInfo->slug ?? null;
            $return_data['fuel'] = $fuelInfo->slug ?? null;
        }
    
        $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])->orderBy('order_by', 'asc')->get();
    
        $return_data['carray'] = $carray;
    
        $our_service = Seo::select('meta_title', 'meta_keyword', 'meta_description', 'extra_meta_description', 'canonical_tag')->where('id', Constant::OUR_SERVICE_SEO_ID)->first();
        $return_data['meta_keywords'] = $our_service->meta_keyword ?? null;
        $return_data['meta_description'] = $our_service->meta_description ?? null;
        $return_data['canonical_tag'] = $our_service->canonical_tag ?? null;
        $return_data['extra_meta_description'] = $our_service->extra_meta_description ?? null;
        $return_data['meta_title'] = $our_service->meta_title ?? null;
    
        return view('front/service/list', array_merge($this->data, $return_data));
    }
public function bookNow(Request $request)
{
    $service_id = $request->input('service_id');
    $brand_id = Session::get('brand_id');
    $model_id = Session::get('model_id');
    $fuel_id = Session::get('fuel_id');

    // Fetch details for pre-filling
    $brand = CarBrand::find($brand_id);
    $model = CarModel::find($model_id);
    $fuel = FuelType::find($fuel_id);
    
    // Get service package detail
    $service = ScheduledPackage::find($service_id);
    
    // Get scheduled package detail for price info
    $scheduledDetail = ScheduledPackageDetail::where([
        ['sp_id', $service_id],
        ['brand_id', $brand_id],
        ['model_id', $model_id],
        ['fuel_type_id', $fuel_id]
    ])->first();

    // You can also fetch user details if logged in
    $user = auth()->user();

    return response()->json([
        'success' => true,
        'data' => [
            'brand' => $brand ? $brand->title : '',
            'model' => $model ? $model->title : '',
            'fuel' => $fuel ? $fuel->title : '',
            'service' => $service ? $service->title : '',
            'service_package' => $service ? $service->title : '',
            'price' => $scheduledDetail ? $scheduledDetail->price : 0,
            'user_name' => $user ? $user->name : '',
            'user_email' => $user ? $user->email : '',
            'user_phone' => $user ? $user->phone : '',
        ]
    ]);
}
    
    public function getModelFromBrand(Request $request)
    {
        $models = CarModel::select('id', 'title', 'image')
            ->where('carbrand_id', $request->brand_id)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->where('status', Constant::ACTIVE)
            ->get();
        
        return response()->json(['models' => $models]);
    }
    
    public function getFuelFromModel(Request $request)
    {
        $fuels = FuelType::select('fuel_type.id', 'fuel_type.title', 'fuel_type.image')
            ->join('model_fueltype_transaction', 'fuel_type.id', '=', 'model_fueltype_transaction.fuel_type_id')
            ->where('model_fueltype_transaction.model_id', $request->model_id)
            ->where('fuel_type.is_archive', Constant::NOT_ARCHIVE)
            ->where('fuel_type.status', Constant::ACTIVE)
            ->get();
        
        return response()->json(['fuels' => $fuels]);
    }
    
    public function storeVehicleSession(Request $request)
    {
        Session::put('brand_id', $request->brand_id);
        Session::put('model_id', $request->model_id);
        Session::put('fuel_id', $request->fuel_id);
        
        return response()->json(['success' => true]);
    }

    public function detail(Request $request)
{
    $category = $request->segment(1);

    // 1) Clear session if asked
    if ($request->boolean('clear_car_session')) {
        Session::forget(['brand_id', 'model_id', 'fuel_id']);
        Session::save();
        // same category URL without query param
        return redirect()->to(url($category));
    }

    // 2) Read slugs from URL (may be null)
    $brand = $request->brand;
    $model = $request->model;
    $fuel  = $request->fuel;

    // 3) If slugs present, sync session from slugs
    if ($brand && $model && $fuel) {
        $brandId = \App\Models\CarBrand::where('slug', $brand)->value('id');
        $modelId = \App\Models\CarModel::where('slug', $model)->value('id');
        $fuelId  = \App\Models\FuelType::where('slug', $fuel)->value('id');

        if ($brandId && $modelId && $fuelId) {
            Session::put('brand_id', $brandId);
            Session::put('model_id', $modelId);
            Session::put('fuel_id',  $fuelId);
            Session::save();
        }
    } else {
        // 4) Else, use session if available
        if (Session::has('brand_id') && Session::has('model_id') && Session::has('fuel_id')) {
            $brand = \App\Models\CarBrand::where('id', Session::get('brand_id'))->value('slug');
            $model = \App\Models\CarModel::where('id', Session::get('model_id'))->value('slug');
            $fuel  = \App\Models\FuelType::where('id', Session::get('fuel_id'))->value('slug');
        } else {
            // 5) Fallback defaults
            $brand = \App\Models\CarBrand::where('title', 'MARUTI SUZUKI')->value('slug');
            $model = \App\Models\CarModel::where('title', 'SWIFT')->value('slug');
            $fuel  = \App\Models\FuelType::where('title', 'Petrol')->value('slug');
        }
    }

    // ---- aapka purana code yahin se continue kare ----
    // $brandquery, $modelname, $fuelname nikaalo (slugs se)
    $brandquery = \App\Models\CarBrand::select('id','slug','title','image')->where('slug', $brand)->first();
    $modelname  = \App\Models\CarModel::select('id','slug','title','image')->where('slug', $model)->first();
    $fuelname   = \App\Models\FuelType::select('id','slug','title','image')->where('slug', $fuel)->first();

    $return_data['brandquery'] = $brandquery;
    $return_data['modelname']  = $modelname;
    $return_data['fuelname']   = $fuelname;

    $query = ScheduledPackage::with('categoryDetail', 'specifications')->select('*');
    $query->whereHas('categoryDetail', function ($q) use ($category) {
        $q->where('slug', $category);
    });
    $query->orderBy('id', 'desc');
    $services = $query->get();

    $categoryInfo = ServiceCategory::select('*')->where('slug', $category)->first();
    $return_data = array();
    $meta_title = $categoryInfo->meta_title ?? null;
    $return_data['meta_keywords'] = $categoryInfo->meta_keywords ?? null;
    $return_data['meta_description'] = $categoryInfo->meta_description ?? null;
    $return_data['canonical_tag'] = $categoryInfo->canonical_tag ?? null;
    $return_data['site_title'] = $meta_title ?: trans('Service Detail');
    $return_data['category'] = $categoryInfo;
    $return_data['detail'] = $services;

    $return_data['price_show'] = '0';

    if ($brand && $model && $fuel) {
        $return_data['price_show'] = '1';
    }

    $faqs = Faq::select('id', 'service_category_id', 'name', 'description')->where('service_category_id', $categoryInfo->id)->where('is_archive', '0')->get();
    $return_data['faqs'] = $faqs;

    $faqcontents = faqcontent::select('id', 'service_category_id', 'name', 'description')->where('service_category_id', $categoryInfo->id)->where('is_archive', '0')->get();
    $return_data['faqcontents'] = $faqcontents;

    $price_list = ServiceCategory::select('id', 'price_list')->where('slug', $category)->first();
    $return_data['price_list'] = $price_list;

    $brandquery = CarBrand::select('id', 'slug', 'title', 'image')->where('slug', $brand)->first();
    $return_data['brandquery'] = $brandquery;

    $modelname = CarModel::select('id', 'slug', 'title', 'image')->where('slug', $model)->first();
    $return_data['modelname'] = $modelname;

    $fuelname = FuelType::select('id', 'slug', 'title', 'image')->where('slug', $fuel)->first();
    $return_data['fuelname'] = $fuelname;

    // Fetch all categories for the dropdown
    $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]])->orderBy('order_by', 'asc')->get();

    return view('front/service/detail', array_merge($this->data, $return_data));
}

    /* ===================================================================
     * API SIBLINGS — additive, do not modify the methods above.
     * =================================================================== */

    public function categoriesApi()
    {
        $categories = ServiceCategory::select('id','slug','title','description','image','image_1','icon_image')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])
            ->orderBy('order_by','asc')->get();

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    public function servicesApi(Request $request)
    {
        $brandId = $request->brand_id;
        $modelId = $request->model_id;
        $fuelId  = $request->fuel_id;

        $availableCategoryIds = [];
        $brand = $brandId ? CarBrand::select('id','slug','title','image')->find($brandId) : null;
        $model = $modelId ? CarModel::select('id','slug','title','image')->find($modelId) : null;
        $fuel  = $fuelId  ? FuelType::select('id','slug','title','image')->find($fuelId)  : null;

        if ($brand && $model && $fuel) {
            $details = ScheduledPackageDetail::with('packageDetail')
                ->where([['brand_id',$brand->id],['model_id',$model->id],['fuel_type_id',$fuel->id]])->get();
            foreach ($details as $d) {
                if (isset($d->packageDetail->sc_id)) $availableCategoryIds[] = $d->packageDetail->sc_id;
            }
        }

        $categories = ServiceCategory::select('id','slug','title','image','icon_image','description')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])
            ->orderBy('order_by','asc')->get();

        $seoRow = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::OUR_SERVICE_SEO_ID)->first();

        return response()->json([
            'success'                => true,
            'categories'             => $categories,
            'available_category_ids' => array_values(array_unique($availableCategoryIds)),
            'brand' => $brand, 'model' => $model, 'fuel' => $fuel,
            'seo'   => \App\Helpers\SeoHelper::fromModel($seoRow, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }

    public function categoryDetailApi(Request $request, $categorySlug)
    {
        $category = ServiceCategory::where('slug', $categorySlug)
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])->first();

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $brand = $request->brand ? CarBrand::select('id','slug','title','image')->where('slug',$request->brand)->first() : null;
        $model = $request->model ? CarModel::select('id','slug','title','image')->where('slug',$request->model)->first() : null;
        $fuel  = $request->fuel  ? FuelType::select('id','slug','title','image')->where('slug',$request->fuel)->first()  : null;

        $services = ScheduledPackage::with('categoryDetail','specifications')
            ->where('sc_id', $category->id)
            ->where('is_archive', Constant::NOT_ARCHIVE)
            ->where('status', Constant::ACTIVE)
            ->orderBy('id','desc')->get();

        if ($brand && $model && $fuel) {
            $services = $services->map(function ($s) use ($brand,$model,$fuel) {
                $d = ScheduledPackageDetail::where([
                    ['sp_id',$s->id],['brand_id',$brand->id],['model_id',$model->id],['fuel_type_id',$fuel->id],
                ])->first();
                $s->vehicle_price       = $d->price ?? null;
                $s->vehicle_package_id  = $d->id ?? null;
                return $s;
            });
        }

        $faqs = Faq::select('id','service_category_id','name','description')
            ->where('service_category_id', $category->id)
            ->where('is_archive', Constant::NOT_ARCHIVE)->get();

        $faqContents = faqcontent::select('id','service_category_id','name','description')
            ->where('service_category_id', $category->id)
            ->where('is_archive', Constant::NOT_ARCHIVE)->get();

        return response()->json([
            'success'      => true,
            'category'     => $category,
            'services'     => $services,
            'price_list'   => $category->price_list ?? null,
            'price_show'   => $brand && $model && $fuel ? 1 : 0,
            'brand' => $brand, 'model' => $model, 'fuel' => $fuel,
            'faqs'         => $faqs,
            'faq_contents' => $faqContents,
            'seo'          => \App\Helpers\SeoHelper::fromModel($category, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }

    public function serviceDetailApi(Request $request, $categorySlug, $serviceSlug)
    {
        $category = ServiceCategory::where('slug', $categorySlug)
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])->first();
        if (!$category) return response()->json(['success'=>false,'message'=>'Category not found'], 404);

        $service = ScheduledPackage::with('categoryDetail','specifications')
            ->where('sc_id', $category->id)->where('slug', $serviceSlug)
            ->where('is_archive', Constant::NOT_ARCHIVE)->where('status', Constant::ACTIVE)->first();
        if (!$service) return response()->json(['success'=>false,'message'=>'Service not found'], 404);

        $vehiclePrice = null; $vehiclePackageId = null;
        if ($request->brand_id && $request->model_id && $request->fuel_id) {
            $d = ScheduledPackageDetail::where([
                ['sp_id',$service->id],['brand_id',$request->brand_id],
                ['model_id',$request->model_id],['fuel_type_id',$request->fuel_id],
            ])->first();
            $vehiclePrice = $d->price ?? null; $vehiclePackageId = $d->id ?? null;
        }

        return response()->json([
            'success' => true,
            'category' => $category,
            'service'  => $service,
            'vehicle_price' => $vehiclePrice,
            'vehicle_package_id' => $vehiclePackageId,
            'seo' => \App\Helpers\SeoHelper::fromModel($service, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
                'type'      => 'product',
            ]),
        ]);
    }

    public function bookNowApi(Request $request)
    {
        $service = ScheduledPackage::find($request->service_id);
        $brand   = $request->brand_id ? CarBrand::find($request->brand_id) : null;
        $model   = $request->model_id ? CarModel::find($request->model_id) : null;
        $fuel    = $request->fuel_id  ? FuelType::find($request->fuel_id)  : null;

        $detail = null;
        if ($service && $brand && $model && $fuel) {
            $detail = ScheduledPackageDetail::where([
                ['sp_id',$service->id],['brand_id',$brand->id],
                ['model_id',$model->id],['fuel_type_id',$fuel->id],
            ])->first();
        }

        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'brand'      => $brand->title  ?? null,
                'model'      => $model->title  ?? null,
                'fuel'       => $fuel->title   ?? null,
                'service'    => $service->title ?? null,
                'price'      => $detail->price  ?? 0,
                'package_id' => $detail->id     ?? null,
                'user_name'  => $user->firstname ?? null,
                'user_email' => $user->email     ?? null,
                'user_phone' => $user->phone     ?? null,
            ],
        ]);
    }

}
