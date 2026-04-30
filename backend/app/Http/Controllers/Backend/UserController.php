<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAddress;
use Auth;
use Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Constant;
use DataTables;

class UserController extends MainController
{
    public function index()
    {
        $return_data = array();       
        $return_data['site_title'] = trans('Users');
        return view('backend.user.list', array_merge($this->data, $return_data));
    }

    //listing 
    public function userDatatable(request $request)
    {
        if($request->ajax()){
            $roles = Session::get('roles');
            $query = User::select('id', 'firstname','lastname','email','phone','address')->where('is_archive', '=', Constant::NOT_ARCHIVE)->orderBy('id', 'DESC');
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('id', function($row) {
                    $html = "";
                    $html .= '<label class="form-check">
                                <input class="form-check-input checkSingle" type="checkbox" value="'.$row->id.'">
                                <span class="form-check-label">
                                    '.$row->id.'
                                </span>
                            </label>';
                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    // $html .= "<a href='javascript:void(0);' data-href='".route('admin_user-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm mr-20 delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    $html .= "<a href='".route('admin_user-detail', array($id))."' class='btn btn-small btn-info'>Detail</a>";
                    
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['action','id'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function destroy(string $id)
    {
        $id = Crypt::decrypt($id);
        $user = User::where('id', $id)->delete();
        if($user) {
            return redirect('backend/user')->with('success', trans('User Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function alldelete(Request $request)
    {
        if($request->ajax()){
            $admins = $request->admin;
            $return = array();
            $return['result'] = 'error';
            if($admins){
                foreach($admins as $user){
                    $admin = User::where('id',$user)->delete();
                }
                if(isset($admin)){
                    $return['result'] = 'success';
                }
                echo json_encode($return);
                exit;
            } else {
                return redirect('/');
            }
        }
    }

    public function address(request $request,$id){
        $return_data = array();       
        $return_data['site_title'] = trans('User Address');
        $return_data['user_id'] = $request->user_id;
        return view('backend.user.address', array_merge($this->data, $return_data));
    }

    public function userAddressDatatable(request $request)
    {  
        if($request->ajax()){
            $query = UserAddress::select('id','user_id','address','zip','city')->with('userDetail')->where('user_id', $request->user_id);
            $list = $query->get();

            return DataTables::of($list)
                ->addColumn('user', function($row) {
                    return $row->userDetail->firstname;
                })
                ->addColumn('action', function ($row) {
                    $html = "";
                    $id = Crypt::encrypt($row->id);
                    $html .= "<span class='text-nowrap'>";
                    $html .= "<a href='javascript:void(0);' data-href='".route('admin_user-address-delete',array($id))."' rel='tooltip' title='".trans('Delete')."' class='btn btn-danger btn-sm mr-20 delete'><i class='fa fa-trash-alt'></i></a>&nbsp";
                    $html .= "</span>";
                    return $html;
                })
                ->rawColumns(['user','address','action'])
                ->make(true);
        } else {
            return redirect('backend/dashboard');
        }
    }

    public function addressDestroy(string $id)
    {
        $id = Crypt::decrypt($id);
        $useraddress = UserAddress::where('id', $id)->delete();
        if($useraddress) {
            return redirect()->back()->with('success', trans('User adrress deleted successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function detail(request $request, $id){
        $id = Crypt::decrypt($id);
        $return_data = array();       
        $return_data['site_title'] = trans('User Detail');
        $detail = User::find($id);
        if(!isset($detail->id)){
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
        $return_data['detail'] = $detail;
        return view('backend.user.detail', array_merge($this->data, $return_data));
    }

    public function ordersDatatable(){
        
    }
}
