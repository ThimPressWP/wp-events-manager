<?php
/**
 * Shortcode login.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class LoginShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'login';

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'form-login.php';
		$shortcode = 'user-login';

		if ( ! wpems_get_page_id( 'login' ) || is_user_logged_in() ) {
			return '';
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

