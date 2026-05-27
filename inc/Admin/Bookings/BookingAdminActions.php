<?php
/**
 * Booking admin action handlers.
 *
 * @package WPEMS\Admin\Bookings
 */

namespace WPEMS\Admin\Bookings;

use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles POST actions on the bookings admin page.
 *
 * Every state-mutating handler follows PRG (POST → redirect → GET).
 * CSV export streams directly to output with exit.
 */
class BookingAdminActions {

	const CAPABILITY = 'manage_options';

	/** @var BookingRepository */
	private BookingRepository $bookings;

	/** @var BookingStatusService */
	private BookingStatusService $status;

	/** @var PaymentSyncService */
	private PaymentSyncService $sync;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository   $bookings Booking repository.
	 * @param BookingStatusService $status   Status service.
	 * @param PaymentSyncService   $sync     Payment sync service.
	 */
	public function __construct(
		BookingRepository $bookings,
		BookingStatusService $status,
		PaymentSyncService $sync
	) {
		$this->bookings = $bookings;
		$this->status   = $status;
		$this->sync     = $sync;
	}

	// ─────────────────────────────────────────────
	// Security helpers
	// ─────────────────────────────────────────────

	/**
	 * Verify nonce and capability; die on failure.
	 *
	 * @param string $nonce_action Nonce action name.
	 *
	 * @return void
	 */
	protected function check_security( string $nonce_action ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die(
				esc_html__( 'Invalid nonce.', 'wp-events-manager' ),
				'',
				array( 'response' => 403 )
			);
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'Permission denied.', 'wp-events-manager' ),
				'',
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Get booking ID from POST request.
	 *
	 * @return int
	 */
	protected function get_booking_id_from_request(): int {
		return isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
	}

