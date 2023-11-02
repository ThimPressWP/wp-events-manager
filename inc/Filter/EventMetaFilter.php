<?php
namespace WPEMS\Filter;
use WPEMS\Filter\Filter;

class EventMetaFilter extends Filter {
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		'meta_id',
		'post_id',
		'meta_key',
		'meta_value',
	];
	/**
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var int foreign key
	 */
	public $post_id = 0;
	/**
	 * @var string meta key (VARCHAR 255)
	 */
	public $meta_key = '';
	/**
	 * @var string meta value (VARCHAR 255)
	 */
	public $meta_value = '';
}
