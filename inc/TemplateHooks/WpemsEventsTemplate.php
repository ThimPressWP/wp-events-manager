<?php

namespace WPEMS\Templates;

use WPEMS\Model as Md;
use WP_Post;

interface EventTemplate {
	public function html_single_event( object $event );
	public function html_events_list( array $posts );
}

class WpemsEventsTemplate implements EventTemplate {
	public $eventModel;
	public $singleEventTemp;

	public function __construct() {
		$this->eventModel      = Md\WpemsEventsModel::getInstance();
		$this->singleEventTemp = new WpemsSingleEventTemplate();
	}

	/**
	 * To create a single event template
	 * @param  object $event  will check by checkEvent method( get a single event or events list) to get the data
	 */
	public function html_single_event( object $event ) {
		$event = $this->eventModel->checkEvent( $event );
		?>
			<div class="listEvent">
				<!-- Left -->
				<div class="left">
					<div class="date-title">
						<div class="date_month">
							<?php echo $this->singleEventTemp->html_date( $event ); ?>
							<?php echo $this->singleEventTemp->html_month( $event ); ?>
						</div>
						<div class="title">
							<?php echo $this->singleEventTemp->html_title( $event ); ?>
							<div class="time">
								<span class="dashicons dashicons-clock"></span>
								<?php echo $this->singleEventTemp->html_time_start_end( $event ); ?>
							</div>
						</div>
					</div>
					<!-- Excerpt  -->
					<?php echo $this->singleEventTemp->html_excerpt( $event ); ?>
				</div>

				<!-- Right -->
				<div class="right">
					<?php echo $this->singleEventTemp->html_img( $event ); ?>
					<div class="totalTime">
						<?php echo $this->singleEventTemp->html_get_template_file( 'shortcodes/event-countdown.php', $event ); ?>
					</div>
					<div class="read-more"><span> Read More</span> <span class="dashicons dashicons-arrow-right-alt2"></span></div>
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
				$event = $this->eventModel->checkEvent( $value );
				if ( ! is_null( $event ) ) {
					$this->html_single_event( $event );
				}
			}
		}
	}
}
