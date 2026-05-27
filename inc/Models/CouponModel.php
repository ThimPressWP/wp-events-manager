<?php
/**
 * Immutable typed model for one row of `wpems_coupons`.
 *
 * @package WPEMS\Models
 * @since   3.0.0
 */

namespace WPEMS\Models;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Coupon model.
 */
final class CouponModel {

	/** @var string Percentage-based discount. */
	public const TYPE_PERCENT = 'percent';

	/** @var string Fixed-amount discount. */
	public const TYPE_AMOUNT = 'amount';

	/** @var string Hybrid: percentage with a max cap. */
	public const TYPE_HYBRID = 'hybrid';

	/** @var string Applies to all events. */
	public const APPLIES_ALL = 'all';

	/** @var string Applies to specific events only. */
	public const APPLIES_SPECIFIC = 'specific';

	/** @var string Active coupon status. */
	public const STATUS_ACTIVE = 'active';

	/** @var string Inactive coupon status. */
	public const STATUS_INACTIVE = 'inactive';

	/** @var int */
	private int $id;

	/** @var string Coupon code (uppercase). */
	private string $code;

	/** @var string|null */
	private ?string $description;

	/** @var string One of TYPE_* constants. */
	private string $discount_type;

	/** @var string|null DECIMAL stored as string. */
	private ?string $percent_value;

	/** @var string|null DECIMAL stored as string. */
	private ?string $amount_value;

	/** @var string|null DECIMAL stored as string. */
	private ?string $max_discount_amount;

	/** @var string One of APPLIES_* constants. */
	private string $applies_to;

	/** @var int|null Global usage cap; null = unlimited. */
	private ?int $usage_limit;

	/** @var int Current usage count. */
	private int $usage_count;

	/** @var int|null Per-user usage cap; null = unlimited. */
	private ?int $usage_limit_per_user;

	/** @var string|null DECIMAL stored as string. */
	private ?string $min_order_amount;

	/** @var string|null UTC datetime string. */
	private ?string $starts_at_gmt;

	/** @var string|null UTC datetime string. */
	private ?string $expires_at_gmt;

