<?php

namespace WPEMS\Filter\Event\Meta;

use WPEMS\Filter\PostTypeFilter;

class EventMetaFilter extends PostTypeFilter {
	const COL_META_ID    = 'meta_id';
	const COL_POST_ID    = 'post_id';
	const COL_META_KEY   = 'meta_key';
	const COL_META_VALUE = 'meta_value';
	
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = array(
		self::COL_META_ID,
		self::COL_POST_ID,
		self::COL_META_KEY,
		self::COL_META_VALUE,
	);

	/**
	 * @var int
	 */
	public $meta_id = 0;

	/**
	 * @var int foreign key, join to table Posts
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

	/**
	 * @var string column count.
	 */
	public $field_count = self::COL_META_ID;
}