	/**
	 * Redirect back to booking detail with a message code.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $code       Message code.
	 * @param string $type       'success', 'error', or 'notice'.
	 *
	 * @return never
	 */
	protected function redirect_with_message( int $booking_id, string $code, string $type = 'success' ): void {
		$url = add_query_arg(
			array(
				'page'         => AdminBookingManager::PAGE_SLUG,
				'booking_id'   => $booking_id,
				'wpems_notice' => $code,
				'wpems_type'   => $type,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		wp_die( '', '', array( 'response' => 200 ) );
	}

	// ─────────────────────────────────────────────
	// Action handlers
	// ─────────────────────────────────────────────

	/**
	 * Mark a booking as paid.
	 *
	 * @return void
	 */
	public function handle_mark_paid(): void {
		$this->check_security( 'wpems_booking_mark_paid' );

		$booking_id = $this->get_booking_id_from_request();
		$booking    = $this->bookings->find( $booking_id );

		if ( ! $booking ) {
			$this->redirect_with_message( $booking_id, 'not_found', 'error' );
		}

		if ( $booking->is_paid() ) {
			$this->redirect_with_message( $booking_id, 'already_paid', 'notice' );
		}

		$user = wp_get_current_user();
		$reason = 'admin:' . ( $user ? $user->user_login : 'system' );

		$ok = $this->status->mark_completed( $booking_id, $reason );

		$this->redirect_with_message(
			$booking_id,
			$ok ? 'marked_paid' : 'transition_failed',
			$ok ? 'success' : 'error'
		);
	}

	/**
	 * Mark a booking as cancelled.
	 *
	 * @return void
	 */
	public function handle_mark_cancelled(): void {
		$this->check_security( 'wpems_booking_mark_cancelled' );

		$booking_id = $this->get_booking_id_from_request();
		$booking    = $this->bookings->find( $booking_id );

		if ( ! $booking ) {
			$this->redirect_with_message( $booking_id, 'not_found', 'error' );
		}

		$user = wp_get_current_user();
		$reason = 'admin:' . ( $user ? $user->user_login : 'system' );

		$ok = $this->status->mark_cancelled( $booking_id, $reason );

		$this->redirect_with_message(
			$booking_id,
			$ok ? 'marked_cancelled' : 'transition_failed',
			$ok ? 'success' : 'error'
		);
	}

	/**
	 * Mark a booking as failed.
	 *
	 * @return void
	 */
	public function handle_mark_failed(): void {
		$this->check_security( 'wpems_booking_mark_failed' );

		$booking_id = $this->get_booking_id_from_request();
		$booking    = $this->bookings->find( $booking_id );

		if ( ! $booking ) {
			$this->redirect_with_message( $booking_id, 'not_found', 'error' );
		}

		$user = wp_get_current_user();
		$reason = 'admin:' . ( $user ? $user->user_login : 'system' );

		$ok = $this->status->mark_failed( $booking_id, $reason );

		$this->redirect_with_message(
			$booking_id,
			$ok ? 'marked_failed' : 'transition_failed',
			$ok ? 'success' : 'error'
		);
	}

	/**
	 * Check payment status from gateway.
	 *
	 * @return void
	 */
	public function handle_check_status(): void {
		$this->check_security( 'wpems_booking_check_status' );

		$booking_id = $this->get_booking_id_from_request();

		$result = $this->sync->sync_booking( $booking_id, 'admin' );

		$code = $this->map_sync_result_code( $result );

		$this->redirect_with_message(
			$booking_id,
			$code,
			$result->is_success() ? 'success' : 'notice'
		);
	}

	/**
	 * Handle bulk mark cancelled from list table.
	 *
	 * @param int[] $booking_ids Booking IDs.
	 *
	 * @return array{success: int, failed: int}
	 */
	public function handle_bulk_mark_cancelled( array $booking_ids ): array {
		$counts = array(
			'success' => 0,
			'failed'  => 0,
		);

		$user   = wp_get_current_user();
		$reason = 'admin:bulk:' . ( $user ? $user->user_login : 'system' );

		foreach ( $booking_ids as $id ) {
			$ok = $this->status->mark_cancelled( (int) $id, $reason );
			if ( $ok ) {
				++$counts['success'];
			} else {
				++$counts['failed'];
			}
		}

		return $counts;
	}

	/**
	 * Export bookings as CSV.
	 *
	 * @return void
	 */
	public function handle_export_csv(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $nonce, 'wpems_booking_export_csv' ) ) {
			wp_die(
				esc_html__( 'Permission denied.', 'wp-events-manager' ),
				'',
				array( 'response' => 403 )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query = $this->build_csv_query( $_GET );

		$timestamp = gmdate( 'Y-m-d-His' );
		$filename  = 'wpems-bookings-' . $timestamp . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			wp_die( esc_html__( 'Could not open output stream.', 'wp-events-manager' ) );
		}

		// UTF-8 BOM for Excel compatibility.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv(
			$out,
			array(
				__( 'ID', 'wp-events-manager' ),
				__( 'Event ID', 'wp-events-manager' ),
				__( 'Event', 'wp-events-manager' ),
				__( 'User ID', 'wp-events-manager' ),
				__( 'User Email', 'wp-events-manager' ),
				__( 'Qty', 'wp-events-manager' ),
				__( 'Subtotal', 'wp-events-manager' ),
				__( 'Discount', 'wp-events-manager' ),
				__( 'Tax Total', 'wp-events-manager' ),
				__( 'Total', 'wp-events-manager' ),
				__( 'Currency', 'wp-events-manager' ),
				__( 'Status', 'wp-events-manager' ),
				__( 'Payment Status', 'wp-events-manager' ),
				__( 'Payment Method', 'wp-events-manager' ),
				__( 'Gateway Order ID', 'wp-events-manager' ),
				__( 'Created (GMT)', 'wp-events-manager' ),
			)
		);

		// Stream in chunks of 1000.
		$chunk_size = 1000;
		$offset     = 0;

		do {
			$query->limit  = $chunk_size;
			$query->offset = $offset;
			$rows          = $this->bookings->query( $query );

			foreach ( $rows as $booking ) {
				$user_id    = $booking->get_user_id();
				$user_email = '';
				if ( $user_id > 0 ) {
					$user = get_userdata( $user_id );
					if ( $user ) {
						$user_email = $user->user_email;
					}
				}

				fputcsv(
					$out,
					array(
						$booking->get_id(),
						$booking->get_event_id(),
						get_the_title( $booking->get_event_id() ) ?: '',
						$user_id,
						$user_email,
						$booking->get_qty(),
						$booking->get_subtotal(),
						$booking->get_discount_total(),
						$booking->get_tax_total(),
						$booking->get_total(),
						$booking->get_currency(),
						$booking->get_status(),
						$booking->get_payment_status(),
						$booking->get_payment_method(),
						$booking->get_gateway_order_id() ?: '',
						$booking->get_created_at_gmt(),
					)
				);
			}

			$offset += $chunk_size;
		} while ( count( $rows ) >= $chunk_size );

		fclose( $out );
		wp_die( '', '', array( 'response' => 200 ) );
	}

	// ─────────────────────────────────────────────
	// Helpers
	// ─────────────────────────────────────────────

	/**
	 * Build a BookingQuery from GET parameters for CSV export.
	 *
	 * @param array $request Request parameters (typically $_GET).
	 *
	 * @return \WPEMS\Repositories\BookingQuery
	 */
	protected function build_csv_query( array $request ): \WPEMS\Repositories\BookingQuery {
		$q = new \WPEMS\Repositories\BookingQuery();

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

		$q->order_by = 'created_at_gmt';
		$q->order    = 'DESC';

		return $q;
	}

	/**
	 * Map a PaymentResult status to a message code.
	 *
	 * @param PaymentResult $result Payment result.
	 *
	 * @return string
	 */
	protected function map_sync_result_code( PaymentResult $result ): string {
		switch ( $result->get_status() ) {
			case PaymentResult::STATUS_PAID:
				return 'sync_paid';
			case PaymentResult::STATUS_PENDING:
				return 'sync_pending';
			case PaymentResult::STATUS_FAILED:
				return 'sync_failed';
			case PaymentResult::STATUS_CANCELLED:
				return 'sync_cancelled';
			case PaymentResult::STATUS_REFUNDED:
				return 'sync_refunded';
			default:
				return 'sync_unknown';
		}
	}
}
