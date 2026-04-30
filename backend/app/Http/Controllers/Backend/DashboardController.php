<?php

namespace App\Http\Controllers\Backend;

use App\Constant;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\ServiceCategory;
use App\Models\BookedSlot;
use App\Models\Order;
use App\Models\User;
use Session;

class DashboardController extends MainController
{
    public function index() 
    {
        $return_data = array();
        $return_data['site_title'] = trans('Dashboard');
        $return_data['total_product'] = Product::select('id')->where('is_archive', '=', Constant::NOT_ARCHIVE)->get()->count();
        $return_data['total_service_category'] = ServiceCategory::select('id')->where('is_archive', '=', Constant::NOT_ARCHIVE)->get()->count();
        $return_data['total_booked_service'] = BookedSlot::select('id')->get()->count();
        $return_data['total_order'] = Order::select('id')->get()->count();
        $return_data['total_user'] = User::select('id')->where('is_archive', '=', Constant::NOT_ARCHIVE)->get()->count();
        return view('backend.dashboard.index', array_merge($this->data, $return_data));
    }

    public function showchangePasswordForm()
    {
        $return_data = array();
        $return_data['site_title'] = trans('Change Password');
        return view('backend.dashboard.change_password', array_merge($this->data, $return_data));
    }

    public function changePassword(Request $request)
    {
        $current_password = Auth::guard('admin')->user()->password;

        if(!\Hash::check($request->old_password, $current_password)){
            return back()->with('error',trans('You have entered wrong old password!'));
        } else {
            $user_id = Auth::guard('admin')->user()->id;
            $password = \Hash::make($request->new_password);
            DB::table('admins')->where('id', $user_id)->update(['password' => $password]);
            return redirect('backend/dashboard')->with('success', trans('Your password updated successfully!'));
        }
        
    }
}
