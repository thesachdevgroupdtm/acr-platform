<?php

  

function changeDateFormate($date,$date_format){

    return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format($date_format);    

}



function getCmsPageName($id = ''){

    $pageData = App\Models\Page::select('name', 'description', 'slug')->where([['id' , $id],['is_archive','0']])->first();

    return $pageData;

}



function getCompnyCmsPages()

{

    $compnycmspages = App\Models\CompnyCmsPage::where([['is_archive','0']])->get();

    foreach($compnycmspages as $compnycmspage)

    {

        if($compnycmspage->section == 0)

        {

            $data['second_section'][] = $compnycmspage;

        }

        if($compnycmspage->section == 1)

        {

            $data['third_section'][] = $compnycmspage;

        }

        if($compnycmspage->section == 2)

        {

            $data['forth_section'][] = $compnycmspage;

        }

    }

    return $data;

}

function getTabServiceCmsPages()

{

    $tabservicecmspages = App\Models\getTabServiceCmsPages::where([['is_archive','0']])->get();

    foreach($tabservicecmspages as $tabservicecmspage)

    {

        if($tabservicecmspage->section == 0)

        {

            $data['second_section'][] = $tabservicecmspage;

        }

        if($tabservicecmspage->section == 1)

        {

            $data['third_section'][] = $tabservicecmspage;

        }

        if($tabservicecmspage->section == 2)

        {

            $data['forth_section'][] = $tabservicecmspage;

        }

    }

    return $data;

}



function getSettingInfo($label){

    $sData = App\Models\Page::select('value')->where('label', $label)->first();

    $result = isset($sData->value) && $sData->value ? $sData->value : NULL;

    return $result;

}



function getSettingDetail(){

    $AdminSetting_list = App\Models\Setting::select('id', 'label', 'value')->get();

    $settings = array();

    if (count($AdminSetting_list) != 0) {

        foreach ($AdminSetting_list as $item) {

            $settings[$item->label] = $item->value;

        }

    }



    return $settings;

}



function fileUpload($request, $file, $path){
        $imageName = $request->$file->getClientOriginalName();

        $file_ext = $request->$file->getClientOriginalExtension();

        $fileInfo = pathinfo($imageName);

        $filename = str_replace(' ', '', $fileInfo['filename']);

        $filename = str_replace('(', '', $filename);

        $filename = str_replace(')', '', $filename);

        $newname = $filename.time() . "." . $file_ext;

        $destinationPath1 = public_path($path);

        $request->file($file)->move($destinationPath1, $newname);

        return $newname;

}



function removeFile($path){

    $filePath = public_path($path);

    File::delete($filePath);

}



function removeDirectory($path){

    $filePath = public_path($path);

    File::deleteDirectory($filePath);

}



function slugify($text){

    // replace non letter or digits by -

    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate

    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters

    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim

    $text = trim($text, '-');

    // remove duplicate -

    $text = preg_replace('~-+~', '-', $text);

    // lowercase

    $text = strtolower($text);

    if (empty($text)) {

        return 'n-a';

    }

    return $text;

}



function getColumnValueinArray($table_nm, $column, $where = null){

    $array = array();

    $query = DB::table($table_nm)

                    ->select($column);

    if($where != ''){

        $query->where($where);

    }



    $result = $query->get();

    if($result->count()){

        foreach($result as $value){

            array_push($array, $value->$column);

        }

    }

    return $array;

}



function getColumnValueFromWhere($table_nm, $column, $where = null){

    $column_val = '';

    $query = DB::table($table_nm)

                    ->select($column);

    if($where != ''){

        $query->where($where);

    }



    $result = $query->first();

    $column_val = isset($result->$column) && $result->$column ? $result->$column : NULL;

    return $column_val;

}



function getRowByWhere($table_nm, $where = null){

    $query = DB::table($table_nm)

                    ->select('*');

    if($where != ''){

        $query->where($where);

    }



    $result = $query->first();

    return $result;

}



function generateRandomString($length = 10) {

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $charactersLength = strlen($characters);

    $randomString = '';

    for ($i = 0; $i < $length; $i++) {

        $randomString .= $characters[rand(0, $charactersLength - 1)];

    }

    return $randomString;

}



function formatNumber($no){

    $no = $no ? number_format($no, 2, '.', '') : 0.00;

    return $no;

}



function containsDecimal( $value ) {

    if ( strpos( $value, "." ) !== false ) {

        return true;

    }

    return false;

}



function getHighestValueFromArray($array, $return_array = array(), $heighest_value = 0){

    $key = array_search(max($array), $array);



    if($heighest_value == $array[$key]) {

        $return_array[$key] = $array[$key];

    } elseif($heighest_value < $array[$key]){

        $return_array[$key] = $array[$key];

        $heighest_value = $array[$key];

        unset($array[$key]);

        if(count($array)){

            $return_array = getHighestValueFromArray($array, $return_array, $heighest_value);

        }

    }

    return $return_array;

}



function getLowestValueFromArray($array){

    $key = array_search(min($array), $array);

    return $key;

}



function diffMonth($from, $to) {

    $fromYear = date("Y", strtotime($from));

    $fromMonth = date("m", strtotime($from));

    $toYear = date("Y", strtotime($to));

    $toMonth = date("m", strtotime($to));

    if ($fromYear == $toYear) {

        return ($toMonth-$fromMonth)+1;

    } else {

        return (12-$fromMonth)+1+$toMonth;

    }

}



