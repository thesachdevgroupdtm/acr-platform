<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TabServiceCmsPage;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use DataTables;

class TabServiceCmsPageController extends MainController
{
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Tab Service Cms Page');
        return view('backend.tabservicecmspage.list', array_merge($this->data, $return_data));
    }

    public function create()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Tab Service Cms Page Create');
        return view('backend.tabservicecmspage.form', array_merge($this->data, $return_data));
    }

    public function store(Request $request)
    {
        $tabcms = new TabServiceCmsPage();
        $fields = array('name', 'image_title','section', 'banner_text', 'description', 'meta_title', 'extra_meta_tag', 'meta_keywords', 'meta_description','canonical_tag');
        
        foreach($fields as $field){
            $tabcms->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;
        }

        if($request->hasFile('banner_image')) {
            $newName = fileUpload($request, 'banner_image', 'uploads/tabservicecms');
            $tabcms->banner_image = $newName;
        }

        // Brochure upload
        if ($request->hasFile('brochure')) {
            $brochureName = fileUpload($request, 'brochure', 'uploads/tabservicecms/brochures');
            $tabcms->brochure = $brochureName;
        }

        $tabcms->slug = slugify($request->slug);
        $tabcms->created_by = Auth::guard('admin')->user()->id;
        $tabcms->save();

        if ($tabcms) {
            return redirect('backend/tabservicecms')->with('success', trans('Tab Service Cms Page Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $return_data = array();
        $tabcmspage = TabServiceCmsPage::find($id);
        $return_data['record'] = $tabcmspage;
        $return_data['site_title'] = trans('Tab Service Cms Page Edit');
        return view('backend.tabservicecmspage.form', array_merge($this->data, $return_data));
    }

    public function update(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $tabcms = TabServiceCmsPage::find($id);
        $fields = array('name', 'image_title','section', 'banner_text', 'description', 'meta_title', 'extra_meta_tag', 'meta_keywords', 'meta_description','canonical_tag');
        
        foreach($fields as $field){
            $tabcms->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;
        }

        if($request->hasFile('banner_image')) {
            $old_image = $tabcms->banner_image;
            if ($old_image) {
                removeFile('uploads/tabservicecms/' . $old_image);
            }
            $newName = fileUpload($request, 'banner_image', 'uploads/tabservicecms');
            $tabcms->banner_image = $newName;
        }

        // Brochure update
        if ($request->hasFile('brochure')) {
            $old_brochure = $tabcms->brochure;
            if ($old_brochure) {
                removeFile('uploads/tabservicecms/brochures/' . $old_brochure);
            }
            $brochureName = fileUpload($request, 'brochure', 'uploads/tabservicecms/brochures');
            $tabcms->brochure = $brochureName;
        }

        $tabcms->slug = slugify($request->slug);
        $tabcms->updated_by = Auth::guard('admin')->user()->id;
        $tabcms->save();

        if ($tabcms) {
            return redirect('backend/tabservicecms')->with('success', trans('Tab Service Cms Page Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $tabcms = TabServiceCmsPage::where('id', $id)->first();
        $old_image =  $tabcms->banner_image;
        $old_brochure =  $tabcms->brochure;

        if ($old_image) {
            removeFile('uploads/tabservicecms/'.$old_image);
        }

        if ($old_brochure) {
            removeFile('uploads/tabservicecms/brochures/'.$old_brochure);
        }

        $tabcms = TabServiceCmsPage::where('id', $id)->delete();

        if ($tabcms) {
            return redirect('backend/tabservicecms')->with('success', trans('Tab Service Cms Page Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function TabServiceCmsPageDatatable(request $request)
    {
        if($request->ajax()){
            $roles = Session::get('roles');
            $query = TabServiceCmsPage::select('id', 'name', 'brochure')->where('is_archive', '=', Constant::ARCHIVE)->orderBy('id', 'DESC');
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('action', function ($row) {
                    $roles = Session::get('roles');
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    $html .= "<a href='".route('admin_tabservicecms-edit', array($id))."' rel='tooltip' title='".trans('Edit')."' class='btn btn-info btn-sm'><i class='fas fa-pencil-alt'></i></a>&nbsp";

                    // Brochure download button
                    if ($row->brochure) {
                        $html .= "<a href='".asset('uploads/tabservicecms/brochures/' . $row->brochure)."' download rel='tooltip' title='".trans('Download Brochure')."' class='btn btn-success btn-sm'><i class='fa fa-download'></i></a>&nbsp";
                    }

                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_tabservicecms-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>";
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }
}
