<?php



namespace App\Http\Controllers\Front;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Constant;

use App\Models\Cart;

use App\Models\ScheduledPackageDetail;

use Session;

use Auth;



class CartController extends MainController

{

    public function index(request $request){
        $return_data = array();

        $return_data['site_title'] = trans('Cart');

        $pcart = Session::get('pcart');

        $scart = Session::get('scart');
        

        if(empty($pcart) && empty($scart)){

            return redirect('/');

        }

        $cart_ids = array();

        if(isset($scart['cart_id']) && $scart['cart_id']){

            array_push($cart_ids, $scart['cart_id']);

        }

        if($pcart && is_array($pcart)){

            foreach($pcart as $pval){

                if(isset($pval['cart_id']) && $pval['cart_id']){

                    array_push($cart_ids, $pval['cart_id']);

                }

            }

        }

        $cart_data = Cart::whereIn('id', $cart_ids)->get();
        

        $return_data['cart_data'] = $cart_data;



        $user_id = Auth::guard('user')->check() ? Auth::guard('user')->user()->id : NULL;

        if($user_id){

            // $addresses = UserAddress::where('user_id', $user_id)->get();

            // $return_data['addresses'] = $addresses;

        }

        return view('front/cart/index', array_merge($this->data, $return_data));
    }


    public function cartAjaxHtml(request $request){

        if($request->ajax()){

            $status = 'success';

            $pcart = Session::get('pcart');

            $scart = Session::get('scart');

            if(empty($pcart) && empty($scart)){

                $status = 'error';

            }

            $cart_ids = array();


            if($scart && is_array($scart)){

                foreach($scart as $pval){

                    if(isset($pval['cart_id']) && $pval['cart_id']){

                        array_push($cart_ids, $pval['cart_id']);

                    }

                }

            }

            if($pcart && is_array($pcart)){

                foreach($pcart as $pval){

                    if(isset($pval['cart_id']) && $pval['cart_id']){

                        array_push($cart_ids, $pval['cart_id']);

                    }

                }

            }

            $cart_data = Cart::with('productDetail', 'serviceDetail')->whereIn('id', $cart_ids)->get();

            $return_data = array();

            $return_data['cart_data'] = $cart_data;

            $html = view('front/cart/cart_ajax', array_merge($this->data, $return_data))->render();

            echo json_encode(array('status' => $status, 'html' => $html));

            exit;

        } else {

            return redirect('/');

        }

    }

    public function add(request $request)

