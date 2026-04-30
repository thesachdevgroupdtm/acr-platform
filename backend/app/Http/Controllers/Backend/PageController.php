<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use DataTables;

class PageController extends MainController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Pages');
        return view('backend.page.list', array_merge($this->data, $return_data));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Page Create');
        return view('backend.page.form',array_merge($this->data,$return_data));
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
                'name' => ['required'],
                'description' => ['required'],
            ],[
                'required'  => trans('The :attribute field is required.')
            ]
        );
        // $slug = $request->name != '' ? slugify($request->name) : NULL;
        $page = Page::create([
            // 'slug' => $slug,
            'slug' => slugify($request->slug),
            'name' => $request->name ? strip_tags($request->name) : NULL,
            'description' => $request->description,
            'meta_title' => $request->meta_title ? strip_tags($request->meta_title) : NULL,
            'extra_meta_tag' => $request->extra_meta_tag ? $request->extra_meta_tag : NULL,
            'meta_keyword' => $request->meta_keyword ? strip_tags($request->meta_keyword) : NULL,
            'meta_description' => $request->meta_description ? strip_tags($request->meta_description) : NULL,
            'canonical_tag' => $request->canonical_tag ? $request->canonical_tag : NULL,
            'is_archive' => Constant::NOT_ARCHIVE,
            'created_by' => Auth::guard('admin')->user()->id,
            'updated_by' => NULL,
        ]);
        if($page){
            return redirect('backend/pages')->with('success', trans('Page Added Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $return_data = array();
        $pages = Page::find($id);
        $return_data['record'] = $pages;
        $return_data['site_title'] = trans('Page Edit');
        return view('backend.page.form', array_merge($this->data, $return_data));
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
                'name' => ['required'],
                'description' => ['required'],
            ],[
                'required'  => trans('The :attribute field is required.')
            ]
        );

        // $slug = $request->name != '' ? slugify($request->name) : NULL;

        $page = Page::where('id', $id)->update([
                // 'slug' => $slug,
                'slug' => slugify($request->slug),
                'name' => $request->name ? strip_tags($request->name) : NULL,
                'description' => $request->description,
                'meta_title' => $request->meta_title ? strip_tags($request->meta_title) : NULL,
                'extra_meta_tag' => $request->extra_meta_tag ? $request->extra_meta_tag : NULL,
                'meta_keyword' => $request->meta_keyword ? strip_tags($request->meta_keyword) : NULL,
                'meta_description' => $request->meta_description ? strip_tags($request->meta_description) : NULL,
                'canonical_tag' => $request->canonical_tag ? $request->canonical_tag : NULL,
                'updated_by' => Auth::guard('admin')->user()->id,
            ]);
        if($page) {
            return redirect('backend/pages')->with('success', trans('Page Updated Successfully!'));
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
        $page = Page::where('id', $id)->delete();
        if($page) {
            return redirect('backend/pages')->with('success', trans('Page Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }
    public function pagesDatatable(request $request)
    {
        if($request->ajax()){
            $roles = Session::get('roles');
            $query = Page::select('id', 'name')->where('is_archive', '=', Constant::ARCHIVE)->orderBy('id', 'DESC');
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('action', function ($row) {
                    $roles = Session::get('roles');
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    $html .= "<a href='".route('admin_page-edit', array($id))."' rel='tooltip' title='".trans('Edit')."' class='btn btn-info btn-sm'><i class='fas fa-pencil-alt'></i></a>&nbsp";
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_page-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>";
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
