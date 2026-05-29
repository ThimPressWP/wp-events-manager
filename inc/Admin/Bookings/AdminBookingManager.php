<?php
/**
 * Admin booking manager — list and detail views.
 *
 * @package WPEMS\Admin\Bookings
 */

namespace WPEMS\Admin\Bookings;

use WPEMS\Models\BookingRefundSummary;
use WPEMS\Payments\PaymentGatewayRegistry;
use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;

defined( 'ABSPATH' ) || exit;

/**
 * Top-level admin page for bookings.
 */
class AdminBookingManager {

	const PAGE_SLUG  = 'wpems-bookings';
	const CAPABILITY = 'manage_options';

	private BookingRepository $bookings;
	private PaymentTransactionRepository $txns;
	private BookingStatusService $status;
	private PaymentSyncService $sync;
	private BookingAdminActions $actions;
	private BookingMetaRepository $meta;

	/**
	 * Stored page hook suffix from add_submenu_page.
	 *
	 * @var string|null
	 */
	private ?string $page_hook = null;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository           $bookings Booking repository.
	 * @param PaymentTransactionRepository $txns     Transaction repository.
	 * @param BookingStatusService         $status   Status service.
	 * @param PaymentSyncService           $sync     Payment sync service.
	 * @param BookingAdminActions          $actions  Action handlers.
	 * @param BookingMetaRepository        $meta     Meta repository.
	 */
	public function __construct(
		BookingRepository $bookings,
		PaymentTransactionRepository $txns,
		BookingStatusService $status,
		PaymentSyncService $sync,
		BookingAdminActions $actions,
		BookingMetaRepository $meta
	) {
		$this->bookings = $bookings;
		$this->txns     = $txns;
		$this->status   = $status;
		$this->sync     = $sync;
		$this->actions  = $actions;
		$this->meta     = $meta;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Hidden CSV export endpoint (uses admin_post for binary download).
		add_action( 'admin_post_wpems_booking_export_csv', array( $this->actions, 'handle_export_csv' ) );

		// Pre-render action dispatcher.
		if ( $this->get_page_hook() ) {
			add_action( 'load-' . $this->get_page_hook(), array( $this, 'maybe_handle_action' ) );
		}
	}

	/**
	 * Get the page hook suffix.
	 *
	 * @return string|null
	 */
	public function get_page_hook(): ?string {
		return $this->page_hook;
	}

