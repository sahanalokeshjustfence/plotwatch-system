<?php
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login'));
    exit;
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

        <nav>

        <?php if ($role === 'customer'): ?>

            <a href="<?php echo esc_url(home_url('/customer-dashboard')); ?>"
               class="<?php echo (is_page('customer-dashboard') && empty($tab)) ? 'active' : ''; ?>">
               Dashboard
            </a>

            <a href="<?php echo esc_url(home_url('/add-property')); ?>"
               class="<?php echo is_page('add-property') ? 'active' : ''; ?>">
               Add Property
            </a>

            <a href="<?php echo esc_url(home_url('/customer-dashboard?tab=my-properties')); ?>"
               class="<?php echo ($tab === 'my-properties') ? 'active' : ''; ?>">
               My Properties
            </a>

            <a href="<?php echo esc_url(home_url('/customer-profile')); ?>"
               class="<?php echo is_page('customer-profile') ? 'active' : ''; ?>">
               Profile
            </a>

        <?php elseif ($role === 'operation_member'): ?>

            <a href="<?php echo esc_url(home_url('/operation-dashboard')); ?>"
               class="<?php echo (is_page('operation-dashboard') && empty($tab)) ? 'active' : ''; ?>">
               Dashboard
            </a>

            <a href="<?php echo esc_url(home_url('/operation-dashboard?tab=new')); ?>"
               class="<?php echo ($tab === 'new') ? 'active' : ''; ?>">
               New Properties
            </a>

            <!-- Assigned & Completed REMOVED -->

            <a href="<?php echo esc_url(home_url('/manage-addons')); ?>"
               class="<?php echo is_page('manage-addons') ? 'active' : ''; ?>">
               Manage Add-ons
            </a>

        <?php elseif ($role === 'engineer'): ?>

            <a href="<?php echo esc_url(home_url('/engineer-dashboard')); ?>"
               class="<?php echo (is_page('engineer-dashboard') && empty($tab)) ? 'active' : ''; ?>">
               Dashboard
            </a>

            <a href="<?php echo esc_url(home_url('/engineer-dashboard')); ?>">
               Assigned Properties
            </a>

            <a href="<?php echo esc_url(home_url('/engineer-dashboard?tab=completed')); ?>"
               class="<?php echo ($tab === 'completed') ? 'active' : ''; ?>">
               Completed Visits
            </a>

        <?php endif; ?>

        </nav>

    </aside>

    <!-- MAIN -->
    <main class="pw-main">

        <header class="pw-header">
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
        /* =========================
           PAGE LOADER
        ========================== */

        if (is_page('customer-dashboard')) {
            include PW_PATH . 'templates/dashboard.php';
        }

        if (is_page('add-property')) {
            include PW_PATH . 'templates/add-property.php';
        }

        if (is_page('customer-profile')) {
            include PW_PATH . 'templates/profile.php';
        }

        if (is_page('operation-dashboard')) {
            include PW_PATH . 'templates/operation-dashboard.php';
        }

        if (is_page('engineer-dashboard')) {
            include PW_PATH . 'templates/engineer-dashboard.php';
        }

        if (is_page('assign-package')) {
            include PW_PATH . 'templates/assign-package.php';
        }

        if (is_page('update-visit')) {
            include PW_PATH . 'templates/update-visit.php';
        }

        if (is_page('manage-addons')) {
            include PW_PATH . 'templates/manage-addons.php';
        }
        ?>

        </section>

    </main>

</div>

<?php wp_footer(); ?>
</body>
</html>