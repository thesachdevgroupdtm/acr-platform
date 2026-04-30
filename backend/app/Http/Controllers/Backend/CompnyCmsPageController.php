<?php



namespace App\Http\Controllers\Backend;



use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Models\CompnyCmsPage;

use Auth;

use Session;

use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Crypt;

use App\Constant;

use DataTables;



class CompnyCmsPageController extends MainController

{

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index()

    {

        $return_data = array();       

        $return_data['site_title'] = trans('Compny Cms Page');

        return view('backend.compnycmspage.list', array_merge($this->data, $return_data));

    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()

    {

        $return_data = array();

        $return_data['site_title'] = trans('Compny Cms Page Create');

        return view('backend.compnycmspage.form',array_merge($this->data,$return_data));

    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */

    public function store(Request $request)

    {

        $cms = new CompnyCmsPage();

        $fields = array('name', 'image_title','section', 'banner_text', 'description', 'meta_title', 'extra_meta_tag', 'meta_keywords', 'meta_description','canonical_tag');

        foreach($fields as $field){

            $cms->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;

        }

        if($request->hasFile('banner_image')) {

            $newName = fileUpload($request, 'banner_image', 'uploads/compnycms');

            $cms->banner_image = $newName;

        }

        $cms->slug = slugify($request->slug);

        $cms->created_by = Auth::guard('admin')->user()->id;

        $cms->save();

        if($cms){

            return redirect('backend/compnycms')->with('success', trans('Compny Cms Page Added Successfully!'));

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

        $cmspage = CompnyCmsPage::find($id);

        $return_data['record'] = $cmspage;

        $return_data['site_title'] = trans('Compny Cms Page Edit');

        return view('backend.compnycmspage.form', array_merge($this->data, $return_data));

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

        $cms = CompnyCmsPage::find($id);

        // print_r($cms);exit;

        $fields = array('name', 'image_title','section', 'banner_text', 'description', 'meta_title', 'extra_meta_tag', 'meta_keywords', 'meta_description','canonical_tag');

        foreach($fields as $field){

            $cms->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;

        }

        if($request->hasFile('banner_image')) {

            $old_image = $cms->banner_image;

            if($old_image){

                removeFile('uploads/compnycms/'.$old_image);

            }

            $newName = fileUpload($request, 'banner_image', 'uploads/compnycms');

            $cms->banner_image = $newName;

        }

        $cms->slug = slugify($request->slug);

        $cms->updated_by = Auth::guard('admin')->user()->id;

        $cms->save();

        if($cms) {

            return redirect('backend/compnycms')->with('success', trans('Compny Cms Page Updated Successfully!'));

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

        $cms = CompnyCmsPage::where('id', $id)->first();

        $old_image = $cms->banner_image;

        if($old_image){

            removeFile('uploads/compnycms/'.$old_image);

        }

        $cms = CompnyCmsPage::where('id', $id)->delete();

        if($cms) {

            return redirect('backend/compnycms')->with('success', trans('Compny Cms Page Deleted Successfully!'));

        } else {

            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));

        }

    }

    public function CompnyCmsPageDatatable(request $request)

    {

        if($request->ajax()){

            $roles = Session::get('roles');

            $query = CompnyCmsPage::select('id', 'name')->where('is_archive', '=', Constant::ARCHIVE)->orderBy('id', 'DESC');

            $list = $query->get();



            return DataTables::of($list)

                ->addColumn('action', function ($row) {

                    $roles = Session::get('roles');

                    $html = "";

                    $id = Crypt::encrypt($row->id);

                    $html .= "<span class='text-nowrap'>";

                    $html .= "<a href='".route('admin_compnycms-edit', array($id))."' rel='tooltip' title='".trans('Edit')."' class='btn btn-info btn-sm'><i class='fas fa-pencil-alt'></i></a>&nbsp";

                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_compnycms-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm delete'><i class='fa fa-trash-alt'></i></a>";

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

