<?php
namespace WPEMS\Database;

class WpemsEventDatabase {
	public $ID;
	public $filter;

	public static function get_instance( $post_id ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return false;
		}

		$_post = wp_cache_get( $post_id, 'posts' );

		if ( ! $_post ) {
			$_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );

			if ( ! $_post ) {
				return false;
			}

			// get data from wp_postmeta
			$post_meta = get_post_meta( $post_id );

			// assign values from wp_postmeta to $_post
			foreach ( $post_meta as $meta_key => $meta_value ) {
				$_post->{$meta_key} = $meta_value[0];
			}

			$_post = sanitize_post( $_post, 'raw' );
			wp_cache_add( $_post->ID, $_post, 'posts' );
		} elseif ( empty( $_post->filter ) || 'raw' !== $_post->filter ) {
			$_post = sanitize_post( $_post, 'raw' );
		}

		return $_post;
	}
}
