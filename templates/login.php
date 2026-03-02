<?php
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard') ); 
    exit;
}
?>

<div class="pw-auth-wrapper">

    <div class="pw-auth-card">

        <img src="<?php echo PW_URL; ?>assets/images/logo.png" class="pw-auth-logo" alt="Logo">

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

            <input type="hidden" 
                   name="redirect_to" 
                   value="<?php echo home_url('/dashboard'); ?>">

            <button type="submit" class="pw-auth-btn">
                Login
            </button>

        </form>

        <p class="pw-switch">
            No account?
            <a href="<?php echo home_url('/register'); ?>">
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