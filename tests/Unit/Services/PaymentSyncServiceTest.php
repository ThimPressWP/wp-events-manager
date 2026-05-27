<?php
/**
 * Unit tests for WPEMS\Services\PaymentSyncService.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use DateTimeImmutable;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentEventRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Testable subclass to stub protected helpers.
 */
class TestablePaymentSyncService extends PaymentSyncService {

	/** @var int */
	public int $max_attempts = 24;

	/** @var string */
	public string $uuid = 'test-uuid-1234';

	/** @inheritDoc */
	protected function get_max_sync_attempts(): int {
		return $this->max_attempts;
	}

	/** @inheritDoc */
	protected function generate_uuid(): string {
		return $this->uuid;
	}
}

/**
 * @covers \WPEMS\Services\PaymentSyncService
 */
class PaymentSyncServiceTest extends TestCase {

	private TestablePaymentSyncService $svc;
	private $bookings;
	private $txns;
	private $events;
	private $queue;
	private $status;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings = \Mockery::mock( BookingRepository::class );
		$this->txns     = \Mockery::mock( PaymentTransactionRepository::class );
		$this->events   = \Mockery::mock( PaymentEventRepository::class );
		$this->queue    = \Mockery::mock( PaymentSyncQueueRepository::class );
		$this->status   = \Mockery::mock( BookingStatusService::class );

