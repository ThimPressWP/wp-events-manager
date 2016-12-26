<?php

if ( !defined( 'ABSPATH' ) ) {
    exit();
}

class Auth_Shortcodes {

    public static function init() {
        $shortcodes = array(
            'auth',
            'auth_login',
            'auth_register',
//            'auth_forgot_password',
//            'auth_reset_password',
            'auth_my_account',
        );

        foreach ( $shortcodes as $shortcode ) {
            add_shortcode( 'event_' . $shortcode, array( __CLASS__, $shortcode ) );
        }
    }

    public static function auth( $atts, $content = '' ) {
        Auth_Authentication::event_auth( $atts, $content );
    }

    public static function auth_login( $atts, $content = '' ) {
        Auth_Authentication::event_auth_login( $atts, $content );
    }

    public static function auth_register( $atts, $content = '' ) {
        Auth_Authentication::event_auth_register( $atts, $content );
    }

    public static function auth_forgot_password( $atts, $content = '' ) {
        Auth_Authentication::forgot_pass( $atts, $content );
    }

    public static function auth_reset_password( $atts, $content = '' ) {
        Auth_Authentication::reset_password( $atts, $content );
    }

    public static function auth_my_account( $atts, $content = '' ) {
        Auth_Authentication::my_account( $atts, $content );
    }

}

Auth_Shortcodes::init();
