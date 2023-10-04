<?php
namespace WPEMS\Template;

use WPEMS\Model\WpemsEventModel;
use DateTime;

class WpemsEventTemplate {
	private $model;

	public function __construct( $event_id ) {
		$this->model = new WpemsEventModel( $event_id );
	}

	public function displayEventTitle( $event_id ) {
		$title = $this->model->title;

		$html = '<div class="entry-title">';
		if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
			$html .= '<h4><a href="' . get_permalink( $event_id ) . '">';
		} else {
			$html .= '<h3>';
		}

		$html .= $title;

		if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
			$html .= '</a></h4>';
		} else {
			$html .= '</h1>';
		}

		$html .= '</div>';

		echo $html;
	}

	public function displayEventThumbnail( $event_id ) {
		$thumbnail = $this->model->thumbnail;

		$html = '';
		if ( has_post_thumbnail( $event_id ) ) {
			$html .= '<div class="entry-thumbnail">';

			if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
				$html .= '<a href="' . get_permalink( $event_id ) . '">';
			}

			$html .= get_the_post_thumbnail();

			if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
				$html .= '</a>';
			}

			$html .= '</div>';
		}

		echo $html;
	}

	public function displayEventContent( $event_id ) {
		// $content = $this->model->getEventContent( $event_id );
		echo '<div class="entry-content">';
		$content = the_content();
		echo '</div>';
	}

	public function displayEventInformation( $event_id ) {
		$start_time        = $this->model->startTime;
		$start_date        = $this->model->startDate;
		$end_time          = $this->model->endTime;
		$end_date          = $this->model->endDate;
		$register_end_time = $this->model->registerEndTime;
		$register_end_date = $this->model->registerEndDate;
		$location_f        = $this->model->locationIframe;

		$html = <<<HTML
        <div class="entry-information">
            <table>
                <tr>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-clock"></span>
                            <h6>Start Time</h6>
                        </div>
                        <p class="content">$start_time - $end_time</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-flag"></span>
                            <h6>End Time</h6>
                        </div>
                        <p class="content">$end_time - $end_date</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-location"></span>
                            <h6>Location</h6>
                        </div>
                        <p class="content">$location_f</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-hourglass"></span>
                            <h6>Registration End Date</h6>
                        </div>
                        <p class="content">$register_end_time - $register_end_date</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-category"></span>
                            <h6>Category</h6>
                        </div>
                        <p class="content">content</p>
                    </td>
                    <td>
                        <div class="title">
                            <span class="dashicons dashicons-editor-ul"></span>
                            <h6>Type</h6>
                        </div>
                        <p class="content">content</p>
                    </td>
                </tr>
            </table>
        </div>
        HTML;

		echo $html;
	}

	public function displayEventCountdown( $event_id ) {
		$current_time = current_time( 'Y-m-d H:i' );
		$time         = wpems_get_time( 'Y-m-d H:i', null, false );

		$html = '<div class="entry-countdown">';

		if ( $time > $current_time ) {
			$date  = new DateTime( date( 'Y-m-d H:i', strtotime( $time ) ) );
			$html .= '<div class="tp_event_counter" data-time="' . esc_attr( $date->format( 'M j, Y H:i:s O' ) ) . '"></div>';
		} else {
			$html .= '<p class="tp-event-notice error">' . esc_html__( 'This event has expired', 'wp-events-manager' ) . '</p>';
		}

		$html .= '</div>';

		echo $html;
	}

	public function displayEventIframe( $event_id ) {
		$iframe = $this->model->iframe;

		$html = '';
		if ( ! empty( $iframe ) ) {
			$html .= '<div class="entry-location">';
			$html .= '<h6>Location</h6>';
			$html .= $iframe;
			$html .= '</div>';
		}

		echo $html;
	}

	public function displayEventSchedules( $event_id ) {
		$schedules     = $this->model->schedules;
		$schedules_arr = json_decode( $schedules, true );

		$html  = '<div class="entry-schedule">';
		$html .= '<h6 class="schedule_header">Schedule</h6>';

		foreach ( $schedules_arr as $key => $value ) {
			$html .= '<div class="schedule_body" id="' . $key . '">';
			$html .= '<div class="schedule_body-header">';
			$html .= '<p class="schedule_title">';
			$html .= $value['title'];
			$html .= '</p>';
			$html .= '<div class="schedule_button">';
			$html .= '<span class="dashicons-before dashicons-minus"></span>';
			$html .= '<span class="dashicons-before dashicons-plus"></span>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '<div class="schedule_body-content">';
			$html .= '<p>' . $value['description'] . '</p>';
			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '</div>';

		echo $html;
	}
}