		$this->svc = new TestablePaymentSyncService(
			$this->bookings,
			$this->txns,
			$this->events,
			$this->queue,
			$this->status
		);
	}

	/**
	 * Helper: build a BookingTableModel.
	 *
	 * @param array $overrides Column overrides.
	 *
	 * @return BookingTableModel
	 */
	private function make_booking( array $overrides = array() ): BookingTableModel {
		return BookingTableModel::from_row( array_merge(
			array(
				'id'             => 100,
				'event_id'       => 10,
				'user_id'        => 1,
				'qty'            => 2,
				'subtotal'       => '200.0000',
				'total'          => '200.0000',
				'currency'       => 'USD',
				'payment_method' => 'stripe',
				'status'         => 'ea-processing',
				'payment_status' => 'pending',
				'created_at_gmt' => '2026-01-01 00:00:00',
				'updated_at_gmt' => '2026-01-01 00:00:00',
			),
			$overrides
		) );
	}

	// ─── sync_booking ───────────────────────────────────────────────

	/** @test */
	public function test_sync_booking_returns_unknown_for_missing_booking(): void {
		$this->bookings->shouldReceive( 'find' )->once()->with( 999 )->andReturn( null );

		$result = $this->svc->sync_booking( 999 );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 0, $result->get_booking_id() );
		$this->assertSame( 'booking_not_found', $result->get_message() );
	}

	/** @test */
	public function test_sync_booking_returns_unknown_for_already_paid(): void {
		$b = $this->make_booking( array(
			'status'         => 'ea-completed',
			'payment_status' => 'paid',
		) );
		$this->bookings->shouldReceive( 'find' )->once()->andReturn( $b );

		$result = $this->svc->sync_booking( 100 );

		$this->assertSame( 'not_syncable', $result->get_message() );
	}

	// ─── apply_result ───────────────────────────────────────────────

	/** @test */
	public function test_apply_result_paid_inserts_transaction_and_completes_booking(): void {
		$b = $this->make_booking();

		$txn_data = array(
			'booking_id'             => 100,
			'type'                   => 'stripe_charge',
			'gateway_transaction_id' => 'pi_123',
			'amount'                 => '200.0000',
			'currency'               => 'USD',
			'status'                 => 'completed',
		);

		$result = PaymentResult::paid( 100, $txn_data, 'evt_abc' );

		$this->events->shouldReceive( 'was_processed' )->once()->with( 'stripe', 'evt_abc' )->andReturn( false );
		$this->events->shouldReceive( 'record' )->once();
		$this->txns->shouldReceive( 'insert' )->once()->with( $txn_data );
		$this->status->shouldReceive( 'mark_completed' )->once()->with( 100, 'paid_via_webhook' );
		$this->queue->shouldReceive( 'remove' )->once()->with( 100 );

		$out = $this->svc->apply_result( $b, $result, 'webhook' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_paid_removes_sync_queue_row(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::paid( 100, array(
			'booking_id' => 100,
			'type'       => 'stripe_charge',
			'amount'     => '200.0000',
			'currency'   => 'USD',
			'status'     => 'completed',
		) );

		$this->events->shouldReceive( 'was_processed' )->andReturn( false );
		$this->events->shouldReceive( 'record' )->once();
		$this->txns->shouldReceive( 'insert' )->once();
		$this->status->shouldReceive( 'mark_completed' )->once();
		$this->queue->shouldReceive( 'remove' )->once()->with( 100 );

		$out = $this->svc->apply_result( $b, $result, 'return' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_pending_extends_backoff(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::pending( 100, 'awaiting' );

		$this->events->shouldReceive( 'was_processed' )->never(); // no gateway_event_id.
		$this->events->shouldReceive( 'record' )->once();
		$this->queue->shouldReceive( 'get' )->once()->with( 100 )->andReturn( array(
			'booking_id'    => 100,
			'sync_attempts' => 1,
		) );
		$this->queue->shouldReceive( 'update_attempt' )->once()->with(
			100,
			\Mockery::type( DateTimeImmutable::class ),
			'awaiting'
		);

		$out = $this->svc->apply_result( $b, $result, 'cron' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_pending_attempts_4_uses_15_min_backoff(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::pending( 100, '' );

		$this->events->shouldReceive( 'record' )->once();
		$this->queue->shouldReceive( 'get' )->once()->with( 100 )->andReturn( array(
			'booking_id'    => 100,
			'sync_attempts' => 3, // +1 = 4 → 15 min backoff.
		) );
		$this->queue->shouldReceive( 'update_attempt' )->once()->with(
			100,
			\Mockery::on( function ( DateTimeImmutable $dt ) {
				// Should be ~15 min from now.
				$diff = $dt->getTimestamp() - time();
				$this->assertGreaterThan( 800, $diff );
				$this->assertLessThan( 1000, $diff );
				return true;
			} ),
			null
		);

		$out = $this->svc->apply_result( $b, $result, 'cron' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_marks_failed_after_max_attempts(): void {
		$this->svc->max_attempts = 3;

		$b      = $this->make_booking();
		$result = PaymentResult::pending( 100, '' );

		$this->events->shouldReceive( 'record' )->once();
		$this->queue->shouldReceive( 'get' )->once()->with( 100 )->andReturn( array(
			'booking_id'    => 100,
			'sync_attempts' => 2, // +1 = 3 >= max.
		) );
		$this->status->shouldReceive( 'mark_failed' )->once()->with( 100, 'sync_max_attempts_exceeded' );
		$this->queue->shouldReceive( 'remove' )->once()->with( 100 );

		$out = $this->svc->apply_result( $b, $result, 'cron' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_replay_with_same_event_id_is_noop(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::paid( 100, array(), 'evt_duplicate' );

		$this->events->shouldReceive( 'was_processed' )->once()->with( 'stripe', 'evt_duplicate' )->andReturn( true );

		// No further calls should happen.
		$this->events->shouldNotReceive( 'record' );
		$this->txns->shouldNotReceive( 'insert' );
		$this->status->shouldNotReceive( 'mark_completed' );
		$this->queue->shouldNotReceive( 'remove' );

		$out = $this->svc->apply_result( $b, $result, 'webhook' );
		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_sync_due_bookings_processes_batch_in_order(): void {
		$this->queue->shouldReceive( 'dequeue_due' )->once()->with( 20 )->andReturn( array(
			array( 'booking_id' => 101 ),
			array( 'booking_id' => 102 ),
		) );

		// Both bookings are not found → unknown.
		$this->bookings->shouldReceive( 'find' )->with( 101 )->once()->andReturn( null );
		$this->bookings->shouldReceive( 'find' )->with( 102 )->once()->andReturn( null );

		$results = $this->svc->sync_due_bookings();

		$this->assertCount( 2, $results );
		$this->assertSame( 'booking_not_found', $results[0]->get_message() );
		$this->assertSame( 'booking_not_found', $results[1]->get_message() );
	}

	/** @test */
	public function test_refunded_result_records_refund_transaction(): void {
		$b = $this->make_booking();

		$txn_data = array(
			'parent_transaction_id'  => 50,
			'gateway_transaction_id' => 're_789',
			'amount'                 => '100.0000',
			'raw_response'           => array( 'foo' => 'bar' ),
		);

		$result = PaymentResult::refunded( 100, $txn_data, 'evt_refund' );

		$this->events->shouldReceive( 'was_processed' )->once()->with( 'stripe', 'evt_refund' )->andReturn( false );
		$this->events->shouldReceive( 'record' )->once();
		$this->txns->shouldReceive( 'record_refund' )->once()->with( 50, '100.0000', 're_789', array( 'foo' => 'bar' ) );
		$this->status->shouldReceive( 'transition' )->once()->with( 100, 'ea-refunded', 'refunded', '' );
		$this->queue->shouldReceive( 'remove' )->once()->with( 100 );

		$out = $this->svc->apply_result( $b, $result, 'webhook' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_apply_result_cancelled_marks_cancelled(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::cancelled( 100, 'user_cancelled', 'evt_cancel' );

		$this->events->shouldReceive( 'was_processed' )->once()->andReturn( false );
		$this->events->shouldReceive( 'record' )->once();
		$this->status->shouldReceive( 'mark_cancelled' )->once()->with( 100, 'user_cancelled' );
		$this->queue->shouldReceive( 'remove' )->once()->with( 100 );

		$out = $this->svc->apply_result( $b, $result, 'return' );

		$this->assertSame( $result, $out );
	}

	/** @test */
	public function test_unknown_result_schedules_one_attempt_only(): void {
		$b      = $this->make_booking();
		$result = PaymentResult::unknown( 100, 'gateway_timeout' );

		$this->events->shouldReceive( 'record' )->once();
		$this->queue->shouldReceive( 'get' )->once()->with( 100 )->andReturn( array(
			'booking_id'    => 100,
			'sync_attempts' => 0,
		) );
		$this->queue->shouldReceive( 'update_attempt' )->once()->with(
			100,
			\Mockery::type( DateTimeImmutable::class ),
			'gateway_timeout'
		);
		$this->status->shouldNotReceive( 'mark_failed' );
		$this->queue->shouldNotReceive( 'remove' );

		$out = $this->svc->apply_result( $b, $result, 'cron' );

		$this->assertSame( $result, $out );
	}
}
