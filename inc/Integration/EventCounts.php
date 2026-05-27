<?php
namespace WPEMS\Integration;

use WPEMS\Repositories\EventInventoryRepository;

defined( 'ABSPATH' ) || exit;

class EventCounts {

	private EventInventoryRepository $inventory;

	public function __construct( EventInventoryRepository $inventory ) {
		$this->inventory = $inventory;
	}

	public function register(): void {
		add_filter( 'tp_event_get_booked_count', [ $this, 'filter_booked_count' ], 10, 2 );
		add_filter( 'tp_event_get_available_count', [ $this, 'filter_available_count' ], 10, 2 );
	}

	public function filter_booked_count( int $count, int $event_id ): int {
		global $wpdb;
		$table = \WPEMS\Tables\TableNames::event_inventory();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$val = $wpdb->get_var( $wpdb->prepare(
			"SELECT held_qty + confirmed_qty FROM {$table} WHERE event_id = %d",
			$event_id
		) );

		return null !== $val ? (int) $val : $count;
	}

	public function filter_available_count( $count, int $event_id ) {
		return $this->inventory->get_available_quantity( $event_id );
	}
}
