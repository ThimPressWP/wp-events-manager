<?php
/**
 * Class Database
 *
 * @since 3.0.0
 * @version 1.0.0
 */

namespace WPEMS\Database;

use Exception;
use wpdb;
use WPEMS\Filter\Filter;

class Database {
	private static $_instance;
	public $wpdb, $tb_users;
	public $tb_posts, $tb_postmeta, $tb_options;
	public $tb_terms, $tb_term_relationships, $tb_term_taxonomy;
	private $collate         = '';
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
	 * @return Database
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

	public function get_collate(): string {
		return $this->collate;
	}

	/**
	 * Check table exists.
	 *
	 * @param string $name_table
	 *
	 * @return bool|int
	 */
	public function check_table_exists( string $name_table ) {
		return $this->wpdb->query( $this->wpdb->prepare( "SHOW TABLES LIKE '%s'", $name_table ) );
	}

	/**
	 * Clone table
	 *
	 * @param string $name_table .
	 *
	 * @throws Exception
	 */
	public function clone_table( string $name_table ):bool {
		if ( ! current_user_can( ADMIN_ROLE ) ) {
			throw new Exception( 'You don\'t have permission' );
		}

		$table_bk = $name_table . '_bk';

		// Drop table bk if exists.
		$this->drop_table( $table_bk );

		// Clone table
		$this->wpdb->query( "CREATE TABLE $table_bk LIKE $name_table" );
		$this->wpdb->query( "INSERT INTO $table_bk SELECT * FROM $name_table" );

		/*dbDelta(
			"CREATE TABLE $table_bk LIKE $name_table;
			INSERT INTO $table_bk SELECT * FROM $name_table;"
		);*/

		$this->check_execute_has_error();

		return true;
	}

	/**
	 * Check column table
	 *
	 * @param string $name_table .
	 * @param string $name_col .
	 *
	 * @return bool|int
	 */
	public function check_col_table( string $name_table = '', string $name_col = '' ) {
		$query = $this->wpdb->prepare( "SHOW COLUMNS FROM $name_table LIKE '%s'", $name_col );

		return $this->wpdb->query( $query );
	}

	/**
	 * Drop Index of Table
	 *
	 * @param string $name_table .
	 *
	 * @return void
	 * @throws Exception
	 */
	public function drop_indexs_table( string $name_table ) {
		$show_index = "SHOW INDEX FROM $name_table";
		$indexs     = $this->wpdb->get_results( $show_index );

		foreach ( $indexs as $index ) {
			if ( 'PRIMARY' === $index->Key_name || '1' !== $index->Seq_in_index ) {
				continue;
			}

			$query = "ALTER TABLE $name_table DROP INDEX $index->Key_name";

			$this->wpdb->query( $query );
			$this->check_execute_has_error();
		}
	}

	/**
	 * Add Index of Table
	 *
	 * @param string $name_table .
	 * @param array  $indexs.
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function add_indexs_table( string $name_table, array $indexs ) {
		$add_index    = '';
		$count_indexs = count( $indexs ) - 1;

		// Drop indexs .
		$this->drop_indexs_table( $name_table );

		foreach ( $indexs as $index ) {
			if ( $count_indexs === array_search( $index, $indexs ) ) {
				$add_index .= ' ADD INDEX ' . $index . ' (' . $index . ')';
			} else {
				$add_index .= ' ADD INDEX ' . $index . ' (' . $index . '),';
			}
		}

		$execute = $this->wpdb->query(
			"ALTER TABLE $name_table
			$add_index"
		);

		$this->check_execute_has_error();

		return $execute;
	}

	/**
	 * Get list columns name of table
	 *
	 * @param string $name_table
	 *
	 * @return array
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.1.6
	 * @author tungnx
	 */
	public function get_cols_of_table( string $name_table ): array {
		$query = "SHOW COLUMNS FROM $name_table";

		$result = $this->wpdb->get_col( $query );

		$this->check_execute_has_error();

		return $result;
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
	 * Check key postmeta exist on Database
	 *
	 * @param int $post_id
	 * @param string $key
	 *
	 * @return bool|int
	 */
	public function check_key_postmeta_exists( int $post_id = 0, string $key = '' ) {
		return $this->wpdb->query(
			$this->wpdb->prepare(
				"
				SELECT meta_id FROM $this->tb_postmeta
				WHERE meta_key = %s
				AND post_id = %d
				",
				$key,
				$post_id
			)
		);
	}

	/**
	 * Get total pages
	 *
	 * @param int $limit
	 * @param int $total_rows
	 *
	 * @return int
	 */
	public static function get_total_pages( int $limit = 0, int $total_rows = 0 ): int {
		if ( $limit == 0 ) {
			return 0;
		}

		$total_pages = floor( $total_rows / $limit );
		if ( $total_rows % $limit !== 0 ) {
			$total_pages++;
		}

		return (int) $total_pages;
	}

	/**
	 * Get query string single row
	 *
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function get_query_single_row( Filter &$filter ) {
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
	 * @since 4.1.6
	 */
	public function execute( Filter $filter, int &$total_rows = 0 ) {
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
				foreach ( $filter->exclude_fields as  $field ) {
					$index_field = array_search( $field, $filter->fields );
					if ( $index_field ) {
						unset( $filter->fields[ $index_field ] );
					}
				}
			}
			$FIELDS = implode( ',', array_unique( $filter->fields ) );
		}

		$INNER_JOIN = array();
		$INNER_JOIN = array_merge( $INNER_JOIN, $filter->join );
		$INNER_JOIN = implode( ' ', array_unique( $INNER_JOIN ) );

		$WHERE = array_merge( $WHERE, $filter->where );
		$WHERE = implode( ' ', array_unique( $WHERE ) );

		// Group by
		$GROUP_BY = '';
		if ( $filter->group_by ) {
			$GROUP_BY .= 'GROUP BY ' . $filter->group_by;
		}

		// Order by
		$ORDER_BY = '';
		if ( ! $filter->return_string_query && $filter->order_by ) {
			$filter->order = strtoupper( $filter->order );
			if ( ! in_array( $filter->order, [ 'DESC', 'ASC' ] ) ) {
				$filter->order = 'DESC';
			}

			$ORDER_BY .= 'ORDER BY ' . $filter->order_by . ' ' . $filter->order . ' ';
		}

		// Limit
		$LIMIT = '';
		if ( $filter->limit != -1 ) {
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
		$ALIAS_COLLECTION = 'X';
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

			$this->check_execute_has_error();

			if ( $filter->query_count ) {
				// Debug string query
				if ( $filter->debug_string_query ) {
					return $query_total;
				}

				return $total_rows;
			}
		}

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Query update
	 *
	 * @throws Exception
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function update_execute( Filter $filter ) {

		$COLLECTION = $filter->collection;

		// SET value
		$SET = implode( ',', array_unique( $filter->set ) );

		// Where
		$WHERE = array( 'WHERE 1=1' );
		$WHERE = array_merge( $WHERE, $filter->where );
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
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function delete_execute( Filter $filter ) {
		$COLLECTION = $filter->collection;

		// Where
		$WHERE = array( 'WHERE 1=1' );
		$WHERE = array_merge( $WHERE, $filter->where );
		$WHERE = implode( ' ', array_unique( $WHERE ) );

		$query = "
			DELETE FROM $COLLECTION
			$WHERE
		";

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
