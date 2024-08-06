<?php
/**
 * Class WPEMSDatabase
 *  @package WPEventsManager/Databases
 */

namespace WPEventsManager\Databases;

defined( 'ABSPATH' ) || exit();

use Exception;
use WPEventsManager\Filters\WPEMSFilter;

class WPEMSDatabase {
	private static $_instance;
	public $wpdb, $tb_users;
	public $tb_wpems_user_items, $tb_wpems_user_itemmeta;
	public $tb_posts, $tb_postmeta, $tb_options;
	public $tb_terms, $tb_term_relationships, $tb_term_taxonomy;
	public $max_index_length = '191';

	protected function __construct() {
		/**
		 * @var wpdb $wpdb
		 */
		global $wpdb;
		$prefix = $wpdb->prefix;

		$this->wpdb                  = $wpdb;
		$this->tb_users              = $wpdb->users;
		$this->tb_posts              = $wpdb->posts;
		$this->tb_postmeta           = $wpdb->postmeta;
		$this->tb_options            = $wpdb->options;
		$this->tb_terms              = $wpdb->terms;
		$this->tb_term_relationships = $wpdb->term_relationships;
		$this->tb_term_taxonomy      = $wpdb->term_taxonomy;
		$this->wpdb->hide_errors();
		$this->set_collate();
	}

	/**
	 * Get Instance
	 *
	 * @return WPEMSDatabases
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function set_collate() {
		$collate = '';

		if ( $this->wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $this->wpdb->charset ) ) {
				$collate .= 'DEFAULT CHARACTER SET ' . $this->wpdb->charset;
			}

			if ( ! empty( $this->wpdb->collate ) ) {
				$collate .= ' COLLATE ' . $this->wpdb->collate;
			}
		}

		$this->collate = $collate;
	}

	/**
	 * Check execute current has any errors.
	 *
	 * @throws Exception
	 */
	public function check_execute_has_error() {
		if ( $this->wpdb->last_error ) {
			throw new Exception( $this->wpdb->last_error );
		}
	}

	/**
	 * Get query string single row
	 *
	 * @version 1.0.0
	 */
	public function get_query_single_row( WPEMSFilter &$filter ) {
		$filter->limit               = 1;
		$filter->return_string_query = true;
		$filter->run_query_count     = false;
	}

