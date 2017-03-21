<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class WPEMS_Autoloader {

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

		$this->include_path = untrailingslashit( WPEMS_PATH ) . '/inc/';
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
	 * Auto-load WPEMS classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );

		$path = $this->include_path;

		// payment gateways
		if ( strpos( $class, 'wpems_payment_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), strlen( 'wpems_payment_gateway_' ) ) . '/';
		}
		// abstract class
		if ( strpos( $class, 'wpems_abstract_' ) === 0 ) {
			$path = $this->include_path . 'abstracts/';
		}

		// admin metaboxs
		if ( strpos( $class, 'wpems_admin_metabox_' ) === 0 ) {
			$path = $this->include_path . 'admin/metaboxes/';
		}

		// widgets
		if ( stripos( $class, 'wpems_widget_' ) === 0 ) {
			$path = $this->include_path . '/widgets/';
		}

		$this->load_file( $path . $file );
	}
}

new WPEMS_Autoloader();
