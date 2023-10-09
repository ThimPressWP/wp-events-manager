<?php
namespace WPEMS\Model;

use WPEMS\Database\EventDatabase;

class EventModel {
	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public int $ID;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @var string
	 */
	public int $post_author = 0;

	/**
	 * The post's local publication time.
	 *
	 * @var string
	 */
	public string $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @var string
	 */
	public string $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's content.
	 *
	 * @var string
	 */
	public string $post_content = '';

	/**
	 * The post's title.
	 *
	 * @var string
	 */
	public string $post_title = '';

	/**
	 * The post's excerpt.
	 *
	 * @var string
	 */
	public string $post_excerpt = '';

	/**
	 * The post's status.
	 *
	 * @var string
	 */
	public string $post_status = 'publish';

	/**
	 * Whether comments are allowed.
	 *
	 * @var string
	 */
	public string $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 *
	 * @var string
	 */
	public string $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 *
	 * @var string
	 */
	public string $post_password = '';

	/**
	 * The post's slug.
	 *
	 * @var string
	 */
	public string $post_name = '';

	/**
	 * URLs queued to be pinged.
	 *
	 * @var string
	 */
	public string $to_ping = '';

	/**
	 * URLs that have been pinged.
	 *
	 * @var string
	 */
	public string $pinged = '';

	/**
	 * The post's local modified time.
	 *
	 * @var string
	 */
	public string $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @var string
	 */
	public string $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 *
	 * @var string
	 */
	public string $post_content_filtered = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @var int
	 */
	public int $post_parent = 0;

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 *
	 * @var string
	 */
	public string $guid = '';

	/**
	 * A field used for ordering posts.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public int $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public string $post_type = 'post';

	/**
	 * An attachment's mime type.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public string $post_mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public int $comment_count = 0;

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public string $filter;

	/**
	 * Retrieve EventModel instance.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $post_id Post ID.
	 * @return EventModel|false|mixed Post object, false otherwise.
	 */
	public static function get_instance( int $event_id ): EventModel {
		global $wpdb;

		if ( $event_id <= 0 ) {
			return false;
		}

		$event_db   = EventDatabase::get_instance();
		$event_data = $event_db->get_event_data( $event_id );

		if ( ! $event_data ) {
			return false;
		}

		// Get data from wp_postmeta
		$post_meta = get_post_meta( $event_id );
		
		if ( is_array($post_meta) ) {
			// Assign values from wp_postmeta to $event_data
			foreach ( $post_meta as $meta_key => $meta_value ) {
				$event_data->{$meta_key} = $meta_value[0];
			}

			$event_data = sanitize_post( $event_data, 'raw' );

			return new EventModel( $event_data );
		}
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
}
