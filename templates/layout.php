<?php
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login'));
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;
$role  = !empty($roles) ? $roles[0] : '';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php wp_head(); ?>
</head>
<body>

<div class="pw-app">

    <!-- SIDEBAR -->
    <aside class="pw-sidebar">

        <div class="pw-logo">
            <img src="<?php echo esc_url(PW_URL . 'assets/images/logo.png'); ?>" alt="PlotWatch Logo">
        </div>

        <nav>

        <?php if ($role === 'customer'): ?>

            <a href="<?php echo home_url('/customer-dashboard'); ?>"
               class="<?php if(is_page('customer-dashboard') && !isset($_GET['tab'])) echo 'active'; ?>">
               Dashboard
            </a>

            <a href="<?php echo home_url('/add-property'); ?>"
               class="<?php if(is_page('add-property')) echo 'active'; ?>">
               Add Property
            </a>

            <a href="<?php echo home_url('/customer-dashboard?tab=my-properties'); ?>"
               class="<?php if(isset($_GET['tab']) && $_GET['tab'] === 'my-properties') echo 'active'; ?>">
               My Properties
            </a>

            <a href="<?php echo home_url('/customer-profile'); ?>"
               class="<?php if(is_page('customer-profile')) echo 'active'; ?>">
               Profile
            </a>

        <?php elseif ($role === 'operation_member'): ?>

            <a href="<?php echo home_url('/operation-dashboard'); ?>"
               class="<?php if(is_page('operation-dashboard') && !isset($_GET['tab'])) echo 'active'; ?>">
               Dashboard
            </a>

            <a href="<?php echo home_url('/operation-dashboard?tab=new'); ?>"
               class="<?php if(isset($_GET['tab']) && $_GET['tab'] === 'new') echo 'active'; ?>">
               New Properties
            </a>

            <a href="<?php echo home_url('/operation-dashboard?tab=assigned'); ?>"
               class="<?php if(isset($_GET['tab']) && $_GET['tab'] === 'assigned') echo 'active'; ?>">
               Assigned
            </a>

            <a href="<?php echo home_url('/operation-dashboard?tab=completed'); ?>"
               class="<?php if(isset($_GET['tab']) && $_GET['tab'] === 'completed') echo 'active'; ?>">
               Completed
            </a>

        <?php elseif ($role === 'engineer'): ?>

            <a href="<?php echo home_url('/engineer-dashboard'); ?>"
               class="<?php if(is_page('engineer-dashboard') && !isset($_GET['tab'])) echo 'active'; ?>">
               Dashboard
            </a>

            <a href="<?php echo home_url('/engineer-dashboard'); ?>">
               Assigned Properties
            </a>

            <a href="<?php echo home_url('/engineer-dashboard?tab=completed'); ?>"
               class="<?php if(isset($_GET['tab']) && $_GET['tab'] === 'completed') echo 'active'; ?>">
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
        ?>

        </section>

    </main>

</div>

<?php wp_footer(); ?>
</body>
</html>