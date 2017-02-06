<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class TP_Event_Roles {

    /**
     * Add Roles
     * @global type $wp_roles
     */
    public static function add_roles() {
        global $wp_roles;
        $wp_roles->add_role( 'event_manager', __( 'Event Manager', 'tp-event' ), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'unfiltered_html' => true,
            'upload_files' => true,
            'export' => true,
            'import' => true,
            'delete_others_pages' => true,
            'delete_others_posts' => true,
            'delete_pages' => true,
            'delete_private_pages' => true,
            'delete_private_posts' => true,
            'delete_published_pages' => true,
            'delete_published_posts' => true,
            'edit_others_pages' => true,
            'edit_others_posts' => true,
            'edit_pages' => true,
            'edit_private_pages' => true,
            'edit_private_posts' => true,
            'edit_published_pages' => true,
            'edit_published_posts' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'moderate_comments' => true,
            'publish_pages' => true,
            'publish_posts' => true,
            'read_private_pages' => true,
            'read_private_posts' => true
        ) );
        $wp_roles->add_role( 'event_editor', __( 'Event Editor', 'tp-event' ), array(
            'read' => true
        ) );
    }

    /**
     * Add Caps
     */
    public static function add_caps() {
        global $wp_roles;

        $core_caps = self::get_cor_caps();
        foreach ( $core_caps as $caps ) {
            foreach ( $caps as $cap ) {
                $wp_roles->add_cap( 'administrator', $cap );
                $wp_roles->add_cap( 'event_manager', $cap );
                $wp_roles->add_cap( 'event_editor', $cap );
            }
        }

        $wp_roles->add_cap( 'administrator', 'event_manage_settings' );
        $wp_roles->add_cap( 'event_manager', 'event_manage_settings' );
    }

    /**
     * Remove Roles
     * @global type $wp_roles
     */
    public static function remove_roles() {
        global $wp_roles;
        /**
         * Remove Custom Caps
         */
        self::remove_caps();

        /**
         * Remove Roles
         */
        remove_role( 'event_manager' );
        remove_role( 'event_editor' );
    }

    /**
     * Remove Caps
     */
    public static function remove_caps() {
        global $wp_roles;
        $core_caps = self::get_cor_caps();
        foreach ( $core_caps as $caps ) {
            foreach ( $caps as $cap ) {
                $wp_roles->remove_cap( 'administrator', $cap );
                $wp_roles->remove_cap( 'event_manager', $cap );
                $wp_roles->remove_cap( 'event_editor', $cap );
            }
        }
    }

    /**
     * Get Core Caps
     * @since 1.0.0
     */
    public static function get_cor_caps() {
        $cap_types = array( 'tp_event' );
        $capabilities = array();
        foreach ( $cap_types as $cap ) {
            $capabilities[$cap] = array(
                "edit_{$cap}",
                "read_{$cap}",
                "delete_{$cap}",
                'delete_' . $cap . 's',
                'delete_published_' . $cap . 's',
                'edit_' . $cap . 's',
                'edit_published_' . $cap . 's',
                'publish_' . $cap . 's',
                'delete_private_' . $cap . 's',
                'edit_private_' . $cap . 's',
                'delete_others_' . $cap . 's',
                'edit_others_' . $cap . 's'
            );
        }
        return $capabilities;
    }

}
