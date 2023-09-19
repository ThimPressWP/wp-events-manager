<?php

namespace WPEMS\Templates;

use WPEMS\Model as Md;
use WP_Post;

class WpemsEventsTemplate {
	public $event_db;
	private $single_event;

	public function __construct() {
		$this->event_db     = new Md\WpemsEventsModel();
		$this->single_event = new WpemsSingleEventTemplate();
	}

	private function checkEvent( $event ) {
		$post = null;
		if ( \is_numeric( $event ) && get_post_type( $event ) === 'tp_event' ) {
			$posts = $this->event_db->get_posts()->posts;
			foreach ( $posts as $value ) {
				if ( $event === $value->ID ) {
					$post = $value;
					break;
				}
			}
			// Checks if the object is of this class or has this class as one of its parents
		} elseif ( is_object( $event ) && is_a( $event, 'WP_Post' ) ) {
			$post = $event;
		}
		return $post;
	}


	public function html_single_event( $event ) {
		$event = $this->checkEvent( $event );
		?>
			<div class="listEvent">
				<!-- Left -->
				<div class="left">
					<div class="date-title">
						<div class="date_month">
							<?php echo $this->single_event->html_date( $event ); ?>
							<?php echo $this->single_event->html_month( $event ); ?>
						</div>
						<div class="title">
							<?php echo $this->single_event->html_title( $event ); ?>
							<div class="time">
								<span class="dashicons dashicons-clock"></span>
								<?php echo $this->single_event->html_time_start_end( $event ); ?>
							</div>
						</div>
					</div>
					<!-- Excerpt  -->
					<?php echo $this->single_event->html_excerpt( $event ); ?>
				</div>

				<!-- Right -->
				<div class="right">
					<?php echo $this->single_event->html_img( $event ); ?>
					<div class="totalTime">
						<?php echo $this->single_event->html_get_template_file( 'shortcodes/event-countdown.php', $event ); ?>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * To create an events list template
	 * @param array $posts
	 */
	public function html_events_list( array $posts ) {
		if ( ! isset( $posts ) || count( $posts ) === 0 ) {
			echo 'There are no events.';
		} else {
			foreach ( $posts as $key => $value ) {
				$event = $this->checkEvent( $value );

				if ( ! is_null( $event ) ) {
					$this->html_single_event( $event );
				}
			}
		}
	}
}
