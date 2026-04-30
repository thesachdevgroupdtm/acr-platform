<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\States;
use Illuminate\Support\Facades\Validator;
use App\Constant;
use Auth;
use DB;

class UserController extends MainController
{
    public function myprofile(Request $request)
    {
        $return_data = array();
        $return_data['settings'] = $this->data;
        $return_data['site_title'] = trans('User Profile');
        $user_id = Auth::guard('user')->user()->id;
        $return_data['states'] = States::select('id','name')->orderBy('id','DESC')->get();
        $addresses = UserAddress::select('id','address', 'zip', 'city','state')->where('user_id',$user_id)->get();
        $return_data['addresses'] = $addresses;
        return view('front.user.profile',array_merge($this->data,$return_data)); 
    } 

    public function myprofileUpdate(request $request)
    {
        $user_id = auth()->user()->id;
        $this->validate($request, [
            'email' => [
                'required',
                Rule::unique('users')->where(function ($query) use($request, $user_id) {
                    return $query->where([['is_archive', Constant::NOT_ARCHIVE], ['id', '!=', $user_id]]);
                }),
            ],
        ]);

        $user =User::find($user_id);
        if($request->file('image')) {                     
            $old_image = auth()->user()->image;
            if($old_image){
                removeFile('uploads/user/'.$old_image);
            }
            $newName = fileUpload($request, 'image', 'uploads/user');
            $user->image = $newName;
        }
        $fields = array('firstname', 'lastname', 'phone', 'email');
        foreach($fields as $field){
            $user->$field = isset($request->$field) && $request->$field != '' ? $request->$field : NULL;
        }
        $user->updated_by = Auth::guard('user')->user()->id;
        $user->save();
        if($user)
        {
            $address = $request->address;
            if($address){
                foreach($address as $key=>$value){
                    $city = isset($request->city) ? $request->city : array();
                    $state = isset($request->state) ? $request->state : array();
                    $zip = isset($request->zip) ? $request->zip : array();
                    $aid = isset($request->aid) ? $request->aid : array();

                    if($value){
                        if(isset($aid[$key]) && $aid[$key]){
                            $user_address = UserAddress::find($aid[$key]);
                        } else {
                            $user_address = new UserAddress();
                        }
                        $user_address->address = $value;
                        $user_address->city = isset($city[$key]) && $city[$key] ? $city[$key] : NULL;
                        $user_address->state = isset($state[$key]) && $state[$key] ? $state[$key] : NULL;
                        $user_address->zip = isset($zip[$key]) && $zip[$key] ? $zip[$key] : NULL;
                        $user_address->user_id = $user->id;
                        $user_address->save();
                    }
                }
            }
            return redirect('my-profile')->with('success', trans('User Updated Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    public function addressDelete(request $request)
    {
        $address = UserAddress::where('id', $request->id)->delete();
        if($address)
        {
        return redirect('my-profile')->with('success', trans('Address Deleted Successfully!'));
        } else {
            return redirect()->back()->with('error', trans('Something went wrong, please try again later!'));
        }
    }

    /* === API SIBLINGS === */

    public function profileApi(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'success'  => true,
            'user' => [
                'id'        => $u->id,
                'firstname' => $u->firstname,
                'lastname'  => $u->lastname,
                'email'     => $u->email,
                'phone'     => $u->phone,
                'image'     => $u->image,
            ],
            'addresses' => UserAddress::select('id','address','zip','city','state')->where('user_id',$u->id)->get(),
            'states'    => States::select('id','name')->orderBy('name')->get(),
        ]);
    }

    public function addressListApi(Request $request)
    {
        return response()->json([
            'success'   => true,
            'addresses' => UserAddress::where('user_id',$request->user()->id)->get(),
        ]);
    }

    public function addressStoreApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'address' => 'required|string|max:1000',
            'city'    => 'required|string|max:255',
            'state'   => 'nullable|string|max:255',
            'zip'     => 'required|string|max:20',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $a = new UserAddress();
        $a->user_id = $request->user()->id;
        $a->address = $request->address;
        $a->city    = $request->city;
        $a->state   = $request->state;
        $a->zip     = $request->zip;
        $a->save();

        return response()->json(['success'=>true,'address'=>$a], 201);
    }

    public function addressUpdateApi(Request $request, $id)
    {
        $a = UserAddress::where('id',$id)->where('user_id',$request->user()->id)->first();
        if (!$a) return response()->json(['success'=>false,'message'=>'Address not found.'], 404);

        $v = \Validator::make($request->all(), [
            'address' => 'sometimes|required|string|max:1000',
            'city'    => 'sometimes|required|string|max:255',
            'state'   => 'nullable|string|max:255',
            'zip'     => 'sometimes|required|string|max:20',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        foreach (['address','city','state','zip'] as $f) {
            if ($request->filled($f)) $a->$f = $request->$f;
        }
        $a->save();

        return response()->json(['success'=>true,'address'=>$a]);
    }

    public function addressDeleteApi(Request $request, $id)
    {
        $a = UserAddress::where('id',$id)->where('user_id',$request->user()->id)->first();
        if (!$a) return response()->json(['success'=>false,'message'=>'Address not found.'], 404);
        $a->delete();
        return response()->json(['success'=>true,'message'=>'Address deleted.']);
    }
}
