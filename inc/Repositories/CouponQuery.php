<?php
/**
 * Data object for building coupon list queries.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Coupon query parameters.
 */
final class CouponQuery {

	/** @var string Filter by status. Empty = no filter. */
	public string $status = '';

	/** @var string Filter by discount type. Empty = no filter. */
	public string $discount_type = '';

	/** @var string Search coupon code. */
	public string $search = '';

	/** @var string Column to sort by (whitelisted). */
	public string $order_by = 'created_at_gmt';

	/** @var string Sort direction: ASC or DESC. */
	public string $order = 'DESC';

	/** @var int Maximum rows to return. */
	public int $limit = 20;

	/** @var int Row offset for pagination. */
	public int $offset = 0;
}
