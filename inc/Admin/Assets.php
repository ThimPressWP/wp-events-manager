<?php
/**
 * Admin assets.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager admin assets.
 */
class Assets {

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action( 'tp_event_before_enqueue_scripts', array( static::class, 'register_scripts' ) );
	}

	/**
	 * Register scripts.
	 *
	 * @param string $hook Current admin hook.
	 *
	 * @return void
	 */
	public static function register_scripts( $hook ) {

		\WPEMS_Assets::register_script( 'wpems-admin-js', WPEMS_ASSETS_URI . '/dist/js/admin/admin-events.js' );
		\WPEMS_Assets::localize_script(
			'wpems-admin-js',
			'WPEMS_ADMIN',
			array(
				'event_remove_notice_nonce' => wp_create_nonce( 'event_remove_notice' ),
			)
		);
		\WPEMS_Assets::register_style( 'wpems-admin-css', WPEMS_ASSETS_URI . '/dist/css/admin/admin.css' );
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Assets', false ) ) {
	\class_alias( Assets::class, 'WPEMS_Admin_Assets' );
}
