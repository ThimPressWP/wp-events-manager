<?php

namespace WPEMS\Models\Event;

use stdClass;
use Throwable;
use WPEMS\Databases\Event\EventDatabase;
use WPEMS\Databases\Event\Meta\EventMetaDatabase;
use WPEMS\Models\Event\Meta\EventMetaModel;
use WPEMS\Filter\Filter;
use WPEMS\Filter\Event\Meta\EventMetaFilter;

class EventModel {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $ID;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @var string
	 */
	public $post_author = 0;

	/**
	 * The post's local publication time.
	 *
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's content.
	 *
	 * @var string
	 */
	public $post_content = '';

	/**
	 * The post's title.
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 * The post's excerpt.
	 *
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * The post's status.
	 *
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Whether comments are allowed.
	 *
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 *
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 *
	 * @var string
	 */
	public $post_password = '';

	/**
	 * The post's slug.
	 *
	 * @var string
	 */
	public $post_name = '';

	/**
	 * URLs queued to be pinged.
	 *
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * URLs that have been pinged.
	 *
	 * @var string
	 */
	public $pinged = '';

	/**
	 * The post's local modified time.
	 *
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 *
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 *
	 * @var string
	 */
	public $guid = '';

	/**
	 * A field used for ordering posts.
	 *
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * An attachment's mime type.
	 *
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @var string
	 */
	public $comment_count = 0;

	/**
	 * Undocumented variable
	 *
	 * @var object { meta_key: {meta_id, event_id, meta_key, meta_value}}
	 */
	public $meta_data;

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}
	
	/**
	 * Map array, object data to EventModel.
	 * Use for data get from database.
	 *
	 * @param  array|object|mixed $data
	 * @return EventModel
	 */
	public function map_to_object( $data ): EventModel {
		// Get data from database: wp_posts
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get event data from database by filter( post_status, post_id).
	 * If not exists, return false.
	 * If exists, return EventModel.
	 *
	 * @param Filter $filter
	 * @param bool $no_cache
	 * @return EventModel|false
	 */
	public static function get_event_data_from_db( Filter $filter, bool $no_cache = true ) {
		$event_db    = EventDatabase::getInstance();
		$event_model = false;

		try {
			$event_db->get_query_single_row( $filter );
			$query_single_row = $event_db->get_events( $filter );
			$events_rs        = $event_db->wpdb->get_row( $query_single_row );

			if ( $events_rs instanceof stdClass ) {
				$event_model = new self( $events_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $event_model;
	}

	/**
	 * Get metadata from object meta_data or database: wp_postmeta by key.
	 *
	 * @param string $key, int $post_id
	 * @return false|EventMetaModel
	 */
	public function get_meta_data_by_key( string $key ) {
		$event_metadata = false;

		// Check object meta_data has value of key.
		if ( $this->meta_data instanceof stdClass
			&& property_exists( $this->meta_data, $key ) ) {

			$event_metadata = $this->meta_data->{$key};

		} else { // Get from DB
			$filter             = new EventMetaFilter();
			$filter->meta_key   = $key;
			$filter->post_id    = $this->ID;
			$event_metadata 	= EventMetaModel::get_meta_value_from_db( $filter );
		}

		return $event_metadata;
	}

	/**
	 * Get meta_value from database: wp_postmeta by key
	 *
	 * @param string $key
	 * @return false|string
	 */
	public function get_meta_value_by_key( string $key ) {
		$data 						 = false;
		$event_metadata 			 = $this->get_meta_data_by_key( $key );
	
		if ( $event_metadata instanceof EventMetaModel ) {
			if ( ! $this->meta_data instanceof stdClass ) {
				$this->meta_data 	 = new stdClass();
			}

			$this->meta_data->{$key} = $event_metadata;
		}

		$data = $event_metadata->meta_value;

		return $data;
	}
}
