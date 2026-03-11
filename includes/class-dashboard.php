<?php
if (!defined('ABSPATH')) exit;

class PW_Dashboard {

    public function __construct() {
        add_filter('template_include', [$this, 'load_templates']);
    }

    public function load_templates($template) {

        global $post;

        $slug = '';

        // 1️⃣ Get slug from WP post object
        if ($post && isset($post->post_name)) {
            $slug = $post->post_name;
        }

        // 2️⃣ Fallback: detect slug from URL
        if (!$slug) {
            $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $parts = explode('/', $uri);
            $slug = end($parts);
        }

        // AUTH PAGES
        if (in_array($slug, ['login','register'])) {
            return PW_PATH . 'templates/auth-layout.php';
        }

        // ALL DASHBOARD PAGES
        $pages = [
            'customer-dashboard',
            'add-property',
            'customer-profile',
            'my-properties',
            'operation-dashboard',
            'engineer-dashboard',
            'assign-package',
            'manage-addons',
            'update-visit',
            'visit-details',
            'visit-reports'
        ];

        if (in_array($slug, $pages)) {
            return PW_PATH . 'templates/layout.php';
        }

        return $template;
    }
}

new PW_Dashboard();