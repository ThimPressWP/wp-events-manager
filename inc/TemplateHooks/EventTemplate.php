<?php
namespace WPEMS\TemplateHooks;

use WPEMS\Model\EventModel;
use DateTime;

class EventTemplate {
	public $event;

	public function __construct( EventModel $eventModel ) {
		$this->event = $eventModel;
	}

	public function displayEventTitle() {
		$title = $this->event->post_title;

		$html = '<div class="entry-title">';
		if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
			$html .= '<h4><a href="' . get_permalink( $this->event->ID ) . '">';
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

	public function displayEventThumbnail() {

		$html = '';
		if ( has_post_thumbnail( $this->event->ID ) ) {
			$html .= '<div class="entry-thumbnail">';

			if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
				$html .= '<a href="' . get_permalink( $this->event->ID ) . '">';
			}

			$html .= get_the_post_thumbnail();

			if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) {
				$html .= '</a>';
			}

			$html .= '</div>';
		}

		echo $html;
	}

	public function displayEventContent() {
		echo '<div class="entry-content">';
		$content = the_content();
		echo '</div>';
	}

	public function displayEventInformation() {
		$start_time        = $this->event->tp_event_time_start;
		$start_date        = $this->event->tp_event_date_start;
		$end_time          = $this->event->tp_event_time_end;
		$end_date          = $this->event->tp_event_date_end;
		$register_end_time = $this->event->tp_event_registration_end_time;
		$register_end_date = $this->event->tp_event_registration_end_date;
		$location_f        = $this->event->tp_event_location_iframe;

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

	public function displayEventCountdown() {
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

	public function displayEventIframe() {
		$iframe = $this->event->tp_event_iframe;
		
		$html = '';
		if ( ! empty( $iframe ) ) {
			$html .= '<div class="entry-location">';
			$html .= '<h6>Location</h6>';
			$html .= $iframe;
			$html .= '</div>';
		}

		echo $html;
	}

	public function displayEventSchedules() {
		$schedules     = $this->event->tp_event_schedules;
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
