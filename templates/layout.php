<?php
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login'));
    exit;
}

global $post;
if ($post) {
    $GLOBALS['wp_query']->is_404 = false;
}

$user  = wp_get_current_user();
$roles = (array) $user->roles;
$role  = !empty($roles) ? $roles[0] : '';

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div class="pw-app">

<!-- SIDEBAR -->
<aside class="pw-sidebar">

<div class="pw-logo">
<img src="<?php echo esc_url(PW_URL . 'assets/images/logo.png'); ?>" alt="PlotWatch Logo">
</div>

<div class="pw-sidebar-toggle" onclick="toggleSidebar()">

<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
<path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

</div>

<nav>

<?php if ($role === 'customer'): ?>

<a href="<?php echo esc_url(home_url('/customer-dashboard')); ?>"
class="<?php echo (is_page('customer-dashboard') && empty($tab)) ? 'active' : ''; ?>">

<span class="pw-icon">🏠</span>
<span class="pw-text">Dashboard</span>

</a>

<a href="<?php echo esc_url(home_url('/add-property')); ?>"
class="<?php echo is_page('add-property') ? 'active' : ''; ?>">

<span class="pw-icon">➕</span>
<span class="pw-text">Add Property</span>

</a>

<a href="<?php echo esc_url(home_url('/customer-dashboard?tab=my-properties')); ?>"
class="<?php echo ($tab === 'my-properties') ? 'active' : ''; ?>">

<span class="pw-icon">📂</span>
<span class="pw-text">My Properties</span>

</a>

<a href="<?php echo esc_url(home_url('/customer-profile')); ?>"
class="<?php echo is_page('customer-profile') ? 'active' : ''; ?>">

<span class="pw-icon">👤</span>
<span class="pw-text">Profile</span>

</a>

<?php elseif ($role === 'operation_member'): ?>

<a href="<?php echo esc_url(home_url('/operation-dashboard')); ?>"
class="<?php echo (is_page('operation-dashboard') && empty($tab)) ? 'active' : ''; ?>">

<span class="pw-icon">🏠</span>
<span class="pw-text">Dashboard</span>

</a>

<a href="<?php echo esc_url(home_url('/operation-dashboard?tab=new')); ?>"
class="<?php echo ($tab === 'new') ? 'active' : ''; ?>">

<span class="pw-icon">📋</span>
<span class="pw-text">New Properties</span>

</a>

<a href="<?php echo esc_url(home_url('/manage-addons')); ?>"
class="<?php echo is_page('manage-addons') ? 'active' : ''; ?>">

<span class="pw-icon">⚙️</span>
<span class="pw-text">Manage Add-ons</span>

</a>

<?php elseif ($role === 'engineer'): ?>

<a href="<?php echo esc_url(home_url('/engineer-dashboard')); ?>"
class="<?php echo (is_page('engineer-dashboard') && empty($tab)) ? 'active' : ''; ?>">

<span class="pw-icon">🏠</span>
<span class="pw-text">Dashboard</span>

</a>

<a href="<?php echo esc_url(home_url('/engineer-dashboard?tab=visits')); ?>"
class="<?php echo ($tab === 'visits') ? 'active' : ''; ?>">

<span class="pw-icon">📍</span>
<span class="pw-text">Property Visits</span>

</a>

<?php endif; ?>

</nav>

</aside>


<!-- MAIN -->
<main class="pw-main">

<header class="pw-header">

<div class="pw-mobile-menu" onclick="toggleSidebar()">☰</div>

<div>
Welcome, <?php echo esc_html($user->display_name); ?>
</div>

<a class="pw-logout"
href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">
Logout
</a>

</header>


<section class="pw-content">

<?php

global $post;

$slug = '';
if ($post && isset($post->post_name)) {
    $slug = $post->post_name;
}

if (!$slug) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $parts = explode('/', $uri);
    $slug = end($parts);
}

if (is_page('customer-dashboard') || $slug === 'customer-dashboard') {

if(empty($tab)){
include PW_PATH . 'templates/customer-dashboard-home.php';
}else{
include PW_PATH . 'templates/dashboard.php';
}

}

if (is_page('add-property') || $slug === 'add-property') {
include PW_PATH . 'templates/add-property.php';
}

if (is_page('customer-profile') || $slug === 'customer-profile') {
include PW_PATH . 'templates/profile.php';
}

if (is_page('operation-dashboard') || $slug === 'operation-dashboard') {

if(empty($tab)){
include PW_PATH . 'templates/operation-dashboard-home.php';
}
elseif($tab === 'new'){
include PW_PATH . 'templates/operation-dashboard.php';
}

}

if (is_page('engineer-dashboard') || $slug === 'engineer-dashboard') {

if(empty($tab)){
include PW_PATH . 'templates/engineer-dashboard-home.php';
}
elseif($tab === 'visits'){
include PW_PATH . 'templates/engineer-dashboard.php';
}

}

if (is_page('assign-package') || $slug === 'assign-package') {
include PW_PATH . 'templates/assign-package.php';
}

if (is_page('update-visit') || $slug === 'update-visit') {
include PW_PATH . 'templates/update-visit.php';
}

if (is_page('visit-details') || $slug === 'visit-details') {
include PW_PATH . 'templates/visit-details.php';
}

if (is_page('visit-reports') || $slug === 'visit-reports') {
include PW_PATH . 'templates/visit-reports.php';
}

if (is_page('manage-addons') || $slug === 'manage-addons') {
include PW_PATH . 'templates/manage-addons.php';
}

?>

</section>

</main>

</div>

<?php wp_footer(); ?>

<script>

function toggleSidebar(){

let sidebar=document.querySelector(".pw-sidebar");
let main=document.querySelector(".pw-main");

if(window.innerWidth < 768){

sidebar.classList.toggle("open");

}else{

sidebar.classList.toggle("collapsed");
main.classList.toggle("expanded");

}

}

window.addEventListener("resize", function(){

let sidebar=document.querySelector(".pw-sidebar");

if(window.innerWidth > 768){

sidebar.classList.remove("open");

}

});

</script>


<div id="pw-loader">
<div class="pw-spinner"></div>
</div>

</body>
</html>