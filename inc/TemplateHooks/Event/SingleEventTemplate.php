<?php

namespace WPEMS\TemplateHooks\Event;

use WPEMS\Helpers\Singleton;
use WPEMS\Helpers\Template;
use WPEMS\Models\Event\EventModel;
use WPEMS\TemplateHooks\EventHelper\ThumbnailHelper;
use WPEMS\Models\Event\Meta\EventMetaConstants;

class SingleEventTemplate {
	use Singleton;

	public function init() {

	}

    /**
	 * Get the content of the event
	 *
	 * @param EventModel $event
	 * @return string
	 */
	public function html_content( EventModel $eventModel ): string {
		$html_wrapper = [
			'<span class="event-content">' => '</span>',
		];
        
		return Template::instance()->nest_elements( $html_wrapper, $eventModel->post_content);

	}

    /**
	 * Get display title event.
	 *
	 * @return string 
	 */
	public function html_title( EventModel $eventModel ): string {
		$html_wrapper = [
			'<span class="event-title">' => '</span>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $eventModel->post_title);

	}

    /**
	 * Get the excerpt of the event
	 *
	 * @return string HTML element
	 */
	public function html_excerpt( EventModel $eventModel ): string {
        $html_wrapper = [
			'<span class="event-excerpt">' => '</span>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $eventModel->post_excerpt );

	}

	/**
	 * Get the image of the event
	 *
	 * @param EventModel $event
	 * @param string $size
	 * @param array $attr
	 * @return string HTML element
	 */
	public function html_image( EventModel $eventModel, $size = '', $attr = array() ): string {
		$thumbnailHelper 	= ThumbnailHelper::instance();
		$content 			= '';

		try {
			$html_wrapper 	= [
				'<div class="event-image">' => '</div>',
			];

			$content 		= $thumbnailHelper->get_event_image( $eventModel->ID, $size, $attr, $eventModel->post_title );
			$content 		= Template::instance()->nest_elements( $html_wrapper, $content );

		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
		
	}

	/**
	 * get quantity from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_quantity( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_QTY;

		$html_wrapper 		= [
			'<div class="event-quantity">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get price from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_price( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_PRICE;

		$html_wrapper 		= [
			'<div class="event-price">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get date_start from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_date_start( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_DATE_START;

		$html_wrapper 		= [
			'<div class="event-date-start">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get date_end from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_date_end( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_DATE_END;

		$html_wrapper 		= [
			'<div class="event-date-end">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get time_start from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_time_start( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_TIME_START;

		$html_wrapper 		= [
			'<div class="event-time-start">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get time_end from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_time_end( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_TIME_END;

		$html_wrapper 		= [
			'<div class="event-time-end">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get registration_end_date from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_registration_end_date( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_REGISTRATION_END_DATE;

		$html_wrapper 		= [
			'<div class="event-registration-end-date">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get registration_end_time from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_registration_end_time( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_REGISTRATION_END_TIME;

		$html_wrapper 		= [
			'<div class="event-registration-end-time">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get location from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_location( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_LOCATION;

		$html_wrapper 		= [
			'<div class="event-location">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get iframe from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_iframe( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_IFRAME;

		$html_wrapper 		= [
			'<div class="event-iframe">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get status from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_status( EventModel $eventModel ):string {
		$key_meta = EventMetaConstants::TP_EVENT_STATUS;

		$html_wrapper 		= [
			'<div class="event-status">' => '</div>',
		];

		$eventMetaValue 	= $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get categories from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_categories( EventModel $eventModel ):string {
		$taxonomy = WPEMS_EVENT_CATEGORY;

		$html_wrapper = [
			'<span class="event-categories">' => '</span>',
		];

		$eventTaxonomy				= get_the_terms( $eventModel->ID, $taxonomy );

		if ($eventTaxonomy&& !is_wp_error($eventTaxonomy)) {

			$taxonomyNames 			= array();

			foreach ($eventTaxonomy as $Taxonomy) {
				$term_link 			= get_term_link( $Taxonomy->term_id, $taxonomy );
            	$taxonomyNames[] 	= sprintf( '<a href="%s">%s</a>', esc_url( $term_link ), esc_html( $Taxonomy->name ) );
			}
		} 

		$content = implode( ', ', $taxonomyNames );

		return Template::instance()->nest_elements( $html_wrapper, $content );

	}

	/**
	 * get tags from database
	 *
	 * @param EventModel $event
	 * @return string HTML element
	 */
	public function html_tags( EventModel $eventModel ):string {
		$taxonomy = WPEMS_EVENT_TAG;

		$html_wrapper = [
			'<span class="event-tags">' => '</span>',
		];

		$eventTaxonomy				= get_the_terms( $eventModel->ID, $taxonomy );

		if ($eventTaxonomy&& !is_wp_error($eventTaxonomy)) {

			$taxonomyNames 			= array();

			foreach ($eventTaxonomy as $Taxonomy) {
				$term_link 			= get_term_link( $Taxonomy->term_id, $taxonomy );
            	$taxonomyNames[] 	= sprintf( '<a href="%s">%s</a>', esc_url( $term_link ), esc_html( $Taxonomy->name ) );
			}
		} 

		$content = implode( ', ', $taxonomyNames );

		return Template::instance()->nest_elements( $html_wrapper, $content );

	}

}