	/**
	 * Add submenu page under Events Manager.
	 *
	 * The parent menu slug is `tp-event-setting` (registered by
	 * {@see \WPEMS\Admin\Menu::admin_menu()}); do not change to `tp-event`,
	 * which would silently fail to render the submenu.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		$this->page_hook = add_submenu_page(
			'tp-event-setting',
			__( 'Bookings', 'wp-events-manager' ),
			__( 'Bookings', 'wp-events-manager' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);

		// Re-register the load- hook since we now have page_hook.
		if ( $this->page_hook ) {
			add_action( 'load-' . $this->page_hook, array( $this, 'maybe_handle_action' ) );
		}

		// Hide the legacy event_auth_book CPT submenu — the new page replaces it.
		remove_submenu_page( 'tp-event-setting', 'edit.php?post_type=event_auth_book' );
	}

	/**
	 * Enqueue admin assets for the bookings page.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'wpems-admin-bookings',
			WPEMS_ASSETS_URI . 'css/admin/bookings.css',
			array(),
			WPEMS_VER
		);

		wp_enqueue_script(
			'wpems-admin-bookings',
			WPEMS_ASSETS_URI . 'js/admin/bookings.js',
			array( 'jquery' ),
			WPEMS_VER,
			true
		);

		wp_localize_script(
			'wpems-admin-bookings',
			'WPEMS_Bookings',
			array(
				'nonces' => array(
					'mark_paid'      => wp_create_nonce( 'wpems_booking_mark_paid' ),
					'mark_cancelled' => wp_create_nonce( 'wpems_booking_mark_cancelled' ),
					'mark_failed'    => wp_create_nonce( 'wpems_booking_mark_failed' ),
					'check_status'   => wp_create_nonce( 'wpems_booking_check_status' ),
				),
			)
		);
	}

	/**
	 * Render the bookings page (list or detail).
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-events-manager' ) );
		}

		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

		if ( $booking_id > 0 ) {
			$this->render_detail( $booking_id );
		} else {
			$this->render_list();
		}
	}

	/**
	 * Render the booking list view.
	 *
	 * @return void
	 */
	protected function render_list(): void {
		$query = $this->build_query_from_request( $_GET );
		$list  = new BookingListTable( $this->bookings, $query );
		$list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings', 'wp-events-manager' ); ?></h1>
			<a href="<?php echo esc_url( $this->csv_export_url() ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export CSV', 'wp-events-manager' ); ?>
			</a>
			<hr class="wp-header-end" />

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>" />
				<?php $list->search_box( __( 'Search', 'wp-events-manager' ), 'booking' ); ?>
				<?php $list->display_filters(); ?>
			</form>

			<form method="post">
				<?php $list->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the booking detail view.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return void
	 */
	protected function render_detail( int $booking_id ): void {
		$booking = $this->bookings->find( $booking_id );
		if ( ! $booking ) {
			wp_die( esc_html__( 'Booking not found.', 'wp-events-manager' ) );
		}

		$txns     = $this->txns->find_by_booking( $booking_id );
		$refund   = $this->txns->get_refund_summary( $booking_id, $booking->get_total() );
		$gateway  = PaymentGatewayRegistry::instance()->get( $booking->get_payment_method() );
		$can_sync = $gateway && $gateway->supports( 'payment_sync' );
		$meta     = $this->meta->get_all( $booking_id );

		include __DIR__ . '/views/booking-detail.php';
	}

	/**
	 * Handle POST actions before rendering (thin dispatcher).
	 *
	 * Security, business logic, and redirect handled by BookingAdminActions.
	 *
	 * @return void
	 */
	public function maybe_handle_action(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_POST['wpems_booking_action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $_POST['wpems_booking_action'] ) );

		switch ( $action ) {
			case 'mark_paid':
				$this->actions->handle_mark_paid();
				break;
			case 'mark_cancelled':
				$this->actions->handle_mark_cancelled();
				break;
			case 'mark_failed':
				$this->actions->handle_mark_failed();
				break;
			case 'check_status':
				$this->actions->handle_check_status();
				break;
		}
	}

	/**
	 * Build a BookingQuery from request parameters.
	 *
	 * @param array $request Request array (e.g., $_GET).
	 *
	 * @return BookingQuery
	 */
	public function build_query_from_request( array $request ): BookingQuery {
		$q = new BookingQuery();

		if ( ! empty( $request['status'] ) ) {
			$q->status = sanitize_key( $request['status'] );
		}
		if ( ! empty( $request['payment_status'] ) ) {
			$q->payment_status = sanitize_key( $request['payment_status'] );
		}
		if ( ! empty( $request['payment_method'] ) ) {
			$q->payment_method = sanitize_key( $request['payment_method'] );
		}
		if ( ! empty( $request['event_id'] ) ) {
			$q->event_id = absint( $request['event_id'] );
		}
		if ( ! empty( $request['date_from'] ) ) {
			$q->date_from = sanitize_text_field( $request['date_from'] );
		}
		if ( ! empty( $request['date_to'] ) ) {
			$q->date_to = sanitize_text_field( $request['date_to'] );
		}
		if ( ! empty( $request['s'] ) ) {
			$q->search = sanitize_text_field( $request['s'] );
		}
		if ( ! empty( $request['paged'] ) ) {
			$q->offset = ( absint( $request['paged'] ) - 1 ) * $q->limit;
		}

		return $q;
	}

	/**
	 * Get the CSV export URL with nonce.
	 *
	 * @return string
	 */
	protected function csv_export_url(): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=wpems_booking_export_csv' ),
			'wpems_booking_export_csv'
		);
	}
}
