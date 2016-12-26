<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class Event_Admin_Menu {

    /**
     * menus
     * @var array
     */
    public $_menus = array();

    /**
     * instead new class
     * @var null
     */
    static $_instance = null;

    public function __construct() {
        // admin menu
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    // add admin menu callback
    public function admin_menu() {
        /**
         * menus
         * @var array
         */
        $menus = apply_filters( 'event_admnin_menus', $this->_menus );
        add_menu_page( __( 'TP Events', 'tp-event' ), __( 'TP Events', 'tp-event' ), 'edit_others_tp_events', 'tp-event-setting', null, 'dashicons-calendar', 9 );
        if ( $menus ) {
//            var_dump($menus);die();
            foreach ( $menus as $menu ) {
                call_user_func_array( 'add_submenu_page', $menu );
            }
        }
        add_submenu_page( 'tp-event-setting', __( 'TP Events Settings', 'tp-event' ), __( 'Settings', 'tp-event' ), 'event_manage_settings', 'tp-event-setting', array( 'Event_Admin_Settings', 'output' ) );
    }

    /**
     * add menu item
     * @param $params
     */
    public function add_menu( $params ) {
        $this->_menus[] = $params;
    }

    /**
     * instance
     * @return object class
     */
    public static function instance() {
        if ( self::$_instance )
            return self::$_instance;

        return new self();
    }

}

Event_Admin_Menu::instance();
