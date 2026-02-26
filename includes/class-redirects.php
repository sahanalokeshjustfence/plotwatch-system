<?php
if (!defined('ABSPATH')) exit;

class PW_Redirects {

    public function __construct() {
        add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);
    }

    public function login_redirect($redirect_to, $request, $user) {

        // If login failed or no roles
        if (!isset($user->roles) || empty($user->roles)) {
            return $redirect_to;
        }

        // Customer Redirect
        if (in_array('customer', (array) $user->roles)) {
            return home_url('/customer-dashboard');
        }

        // Operation Member Redirect
        if (in_array('operation_member', (array) $user->roles)) {
            return home_url('/operation-dashboard');
        }

        // Engineer Redirect
        if (in_array('engineer', (array) $user->roles)) {
            return home_url('/engineer-dashboard');
        }

        // Default (admin or others)
        return $redirect_to;
    }
}

new PW_Redirects();