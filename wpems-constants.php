<?php
/**
 * Define common constants for WP Events Manager
 */
const WPEMS_PLUGIN_FILE = __FILE__;
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$upload_dir  = wp_upload_dir();
$plugin_info = get_plugin_data( WPEMS_PLUGIN_FILE );

// version.
define( 'WPEMS_VERSION', $plugin_info['Version'] );

// Path
define( 'WPEMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEMS_URI', plugin_dir_url( __FILE__ ) );

// Custom post type
const WPEMS_EVENT_CPT = 'tp_event';

const WPEMS_INC        = WPEMS_PATH . 'inc/';
const WPEMS_INC_URI    = WPEMS_URI . 'inc/';
const WPEMS_ASSETS_URI = WPEMS_URI . 'assets/';
const WPEMS_LIB_URI    = WPEMS_INC_URI . 'libraries/';
const WPEMS_VER        = '2.1.8';
const WPEMS_MAIN_FILE  = __FILE__;