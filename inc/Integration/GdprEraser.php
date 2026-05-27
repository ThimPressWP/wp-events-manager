<?php
namespace WPEMS\Integration;

use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;

defined( 'ABSPATH' ) || exit;

class GdprEraser {

	private BookingRepository $bookings;

	public function __construct( BookingRepository $bookings ) {
		$this->bookings = $bookings;
	}

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	public function register_eraser( array $erasers ): array {
		$erasers['wpems-bookings'] = [
			'eraser_friendly_name' => __( 'Event bookings', 'wp-events-manager' ),
			'callback'             => [ $this, 'erase' ],
		];
		return $erasers;
	}

	public function erase( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return [ 'items_removed' => 0, 'items_retained' => 0, 'messages' => [], 'done' => true ];
		}

		$per_page = 50;

		$q          = new BookingQuery();
		$q->user_id = $user->ID;
		$q->limit   = $per_page;
		$q->offset  = ( $page - 1 ) * $per_page;
		$q->order_by = 'id';
		$q->order   = 'ASC';

		$items = $this->bookings->query( $q );

		$retained = 0;
		foreach ( $items as $b ) {
			$this->bookings->update( $b->get_id(), [
				'user_id'         => 0,
				'idempotency_key' => null,
			] );
			$retained++;
		}

		return [
			'items_removed'  => 0,
			'items_retained' => $retained,
			'messages'       => [ __( 'Booking records anonymised. Financial audit data preserved.', 'wp-events-manager' ) ],
			'done'           => count( $items ) < $per_page,
		];
	}
}
