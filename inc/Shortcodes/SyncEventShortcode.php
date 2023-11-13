<?php
/**
 * Shortcode
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;
use WPEMS\Database\GoogleCalendar;

class SyncEventShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'sync_event';

	/**
	 *
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'google-calendars.php';
		$shortcode = 'google-sync';

		$eventData   = GoogleCalendar::event_data();
		$bookingData = array();

		if ( ! empty( $eventData ) ) {
			foreach ( $eventData as $key => $value ) {
				$bookingData[] = array(
					'summary' => $value->post_content,
					'start'   => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
					'end'     => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
				);
			}
		}

		$attrs = shortcode_atts(
			array(
				'bookingData' => $bookingData,
			),
			$attrs
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

