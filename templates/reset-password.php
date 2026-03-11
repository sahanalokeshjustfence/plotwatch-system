<?php
if (!defined('ABSPATH')) exit;

$error = "";
$success = "";

$key   = $_GET['key'] ?? '';
$login = $_GET['login'] ?? '';

if(empty($key) || empty($login)){
$error = "Invalid reset link.";
}

$user = check_password_reset_key($key,$login);

if(is_wp_error($user)){
$error = "This password reset link is invalid or expired.";
}

if(isset($_POST['pw_new_pass']) && !is_wp_error($user)){

$pass1 = sanitize_text_field($_POST['password']);
$pass2 = sanitize_text_field($_POST['confirm']);

if(empty($pass1) || empty($pass2)){
$error = "All fields are required.";
}

elseif($pass1 !== $pass2){
$error = "Passwords do not match.";
}

elseif(strlen($pass1) < 6){
$error = "Password must be at least 6 characters.";
}

else{

reset_password($user,$pass1);

$success = "Your password has been reset successfully.";

}

}
?>

<div class="pw-auth-wrapper">

<div class="pw-auth-card">

<img src="<?php echo esc_url(PW_URL.'assets/images/logo.png'); ?>" 
class="pw-auth-logo">

<h2>Reset Password</h2>

<?php if($error): ?>
<div class="pw-error"><?php echo esc_html($error); ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="pw-success">
<?php echo esc_html($success); ?>

<br><br>

<a href="<?php echo esc_url(home_url('/login')); ?>" class="pw-auth-btn">
Login Now
</a>

</div>
<?php endif; ?>


<?php if(!$success && !is_wp_error($user)): ?>

<form method="post">

<input type="hidden" name="pw_new_pass" value="1">

<input type="password"
name="password"
placeholder="New Password"
required>

<input type="password"
name="confirm"
placeholder="Confirm Password"
required>

<button type="submit" class="pw-auth-btn">
Save Password
</button>

</form>

<?php endif; ?>

</div>

</div>