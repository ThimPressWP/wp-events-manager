<?php
namespace WPEMS\Model;

use WPEMS\Database\EventDatabase;

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
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $filter;

	/**
	 * Retrieve EventModel instance.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $post_id Post ID.
	 * @return EventModel|false Post object, false otherwise.
	 */
	public static function get_instance( $event_id ) {
		$event_db   = EventDatabase::get_instance();
		$event_data = $event_db->get_event_data( $event_id );

		if ( ! $event_data ) {
			return false;
		}

		return new EventModel( $event_data );
	}

	/**
	 * Constructor.
	 *
	 * @param EventModel|object $event Event object.
	 */
	public function __construct( $event ) {
		foreach ( get_object_vars( $event ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function get_event_by_id( $event_id ) {
		global $wpdb;

		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return false;
		}

		$event_db   = EventDatabase::get_instance();
		$event_data = $event_db->get_event_data( $event_id );

		if ( ! $event_data ) {
			return false;
		}

		// Get data from wp_postmeta
		$post_meta = get_post_meta( $event_id );

		// Assign values from wp_postmeta to $event_data
		foreach ( $post_meta as $meta_key => $meta_value ) {
			$event_data->{$meta_key} = $meta_value[0];
		}

		$event_data = sanitize_post( $event_data, 'raw' );
		// var_dump($event_data);die;
		return $event_data;
	}
}