    {

        if($request->ajax()){

            $cart_id = NULL;

            $service_cart = Session::get('scart') ? Session::get('scart') : array();

            $cart_service_info = Session::get('cart_service_info') ? Session::get('cart_service_info') : array();



            $cart_brand_id = $cart_model_id = $cart_fuel_type_id = $sel_brand_id = $sel_model_id = $sel_fuel_type_id = '';

            $result = array('result' => 'success', 'message' => 'success');

            if(isset($request->service_id) && $request->service_id){

                

                $spackageDetail = ScheduledPackageDetail::select('id', 'brand_id', 'model_id', 'fuel_type_id')->where('id',$request->service_id)->first();

                $sel_brand_id = isset($spackageDetail->brand_id) ? $spackageDetail->brand_id : NULL;

                $sel_model_id = isset($spackageDetail->model_id) ? $spackageDetail->model_id : NULL;

                $sel_fuel_type_id = isset($spackageDetail->fuel_type_id) ? $spackageDetail->fuel_type_id : NULL;



                if($cart_service_info){

                    $cart_brand_id = isset($cart_service_info['brand_id']) ? $cart_service_info['brand_id'] : NULL;

                    $cart_model_id = isset($cart_service_info['model_id']) ? $cart_service_info['model_id'] : NULL;

                    $cart_fuel_type_id = isset($cart_service_info['fuel_type_id']) ? $cart_service_info['fuel_type_id'] : NULL;

                } else {

                    $cart_service_info_new = array('brand_id' => $sel_brand_id, 'model_id' => $sel_model_id, 'fuel_type_id' => $sel_fuel_type_id);

                    Session::put('cart_service_info', $cart_service_info_new);

                }

            }



            if(!empty($cart_service_info)){

                if($sel_brand_id == $cart_brand_id && $sel_model_id == $cart_model_id && $sel_fuel_type_id == $cart_fuel_type_id){} else {

                    echo json_encode(array('result' => 'error', 'message' => 'Your cart is already fill with other model services.'));

                    exit;

                }

            }

            $user_id = Auth::guard('user')->check() ? Auth::guard('user')->user()->id : NULL;

            $product_cart = Session::get('pcart') ? Session::get('pcart') : array();

            

            if(isset($request->product_id) && $request->product_id){

                if(isset($product_cart[$request->product_id]) && $product_cart[$request->product_id]){

                    $cart_id = isset($product_cart[$request->product_id]['cart_id']) && $product_cart[$request->product_id]['cart_id'] ? $product_cart[$request->product_id]['cart_id'] : NULL;

                }

            }

            if(isset($request->service_id) && $request->service_id){

            // $cart_id = $this->isSameCategoryService($request->service_id);

                if(isset($service_cart[$request->service_id]) && $service_cart[$request->service_id]){

                    $cart_id = isset($service_cart[$request->service_id]['cart_id']) && $service_cart[$request->service_id]['cart_id'] ? $service_cart[$request->service_id]['cart_id'] : NULL;

                }

                /*if(isset($service_cart['service']) && $service_cart['service']){

            //                   dd($service_cart);

                    $cart_id = isset($service_cart['cart_id']) && $service_cart['cart_id'] ? $service_cart['cart_id'] : NULL;

                }*/

            }

            $service_cart = Session::get('scart') ? Session::get('scart') : array();

            $cfields = array('product_id', 'service_id', 'qty');



            if($cart_id){

                $cart_data = Cart::find($cart_id);

                $qty = $cart_data->qty;

                if(isset($cart_data->service_id) && $cart_data->service_id){

                    $request->qty = $request->qty;

                } else {

                    $request->qty = $request->qty + $qty;

                }

                

            } else {

                $cart_data = new Cart();

            }

            foreach($cfields as $cfield){

                $cart_data->$cfield = isset($request->$cfield) && $request->$cfield != '' ? $request->$cfield : NULL;

            }

            $cart_data->user_id = $user_id;

            $cart_data->save();

            $cart_id = $cart_data->id;



            if(isset($request->product_id) && $request->product_id){

                $product_cart[$request->product_id] = array('cart_id' => $cart_id, 'qty' => $request->qty);

                Session::put('pcart', $product_cart);

            }

            if(isset($request->service_id) && $request->service_id){

                $service_cart[$request->service_id] = array('cart_id' => $cart_id, 'qty' => $request->qty);

                Session::put('scart', $service_cart);

            }

            /*if(isset($request->service_id) && $request->service_id){

                $service_cart['service'] = $request->service_id;

                $service_cart['cart_id'] = $cart_id;

                Session::put('scart', $service_cart);

            }*/

            Session::save();

            echo json_encode($result);

            exit;

        } else {

            return redirect('/');

        }

    }



    public function isSameCategoryService($service_id){

        $serviceInfo = ScheduledPackageDetail::with('packageDetail')->select('id','sp_id')->where('id',$service_id)->first();

        $selected_category = isset($serviceInfo->packageDetail->sc_id) ? $serviceInfo->packageDetail->sc_id : NULL;



        $scart = array();

        $service_cart = Session::get('scart') ? Session::get('scart') : array();

        $cart_id = '';

        if($service_cart){

            foreach($service_cart as  $sk => $sv){

                $spquery = ScheduledPackageDetail::with('packageDetail')->select('id','sp_id')->where('id',$sk)->first();

                $service_category_id = isset($spquery->packageDetail->sc_id) ? $spquery->packageDetail->sc_id : NULL;

                if($selected_category == $service_category_id){

                    $cart_id = $sv['cart_id'];

                    $scart[$service_id] = $sv;

                    Cart::where('id', $cart_id)->update(array('service_id' => $service_id));

                } else {

                    $scart[$sk] = $sv;

                }

            }

            Session::put('scart', $scart);

        }

        return $cart_id;

    }



