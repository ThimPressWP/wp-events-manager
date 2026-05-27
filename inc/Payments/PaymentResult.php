<?php
/**
 * Immutable payment result value object.
 *
 * Returned by every gateway's handle_return_request(), handle_webhook_request(),
 * sync_payment_status(), and refund_payment() methods. Provides a single shape
 * so PaymentSyncService can treat all gateways uniformly.
 *
 * @package WPEMS\Payments
 * @since   3.0.0
 */

namespace WPEMS\Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Payment result.
 */
final class PaymentResult {

	/** @var string Payment completed. */
	public const STATUS_PAID = 'paid';

	/** @var string Payment still processing. */
	public const STATUS_PENDING = 'pending';

	/** @var string Payment failed. */
	public const STATUS_FAILED = 'failed';

	/** @var string Payment cancelled by user. */
	public const STATUS_CANCELLED = 'cancelled';

	/** @var string Payment refunded. */
	public const STATUS_REFUNDED = 'refunded';

	/** @var string Gateway returned an unrecognised status. */
	public const STATUS_UNKNOWN = 'unknown';

	/** @var bool Whether this result represents a non-error outcome. */
	private bool $success;

	/** @var string One of the STATUS_* constants. */
	private string $status;

	/** @var int Booking ID this result belongs to. */
	private int $booking_id;

	/** @var string Human-readable message. */
	private string $message;

	/**
	 * Transaction data ready for PaymentTransactionRepository::insert().
	 *
	 * Required keys for paid/refunded: type, gateway_transaction_id, amount,
	 * currency, status.
	 *
	 * @var array
	 */
	private array $transaction_data;

	/** @var string|null Gateway-side event ID (for idempotency ledger). */
	private ?string $gateway_event_id;

	/**
	 * Private constructor — use static factories.
	 */
	private function __construct() {}

	// ─── Static factories ───────────────────────────────────────────

	/**
	 * Payment completed successfully.
	 *
	 * @param int         $booking_id       Booking ID.
	 * @param array       $transaction_data Data for PaymentTransactionRepository.
	 * @param string|null $gateway_event_id Gateway-side event ID.
	 *
	 * @return self
	 */
	public static function paid( int $booking_id, array $transaction_data, ?string $gateway_event_id = null ): self {
		$r                   = new self();
		$r->success          = true;
		$r->status           = self::STATUS_PAID;
		$r->booking_id       = $booking_id;
		$r->message          = '';
		$r->transaction_data = $transaction_data;
		$r->gateway_event_id = $gateway_event_id;

		return $r;
	}

	/**
	 * Payment is pending (not an error; not terminal yet).
	 *
	 * @param int         $booking_id       Booking ID.
	 * @param string      $message          Human-readable message.
	 * @param string|null $gateway_event_id Gateway-side event ID.
	 *
	 * @return self
	 */
	public static function pending( int $booking_id, string $message = '', ?string $gateway_event_id = null ): self {
		$r                   = new self();
		$r->success          = true;
		$r->status           = self::STATUS_PENDING;
		$r->booking_id       = $booking_id;
		$r->message          = $message;
		$r->transaction_data = array();
		$r->gateway_event_id = $gateway_event_id;

		return $r;
	}

	/**
	 * Payment failed.
	 *
	 * @param int         $booking_id       Booking ID.
	 * @param string      $message          Error message.
	 * @param string|null $gateway_event_id Gateway-side event ID.
	 *
	 * @return self
	 */
	public static function failed( int $booking_id, string $message, ?string $gateway_event_id = null ): self {
		$r                   = new self();
		$r->success          = false;
		$r->status           = self::STATUS_FAILED;
		$r->booking_id       = $booking_id;
		$r->message          = $message;
		$r->transaction_data = array();
		$r->gateway_event_id = $gateway_event_id;

		return $r;
	}

	/**
	 * Payment cancelled by user.
	 *
	 * @param int         $booking_id       Booking ID.
	 * @param string      $message          Human-readable message.
	 * @param string|null $gateway_event_id Gateway-side event ID.
	 *
	 * @return self
	 */
	public static function cancelled( int $booking_id, string $message = '', ?string $gateway_event_id = null ): self {
		$r                   = new self();
		$r->success          = false;
		$r->status           = self::STATUS_CANCELLED;
		$r->booking_id       = $booking_id;
		$r->message          = $message;
		$r->transaction_data = array();
		$r->gateway_event_id = $gateway_event_id;

		return $r;
	}

	/**
	 * Payment refunded.
	 *
	 * @param int         $booking_id       Booking ID.
	 * @param array       $transaction_data Data for PaymentTransactionRepository.
	 * @param string|null $gateway_event_id Gateway-side event ID.
	 *
	 * @return self
	 */
	public static function refunded( int $booking_id, array $transaction_data, ?string $gateway_event_id = null ): self {
		$r                   = new self();
		$r->success          = true;
		$r->status           = self::STATUS_REFUNDED;
		$r->booking_id       = $booking_id;
		$r->message          = '';
		$r->transaction_data = $transaction_data;
		$r->gateway_event_id = $gateway_event_id;

		return $r;
	}

	/**
	 * Gateway returned an unrecognised status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $message    Human-readable message.
	 *
	 * @return self
	 */
	public static function unknown( int $booking_id, string $message = '' ): self {
		$r                   = new self();
		$r->success          = false;
		$r->status           = self::STATUS_UNKNOWN;
		$r->booking_id       = $booking_id;
		$r->message          = $message;
		$r->transaction_data = array();
		$r->gateway_event_id = null;

		return $r;
	}

	// ─── Getters ────────────────────────────────────────────────────

	/**
	 * Whether this result represents a non-error outcome.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Get the payment status.
	 *
	 * @return string One of the STATUS_* constants.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get the booking ID.
	 *
	 * @return int
	 */
	public function get_booking_id(): int {
		return $this->booking_id;
	}

	/**
	 * Get the human-readable message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Get the transaction data array for PaymentTransactionRepository::insert().
	 *
	 * @return array
	 */
	public function get_transaction_data(): array {
		return $this->transaction_data;
	}

	/**
	 * Get the gateway-side event ID (for idempotency ledger).
	 *
	 * @return string|null
	 */
	public function get_gateway_event_id(): ?string {
		return $this->gateway_event_id;
	}
}
