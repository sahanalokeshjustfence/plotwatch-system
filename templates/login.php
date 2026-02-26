<div class="pw-auth-wrapper">

    <div class="pw-auth-card">

        <img src="<?php echo PW_URL; ?>assets/images/logo.png" class="pw-auth-logo">

        <h2>Welcome Back</h2>

        <form method="post" action="<?php echo wp_login_url(); ?>">

            <input type="text" name="log" placeholder="Email Address" required>

            <div class="pw-input-group">
                <input type="password" name="pwd" id="pw_login_pass" placeholder="Password" required>
                <span class="pw-eye" onclick="toggleLoginPass()">👁</span>
            </div>

            <div class="pw-forgot">
                <a href="<?php echo wp_lostpassword_url(); ?>">Forgot Password?</a>
            </div>

            <button type="submit" class="pw-auth-btn">Login</button>

        </form>

        <p class="pw-switch">
            No account? <a href="<?php echo home_url('/register'); ?>">Register</a>
        </p>

    </div>

</div>

<script>
function toggleLoginPass(){
    var x = document.getElementById("pw_login_pass");
    x.type = (x.type === "password") ? "text" : "password";
}
</script>