<?php
if (!defined('ABSPATH')) exit;

$error_code = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$error_code = trim($error_code,"/"); // IMPORTANT FIX
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

<?php if(!empty($error)): ?>
<div class="pw-error"><?php echo esc_html($error); ?></div>
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
<?php
$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : ($_GET['email'] ?? '');
$auth = isset($GLOBALS['pw_auth']) ? $GLOBALS['pw_auth'] : new PW_Auth();

if (!empty($email) && $auth->should_show_captcha($email)) :
?>
    <div class="g-recaptcha" data-sitekey="6Ld2y48sAAAAAAVEeIjTjIy8a26zU8Nzd-7Y9EBQ"></div>
<?php endif; ?>



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
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if($error_code == 'invalid'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {

Swal.fire({
icon:'error',
title:'Login Failed',
text:'Invalid email or password.',
confirmButtonColor:'#e31c3d'
});

});
</script>
<?php endif; ?>

<script>

function toggleLoginPass(){
var x=document.getElementById("pw_login_pass");

if(x.type==="password"){
x.type="text";
}else{
x.type="password";
}

}

</script>