<?php
namespace WPEMS\TemplateHooks;
use WP_Post;
use WPEMS\Model as Model;
class SingleEventTemplate {

	/**
	 * Get the title of the event
	 *
	 * @return string HTML element
	 */
	public function html_title($event ): string {
		$title         = isset( $event ) ? $event->post_title : '';
		$base_url      = site_url();
		$link          = $base_url . '/event-list' . '/' . $event->ID;
		$html_template = '<div class="event-title"><a href="%s">%s</a></div>';
		return sprintf( $html_template, esc_url( $link ), esc_html( ucfirst( $title ) ) );
	}

	/**
	 * Get the excerpt of the event
	 *
	 * @return string HTML element
	 */
		public function html_excerpt(  $event ): string {
		$excerpt = isset( $event ) ? $event->post_excerpt : '';

		$html_template = '<div class="event-content"><span>%s...</span></div>';
		return sprintf( $html_template, esc_html( ucfirst( \substr( $excerpt, 0, 170 ) ) ) );
	}

	/**
	 * Get the date start of the event
	 *
	 * @return string HTML element
	 */
	public function html_date( $event ): string {
		$date_start = isset( $event ) ? $event->tp_event_date_start : '';

		$html_template = '<div class="event-date"><span>%s</span></div>';
		return sprintf( $html_template, esc_html( date( 'd', strtotime( $date_start ) ) ) );
	}

	/**
	 * Get the month of the event
	 *
	 * @return string HTML element
	 */
	public function html_month(  $event ): string {
		$date_start = isset( $event ) ? $event->tp_event_date_start : '';

		$html_template = '<div class="event-month"><span>%s</span></div>';
		return sprintf( $html_template, esc_html__( date_i18n( 'M', strtotime( $date_start ) ), 'wp-events-manager' ) );
	}

	/**
	 * Get the time start and time end of the event
	 *
	 * @return string HTML element
	 */
	public function html_time_start_end(  $event ): string {
		$time_start = isset( $event ) ? $event->tp_event_time_start : '';
		$time_end   = isset( $event ) ? $event->tp_event_time_end : '';

		$html_template = '<span>%s - %s</span>';
		return sprintf(
			$html_template,
			esc_html( date( 'g:i A', strtotime( $time_start ) ) ),
			esc_html( date( 'g:i A', strtotime( $time_end ) ) )
		);
	}

	/**
	 * Get the image of the event
	 *
	 * @return string HTML element
	 */
	public function html_img( $event ): string {
		$event_id = isset( $event ) ? $event->ID : '';

		$html_template = '<div class="event-image"><img src="%s" alt="Feature image"></div>';
		return  sprintf(
			$html_template,
			esc_attr( get_the_post_thumbnail_url( $event_id, 'full' ) )
		);
	}

	/**
	 * Get the template file that you want to use.
	 *
	 * @param string $templateFile_url The URL of the template file (e.g., 'shortcodes/event-countdown.php')
	 * @return string HTML element
	 */
	public function html_get_template_file( string $templateFile_url, $event ): string {
		$event_id = isset( $event ) ? $event->ID : '';

		$html_template = '<div class="event-get-template"><span class="time-remaining">%s</span></div>';
		ob_start();
		wpems_get_template( $templateFile_url, [ 'event_id' => $event_id ] );
		$template_content = ob_get_clean();

		return  sprintf( $html_template, $template_content );
	}
}
