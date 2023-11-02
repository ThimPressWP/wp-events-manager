<?php
/**
 * Shortcode list event.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class ForgotPasswordShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'forgot_password';

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
		$shortcode = 'forgot-password';

		$checkemail = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false;
		if ( $checkemail ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );
		} else {
			$template = 'forgot-password.php';
		}

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			self::shortcode_wrapper_start( $shortcode );
			wpems_get_template( 'shortcodes/' . $template, $attrs );
			self::shortcode_wrapper_end( $shortcode );

			echo $checkemail;
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}


}

