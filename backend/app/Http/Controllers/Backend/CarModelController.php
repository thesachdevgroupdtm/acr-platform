<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\CarBrand;
use App\Models\CarModel;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use DataTables;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportCarModel;
use App\Imports\ImportCarModel;

class CarModelController extends MainController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Car Model');
        $carbrand = CarBrand::select('id','title')->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])->orderby('id')->get();
        $return_data['carbrand'] = $carbrand;
        return view('backend.carmodel.list', array_merge($this->data, $return_data));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
                'title' => [
                    'required',
                    Rule::unique('model')->where(function ($query) use($request) {
                        return $query->where([['is_archive', Constant::NOT_ARCHIVE],['carbrand_id','=',$request->carbrand_id]]);
                    }),
                ],
            ]
        );
            
        $slug = $request->title != '' ? slugify($request->title) : NULL;

        $carmodel = new CarModel();
        $fields = array('title','carbrand_id');
        foreach($fields as $field){
            $carmodel->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        $carmodel->slug = $slug;
        if($request->hasFile('image')) {
            $newName = fileUpload($request, 'image', 'uploads/carmodel');
            $carmodel->image = $newName;
        }
        $carmodel->created_by = Auth::guard('admin')->user()->id;
        $carmodel->save();
        if($carmodel){
            return redirect()->back()->with('success', trans('Car Model Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ajaxEditModelHtml(request $request)
    {
        //print_r("model ajaxEditModelHtml");exit;
        if($request->ajax()){
            $id = $request->id;
            $id = $id ? Crypt::decrypt($id) : NULL;
            $brand  = CarBrand::select('id','title')->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])->orderby('id')->get(); 
            $record = $id ? CarModel::find($id) : NULL;
            $html = view('backend.carmodel.ajax_edit_html', array('record' => $record, 'brand' => $brand))->render();
            $return = array();
            $return['html'] = $html;
            echo json_encode($return);
        } else {
            return redirect('backend/dashboard');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $this->validate($request, [
                'title' => [
                    'required',
                    Rule::unique('model')->where(function ($query) use($request, $id) {
                        return $query->where([['is_archive', '=', Constant::NOT_ARCHIVE],['id', '!=', $id],['carbrand_id','=',$request->carbrand_id]]);
                    }),
                ],
            ]
        );
        $slug = $request->title != '' ? slugify($request->title) : NULL;

        $carmodel = CarModel::find($id);
        $fields = array('title','carbrand_id');
        foreach($fields as $field){
            $carmodel->$field = isset($request->$field) && $request->$field ? $request->$field : NULL;
        }
        $carmodel->slug = $slug;
        $carmodel->updated_by = Auth::guard('admin')->user()->id;
        if($request->hasFile('image')) {
            $old_image = $carmodel->image;
            if($old_image){
                removeFile('uploads/carmodel/'.$old_image);
            }
            $newName = fileUpload($request, 'image', 'uploads/carmodel');
            $carmodel->image = $newName;
        }
        $carmodel->save();

        if($carmodel) {
            return redirect()->back()->with('success', trans('Car Model Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $constraint_array = array(
            array('table' => 'sceduled_packages', 'column' => 'model_id'),
        );
        $is_delete = checkDeleteConstrainnt($constraint_array, $id);
        if($is_delete) {
            $image = CarModel::where('id', $id)->first();
            $old_image = $image->image;
            if($old_image){
                removeFile('uploads/carmodel/'.$old_image);
            }
            $carmodel = CarModel::where('id', $id)->delete();
            if($carmodel) {
                return redirect()->back()->with('success', trans('Car Model Deleted Successfully!'));
            } else {
                return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
            }
        } else {
            return redirect()->back()->with('error', trans('You can not delete this car model! Somewhere this car model information is added in system!'));
        }
    }
    public function carmodelsDatatable(request $request)
    {
        if($request->ajax()){
            $query = CarModel::with('brandDetail')->select('id', 'image', 'carbrand_id', 'title', 'status')->where('is_archive', '=', Constant::NOT_ARCHIVE)->orderBy('id', 'DESC');

            if($request->carBrand!='all') {
                if($request->carBrand!='') {
                    $query->whereHas('brandDetail', function($q) use ($request) {
                        $q->where([['carbrand_id', '=', $request->carBrand]]);
                    });
                }
            }
            $list = $query->get();
            return DataTables::of($list)
                ->addColumn('image', function ($row) {
                    $image = $row->image ? "<img src='".url('public/uploads/carmodel/'.$row->image)."' width='80px' height='80px'>" : '';
                    return $image;
                })
                ->addColumn('maker', function ($row) {
                    $maker = isset($row->brandDetail->title) ? $row->brandDetail->title : '';
                    return $maker;
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
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_car-model-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    if($row->status == Constant::ACTIVE) {
                        $html .= "<a href='javascript:void(0);' class='btn btn-warning btn-sm status' data-status='".Constant::INACTIVE."' data-id='".$id."' rel='tooltip' title='Inactive'><i class='far fa-fw fa-window-close'></i></a>&nbsp";
                    } else {
                        $html .= "<a href='javascript:void(0);' class='btn btn-success btn-sm status' data-status='".Constant::ACTIVE."' data-id='".$id."' title='Active'><i class='fas fa-check'></i></a>";
                    }
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['maker', 'image','action','status'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }
    
    public function changeCarModelStatus(request $request){
        if($request->ajax()){
            $id = Crypt::decrypt($request->id);
            $message = $request->status == Constant::ACTIVE ? 'Car Model Activated Successfully!' : 'Car Model Inactivated Successfully!';
            CarModel::where([['id', $id]])->update(array('status' => $request->status, 'updated_by' => Auth::guard('admin')->user()->id)); 
            echo json_encode(array('message' => $message));
            exit;
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function export(Request $request){
        return Excel::download(new ExportCarModel, 'Car_Model_SampleData.csv');
    }

    // public function importAdd()
    // {
    //     $return_data = array();
    //     $return_data['site_title'] = trans('Import Data');
    //     return view('backend.carmodel.import', array_merge($this->data, $return_data));
    // }

    public function import(Request $request){
        $request->validate([
            'file' => 'required|max:10000',
        ]);

        $path = $request->file('file')->store('files'); 

        Excel::import(new ImportCarModel,$path);
        return redirect('backend/car-model')->with('success', trans('Car Model Imported successfully.'));
    }
}
