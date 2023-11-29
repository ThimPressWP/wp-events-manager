<?php
/**
 * Class EventFilter
 *
 * Filter events of WPEMS
 *
 */
namespace WPEMS\Filter\Event;

use WPEMS\Filter\PostTypeFilter;

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'EventFilter' ) ) {
	return;
}

class EventFilter extends PostTypeFilter {
	
	/**
	 * @var string
	 */
	public $post_type = WPEMS_EVENT_CPT;
}
