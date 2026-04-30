<?php



namespace App\Http\Controllers\Front;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Models\Seo;
use App\Constant;



class offerController extends MainController

{

    public function index()

    {

        $return_data = array();

        $return_data['settings'] = $this->data;

        // $return_data['site_title'] = trans('offer');
        $return_data['site_name'] = trans('offer');

         // Add service categories
         $return_data['scategories'] = ServiceCategory::select('id', 'slug', 'title', 'image', 'icon_image', 'description')
         ->where([
             ['is_archive', Constant::NOT_ARCHIVE],
             ['status', Constant::ACTIVE]
         ])
         ->orderBy('order_by', 'asc')
         ->get();
         
        $offer = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')->where('id', Constant::OFFERS_SEO_ID)->first();
        $return_data['meta_keywords'] =  isset($offer->meta_keyword) && $offer->meta_keyword ? $offer->meta_keyword : NULL;
        $return_data['meta_description'] = isset($offer->meta_description) && $offer->meta_description ? $offer->meta_description : NULL;
        $return_data['canonical_tag'] =  isset($offer->canonical_tag) && $offer->canonical_tag ? $offer->canonical_tag : NULL;
        $return_data['extra_meta_description'] =  isset($offer->extra_meta_description) && $offer->extra_meta_description ? $offer->extra_meta_description : NULL;
        
        $return_data['meta_title'] =  isset($offer->meta_title) && $offer->meta_title ? $offer->meta_title : NULL;

        return view('front/offer/index',array_merge($this->data,$return_data));

    }

    /* === API SIBLING === */

    public function indexApi(Request $request)
    {
        $offers = \App\Models\OfferSlider::select('id','title1','title2','image','image_url','image_title','btn_link','btn_title','background','title_color','subtitle_color')
            ->where('membership_package',0)->orderBy('reorder','ASC')->get();

        $tabular = \App\Models\TabularOffer::orderBy('reorder','asc')->get();

        $seoRow = Seo::select('meta_title','meta_keyword','meta_description','extra_meta_description','canonical_tag')
            ->where('id', Constant::OFFERS_SEO_ID)->first();

        return response()->json([
            'success'        => true,
            'offers'         => $offers,
            'tabular_offers' => $tabular,
            'seo'            => \App\Helpers\SeoHelper::fromModel($seoRow, [
                'site_name' => $this->data['site_name'] ?? 'ACR',
                'url'       => $request->url(),
            ]),
        ]);
    }

}