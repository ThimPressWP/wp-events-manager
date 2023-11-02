<?php
namespace WPEMS\TemplateHooks;
use WP_Post;
use WPEMS\Model as Model;
use WPEMS\Model\EventModel;

class SingleEventTemplate {

	/**
	 * Get the title of the event
	 *
	 * @return string HTML element
	 */
	public function html_title( EventModel $event ): string {
		$title         = isset( $event ) ? $event->post_title : '';
		$base_url      = site_url();
		$link          = $base_url . '/event-list' . '/' . $event->ID;
		$html_template = '<span class="event-title"><a href="%s">%s</a></span>';
		return sprintf( $html_template, esc_url( $link ), esc_html( ucfirst( $title ) ) );
	}

	/**
	 * Get the excerpt of the event
	 *
	 * @return string HTML element
	 */
	public function html_excerpt( EventModel $event ): string {
		$excerpt = isset( $event ) ? $event->post_excerpt : '';

		$html_template = '<div class="event-excerpt"><span>%s...</span></div>';
		return sprintf( $html_template, esc_html( ucfirst( \substr( $excerpt, 0, 170 ) ) ) );
	}

	/**
	 * Get the content of the event
	 *
	 * @param EventModel $event
	 * @return string
	 */
	public function html_content( EventModel $event ): string {
		$content = isset( $event ) ? $event->post_content : '';

		$html_template = '<div class="event-content"><span>%s...</span></div>';
		return sprintf( $html_template, $content );
	}

	/**
	 * Get the image of the event
	 *
	 * @return string HTML element
	 */
	public function html_thumbnail( EventModel $event ): string {
		$event_id = isset( $event ) ? $event->ID : '';

		$html_template = '<div class="event-image"><img src="%s" alt="Feature image"></div>';
		return  sprintf(
			$html_template,
			esc_attr( get_the_post_thumbnail_url( $event_id, 'full' ) )
		);
	}

	/**
	 * get the start date of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_date_start( EventModel $event ):string {
		$date_start = isset( $event ) ? $event->tp_event_date_start : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $date_start );
	}

	/**
	 * get the start time of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_time_start( EventModel $event ):string {
		$time_start = isset( $event ) ? $event->tp_event_time_start : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $time_start );
	}

	/**
	 * get the end date of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_date_end( EventModel $event ):string {
		$date_end = isset( $event ) ? $event->tp_event_date_end : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $date_end );
	}

	/**
	 * get the end time of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_time_end( EventModel $event ):string {
		$time_end = isset( $event ) ? $event->tp_event_time_end : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $time_end );
	}

	/**
	 * get the registration end date of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_registration_end_date( EventModel $event ):string {
		$registration_end_date = isset( $event ) ? $event->tp_event_registration_end_date : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $registration_end_date );
	}

	/**
	 * get the registration end time of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_registration_end_time( EventModel $event ):string {
		$registration_end_time = isset( $event ) ? $event->tp_event_registration_end_time : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $registration_end_time );
	}

	/**
	 * get the location on iframe of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_location_iframe( EventModel $event ):string {
		$location_iframe = isset( $event ) ? $event->tp_event_location_iframe : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $location_iframe );
	}

	/**
	 * get the map displayed via iframe of the event
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_map_by_iframe( EventModel $event ):string {
		$map_iframe = isset( $event ) ? $event->tp_event_iframe : '';

		$html_template = '<span>%s</span>';
		return sprintf( $html_template, $map_iframe );
	}

	/**
	 * get the schedules of the event
	 *
	 * @param EventModel $event
	 * @return void
	 */
	public function html_schedules( EventModel $event ) {

	}
}
