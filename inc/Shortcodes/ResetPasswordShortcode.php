<?php
/**
 * Shortcode reset password.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class ResetPasswordShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'reset_password';

	/**
	 * Show reset_password
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'reset-password.php';
		$shortcode = 'reset-password';

		$attrs = wp_parse_args(
			$attrs,
			array(
				'key'   => isset( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : '',
				'login' => isset( $_REQUEST['login'] ) ? sanitize_text_field( $_REQUEST['login'] ) : '',
			)
		);

		$attrs = wp_parse_args(
			$attrs,
			array(
				'user_login'  => '',
				'redirect_to' => '',
				'checkemail'  => isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false,
			)
		);

		if ( $attrs['checkemail'] ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );
		}

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			self::shortcode_wrapper_start( $shortcode );
			wpems_get_template( 'shortcodes/' . $template, array( 'atts' => $attrs ) );
			self::shortcode_wrapper_end( $shortcode );

			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

