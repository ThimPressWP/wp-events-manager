<?php
/**
 * Define common constants for WP Events Manager
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$upload_dir                 = wp_upload_dir();
$plugin_info                = get_plugin_data( WPEMS_PLUGIN_FILE );

// Version.
define( 'WPEMS_VER', $plugin_info['Version'] );

// Plugin Paths and Urls.
define( 'WPEMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEMS_URI', plugin_dir_url( __FILE__ ) );
define( 'WPEMS_INC', WPEMS_PATH . 'inc/' );
define( 'WPEMS_INC_URI', WPEMS_URI . 'inc/' );
define( 'WPEMS_ASSETS_URI', WPEMS_URI . 'assets/' );
define( 'WPEMS_LIB_URI', WPEMS_INC_URI . 'libraries/' );
define( 'WPEMS_MAIN_FILE', __FILE__ );

// Define constants for custom post types.
const WPEMS_EVENT_CPT       = 'tp_event';

// Define constants for custom taxonomy.
const WPEMS_EVENT_CATEGORY  = 'tp_event_category';
const WPEMS_EVENT_TAG       = 'tp_event_tag';
