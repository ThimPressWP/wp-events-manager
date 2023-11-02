<?php
/**
 * Shortcode list event.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class CountdownShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'countdown';

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'event-countdown.php';
		$shortcode = 'event-countdown';

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

