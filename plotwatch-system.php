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
define('PW_DB_VERSION', '1.5');

/*
|--------------------------------------------------------------------------
| STATUS CONSTANTS
|--------------------------------------------------------------------------
*/

define('PW_STATUS_PENDING', 'Pending Package Assignment');
define('PW_STATUS_VISITS_CREATED', 'Visits Created');
define('PW_STATUS_VISIT_COMPLETED', 'Visit Completed');
define('PW_STATUS_SUBSCRIPTION_COMPLETED', 'Subscription Completed');

/*
|--------------------------------------------------------------------------
| REMOVE ADMIN BAR
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

    /* PROPERTIES */

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
        special_instructions TEXT DEFAULT NULL,
        subscription_status VARCHAR(100) DEFAULT '" . PW_STATUS_PENDING . "',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql1);

    /* SUBSCRIPTIONS */

    $subscriptions = $wpdb->prefix . 'pw_subscriptions';

    $sql2 = "CREATE TABLE $subscriptions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        property_id BIGINT UNSIGNED NOT NULL,
        package_type VARCHAR(100) DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        package_price DECIMAL(10,2) DEFAULT NULL,
        addons TEXT DEFAULT NULL,
        status VARCHAR(100) DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql2);

    /* PROPERTY LOGS */

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

    /* ADDONS */

    $addons = $wpdb->prefix . 'pw_addons';

    $sql4 = "CREATE TABLE $addons (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL,
        description TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql4);

    /* VISITS */

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

    /* PROFILE */

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
| AUTO UPDATE DB
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
| ENQUEUE ASSETS
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style('pw-style', PW_URL . 'assets/css/style.css', [], PW_VERSION);

    wp_enqueue_script('pw-script', PW_URL . 'assets/js/main.js', ['jquery'], PW_VERSION, true);

});

/*
|--------------------------------------------------------------------------
| TEMPLATE LOADER
|--------------------------------------------------------------------------
*/

