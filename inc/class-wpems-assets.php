<?php
/**
 * WP Events Manager Assets class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * DN_Assets class
 */
class WPEMS_Assets {

	/**
	 * styles
	 * @var type array
	 */
	public static $_styles = array();

	/**
	 * scripts
	 * @var type array
	 */
	public static $_scripts = array();

	/**
	 * localize
	 * @var type array
	 */
	public static $_localize_scripts = array();

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * register script
	 */
	public static function register_script( $handle = '', $src = '', $deps = array(), $ver = false, $in_footer = true ) {
		self::$_scripts[$handle] = array( $handle, self::_load_file_min( $src ), $deps, $ver, $in_footer );
	}

	/**
	 * register style
	 */
	public static function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		$uri = $src;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$ver = uniqid();

		} else {
			$ver = WPEMS_VER;
			$uri = self::_load_file_min( $src );
		}

		self::$_styles[$handle] = array( $handle, $uri, $deps, $ver, $media );
	}

	/**
	 * localize_script scripts
	 *
	 * @param type $handle
	 * @param type $name
	 * @param type $data
	 */
	public static function localize_script( $handle, $name, $data ) {
		self::$_localize_scripts[$handle] = array( $handle, $name, $data );
	}

	/**
	 * frontend enqueue scripts
	 */
	public static function enqueue_scripts( $hook ) {
		/**
		 * Before enqueue scripts
		 */
		do_action( 'tp_event_before_enqueue_scripts', $hook );

		wp_enqueue_script( 'jquery' );
		// wp_dequeue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'wp-util' );
		wp_enqueue_script( 'backbone' );
		wp_enqueue_script( 'underscore' );

		if ( self::$_scripts ) {
			foreach ( self::$_scripts as $handle => $param ) {
				call_user_func_array( 'wp_register_script', $param );
				if ( array_key_exists( $handle, self::$_localize_scripts ) ) {
					call_user_func_array( 'wp_localize_script', self::$_localize_scripts[$handle] );
				}
				wp_enqueue_script( $handle );
			}
		}

		if ( self::$_styles ) {
			foreach ( self::$_styles as $handle => $param ) {
				call_user_func_array( 'wp_register_style', $param );
				wp_enqueue_style( $handle );
			}
		}

		/**
		 * After enqueue scripts
		 */
		do_action( 'tp_event_after_enqueue_scripts', $hook );
	}

	/**
	 * Get file uri.
	 * if WP_DEBUG is FALSE will load minify file
	 */
	public static function _load_file_min( $uri = '' ) {

		$file      = self::_get_path_by_uri( $uri );
		$file_name = basename( $file );
		$parse     = explode( '.', $file_name );
		/**
		 * file's extension '.js' or '.css'
		 */
		$file_type = end( $parse );
		if ( in_array( 'min', $parse ) ) {
			return $uri;
		}

		array_pop( $parse );
		$parse[]  = 'min';
		$parse[]  = $file_type;
		$new_file = implode( '.', $parse );

		$new_uri  = str_replace( $file_name, $new_file, $uri );
		$new_path = self::_get_path_by_uri( $new_uri );
		if ( file_exists( $new_path ) ) {
			return $new_uri;
		}
		return $uri;
	}

	/**
	 * get file path by uri
	 *
	 * @param type $uri
	 */
	public static function _get_path_by_uri( $uri = '' ) {
		$base_url = trailingslashit( WPEMS_URI );
		$path     = trailingslashit( WPEMS_PATH );

		/**
		 * file path
		 */
		return str_replace( $base_url, $path, $uri );
	}

}

/**
 * init
 */
WPEMS_Assets::init();
