<?php
/**
 * Legacy booking CPT to custom bookings-table migrator.
 *
 * @package WPEMS\Migrations
 * @since   3.0.0
 */

namespace WPEMS\Migrations;

defined( 'ABSPATH' ) || exit;

use Throwable;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Tables\TableNames;

/**
 * Migrates legacy event_auth_book posts into wpems_bookings rows.
 */
class BookingMigrator {

	/**
	 * Legacy booking post type.
	 */
	private const LEGACY_POST_TYPE = 'event_auth_book';

	/**
	 * Maximum batch size allowed in one request.
	 */
	private const MAX_BATCH_SIZE = 1000;

	/**
	 * Booking repository.
	 *
	 * @var BookingRepository
	 */
	private BookingRepository $bookings;

	/**
	 * Payment transaction repository.
	 *
	 * @var PaymentTransactionRepository
	 */
	private PaymentTransactionRepository $txns;

	/**
	 * Event inventory repository.
	 *
	 * @var EventInventoryRepository
	 */
	private EventInventoryRepository $inventory;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository            $bookings  Booking repository.
	 * @param PaymentTransactionRepository $txns      Payment transaction repository.
	 * @param EventInventoryRepository     $inventory Event inventory repository.
	 */
	public function __construct(
		BookingRepository $bookings,
		PaymentTransactionRepository $txns,
		EventInventoryRepository $inventory
	) {
		$this->bookings  = $bookings;
		$this->txns      = $txns;
		$this->inventory = $inventory;
	}

