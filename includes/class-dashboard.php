<?php
if (!defined('ABSPATH')) exit;

class PW_Dashboard {

    public function __construct() {
        add_filter('template_include', [$this, 'load_templates']);
    }

    public function load_templates($template) {

        // AUTH PAGES (Login / Register)
        if (is_page(['login','register'])) {
            return PW_PATH . 'templates/auth-layout.php';
        }

        // CUSTOMER PAGES
        if (
            is_page('customer-dashboard') ||
            is_page('add-property') ||
            is_page('customer-profile') ||
            is_page('my-properties')
        ) {
            return PW_PATH . 'templates/layout.php';
        }

        // OPERATION DASHBOARD
        if (is_page('operation-dashboard')) {
            return PW_PATH . 'templates/layout.php';
        }

        // ENGINEER DASHBOARD
        if (is_page('engineer-dashboard')) {
            return PW_PATH . 'templates/layout.php';
        }

        // OTHER CUSTOM PAGES
        if (
            is_page('assign-package') ||
            is_page('manage-addons') ||
            is_page('update-visit')
        ) {
            return PW_PATH . 'templates/layout.php';
        }

        return $template;
    }
}

new PW_Dashboard();