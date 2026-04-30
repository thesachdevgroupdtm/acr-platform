<?php

   try{
       

$curl = curl_init();

$name=$_POST['name'];
$email=$_POST['email'];
$mobile=$_POST['mobile'];
$location=$_POST['location'];
$visitdate=$_POST['servicedate'];
$model="BMW";
$enquiry_type="ACR Service";
//$model=$_POST['model'];
//$reg=$_POST['vehicleregistration'];
//$insurance=$_POST['currentinsurancecompany'];
$timestamp=time();
$formname="ACR Landing";
//$plan=$_POST['plantobuy'];
$sourceid = "70001109499";
$sources=$_POST['utm_source'];
$medium=$_POST['utm_medium'];
$campaign=$_POST['utm_campaign'];
$term=$_POST['utm_term'];
$content=$_POST['utm_content'];


curl_setopt_array($curl, [
  CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/search.json?include=contact&q=".$mobile."&qf=mobile",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => $objPass,
  CURLOPT_HTTPHEADER => [
    "Authorization: Token token=eAYC1H05AnUO6D_w-W2Fbg",
    "Content-Type: application/json"
  ],
]);

$response = curl_exec($curl);

$err = curl_error($curl);

curl_close($curl);


   if($location==""){
    $location=$_POST['location'];
}

    


$jsonobj=array("contact"=>array("first_name"=>$name."-".$timestamp,"last_name"=>".","email"=>$email,"mobile_number"=>$mobile,"lead_source_id"=>$sourceid,"custom_field"=>array("cf_enquiry_type"=>$enquiry_type,"cf_ford_showroom_location"=>$location,"lead_source_id"=>$source,"cf_acr_service_model"=>$model,"cf_utm_source"=>$sources,"cf_utm_medium"=>$medium,"cf_utm_campaign"=>$campaign,"cf_utm_term"=>$term,"cf_utm_content"=>$content,"cf_form_name"=>$formname)));
$objPass=json_encode($jsonobj);







  $result = json_decode($response);
//print_r($result);
//die();
if(!empty($result)){
    


$jsonobj1=array("contact"=>array("first_name"=>$name."-".$timestamp,"last_name"=>".","email"=>$email,"custom_field"=>array("cf_enquiry_type"=>$enquiry_type,"cf_ford_showroom_location"=>$location,"lead_source_id"=>$source,"cf_acr_service_model"=>$model,"cf_utm_source"=>$sources,"cf_utm_medium"=>$medium,"cf_utm_campaign"=>$campaign,"cf_utm_term"=>$term,"cf_utm_content"=>$content,"cf_form_name"=>$formname)));
$objPass1=json_encode($jsonobj1);



    echo "working update2<br>";
    
    $result = json_decode($response);

    $responseid= $result[0]->id;  

$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/contacts/".$responseid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "PUT",
  CURLOPT_POSTFIELDS => $objPass1,
  CURLOPT_HTTPHEADER => [
    "Authorization: Token token=eAYC1H05AnUO6D_w-W2Fbg",
    "Content-Type: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
  header('Location: thankyou.html');
}
    
    
}else{
    
    
$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://harpreet-ford.myfreshworks.com/crm/sales/api/contacts",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $objPass,
  CURLOPT_HTTPHEADER => [
    "Authorization: Token token=eAYC1H05AnUO6D_w-W2Fbg",
    "Content-Type: application/json"
  ],
]);

$response = curl_exec($curl);


$err = curl_error($curl);

curl_close($curl);



if ($err) {
  echo "cURL Error #:" . $err;
} else {
    echo "ee";
  echo $response;
  header('Location: thankyou.html');
}

}
     
   }catch (Exception $ex) {
        curl_close($curl);
    }
  

 ?>