<?php
/**
 * Read-only value object for a booking's derived refund state.
 *
 * Computed by PaymentTransactionRepository::get_refund_summary() —
 * these columns are NOT stored on wpems_bookings.
 *
 * @package WPEMS\Models
 * @since   3.0.0
 */

namespace WPEMS\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Booking refund summary value object.
 */
final class BookingRefundSummary {

	/** @var string No refund activity. */
	public const STATUS_NOT_REFUNDED = 'not_refunded';

	/** @var string Partially refunded. */
	public const STATUS_PARTIAL = 'partial';

	/** @var string Fully refunded. */
	public const STATUS_REFUNDED = 'refunded';

	/** @var string DECIMAL(15,4) stored as string. */
	private string $refunded_total;

	/** @var string One of the STATUS_* constants. */
	private string $refund_status;

	/** @var string|null */
	private ?string $payment_completed_at_gmt;

	/**
	 * Private constructor.
	 */
	private function __construct( string $refunded_total, string $refund_status, ?string $payment_completed_at_gmt ) {
		$this->refunded_total           = $refunded_total;
		$this->refund_status            = $refund_status;
		$this->payment_completed_at_gmt = $payment_completed_at_gmt;
	}

	/**
	 * Factory for a booking with no refund activity.
	 *
	 * @return self
	 */
	public static function none(): self {
		return new self( '0.0000', self::STATUS_NOT_REFUNDED, null );
	}

	/**
	 * Factory computing refund status from totals.
	 *
	 * Uses bccomp() for precise DECIMAL comparison — no float math.
	 *
	 * @param string      $refunded_total           SUM of refund amounts (positive).
	 * @param string      $booking_total            Booking total.
	 * @param string|null $payment_completed_at_gmt Earliest completed payment timestamp.
	 *
	 * @return self
	 */
	public static function from_totals( string $refunded_total, string $booking_total, ?string $payment_completed_at_gmt ): self {
		$cmp_zero  = bccomp( $refunded_total, '0', 4 );
		$cmp_total = bccomp( $refunded_total, $booking_total, 4 );

		if ( $cmp_zero <= 0 ) {
			$status = self::STATUS_NOT_REFUNDED;
		} elseif ( $cmp_total >= 0 ) {
			$status = self::STATUS_REFUNDED;
		} else {
			$status = self::STATUS_PARTIAL;
		}

		$normalised = bcadd( $refunded_total, '0', 4 );

		return new self( $normalised, $status, $payment_completed_at_gmt );
	}

	/**
	 * Get the total amount refunded.
	 *
	 * @return string DECIMAL(15,4) as string.
	 */
	public function get_refunded_total(): string {
		return $this->refunded_total;
	}

	/**
	 * Get the refund status.
	 *
	 * @return string One of STATUS_* constants.
	 */
	public function get_refund_status(): string {
		return $this->refund_status;
	}

	/**
	 * Get the timestamp of the earliest completed payment.
	 *
	 * @return string|null UTC datetime string.
	 */
	public function get_payment_completed_at_gmt(): ?string {
		return $this->payment_completed_at_gmt;
	}

	/**
	 * Whether the booking has been fully refunded.
	 *
	 * @return bool
	 */
	public function is_fully_refunded(): bool {
		return self::STATUS_REFUNDED === $this->refund_status;
	}

	/**
	 * Whether the booking has been partially refunded.
	 *
	 * @return bool
	 */
	public function is_partially_refunded(): bool {
		return self::STATUS_PARTIAL === $this->refund_status;
	}

	/**
	 * Convert to an associative array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'refunded_total'           => $this->refunded_total,
			'refund_status'            => $this->refund_status,
			'payment_completed_at_gmt' => $this->payment_completed_at_gmt,
		);
	}
}
