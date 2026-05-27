<?php
/**
 * Booking list table for the admin bookings page.
 *
 * @package WPEMS\Admin\Bookings
 */

namespace WPEMS\Admin\Bookings;

use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;

defined( 'ABSPATH' ) || exit;

// WP_List_Table may not be loaded in all contexts (e.g., front-end AJAX or unit tests).
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Booking list table for the admin bookings page.
 *
 * @extends \WP_List_Table
 */
class BookingListTable extends \WP_List_Table {

	/**
	 * Allowed orderby columns (mirrors BookingRepository::ORDER_BY_WHITELIST).
	 *
	 * @var string[]
	 */
	private const ORDERBY_WHITELIST = array( 'id', 'created_at_gmt', 'total', 'status', 'event_id' );

	/**
	 * Allowed sort orders.
	 *
	 * @var string[]
	 */
	private const ORDER_WHITELIST = array( 'ASC', 'DESC' );

	/**
	 * Booking repository.
	 *
	 * @var BookingRepository
	 */
	private BookingRepository $bookings;

	/**
	 * Query filter object.
	 *
	 * @var BookingQuery
	 */
	private BookingQuery $query;

	/**
	 * Construct.
	 *
	 * @param BookingRepository $bookings Booking repository.
	 * @param BookingQuery      $query    Query filter.
	 */
	public function __construct( BookingRepository $bookings, BookingQuery $query ) {
		parent::__construct(
			array(
				'singular' => 'booking',
				'plural'   => 'bookings',
				'ajax'     => false,
			)
		);

		$this->bookings = $bookings;
		$this->query    = $query;
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'       => '<input type="checkbox" />',
			'id'       => __( '#', 'wp-events-manager' ),
			'event'    => __( 'Event', 'wp-events-manager' ),
			'customer' => __( 'Customer', 'wp-events-manager' ),
			'qty'      => __( 'Qty', 'wp-events-manager' ),
			'total'    => __( 'Total', 'wp-events-manager' ),
			'status'   => __( 'Status', 'wp-events-manager' ),
			'payment'  => __( 'Payment', 'wp-events-manager' ),
			'method'   => __( 'Method', 'wp-events-manager' ),
			'date'     => __( 'Date', 'wp-events-manager' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'id'    => array( 'id', false ),
			'total' => array( 'total', false ),
			'date'  => array( 'created_at_gmt', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'mark_cancelled' => __( 'Mark as cancelled', 'wp-events-manager' ),
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		// Apply sorting from request (clamped to allow-lists).
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order   = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : '';

		if ( $orderby && in_array( $orderby, self::ORDERBY_WHITELIST, true ) ) {
			$this->query->order_by = $orderby;
		}
		if ( $order && in_array( $order, self::ORDER_WHITELIST, true ) ) {
			$this->query->order = $order;
		}

		$per_page     = $this->get_items_per_page( 'bookings_per_page', 20 );
		$current_page = $this->get_pagenum();

		$this->query->limit  = $per_page;
		$this->query->offset = ( $current_page - 1 ) * $per_page;

		$total_items = $this->bookings->count( $this->query );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->bookings->query( $this->query );
	}

	/**
	 * Display filter controls above the table.
	 *
	 * @return void
	 */
	public function display_filters(): void {
		$statuses         = BookingTableModel::ALLOWED_STATUSES;
		$payment_statuses = BookingTableModel::ALLOWED_PAYMENT_STATUSES;
		$methods          = array( 'paypal', 'stripe', 'manual', 'check', 'offline' );

		$current_status         = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$current_payment_status = isset( $_GET['payment_status'] ) ? sanitize_key( wp_unslash( $_GET['payment_status'] ) ) : '';
		$current_method         = isset( $_GET['payment_method'] ) ? sanitize_key( wp_unslash( $_GET['payment_method'] ) ) : '';
		$current_event          = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$current_date_from      = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$current_date_to        = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		?>
		<div class="wpems-booking-filters" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin-bottom:12px;">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'wp-events-manager' ); ?></option>
				<?php foreach ( $statuses as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $current_status, $s ); ?>>
						<?php echo esc_html( ucfirst( str_replace( 'ea-', '', $s ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="payment_status">
				<option value=""><?php esc_html_e( 'All payments', 'wp-events-manager' ); ?></option>
				<?php foreach ( $payment_statuses as $ps ) : ?>
					<option value="<?php echo esc_attr( $ps ); ?>" <?php selected( $current_payment_status, $ps ); ?>>
						<?php echo esc_html( ucfirst( $ps ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="payment_method">
				<option value=""><?php esc_html_e( 'All methods', 'wp-events-manager' ); ?></option>
				<?php foreach ( $methods as $m ) : ?>
					<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $current_method, $m ); ?>>
						<?php echo esc_html( ucfirst( $m ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input
				type="number"
				name="event_id"
				value="<?php echo $current_event ? esc_attr( (string) $current_event ) : ''; ?>"
				placeholder="<?php esc_attr_e( 'Event ID', 'wp-events-manager' ); ?>"
				style="width:100px;"
			/>

			<input
				type="date"
				name="date_from"
				value="<?php echo esc_attr( $current_date_from ); ?>"
				placeholder="<?php esc_attr_e( 'From', 'wp-events-manager' ); ?>"
			/>

			<input
				type="date"
				name="date_to"
				value="<?php echo esc_attr( $current_date_to ); ?>"
				placeholder="<?php esc_attr_e( 'To', 'wp-events-manager' ); ?>"
			/>

			<button type="submit" class="button">
				<?php esc_html_e( 'Filter', 'wp-events-manager' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render default column (fallback).
	 *
	 * @param mixed  $item        Booking row.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return '';
	}

	/**
	 * Render checkbox column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="booking[]" value="%d" />',
			$item->get_id()
		);
	}

	/**
	 * Render ID column with detail link.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_id( $item ): string {
		$url = add_query_arg(
			array(
				'page'       => AdminBookingManager::PAGE_SLUG,
				'booking_id' => $item->get_id(),
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<a href="%s">#%d</a>',
			esc_url( $url ),
			$item->get_id()
		);
	}

	/**
	 * Render event column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_event( $item ): string {
		$title = get_the_title( $item->get_event_id() );
		if ( ! $title ) {
			$title = '—';
		}

		$link = get_edit_post_link( $item->get_event_id() );
		if ( $link ) {
			return sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $title ) );
		}

		return esc_html( $title );
	}

	/**
	 * Render customer column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_customer( $item ): string {
		$user_id = $item->get_user_id();
		if ( $user_id <= 0 ) {
			return esc_html__( 'Guest', 'wp-events-manager' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return esc_html__( 'Guest', 'wp-events-manager' );
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_user_link( $user->ID ) ),
			esc_html( $user->display_name . ' (' . $user->user_email . ')' )
		);
	}

	/**
	 * Render quantity column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_qty( $item ): string {
		return esc_html( (string) $item->get_qty() );
	}

	/**
	 * Render total column with currency formatting.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_total( $item ): string {
		return esc_html(
			$item->get_currency() . ' ' . number_format_i18n( (float) $item->get_total(), 2 )
		);
	}

	/**
	 * Render status column as coloured badge.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_status( $item ): string {
		$labels = array(
			'ea-pending'    => __( 'Pending', 'wp-events-manager' ),
			'ea-processing' => __( 'Processing', 'wp-events-manager' ),
			'ea-completed'  => __( 'Completed', 'wp-events-manager' ),
			'ea-cancelled'  => __( 'Cancelled', 'wp-events-manager' ),
			'ea-failed'     => __( 'Failed', 'wp-events-manager' ),
			'ea-expired'    => __( 'Expired', 'wp-events-manager' ),
			'ea-refunded'   => __( 'Refunded', 'wp-events-manager' ),
		);

		$status = $item->get_status();

		return sprintf(
			'<span class="wpems-status-badge wpems-status-%s">%s</span>',
			esc_attr( $status ),
			esc_html( $labels[ $status ] ?? $status )
		);
	}

	/**
	 * Render payment status column as badge.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_payment( $item ): string {
		$labels = array(
			'unpaid'    => __( 'Unpaid', 'wp-events-manager' ),
			'pending'   => __( 'Pending', 'wp-events-manager' ),
			'paid'      => __( 'Paid', 'wp-events-manager' ),
			'failed'    => __( 'Failed', 'wp-events-manager' ),
			'cancelled' => __( 'Cancelled', 'wp-events-manager' ),
			'refunded'  => __( 'Refunded', 'wp-events-manager' ),
		);

		$status = $item->get_payment_status();

		return sprintf(
			'<span class="wpems-payment-badge wpems-payment-%s">%s</span>',
			esc_attr( $status ),
			esc_html( $labels[ $status ] ?? $status )
		);
	}

	/**
	 * Render payment method column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_method( $item ): string {
		$method = $item->get_payment_method();
		if ( ! $method ) {
			return '—';
		}

		$label = ucfirst( $method );

		if ( $item->get_payment_mode() ) {
			$label .= ' (' . $item->get_payment_mode() . ')';
		}

		return esc_html( $label );
	}

	/**
	 * Render date column.
	 *
	 * @param BookingTableModel $item Booking.
	 *
	 * @return string
	 */
	public function column_date( $item ): string {
		$gmt = $item->get_created_at_gmt();
		if ( ! $gmt ) {
			return '—';
		}

		$local = get_date_from_gmt( $gmt, 'Y-m-d H:i' );

		return esc_html( $local );
	}

	/**
	 * Message when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No bookings found.', 'wp-events-manager' );
	}
}