    public function itemCount(request $request)

    {

        if($request->ajax()){

            $product_cart = Session::get('pcart') ? Session::get('pcart') : array();

            $ptotal = is_array($product_cart) && $product_cart ? count($product_cart) : 0;



            $service_cart = Session::get('scart') ? Session::get('scart') : array();

            $stotal = is_array($service_cart) && $service_cart ? count($service_cart) : 0;

            //   $stotal = isset($service_cart['service']) && $service_cart['service'] ? 1 : 0;



            $total = $ptotal + $stotal;

            echo json_encode(array('total' => $total));

            exit;

        } else {

            return redirect('/');

        }

    }



    public function update(request $request){

        if($request->ajax()){

            Cart::where('id', $request->cart_id)->update(array('qty' => $request->qty));

            $cart = Session::get('pcart') ? Session::get('pcart') : array();

            $pcart = array();

            foreach($cart as $key => $val){

                foreach($val as $v){

                    if(isset($v['cart_id']) && $v['cart_id'] == $request->cart_id){

                        $cart[$key]['qty'] = $request->qty;

                    }

                }

            }

            Session::put('pcart', $cart);

        } else {

            return redirect('/');

        }

    }



    public function remove(request $request){

        if($request->ajax()){

            $type = $request->type;

            $cart_info = Cart::select('product_id', 'service_id')->where('id', $request->cart_id)->first();

            if(isset($cart_info->service_id) && $cart_info->service_id){

               // Session::put('scart', array());

                $cart = Session::get('scart') ? Session::get('scart') : array();

                $scart = array();

                foreach($cart as $key => $val){

                    if(isset($val['cart_id']) && $val['cart_id'] == $request->cart_id){ } else {

                        $scart[$key] = $val;

                    }

                }

                if(empty($scart)){

                    Session::put('cart_service_info', array());

                }

                Session::put('scart', $scart);

            } elseif(isset($cart_info->product_id) && $cart_info->product_id){

                $cart = Session::get('pcart') ? Session::get('pcart') : array();

                $pcart = array();

                foreach($cart as $key => $val){

                    if(isset($val['cart_id']) && $val['cart_id'] == $request->cart_id){ } else {

                        $pcart[$key] = $val;

                    }

                }

                Session::put('pcart', $pcart);

            }

            Cart::where('id', $request->cart_id)->delete();

        } else {

            return redirect('/');

        }

    }

    /* ===================================================================
     * API SIBLINGS — DB-backed cart keyed on Sanctum-authenticated user.
     * Web session-based methods above are unchanged.
     * =================================================================== */

    public function indexApi(Request $request)
    {
        $userId = $request->user()->id;
        $cart = Cart::with('productDetail','serviceDetail')
            ->where('user_id', $userId)->orderBy('id','desc')->get();

        return response()->json([
            'success' => true,
            'items'   => $cart,
            'totals'  => $this->cartTotals($cart),
        ]);
    }

    public function addApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'product_id' => 'nullable|integer|required_without:service_id',
            'service_id' => 'nullable|integer|required_without:product_id',
            'qty'        => 'required|integer|min:1',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $userId = $request->user()->id;

