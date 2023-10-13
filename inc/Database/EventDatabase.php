<?php
namespace WPEMS\Database;

use Exception;
use WPEMS\Filter\Filter;
use WPEMS\Helper\Utils;

class EventDatabase extends Database {
	private static $instance;

	/**
	 * Get a single instance of the EventDatabase class
	 *
	 * @return EventDatabase
	 */
	public static function getInstance(): EventDatabase {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get events from the database based on the filter
	 *
	 * @param Filter $filter
	 * @param int $total_rows
	 * @return array|int|object|string|null
	 * @throws Exception
	 */
	public function get_events( Filter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'e';
		}

		// Where
		$filter->where[] = $this->wpdb->prepare( 'AND e.post_type = %s', $filter->post_type );

		// Status
		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = Utils::db_format_array( $filter->post_status, '%s' );
			$filter->where[]    = $this->wpdb->prepare( 'AND e.post_status IN (' . $post_status_format . ')', $filter->post_status );
		}

		// Has term ids and tag ids
		if ( ! empty( $filter->term_ids ) && ! empty( $filter->tag_ids ) ) {
			$term_ids_format = Utils::db_format_array( $filter->term_ids, '%d' );
			$tag_ids_format  = Utils::db_format_array( $filter->tag_ids, '%d' );

			$filter->join[] = "INNER JOIN $this->tb_term_relationships AS r_term ON e.ID = r_term.object_id";
			
			// Get all course ids by term ids
			$filter_course_ids_by_term                      = new LP_Course_Filter();
			$filter_course_ids_by_term->only_fields         = array( 'ID' );
			$filter_course_ids_by_term->join[]              = "INNER JOIN $this->tb_term_relationships AS r_term ON e.ID = r_term.object_id";
			$filter_course_ids_by_term->where[]             = $this->wpdb->prepare( 'AND r_term.term_taxonomy_id IN (' . $term_ids_format . ')', $filter->term_ids );
			$filter_course_ids_by_term->return_string_query = true;
			$course_ids_by_term                             = LP_Course_DB::getInstance()->get_courses( $filter_course_ids_by_term );

			// Get all course ids by tag ids
			$filter->where[] = $this->wpdb->prepare( 'AND r_term.term_taxonomy_id IN (' . $tag_ids_format . ')', $filter->tag_ids );
			$filter->where[] = 'AND e.ID IN(' . $course_ids_by_term . ')';
		} else {
			// Term ids
			if ( ! empty( $filter->term_ids ) ) {
				$filter->join[] = "INNER JOIN $this->tb_term_relationships AS r_term ON e.ID = r_term.object_id";

				$term_ids_format = Utils::db_format_array( $filter->term_ids, '%d' );
				$filter->where[] = $this->wpdb->prepare( 'AND r_term.term_taxonomy_id IN (' . $term_ids_format . ')', $filter->term_ids );
			}

			// Tag ids
			if ( ! empty( $filter->tag_ids ) ) {
				$filter->join[] = "INNER JOIN $this->tb_term_relationships AS r_term ON e.ID = r_term.object_id";
				
				$tag_ids_format  = Utils::db_format_array( $filter->tag_ids, '%d' );
				$filter->where[] = $this->wpdb->prepare( 'AND r_term.term_taxonomy_id IN (' . $tag_ids_format . ')', $filter->tag_ids );
			}
		}

		// event ids
		if ( ! empty( $filter->post_ids ) ) {
			$list_ids_format = Utils::db_format_array( $filter->post_ids, '%d' );
			$filter->where[] = $this->wpdb->prepare( 'AND e.ID IN (' . $list_ids_format . ')', $filter->post_ids );
		}

		// Title
		if ( $filter->post_title ) {
			$filter->where[] = $this->wpdb->prepare( 'AND p.post_title LIKE %s', '%' . $filter->post_title . '%' );
		}

		// Slug
		if ( $filter->post_name ) {
			$filter->where[] = $this->wpdb->prepare( 'AND p.post_name = %s', $filter->post_name );
		}

		// Author
		if ( $filter->post_author ) {
			$filter->where[] = $this->wpdb->prepare( 'AND p.post_author = %d', $filter->post_author );
		}

		// Authors
		if ( ! empty( $filter->post_authors ) ) {
			$post_authors_format = Utils::db_format_array( $filter->post_authors, '%d' );
			$filter->where[]     = $this->wpdb->prepare( 'AND p.post_author IN (' . $post_authors_format . ')', $filter->post_authors );
		}

		$filter = apply_filters( 'lp/course/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
