<?php

/**
 * Class PostTypeFilter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.1
 */

namespace WPEMS\Filter;

use WPEMS\Filter\Filter;
class EventFilter extends PostTypeFilter {

	public $post_type = WPEMS_EVENT_CPT;
}
