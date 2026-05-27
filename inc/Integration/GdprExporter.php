<?php
namespace WPEMS\Integration;

use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;

defined( 'ABSPATH' ) || exit;

class GdprExporter {

	private BookingRepository $bookings;

	public function __construct( BookingRepository $bookings ) {
		$this->bookings = $bookings;
	}

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_exporter' ] );
	}

	public function register_exporter( array $exporters ): array {
		$exporters['wpems-bookings'] = [
			'exporter_friendly_name' => __( 'Event bookings', 'wp-events-manager' ),
			'callback'               => [ $this, 'export' ],
		];
		return $exporters;
	}

	public function export( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return [ 'data' => [], 'done' => true ];
		}

		$per_page = 50;

		$q          = new BookingQuery();
		$q->user_id = $user->ID;
		$q->limit   = $per_page;
		$q->offset  = ( $page - 1 ) * $per_page;
		$q->order_by = 'id';
		$q->order   = 'ASC';

		$items = $this->bookings->query( $q );
		$total = $this->bookings->count( $q );

		$data = [];
		foreach ( $items as $b ) {
			$data[] = [
				'group_id'    => 'wpems-bookings',
				'group_label' => __( 'Event Bookings', 'wp-events-manager' ),
				'item_id'     => 'booking-' . $b->get_id(),
				'data'        => [
					[ 'name' => __( 'Booking ID', 'wp-events-manager' ), 'value' => $b->get_id() ],
					[ 'name' => __( 'Event', 'wp-events-manager' ), 'value' => get_the_title( $b->get_event_id() ) ?: $b->get_event_id() ],
					[ 'name' => __( 'Quantity', 'wp-events-manager' ), 'value' => $b->get_qty() ],
					[ 'name' => __( 'Total', 'wp-events-manager' ), 'value' => $b->get_total() . ' ' . $b->get_currency() ],
					[ 'name' => __( 'Status', 'wp-events-manager' ), 'value' => $b->get_status() ],
					[ 'name' => __( 'Created', 'wp-events-manager' ), 'value' => $b->get_created_at_gmt() ],
				],
			];
		}

		return [
			'data' => $data,
			'done' => count( $items ) < $per_page,
		];
	}
}
