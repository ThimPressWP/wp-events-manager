<?php
/**
 * Shortcode Account.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class AccountShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'account';

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'user-account.php';
		$shortcode = 'user-account';

		$user  = wp_get_current_user();
		$attrs = array(
			'post_type'     => 'event_auth_book',
			'post_per_page' => - 1,
			'order'         => 'DESC',
			'meta_query'    => array(
				array(
					'key'   => 'ea_booking_user_id',
					'value' => $user->ID,
				),
			),
		);

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

