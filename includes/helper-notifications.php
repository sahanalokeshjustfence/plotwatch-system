<?php
if (!defined('ABSPATH')) exit;

/* =====================================================
   COMMON WHATSAPP SENDER (FINAL FIXED)
===================================================== */

function pw_send_whatsapp($template_name, $phone, $params){

    $token = PW_WHATSAPP_TOKEN;
    $phone_id = PW_PHONE_ID;

    $url = "https://graph.facebook.com/v22.0/$phone_id/messages";

    $parameters = [];

    // ✅ IMPORTANT: associative array with parameter_name
    foreach($params as $key => $value){
        $parameters[] = [
            "type" => "text",
            "parameter_name" => $key,
            "text" => (string)$value
        ];
    }

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $phone,
        "type" => "template",
        "template" => [
            "name" => $template_name,
            "language" => ["code" => "en"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => $parameters
                ]
            ]
        ]
    ];

    $args = [
        'headers' => [
            'Authorization' => "Bearer ".$token,
            'Content-Type' => "application/json"
        ],
        'body' => json_encode($data),
        'method' => 'POST'
    ];

    $response = wp_remote_post($url,$args);

    /* DEBUG LOG */

    if(is_wp_error($response)){
        error_log('[WHATSAPP ERROR] '.$response->get_error_message());
    }else{
        $body = wp_remote_retrieve_body($response);
        error_log('[WHATSAPP RESPONSE] '.$body);
    }

    return $response;
}


/* =====================================================
   COMMON EMAIL SENDER
===================================================== */

function pw_send_email($email,$subject,$message){

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($email,$subject,$message,$headers);
}


/* =====================================================
   ADDON NAME HELPER (SAFE)
===================================================== */

function pw_get_addon_names($addon_ids){

    global $wpdb;

    if(empty($addon_ids)) return 'None';

    // handle JSON safely
    if(is_string($addon_ids)){
        $decoded = json_decode($addon_ids,true);
        if(is_array($decoded)){
            $addon_ids = $decoded;
        }
    }

    if(empty($addon_ids)) return 'None';

    $table = $wpdb->prefix.'pw_addons';

    $placeholders = implode(',', array_fill(0,count($addon_ids),'%d'));

    $names = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT name FROM $table WHERE id IN ($placeholders)",
            ...$addon_ids
        )
    );

    return !empty($names) ? implode(', ', $names) : 'None';
}


/* =====================================================
   PROPERTY NOTIFICATION (OPERATION TEAM)
===================================================== */

function pw_notify_operation_team($property){

    global $wpdb;

    $profile_table = $wpdb->prefix.'pw_profile';

    $operators = get_users(['role'=>'operation_member']);

    foreach($operators as $op){

        $phone = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT mobile FROM $profile_table WHERE user_id=%d",
                $op->ID
            )
        );

        if(empty($phone)) continue;

        // format phone
        $phone = str_replace('+','',$phone);

        if(strlen($phone)==10){
            $phone = '91'.$phone;
        }

        /* WHATSAPP */

        pw_send_whatsapp("new_property_added",$phone,[
            "name" => $op->display_name,
            "property_id" => $property->property_code,
            "property_name" => $property->property_name,
            "location" => $property->location_name,
            "plot_size" => $property->plot_size
        ]);

        /* EMAIL */

        pw_send_email(
            $op->user_email,
            "New Property Added",
            "
            Hello {$op->display_name},<br><br>
            New property added.<br><br>
            Property ID: {$property->property_code}<br>
            Name: {$property->property_name}<br>
            Location: {$property->location_name}<br>
            Plot Size: {$property->plot_size}<br><br>
            Team JustFence
            "
        );
    }
}


/* =====================================================
   CUSTOMER PACKAGE NOTIFICATION (FINAL)
===================================================== */

function pw_notify_customer_package($property,$subscription){

    global $wpdb;

    /* GET PHONE */

    $profile_table = $wpdb->prefix.'pw_profile';

    $phone = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT mobile FROM $profile_table WHERE user_id=%d",
            $property->user_id
        )
    );

    // format phone
    if(!empty($phone)){
        $phone = str_replace('+','',$phone);
        if(strlen($phone)==10){
            $phone = '91'.$phone;
        }
    }

    /* ADDONS */

    $addons_text = pw_get_addon_names($subscription->addons);

    /* WHATSAPP */

    if(!empty($phone)){
        pw_send_whatsapp("package_assigned",$phone,[
            "name" => $property->display_name,
            "property_id" => $property->property_code,
            "property_name" => $property->property_name,
            "package_type" => $subscription->package_type,
            "start_date" => $subscription->start_date,
            "end_date" => $subscription->end_date,
            "price" => $subscription->package_price,
            "addons" => $addons_text
        ]);
    }

    /* EMAIL */

    pw_send_email(
        $property->user_email,
        "Package Assigned",
        "
        Hello {$property->display_name},<br><br>

        Your package has been assigned.<br><br>

        Property ID: {$property->property_code}<br>
        Name: {$property->property_name}<br>
        Package: {$subscription->package_type}<br>
        Start: {$subscription->start_date}<br>
        End: {$subscription->end_date}<br>
        Price: ₹{$subscription->package_price}<br>
        Add-ons: {$addons_text}<br><br>

        Team JustFence
        "
    );
}