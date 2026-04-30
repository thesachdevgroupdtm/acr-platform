<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\ServiceCategory;
use App\Constant;

class FaqController extends MainController
{
    public function index()
{
    $return_data['site_title'] = trans('Faqs');
    $return_data['faqs'] = Faq::select('id','name','description')
        ->where('is_archive','0')
        ->orderBy('updated_at','desc')
        ->get();

    $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
        ->where([
            ['is_archive', Constant::NOT_ARCHIVE],
            ['status', Constant::ACTIVE]
        ])
        ->orderBy('order_by', 'asc')
        ->get();

    // Only add meta data for this specific page
    $return_data['meta_title'] = 'FAQs – Car Service Information & Common Repair Queries';
    $return_data['meta_description'] = 'Find helpful answers to frequently asked questions about car repairs, service options, and more. Visit our FAQ page to learn more about Auto Car Repair.';
    $return_data['meta_keywords'] = 'Car service FAQs, Auto repair questions, Common car issues, Car maintenance help, Auto repair tips';

    return view('front.faq.index', array_merge($this->data, $return_data));
}

    /* === API SIBLING === */
    public function indexApi(Request $request)
    {
        $q = Faq::select('id','service_category_id','name','description')
            ->where('is_archive', Constant::NOT_ARCHIVE);

        if ($request->filled('category_id')) {
            $q->where('service_category_id', $request->category_id);
        }

        return response()->json([
            'success' => true,
            'faqs'    => $q->orderBy('updated_at','desc')->get(),
            'seo'    => \App\Helpers\SeoHelper::build([
                'title'       => 'FAQs – Car Service Information & Common Repair Queries',
                'description' => 'Find helpful answers to frequently asked questions about car repairs, service options, and more.',
                'keywords'    => 'Car service FAQs, Auto repair questions, Common car issues, Car maintenance help, Auto repair tips',
                'site_name'   => $this->data['site_name'] ?? 'ACR',
                'url'         => $request->url(),
            ]),
        ]);
    }

}
