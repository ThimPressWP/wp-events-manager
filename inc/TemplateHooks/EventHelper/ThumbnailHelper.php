<?php
/**
 * Class ThumbnailHelper
 */

namespace WPEMS\TemplateHooks\EventHelper;

use WPEMS\Filter\Event\EventFilter;
use WPEMS\Models\Event\EventModel;
use WPEMS\Helpers\Singleton;

class ThumbnailHelper {
	use Singleton;

	public function init(){
	}

	/**
	 * 
	 * 
	 * @param int    $event_id
	 * @param string $size
	 * @param array  $attr
	 *
	 * @return string
	 */
	public function get_event_image( $event_id, $size = 'event-thumbnail', $attr = array(), $event_title = '' ) {
		$attr  = wp_parse_args(
			$attr,
			array(
				'alt'   => $event_title,
				'title' => $event_title,
			)
		);

		$image = '';

		if ( has_post_thumbnail( $event_id ) ) {
			$image = get_the_post_thumbnail( $event_id, $size, $attr );
		}

		if ( ! $image ) {
			$image = $this->image( 'no-image.png' );
			$image = sprintf(
				'<img src="%s" alt="%s">',
				esc_url_raw( $image ),
				_x( 'event thumbnail', 'no event thumbnail', 'WPEMS' )
			);
		}

		return $image;
	}

	/**
	 * Short way to return image file is located in LearnPress directory.
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function image( $file ) {
		if ( ! preg_match( '/.(jpg|png)$/', $file ) ) {
			$file .= '.jpg';
		}

		return WPEMS_URI . "assets/images/{$file}";;
	}
}