function checkDeleteConstrainnt($relationArray, $value){

    if($relationArray){

        foreach($relationArray as $rk => $rv){

            $table = isset($rv['table']) && $rv['table'] ? $rv['table'] : NULL;

            $column = isset($rv['column']) && $rv['column'] ? $rv['column'] : NULL;

            $relation_info = DB::table($table)->select('id')->where([[$column, $value], ['is_archive', App\Constant::NOT_ARCHIVE]])->get()->count();

            if($relation_info){

                return FALSE;

                exit;

            }

        }

    }

    return TRUE;

}

function getFooterContent(){
    $test= \App\Models\Setting::select( 'name', 'label', 'value')->where('label','footer_section')->first();
    return $test->value;
}

function getServiceCategory(){

    $services = \App\Models\ServiceCategory::select('id', 'title','slug')->where([['is_archive', \App\Constant::NOT_ARCHIVE], ['status', \App\Constant::ACTIVE]])->orderBy('id', 'desc')->get();

    return $services;

}



function getbrands(){

    $brands = \App\Models\CarBrand::select('id', 'image')->where([['is_archive', \App\Constant::NOT_ARCHIVE], ['status', \App\Constant::ACTIVE]])->orderBy('id', 'desc')->get();

    return $brands;

}



function getmodel(){

    $models = \App\Models\CarModel::select('id', 'image')->where([['is_archive', \App\Constant::NOT_ARCHIVE], ['status', \App\Constant::ACTIVE]])->orderBy('id', 'desc')->get();

    return $models;

}



function weekOfDays($total_days){

    $weekOfdays = array();

    $date = date("l");// current day

    $weekends = 0;

    for($i = 0; $i < $total_days; $i++) {

        $day = date("l", strtotime($date . "+$i day"));

        if($day == 'Sunday'){

            $weekends++;

        }

    }

    $total_days = $total_days + $weekends;

    for($i = 0; $i < $total_days; $i++) {

        $day = date("l", strtotime($date . "+$i day"));

        if($day != 'Sunday'){

            $weekOfdays[] = date("d M", strtotime($date . "+$i day"));

        }

    }

    return $weekOfdays;

}



function getBrandSlugFromBrandId($brand_id = ''){

    $qinfo = App\Models\CarBrand::select('slug')->where('id', $brand_id)->first();

    $slug = isset($qinfo->slug) ? $qinfo->slug : NULL;

    return $slug;

}



function getModelSlugFromModelId($model_id = ''){

    $qinfo = App\Models\CarModel::select('slug')->where('id', $model_id)->first();

    $slug = isset($qinfo->slug) ? $qinfo->slug : NULL;

    return $slug;

}



function getFuelSlugFromFuelId($fuel_id = ''){

    $qinfo = App\Models\FuelType::select('slug')->where('id', $fuel_id)->first();

    $slug = isset($qinfo->slug) ? $qinfo->slug : NULL;

    return $slug;

}



function getServicePrice($brand = '',$model = '', $fuel = '', $sp_id = ''){

    $priceInfo = App\Models\ScheduledPackageDetail::select('id','price')->where([['sp_id', $sp_id],['brand_id', $brand], ['model_id', $model], ['fuel_type_id', $fuel]])->first();

    return $priceInfo;

}



function getDefualtServiceSlug(){

    $brand_id = Session::get('brand_id');

    $model_id = Session::get('model_id');

    $fuel_id = Session::get('fuel_id');



    /*if($brand_id && $model_id && $fuel_id){} else{

        $brandInfo = App\Models\CarBrand::select('id')->where([['title', 'MARUTI SUZUKI']])->first();

        $brand_id = isset($brandInfo->id) ? $brandInfo->id  : NULL;



        $modelInfo = \App\Models\CarModel::select('id')->where([['title', 'SWIFT']])->first();

        $model_id = isset($modelInfo->id) ? $modelInfo->id  : NULL;



        $fuelInfo = App\Models\FuelType::select('id')->where([['title', 'Petrol']])->first();

        $fuel_id =  isset($fuelInfo->id) ? $fuelInfo->id  : NULL;

    }*/

    $brandInfo = App\Models\CarBrand::select('id', 'slug')->where([['id', $brand_id]])->first();

    $brand = isset($brandInfo->slug) ? $brandInfo->slug : NULL;

    $modelInfo = \App\Models\CarModel::select('id', 'slug')->where([['id', $model_id]])->first();

    $model = isset($modelInfo->slug) ? $modelInfo->slug : NULL;

    $fuelInfo = App\Models\FuelType::select('id', 'slug')->where([['id', $fuel_id]])->first();

    $fuel = isset($fuelInfo->slug) ? $fuelInfo->slug : NULL;



    return $brand.'/'.$model.'/'.$fuel;

}

function renderExcerpts($string,$wordCount){
    $words = str_word_count($string, 1);
    return implode(' ', array_slice($words, 0, $wordCount));
}

function renderCountedCharacter($string,$chars){
    $string=trim($string,"  ");
    $string=strip_tags($string);
    return substr($string, 0, $chars);
}