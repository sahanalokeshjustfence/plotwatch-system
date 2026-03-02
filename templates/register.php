<?php
if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| REDIRECT IF ALREADY LOGGED IN (ROLE BASED)
|--------------------------------------------------------------------------
*/
if (is_user_logged_in()) {

    $current_user = wp_get_current_user();

    if (in_array('customer', $current_user->roles)) {
        wp_safe_redirect(home_url('/customer-dashboard'));
        exit;
    }

    if (in_array('operation_member', $current_user->roles)) {
        wp_safe_redirect(home_url('/operation-dashboard'));
        exit;
    }

    if (in_array('engineer', $current_user->roles)) {
        wp_safe_redirect(home_url('/engineer-dashboard'));
        exit;
    }

    if (in_array('administrator', $current_user->roles)) {
        wp_safe_redirect(admin_url());
        exit;
    }
}

global $wpdb;
$profile_table = $wpdb->prefix . 'pw_profile';
$errors = [];

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if (isset($_POST['pw_register'])) {

    if (!isset($_POST['_wpnonce']) ||
        !wp_verify_nonce($_POST['_wpnonce'], 'pw_register_nonce')) {
        $errors[] = "Security verification failed.";
    }

    $name     = sanitize_text_field($_POST['name']);
    $email    = sanitize_email($_POST['email']);
    $mobile   = sanitize_text_field($_POST['mobile']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (!is_email($email)) {
        $errors[] = "Invalid email address.";
    }

    if (email_exists($email)) {
        $errors[] = "Email already registered.";
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {

        $user_id = wp_insert_user([
            'user_login'   => $email,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => $name,
            'role'         => 'customer'
        ]);

        if (is_wp_error($user_id)) {

            $errors[] = $user_id->get_error_message();

        } else {

            /*
            |--------------------------------------------------------------------------
            | INSERT INTO PROFILE TABLE
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | EMAIL VERIFICATION (Compatible with class-auth system)
            |--------------------------------------------------------------------------
            */

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
}
?>

<div class="pw-auth-wrapper">

    <div class="pw-auth-card">

        <img src="<?php echo esc_url(PW_URL . 'assets/images/logo.png'); ?>" 
             class="pw-auth-logo" 
             alt="Logo">

        <h2>Create Account</h2>

        <?php if (!empty($errors)) : ?>
            <div class="pw-error">
                <?php foreach ($errors as $error) : ?>
                    <p><?php echo esc_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <?php wp_nonce_field('pw_register_nonce'); ?>
            <input type="hidden" name="pw_register" value="1">

            <input type="text"
                   name="name"
                   placeholder="Full Name"
                   required>

            <input type="email"
                   name="email"
                   placeholder="Email Address"
                   required>

            <input type="text"
                   name="mobile"
                   placeholder="Mobile Number"
                   maxlength="10"
                   required>

            <div class="pw-input-group">
                <input type="password"
                       name="password"
                       id="pw_reg_pass"
                       placeholder="Password"
                       required>
                <span class="pw-eye" onclick="toggleRegPass()">👁</span>
            </div>

            <div class="pw-input-group">
                <input type="password"
                       name="confirm_password"
                       id="pw_reg_confirm"
                       placeholder="Confirm Password"
                       required>
                <span class="pw-eye" onclick="toggleRegConfirm()">👁</span>
            </div>

            <button type="submit" class="pw-auth-btn">
                Create Account
            </button>

        </form>

        <p class="pw-switch">
            Already registered?
            <a href="<?php echo esc_url(home_url('/login')); ?>">
                Login
            </a>
        </p>

    </div>

</div>

<script>
function toggleRegPass(){
    var x = document.getElementById("pw_reg_pass");
    x.type = (x.type === "password") ? "text" : "password";
}

function toggleRegConfirm(){
    var x = document.getElementById("pw_reg_confirm");
    x.type = (x.type === "password") ? "text" : "password";
}
</script>