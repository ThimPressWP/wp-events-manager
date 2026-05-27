<?php
/**
 * Repository for wpems_payment_events idempotency ledger.
 *
 * Guards against double-confirming a booking when PayPal/Stripe
 * deliver the same webhook event twice.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use WPEMS\Tables\TableNames;

/**
 * Payment event repository.
 */
class PaymentEventRepository {

	/**
	 * Keys that record() requires.
	 *
	 * @var string[]
	 */
	private const REQUIRED_KEYS = array(
		'gateway_id',
		'event_id',
		'event_type',
	);

	/**
	 * Check whether a webhook event has already been processed.
	 *
	 * @param string $gateway_id Gateway identifier (e.g. 'paypal', 'stripe').
	 * @param string $event_id   Gateway event ID.
	 *
	 * @return bool True if already processed.
	 */
	public function was_processed( string $gateway_id, string $event_id ): bool {
		if ( '' === $gateway_id || '' === $event_id ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::payment_events();

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$table} WHERE gateway_id = %s AND event_id = %s AND status = 'processed' LIMIT 1",
				$gateway_id,
				$event_id
			)
		);

		return (bool) $result;
	}

	/**
	 * Record a webhook event. Duplicates are silently absorbed.
	 *
	 * @param array $data Event data with gateway_id, event_id, event_type.
	 *
	 * @return int Row ID (existing or new).
	 *
	 * @throws InvalidArgumentException If required keys are missing.
	 */
	public function record( array $data ): int {
		foreach ( self::REQUIRED_KEYS as $key ) {
			if ( empty( $data[ $key ] ) ) {
				throw new InvalidArgumentException( "PaymentEventRepository::record() requires '{$key}'." );
			}
		}

		$payload = array_merge(
			array(
				'gateway_id'     => '',
				'event_id'       => '',
				'object_id'      => null,
				'event_type'     => '',
				'booking_id'     => null,
				'status'         => 'processed',
				'payload_hash'   => null,
				'created_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			),
			$data
		);

		// Compute payload_hash if raw_payload provided.
		if ( ! empty( $data['raw_payload'] ) ) {
			$payload['payload_hash'] = hash( 'sha256', $data['raw_payload'] );
			unset( $payload['raw_payload'] );
		}

		global $wpdb;

		// Suppress duplicate key errors.
		$wpdb->suppress_errors( true );
		$inserted = $wpdb->insert( TableNames::payment_events(), $payload );
		$wpdb->suppress_errors( false );

		if ( $inserted ) {
			return (int) $wpdb->insert_id;
		}

		// Duplicate — fetch existing row ID.
		$table = TableNames::payment_events();

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE gateway_id = %s AND event_id = %s LIMIT 1",
				$payload['gateway_id'],
				$payload['event_id']
			)
		);

		return (int) $existing;
	}
}
