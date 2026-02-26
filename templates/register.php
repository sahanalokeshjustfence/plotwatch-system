<div class="pw-auth-wrapper">

    <div class="pw-auth-card">

        <img src="<?php echo PW_URL; ?>assets/images/logo.png" class="pw-auth-logo">

        <h2>Create Account</h2>

        <form method="post">

            <?php wp_nonce_field('pw_register_nonce'); ?>
            <input type="hidden" name="pw_register" value="1">

            <input type="text" name="name" placeholder="Full Name" required>

            <input type="email" name="email" placeholder="Email Address" required>

            <input type="text" name="mobile" placeholder="Mobile Number" required>

            <div class="pw-password-field">
                <input type="password" name="password" id="pw_reg_pass" placeholder="Password" required>
                <span onclick="toggleRegPass()">👁</span>
            </div>

            <div class="pw-password-field">
                <input type="password" name="confirm_password" id="pw_reg_confirm" placeholder="Confirm Password" required>
                <span onclick="toggleRegConfirm()">👁</span>
            </div>

            <button type="submit" class="pw-auth-btn">Create Account</button>

        </form>

        <p class="pw-switch">
            Already registered? <a href="<?php echo home_url('/login'); ?>">Login</a>
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