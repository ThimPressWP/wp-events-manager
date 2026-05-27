<?php
/**
 * Repository for wpems_payment_transactions table.
 *
 * Owns the refund-ready ledger. Provides get_refund_summary() which
 * is the canonical source for derived refund/payment columns.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\BookingRefundSummary;
use WPEMS\Models\PaymentTransactionModel;
use WPEMS\Tables\TableNames;

/**
 * Payment transaction repository.
 */
class PaymentTransactionRepository {

	/**
	 * Explicit column list for transaction reads.
	 *
	 * @var string[]
	 */
	private const SELECT_COLUMNS = array(
		'id',
		'booking_id',
		'type',
		'gateway_transaction_id',
		'gateway_capture_id',
		'gateway_charge_id',
		'gateway_payment_intent_id',
		'parent_transaction_id',
		'amount',
		'currency',
		'status',
		'raw_response',
		'created_at_gmt',
	);

	/**
	 * Keys that insert() requires.
	 *
	 * @var string[]
	 */
	private const REQUIRED_INSERT_KEYS = array(
		'booking_id',
		'type',
		'amount',
		'currency',
		'status',
	);

	/**
	 * Insert a new payment transaction row.
	 *
	 * @param array $data Column values.
	 *
	 * @return int New transaction ID.
	 *
	 * @throws InvalidArgumentException If required keys are missing.
	 * @throws RuntimeException         On database error.
	 */
	public function insert( array $data ): int {
		foreach ( self::REQUIRED_INSERT_KEYS as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				throw new InvalidArgumentException( "PaymentTransactionRepository::insert() requires '{$key}'." );
			}
		}

		$data['created_at_gmt'] = gmdate( 'Y-m-d H:i:s' );

		// Encode raw_response if array.
		if ( isset( $data['raw_response'] ) && is_array( $data['raw_response'] ) ) {
			$data['raw_response'] = wp_json_encode( $data['raw_response'] );
		}

		global $wpdb;

		$wpdb->insert( TableNames::payment_transactions(), $data );

		if ( '' !== $wpdb->last_error ) {
			throw new RuntimeException( $wpdb->last_error );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find a transaction by primary key.
	 *
	 * @param int $id Transaction ID.
	 *
	 * @return PaymentTransactionModel|null
	 */
	public function find( int $id ): ?PaymentTransactionModel {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::payment_transactions();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$row     = $wpdb->get_row(
			$wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE id = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return $row ? PaymentTransactionModel::from_row( $row ) : null;
	}

	/**
	 * Find all transactions for a booking.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return PaymentTransactionModel[]
	 */
	public function find_by_booking( int $booking_id ): array {
		if ( $booking_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$table   = TableNames::payment_transactions();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$rows    = $wpdb->get_results(
			$wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE booking_id = %d ORDER BY id ASC", $booking_id ),
			ARRAY_A
		);

		return array_map( array( PaymentTransactionModel::class, 'from_row' ), (array) $rows );
	}

	/**
	 * Find a transaction by type and gateway transaction ID.
	 *
	 * @param string $type   Transaction type.
	 * @param string $txn_id Gateway transaction ID.
	 *
	 * @return PaymentTransactionModel|null
	 */
	public function find_by_txn_id( string $type, string $txn_id ): ?PaymentTransactionModel {
		if ( '' === $type || '' === $txn_id ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::payment_transactions();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$row     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE type = %s AND gateway_transaction_id = %s LIMIT 1",
				$type,
				$txn_id
			),
			ARRAY_A
		);

		return $row ? PaymentTransactionModel::from_row( $row ) : null;
	}

	/**
	 * Mark a transaction as completed.
	 *
	 * @param int   $id    Transaction ID.
	 * @param array $extra Additional columns to update.
	 *
	 * @return bool
	 */
	public function mark_completed( int $id, array $extra = array() ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		$data           = $extra;
		$data['status'] = PaymentTransactionModel::STATUS_COMPLETED;

		// Encode raw_response if array.
		if ( isset( $data['raw_response'] ) && is_array( $data['raw_response'] ) ) {
			$data['raw_response'] = wp_json_encode( $data['raw_response'] );
		}

		global $wpdb;

		return false !== $wpdb->update( TableNames::payment_transactions(), $data, array( 'id' => $id ) );
	}

	/**
	 * Mark a transaction as failed, storing reason in raw_response.
	 *
	 * @param int    $id     Transaction ID.
	 * @param string $reason Failure reason.
	 *
	 * @return bool
	 */
	public function mark_failed( int $id, string $reason ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		$parent = $this->find( $id );

		$decoded = array();
		if ( null !== $parent ) {
			$decoded = $parent->decode_raw_response();
		}

		$decoded['failure_reason'] = $reason;

		global $wpdb;

		return false !== $wpdb->update(
			TableNames::payment_transactions(),
			array(
				'status'       => PaymentTransactionModel::STATUS_FAILED,
				'raw_response' => wp_json_encode( $decoded ),
			),
			array( 'id' => $id )
		);
	}

	/**
	 * Record a refund transaction linked to a parent capture/charge.
	 *
	 * Amount is always stored negative.
	 *
	 * @param int    $parent_id Parent transaction ID.
	 * @param string $amount    Refund amount (positive).
	 * @param string $txn_id    Gateway refund transaction ID.
	 * @param array  $raw       Raw gateway response.
	 *
	 * @return int New refund transaction ID.
	 *
	 * @throws RuntimeException If parent transaction is not found.
	 */
	public function record_refund( int $parent_id, string $amount, string $txn_id, array $raw ): int {
		$parent = $this->find( $parent_id );

		if ( null === $parent ) {
			throw new RuntimeException( "Parent transaction {$parent_id} not found." );
		}

		// Classify: full vs partial.
		$type = 0 === bccomp( $amount, $parent->get_amount(), 4 )
			? PaymentTransactionModel::TYPE_REFUND
			: PaymentTransactionModel::TYPE_PARTIAL_REFUND;

		$payload = array(
			'booking_id'             => $parent->get_booking_id(),
			'type'                   => $type,
			'gateway_transaction_id' => $txn_id,
			'parent_transaction_id'  => $parent_id,
			'amount'                 => '-' . ltrim( $amount, '-' ),
			'currency'               => $parent->get_currency(),
			'status'                 => PaymentTransactionModel::STATUS_COMPLETED,
			'raw_response'           => $raw,
		);

		return $this->insert( $payload );
	}

	/**
	 * Compute refund summary for a booking from the transactions ledger.
	 *
	 * Single query — no N+1. Canonical source for refunded_total,
	 * refund_status, and payment_completed_at_gmt.
	 *
	 * @param int    $booking_id   Booking ID.
	 * @param string $booking_total Total booking amount for status comparison.
	 *
	 * @return BookingRefundSummary
	 */
	public function get_refund_summary( int $booking_id, string $booking_total ): BookingRefundSummary {
		global $wpdb;

		$table = TableNames::payment_transactions();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE( -SUM( CASE WHEN type IN ('refund','partial_refund') AND status = 'completed' THEN amount END ), 0 ) AS refunded_total,
					MIN( CASE WHEN type IN ('paypal_capture','paypal_standard_txn','stripe_payment_intent','stripe_charge') AND status = 'completed' THEN created_at_gmt END ) AS first_completed
				FROM {$table}
				WHERE booking_id = %d",
				$booking_id
			),
			ARRAY_A
		);

		$refunded        = (string) ( $row['refunded_total'] ?? '0' );
		$first_completed = $row['first_completed'] ?? null;

		return BookingRefundSummary::from_totals( $refunded, $booking_total, $first_completed );
	}
}
