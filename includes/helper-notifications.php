<?php
if (!defined('ABSPATH')) exit;

function pw_send_whatsapp_template($phone,$name,$property_id,$property_name,$location,$plot_size){

$token = PW_WHATSAPP_TOKEN;
$phone_id = PW_PHONE_ID;

$url = "https://graph.facebook.com/v22.0/$phone_id/messages";

$data = [

"messaging_product"=>"whatsapp",

"to"=>$phone,

"type"=>"template",

"template"=>[
"name"=>"new_property_added",
"language"=>[
"code"=>"en"
],
"components"=>[
[
"type"=>"body",
"parameters"=>[
[
"type"=>"text",
"parameter_name"=>"name",
"text"=>$name
],
[
"type"=>"text",
"parameter_name"=>"property_id",
"text"=>$property_id
],
[
"type"=>"text",
"parameter_name"=>"property_name",
"text"=>$property_name
],
[
"type"=>"text",
"parameter_name"=>"location",
"text"=>$location
],
[
"type"=>"text",
"parameter_name"=>"plot_size",
"text"=>$plot_size
]
]
]
]
]

];

$args=[
'headers'=>[
'Authorization'=>"Bearer ".$token,
'Content-Type'=>"application/json"
],
'body'=>json_encode($data),
'method'=>'POST'
];

$response = wp_remote_post($url,$args);

/* DEBUG LOG */

if(is_wp_error($response)){
    error_log('[PLOTWATCH WHATSAPP ERROR] '.$response->get_error_message());
}else{
    error_log('[PLOTWATCH WHATSAPP RESPONSE] '.wp_remote_retrieve_body($response));
}

return $response;

}

function pw_send_property_email($email,$name,$property_id,$property_name,$location,$plot_size){

$subject="New Property Added";

$message="

Hello $name,<br><br>

A new property has been successfully added.<br><br>

<b>Property Details</b><br>

Property ID : $property_id<br>
Property Name : $property_name<br>
Location : $location<br>
Plot Size : $plot_size<br><br>

Please assign the required package and continue the process.<br><br>

Thank you,<br>
Team JustFence

";

$headers=['Content-Type: text/html; charset=UTF-8'];

wp_mail($email,$subject,$message,$headers);

}

function pw_notify_operation_team($property){

global $wpdb;

/* profile table */

$profile_table = $wpdb->prefix.'pw_profile';

/* get operation members */

$operators = get_users([
'role'=>'operation_member'
]);

foreach($operators as $op){

/* get mobile from pw_profile table */

$phone = $wpdb->get_var(
$wpdb->prepare(
"SELECT mobile FROM $profile_table WHERE user_id=%d",
$op->ID
)
);
error_log('[WHATSAPP PHONE] '.$phone);

$email = $op->user_email;
$name  = $op->display_name;

/* skip if phone empty */

if(empty($phone)){
continue;
}

/* remove + sign if exists */

$phone = str_replace('+','',$phone);

/* add country code if missing */

if(strlen($phone)==10){
    $phone = '91'.$phone;
}

/* send whatsapp */

pw_send_whatsapp_template(
$phone,
$name,
$property->property_code,
$property->property_name,
$property->location_name,
$property->plot_size
);

/* send email */

pw_send_property_email(
$email,
$name,
$property->property_code,
$property->property_name,
$property->location_name,
$property->plot_size
);

}

}