	/**
	 * Get result query
	 *
	 * @return array|object|null|int|string
	 * @throws Exception
	 * @author tungnx
	 * @version 1.0.0
	 */
	public function execute( WPEMSFilter $filter, int &$total_rows = 0 ) {
		$result = null;

		// Where
		$WHERE = array( 'WHERE 1=1' );

		// Fields select
		$FIELDS = '*';
		if ( ! empty( $filter->only_fields ) ) {
			$FIELDS = implode( ',', array_unique( $filter->only_fields ) );
		} elseif ( ! empty( $filter->fields ) ) {
			// exclude more fields
			if ( ! empty( $filter->exclude_fields ) ) {
				foreach ( $filter->exclude_fields as $field ) {
					$index_field = array_search( $field, $filter->fields );
					if ( $index_field ) {
						unset( $filter->fields[ $index_field ] );
					}
				}
			}
			$FIELDS = implode( ',', array_unique( $filter->fields ) );
		}
		$FIELDS = apply_filters( 'lp/query/fields', $FIELDS, $filter );

		$INNER_JOIN = array();
		$INNER_JOIN = array_merge( $INNER_JOIN, $filter->join );
		$INNER_JOIN = apply_filters( 'lp/query/inner_join', $INNER_JOIN, $filter );
		$INNER_JOIN = implode( ' ', array_unique( $INNER_JOIN ) );

		$WHERE = array_merge( $WHERE, $filter->where );
		$WHERE = apply_filters( 'lp/query/where', $WHERE, $filter );
		$WHERE = implode( ' ', array_unique( $WHERE ) );

		// Group by
		$GROUP_BY = '';
		if ( $filter->group_by ) {
			$GROUP_BY .= 'GROUP BY ' . $filter->group_by;
			$GROUP_BY  = apply_filters( 'lp/query/group_by', $GROUP_BY, $filter );
		}

		// Order by
		$ORDER_BY = '';
		if ( ! $filter->return_string_query && $filter->order_by ) {
			$filter->order = strtoupper( $filter->order );
			if ( ! in_array( $filter->order, [ 'DESC', 'ASC' ] ) ) {
				$filter->order = 'DESC';
			}

			$ORDER_BY .= 'ORDER BY ' . $filter->order_by . ' ' . $filter->order . ' ';
			$ORDER_BY  = apply_filters( 'lp/query/order_by', $ORDER_BY, $filter );
		}

		// Limit
		$LIMIT = '';
		if ( $filter->limit != - 1 ) {
			$filter->limit = absint( $filter->limit );
			/*if ( $filter->limit > $filter->max_limit ) {
				$filter->limit = $filter->max_limit;
			}*/
			$offset = $filter->limit * ( $filter->page - 1 );
			$LIMIT  = $this->wpdb->prepare( 'LIMIT %d, %d', $offset, $filter->limit );
		}

		// For nest query
		if ( $filter->return_string_query ) {
			$LIMIT = '';
		}

		// From table or group select
		$COLLECTION = '';
		if ( ! empty( $filter->collection ) ) {
			$COLLECTION = $filter->collection;
		}

		// Alias table
		$ALIAS_COLLECTION = 'p';
		if ( ! empty( $filter->collection_alias ) ) {
			$ALIAS_COLLECTION = $filter->collection_alias;
		}

		// Query
		$query = "SELECT $FIELDS FROM $COLLECTION AS $ALIAS_COLLECTION
		$INNER_JOIN
		$WHERE
		$GROUP_BY
		$ORDER_BY
		$LIMIT
		";

		if ( $filter->return_string_query ) {
			return $query;
		} elseif ( ! empty( $filter->union ) ) {
			$query  = implode( ' UNION ', array_unique( $filter->union ) );
			$query .= $GROUP_BY;
			$query .= $ORDER_BY;
			$query .= $LIMIT;
		}

		if ( ! $filter->query_count ) {
			// Debug string query
			if ( $filter->debug_string_query ) {
				return $query;
			}

			$result = $this->wpdb->get_results( $query );
		}

		// Query total rows
		if ( $filter->run_query_count ) {
			$query       = str_replace( array( $LIMIT, $ORDER_BY ), '', $query );
			$query_total = "SELECT COUNT($filter->field_count) FROM ($query) AS $ALIAS_COLLECTION";
			$total_rows  = (int) $this->wpdb->get_var( $query_total );

			// $this->check_execute_has_error();

			if ( $filter->query_count ) {
				// Debug string query
				if ( $filter->debug_string_query ) {
					return $query_total;
				}

				return $total_rows;
			}
		}

		// $this->check_execute_has_error();

		return $result;
	}

	/**
	 * Query update
	 *
	 * @throws Exception
	 * @version 1.0.0
	 */
	public function update_execute( WPEMSFilter $filter ) {

		$COLLECTION = $filter->collection;

		// SET value
		$SET = apply_filters( 'wpems/query/update/set', $filter->set, $filter );
		$SET = implode( ',', array_unique( $SET ) );

		// Where
		$WHERE = array( 'WHERE 1=1' );
		$WHERE = array_merge( $WHERE, $filter->where );
		$WHERE = apply_filters( 'wpems/query/update/where', $WHERE, $filter );
		$WHERE = implode( ' ', array_unique( $WHERE ) );

		$query = "
			UPDATE $COLLECTION
			SET $SET
			$WHERE
		";

		$result = $this->wpdb->query( $query );

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Query delete
	 *
	 * @throws Exception
	 * @version 1.0.0
	 */
	public function delete_execute( WPEMSFilter $filter, string $table = '' ) {
		$COLLECTION = $filter->collection;

		// Where
		$WHERE = array( 'WHERE 1=1' );
		$WHERE = array_merge( $WHERE, $filter->where );
		$WHERE = apply_filters( 'lp/query/delete/where', $WHERE, $filter );
		$WHERE = implode( ' ', array_unique( $WHERE ) );

		// Join
		$INNER_JOIN = array();
		$INNER_JOIN = array_merge( $INNER_JOIN, $filter->join );
		$INNER_JOIN = apply_filters( 'lp/query/delete/inner_join', $INNER_JOIN, $filter );
		$INNER_JOIN = implode( ' ', array_unique( $INNER_JOIN ) );

		$query = "
			DELETE $table FROM $COLLECTION
			$INNER_JOIN
			$WHERE
		";

		if ( $filter->return_string_query ) {
			return $query;
		}

		$result = $this->wpdb->query( $query );

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Get values of list object by key
	 *
	 * @param array $arr_object
	 * @param string $key
	 *
	 * @return array
	 */
	public static function get_values_by_key( array $arr_object, string $key = 'ID' ): array {
		$arr_object_ids = array();
		foreach ( $arr_object as $object ) {
			$arr_object_ids[] = $object->{$key};
		}

		return $arr_object_ids;
	}
}
