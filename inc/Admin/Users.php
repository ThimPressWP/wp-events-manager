<?php
/**
 * Admin users list table.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP Events Manager admin users table.
 */
class Users extends \WP_List_Table {

	/**
	 * Table items.
	 *
	 * @var array|null
	 */
	public $items = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'user', 'wp-events-manager' ),
				'plural'   => __( 'users', 'wp-events-manager' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Total users count for pagination.
	 *
	 * @var int
	 */
	public $total_users = 0;

	/**
	 * Load event users with database pagination and sorting.
	 *
	 * @param int $per_page     Items per page.
	 * @param int $current_page Current page number.
	 *
	 * @return array
	 */
	public function load_users( $per_page = 10, $current_page = 1 ) {
		global $wpdb;

		if ( ! current_user_can( 'list_users' ) ) {
			return array();
		}

		$user_id_filter = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;

		$allowed_orderby = array(
			'id'            => 'user.ID',
			'user_login'    => 'user.user_login',
			'user_nicename' => 'user.user_nicename',
			'user_email'    => 'user.user_email',
		);
		$orderby_key = ( ! empty( $_GET['orderby'] ) ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$orderby     = isset( $allowed_orderby[ $orderby_key ] ) ? $allowed_orderby[ $orderby_key ] : 'user.user_login';

		$order = ( ! empty( $_GET['order'] ) ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'ASC';
		$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

		$statuses   = array( 'ea-cancelled', 'ea-pending', 'ea-processing', 'ea-completed' );
		$user_where = $user_id_filter ? $wpdb->prepare( 'AND CAST(pm.meta_value AS UNSIGNED) = %d', $user_id_filter ) : '';

		$booked_users_sql = $wpdb->prepare(
			"
				SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS user_id
				FROM {$wpdb->posts} AS book
				INNER JOIN {$wpdb->postmeta} AS pm ON book.ID = pm.post_id
				WHERE
					pm.meta_key = %s
					AND book.post_type = %s
					AND book.post_status IN (%s,%s,%s,%s)
					{$user_where}
			",
			'ea_booking_user_id',
			'event_auth_book',
			$statuses[0],
			$statuses[1],
			$statuses[2],
			$statuses[3]
		);

		$this->total_users = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->users} AS user INNER JOIN ({$booked_users_sql}) AS booked_users ON user.ID = booked_users.user_id"
		);

		if ( 0 === $this->total_users ) {
			return array();
		}

		$per_page     = max( 1, absint( $per_page ) );
		$current_page = max( 1, absint( $current_page ) );
		$offset       = ( $current_page - 1 ) * $per_page;

		$query = $wpdb->prepare(
			"
				SELECT user.* FROM {$wpdb->users} AS user
				INNER JOIN ({$booked_users_sql}) AS booked_users ON user.ID = booked_users.user_id
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d
			",
			$per_page,
			$offset
		);

		$users = $wpdb->get_results( $query );

		$results = array();

		if ( $users ) {
			foreach ( $users as $user ) {
				// $approve = is_super_admin( $user->ID ) || get_user_meta( $user->ID, 'ea_user_approved', true ) ;

				$booking_url = admin_url( 'edit.php?post_type=event_auth_book&user_id=' . absint( $user->ID ) );
				$results[]   = array(
					'ID'            => $user->ID,
					'user_login'    => sprintf( '<a href="%s">%s</a>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->user_login ) ),
					'user_nicename' => $user->user_nicename,
					'user_email'    => $user->user_email,
					'bookings'      => sprintf( '<a href="%s">%s</a>', esc_url( $booking_url ), esc_html__( 'View', 'wp-events-manager' ) ),
					// 'approved'       => (boolean) $approve
				);
			}
		}

		return $results;
	}

	/**
	 * Render empty table message.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No users found.', 'wp-events-manager' );
	}

	/**
	 * Render default column.
	 *
	 * @param array  $item   Row item.
	 * @param string $column Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		switch ( $column ) {
			case 'ID':
			case 'user_login':
				return wp_kses_post( $item[ $column ] );
				break;
			case 'user_nicename':
			case 'user_email':
				return esc_html( $item[ $column ] );
				break;
			case 'bookings':
				return wp_kses_post( $item[ $column ] );
				break;
			default:
				return esc_html( print_r( $item, true ) );
				break;
		}
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'user_login' => array( 'user_login', false ),
		);
		return $sortable;
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'user_login'    => __( 'Username', 'wp-events-manager' ),
			'user_nicename' => __( 'Name', 'wp-events-manager' ),
			'user_email'    => __( 'Email', 'wp-events-manager' ),
			'bookings'      => __( 'Event Booking', 'wp-events-manager' ),
		);
		return $columns;
	}

	/**
	 * Sort row data.
	 *
	 * @param array $a First item.
	 * @param array $b Second item.
	 *
	 * @return int
	 */
	public function sort_data( $a, $b ) {
		$allowed_orderby = array( 'ID', 'user_login', 'user_nicename', 'user_email', 'bookings' );
		$orderby         = ( ! empty( $_GET['orderby'] ) ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$orderby         = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'user_login';

		$order = ( ! empty( $_GET['order'] ) ) ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'asc';
		$order = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'asc';

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( $order === 'asc' ) ? $result : - $result;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		return array(
			// 'approve'        => __( 'Approve', 'wp-events-manager' ),
			// 'unapprove'      => __( 'Unapprove', 'wp-events-manager' )
		);
	}

	/**
	 * Process bulk action.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		return;
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' === $request_method ) {
			if ( ! current_user_can( 'promote_users' ) ) {
				return;
			}

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			if ( ! isset( $_POST['action'] ) || ! $_POST['action'] || ! isset( $_POST['users'] ) || empty( $_POST['users'] ) ) {
				return;
			}

			$action = sanitize_key( wp_unslash( $_POST['action'] ) );
			$users  = array_map( 'absint', (array) wp_unslash( $_POST['users'] ) );

			foreach ( $users as $user ) {
				if ( $action === 'approve' || is_super_admin( $user ) ) {
					update_user_meta( $user, 'ea_user_approved', true );
				} elseif ( $action === 'unapprove' ) {
					delete_user_meta( $user, 'ea_user_approved' );
				}
			}
		} else {
			$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
			if ( 'tp-event-users' !== $page ) {
				return;
			}

			if ( ! current_user_can( 'promote_users' ) ) {
				return;
			}

			$nonce = isset( $_REQUEST['event_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['event_nonce'] ) ) : '';
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'event_auth_user_action' ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['action'] ) || ! $_REQUEST['action'] || ! isset( $_REQUEST['user_id'] ) || ! $_REQUEST['user_id'] ) {
				return;
			}

			$action  = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
			$user_id = absint( wp_unslash( $_REQUEST['user_id'] ) );

			if ( $action === 'approve' ) {
				update_user_meta( $user_id, 'ea_user_approved', true );
			} elseif ( $action === 'unapprove' ) {
				delete_user_meta( $user_id, 'ea_user_approved' );
			}
		}
	}

	/**
	 * Render user login column.
	 *
	 * @param array $item Row item.
	 *
	 * @return string
	 */
	public function column_user_login( $item ) {
		// $status = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
		// $status = wp_nonce_url( $status, 'event_auth_user_action', 'event_nonce' );
		$actions = array();
		if ( isset( $item['approved'] ) && ! $item['approved'] ) {
			// $status_name = __( 'Approve', 'wp-events-manager' );
			// $status = add_query_arg( array(
			// 'action'    => 'approve',
			// 'user_id'   => $item['ID']
			// ), $status );
			// $actions['edit'] = sprintf( __( '<a href="%s">%s</a>' ), $status, $status_name );
		} else {
			// $status_name = __( 'Unapprove', 'wp-events-manager' );
			// $status = add_query_arg( array(
			// 'action'    => 'unapprove',
			// 'user_id'   => $item['ID']
			// ), $status );
			// $actions['spam'] = sprintf( __( '<a href="%s">%s</a>' ), $status, $status_name );
		}

		return sprintf( '%1$s %2$s', $item['user_login'], $this->row_actions( $actions ) );
	}

	/**
	 * Render checkbox column.
	 *
	 * @param array $item Row item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="users[]" value="%s" />',
			esc_attr( absint( $item['ID'] ) )
		);
	}

	/**
	 * Prepare table items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// process bulk action
		$this->process_bulk_action();

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page    = 10;
		$this->items = $this->load_users( $per_page, $this->get_pagenum() );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_users,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Output users screen.
	 *
	 * @return void
	 */
	public static function output() {
		if ( ! current_user_can( 'list_users' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-events-manager' ) );
		}

		$user_table = new self();
		?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Event Users', 'wp-events-manager' ); ?></h2>

			<?php $user_table->prepare_items(); ?>
			<form method="post">
				<?php $user_table->display(); ?>
			</form>

		</div>
		<?php
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Users', false ) ) {
	\class_alias( Users::class, 'WPEMS_Admin_Users' );
}
