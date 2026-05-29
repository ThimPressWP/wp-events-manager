<?php
/**
 * Event template hooks.
 *
 * @package WPEMS/TemplateHooks
 */

namespace WPEMS\TemplateHooks;

defined( 'ABSPATH' ) || exit;

class EventTemplateHooks {

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register template hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action( 'tp_event_before_main_content', 'wpems_before_main_content' );
		add_action( 'tp_event_after_main_content', 'wpems_after_main_content' );
		add_action( 'tp_event_before_single_event', 'wpems_before_single_event' );
		add_action( 'tp_event_after_single_event', 'wpems_after_single_event' );
		add_action( 'tp_event_loop_event_countdown', 'wpems_loop_event_countdown' );
		add_action( 'tp_event_after_event_loop', 'wpems_archive_event_pagination' );
		add_action( 'tp_event_single_event_content', 'wpems_single_event_content' );
		add_action( 'tp_event_after_single_event', 'wpems_single_event_register' );
	}
}
