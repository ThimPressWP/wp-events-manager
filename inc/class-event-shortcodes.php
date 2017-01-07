<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * TP_Event_Shortcodes class
 */
class TP_Event_Shortcodes {

	/**
	 * Init shortcodes
	 */
	public static function init() {
		$shortcodes = array(
			'archive_page'    => __CLASS__ . '::archive_page',
			'register'        => __CLASS__ . '::register',
			'login'           => __CLASS__ . '::login',
			'account'         => __CLASS__ . '::account',
			'countdown'       => __CLASS__ . '::countdown',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "tp_event_{$shortcode}_shortcode_tag", 'tp_event_' . $shortcode ), $function );
		}

		add_action( 'template_redirect', array( __CLASS__, 'auto_shortcode' ) );
	}

}