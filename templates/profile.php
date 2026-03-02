<?php
if (!is_user_logged_in()) return;

global $wpdb;

$user = wp_get_current_user();
$user_id = $user->ID;
$profile_table = $wpdb->prefix . 'pw_profile';

$errors = [];

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

    if (!isset($_POST['_wpnonce']) || 
        !wp_verify_nonce($_POST['_wpnonce'], 'pw_profile_nonce')) {
        $errors[] = "Security verification failed.";
    }

    $name    = sanitize_text_field($_POST['name']);
    $email   = sanitize_email($_POST['email']);
    $mobile  = sanitize_text_field($_POST['mobile']);
    $dob     = sanitize_text_field($_POST['dob']);
    $address = sanitize_textarea_field($_POST['address']);
    $aadhaar = sanitize_text_field($_POST['aadhaar']);
    $pan     = strtoupper(sanitize_text_field($_POST['pan']));
    $password = $_POST['password'];

    /* ============================
       VALIDATIONS
    ============================ */

    // Aadhaar must be 12 digits
    if (!empty($aadhaar) && !preg_match('/^[0-9]{12}$/', $aadhaar)) {
        $errors[] = "Aadhaar must be exactly 12 digits.";
    }

    // PAN validation
    if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
        $errors[] = "Invalid PAN format (Example: ABCDE1234F).";
    }

    if (empty($errors)) {

        // Update WP user basic fields
        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'user_email'   => $email,
        ]);

        /* ============================
           PHOTO UPLOAD
        ============================ */
        $photo_url = $profile->photo ?? '';

        if (!empty($_FILES['photo']['name'])) {

            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $uploaded = wp_handle_upload($_FILES['photo'], ['test_form' => false]);

            if (isset($uploaded['url'])) {
                $photo_url = esc_url_raw($uploaded['url']);
            } else {
                $errors[] = $uploaded['error'];
            }
        }

        /* ============================
           INSERT OR UPDATE PROFILE
        ============================ */
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $profile_table WHERE user_id = %d",
                $user_id
            )
        );

        $data = [
            'user_id' => $user_id,
            'mobile'  => $mobile,
            'dob'     => $dob,
            'address' => $address,
            'aadhaar' => $aadhaar,
            'pan'     => $pan,
            'photo'   => $photo_url,
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
            $wpdb->insert($profile_table, $data);
        }

        /* ============================
           PASSWORD CHANGE
        ============================ */
        if (!empty($password)) {
            wp_set_password($password, $user_id);
            wp_safe_redirect(home_url('/login'));
            exit;
        }

        wp_safe_redirect(home_url('/customer-dashboard'));
        exit;
    }
}
?>

<div class="pw-profile-card">

<h2 class="pw-page-title">My Profile</h2>

<?php if (!empty($errors)) : ?>
    <div class="pw-error">
        <?php foreach ($errors as $error) : ?>
            <p><?php echo esc_html($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="pw-profile-form">

<?php wp_nonce_field('pw_profile_nonce'); ?>
<input type="hidden" name="pw_update_profile" value="1">

<div class="pw-grid-3">

    <!-- Full Name -->
    <div>
        <label>Full Name</label>
        <input type="text" name="name"
        value="<?php echo esc_attr($user->display_name); ?>" required>
    </div>

    <!-- Email -->
    <div>
        <label>Email Address</label>
        <input type="email" name="email"
        value="<?php echo esc_attr($user->user_email); ?>" required>
    </div>

    <!-- Mobile -->
    <div>
        <label>Mobile Number</label>
        <input type="text" name="mobile"
        value="<?php echo esc_attr($profile->mobile ?? ''); ?>" required>
    </div>

    <!-- Date of Birth -->
    <div>
        <label>Date of Birth</label>
        <input type="date" name="dob"
        value="<?php echo esc_attr($profile->dob ?? ''); ?>">
    </div>

    <!-- Aadhaar -->
    <div>
        <label>Aadhaar Number</label>
        <input type="text" name="aadhaar"
        value="<?php echo esc_attr($profile->aadhaar ?? ''); ?>"
        maxlength="12">
    </div>

    <!-- PAN -->
    <div>
        <label>PAN Number</label>
        <input type="text" name="pan"
        value="<?php echo esc_attr($profile->pan ?? ''); ?>"
        maxlength="10">
    </div>

    <!-- Address -->
    <div class="full">
        <label>Address</label>
        <textarea name="address"><?php echo esc_textarea($profile->address ?? ''); ?></textarea>
    </div>

    <!-- Profile Photo -->
    <div>
        <label>Profile Photo</label>
        <input type="file" name="photo" accept="image/*">
    </div>

    <?php if (!empty($profile->photo)) : ?>
        <div>
            <label>Current Photo</label><br>
            <img src="<?php echo esc_url($profile->photo); ?>" width="100" style="border-radius:10px;">
        </div>
    <?php endif; ?>

    <!-- Change Password -->
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