	/**
	 * Count legacy booking posts that do not have a custom-table row yet.
	 *
	 * @return int
	 */
	public function count_pending(): int {
		global $wpdb;

		$posts_table    = $this->posts_table();
		$bookings_table = TableNames::bookings();

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$posts_table} p
			WHERE p.post_type = %s
				AND NOT EXISTS (
					SELECT 1 FROM {$bookings_table} b WHERE b.legacy_post_id = p.ID
				)",
			self::LEGACY_POST_TYPE
		);

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Run one idempotent migration batch.
	 *
	 * @param int $batch_size Requested batch size.
	 *
	 * @return BatchResult
	 */
	public function run_batch( int $batch_size = 200 ): BatchResult {
		global $wpdb;

		$batch_size = max( 1, min( self::MAX_BATCH_SIZE, $batch_size ) );

		$posts_table    = $this->posts_table();
		$bookings_table = TableNames::bookings();
		$sql            = $wpdb->prepare(
			"SELECT p.ID FROM {$posts_table} p
			WHERE p.post_type = %s
				AND NOT EXISTS (
					SELECT 1 FROM {$bookings_table} b WHERE b.legacy_post_id = p.ID
				)
			ORDER BY p.ID ASC
			LIMIT %d",
			self::LEGACY_POST_TYPE,
			$batch_size
		);

		$ids    = (array) $wpdb->get_col( $sql );
		$result = new BatchResult();

		foreach ( $ids as $id ) {
			$post = get_post( (int) $id );

			if ( ! $post instanceof \WP_Post ) {
				++$result->skipped;
				continue;
			}

			try {
				$data       = $this->map_legacy_post( $post );
				$booking_id = $this->bookings->upsert_from_legacy( $data );
				$this->maybe_insert_legacy_transaction( $booking_id, $post );
				++$result->imported;
			} catch ( Throwable $e ) {
				++$result->failed;
				$result->errors[ (int) $post->ID ] = $e->getMessage();
			}
		}

		$result->remaining = $this->count_pending();

		return $result;
	}

	/**
	 * Verify migrated booking counts and totals against legacy posts.
	 *
	 * @return VerifyReport
	 */
	public function verify(): VerifyReport {
		global $wpdb;

		$report = new VerifyReport();

		$posts_table    = $this->posts_table();
		$bookings_table = TableNames::bookings();

		$legacy_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_status FROM {$posts_table} WHERE post_type = %s",
				self::LEGACY_POST_TYPE
			),
			ARRAY_A
		);

		$legacy_counts = array();
		$legacy_totals = array();

		foreach ( (array) $legacy_rows as $row ) {
			$post_id = (int) $this->row_value( $row, 'ID', 0 );
			$status  = $this->map_legacy_status( (string) $this->row_value( $row, 'post_status', '' ) );
			$key     = $status['status'];

			$legacy_counts[ $key ] = ( $legacy_counts[ $key ] ?? 0 ) + 1;
			$legacy_totals[ $key ] = $this->decimal_add(
				$legacy_totals[ $key ] ?? '0.0000',
				$this->legacy_total( $post_id )
			);
		}

		$new_rows = $wpdb->get_results(
			"SELECT status, COUNT(*) AS total_count, COALESCE(SUM(total), 0) AS total_amount
			FROM {$bookings_table}
			WHERE legacy_post_id IS NOT NULL
			GROUP BY status",
			ARRAY_A
		);

		$new_counts = array();
		$new_totals = array();

		foreach ( (array) $new_rows as $row ) {
			$status                = (string) $this->row_value( $row, 'status', '' );
			$new_counts[ $status ] = (int) $this->row_value( $row, 'total_count', 0 );
			$new_totals[ $status ] = $this->decimal_add( (string) $this->row_value( $row, 'total_amount', '0' ), '0' );
		}

		$report->legacy_total = array_sum( $legacy_counts );
		$report->new_total    = array_sum( $new_counts );
		$report->diff_count   = $report->legacy_total - $report->new_total;
		$report->status_diff  = $this->diff_counts( $legacy_counts, $new_counts );

		$statuses = array_unique( array_merge( array_keys( $legacy_totals ), array_keys( $new_totals ) ) );
		foreach ( $statuses as $status ) {
			$diff = $this->decimal_sub( $legacy_totals[ $status ] ?? '0.0000', $new_totals[ $status ] ?? '0.0000' );
			if ( '0.0000' !== $diff ) {
				$report->total_amount_diff[ $status ] = $diff;
			}
		}

		$report->ok = 0 === $report->diff_count
			&& empty( $report->status_diff )
			&& empty( $report->total_amount_diff );

		return $report;
	}

	/**
	 * Rebuild inventory rows for each migrated booking event.
	 *
	 * @return InventoryRebuildReport
	 */
	public function rebuild_inventory(): InventoryRebuildReport {
		global $wpdb;

		$report = new InventoryRebuildReport();
		$table  = TableNames::bookings();
		$ids    = (array) $wpdb->get_col(
			"SELECT DISTINCT event_id FROM {$table}
			WHERE legacy_post_id IS NOT NULL AND event_id > 0
			ORDER BY event_id ASC"
		);

		$event_ids = array_unique(
			array_filter(
				array_map( 'intval', $ids ),
				static function ( int $event_id ): bool {
					return $event_id > 0;
				}
			)
		);

		foreach ( $event_ids as $event_id ) {
			$this->inventory->rebuild_for_event( $event_id );
			++$report->rows_updated;
		}

		$report->events_processed = count( $event_ids );

		return $report;
	}

	/**
	 * Map one legacy booking post to the custom-table row payload.
	 *
	 * @param \WP_Post $post Legacy booking post.
	 *
	 * @return array
	 */
	protected function map_legacy_post( \WP_Post $post ): array {
		$qty      = max( 1, abs( (int) $this->get_legacy_meta( (int) $post->ID, 'qty', 1 ) ) );
		$total    = $this->legacy_total( (int) $post->ID );
		$status   = $this->map_legacy_status( (string) $post->post_status );
		$method   = $this->normalize_payment_method( $this->get_legacy_meta( (int) $post->ID, 'payment_id', '' ) );
		$user_id  = abs( (int) $this->get_legacy_meta( (int) $post->ID, 'user_id', 0 ) );
		$user_id  = $user_id > 0 ? $user_id : abs( (int) ( $post->post_author ?? 0 ) );
		$txn_id   = $this->get_legacy_transaction_id( (int) $post->ID );
		$order_id = $this->get_legacy_gateway_order_id( (int) $post->ID );
		$order_id = '' !== $order_id ? $order_id : $txn_id;
		$created  = $this->normalize_gmt_datetime( (string) ( $post->post_date_gmt ?? '' ), (string) ( $post->post_date ?? '' ) );
		$updated  = $this->normalize_gmt_datetime( (string) ( $post->post_modified_gmt ?? '' ), (string) ( $post->post_modified ?? '' ) );
		$currency = $this->normalize_currency( $this->get_legacy_meta( (int) $post->ID, 'currency', 'USD' ) );
		$event_id = abs( (int) $this->get_legacy_meta( (int) $post->ID, 'event_id', 0 ) );

		return array(
			'legacy_post_id'      => (int) $post->ID,
			'event_id'            => $event_id,
			'user_id'             => $user_id,
			'qty'                 => $qty,
			'subtotal'            => $total,
			'discount_total'      => '0.0000',
			'tax_rate'            => '0.0000',
			'tax_total'           => '0.0000',
			'total'               => $total,
			'currency'            => $currency,
			'coupon_id'           => null,
			'payment_method'      => $method,
			'payment_mode'        => $this->payment_mode_for_method( $method ),
			'gateway_order_id'    => $this->nullable_string( $order_id ),
			'status'              => $status['status'],
			'payment_status'      => $status['payment_status'],
			'hold_expires_at_gmt' => null,
			'idempotency_key'     => null,
			'created_at_gmt'      => $created,
			'updated_at_gmt'      => $updated,
		);
	}

	/**
	 * Map a legacy post status to booking and payment statuses.
	 *
	 * @param string $post_status Legacy post status.
	 *
	 * @return array{status:string,payment_status:string}
	 */
	protected function map_legacy_status( string $post_status ): array {
		switch ( $post_status ) {
			case 'ea-completed':
			case 'publish':
				return array(
					'status'         => 'ea-completed',
					'payment_status' => 'paid',
				);

			case 'ea-processing':
				return array(
					'status'         => 'ea-processing',
					'payment_status' => 'pending',
				);

			case 'ea-cancelled':
			case 'trash':
				return array(
					'status'         => 'ea-cancelled',
					'payment_status' => 'cancelled',
				);

			case 'ea-failed':
				return array(
					'status'         => 'ea-failed',
					'payment_status' => 'failed',
				);

			case 'ea-expired':
				return array(
					'status'         => 'ea-expired',
					'payment_status' => 'unpaid',
				);

			case 'ea-refunded':
				return array(
					'status'         => 'ea-refunded',
					'payment_status' => 'refunded',
				);

			case 'ea-pending':
			case 'draft':
			default:
				return array(
					'status'         => 'ea-pending',
					'payment_status' => 'unpaid',
				);
		}
	}

	/**
	 * Insert a legacy PayPal Standard transaction if old meta carries one.
	 *
	 * @param int      $booking_id Migrated booking ID.
	 * @param \WP_Post $post       Legacy booking post.
	 *
	 * @return void
	 */
	protected function maybe_insert_legacy_transaction( int $booking_id, \WP_Post $post ): void {
		if ( $booking_id <= 0 ) {
			return;
		}

		$status = $this->map_legacy_status( (string) $post->post_status );
		if ( 'paid' !== $status['payment_status'] ) {
			return;
		}

		$txn_id = $this->get_legacy_transaction_id( (int) $post->ID );
		if ( '' === $txn_id ) {
			return;
		}

		if ( null !== $this->txns->find_by_txn_id( 'paypal_standard_txn', $txn_id ) ) {
			return;
		}

		$method = $this->normalize_payment_method( $this->get_legacy_meta( (int) $post->ID, 'payment_id', 'paypal' ) );

		$this->txns->insert(
			array(
				'booking_id'             => $booking_id,
				'type'                   => 'paypal_standard_txn',
				'gateway_transaction_id' => $txn_id,
				'amount'                 => $this->legacy_total( (int) $post->ID ),
				'currency'               => $this->normalize_currency( $this->get_legacy_meta( (int) $post->ID, 'currency', 'USD' ) ),
				'status'                 => 'completed',
				'raw_response'           => array(
					'migrated_from'  => 'event_auth_book',
					'legacy_post_id' => (int) $post->ID,
					'payment_method' => $method,
				),
			)
		);
	}

	/**
	 * Get a trusted posts table name.
	 *
	 * @return string
	 */
	private function posts_table(): string {
		global $wpdb;

		return isset( $wpdb->posts ) ? (string) $wpdb->posts : $wpdb->prefix . 'posts';
	}

	/**
	 * Read one legacy booking meta key.
	 *
	 * @param int    $post_id Legacy post ID.
	 * @param string $key     Short key without prefix.
	 * @param mixed  $fallback Default value.
	 *
	 * @return mixed
	 */
	private function get_legacy_meta( int $post_id, string $key, $fallback = '' ) {
		foreach ( $this->legacy_meta_keys( $key ) as $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );

			if ( '' !== $value && null !== $value && false !== $value ) {
				return $value;
			}
		}

		return $fallback;
	}

	/**
	 * Legacy meta keys to check for one short key.
	 *
	 * @param string $key Short key.
	 *
	 * @return string[]
	 */
	private function legacy_meta_keys( string $key ): array {
		if ( 0 === strpos( $key, 'ea_booking_' ) || 0 === strpos( $key, '_event_auth_book_' ) ) {
			return array( $key );
		}

		return array(
			'ea_booking_' . $key,
			'_event_auth_book_' . $key,
		);
	}

	/**
	 * Get legacy booking total.
	 *
	 * WPEMS currently stores ea_booking_price as the booking total, not unit price.
	 *
	 * @param int $post_id Legacy post ID.
	 *
	 * @return string
	 */
	private function legacy_total( int $post_id ): string {
		$total = $this->get_legacy_meta( $post_id, 'price', '' );

		if ( '' === $total ) {
			$total = $this->get_legacy_meta( $post_id, 'total', '0' );
		}

		return $this->normalize_decimal( $total );
	}

	/**
	 * Get a known legacy transaction ID if one exists.
	 *
	 * @param int $post_id Legacy post ID.
	 *
	 * @return string
	 */
	private function get_legacy_transaction_id( int $post_id ): string {
		$keys = array(
			'transaction_id',
			'txn_id',
			'payment_transaction_id',
			'paypal_transaction_id',
			'paypal_txn_id',
			'gateway_transaction_id',
		);

		foreach ( $keys as $key ) {
			$value = $this->get_legacy_meta( $post_id, $key, '' );
			if ( '' !== $value ) {
				return trim( (string) $value );
			}
		}

		return '';
	}

	/**
	 * Get a known legacy gateway order ID if one exists.
	 *
	 * @param int $post_id Legacy post ID.
	 *
	 * @return string
	 */
	private function get_legacy_gateway_order_id( int $post_id ): string {
		$keys = array(
			'gateway_order_id',
			'order_id',
			'paypal_order_id',
			'stripe_session_id',
		);

		foreach ( $keys as $key ) {
			$value = $this->get_legacy_meta( $post_id, $key, '' );
			if ( '' !== $value ) {
				return trim( (string) $value );
			}
		}

		return '';
	}

	/**
	 * Normalize a decimal to four places.
	 *
	 * @param mixed $value Raw decimal value.
	 *
	 * @return string
	 */
	private function normalize_decimal( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '0';
		$value = preg_replace( '/[^0-9.\-]/', '', $value );

		if ( null === $value || '' === $value || '-' === $value || '.' === $value || '-.' === $value ) {
			$value = '0';
		}

		return $this->decimal_add( $value, '0' );
	}

	/**
	 * Add two decimal strings at 4dp.
	 *
	 * @param string $left  Left value.
	 * @param string $right Right value.
	 *
	 * @return string
	 */
	private function decimal_add( string $left, string $right ): string {
		if ( function_exists( 'bcadd' ) ) {
			return bcadd( $left, $right, 4 );
		}

		return number_format( (float) $left + (float) $right, 4, '.', '' );
	}

	/**
	 * Subtract two decimal strings at 4dp.
	 *
	 * @param string $left  Left value.
	 * @param string $right Right value.
	 *
	 * @return string
	 */
	private function decimal_sub( string $left, string $right ): string {
		if ( function_exists( 'bcsub' ) ) {
			return bcsub( $left, $right, 4 );
		}

		return number_format( (float) $left - (float) $right, 4, '.', '' );
	}

	/**
	 * Normalize currency to a three-letter uppercase code.
	 *
	 * @param mixed $currency Raw currency value.
	 *
	 * @return string
	 */
	private function normalize_currency( $currency ): string {
		$currency = strtoupper( preg_replace( '/[^A-Z]/', '', strtoupper( (string) $currency ) ) );
		$currency = substr( $currency, 0, 3 );

		return 3 === strlen( $currency ) ? $currency : 'USD';
	}

	/**
	 * Normalize payment method IDs.
	 *
	 * @param mixed $method Raw method.
	 *
	 * @return string
	 */
	private function normalize_payment_method( $method ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $method ) );
	}

	/**
	 * Infer the new payment mode from the legacy payment method.
	 *
	 * @param string $method Payment method.
	 *
	 * @return string|null
	 */
	private function payment_mode_for_method( string $method ): ?string {
		if ( 'paypal' === $method ) {
			return 'paypal_standard';
		}

		if ( 'stripe' === $method ) {
			return 'stripe_checkout';
		}

		return null;
	}

	/**
	 * Convert empty strings to null.
	 *
	 * @param string $value Value.
	 *
	 * @return string|null
	 */
	private function nullable_string( string $value ): ?string {
		$value = trim( $value );

		return '' === $value ? null : $value;
	}

	/**
	 * Normalize a GMT datetime, with fallback.
	 *
	 * @param string $value    Candidate GMT datetime.
	 * @param string $fallback Fallback datetime.
	 *
	 * @return string
	 */
	private function normalize_gmt_datetime( string $value, string $fallback = '' ): string {
		$value = trim( $value );
		if ( '' !== $value && '0000-00-00 00:00:00' !== $value ) {
			return $value;
		}

		$fallback = trim( $fallback );
		if ( '' !== $fallback && '0000-00-00 00:00:00' !== $fallback ) {
			return $fallback;
		}

		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Build non-zero count diffs.
	 *
	 * @param array<string, int> $legacy Legacy counts.
	 * @param array<string, int> $migrated New counts.
	 *
	 * @return array<string, int>
	 */
	private function diff_counts( array $legacy, array $migrated ): array {
		$diff     = array();
		$statuses = array_unique( array_merge( array_keys( $legacy ), array_keys( $migrated ) ) );

		foreach ( $statuses as $status ) {
			$count_diff = (int) ( $legacy[ $status ] ?? 0 ) - (int) ( $migrated[ $status ] ?? 0 );
			if ( 0 !== $count_diff ) {
				$diff[ $status ] = $count_diff;
			}
		}

		return $diff;
	}

	/**
	 * Get a field value from an array or object row.
	 *
	 * @param mixed  $row     Row.
	 * @param string $key     Field key.
	 * @param mixed  $fallback Default value.
	 *
	 * @return mixed
	 */
	private function row_value( $row, string $key, $fallback = null ) {
		if ( is_array( $row ) ) {
			return $row[ $key ] ?? $fallback;
		}

		if ( is_object( $row ) ) {
			return $row->{$key} ?? $fallback;
		}

		return $fallback;
	}
}
