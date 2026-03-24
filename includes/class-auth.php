<?php
if (!defined('ABSPATH')) exit;

class PW_Auth {

    public function __construct() {

        add_shortcode('pw_login', [$this, 'login_form']);
        add_shortcode('pw_register', [$this, 'register_form']);

        add_action('init', [$this, 'handle_login']);
        add_action('init', [$this, 'handle_register']);
        add_action('init', [$this, 'verify_account']);

        add_filter('authenticate', [$this, 'block_unverified'], 30, 3);
    }
    
    // 🤖 CAPTCHA TRIGGER LOGIC
    public function should_show_captcha($email) {
        $ip = $_SERVER['REMOTE_ADDR'];

        $email_key = 'pw_login_email_' . md5($email);
        $ip_key    = 'pw_login_ip_' . md5($ip);

        $email_attempts = get_transient($email_key) ?: 0;
        $ip_attempts    = get_transient($ip_key) ?: 0;

        return ($email_attempts >= 3 || $ip_attempts >= 5);
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE LOGIN
    |--------------------------------------------------------------------------
    */

    public function handle_login() {

        if (!isset($_POST['pw_login'])) return;

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // ✅ STEP 1: CHECK LOGIN ATTEMPTS
        $this->check_login_attempts($email);

        // 🤖 CAPTCHA VALIDATION
        if ($this->should_show_captcha($email)) {

            $recaptcha = $_POST['g-recaptcha-response'] ?? '';

            if (empty($recaptcha)) {
                wp_safe_redirect(add_query_arg('error','captcha',home_url('/login')));
                exit;
            }

            $response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", [
                'body' => [
                    'secret' => PW_RECAPTCHA_SECRET,
                    'response' => $recaptcha
                ]
            ]);

            $result = json_decode(wp_remote_retrieve_body($response));

            if (!$result->success) {
                wp_safe_redirect(add_query_arg('error','captcha',home_url('/login')));
                exit;
            }
        }

        $creds = [
            'user_login'    => $email,
            'user_password' => $password,
            'remember'      => true
        ];

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {

            // ❌ STEP 2: RECORD FAILED ATTEMPT
            $this->record_failed_attempt($email);

            pw_log("Login failed for email: ".$email,"LOGIN");

            wp_safe_redirect(add_query_arg('error','invalid',home_url('/login')));
            exit;
        }

        // ✅ STEP 3: CLEAR FAILED ATTEMPTS
        $this->clear_login_attempts($email);

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        pw_log("User login success: ".$user->user_email." | Role: ".implode(',',$user->roles),"LOGIN");

        /*
        |--------------------------------------------------------------------------
        | ROLE BASED REDIRECT
        |--------------------------------------------------------------------------
        */

        if (in_array('customer', (array) $user->roles)) {
            wp_safe_redirect(home_url('/customer-dashboard'));
            exit;
        }

        if (in_array('operation_member', (array) $user->roles)) {
            wp_safe_redirect(home_url('/operation-dashboard'));
            exit;
        }

        if (in_array('engineer', (array) $user->roles)) {
            wp_safe_redirect(home_url('/engineer-dashboard'));
            exit;
        }

        if (in_array('administrator', (array) $user->roles)) {
            wp_safe_redirect(admin_url());
            exit;
        }

        wp_safe_redirect(home_url());
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE CUSTOMER REGISTER ONLY
    |--------------------------------------------------------------------------
    */

