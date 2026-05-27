<?php
/**
 * Immutable typed value object for one row of `wpems_bookings`.
 *
 * Typed getters only — no magic methods, no setters.
 * Construction from a DB row uses `from_row()`;
 * mutation is the repository's job.
 *
 * @package WPEMS\Models
 * @since   3.0.0
 */

namespace WPEMS\Models;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Booking table model.
 */
final class BookingTableModel {

	/**
	 * Allowed booking statuses.
	 *
	 * @var string[]
	 */
	public const ALLOWED_STATUSES = array(
		'ea-pending',
		'ea-processing',
		'ea-completed',
		'ea-cancelled',
		'ea-failed',
		'ea-expired',
		'ea-refunded',
	);

	/**
	 * Allowed payment statuses.
	 *
	 * @var string[]
	 */
	public const ALLOWED_PAYMENT_STATUSES = array(
		'unpaid',
		'pending',
		'paid',
		'failed',
		'cancelled',
		'refunded',
	);

	/**
	 * Terminal booking statuses (no further state transitions expected).
	 *
	 * @var string[]
	 */
	private const TERMINAL_STATUSES = array(
		'ea-completed',
		'ea-cancelled',
		'ea-failed',
		'ea-expired',
		'ea-refunded',
	);

	/**
	 * Gateways that support payment sync polling.
	 *
	 * @var string[]
	 */
	private const SYNCABLE_METHODS = array( 'paypal', 'stripe' );

	/**
	 * Payment statuses that indicate sync is still needed.
	 *
	 * @var string[]
	 */
	private const SYNC_PENDING_STATUSES = array( 'unpaid', 'pending' );

	/** @var int */
	private int $id;

	/** @var int|null */
	private ?int $legacy_post_id;

	/** @var int */
	private int $event_id;

	/** @var int */
	private int $user_id;

	/** @var int */
	private int $qty;

	/** @var string DECIMAL stored as string to avoid float drift. */
	private string $subtotal;

	/** @var string */
	private string $discount_total;

	/** @var string */
	private string $tax_rate;

	/** @var string */
	private string $tax_total;

	/** @var string */
	private string $total;

	/** @var string ISO 4217 currency code. */
	private string $currency;

	/** @var int|null */
	private ?int $coupon_id;

	/** @var string */
	private string $payment_method;

	/** @var string|null */
	private ?string $payment_mode;

	/** @var string|null */
	private ?string $gateway_order_id;

	/** @var string */
	private string $status;

	/** @var string */
	private string $payment_status;

	/** @var string|null */
	private ?string $hold_expires_at_gmt;

	/** @var string|null */
	private ?string $idempotency_key;

	/** @var string */
	private string $created_at_gmt;

