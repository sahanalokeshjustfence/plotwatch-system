<?php
if (!defined('ABSPATH')) exit;

class PW_Dashboard {

    public function __construct() {
        add_filter('template_include', [$this, 'load_templates']);
    }

    public function load_templates($template) {

        /*
        |-----------------------------------------
        | AUTH PAGES
        |-----------------------------------------
        */
        if (is_page(['login','register'])) {
            return PW_PATH . 'templates/auth-layout.php';
        }

        /*
        |-----------------------------------------
        | CUSTOMER PAGES
        |-----------------------------------------
        */
        if (is_page(['customer-dashboard','add-property','customer-profile'])) {
            return PW_PATH . 'templates/layout.php';
        }

        /*
        |-----------------------------------------
        | OPERATION DASHBOARD
        |-----------------------------------------
        */
        if (is_page('operation-dashboard')) {
            return PW_PATH . 'templates/layout.php';
        }

        /*
        |-----------------------------------------
        | ENGINEER DASHBOARD
        |-----------------------------------------
        */
        if (is_page('engineer-dashboard')) {
            return PW_PATH . 'templates/layout.php';
        }

        return $template;
    }
}

new PW_Dashboard();