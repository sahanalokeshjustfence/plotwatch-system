<?php

if (!defined('ABSPATH')) exit;

$error_code = sanitize_key($_REQUEST['error'] ?? '');
$error = '';

if($error_code){

    switch($error_code){

        case 'invalid':
            $error = "Invalid email or password.";
        break;

        case 'not_verified':
            $error = "Please verify your email before logging in.";
        break;

        case 'captcha':
            $error = "Please complete captcha verification.";
        break;

        case 'blocked':
            $error = "Too many login attempts. Try again after 15 minutes.";
        break;

        default:
            $error = "Login failed. Please try again.";
    }

}
?>

<div class="pw-auth-wrapper">

<div class="pw-auth-card">

<img src="<?php echo esc_url(PW_URL.'assets/images/logo.png'); ?>" 
class="pw-auth-logo">

<h2>Welcome Back</h2>

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


<!-- ✅ SUCCESS: REGISTER -->
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
Registration successful. Please verify your email before login.
</div>
<?php endif; ?>


<!-- ✅ SUCCESS: VERIFIED -->
<?php if(isset($_REQUEST['verified'])): ?>
<div style="
background:#e6ffed;
color:#006b2e;
padding:12px;
border-radius:8px;
margin-bottom:15px;
text-align:center;
font-weight:500;
">
Email verified successfully. You can login now.
</div>
<?php endif; ?>


<form method="post">

<input type="hidden" name="pw_login" value="1">

<input type="text"
name="email"
placeholder="Email Address"
required>

<div class="pw-input-group">

<input type="password"
name="password"
id="pw_login_pass"
placeholder="Password"
required>

<span class="pw-eye" onclick="toggleLoginPass()">👁</span>

</div>


<div class="pw-forgot">
<a href="<?php echo esc_url(home_url('/forgot-password')); ?>">
Forgot Password?
</a>
</div>

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

    if(x.type === "password"){
        x.type = "text";
    } else {
        x.type = "password";
    }
}
</script>