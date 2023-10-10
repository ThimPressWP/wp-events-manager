<?php

namespace WPEMS\TemplateHooks;
use WPEMS\Model as Model;
use WP_Post;
use WP_Query;
use Exception;
class EventsTemplate {
	public $event = null;
	public $posts = null;

	public $singleEventTemp;
	public function __construct() {
		$this->singleEventTemp = new SingleEventTemplate();
	}

	/**
	 * To create a single event template
	 * @param WP_Post  $event  will check by checkEvent method( get a single event or events list) to get the data
	 */
	public function html_single_event( $event ) {
		?>
			<div class="listEvent">
				<!-- Left -->
				<div class="left">
					<div class="date-title">
						<div class="date_month">
							<?php echo $this->singleEventTemp->html_date( $event); ?>
							<?php echo $this->singleEventTemp->html_month( $event); ?>
						</div>
						<div class="title">
							<?php echo $this->singleEventTemp->html_title( $event ); ?>
							<div class="time">
								<span class="dashicons dashicons-clock"></span>
								<?php echo $this->singleEventTemp->html_time_start_end( $event); ?>
							</div>
						</div>
					</div>
					<!-- Excerpt  -->
					<?php echo $this->singleEventTemp->html_excerpt( $event); ?>
				</div>

				<!-- Right -->
				<div class="right">
					<?php echo $this->singleEventTemp->html_img( $event); ?>
					<div class="totalTime">
						<?php echo $this->singleEventTemp->html_get_template_file( 'shortcodes/event-countdown.php', $event); ?>
					</div>
					<div class="read-more"><span> Read More</span> <span class="dashicons dashicons-arrow-right-alt2"></span></div>
				</div>
			</div>
		<?php
	}

	/**
	 * To create an events list template
	 * @param WP_Query  $posts
	 */
	public function html_events_list( WP_Query $query_object ) {
		if ( ! isset( $query_object ) || count( $query_object->posts ) === 0 ) {
			echo 'There are no events.';
		} else {
			try {
				if ( is_array( $query_object->posts ) && count( $query_object->posts ) > 0 ) {
					$this->posts = $query_object->posts;
					foreach ( $this->posts as $key => $value ) {
						$this->event = Model\EventModel::get_instance($value->ID);

						$this->html_single_event($this->event);
					}
				}
			} catch ( Exception $e ) {
				echo 'There is a problem: ' . $e->getMessage();
			}
		}
	}
}
