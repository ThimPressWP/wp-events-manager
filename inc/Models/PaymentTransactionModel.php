<?php
/**
 * Immutable typed model for one row of `wpems_payment_transactions`.
 *
 * @package WPEMS\Models
 * @since   3.0.0
 */

namespace WPEMS\Models;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Payment transaction model.
 */
final class PaymentTransactionModel {

	/** @var string PayPal REST capture. */
	public const TYPE_PAYPAL_CAPTURE = 'paypal_capture';

	/** @var string PayPal Standard IPN transaction. */
	public const TYPE_PAYPAL_STANDARD_TXN = 'paypal_standard_txn';

	/** @var string Stripe PaymentIntent. */
	public const TYPE_STRIPE_PAYMENT_INTENT = 'stripe_payment_intent';

	/** @var string Stripe Charge. */
	public const TYPE_STRIPE_CHARGE = 'stripe_charge';

	/** @var string Full refund. */
	public const TYPE_REFUND = 'refund';

	/** @var string Partial refund. */
	public const TYPE_PARTIAL_REFUND = 'partial_refund';

	/** @var string */
	public const STATUS_PENDING = 'pending';

	/** @var string */
	public const STATUS_COMPLETED = 'completed';

	/** @var string */
	public const STATUS_FAILED = 'failed';

	/** @var string */
	public const STATUS_REFUNDED = 'refunded';

	/** @var int */
	private int $id;

	/** @var int Parent booking ID. */
	private int $booking_id;

	/** @var string One of TYPE_* constants. */
	private string $type;

	/** @var string|null */
	private ?string $gateway_transaction_id;

	/** @var string|null */
	private ?string $gateway_capture_id;

	/** @var string|null */
	private ?string $gateway_charge_id;

	/** @var string|null */
	private ?string $gateway_payment_intent_id;

	/** @var int|null Parent transaction for refund linkage. */
	private ?int $parent_transaction_id;

	/** @var string DECIMAL as string; can be negative for refunds. */
	private string $amount;

	/** @var string ISO 4217 currency code. */
	private string $currency;

	/** @var string One of STATUS_* constants. */
	private string $status;

	/** @var string|null Raw gateway JSON response. */
	private ?string $raw_response;

	/** @var string UTC datetime string. */
	private string $created_at_gmt;

	/**
	 * Private constructor — use from_row() to instantiate.
	 */
	private function __construct() {}

	/**
	 * Construct a model from a database row.
	 *
	 * @param array $row Associative array keyed by column name.
	 *
	 * @return self
	 *
	 * @throws InvalidArgumentException If `id` or `booking_id` is missing.
	 */
	public static function from_row( array $row ): self {
		if ( ! isset( $row['id'] ) ) {
			throw new InvalidArgumentException( 'PaymentTransactionModel requires "id" in row data.' );
		}
		if ( ! isset( $row['booking_id'] ) ) {
			throw new InvalidArgumentException( 'PaymentTransactionModel requires "booking_id" in row data.' );
		}

		$model = new self();

		$model->id                        = (int) $row['id'];
		$model->booking_id                = (int) $row['booking_id'];
		$model->type                      = (string) ( $row['type'] ?? '' );
		$model->gateway_transaction_id    = isset( $row['gateway_transaction_id'] ) ? (string) $row['gateway_transaction_id'] : null;
		$model->gateway_capture_id        = isset( $row['gateway_capture_id'] ) ? (string) $row['gateway_capture_id'] : null;
		$model->gateway_charge_id         = isset( $row['gateway_charge_id'] ) ? (string) $row['gateway_charge_id'] : null;
		$model->gateway_payment_intent_id = isset( $row['gateway_payment_intent_id'] ) ? (string) $row['gateway_payment_intent_id'] : null;
		$model->parent_transaction_id     = isset( $row['parent_transaction_id'] ) ? (int) $row['parent_transaction_id'] : null;
		$model->amount                    = (string) ( $row['amount'] ?? '0' );
		$model->currency                  = (string) ( $row['currency'] ?? '' );
		$model->status                    = (string) ( $row['status'] ?? self::STATUS_PENDING );
		$model->raw_response              = isset( $row['raw_response'] ) ? (string) $row['raw_response'] : null;
		$model->created_at_gmt            = (string) ( $row['created_at_gmt'] ?? '' );

		return $model;
	}

	/**
	 * Get transaction ID
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get booking ID
	 * @return int
	 */
	public function get_booking_id(): int {
		return $this->booking_id;
	}

	/**
	 * Get transaction type
	 * @return string One of TYPE_* constants.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get gateway transaction ID
	 * @return string|null
	 */
	public function get_gateway_transaction_id(): ?string {
		return $this->gateway_transaction_id;
	}

	/**
	 * Get gateway capture ID
	 * @return string|null
	 */
	public function get_gateway_capture_id(): ?string {
		return $this->gateway_capture_id;
	}

	/**
	 * Get gateway charge ID
	 * @return string|null
	 */
	public function get_gateway_charge_id(): ?string {
		return $this->gateway_charge_id;
	}

	/**
	 * Get gateway payment intent ID
	 * @return string|null
	 */
	public function get_gateway_payment_intent_id(): ?string {
		return $this->gateway_payment_intent_id;
	}

	/**
	 * Get parent transaction ID
	 * @return int|null
	 */
	public function get_parent_transaction_id(): ?int {
		return $this->parent_transaction_id;
	}

	/**
	 * Get transaction amount
	 * @return string DECIMAL as string; can be negative for refunds.
	 */
	public function get_amount(): string {
		return $this->amount;
	}

	/**
	 * Get transaction currency
	 * @return string ISO 4217 currency code.
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Get transaction status
	 * @return string One of STATUS_* constants.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get raw response
	 * @return string|null Raw JSON string.
	 */
	public function get_raw_response(): ?string {
		return $this->raw_response;
	}

	/**
	 * Get created at (UTC)
	 * @return string UTC datetime string.
	 */
	public function get_created_at_gmt(): string {
		return $this->created_at_gmt;
	}

	/**
	 * Whether this transaction is a refund (full or partial).
	 *
	 * @return bool
	 */
	public function is_refund(): bool {
		return in_array( $this->type, array( self::TYPE_REFUND, self::TYPE_PARTIAL_REFUND ), true );
	}

	/**
	 * Whether this transaction completed successfully.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return self::STATUS_COMPLETED === $this->status;
	}

	/**
	 * Decode the raw gateway response JSON.
	 *
	 * @return array Never throws on bad JSON.
	 */
	public function decode_raw_response(): array {
		if ( null === $this->raw_response || '' === $this->raw_response ) {
			return array();
		}

		$decoded = json_decode( $this->raw_response, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Convert to an associative array keyed by column name.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'id'                        => $this->id,
			'booking_id'                => $this->booking_id,
			'type'                      => $this->type,
			'gateway_transaction_id'    => $this->gateway_transaction_id,
			'gateway_capture_id'        => $this->gateway_capture_id,
			'gateway_charge_id'         => $this->gateway_charge_id,
			'gateway_payment_intent_id' => $this->gateway_payment_intent_id,
			'parent_transaction_id'     => $this->parent_transaction_id,
			'amount'                    => $this->amount,
			'currency'                  => $this->currency,
			'status'                    => $this->status,
			'raw_response'              => $this->raw_response,
			'created_at_gmt'            => $this->created_at_gmt,
		);
	}
}
