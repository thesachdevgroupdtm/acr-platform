<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;
use App\Models\ServiceCategory;
class ContactController extends MainController
{
public function index()
{
    $return_data = array();
    $return_data['settings'] = $this->data;
    $return_data['site_title'] = trans('Contact US');

    // Add service categories
    $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
        ->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])
        ->orderBy('order_by', 'asc')
        ->get();

    // Set meta data specifically for Contact Us page
    $return_data['meta_title'] = 'Contact Auto Car Repair – Book Your Car Service Today';
    $return_data['meta_description'] = 'Have a question or need to book a service? Contact ACR for expert car services, repairs, and quick support. We\'re here for all your car maintenance needs!';
    $return_data['meta_keywords'] = 'contact car repair, car service booking, car service support, auto repair contact, vehicle maintenance help';

    return view('front.contact.index', array_merge($this->data, $return_data));
}

    /* === API SIBLINGS === */

    public function indexApi(Request $request)
    {
        $categories = ServiceCategory::select('id','slug','title','image','icon_image','description')
            ->where([['is_archive', Constant::NOT_ARCHIVE],['status', Constant::ACTIVE]])
            ->orderBy('order_by','asc')->get();

        return response()->json([
            'success'           => true,
            'service_categories'=> $categories,
            'seo'               => \App\Helpers\SeoHelper::build([
                'title'       => 'Contact Auto Car Repair – Book Your Car Service Today',
                'description' => "Have a question or need to book a service? Contact ACR for expert car services, repairs, and quick support. We're here for all your car maintenance needs!",
                'keywords'    => 'contact car repair, car service booking, car service support, auto repair contact, vehicle maintenance help',
                'site_name'   => $this->data['site_name'] ?? 'ACR',
                'url'         => $request->url(),
            ]),
        ]);
    }

    public function submitApi(Request $request)
    {
        $v = \Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'required|digits:10',
            'message' => 'required|string|max:5000',
            'location'=> 'nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $enquiry = \App\Models\Enquiry::create([
            'name'     => strip_tags($request->name),
            'email'    => $request->email,
            'phone'    => $request->phone,
            'location' => $request->location,
            'message'  => $request->message,
        ]);

        try { HomeController::sendDataToFreshFork($request); } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Our executive will contact you shortly.',
            'id'      => $enquiry->id,
        ]);
    }

    public function appointmentApi(Request $request)
    {
        return $this->submitApi($request);
    }

}