	/** @var string */
	private string $updated_at_gmt;

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
	 * @throws InvalidArgumentException If `id` is missing.
	 */
	public static function from_row( array $row ): self {
		if ( ! isset( $row['id'] ) ) {
			throw new InvalidArgumentException( 'BookingTableModel requires "id" in row data.' );
		}

		$model = new self();

		$model->id                  = (int) $row['id'];
		$model->legacy_post_id      = isset( $row['legacy_post_id'] ) ? (int) $row['legacy_post_id'] : null;
		$model->event_id            = (int) ( $row['event_id'] ?? 0 );
		$model->user_id             = (int) ( $row['user_id'] ?? 0 );
		$model->qty                 = (int) ( $row['qty'] ?? 1 );
		$model->subtotal            = (string) ( $row['subtotal'] ?? '0' );
		$model->discount_total      = (string) ( $row['discount_total'] ?? '0' );
		$model->tax_rate            = (string) ( $row['tax_rate'] ?? '0' );
		$model->tax_total           = (string) ( $row['tax_total'] ?? '0' );
		$model->total               = (string) ( $row['total'] ?? '0' );
		$model->currency            = (string) ( $row['currency'] ?? '' );
		$model->coupon_id           = isset( $row['coupon_id'] ) ? (int) $row['coupon_id'] : null;
		$model->payment_method      = (string) ( $row['payment_method'] ?? '' );
		$model->payment_mode        = isset( $row['payment_mode'] ) ? (string) $row['payment_mode'] : null;
		$model->gateway_order_id    = isset( $row['gateway_order_id'] ) ? (string) $row['gateway_order_id'] : null;
		$model->status              = (string) ( $row['status'] ?? 'ea-pending' );
		$model->payment_status      = (string) ( $row['payment_status'] ?? 'unpaid' );
		$model->hold_expires_at_gmt = isset( $row['hold_expires_at_gmt'] ) ? (string) $row['hold_expires_at_gmt'] : null;
		$model->idempotency_key     = isset( $row['idempotency_key'] ) ? (string) $row['idempotency_key'] : null;
		$model->created_at_gmt      = (string) ( $row['created_at_gmt'] ?? '' );
		$model->updated_at_gmt      = (string) ( $row['updated_at_gmt'] ?? '' );

		return $model;
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @return int|null
	 */
	public function get_legacy_post_id(): ?int {
		return $this->legacy_post_id;
	}

	/**
	 * @return int
	 */
	public function get_event_id(): int {
		return $this->event_id;
	}

	/**
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * @return int
	 */
	public function get_qty(): int {
		return $this->qty;
	}

	/**
	 * @return string
	 */
	public function get_subtotal(): string {
		return $this->subtotal;
	}

	/**
	 * @return string
	 */
	public function get_discount_total(): string {
		return $this->discount_total;
	}

	/**
	 * @return string
	 */
	public function get_tax_rate(): string {
		return $this->tax_rate;
	}

	/**
	 * @return string
	 */
	public function get_tax_total(): string {
		return $this->tax_total;
	}

	/**
	 * @return string
	 */
	public function get_total(): string {
		return $this->total;
	}

	/**
	 * @return string
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * @return int|null
	 */
	public function get_coupon_id(): ?int {
		return $this->coupon_id;
	}

	/**
	 * @return string
	 */
	public function get_payment_method(): string {
		return $this->payment_method;
	}

	/**
	 * @return string|null
	 */
	public function get_payment_mode(): ?string {
		return $this->payment_mode;
	}

	/**
	 * @return string|null
	 */
	public function get_gateway_order_id(): ?string {
		return $this->gateway_order_id;
	}

	/**
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function get_payment_status(): string {
		return $this->payment_status;
	}

	/**
	 * @return string|null
	 */
	public function get_hold_expires_at_gmt(): ?string {
		return $this->hold_expires_at_gmt;
	}

	/**
	 * @return string|null
	 */
	public function get_idempotency_key(): ?string {
		return $this->idempotency_key;
	}

	/**
	 * @return string
	 */
	public function get_created_at_gmt(): string {
		return $this->created_at_gmt;
	}

	/**
	 * @return string
	 */
	public function get_updated_at_gmt(): string {
		return $this->updated_at_gmt;
	}

	/**
	 * Whether the booking's payment has been completed.
	 *
	 * @return bool
	 */
	public function is_paid(): bool {
		return 'paid' === $this->payment_status;
	}

	/**
	 * Whether the booking is in a terminal state (no further transitions expected).
	 *
	 * @return bool
	 */
	public function is_terminal(): bool {
		return in_array( $this->status, self::TERMINAL_STATUSES, true );
	}

	/**
	 * Whether the booking needs payment sync polling.
	 *
	 * @return bool
	 */
	public function requires_payment_sync(): bool {
		return in_array( $this->payment_method, self::SYNCABLE_METHODS, true )
			&& in_array( $this->payment_status, self::SYNC_PENDING_STATUSES, true )
			&& ! $this->is_terminal();
	}

	/**
	 * Whether this booking was migrated from the legacy CPT.
	 *
	 * @return bool
	 */
	public function is_migrated_from_legacy(): bool {
		return null !== $this->legacy_post_id;
	}

	/**
	 * Convert to an associative array keyed by column name.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'id'                  => $this->id,
			'legacy_post_id'      => $this->legacy_post_id,
			'event_id'            => $this->event_id,
			'user_id'             => $this->user_id,
			'qty'                 => $this->qty,
			'subtotal'            => $this->subtotal,
			'discount_total'      => $this->discount_total,
			'tax_rate'            => $this->tax_rate,
			'tax_total'           => $this->tax_total,
			'total'               => $this->total,
			'currency'            => $this->currency,
			'coupon_id'           => $this->coupon_id,
			'payment_method'      => $this->payment_method,
			'payment_mode'        => $this->payment_mode,
			'gateway_order_id'    => $this->gateway_order_id,
			'status'              => $this->status,
			'payment_status'      => $this->payment_status,
			'hold_expires_at_gmt' => $this->hold_expires_at_gmt,
			'idempotency_key'     => $this->idempotency_key,
			'created_at_gmt'      => $this->created_at_gmt,
			'updated_at_gmt'      => $this->updated_at_gmt,
		);
	}
}
