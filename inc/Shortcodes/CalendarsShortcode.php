<?php
/**
 * Shortcode calendars.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class CalendarsShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'calendars';

		/**
	 * Show event calendars
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'event-calendar.php';
		$shortcode = 'event';

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

