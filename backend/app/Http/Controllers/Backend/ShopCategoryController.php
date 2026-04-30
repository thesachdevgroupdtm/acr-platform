<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShopCategory;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use DataTables;
use DB;

class ShopCategoryController extends MainController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('shop Category');
        return view('backend.shopcategory.list', array_merge($this->data, $return_data));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
                'name' => [
                    'required',
                    Rule::unique('shop_categories')->where(function ($query) use($request) {
                        return $query->where([['is_archive', Constant::NOT_ARCHIVE]]);
                    }),
                ],
            ]
        );
            
        $slug = $request->name != '' ? slugify($request->name) : NULL;

        $shopcategory = new ShopCategory();
        $fields = array('name','id','image');
        foreach($fields as $field){
            $shopcategory->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        // if($request->hasFile('image')) {
        //     $newName = fileUpload($request, 'image', 'uploads/shopCategory');
        //     $shopcategory->image = $newName;
        // }
        $shopcategory->slug = $slug;
        $shopcategory->created_by = Auth::guard('admin')->user()->id;
        $shopcategory->save();
        if($shopcategory){
            return redirect()->back()->with('success', trans('Shop Category Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function ajaxEditShopCategoryHtml(request $request)
    {
        if($request->ajax()){
            $id = $request->id;
            $id = $id ? Crypt::decrypt($id) : NULL;
            $record = $id ? ShopCategory::find($id) : NULL;
            $html = view('backend.shopcategory.ajax_edit_html', array('record' => $record))->render();
            $return = array();
            $return['html'] = $html;
            echo json_encode($return);
        } else {
            return redirect('backend/dashboard');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $id = Crypt::decrypt($id);
        $this->validate($request, [
                'name' => [
                    'required',
                    Rule::unique('shop_categories')->where(function ($query) use($request, $id) {
                        return $query->where([['is_archive', Constant::NOT_ARCHIVE],['id', '!=', $id]]);
                    }),
                ],
            ]
        );
        $slug = $request->name != '' ? slugify($request->name) : NULL;

        $shopcategory = ShopCategory::find($id);
        $fields = array('name','image');
        foreach($fields as $field){
            $shopcategory->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        // if($request->hasFile('image')) {
        //     $old_image = $shopcategory->image;
        //     if($old_image){
        //         removeFile('uploads/shopCategory/'.$old_image);
        //     }
        //     $newName = fileUpload($request, 'image', 'uploads/shopCategory');
        //     $shopcategory->image = $newName;
        // }
        $shopcategory->slug = $slug;
        $shopcategory->updated_by = Auth::guard('admin')->user()->id;
        $shopcategory->save();

        if($shopcategory) {
            return redirect()->back()->with('success', trans('Shop Category Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $id = Crypt::decrypt($id);
       $constraint_array = array(
           array('table' => 'products', 'column' => 'shop_category_id')
       );
       $is_delete = checkDeleteConstrainnt($constraint_array, $id);
       if($is_delete) {
            // $shopcategory = ShopCategory::where('id', $id)->first();
            // $old_image = $shopcategory->image;
            // if($old_image){
            //     removeFile('uploads/shopCategory/'.$old_image);
            // }

            $shopcategory = ShopCategory::where('id', $id)->delete();
            if($shopcategory) {
                return redirect()->back()->with('success', trans('Shop Category Deleted Successfully!'));
            } else {
                return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
            }
       } else {
           return redirect()->back()->with('error', trans('You can not delete this Shop Category! Somewhere this Shop Category information is added in system!'));
       }
    }

    public function shopcategoriesDatatable(request $request)
    {
        if($request->ajax()){
            $query = ShopCategory::select('id', 'name','image', 'status')->where('is_archive', '=', Constant::NOT_ARCHIVE)->orderBy('id', 'DESC');
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('name', function ($row) {
                    $html = $row->name;
                    return $html;
                })
                ->addColumn('image', function ($row) {
                    $image = $row->image ? "<img src='".url($row->image)."' width='80px' height='80px'>" : '';
                    return $image;
                })
                ->addColumn('status', function ($row) {
                    $html = $row->status == Constant::ACTIVE ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    $html .= "<a href='javascript:void(0);' rel='tooltip' title='".trans('Edit')."' data-id='".$id."' class='btn btn-info btn-sm ajax-form'><i class='fas fa-pencil-alt'></i></a>&nbsp";
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_shop-category-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    if($row->status == Constant::ACTIVE) {
                        $html .= "<a href='javascript:void(0);' class='btn btn-warning btn-sm status' data-status='".Constant::INACTIVE."' data-id='".$id."' rel='tooltip' title='Inactive'><i class='far fa-fw fa-window-close'></i></a>&nbsp";
                    } else {
                        $html .= "<a href='javascript:void(0);' class='btn btn-success btn-sm status' data-status='".Constant::ACTIVE."' data-id='".$id."' title='Active'><i class='fas fa-check'></i></a>";
                    }
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['id','name','image','action','status'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function changeShopCategoryStatus(request $request){
        if($request->ajax()){
            $id = Crypt::decrypt($request->id);
            $message = $request->status == Constant::ACTIVE ? 'Shop Category Activated Successfully!' : 'Shop Category Inactivated Successfully!';
            ShopCategory::where([['id', $id]])->update(array('status' => $request->status, 'updated_by' => Auth::guard('admin')->user()->id)); 
            echo json_encode(array('message' => $message));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }
}
