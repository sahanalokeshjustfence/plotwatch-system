<?php
if (!defined('ABSPATH')) exit;

class PW_Properties {

    public function __construct() {

        add_action('init', [$this, 'handle_property_submission']);
        add_action('init', [$this, 'handle_property_update']);
        add_action('init', [$this, 'handle_package_assignment']);
        add_action('init', [$this, 'handle_engineer_report']);

    }

    /*
    |--------------------------------------------------------------------------
    | ADD PROPERTY (Customer)
    |--------------------------------------------------------------------------
    */

    public function handle_property_submission() {

        if (!isset($_POST['pw_add_property'])) return;
        if (!is_user_logged_in()) return;

        if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_add_property_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pw_properties';

        $wpdb->insert(
            $table,
            [
                'user_id'              => get_current_user_id(),
                'property_name'        => sanitize_text_field($_POST['property_name']),
                'location_name'        => sanitize_text_field($_POST['location_name']),
                'address'              => sanitize_textarea_field($_POST['address']),
                'google_map'           => sanitize_text_field($_POST['google_map']),
                'plot_size'            => sanitize_text_field($_POST['plot_size']),
                'property_type'        => sanitize_text_field($_POST['property_type']),
                'contact_person'       => sanitize_text_field($_POST['contact_person']),
                'contact_number'       => sanitize_text_field($_POST['contact_number']),
                'special_instructions' => sanitize_textarea_field($_POST['special_instructions']),
                'subscription_status'  => 'Pending Package Assignment',
                'created_at'           => current_time('mysql')
            ]
        );

        $insert_id = $wpdb->insert_id;

        if ($insert_id) {

            $property_code = 'PW' . str_pad($insert_id, 4, '0', STR_PAD_LEFT);

            $wpdb->update(
                $table,
                ['property_code' => $property_code],
                ['id' => $insert_id]
            );

            wp_safe_redirect(home_url('/customer-dashboard?added=1'));
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROPERTY (Customer)
    |--------------------------------------------------------------------------
    */

    public function handle_property_update() {

        if (!isset($_POST['pw_update_property'])) return;
        if (!is_user_logged_in()) return;

        if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_update_property_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pw_properties';

        $property_id = intval($_POST['property_id']);
        $user_id     = get_current_user_id();

        $owner_check = $wpdb->get_var(
            $wpdb->prepare("SELECT user_id FROM $table WHERE id = %d", $property_id)
        );

        if ($owner_check != $user_id) {
            wp_die('Unauthorized access.');
        }

        $wpdb->update(
            $table,
            [
                'property_name'        => sanitize_text_field($_POST['property_name']),
                'location_name'        => sanitize_text_field($_POST['location_name']),
                'address'              => sanitize_textarea_field($_POST['address']),
                'google_map'           => sanitize_text_field($_POST['google_map']),
                'plot_size'            => sanitize_text_field($_POST['plot_size']),
                'property_type'        => sanitize_text_field($_POST['property_type']),
                'contact_person'       => sanitize_text_field($_POST['contact_person']),
                'contact_number'       => sanitize_text_field($_POST['contact_number']),
                'special_instructions' => sanitize_textarea_field($_POST['special_instructions']),
            ],
            ['id' => $property_id]
        );

        wp_safe_redirect(home_url('/customer-dashboard?updated=1'));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | PACKAGE ASSIGNMENT (Operation Member)
    |--------------------------------------------------------------------------
    */

    public function handle_package_assignment() {

        if (!isset($_POST['pw_assign_package'])) return;
        if (!is_user_logged_in()) return;

        if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_assign_package_nonce')) {
            wp_die('Security failed');
        }

        $current_user = wp_get_current_user();

        if (!in_array('operation_member', $current_user->roles)) {
            wp_die('Unauthorized.');
        }

        global $wpdb;

        $property_id = intval($_POST['property_id']);
        $addons = isset($_POST['addons']) ? json_encode($_POST['addons']) : '';

        // Insert subscription
        $wpdb->insert(
            $wpdb->prefix . 'pw_subscriptions',
            [
                'property_id' => $property_id,
                'package_type' => sanitize_text_field($_POST['package_type']),
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date']),
                'cost' => sanitize_text_field($_POST['cost']),
                'addons' => $addons
            ]
        );

        // Update property
        $wpdb->update(
            $wpdb->prefix . 'pw_properties',
            [
                'subscription_status' => 'Active Subscription',
                'assigned_engineer'   => intval($_POST['engineer_id'])
            ],
            ['id' => $property_id]
        );

        wp_safe_redirect(home_url('/operation-dashboard?success=1'));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | ENGINEER REPORT SUBMISSION
    |--------------------------------------------------------------------------
    */

    public function handle_engineer_report() {

        if (!isset($_POST['pw_engineer_report'])) return;
        if (!is_user_logged_in()) return;

        if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_engineer_report_nonce')) {
            wp_die('Security failed');
        }

        $user = wp_get_current_user();

        if (!in_array('engineer', $user->roles)) {
            wp_die('Unauthorized');
        }

        global $wpdb;

        $property_id = intval($_POST['property_id']);
        $media_url = '';

        // File upload
        if (!empty($_FILES['media_file']['name'])) {

            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $uploaded = wp_handle_upload($_FILES['media_file'], ['test_form' => false]);

            if (!isset($uploaded['error'])) {
                $media_url = $uploaded['url'];
            }
        }

        // Insert log
        $wpdb->insert(
            $wpdb->prefix . 'pw_property_logs',
            [
                'property_id' => $property_id,
                'engineer_id' => $user->ID,
                'comment'     => sanitize_textarea_field($_POST['comment']),
                'media_url'   => $media_url,
                'created_at'  => current_time('mysql')
            ]
        );

        wp_safe_redirect(home_url('/engineer-dashboard?submitted=1'));
        exit;
    }
}

new PW_Properties();