add_filter('template_include', function ($template) {

    if (is_page(['login', 'register','forgot-password','reset-password'])) {
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

/*
|--------------------------------------------------------------------------
| ROLE BASED LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

/*add_filter('login_redirect', 'pw_role_based_redirect', 10, 3);

function pw_role_based_redirect($redirect_to, $request, $user) {

    if (!isset($user->roles) || !is_array($user->roles)) {
        return home_url();
    }

    $roles = (array) $user->roles;

    if (in_array('customer', $roles)) return home_url('/customer-dashboard');
    if (in_array('operation_member', $roles)) return home_url('/operation-dashboard');
    if (in_array('engineer', $roles)) return home_url('/engineer-dashboard');
    if (in_array('administrator', $roles)) return admin_url();

    return home_url();
}*/

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

add_action('wp_footer', 'pw_global_footer');

function pw_global_footer() {

    if (!is_page([
        'login','register','customer-dashboard','operation-dashboard',
        'engineer-dashboard','assign-package','add-property',
        'customer-profile','manage-addons'
    ])) return;

    echo '<div class="pw-footer-global">'.date('Y').' © Dextra Square Private Limited</div>';
}

/*
|--------------------------------------------------------------------------
| USER MOBILE FIELD
|--------------------------------------------------------------------------
*/

add_action('show_user_profile','pw_user_mobile_field');
add_action('edit_user_profile','pw_user_mobile_field');
add_action('user_new_form','pw_user_mobile_field'); // ADD THIS LINE

function pw_user_mobile_field($user){

global $wpdb;

$table = $wpdb->prefix.'pw_profile';

$profile = $wpdb->get_row(
$wpdb->prepare("SELECT mobile FROM $table WHERE user_id=%d",$user->ID)
);

$mobile = $profile->mobile ?? '';

?>

<h3>PlotWatch Details</h3>

<table class="form-table">
<tr>
<th><label>Mobile Number</label></th>
<td>
<input type="text" name="pw_mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text" required>
<p class="description">Enter WhatsApp number</p>
</td>
</tr>
</table>

<?php
}

add_action('personal_options_update','pw_save_user_mobile');
add_action('edit_user_profile_update','pw_save_user_mobile');
add_action('user_register','pw_save_user_mobile'); // ADD THIS LINE

function pw_save_user_mobile($user_id){

if (!current_user_can('edit_user',$user_id)) return false;

global $wpdb;

$table = $wpdb->prefix.'pw_profile';

$mobile = sanitize_text_field($_POST['pw_mobile'] ?? '');

if(empty($mobile)){
    wp_die('Mobile number is required.');
}

$existing = $wpdb->get_var(
$wpdb->prepare("SELECT id FROM $table WHERE user_id=%d",$user_id)
);

$data = [
'user_id'=>$user_id,
'mobile'=>$mobile,
'updated_at'=>current_time('mysql')
];

if($existing){
$wpdb->update($table,$data,['user_id'=>$user_id]);
}else{
$data['created_at']=current_time('mysql');
$wpdb->insert($table,$data);
}

}

add_filter('auth_cookie_expiration', 'pw_login_session_time', 10, 3);

function pw_login_session_time($expiration, $user_id, $remember){

    $user = get_user_by('id', $user_id);

    if(in_array('customer', $user->roles) || 
       in_array('engineer', $user->roles) || 
       in_array('operation_member', $user->roles)){

        return 60 * 60 * 8; // 8 hours
    }

    return $expiration;
}

add_action('admin_init','pw_block_admin_access');

function pw_block_admin_access(){

    if(!current_user_can('administrator') && !wp_doing_ajax()){
       wp_safe_redirect(home_url('/login'));
        exit;
    }

}


add_filter('retrieve_password_message','pw_custom_reset_email',10,4);

function pw_custom_reset_email($message,$key,$user_login,$user_data){

    $reset_link = network_site_url(
        "wp-login.php?action=rp&key=$key&login=" .
        rawurlencode($user_login),
        'login'
    );

    $message = '
    <html>
    <body style="background:#f4f6f8;padding:30px;font-family:Arial">

    <div style="max-width:520px;margin:auto;background:#ffffff;padding:30px;border-radius:8px;text-align:center">

    <h2 style="color:#1e293b;margin-bottom:10px">PlotWatch</h2>

    <h3>Password Reset Request</h3>

    <p>Hello <b>'.$user_data->display_name.'</b>,</p>

    <p>We received a request to reset your password.</p>

    <p>Click the button below to create a new password.</p>

    <a href="'.$reset_link.'" 
    style="display:inline-block;background:#e31c3d;color:#fff;
    padding:12px 22px;border-radius:6px;text-decoration:none;margin-top:10px">
    Reset Password
    </a>

    <p style="margin-top:20px;font-size:13px;color:#666">
    If you did not request a password reset, please ignore this email.
    </p>

    <p style="font-size:12px;color:#888;margin-top:25px">
    PlotWatch Security Team
    </p>

    </div>

    </body>
    </html>
    ';

    return $message;
}

add_filter('wp_mail_content_type', function(){
    return "text/html";
});

add_action('login_init', function(){

if(isset($_GET['action']) && $_GET['action']=='rp'){

$key = $_GET['key'];
$login = $_GET['login'];

wp_redirect(home_url("/reset-password/?key=$key&login=$login"));
exit;

}

});


function pw_log($msg){
    if(is_array($msg) || is_object($msg)){
        $msg = print_r($msg,true);
    }
    error_log('[PLOTWATCH] '.$msg);
}

add_action('template_redirect','pw_auth_redirect');

function pw_auth_redirect(){

if(!is_user_logged_in()) return;

$user = wp_get_current_user();
$roles = (array) $user->roles;

if(is_page(['login','register','forgot-password','reset-password'])){

    if(in_array('customer',$roles)){
        wp_safe_redirect(home_url('/customer-dashboard'));
        exit;
    }

    if(in_array('operation_member',$roles)){
        wp_safe_redirect(home_url('/operation-dashboard'));
        exit;
    }

    if(in_array('engineer',$roles)){
        wp_safe_redirect(home_url('/engineer-dashboard'));
        exit;
    }

    if(in_array('administrator',$roles)){
        wp_safe_redirect(admin_url());
        exit;
    }

}

}