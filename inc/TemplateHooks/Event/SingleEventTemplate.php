<?php

namespace WPEMS\TemplateHooks\Event;

use WPEMS\Helpers\Singleton;
use WPEMS\Helpers\Template;
use WPEMS\Models\Event\EventModel;
use WPEMS\TemplateHooks\EventHelper\ThumbnailHelper;

class SingleEventTemplate {
    use Singleton;
	public function init(){
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
		$thumbnailHelper = ThumbnailHelper::instance();
		$content = '';

		try {
			$html_wrapper = [
				'<div class="event-img">' => '</div>',
			];

			$content = $thumbnailHelper->get_event_image( $eventModel->ID, $size, $attr, $eventModel->post_title );
			$content = Template::instance()->nest_elements( $html_wrapper, $content );

		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
		
	}

	/**
	 * get any postmeta you want just by change the $key_meta
	 *
	 * @param EventModel $event
	 * @param string $key_meta
	 * @return string HTML element
	 */
	public function html_postmeta( EventModel $eventModel, $key_meta ):string {
		$class_name = str_replace(['tp_', '_'], ['', '-'], strtolower($key_meta));

		$html_wrapper = [
			"<span class=\"$class_name\">" => '</span>',
		];

		$eventMetaValue 	 = $eventModel->get_meta_value_by_key( $key_meta );

		return Template::instance()->nest_elements( $html_wrapper, $eventMetaValue );

	}

	/**
	 * get taxonomy(categories or tags) from database
	 *
	 * @param EventModel $event
	 * @param string $taxonomy
	 * @return string HTML element
	 */
	public function html_taxonomy( EventModel $eventModel, $taxonomy ):string {
		$class_name = str_replace(['tp_', '_'], ['', '-'], strtolower($taxonomy));

		$html_wrapper 		= [
			"<span class=\"$class_name\">" => '</span>',
		];

		$eventTaxonomy		= get_the_terms( $eventModel->ID, $taxonomy );

		if ($eventTaxonomy&& !is_wp_error($eventCategories)) {

			$Taxonomy_names = array();

			foreach ($eventTaxonomy as $Taxonomy) {
				$term_link = get_term_link( $Taxonomy->term_id, $taxonomy_name );
            	$Taxonomy_names[] = sprintf( '<a href="%s">%s</a>', esc_url( $term_link ), esc_html( $Taxonomy->name ) );
			}
		} 

		$content = implode( ', ', $Taxonomy_names );

		return Template::instance()->nest_elements( $html_wrapper, $content );

	}
	
}