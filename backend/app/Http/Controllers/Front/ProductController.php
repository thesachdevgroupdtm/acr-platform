<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use App\Models\ShopCategory;
use App\Models\Product;
use App\Models\Seo;
use DB;

class ProductController extends MainController
{
    public function accessories(request $request)
    {
        $return_data = array();
        $return_data['site_title'] = trans('Accessories');
        $return_data['scategories'] = ShopCategory::with('products')->select('id', 'slug', 'name')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]])->orderBy('id', 'desc')->get();
        $pquery = Product::with('shopCategoryDetail', 'primaryImage')->select('id', 'slug', 'name', 'sku', 'shop_category_id', 'price')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
        $pquery->whereHas('shopCategoryDetail', function($q) use ($request) {
            $q->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
        });
        
        $return_data['products'] = $pquery->orderBy('id', 'desc')->paginate(12);
        $accessories = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')->where('id', Constant::ACCESSORIES_SEO_ID)->first();
        $return_data['meta_keywords'] =  isset($accessories->meta_keyword) && $accessories->meta_keyword ? $accessories->meta_keyword : NULL;
        $return_data['meta_description'] = isset($accessories->meta_description) && $accessories->meta_description ? $accessories->meta_description : NULL;
        $return_data['canonical_tag'] =  isset($accessories->canonical_tag) && $accessories->canonical_tag ? $accessories->canonical_tag : NULL;
        $return_data['extra_meta_description'] =  isset($accessories->extra_meta_description) && $accessories->extra_meta_description ? $accessories->extra_meta_description : NULL;
         $return_data['meta_title'] =  isset($accessories->meta_title) && $accessories->meta_title ? $accessories->meta_title : NULL;
        // print_r($return_data['products']);exit;
        return view('front/accessories/list',array_merge($this->data,$return_data));
    }

    public function searchAjax(request $request)
    {
        if($request->ajax()){
            $return_data = array();
            $category = $request->category;
            $priceRange=$request->priceRange;
            $query = Product::with('shopCategoryDetail', 'primaryImage')->select('id', 'slug', 'name', 'sku', 'shop_category_id', 'price')->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            if($category){
                $query->whereIn('shop_category_id', $category);
            }
            if(!empty($priceRange)){
                $query->whereBetween('price', $priceRange);
            }
            if(!empty($request->productStatus) && in_array('instock',$request->productStatus)){
                $query->where('inventory','>',0);
            }
            if(!empty($request->productStatus) && in_array('onsale',$request->productStatus)){
                $query->where('onsale',1);
            }

            $query->whereHas('shopCategoryDetail', function($q) use ($request) {
                $q->where([['is_archive', Constant::NOT_ARCHIVE], ['status', Constant::ACTIVE]]);
            });
            $return_data['products'] = $query->orderBy('id', 'desc')->paginate(12);
            $html = view('front.accessories.ajax_search_list', $return_data)->render();
            $return = array();
            $return['html'] = $html;
            echo json_encode($return);
        } else {
            return redirect('/');
        }
    }

    public function detail($slug)
    {
        // $segment = request()->segment(2);
        // if($segment){
            $record = Product::with('shopCategoryDetail', 'primaryImage', 'images')->select('*')->where([['is_archive', Constant::NOT_ARCHIVE], ['slug', $slug], ['status', Constant::ACTIVE]])->first();
            if(isset($record->id)){
                $return_data = array();
                $return_data['site_title'] = $record->name;
                $return_data['meta_keywords'] = $record->meta_keywords;
                $return_data['meta_description'] = $record->meta_description;
                $return_data['canonical_tag'] = $record->canonical_tag;
                $return_data['record'] = $record;

                return view('front/accessories/detail',array_merge($this->data,$return_data));
            } else {
                return redirect('/');
            }
        // } else {
        //     return redirect('/');
        // }
    }

    /* === API SIBLINGS === */

    public function accessoriesApi(Request $request)
    {
        $query = Product::with('shopCategoryDetail','primaryImage')
            ->select('id','slug','name','sku','shop_category_id','price')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])
            ->whereHas('shopCategoryDetail', function($q){
                $q->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]]);
            });

        if ($request->filled('category')) {
            $query->whereIn('shop_category_id', (array) $request->category);
        }
        if ($request->filled('price_min') || $request->filled('price_max')) {
            $query->whereBetween('price', [
                (float) $request->input('price_min', 0),
                (float) $request->input('price_max', PHP_INT_MAX),
            ]);
        }
        $statuses = (array) $request->input('status', []);
        if (in_array('instock', $statuses, true)) $query->where('inventory','>',0);
        if (in_array('onsale',  $statuses, true)) $query->where('onsale', 1);

        $perPage = (int) $request->input('per_page', 12);

        $categories = ShopCategory::select('id','slug','name')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])
            ->orderBy('id','desc')->get();

        $seoRow = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::ACCESSORIES_SEO_ID)->first();

        return response()->json([
            'success'    => true,
            'products'   => $query->orderBy('id','desc')->paginate($perPage),
            'categories' => $categories,
            'seo'        => \App\Helpers\SeoHelper::fromModel($seoRow, [
                'site_name' => 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }

    public function detailApi(Request $request, $slug)
    {
        $product = Product::with('shopCategoryDetail','primaryImage','images')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['slug', $slug],['status', Constant::ACTIVE]])
            ->first();

        if (!$product) return response()->json(['success'=>false,'message'=>'Product not found.'], 404);

        return response()->json([
            'success' => true,
            'product' => $product,
            'seo'     => \App\Helpers\SeoHelper::fromModel($product, [
                'site_name' => 'ACR',
                'url'       => $request->url(),
                'type'      => 'product',
            ]),
        ]);
    }
}