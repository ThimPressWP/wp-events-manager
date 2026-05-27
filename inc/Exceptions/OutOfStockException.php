<?php
/**
 * Out of stock exception.
 *
 * Thrown when inventory reservation fails during checkout.
 *
 * @package WPEMS\Exceptions
 * @since   3.0.0
 */

namespace WPEMS\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Out of stock exception.
 */
class OutOfStockException extends \RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $qty      Requested quantity.
	 */
	public function __construct( int $event_id, int $qty ) {
		parent::__construct(
			sprintf( 'Event %d cannot reserve %d ticket(s) — out of stock.', $event_id, $qty )
		);
	}
}
