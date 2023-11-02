<?php
/**
 * Shortcode list event.
 */
namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;

class ListEventShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'list_event';

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'event-list.php';
		$shortcode = 'list-event';

		$attrs = array( 'post_type' => 'tp_event' );

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			self::shortcode_wrapper_start( $shortcode );
			wpems_get_template( 'shortcodes/' . $template, array( 'args' => $attrs) );
			self::shortcode_wrapper_end( $shortcode );

			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}

