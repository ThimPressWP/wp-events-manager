<?php

namespace WPEMS\Shortcodes;

/**
 * WP Events Manager Shortcodes class
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
 * Class AbstractShortcode
 */

abstract class AbstractShortcode {

	protected $prefix = 'wp_event_';
	protected $shortcode_name;

	protected function init() {
		 // Register shortcode.
		add_shortcode( $this->prefix . $this->shortcode_name, array( $this, 'render' ) );

		// add_action('template_redirect', array($this, 'redirect_logged_in'));
	}

	/**
	 * Redirect page
	 */
	public function redirect_logged_in() {
		if ( ! is_page() ) {
			return;
		}

		global $post;

		if ( ! isset( $post->ID ) ) {
			return;
		}

		$page_ids = [ 'register', 'login', 'forgot_password' ];
		$page_id  = [];

		foreach ( $page_ids as $page ) {
			$page_id[] = wpems_get_page_id( $page );
		}

		if ( is_user_logged_in() && in_array( $post->ID, $page_id ) ) {
			wp_safe_redirect( home_url( '/' ) );
		}
	}

	/**
	 * Shortcode wrapper start
	 *
	 * @param $shortcode
	 */
	public function shortcode_wrapper_start( $shortcode ) {
		echo '<div class="event-wrapper-shortcode ' . esc_attr( $shortcode ) . '">';
	}

	/**
	 * Shortcode wrapper end
	 */
	public function shortcode_wrapper_end() {
		echo '</div>';
	}

	/**
	 * Render template of shortcode.
	 * If not set any atrribute on short, $attrs is empty string.
	 *
	 * @param string|array $attrs
	 *
	 * @return string
	 */
	abstract public function render( $attrs): string;
}
