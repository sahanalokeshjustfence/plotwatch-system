<?php
if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN → ROLE BASED REDIRECT
|--------------------------------------------------------------------------
*/

if ( is_user_logged_in() ) {

    $current_user = wp_get_current_user();

    if (in_array('customer', (array)$current_user->roles)) {
        wp_safe_redirect(home_url('/customer-dashboard'));
        exit;
    }

    if (in_array('operation_member', (array)$current_user->roles)) {
        wp_safe_redirect(home_url('/operation-dashboard'));
        exit;
    }

    if (in_array('engineer', (array)$current_user->roles)) {
        wp_safe_redirect(home_url('/engineer-dashboard'));
        exit;
    }

    if (in_array('administrator', (array)$current_user->roles)) {
        wp_safe_redirect(admin_url());
        exit;
    }
}
?>

<div class="pw-auth-wrapper">

    <div class="pw-auth-card">

        <img src="<?php echo esc_url(PW_URL . 'assets/images/logo.png'); ?>" 
             class="pw-auth-logo" 
             alt="Logo">

        <h2>Welcome Back</h2>

        <?php
        if ( isset($_GET['login']) && $_GET['login'] == 'failed' ) {
            echo '<div class="pw-error">Invalid email or password.</div>';
        }
        ?>

        <form method="post" action="<?php echo esc_url( wp_login_url() ); ?>">

            <input type="text" 
                   name="log" 
                   placeholder="Email Address" 
                   required>

            <div class="pw-input-group">
                <input type="password" 
                       name="pwd" 
                       id="pw_login_pass" 
                       placeholder="Password" 
                       required>
                <span class="pw-eye" onclick="toggleLoginPass()">👁</span>
            </div>

            <div class="pw-forgot">
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
                    Forgot Password?
                </a>
            </div>

            <!-- Let login_redirect filter handle redirection -->
            <!-- No forced redirect_to here -->

            <button type="submit" class="pw-auth-btn">
                Login
            </button>

        </form>

        <p class="pw-switch">
            No account?
            <a href="<?php echo esc_url(home_url('/register')); ?>">
                Register
            </a>
        </p>

    </div>

</div>

<script>
function toggleLoginPass(){
    var x = document.getElementById("pw_login_pass");
    x.type = (x.type === "password") ? "text" : "password";
}
</script>