<?php
if (!defined('ABSPATH')) exit;

class PW_Roles {

    public function __construct() {
        add_action('init', [$this, 'create_roles']);
    }

    public function create_roles() {

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */
        if (!get_role('customer')) {
            add_role(
                'customer',
                'Customer',
                [
                    'read' => true
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OPERATION MEMBER
        |--------------------------------------------------------------------------
        */
        if (!get_role('operation_member')) {
            add_role(
                'operation_member',
                'Operation Member',
                [
                    'read' => true
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ENGINEER
        |--------------------------------------------------------------------------
        */
        if (!get_role('engineer')) {
            add_role(
                'engineer',
                'Engineer',
                [
                    'read' => true
                ]
            );
        }
    }
}

new PW_Roles();