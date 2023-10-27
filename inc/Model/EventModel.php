<?php
namespace WPEMS\Model;

use stdClass;
use Throwable;
use WPEMS\Database\EventDatabase;
use WPEMS\Filter\Filter;

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
	 * @since 3.5.0
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * An attachment's mime type.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
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
	 * Map array, object data to UserItemModel.
	 * Use for data get from database.
	 *
	 * @param  array|object|mixed $data
	 * @return EventModel
	 */
	public function map_to_object( $data ): EventModel {
		// Get data from wp_posts
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		// Get data from wp_postmeta
		// $meta_data = get_post_meta( $data->ID );
		// foreach ( $meta_data as $meta_key => $meta_value ) {
		// 	$this->{$meta_key} = $meta_value[0];
		// }

		$this->meta_data = new EventMetaModel();

		$meta_key                                 = 'tp_event_iframe';
		$this->meta_data->{$meta_key}             = new EventMetaModel();
		$this->meta_data->{$meta_key}->meta_id    = 1;
		$this->meta_data->{$meta_key}->event_id   = $this->ID;
		$this->meta_data->{$meta_key}->meta_key   = $meta_key;
		$this->meta_data->{$meta_key}->meta_value = '';

		return $this;
	}

	/**
	 * Get event from database by post_id.
	 * If not exists, return false.
	 * If exists, return EventModel.
	 *
	 * @param Filter $filter
	 * @param bool $no_cache
	 * @return EventModel|false
	 */
	public static function get_event_model_from_db( Filter $filter, bool $no_cache = false ) {
		$lp_user_item_db = EventDatabase::getInstance();
		$event_model     = false;

		try {
			$lp_user_item_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_item_db->get_events( $filter );
			$events_rs        = $lp_user_item_db->wpdb->get_row( $query_single_row );
			if ( $events_rs instanceof stdClass ) {
				$event_model = new self( $events_rs );
				echo '<pre>';
				print_r( $event_model );
				echo '</pre>';
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $event_model;
	}
}
