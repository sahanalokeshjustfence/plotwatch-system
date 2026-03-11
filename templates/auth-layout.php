<?php
if (!defined('ABSPATH')) exit;
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
elseif (is_page('forgot-password')) {
    include PW_PATH . 'templates/forgot-password.php';
}
elseif (is_page('reset-password')) {
    include PW_PATH . 'templates/reset-password.php';
}
?>

<?php wp_footer(); ?>

</body>
</html>