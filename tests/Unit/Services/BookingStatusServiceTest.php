<?php
/**
 * Unit tests for WPEMS\Services\BookingStatusService.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use Brain\Monkey\Actions;
use DomainException;
use InvalidArgumentException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\CouponService;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Services\BookingStatusService
 */
class BookingStatusServiceTest extends TestCase {

	private BookingStatusService $svc;
	private $bookings;
	private $inventory;
	private $coupons;
	private $sync_queue;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings   = \Mockery::mock( BookingRepository::class );
		$this->inventory  = \Mockery::mock( EventInventoryRepository::class );
		$this->coupons    = \Mockery::mock( CouponService::class );
		$this->sync_queue = \Mockery::mock( PaymentSyncQueueRepository::class );

		$this->svc = new BookingStatusService(
			$this->bookings,
			$this->inventory,
			$this->coupons,
			$this->sync_queue
		);
	}

	/**
	 * Helper: create a BookingTableModel with given overrides.
	 *
	 * @param array $overrides Column overrides.
	 *
	 * @return BookingTableModel
	 */
	private function make_booking( array $overrides = array() ): BookingTableModel {
		return BookingTableModel::from_row( array_merge(
			array(
				'id'               => 100,
				'event_id'         => 10,
				'user_id'          => 1,
				'qty'              => 2,
				'subtotal'         => '200.0000',
				'discount_total'   => '0',
				'tax_rate'         => '0',
				'tax_total'        => '0',
				'total'            => '200.0000',
				'currency'         => 'USD',
				'payment_method'   => 'stripe',
				'status'           => 'ea-pending',
				'payment_status'   => 'unpaid',
				'created_at_gmt'   => '2026-01-01 00:00:00',
				'updated_at_gmt'   => '2026-01-01 00:00:00',
			),
			$overrides
		) );
	}

	// ─── transition() ───────────────────────────────────────────────

	/** @test */
	public function test_transition_returns_false_for_missing_booking(): void {
		$this->bookings->shouldReceive( 'find' )->once()->with( 999 )->andReturn( null );

		$this->assertFalse( $this->svc->transition( 999, 'ea-completed', 'paid' ) );
	}

	/** @test */
	public function test_transition_idempotent_for_same_state(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		// No side-effects should be called.
		$this->bookings->shouldNotReceive( 'update' );

		$this->assertTrue( $this->svc->transition( 100, 'ea-pending', 'unpaid' ) );
	}

	/** @test */
	public function test_transition_throws_on_invalid_status(): void {
		$b = $this->make_booking();
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'invalid_status:bogus' );
		$this->svc->transition( 100, 'bogus', 'paid' );
	}

	/** @test */
	public function test_transition_throws_on_invalid_payment_status(): void {
		$b = $this->make_booking();
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'invalid_payment_status:bogus' );
		$this->svc->transition( 100, 'ea-completed', 'bogus' );
	}

	/** @test */
	public function test_transition_throws_on_illegal_from_to(): void {
		// Terminal → anything is illegal.
		$b = $this->make_booking( array( 'status' => 'ea-cancelled' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( 'illegal_transition:ea-cancelled->ea-completed' );
		$this->svc->transition( 100, 'ea-completed', 'paid' );
	}

	/** @test */
	public function test_transition_pending_to_completed_confirms_hold(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'confirm_hold' )->once()->with( 10, 2 );
		$this->bookings->shouldReceive( 'update' )->once()->with( 100, array(
			'status'         => 'ea-completed',
			'payment_status' => 'paid',
		) )->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once()->with( 100 );

		Actions\expectDone( 'wpems_booking_status_changed' )->once()->with( 100, 'ea-pending', 'ea-completed', '' );
		Actions\expectDone( 'wpems_booking_status_ea-completed' )->once()->with( 100, '' );

		$this->assertTrue( $this->svc->transition( 100, 'ea-completed', 'paid' ) );
	}

	/** @test */
	public function test_transition_processing_to_failed_releases_hold_and_voids_coupon(): void {
		$b = $this->make_booking( array( 'status' => 'ea-processing', 'payment_status' => 'pending' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'release_hold' )->once()->with( 10, 2 );
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once()->with( 100 );
		$this->coupons->shouldReceive( 'void_usage' )->once()->with( 100, 'payment_failed' );

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-failed' )->once();

		$this->assertTrue( $this->svc->transition( 100, 'ea-failed', 'failed', 'payment_failed' ) );
	}

	/** @test */
	public function test_transition_completed_to_refunded_releases_confirmed(): void {
		$b = $this->make_booking( array( 'status' => 'ea-completed', 'payment_status' => 'paid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'release_confirmed' )->once()->with( 10, 2 );
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once()->with( 100 );

		// Coupon void should NOT happen (from=completed).
		$this->coupons->shouldNotReceive( 'void_usage' );

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-refunded' )->once();

		$this->assertTrue( $this->svc->transition( 100, 'ea-refunded', 'refunded' ) );
	}

	/** @test */
	public function test_transition_does_not_void_coupon_when_completed_was_reached(): void {
		// completed → refunded: coupon stays committed.
		$b = $this->make_booking( array( 'status' => 'ea-completed', 'payment_status' => 'paid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'release_confirmed' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once();
		$this->coupons->shouldNotReceive( 'void_usage' );

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-refunded' )->once();

		$this->assertTrue( $this->svc->transition( 100, 'ea-refunded', 'refunded' ) );
	}

	/** @test */
	public function test_transition_removes_sync_queue_row_on_paid(): void {
		$b = $this->make_booking( array( 'status' => 'ea-processing', 'payment_status' => 'pending' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'confirm_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once()->with( 100 );

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-completed' )->once();

		$this->assertTrue( $this->svc->transition( 100, 'ea-completed', 'paid' ) );
	}

	/** @test */
	public function test_transition_fires_action_hooks_in_order(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$this->inventory->shouldReceive( 'confirm_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once();

		// Verify both hooks fire.
		Actions\expectDone( 'wpems_booking_status_changed' )
			->once()
			->with( 100, 'ea-pending', 'ea-completed', 'webhook' );
		Actions\expectDone( 'wpems_booking_status_ea-completed' )
			->once()
			->with( 100, 'webhook' );

		$this->assertTrue( $this->svc->transition( 100, 'ea-completed', 'paid', 'webhook' ) );
	}

	// ─── Convenience methods ────────────────────────────────────────

	/** @test */
	public function test_mark_completed(): void {
		$b = $this->make_booking( array( 'status' => 'ea-processing', 'payment_status' => 'pending' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );
		$this->inventory->shouldReceive( 'confirm_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once();

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-completed' )->once();

		$this->assertTrue( $this->svc->mark_completed( 100, 'payment received' ) );
	}

	/** @test */
	public function test_mark_cancelled(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );
		$this->inventory->shouldReceive( 'release_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once();
		$this->coupons->shouldReceive( 'void_usage' )->once();

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-cancelled' )->once();

		$this->assertTrue( $this->svc->mark_cancelled( 100, 'user cancelled' ) );
	}

	/** @test */
	public function test_mark_failed(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );
		$this->inventory->shouldReceive( 'release_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'remove' )->once();
		$this->coupons->shouldReceive( 'void_usage' )->once();

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-failed' )->once();

		$this->assertTrue( $this->svc->mark_failed( 100, 'payment error' ) );
	}

	/** @test */
	public function test_mark_expired(): void {
		$b = $this->make_booking( array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );
		$this->inventory->shouldReceive( 'release_hold' )->once();
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		$this->coupons->shouldReceive( 'void_usage' )->once();
		// 'unpaid' is NOT in SYNC_DONE_PAYMENT_STATUSES, so remove is not called.
		$this->sync_queue->shouldNotReceive( 'remove' );

		Actions\expectDone( 'wpems_booking_status_changed' )->once();
		Actions\expectDone( 'wpems_booking_status_ea-expired' )->once();

		$this->assertTrue( $this->svc->mark_expired( 100, 'hold timeout' ) );
	}
}
