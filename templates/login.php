<?php
if (!defined('ABSPATH')) exit;

$error = isset($_GET['error']) ? "Invalid email or password." : "";
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
var x=document.getElementById("pw_login_pass");

if(x.type==="password"){
x.type="text";
}else{
x.type="password";
}

}

</script>