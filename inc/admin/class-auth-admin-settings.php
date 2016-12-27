<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Auth_Admin_Settings {

    public static function init() {
        /**
         * Add general setting fields
         */
        add_action( 'event_admin_setting_page_general', array( __CLASS__, 'general_fields' ) );
        /**
         * Add page settings
         */
        add_action( 'event_admin_setting_pages', array( __CLASS__, 'setting_pages' ) );
    }

    public static function general_fields( $fields ) {
        $prefix = 'thimpress_events_';
        $auth_fields = array(
            array(
                'type' => 'section_start',
                'id' => 'auth_general_settings',
                'title' => __( 'Authentication Settings', 'tp-event' ),
                'desc' => __( 'Auth setting page', 'tp-event' )
            ),
            array(
                'type' => 'select_page',
                'title' => __( 'Register Page', 'tp-event' ),
                'desc' => __( 'This controlls which the register page.', 'tp-event' ),
                'id'    => $prefix . 'register_page_id',
            ),
            array(
                'type' => 'select_page',
                'title' => __( 'Login Page', 'tp-event' ),
                'desc' => __( 'This controlls which the login page.', 'tp-event' ),
                'id'    => $prefix . 'login_page_id',
            ),
//            array(
//                'type' => 'select_page',
//                'title' => __( 'Reset Password', 'tp-event' ),
//                'desc' => __( 'This controlls which the reset password page.', 'tp-event' ),
//                'id'    => $prefix . 'reset_password_page_id'
//            ),
//            array(
//                'type' => 'select_page',
//                'title' => __( 'Forgot Pass', 'tp-event' ),
//                'desc' => __( 'This controlls which the forgot password page.', 'tp-event' ),
//                'id' => $prefix . 'forgot_pass_page_id'
//            ),
            array(
                'type' => 'select_page',
                'title' => __( 'My Account', 'tp-event' ),
                'desc' => __( 'This controlls which the dashboard page.', 'tp-event' ),
                'id' => $prefix . 'account_page_id',
            ),
            array(
                'type' => 'checkbox',
                'title' => __( 'Send email.', 'tp-event' ),
                'desc' => __( 'Send notify when user register.', 'tp-event' ),
                'id' => $prefix . 'register_notify',
            ),
            array(
                'type' => 'section_end',
                'id' => 'auth_general_settings'
            ),
            // Currency
            array(
                'type' => 'section_start',
                'id' => 'auth_currency_settings',
                'title' => __( 'Currency', 'tp-event' ),
                'desc'  => __( 'Currency setting will show up on frontend', 'tp-event' )
            ),
            array(
                'type' => 'select',
                'title' => __( 'Currency', 'tp-event' ),
                'desc' => __( 'This controlls what the currency prices', 'tp-event' ),
                'id' => $prefix . 'currency',
                'options' => event_auth_currencies(),
                'default' => 'USD'
            ),
            array(
                'type' => 'select',
                'title' => __( 'Currency Position', 'tp-event' ),
                'desc' => __( 'This controlls the position of the currency symbol', 'tp-event' ),
                'id' => $prefix . 'currency_position',
                'options' => array(
                    'left' => __( 'Left', 'tp-event' ) . ' ' . '(£99.99)',
                    'right' => __( 'Right', 'tp-event' ) . ' ' . '(99.99£)',
                    'left_space' => __( 'Left with space', 'tp-event' ) . ' ' . '(£ 99.99)',
                    'right_space' => __( 'Right with space', 'tp-event' ) . ' ' . '(99.99 £)',
                ),
                'default' => 'left'
            ),
            array(
                'type' => 'text',
                'title' => __( 'Thousand Separator', 'tp-event' ),
                'id' => $prefix . 'currency_thousand',
                'default' => ',',
                'placeholder' => ','
            ),
            array(
                'type' => 'text',
                'title' => __( 'Decimal Separator', 'tp-event' ),
                'id' => $prefix . 'currency_separator',
                'default' => '.',
                'placeholder' => '.'
            ),
            array(
                'type' => 'number',
                'title' => __( 'Number of Decimals', 'tp-event' ),
                'id' => $prefix . 'currency_num_decimal',
                'atts' => array( 'step' => 'any' ),
                'default' => '2',
                'placeholder' => '2'
            ),
            array(
                'type' => 'section_end',
                'id' => 'auth_currency_settings'
            ),
        );
        return array_merge( $fields, $auth_fields );
    }
    
    /**
     * filter pages
     * @param type $pages
     * @return array
     */
    public static function setting_pages( $pages = array() ) {
        $pages[] = require_once TP_EVENT_PATH . 'inc/admin/settings/class-auth-admin-setting-email.php';
        $pages[] = require_once TP_EVENT_PATH . 'inc/admin/settings/class-auth-admin-setting-checkout.php';
        $pages[] = require_once TP_EVENT_PATH . 'inc/admin/settings/class-auth-admin-setting-account.php';
        return $pages;
    }

}

Auth_Admin_Settings::init();
