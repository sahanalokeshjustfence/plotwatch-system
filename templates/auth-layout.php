<?php
if (!defined('ABSPATH')) exit;

// If already logged in → let role redirect handle it
if (is_user_logged_in()) {
    wp_safe_redirect(home_url());
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php wp_head(); ?>
</head>
<body>

<?php
if (is_page('login')) include PW_PATH.'templates/login.php';
if (is_page('register')) include PW_PATH.'templates/register.php';
?>

<?php wp_footer(); ?>
</body>
</html>