    public function handle_register() {

        if (!isset($_POST['pw_register'])) return;

        if (!isset($_POST['_wpnonce']) ||
            !wp_verify_nonce($_POST['_wpnonce'], 'pw_register_nonce')) {
            wp_die('Security failed');
        }

        global $wpdb;
        $profile_table = $wpdb->prefix . 'pw_profile';

        $name   = sanitize_text_field($_POST['name'] ?? '');
        $email  = sanitize_email($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $mobile = sanitize_text_field($_POST['mobile'] ?? '');

        if($pass !== $confirm){
            wp_safe_redirect(add_query_arg('error','password',home_url('/register')));
            exit;
        }

        if (!is_email($email)) {
            wp_safe_redirect(add_query_arg('error','invalid',home_url('/register')));
            exit;
        }

        if (email_exists($email)) {
            pw_log("Registration failed: email already exists ".$email,"REGISTER");
            wp_safe_redirect(add_query_arg('error','email',home_url('/register')));
            exit;
        }

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            wp_safe_redirect(add_query_arg('error','mobile',home_url('/register')));
            exit;
        }

        $user_id = wp_insert_user([
            'user_login'   => $email,
            'user_email'   => $email,
            'user_pass'    => $pass,
            'display_name' => $name,
            'role'         => 'customer'
        ]);

        if (!is_wp_error($user_id)) {

            pw_log("New user registered: ".$email." | UserID: ".$user_id,"REGISTER");

            $wpdb->insert(
                $profile_table,
                [
                    'user_id'    => $user_id,
                    'mobile'     => $mobile,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d','%s','%s','%s']
            );

            update_user_meta($user_id, 'pw_verified', 0);

            $token = wp_generate_password(32, false);
            update_user_meta($user_id, 'pw_verify_token', $token);

            $verify_link = home_url('/?pw_verify=1&uid=' . $user_id . '&token=' . $token);

         $subject = "Verify Your Account - PlotWatch";

$message = '
<html>
<head>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    padding: 20px;
}
.container {
    max-width: 500px;
    margin: auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
}
h2 {
    color: #dc2626;
}
p {
    color: #555;
    font-size: 14px;
}
.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
    background: #dc2626;
    color: #ffffff !important;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
}
.footer {
    margin-top: 20px;
    font-size: 12px;
    color: #999;
}
</style>
</head>

<body>
<div class="container">
    <h2>Verify Your Email</h2>

    <p>Welcome to <strong>PlotWatch</strong> 👋</p>

    <p>Please click the button below to verify your account.</p>

    <a href="'.$verify_link.'" class="btn">Verify Email</a>

    <p>If button is not working, copy below link:</p>

    <p style="word-break:break-all;">
        '.$verify_link.'
    </p>

    <div class="footer">
        © PlotWatch by JustFence
    </div>
</div>
</body>
</html>
';

$headers = [
    'Content-Type: text/html; charset=UTF-8'
];

wp_mail($email, $subject, $message, $headers);

            wp_safe_redirect(home_url('/login?registered=1'));
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY CUSTOMER ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function verify_account() {

        if (!isset($_GET['pw_verify'])) return;

        $user_id = intval($_GET['uid'] ?? 0);
        $token   = sanitize_text_field($_GET['token'] ?? '');

        $saved_token = get_user_meta($user_id, 'pw_verify_token', true);

        if ($token === $saved_token && !empty($saved_token)) {
            pw_log("Account verified for userID: ".$user_id,"VERIFY");
            update_user_meta($user_id, 'pw_verified', 1);
            delete_user_meta($user_id, 'pw_verify_token');

            wp_safe_redirect(home_url('/login?verified=1'));
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN CONTROL
    |--------------------------------------------------------------------------
    */

    public function block_unverified($user, $username, $password) {

        if (is_wp_error($user)) return $user;
        if (!$user) return $user;

        if (in_array('administrator', (array)$user->roles)) {
            return $user;
        }

        if (in_array('customer', (array)$user->roles)) {

            $verified = get_user_meta($user->ID, 'pw_verified', true);

            if ($verified != 1) {
                return new WP_Error(
                    'not_verified',
                    '<strong>ERROR:</strong> Please verify your email before logging in.'
                );
            }
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function login_form() {
        ob_start();
        include PW_PATH . 'templates/login.php';
        return ob_get_clean();
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTER TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function register_form() {

        if (is_user_logged_in()) {
            return "<p>You are already logged in.</p>";
        }

        ob_start();
        include PW_PATH . 'templates/register.php';
        return ob_get_clean();
    }

    /* =====================================================
       🔥 ADDED FUNCTIONS (FIX FOR ERROR)
    ===================================================== */

    private function check_login_attempts($email) {

        $ip = $_SERVER['REMOTE_ADDR'];

        $email_key = 'pw_login_email_' . md5($email);
        $ip_key    = 'pw_login_ip_' . md5($ip);

        $email_attempts = get_transient($email_key) ?: 0;
        $ip_attempts    = get_transient($ip_key) ?: 0;

        if ($email_attempts >= 5 || $ip_attempts >= 10) {

            wp_safe_redirect(add_query_arg('error','blocked',home_url('/login')));
            exit;
        }
    }

    private function record_failed_attempt($email) {

        $ip = $_SERVER['REMOTE_ADDR'];

        $email_key = 'pw_login_email_' . md5($email);
        $ip_key    = 'pw_login_ip_' . md5($ip);

        $email_attempts = get_transient($email_key) ?: 0;
        $ip_attempts    = get_transient($ip_key) ?: 0;

        set_transient($email_key, $email_attempts + 1, 900);
        set_transient($ip_key, $ip_attempts + 1, 900);
    }

    private function clear_login_attempts($email) {

        $ip = $_SERVER['REMOTE_ADDR'];

        delete_transient('pw_login_email_' . md5($email));
        delete_transient('pw_login_ip_' . md5($ip));
    }

}

new PW_Auth();