        // Same-vehicle rule (parity with legacy Session-based logic)
        if ($request->service_id) {
            $newPkg = ScheduledPackageDetail::select('id','brand_id','model_id','fuel_type_id')
                ->find($request->service_id);
            if (!$newPkg) {
                return response()->json(['success'=>false,'message'=>'Service package not found.'], 404);
            }
            $existingService = Cart::with('serviceDetail')
                ->where('user_id',$userId)->whereNotNull('service_id')->first();
            if ($existingService && $existingService->serviceDetail) {
                $e = $existingService->serviceDetail;
                if ($e->brand_id != $newPkg->brand_id || $e->model_id != $newPkg->model_id || $e->fuel_type_id != $newPkg->fuel_type_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your cart already contains services for a different vehicle.',
                    ], 409);
                }
            }
        }

        $q = Cart::where('user_id', $userId);
        if ($request->product_id)      $q->where('product_id', $request->product_id);
        elseif ($request->service_id)  $q->where('service_id', $request->service_id);
        $existing = $q->first();

        if ($existing) {
            $existing->qty = $request->service_id ? $request->qty : ($existing->qty + $request->qty);
            $existing->save();
            $row = $existing;
        } else {
            $row = Cart::create([
                'user_id'    => $userId,
                'product_id' => $request->product_id,
                'service_id' => $request->service_id,
                'qty'        => $request->qty,
            ]);
        }

        return response()->json([
            'success' => true,
            'item'    => $row->fresh(['productDetail','serviceDetail']),
            'count'   => Cart::where('user_id',$userId)->count(),
        ]);
    }

    public function updateApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'cart_id' => 'required|integer',
            'qty'     => 'required|integer|min:1',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $row = Cart::where('id',$request->cart_id)->where('user_id',$request->user()->id)->first();
        if (!$row) return response()->json(['success'=>false,'message'=>'Cart item not found.'], 404);

        $row->qty = $request->qty;
        $row->save();

        return response()->json([
            'success' => true,
            'item'    => $row->fresh(['productDetail','serviceDetail']),
        ]);
    }

    public function removeApi(Request $request)
    {
        $v = \Validator::make($request->all(), ['cart_id' => 'required|integer']);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $row = Cart::where('id',$request->cart_id)->where('user_id',$request->user()->id)->first();
        if (!$row) return response()->json(['success'=>false,'message'=>'Cart item not found.'], 404);

        $row->delete();

        return response()->json([
            'success' => true,
            'count'   => Cart::where('user_id',$request->user()->id)->count(),
        ]);
    }

    public function countApi(Request $request)
    {
        return response()->json([
            'success' => true,
            'count'   => Cart::where('user_id',$request->user()->id)->count(),
        ]);
    }

    /** Bulk-replay items the React client held in localStorage before login. */
    public function syncApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.qty' => 'required|integer|min:1',
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $userId = $request->user()->id;
        $added  = 0;
        foreach ($request->items as $i) {
            $pid = isset($i['product_id']) ? (int)$i['product_id'] : null;
            $sid = isset($i['service_id']) ? (int)$i['service_id'] : null;
            if (!$pid && !$sid) continue;

            $q = Cart::where('user_id',$userId);
            if ($pid) $q->where('product_id',$pid);
            if ($sid) $q->where('service_id',$sid);
            $existing = $q->first();
            if ($existing) {
                $existing->qty = $sid ? (int)$i['qty'] : ($existing->qty + (int)$i['qty']);
                $existing->save();
            } else {
                Cart::create([
                    'user_id'    => $userId,
                    'product_id' => $pid,
                    'service_id' => $sid,
                    'qty'        => (int)$i['qty'],
                ]);
            }
            $added++;
        }

        $cart = Cart::with('productDetail','serviceDetail')
            ->where('user_id',$userId)->orderBy('id','desc')->get();

        return response()->json([
            'success' => true,
            'merged'  => $added,
            'items'   => $cart,
            'totals'  => $this->cartTotals($cart),
        ]);
    }

    protected function cartTotals($cart)
    {
        $subtotal = 0;
        foreach ($cart as $row) {
            $price = 0;
            if ($row->product_id && isset($row->productDetail->price)) {
                $price = (float) $row->productDetail->price;
            } elseif ($row->service_id && isset($row->serviceDetail->price)) {
                $price = (float) $row->serviceDetail->price;
            }
            $subtotal += $price * (int) $row->qty;
        }
        return ['subtotal' => $subtotal, 'count' => $cart->count()];
    }

}