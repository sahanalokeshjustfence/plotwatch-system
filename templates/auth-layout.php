<?php
if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| IF USER LOGGED IN → REDIRECT BASED ON ROLE
|--------------------------------------------------------------------------
*/

if (is_user_logged_in()) {

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    if (in_array('customer', $roles)) {
        wp_safe_redirect(home_url('/customer-dashboard'));
        exit;
    }

    if (in_array('operation_member', $roles)) {
        wp_safe_redirect(home_url('/operation-dashboard'));
        exit;
    }

    if (in_array('engineer', $roles)) {
        wp_safe_redirect(home_url('/engineer-dashboard'));
        exit;
    }

    if (in_array('administrator', $roles)) {
        wp_safe_redirect(admin_url());
        exit;
    }

    // Fallback
    wp_safe_redirect(home_url());
    exit;
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body class="pw-auth-page">

<?php
/*
|--------------------------------------------------------------------------
| LOAD AUTH TEMPLATE
|--------------------------------------------------------------------------
*/

if (is_page('login')) {
    include PW_PATH . 'templates/login.php';
}
elseif (is_page('register')) {
    include PW_PATH . 'templates/register.php';
}
?>



<?php wp_footer(); ?>
</body>
</html>