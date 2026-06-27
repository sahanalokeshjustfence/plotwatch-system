<?php
if (!defined('ABSPATH')) exit;

function pw_visit_reminder() {

    global $wpdb;

    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $visits = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT v.*, p.*
            FROM {$wpdb->prefix}pw_visits v
            LEFT JOIN {$wpdb->prefix}pw_properties p
            ON v.property_id = p.id
            WHERE DATE(v.visit_date) = %s
            AND (
                v.engineer_id IS NULL
                OR v.engineer_id = 0
                OR v.visit_status = 'Pending'
            )",
            $tomorrow
        )
    );

    foreach ($visits as $visit) {

        $visitNo = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$wpdb->prefix}pw_visits
                WHERE property_id=%d
                AND id<=%d",
                $visit->property_id,
                $visit->id
            )
        );

        $visit_number = "Visit ".$visitNo;

        pw_notify_visit_assignment_reminder(
            $visit,
            $visit_number,
            $visit->visit_date
        );
    }
}

add_action('pw_visit_reminder_event', 'pw_visit_reminder');