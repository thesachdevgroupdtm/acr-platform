<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ShopCategory;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportProduct;
use App\Imports\ImportProduct;
use DataTables;
use File;

class ProductController extends MainController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Products');
        $shopcategories = ShopCategory::select('id','name')->where([['is_archive', Constant::NOT_ARCHIVE]])->orderby('id')->get();
        $return_data['shopcategories'] = $shopcategories;
        return view('backend.product.list',array_merge($this->data,$return_data));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Product Create');
        $shop_category = ShopCategory::select('id','name')->where('is_archive', '=', Constant::NOT_ARCHIVE)->get();
        $return_data['shop_category'] = $shop_category;
        return view('backend.product.form',array_merge($this->data,$return_data));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->slug = isset($request->slug) && $request->slug ? $request->slug : NULL;
            $this->validate($request, [
                'name' => ['required'],
                'price' => ['required'],
                'sku' => ['required',
                    Rule::unique('products')->where(function ($query) use($request) {
                        return $query->where([['is_archive', Constant::NOT_ARCHIVE]]);
                    }),
                ],
            ],[
                'required'  => trans('The :attribute field is required.')
            ]
        );
        // $slug = $request->name != '' || $request->name != '' ?  slugify($request->name.'-'.$request->sku) : NULL;
        $slug = $request->slug;
        $product = new Product();
        $fields = array('name', 'sku', 'shop_category_id', 'description', 'specification', 'price', 'amazon_link', 'flipcart_link', 'meta_title', 'meta_keywords', 'meta_description','canonical_tag','slug');
        foreach($fields as $field){
            $product->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;
        }
        // $product->slug = $slug;

        $isFeatured = $request->has("featured_product");
        $onSale = $request->has("onsale");
        $product->featured = $isFeatured? 1 : 0;
        $product->onsale = $onSale? 1 : 0;
        $product->inventory = $request->inventory??0;

        $product->created_by = Auth::guard('admin')->user()->id;
        $product->save();
        if($product){
            $total_images = isset($request->last_id) && $request->last_id ? $request->last_id : NULL;
            $is_primary = isset($request->is_primary) ? isset($request->is_primary) : NULL;
            $image_title = isset($request->image_title) ? isset($request->image_title) : NULL;
            if($total_images){
                for($i = 0; $i < $total_images; $i++){
                    $name = 'image'.$i;
                    $image_title = 'image_title'.$i;
                     if($request->hasFile($name)) {
                        $newName = fileUpload($request, $name, 'uploads/product/'.$product->id);
                        $product_img = new ProductImage();
                        //$product_img->image = $request->$name ? $request->$name : NULL;
                        $product_img->image = $newName ? $newName : NULL;
                        $product_img->is_primary = $is_primary == $i ? '1' : 0;
                        $product_img->product_id = $product->id;
                        $product_img->image_title = $request->$image_title ? $request->$image_title : NULL;
                        $product_img->save();
                     }
                }
            }
            return redirect('backend/products')->with('success', trans('Product Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $return_data = array();
        $return_data['site_title'] = trans('Product Edit');
        $products = Product::with('images')->find($id);
        $return_data['record'] = $products;
        $shop_category = ShopCategory::select('id','name')->where('is_archive', '=', Constant::NOT_ARCHIVE)->get();
        $return_data['shop_category'] = $shop_category;
        return view('backend.product.form', array_merge($this->data, $return_data));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $id)
    {
        $id = Crypt::decrypt($id);
        $request->slug = isset($request->slug) && $request->slug ? $request->slug : NULL;
        $this->validate($request, [
            'name' => ['required'],
            'price' => ['required'],
            'sku' => ['required',
                Rule::unique('products')->where(function ($query) use($request, $id) {
                    return $query->where([['is_archive', Constant::NOT_ARCHIVE], ['id' , '!=', $id]]);
                }),
            ],
        ],[
            'required'  => trans('The :attribute field is required.')
        ]);
        // $slug = $request->name != '' || $request->sku != '' ?  slugify($request->name.'-'.$request->sku) : NULL;
        $featured = $request->has("featured_product");
        $isFeatured = $featured? 1 : 0;
        $onSale = $request->has("onsale");
        $product = Product::where('id', $id)->update([
            // 'slug' => $slug,
            'slug' => $request->slug,
            'name' => $request->name,
            'sku' => $request->sku,
            'shop_category_id' => $request->shop_category_id,
            'description' => $request->description,
            'specification' => $request->specification,
            'price' => $request->price,
            'amazon_link' => $request->amazon_link,
            'flipcart_link' => $request->flipcart_link,
            'meta_title' => $request->meta_title,
            'meta_keywords' => $request->meta_keywords,
            'meta_description' => $request->meta_description,
            'canonical_tag' => $request->canonical_tag,
            'onsale' => $onSale? 1 : 0,
            'inventory' => $request->inventory,
            'is_archive' => Constant::NOT_ARCHIVE,
            'updated_by' => Auth::guard('admin')->user()->id,
            'featured'=>$isFeatured,
        ]);
        if($product){
            // $total_images = isset($request->last_id) && $request->last_id ? $request->last_id : NULL;
            $total_images = isset($request->total) && $request->total ? $request->total : NULL;
            $is_primary = isset($request->is_primary) ? $request->is_primary : NULL;
            // $image_title  = isset($request->image_title ) ? isset($request->image_title ) : NULL;
            if($total_images){
                for($i = 0; $i < $total_images; $i++){
                    $name = 'image'.$i;
                    $image_title = 'image_title'.$i;
                    // $filename = pathinfo($request->$name, PATHINFO_BASENAME);
                    $pid = 'pid'.$i;
                    if($request->$pid){
                        $product_img = ProductImage::find($request->$pid);
                    } else {
                        $product_img = new ProductImage();
                    }
                    $product_img->is_primary = $is_primary == $i ? '1' : 0;
                    $primary_key = "is_primary".$i;
                    // $product_img->is_primary = $is_primary;
                    $product_img->product_id = $id;
                    $product_img->image_title = $request->$image_title ? $request->$image_title : NULL;
                    //$product_img->image = $request->$name ? $request->$name : NULL;
                    if($request->hasFile($name)) {
                        if($request->$pid){
                            $old_image = $product_img->image;
                            if($old_image){
                                removeFile('uploads/product/'.$id.'/'.$old_image);
                            }
                        }
                        $newName = fileUpload($request, $name, 'uploads/product/'.$id);
                        $product_img->image = $newName;
                    }
                    $product_img->save();
                }
            }
            return redirect('backend/products')->with('success', trans('Product Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function imageDelete(request $request){
        if($request->ajax()){
            $image_info = ProductImage::find($request->id);
            $image = isset($image_info->image) && $image_info->image ? $image_info->image : NULL;
            $product_id = isset($image_info->product_id) && $image_info->product_id ? $image_info->product_id : NULL;
            if($image){
                removeFile('public/uploads/product/'.$product_id.'/'.$image);
            }
            ProductImage::where('id', $request->id)->delete();
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $product = Product::where('id',$id)->update(array('is_archive' => Constant::ARCHIVE));
        if($product){
            return redirect()->back()->with('success', trans('Product Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function productsDatatable(request $request)
    {
        if($request->ajax()){
            $query = Product::with('shopCategoryDetail','primaryImage')->select('id','shop_category_id', 'name','sku', 'price','status')->where('is_archive', '=', Constant::NOT_ARCHIVE)->orderBy('id', 'DESC');

            if($request->shopCategory!='all') {
                if($request->shopCategory!='') {
                    $query->whereHas('shopCategoryDetail', function($q) use ($request) {
                        $q->where([['shop_category_id', '=', $request->shopCategory]]);
                    });
                }
            }
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('name', function ($row) {
                    $name = $row->name;
                    return $name;
                })
                ->addColumn('shop_category_id', function ($row) {
                    $shop_category = isset($row->shopCategoryDetail->name) ? $row->shopCategoryDetail->name : '';
                    return $shop_category;
                })
                ->addColumn('image', function ($row) {
                    $image = isset($row->primaryImage->image) ? "<img src='".url('public/uploads/product/'.$row->id.'/'.$row->primaryImage->image)."' width='80px' height='80px'>" : '';
                    return $image;
                })
                ->addColumn('price', function ($row) {
                    $price = $row->price;
                    return $price;
                })
                ->addColumn('status', function ($row) {
                    $html = $row->status == Constant::ACTIVE ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    $html .= "<a href='".route('admin_product-edit', array($id))."' rel='tooltip' title='".trans('Edit')."' class='btn btn-info btn-sm'><i class='fas fa-pencil-alt'></i></a>&nbsp";
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_product-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    if($row->status == Constant::ACTIVE) {
                        $html .= "<a href='javascript:void(0);' class='btn btn-warning btn-sm status' data-status='".Constant::INACTIVE."' data-id='".$id."' rel='tooltip' title='Inactive'><i class='far fa-fw fa-window-close'></i></a>&nbsp";
                    } else {
                        $html .= "<a href='javascript:void(0);' class='btn btn-success btn-sm status' data-status='".Constant::ACTIVE."' data-id='".$id."' title='Active'><i class='fas fa-check'></i></a>";
                    }
                    $html .= "<a href='".route('admin_product-detail',array($id))."' data-href='' rel='tooltip' title='".trans('Detail')."' class='btn btn-info btn-sm detail'>Detail</a>";
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['id','name','shop_category_id', 'image','price','action','status'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }  

    public function changeProductStatus(request $request)
    {
        if($request->ajax()){
            $id = Crypt::decrypt($request->id);
            $message = $request->status == Constant::ACTIVE ? 'Product Activated Successfully!' : 'Product Inactivated Successfully!';
            Product::where([['id', $id]])->update(array('status' => $request->status, 'updated_by' => Auth::guard('admin')->user()->id)); 
            echo json_encode(array('message' => $message));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function imageAjaxHtml(request $request)
    {
        if($request->ajax()){
            $html = view('backend.product.ajax_html',array('i' => $request->id))->render();
            echo json_encode(array('html' => $html));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function makeSlug(request $request)
    {
        if($request->ajax()){
            $slug = $request->slug ? slugify($request->slug) : NULL;
            echo json_encode(array('slug' => $slug));exit;
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function productDetail(request $request,$id)
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Product Detail');
        $id = Crypt::decrypt($id);
        $detail = Product::with('shopCategoryDetail','primaryImage')->where('id',$id)->first();
        if(!isset($detail->id)){
            return redirect()->back()->with('error', 'Something went wrong, please try again later!');
        }
        $return_data['detail'] = $detail;
        $images =  ProductImage::select('id','product_id','image')->where('product_id',$id)->get();
        $return_data['images'] = $images;
        return view('backend.product.detail', array_merge($this->data, $return_data));
    }

    public function export(Request $request){
        return Excel::download(new ExportProduct, 'Product_SampleData.csv');
    }

    //use when import from another tab(browser)....not by pop-up
    // public function importAdd()
    // {
    //     $return_data = array();
    //     $return_data['site_title'] = trans('Import Data');
    //     return view('backend.product.import', array_merge($this->data, $return_data));
    // }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|max:10000',
        ]);
        // $path = $request->file('file')->store('files'); 
        // Excel::import(new ImportProduct,$path);
        $header = null;
        $handle = fopen($request->file->getPathName(), 'r');
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            // if(!$header) {
            //     $header = $row;
            // } else {
                $category_fields = array('name'=>$row[0], 'slug' => strtolower($row[0]), 'image' => NULL, 'is_archive' => 1, 'status' => 1,);
                $category_exists = ShopCategory::where([['name', '=', $row[0]]])->first();
                if(!empty($category_exists)) {
                    $category_id = $category_exists->id;
                } else {
                    $category_new = ShopCategory::create($category_fields);
                    $category_id = $category_new->id;
                }
                $product_fields = array('shop_category_id'=>$category_id, 'name'=>$row[1], 'description'=>$row[2], 'specification'=>$row[3], 'amazon_link'=>$row[4], 'flipcart_link'=>$row[5], 'price'=>$row[6], 'sku'=>$row[7], 'slug'=>strtolower($row[8]), 'meta_title' => $row[9], 'meta_keywords'=> $row[10], 'meta_description'=>$row[11]);
                $product_name = Product::select('id','name')->where([['name', '=', $row[1]], ['is_archive', '=', 1]])->first();
                if(!empty($product_name)) {
                    $product_id = $product_name->id;
                    Product::where([['id', '=', $product_id]])->update($product_fields);
                    /*if(isset($row[12]) && !empty($row[12])) {
                        ProductImage::where('product_id', $product_id)->delete();
                        $json_array = json_decode($row[12]);
                        if($json_array) {
                            foreach($json_array as $image) {
                                if($image->is_primary==1) {
                                    ProductImage::where('product_id', $product_id)->update(['is_primary'=>0]);
                                }
                                // $filename = pathinfo($image->image, PATHINFO_BASENAME);
                                $image_data = new ProductImage([
                                    'product_id' => $product_id,
                                    'is_primary' => $image->is_primary,
                                    'image'=> $image->image,
                                    'image_title' => $image->image_title,
                                ]);
                                $image_data->save();
                            }
                        }
                    }*/
                } else {
                    $product_fields['price'] = $row[6] ? $row[6] : 0 ;
                    $product = Product::create($product_fields);
                    $product->shop_category_id = $category_id;
                    $product_id = $product->id;
                    if($product) {
                        /*if(isset($row[12]) && !empty($row[12])) {
                            $json_array = json_decode($row[12]);
                            if($json_array) {
                                foreach($json_array as $image) {
                                    // $filename = pathinfo($image->image, PATHINFO_BASENAME);
                                    $image_data = new ProductImage([
                                        'product_id' => $product_id,
                                        'is_primary' => $image->is_primary,
                                        'image'=> $image->image,
                                        'image_title' => $image->image_title,
                                    ]);
                                    $image_data->save();
                                }
                            }
                        }*/
                    }
                }
            // }
        }
        return redirect('backend/products')->with('success', trans('Procduct Imported successfully.'));
    }
}
