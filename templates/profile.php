<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();

if (isset($_POST['pw_update_profile'])) {

    wp_update_user([
        'ID' => $user->ID,
        'display_name' => sanitize_text_field($_POST['name']),
        'user_email' => sanitize_email($_POST['email'])
    ]);

    echo "<p>Profile updated successfully.</p>";
}
?>

<h2>My Profile</h2>

<form method="post" class="pw-form">

<input type="hidden" name="pw_update_profile" value="1">

<input type="text" name="name"
value="<?php echo esc_attr($user->display_name); ?>"
class="full" required>

<input type="email" name="email"
value="<?php echo esc_attr($user->user_email); ?>"
class="full" required>

<button type="submit" class="pw-btn">
Update Profile
</button>

</form>