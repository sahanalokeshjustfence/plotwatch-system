<?php
/*
Plugin Name: PlotWatch System
Description: Professional Property & Customer Management System
Version: 2.2
Author: PlotWatch
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

define('PW_PATH', plugin_dir_path(__FILE__));
define('PW_URL', plugin_dir_url(__FILE__));
define('PW_VERSION', '2.2');
define('PW_DB_VERSION', '1.3');

/*
|--------------------------------------------------------------------------
| STATUS CONSTANTS
|--------------------------------------------------------------------------
*/

define('PW_STATUS_PENDING', 'Pending Package Assignment');
define('PW_STATUS_PACKAGE_ASSIGNED', 'Package Assigned');
define('PW_STATUS_VISITS_CREATED', 'Visits Created');
define('PW_STATUS_VISIT_SCHEDULED', 'Visit Scheduled');
define('PW_STATUS_VISIT_COMPLETED', 'Visit Completed');
define('PW_STATUS_SUBSCRIPTION_COMPLETED', 'Subscription Completed');

/*
|--------------------------------------------------------------------------
| REMOVE ADMIN BAR (NON-ADMIN USERS)
|--------------------------------------------------------------------------
*/

add_action('after_setup_theme', function () {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
});

/*
|--------------------------------------------------------------------------
| ACTIVATE PLUGIN
|--------------------------------------------------------------------------
*/

register_activation_hook(__FILE__, 'pw_activate_plugin');

function pw_activate_plugin() {
    pw_create_tables();
    flush_rewrite_rules();
}

/*
|--------------------------------------------------------------------------
| CREATE / UPDATE DATABASE TABLES
|--------------------------------------------------------------------------
*/

function pw_create_tables() {

    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    /* ==========================
       PROPERTIES TABLE
    ========================== */

    $properties = $wpdb->prefix . 'pw_properties';

    $sql1 = "CREATE TABLE $properties (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        property_code VARCHAR(20) DEFAULT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        assigned_engineer BIGINT UNSIGNED DEFAULT NULL,
        property_name VARCHAR(255) NOT NULL,
        location_name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        google_map VARCHAR(255) DEFAULT NULL,
        plot_size VARCHAR(100) DEFAULT NULL,
        property_type VARCHAR(100) DEFAULT NULL,
        contact_person VARCHAR(255) DEFAULT NULL,
        contact_number VARCHAR(20) DEFAULT NULL,
        subscription_status VARCHAR(100) DEFAULT '" . PW_STATUS_PENDING . "',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql1);

    /* ==========================
       SUBSCRIPTIONS TABLE
    ========================== */

    $subscriptions = $wpdb->prefix . 'pw_subscriptions';

    $sql2 = "CREATE TABLE $subscriptions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        property_id BIGINT UNSIGNED NOT NULL,
        package_type VARCHAR(100) DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        cost DECIMAL(10,2) DEFAULT 0,
        addons TEXT DEFAULT NULL,
        status VARCHAR(100) DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql2);

    /* ==========================
       PROPERTY LOGS TABLE
    ========================== */

    $logs = $wpdb->prefix . 'pw_property_logs';

    $sql3 = "CREATE TABLE $logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        property_id BIGINT UNSIGNED NOT NULL,
        engineer_id BIGINT UNSIGNED NOT NULL,
        visit_date DATE DEFAULT NULL,
        comment TEXT DEFAULT NULL,
        media_url TEXT DEFAULT NULL,
        visit_status VARCHAR(100) DEFAULT '" . PW_STATUS_VISIT_COMPLETED . "',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql3);

    /* ==========================
       ADDONS TABLE
    ========================== */

    $addons = $wpdb->prefix . 'pw_addons';

    $sql4 = "CREATE TABLE $addons (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        description TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql4);

    /* ==========================
       VISITS TABLE
    ========================== */

    $visits = $wpdb->prefix . 'pw_visits';

    $sql5 = "CREATE TABLE $visits (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        property_id BIGINT UNSIGNED NOT NULL,
        subscription_id BIGINT UNSIGNED DEFAULT NULL,
        engineer_id BIGINT UNSIGNED DEFAULT NULL,
        visit_date DATE NOT NULL,
        visit_status VARCHAR(100) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql5);

    /* ==========================
       PROFILE TABLE
    ========================== */

    $profile = $wpdb->prefix . 'pw_profile';

    $sql6 = "CREATE TABLE $profile (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        mobile VARCHAR(20) DEFAULT NULL,
        dob DATE DEFAULT NULL,
        address TEXT DEFAULT NULL,
        aadhaar VARCHAR(20) DEFAULT NULL,
        pan VARCHAR(20) DEFAULT NULL,
        photo VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    dbDelta($sql6);

    update_option('pw_db_version', PW_DB_VERSION);
}

/*
|--------------------------------------------------------------------------
| AUTO UPDATE DB IF VERSION CHANGES
|--------------------------------------------------------------------------
*/

add_action('plugins_loaded', function () {
    if (get_option('pw_db_version') !== PW_DB_VERSION) {
        pw_create_tables();
    }
});

/*
|--------------------------------------------------------------------------
| LOAD CORE CLASSES
|--------------------------------------------------------------------------
*/

require_once PW_PATH . 'includes/class-roles.php';
require_once PW_PATH . 'includes/class-auth.php';
require_once PW_PATH . 'includes/class-properties.php';
require_once PW_PATH . 'includes/class-dashboard.php';
require_once PW_PATH . 'includes/class-redirects.php';

/*
|--------------------------------------------------------------------------
| ENQUEUE FRONTEND ASSETS
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'pw-style',
        PW_URL . 'assets/css/style.css',
        [],
        PW_VERSION
    );

    wp_enqueue_script(
        'pw-script',
        PW_URL . 'assets/js/main.js',
        ['jquery'],
        PW_VERSION,
        true
    );
});

/*
|--------------------------------------------------------------------------
| TEMPLATE LOADER
|--------------------------------------------------------------------------
*/

add_filter('template_include', function ($template) {

    if (is_page(['login', 'register'])) {
        return PW_PATH . 'templates/auth-layout.php';
    }

    if (is_page([
        'customer-dashboard',
        'operation-dashboard',
        'engineer-dashboard',
        'assign-package',
        'add-property',
        'customer-profile',
        'manage-addons'
    ])) {
        return PW_PATH . 'templates/layout.php';
    }

    return $template;

}, 99);