	/** @var string One of STATUS_* constants. */
	private string $status;

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
			throw new InvalidArgumentException( 'CouponModel requires "id" in row data.' );
		}

		$model = new self();

		$model->id                   = (int) $row['id'];
		$model->code                 = (string) ( $row['code'] ?? '' );
		$model->description          = isset( $row['description'] ) ? (string) $row['description'] : null;
		$model->discount_type        = (string) ( $row['discount_type'] ?? self::TYPE_PERCENT );
		$model->percent_value        = isset( $row['percent_value'] ) ? (string) $row['percent_value'] : null;
		$model->amount_value         = isset( $row['amount_value'] ) ? (string) $row['amount_value'] : null;
		$model->max_discount_amount  = isset( $row['max_discount_amount'] ) ? (string) $row['max_discount_amount'] : null;
		$model->applies_to           = (string) ( $row['applies_to'] ?? self::APPLIES_ALL );
		$model->usage_limit          = isset( $row['usage_limit'] ) ? (int) $row['usage_limit'] : null;
		$model->usage_count          = (int) ( $row['usage_count'] ?? 0 );
		$model->usage_limit_per_user = isset( $row['usage_limit_per_user'] ) ? (int) $row['usage_limit_per_user'] : null;
		$model->min_order_amount     = isset( $row['min_order_amount'] ) ? (string) $row['min_order_amount'] : null;
		$model->starts_at_gmt        = isset( $row['starts_at_gmt'] ) ? (string) $row['starts_at_gmt'] : null;
		$model->expires_at_gmt       = isset( $row['expires_at_gmt'] ) ? (string) $row['expires_at_gmt'] : null;
		$model->status               = (string) ( $row['status'] ?? self::STATUS_ACTIVE );
		$model->created_at_gmt       = (string) ( $row['created_at_gmt'] ?? '' );
		$model->updated_at_gmt       = (string) ( $row['updated_at_gmt'] ?? '' );

		return $model;
	}

	/**
	 * Get coupon ID
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get coupon code
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get coupon description
	 *
	 * @return string|null
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * Get discount type
	 *
	 * @return string One of TYPE_* constants.
	 */
	public function get_discount_type(): string {
		return $this->discount_type;
	}

	/**
	 * Get percent value
	 *
	 * @return string|null DECIMAL as string.
	 */
	public function get_percent_value(): ?string {
		return $this->percent_value;
	}

	/**
	 * Get amount value
	 *
	 * @return string|null DECIMAL as string.
	 */
	public function get_amount_value(): ?string {
		return $this->amount_value;
	}

	/**
	 * Get max discount amount
	 *
	 * @return string|null DECIMAL as string.
	 */
	public function get_max_discount_amount(): ?string {
		return $this->max_discount_amount;
	}

	/**
	 * Get applies to
	 *
	 * @return string One of APPLIES_* constants.
	 */
	public function get_applies_to(): string {
		return $this->applies_to;
	}

	/**
	 * Get usage limit
	 *
	 * @return int|null Null means unlimited.
	 */
	public function get_usage_limit(): ?int {
		return $this->usage_limit;
	}

	/**
	 * Get usage count
	 *
	 * @return int
	 */
	public function get_usage_count(): int {
		return $this->usage_count;
	}

	/**
	 * Get usage limit per user
	 *
	 * @return int|null Null means unlimited.
	 */
	public function get_usage_limit_per_user(): ?int {
		return $this->usage_limit_per_user;
	}

	/**
	 * Get minimum order amount
	 *
	 * @return string|null DECIMAL as string.
	 */
	public function get_min_order_amount(): ?string {
		return $this->min_order_amount;
	}

	/**
	 * Get starts at
	 *
	 * @return string|null UTC datetime string.
	 */
	public function get_starts_at_gmt(): ?string {
		return $this->starts_at_gmt;
	}

	/**
	 * Get expires at
	 *
	 * @return string|null UTC datetime string.
	 */
	public function get_expires_at_gmt(): ?string {
		return $this->expires_at_gmt;
	}

	/**
	 * Get status
	 *
	 * @return string One of STATUS_* constants.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get created at
	 *
	 * @return string
	 */
	public function get_created_at_gmt(): string {
		return $this->created_at_gmt;
	}

	/**
	 * Get updated at
	 *
	 * @return string
	 */
	public function get_updated_at_gmt(): string {
		return $this->updated_at_gmt;
	}

	/**
	 * Whether the coupon is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return self::STATUS_ACTIVE === $this->status;
	}

	/**
	 * Whether the current time falls within the coupon's validity window.
	 *
	 * @param DateTimeImmutable $now Current UTC timestamp.
	 *
	 * @return bool
	 */
	public function is_in_window( DateTimeImmutable $now ): bool {
		$utc = new DateTimeZone( 'UTC' );

		if ( null !== $this->starts_at_gmt ) {
			$start = new DateTimeImmutable( $this->starts_at_gmt, $utc );
			if ( $now < $start ) {
				return false;
			}
		}

		if ( null !== $this->expires_at_gmt ) {
			$end = new DateTimeImmutable( $this->expires_at_gmt, $utc );
			if ( $now > $end ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the coupon has remaining global capacity.
	 *
	 * @return bool
	 */
	public function has_global_capacity(): bool {
		if ( null === $this->usage_limit ) {
			return true;
		}

		return $this->usage_count < $this->usage_limit;
	}

	/**
	 * Convert to an associative array keyed by column name.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'id'                   => $this->id,
			'code'                 => $this->code,
			'description'          => $this->description,
			'discount_type'        => $this->discount_type,
			'percent_value'        => $this->percent_value,
			'amount_value'         => $this->amount_value,
			'max_discount_amount'  => $this->max_discount_amount,
			'applies_to'           => $this->applies_to,
			'usage_limit'          => $this->usage_limit,
			'usage_count'          => $this->usage_count,
			'usage_limit_per_user' => $this->usage_limit_per_user,
			'min_order_amount'     => $this->min_order_amount,
			'starts_at_gmt'        => $this->starts_at_gmt,
			'expires_at_gmt'       => $this->expires_at_gmt,
			'status'               => $this->status,
			'created_at_gmt'       => $this->created_at_gmt,
			'updated_at_gmt'       => $this->updated_at_gmt,
		);
	}
}
