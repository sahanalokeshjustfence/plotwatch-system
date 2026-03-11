<?php
if (!defined('ABSPATH')) exit;

$errors = "";
$success = "";

if(isset($_POST['pw_reset'])){

$email = sanitize_email($_POST['email']);

if(empty($email)){
$errors = "Please enter your email address.";
}
else{

$user = get_user_by('email',$email);

if(!$user){
$errors = "No account found with this email.";
}
else{

$reset = retrieve_password($email);

if($reset){
$success = "Password reset link sent to your email.";
}
else{
$errors = "Something went wrong. Please try again.";
}

}

}

}
?>

<div class="pw-auth-wrapper">

<div class="pw-auth-card">

<img src="<?php echo esc_url(PW_URL.'assets/images/logo.png'); ?>" 
class="pw-auth-logo">

<h2>Reset Password</h2>

<?php if($errors): ?>
<div class="pw-error"><?php echo esc_html($errors); ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="pw-success"><?php echo esc_html($success); ?></div>
<?php endif; ?>

<form method="post">

<input type="hidden" name="pw_reset" value="1">

<input type="email"
name="email"
placeholder="Enter your email address"
required>

<button type="submit" class="pw-auth-btn">
Send Reset Link
</button>

</form>

<p class="pw-switch">
<a href="<?php echo esc_url(home_url('/login')); ?>">
Back to Login
</a>
</p>

</div>

</div>