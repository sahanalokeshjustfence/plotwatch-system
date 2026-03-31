<?php
if (!defined('ABSPATH')) exit;

$error_code = sanitize_key($_REQUEST['error'] ?? '');
$error = '';

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

/*
|--------------------------------------------------------------------------
| ERROR MESSAGE HANDLING
|--------------------------------------------------------------------------
*/

if ($error_code) {

    switch ($error_code) {

        case 'email':
            $error = "This email is already registered.";
        break;

        case 'invalid':
            $error = "Invalid email address.";
        break;

        case 'mobile':
            $error = "Mobile number must contain exactly 10 digits.";
        break;

        case 'password':
            $error = "Passwords do not match.";
        break;

        default:
            $error = "Registration failed. Please try again.";
    }

}
?>

<div class="pw-auth-wrapper">

<div class="pw-auth-card">

<img src="<?php echo esc_url(PW_URL . 'assets/images/logo.png'); ?>" 
class="pw-auth-logo" 
alt="Logo">

<h2>Create Account</h2>

<!-- ✅ ERROR MESSAGE -->
<?php if(!empty($error)): ?>
<div style="
background:#ffe6e6;
color:#b30000;
padding:12px;
border-radius:8px;
margin-bottom:15px;
text-align:center;
font-weight:500;
">
<?php echo esc_html($error); ?>
</div>
<?php endif; ?>

<!-- ✅ SUCCESS MESSAGE (OPTIONAL SAFE ADD) -->
<?php if(isset($_REQUEST['registered'])): ?>
<div style="
background:#e6ffed;
color:#006b2e;
padding:12px;
border-radius:8px;
margin-bottom:15px;
text-align:center;
font-weight:500;
">
Registration successful. Please check your email to verify your account.
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

<button type="submit" class="pw-auth-btn" id="pw-register-btn">
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

    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}

function toggleRegConfirm(){
    var x = document.getElementById("pw_reg_confirm");

    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}

</script>