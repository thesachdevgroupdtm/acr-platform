<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Models\FuelType;
use App\Constant;
use Illuminate\Http\Request;
use DataTables;
use Auth;

class FuelTypeController extends MainController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Fuel Type');
        return view('backend.fueltype.list', array_merge($this->data, $return_data));
    }

    public function fueltypeDatatable(request $request)
    {
        if($request->ajax()){
            $query = FuelType::select('id', 'image', 'title','status')->where('is_archive', '=', Constant::NOT_ARCHIVE)->orderBy('id', 'DESC');
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('title', function ($row) {
                    $html = $row->title;
                    return $html;
                })
                ->addColumn('image', function ($row) {
                    $image = $row->image ? "<img src='".asset('uploads/fueltype/'.$row->image)."' width='80px' height='80px'>" : '';
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
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_fuel-type-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    if($row->status == Constant::ACTIVE) {
                        $html .= "<a href='javascript:void(0);' class='btn btn-warning btn-sm status' data-status='".Constant::INACTIVE."' data-id='".$id."' rel='tooltip' title='Inactive'><i class='far fa-fw fa-window-close'></i></a>&nbsp";
                    } else {
                        $html .= "<a href='javascript:void(0);' class='btn btn-success btn-sm status' data-status='".Constant::ACTIVE."' data-id='".$id."' title='Active'><i class='fas fa-check'></i></a>";
                    }
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['id','image','title','status','action'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => [
                'required',
                Rule::unique('fuel_type')->where(function ($query) use($request) {
                    return $query->where('is_archive', Constant::NOT_ARCHIVE);
                }),
            ],
        ]);
        $slug = $request->title != '' ? slugify($request->title) : NULL;

        $fueltype = new FuelType();
        $fields = array('title');
        foreach($fields as $field){
            $fueltype->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        if($request->hasFile('image')) {
            $old_image = $fueltype->image;
            if($old_image){
                removeFile('uploads/fueltype/'.$old_image);
            }
            $newName = fileUpload($request, 'image', 'uploads/fueltype');
            $fueltype->image = $newName;
        }
        $fueltype->slug = $slug;
        $fueltype->created_by = Auth::guard('admin')->user()->id;
        $fueltype->save();
        if($fueltype){
            return redirect()->back()->with('success', trans('Fuel Type Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function ajaxEditFuelHtml(request $request)
    {
        if($request->ajax()){
            $id = $request->id;
            $id = $id ? Crypt::decrypt($id) : NULL;
            $record = $id ? FuelType::find($id) : NULL;
            $html = view('backend.fueltype.ajax_edit_html', array('record' => $record))->render();
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
    public function update(Request $request,$id)
    {
        $id = Crypt::decrypt($id);
        $this->validate($request, [
            'title' => [
                'required',
                Rule::unique('fuel_type')->where(function ($query) use($request, $id) {
                    return $query->where([['is_archive', Constant::NOT_ARCHIVE], ['id', '!=', $id]]);
                }),
            ],
        ]);

        $slug = $request->title != '' ? slugify($request->title) : NULL;

        $fueltype = FuelType::find($id);
        $fields = array('title');
        foreach($fields as $field){
            $fueltype->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        if($request->hasFile('image')) {
            $old_image = $fueltype->image;
            if($old_image){
                removeFile('uploads/fueltype/'.$old_image);
            }
            $newName = fileUpload($request, 'image', 'uploads/fueltype');
            $fueltype->image = $newName;
        }
        $fueltype->slug = $slug;
        $fueltype->updated_by = Auth::guard('admin')->user()->id;
        $fueltype->save();
        if($fueltype){
            return redirect()->back()->with('success', trans('Fuel Type Updated Successfully!'));
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
        $fueltype = FuelType::where('id',$id)->first();
        $old_image = $fueltype->image;
        if($old_image){
            removeFile('uploads/fueltype/'.$old_image);
        }
        $fueltype = FuelType::where('id',$id)->delete();
        if($fueltype){
            return redirect()->back()->with('success', trans('Fuel Type Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function changeFuelTypeStatus(request $request)
    {
        if($request->ajax()){
            $id = Crypt::decrypt($request->id);
            $message = $request->status == Constant::ACTIVE ? 'Fuel Type Activated Successfully!' : 'Fuel Type Inactivated Successfully!';
            FuelType::where([['id', $id]])->update(array('status' => $request->status, 'updated_by' => Auth::guard('admin')->user()->id)); 
            echo json_encode(array('message' => $message));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }
}
