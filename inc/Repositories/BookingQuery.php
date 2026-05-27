<?php
/**
 * Data object for building booking list queries.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Booking query parameters.
 */
final class BookingQuery {

	/** @var int Filter by event ID. 0 = no filter. */
	public int $event_id = 0;

	/** @var int Filter by user ID. 0 = no filter. */
	public int $user_id = 0;

	/** @var string Filter by booking status. Empty = no filter. */
	public string $status = '';

	/** @var string Filter by payment status. Empty = no filter. */
	public string $payment_status = '';

	/** @var string Filter by payment method. Empty = no filter. */
	public string $payment_method = '';

	/** @var string Filter bookings created on/after this GMT date. */
	public string $date_from = '';

	/** @var string Filter bookings created on/before this GMT date. */
	public string $date_to = '';

	/** @var string Search idempotency_key or gateway_order_id. */
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
