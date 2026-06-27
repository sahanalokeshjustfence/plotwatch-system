<?php
if (!defined('ABSPATH')) exit;

/* =====================================================
   COMMON WHATSAPP SENDER (FINAL FIXED)
===================================================== */

function pw_send_whatsapp($templateId, $campaignName, $phone, $params){

    $token = PW_SKALEBOT_TOKEN;
    $url = "https://api.skalebot.com/api/v1/campaign/bulk";

    $data = [
        "campaignName" => $campaignName,
        "templateId"   => $templateId,
        "variables"    => $params,
        "customerData" => [
            [
               "name" => reset($params),
                "phone" => $phone
            ]
        ],
        "groupIds" => [],
        "SKUCodes" => []
    ];

    $args = [
        'headers' => [
            'Authorization' => $token,
            'Content-Type'  => 'application/json'
        ],
        'body'   => json_encode($data),
        'method' => 'POST'
    ];
error_log("===== SKALEBOT PAYLOAD =====");
error_log(json_encode($data, JSON_PRETTY_PRINT));
    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('[WHATSAPP ERROR] '.$response->get_error_message());
    } else {
        error_log('[WHATSAPP RESPONSE] '.wp_remote_retrieve_body($response));
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

       pw_send_whatsapp(
    8671, // <-- Replace with your New Property template ID
    "New Property Added",
    $phone,
   [
    "{{1}}" => $op->display_name,
    "{{2}}" => $property->property_code,
    "{{3}}" => $property->property_name,
    "{{4}}" => $property->location_name,
    "{{5}}" => $property->plot_size
]
);

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
/* =====================================================
   ENGINEER VISIT ASSIGNED NOTIFICATION
===================================================== */

function pw_notify_engineer_visit($engineer, $phone, $property, $visit_number, $visit_date){

    // WhatsApp
    pw_send_whatsapp(
        8680, // Replace with Engineer Visit Template ID
        "Engineer Visit Assigned",
        $phone,
        [
    "{{1}}" => $engineer->display_name,
    "{{2}}" => $visit_number,
    "{{3}}" => $property->property_code,
    "{{4}}" => $property->property_name,
    "{{5}}" => $visit_date,
    "{{6}}" => $property->address,
    "{{7}}" => $property->google_map
]
    );

    // Email
    pw_send_email(
        $engineer->user_email,
        "New Plotwatch Site Visit Assigned",
        "
        Hello {$engineer->display_name},<br><br>

        A new Plotwatch site visit has been assigned to you.<br><br>

        <b>Visit Number:</b> {$visit_number}<br>
        <b>Scheduled Date:</b> {$visit_date}<br>
        <b>Property ID:</b> {$property->property_code}<br>
        <b>Property Name:</b> {$property->property_name}<br>
        <b>Full Address:</b> {$property->address}<br>
        <b>Google Map:</b>
        <a href='{$property->google_map}'>Open Location</a><br><br>

        Regards,<br>
        Plotwatch Team
        "
    );
}


/* =====================================================
   VISIT COMPLETED NOTIFICATION
===================================================== */

function pw_notify_visit_completed($property, $engineer, $visit_number, $visit_date){

    global $wpdb;

    $profile_table = $wpdb->prefix.'pw_profile';

    // Send to all Operation Members
    $operators = get_users(['role' => 'operation_member']);

    foreach($operators as $op){

        $phone = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT mobile FROM $profile_table WHERE user_id=%d",
                $op->ID
            )
        );

        if(!empty($phone)){
            $phone = str_replace('+','',$phone);

            if(strlen($phone)==10){
                $phone = '91'.$phone;
            }

            // WhatsApp
            pw_send_whatsapp(
                8682, // Replace with your Visit Completed Template ID
                "Visit Completed",
                $phone,
                [
                    "{{1}}" => $op->display_name,
                    "{{2}}" => $visit_number,
                    "{{3}}" => $property->property_code,
                    "{{4}}" => $property->property_name,
                    "{{5}}" => $engineer->display_name,
                    "{{6}}" => $visit_date
                ]
            );
        }

        // Email to Operation Member
        pw_send_email(
            $op->user_email,
            "PlotWatch Site Visit Completed",
            "
            Hello {$op->display_name},<br><br>

            A PlotWatch site visit has been completed successfully.<br><br>

            <b>Visit Number:</b> {$visit_number}<br>
            <b>Property ID:</b> {$property->property_code}<br>
            <b>Property Name:</b> {$property->property_name}<br>
            <b>Completed By:</b> {$engineer->display_name}<br>
            <b>Visit Date:</b> {$visit_date}<br><br>

            Please review the submitted report by logging in to PlotWatch.<br><br>

            <a href='https://plotwatch.in'>https://plotwatch.in</a><br><br>

            Team Plotwatch
            "
        );
    }

    // Email to Customer
    pw_send_email(
        $property->user_email,
        "Your PlotWatch Site Visit Has Been Completed",
        "
        Hello {$property->display_name},<br><br>

        Your PlotWatch site visit has been completed successfully.<br><br>

        <b>Visit Number:</b> {$visit_number}<br>
        <b>Property ID:</b> {$property->property_code}<br>
        <b>Property Name:</b> {$property->property_name}<br>
        <b>Completed By:</b> {$engineer->display_name}<br>
        <b>Visit Date:</b> {$visit_date}<br><br>
        
        Please review the submitted report by logging in to PlotWatch.<br><br>

            <a href='https://plotwatch.in'>https://plotwatch.in</a><br><br>

        Thank you for choosing JustFence.<br><br>

        Team Plotwatch
        "
    );
}

/* =====================================================
   VISIT ASSIGNMENT REMINDER (1 DAY BEFORE)
===================================================== */

function pw_notify_visit_assignment_reminder($property, $visit_number, $visit_date){

    global $wpdb;

    $profile_table = $wpdb->prefix.'pw_profile';

    $operators = get_users(['role' => 'operation_member']);

    foreach($operators as $op){

        $phone = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT mobile
                 FROM $profile_table
                 WHERE user_id=%d",
                $op->ID
            )
        );

        if(!empty($phone)){

            $phone = str_replace('+','',$phone);

            if(strlen($phone)==10){
                $phone = '91'.$phone;
            }

            /* ==========================
               WHATSAPP
            ========================== */

            pw_send_whatsapp(
                8683,   // Replace with your Reminder Template ID
                "Visit Assignment Reminder",
                $phone,
                [
                    "{{1}}" => $op->display_name,
                    "{{2}}" => $visit_number,
                    "{{3}}" => $property->property_code,
                    "{{4}}" => $property->property_name,
                    "{{5}}" => $visit_date,
                    "{{6}}" => $property->contact_number
                ]
            );
        }

        /* ==========================
           EMAIL
        ========================== */

        pw_send_email(
            $op->user_email,
            "Engineer Assignment Reminder",
            "
            Hello {$op->display_name},<br><br>

            This is a reminder that the following PlotWatch site visit is scheduled for tomorrow but has not yet been assigned to an engineer.<br><br>

            <b>Visit Number:</b> {$visit_number}<br>
            <b>Property ID:</b> {$property->property_code}<br>
            <b>Property Name:</b> {$property->property_name}<br>
            <b>Customer Number:</b> {$property->contact_number}<br>
            <b>Visit Date:</b> {$visit_date}<br><br>

            Please assign an engineer and schedule the visit before tomorrow.<br><br>

            Regards,<br>
            Team JustFence
            "
        );
    }

}