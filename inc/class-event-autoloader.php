<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class TP_Event_Autoloader {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( TP_EVENT_PATH ) . '/inc/';
	}

	/**
	 * Take a class name and turn it into a file name
	 *
	 * @param  string $class
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( array( 'tp_', '_' ), array( '', '-' ), $class ) . '.php';
	}

	/**
	 * Include a class file
	 *
	 * @param  string $path
	 *
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}

	/**
	 * Auto-load HB classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );

		$path = $this->include_path;

		// payment gateways
		if ( strpos( $class, 'tp_event_payment_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), strlen( 'tp_event_payment_gateway_' ) ) . '/';
		}
		// abstract class
		if ( strpos( $class, 'tp_event_abstract_' ) === 0 ) {
			$path = $this->include_path . 'abstracts/';
		}

		// widgets
		if ( stripos( $class, 'tp_event_widget_' ) === 0 ) {
			$path = $this->include_path . '/widgets/';
		}

		// admin metaboxs
		if ( strpos( $class, '_event_admin_metabox_' ) ) {
			$path = $this->include_path . 'admin/metaboxes/';
		}

		$this->load_file( $path . $file );
	}
}

new TP_Event_Autoloader();
