<?php
if (!defined('ABSPATH')) exit;

class PW_Auth {

    public function __construct() {

        add_shortcode('pw_login', [$this, 'login_form']);
        add_shortcode('pw_register', [$this, 'register_form']);

        add_action('init', [$this, 'handle_register']);
        add_action('init', [$this, 'verify_account']);

        add_filter('authenticate', [$this, 'block_unverified'], 30, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE CUSTOMER REGISTER ONLY
    |--------------------------------------------------------------------------
    */

    public function handle_register() {

        if (!isset($_POST['pw_register'])) return;

        if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_register_nonce')) {
            wp_die('Security failed');
        }

        $name  = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $pass  = $_POST['password'];

        if (!is_email($email)) {
            wp_safe_redirect(home_url('/register?error=invalid'));
            exit;
        }

        if (email_exists($email)) {
            wp_safe_redirect(home_url('/register?error=email'));
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | ONLY CREATE CUSTOMER ROLE
        |--------------------------------------------------------------------------
        */

        $user_id = wp_insert_user([
            'user_login'   => $email,
            'user_email'   => $email,
            'user_pass'    => $pass,
            'display_name' => $name,
            'role'         => 'customer'
        ]);

        if (!is_wp_error($user_id)) {

            // Only customers require verification
            update_user_meta($user_id, 'pw_verified', 0);

            $token = wp_generate_password(32, false);
            update_user_meta($user_id, 'pw_verify_token', $token);

            $verify_link = home_url('/?pw_verify=1&uid=' . $user_id . '&token=' . $token);

            $subject = "Verify Your Account - PlotWatch";

            $message = "
Hello $name,

Please verify your account:

$verify_link

Regards,
PlotWatch Team
";

            wp_mail($email, $subject, $message);

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

        $user_id = intval($_GET['uid']);
        $token   = sanitize_text_field($_GET['token']);

        $saved_token = get_user_meta($user_id, 'pw_verify_token', true);

        if ($token === $saved_token && !empty($saved_token)) {

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

        /*
        |--------------------------------------------------------------------------
        | ADMIN CAN LOGIN ALWAYS
        |--------------------------------------------------------------------------
        */

        if (in_array('administrator', (array)$user->roles)) {
            return $user;
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER MUST BE VERIFIED
        |--------------------------------------------------------------------------
        */

        if (in_array('customer', (array)$user->roles)) {

            $verified = get_user_meta($user->ID, 'pw_verified', true);

            if ($verified != 1) {
                return new WP_Error(
                    'not_verified',
                    '<strong>ERROR:</strong> Please verify your email before logging in.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | OPERATION MEMBER & ENGINEER
        | No verification required
        | But must exist in WP admin
        |--------------------------------------------------------------------------
        */

        if (in_array('operation_member', (array)$user->roles) ||
            in_array('engineer', (array)$user->roles)) {

            return $user;
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
    | REGISTER TEMPLATE (Only for customers)
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
}

new PW_Auth();