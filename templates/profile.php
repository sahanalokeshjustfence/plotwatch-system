<?php
if (!is_user_logged_in()) return;

global $wpdb;

$user = wp_get_current_user();
$user_id = $user->ID;
$profile_table = $wpdb->prefix . 'pw_profile';

$errors = [];
$success = false;

/* =========================================
FETCH PROFILE DATA
========================================= */

$profile = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $profile_table WHERE user_id = %d",
        $user_id
    )
);

/* =========================================
UPDATE PROFILE
========================================= */

if (isset($_POST['pw_update_profile'])) {

    /* ===== NONCE CHECK ===== */

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'pw_profile_nonce')) {
        $errors[] = "Security verification failed.";
    }

    /* ===== GET FORM VALUES ===== */

    $name     = sanitize_text_field($_POST['name'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $mobile   = sanitize_text_field($_POST['mobile'] ?? '');
    $pan      = strtoupper(sanitize_text_field($_POST['pan'] ?? ''));
    $password = $_POST['password'] ?? '';

    /* ================= VALIDATIONS ================= */

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
        $errors[] = "Invalid PAN format (Example: ABCDE1234F).";
    }

    /* ================= UPDATE ================= */

    if (empty($errors)) {

        /* ===== UPDATE WORDPRESS USER ===== */

        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'user_email'   => $email
        ]);

        /* ===== CHECK IF PROFILE EXISTS ===== */

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $profile_table WHERE user_id=%d",
                $user_id
            )
        );

        $data = [
            'user_id'    => $user_id,
            'mobile'     => $mobile,
            'pan'        => $pan,
            'updated_at' => current_time('mysql')
        ];

        if ($existing) {

            $wpdb->update(
                $profile_table,
                $data,
                ['user_id' => $user_id]
            );

        } else {

            $data['created_at'] = current_time('mysql');

            $wpdb->insert(
                $profile_table,
                $data
            );

        }

        /* ===== PASSWORD CHANGE ===== */

        if (!empty(trim($password))) {

            wp_set_password($password, $user_id);
            wp_set_auth_cookie($user_id);

        }

        /* ===== SUCCESS MESSAGE ===== */

        $success = true;

        /* refresh user object */

        $user = wp_get_current_user();
    }
}
?>

<div class="pw-profile-card">

<h2 class="pw-page-title">My Profile</h2>

<?php if ($success): ?>
<div class="pw-success">
Profile updated successfully
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="pw-error">
<?php foreach ($errors as $error): ?>
<p><?php echo esc_html($error); ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="" class="pw-profile-form">

<?php wp_nonce_field('pw_profile_nonce'); ?>
<input type="hidden" name="pw_update_profile" value="1">

<div class="pw-grid-3">

<div>
<label>Full Name</label>
<input type="text" name="name"
value="<?php echo esc_attr($user->display_name); ?>" required>
</div>

<div>
<label>Email Address</label>
<input type="email" name="email"
value="<?php echo esc_attr($user->user_email); ?>" required>
</div>

<div>
<label>Mobile Number</label>
<input type="text" name="mobile"
value="<?php echo esc_attr($profile->mobile ?? ''); ?>" required>
</div>

<div>
<label>PAN Number</label>
<input type="text" name="pan"
value="<?php echo esc_attr($profile->pan ?? ''); ?>"
maxlength="10"
style="text-transform:uppercase">
</div>

<div class="full">
<label>Change Password (Optional)</label>
<input type="password" name="password"
placeholder="Leave blank if not changing">
</div>

</div>

<button type="submit" class="pw-btn">
Update Profile
</button>

</form>

</div>

<script>
setTimeout(function(){
const msg=document.querySelector('.pw-success');
if(msg){
msg.style.display='none';
}
},3000);
</script>