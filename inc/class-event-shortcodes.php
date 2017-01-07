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
		add_action( 'tp_event_shortcode_wrapper_start', array( __CLASS__, 'shortcode_wrapper_start' ) );
		add_action( 'tp_event_shortcode_wrapper_end', array( __CLASS__, 'shortcode_wrapper_end' ) );

		$shortcodes = array(
			'list_event' => __CLASS__ . '::list_event',
			'register'   => __CLASS__ . '::register',
			'login'      => __CLASS__ . '::login',
			'account'    => __CLASS__ . '::account',
			'countdown'  => __CLASS__ . '::countdown',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "tp_event_{$shortcode}_shortcode_tag", 'tp_event_' . $shortcode ), $function );
		}

		add_action( 'template_redirect', array( __CLASS__, 'auto_shortcode' ) );
	}

	public static function list_event( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_type' => 'tp_event'
			), $atts
		);
		return TP_Event_Shortcodes::render( 'event-list', 'event-list.php', $atts );
	}

	/**
	 * Redirect page
	 */
	public function auto_shortcode() {
		if ( !is_page() ) {
			return;
		}

		global $post;
		if ( is_user_logged_in() && in_array( $post->ID, array( tp_event_get_page_id( 'register' ), tp_event_get_page_id( 'login' ) ) ) ) {
			wp_safe_redirect( home_url( '/' ) );
		}
	}

	/**
	 * Shortcode wrapper start
	 *
	 * @param $shortcode
	 */
	public static function shortcode_wrapper_start( $shortcode ) {
		echo '<div class="tp-event-wrapper "' . esc_attr( $shortcode ) . '>';
	}

	/**
	 * Shortcode wrapper end
	 */
	public static function shortcode_wrapper_end() {
		echo '<div>';
	}

	/**
	 * Render shortcode
	 *
	 * @param string $shortcode
	 * @param string $template
	 * @param array  $atts
	 *
	 * @return string
	 */
	public static function render( $shortcode = '', $template = '', $atts = array() ) {
		ob_start();
		do_action( 'tp_event_shortcode_wrapper_start', $shortcode );
		tp_event_get_template( 'shortcodes/' . $template, array( 'atts' => $atts ) );
		do_action( 'tp_event_shortcode_wrapper_end', $shortcode );
		return ob_get_clean();
	}

}

TP_Event_Shortcodes::init();