<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Event_Install {

    /**
     * upgrade store
     * @var type null || array
     */
    public static $db_upgrade = null;

    /**
     * Init
     */
    public static function init() {
        self::$db_upgrade = array(
            '1.5' => TP_EVENT_INC . 'admin/upgrades/upgrade-1.5.php'
        );
    }

    /**
     * register_activation_hook callback
     */
    public static function install() {
        if ( !defined( 'TP_EVENT_INSTALLING' ) ) {
            define( 'TP_EVENT_INSTALLING', true );
        }
        /**
         * Upgrade options
         */
        self::upgrade_database();
        /**
         * Add new roles
         */
        Event_Roles::add_roles();
        /**
         * Add new caps
         */
        Event_Roles::add_caps();
        /**
         * Create Pages
         */
        self::create_pages();
        /**
         * Update current version
         */
        update_option( 'thimpress-event-version', TP_EVENT_VER );
    }

    /**
     * register_deactivation_hook callback
     */
    public static function uninstall() {
        /**
         * Remove Caps and Roles
         */
        Event_Roles::remove_roles();
    }

    /**
     * Create default options
     */
    public static function create_options() {
        $setings = Event_Admin_Settings::get_setting_pages();
        foreach ( $setings as $setting ) {
            $options = $setting->get_settings();
            foreach ( $options as $option ) {
                if ( ! empty( $option['id'] ) && ! get_option( $option['id'] ) && ! empty( $option['default'] ) ) {
                    update_option( $option['id'], $option['default'] );
                }
            }
        }
    }

    /**
     * Create pages
     */
    public static function create_pages() {
        $pages = array(
            'event_archive'    => array(
                'name'    => _x( 'events', 'Page Slug', 'tp-hotel-booking' ),
                'title'   => _x( 'Event Archive', 'Page Title', 'tp-hotel-booking' ),
                'content' => '[' . apply_filters( 'event_archive_page_content', 'event_archive' ) . ']'
            )
        );
        foreach ( $pages as $name => $page ) {
            event_create_page( esc_sql( $page['name'] ), $name . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? event_get_page_id( $page['parent'] ) : '' );
        }
    }

    /**
     * Upgrade Database
     */
    public static function upgrade_database() {
        $old_version = get_option( 'thimpress-event-version' );
        foreach ( self::$db_upgrade as $ver => $file ) {
            if ( !$old_version || version_compare( $old_version, $ver, '<' ) ) {
                require_once $file;
            }
        }
    }

}

Event_Install::init();

// active plugin
register_activation_hook( TP_EVENT_MAIN_FILE, array( 'Event_Install', 'install' ) );
register_deactivation_hook( TP_EVENT_MAIN_FILE, array( 'Event_Install', 'uninstall' ) );
