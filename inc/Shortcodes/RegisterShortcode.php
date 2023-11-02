<?php
/**
 * Shortcode list event.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class RegisterShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'register';

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = '';
		$shortcode = 'user-register';

		// handle the registration function
		if ( ! get_option( 'users_can_register' ) ) {
			$template = 'user-cannot-register.php';
		} elseif ( ! empty( $_REQUEST['registered'] ) ) {
			$email = sanitize_email( $_REQUEST['registered'] );
			$user  = get_user_by( 'email', $email );
			if ( $user && $user->ID ) {
				wp_new_user_notification( $user->ID, null, 'user' );

				// register completed
				$template = 'register-completed.php';
			} else {
				// error
				$template = 'register-error.php';
			}
		} elseif ( ! is_user_logged_in() ) {
			// show register form
			$template = 'form-register.php';
		}

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			self::shortcode_wrapper_start( $shortcode );
			wpems_get_template( 'shortcodes/' . $template, $attrs );
			self::shortcode_wrapper_end( $shortcode );

			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

