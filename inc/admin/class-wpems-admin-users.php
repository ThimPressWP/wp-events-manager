<?php
/**
 * WP Events Manager Admin Users class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPEMS_Admin_Users extends WP_List_Table {

	public $items = null;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'user', 'wp-events-manager' ),
				'plural'   => __( 'users', 'wp-events-manager' ),
				'ajax'     => false,
			)
		);
	}

	public function load_users() {
		global $wpdb;
		if ( isset( $_GET['user_id'] ) && $_GET['user_id'] ) {
			$query = $wpdb->prepare(
				"
					SELECT user.* FROM $wpdb->users AS user
					LEFT JOIN $wpdb->postmeta AS pm ON user.ID = pm.meta_value
					LEFT JOIN $wpdb->posts AS book ON pm.post_id = book.ID
					WHERE
						pm.meta_key = %s
						AND book.post_type = %s
						AND book.post_status IN (%s,%s,%s,%s)
						AND user.ID = %d
					GROUP BY user.ID
				",
				'ea_booking_user_id',
				'event_auth_book',
				'ea-cancelled',
				'ea-pending',
				'ea-processing',
				'ea-completed',
				absint( $_GET['user_id'] )
			);
		} else {
			$query = $wpdb->prepare(
				"
					SELECT user.* FROM $wpdb->users AS user
					LEFT JOIN $wpdb->postmeta AS pm ON user.ID = pm.meta_value
					LEFT JOIN $wpdb->posts AS book ON pm.post_id = book.ID
					WHERE
						pm.meta_key = %s
						AND book.post_type = %s
						AND book.post_status IN (%s,%s,%s,%s)
					GROUP BY user.ID
				",
				'ea_booking_user_id',
				'event_auth_book',
				'ea-cancelled',
				'ea-pending',
				'ea-processing',
				'ea-completed'
			);
		}

		$users = $wpdb->get_results( $query );

		$results = array();

		if ( $users ) {
			foreach ( $users as $user ) {
				// $approve = is_super_admin( $user->ID ) || get_user_meta( $user->ID, 'ea_user_approved', true ) ;

				$booking_url = admin_url() . 'edit.php?post_type=event_auth_book&user_id=' . $user->ID;
				$results[]   = array(
					'ID'            => $user->ID,
					'user_login'    => sprintf( '<a href="%s">%s</a>', get_edit_user_link( $user->ID ), $user->user_login ),
					'user_nicename' => $user->user_nicename,
					'user_email'    => $user->user_email,
					'bookings'      => sprintf( '<a href="%s">%s</a>', $booking_url, __( 'View', 'wp-events-manager' ) ),
					// 'approved'		=> (boolean) $approve
				);
			}
		}

		return $results;
	}

	// $this->items is empty
	public function no_items() {
		_e( 'No users found.', 'wp-events-manager' );
	}

	// default columns
	public function column_default( $item, $column ) {
		switch ( $column ) {
			case 'ID':
			case 'user_login':
			case 'user_nicename':
			case 'user_email':
				return $item[ $column ];
				break;
			case 'bookings':
				return $item[ $column ];
				break;
			default:
				return print_r( $item, true );
				break;
		}
	}

	// sort columns
	public function get_sortable_columns() {
		$sortable = array(
			'user_login' => array( 'user_login', false ),
		);
		return $sortable;
	}

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

	public function sort_data( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'user_login';

		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( $order === 'asc' ) ? $result : - $result;
	}

	// bulk action
	public function get_bulk_actions() {

		return array(
			// 'approve'    	=> __( 'Approve', 'wp-events-manager' ),
			// 'unapprove'    	=> __( 'Unapprove', 'wp-events-manager' )
		);
	}

	// process bulk action
	public function process_bulk_action() {
		return;
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( ! isset( $_POST['action'] ) || ! $_POST['action'] || ! isset( $_POST['users'] ) || empty( $_POST['users'] ) ) {
				return;
			}

			$action = sanitize_text_field( $_POST['action'] );
			$users  = absint( $_POST['users'] );

			foreach ( $users as $user ) {
				$status = get_user_meta( $user, 'ea_user_approved', true );
				if ( $action === 'approve' || is_super_admin( $user ) ) {
					update_user_meta( $user, 'ea_user_approved', true );
				} elseif ( $action === 'unapprove' ) {
					delete_user_meta( $user, 'ea_user_approved' );
				}
			}
		} else {
			if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'tp-event-users' ) {
				return;
			}

			if ( ! isset( $_REQUEST['event_nonce'] ) || ! wp_verify_nonce( $_REQUEST['event_nonce'], 'event_auth_user_action' ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['action'] ) || ! $_REQUEST['action'] || ! isset( $_REQUEST['user_id'] ) || ! $_REQUEST['user_id'] ) {
				return;
			}

			$action  = sanitize_text_field( $_REQUEST['action'] );
			$user_id = absint( sanitize_text_field( $_REQUEST['user_id'] ) );

			if ( $action === 'approve' ) {
				update_user_meta( $user_id, 'ea_user_approved', true );
			} elseif ( $action === 'unapprove' ) {
				delete_user_meta( $user_id, 'ea_user_approved' );
			}
		}
	}

	public function column_user_login( $item ) {
		// $status = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
		// $status = wp_nonce_url( $status, 'event_auth_user_action', 'event_nonce' );
		$actions = array();
		if ( isset( $item['approved'] ) && ! $item['approved'] ) {
			// $status_name = __( 'Approve', 'wp-events-manager' );
			// $status = add_query_arg( array(
			// 			'action' 	=> 'approve',
			// 			'user_id' 	=> $item['ID']
			// 		), $status );
			// $actions['edit'] = sprintf( __( '<a href="%s">%s</a>' ), $status, $status_name );
		} else {
			// $status_name = __( 'Unapprove', 'wp-events-manager' );
			// $status = add_query_arg( array(
			// 		'action' 	=> 'unapprove',
			// 		'user_id' 	=> $item['ID']
			// 	), $status );
			// $actions['spam'] = sprintf( __( '<a href="%s">%s</a>' ), $status, $status_name );
		}

		return sprintf( '%1$s %2$s', $item['user_login'], $this->row_actions( $actions ) );
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="users[]" value="%s" />',
			$item['ID']
		);
	}

	public function prepare_items() {
		// process bulk action
		$this->process_bulk_action();

		// load items
		$this->items = $this->load_users();

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		usort( $this->items, array( $this, 'sort_data' ) );

		$per_page    = 10;
		$total_items = count( $this->items );
		// pagination
		if ( $total_items > $per_page ) {
			$this->items = array_slice( $this->items, ( $this->get_pagenum() - 1 ) * $per_page, $per_page );
		}
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->items;
	}

	public static function output() {
		$user_table = new WPEMS_Admin_Users();
		?>
		<div class="wrap">

			<h2><?php _e( 'Event Users', 'wp-events-manager' ); ?></h2>

			<?php $user_table->prepare_items(); ?>
			<form method="post">
				<?php $user_table->display(); ?>
			</form>

		</div>
		<?php
	}


}
