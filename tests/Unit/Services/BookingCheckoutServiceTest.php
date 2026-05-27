<?php
/**
 * Unit tests for WPEMS\Services\BookingCheckoutService.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use WPEMS\Models\BookingTableModel;
use WPEMS\Models\CouponModel;
use WPEMS\Pricing\CheckoutQuote;
use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Services\BookingCheckoutService;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\CheckoutDispatchResult;
use WPEMS\Services\CheckoutQuoteService;
use WPEMS\Services\CouponService;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Services\BookingCheckoutService
 */
class BookingCheckoutServiceTest extends TestCase {

	private BookingCheckoutService $svc;
	private $bookings;
	private $inventory;
	private $quotes;
	private $coupons;
	private $coupon_repo;
	private $status;
	private $sync_queue;
	private $meta;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings    = \Mockery::mock( BookingRepository::class );
		$this->inventory   = \Mockery::mock( EventInventoryRepository::class );
		$this->quotes      = \Mockery::mock( CheckoutQuoteService::class );
		$this->coupons     = \Mockery::mock( CouponService::class );
		$this->coupon_repo = \Mockery::mock( CouponRepository::class );
		$this->status      = \Mockery::mock( BookingStatusService::class );
		$this->sync_queue  = \Mockery::mock( PaymentSyncQueueRepository::class );
		$this->meta        = \Mockery::mock( BookingMetaRepository::class );

		$this->svc = new BookingCheckoutService(
			$this->bookings,
			$this->inventory,
			$this->quotes,
			$this->coupons,
			$this->coupon_repo,
			$this->status,
			$this->sync_queue,
			$this->meta
		);

		// Stub $wpdb for transaction calls.
		$wpdb         = \Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'query' )->with( 'START TRANSACTION' )->andReturn( true )->byDefault();
		$wpdb->shouldReceive( 'query' )->with( 'COMMIT' )->andReturn( true )->byDefault();
		$wpdb->shouldReceive( 'query' )->with( 'ROLLBACK' )->andReturn( true )->byDefault();
		$GLOBALS['wpdb'] = $wpdb;
	}

	/**
	 * Build a standard set of checkout args.
	 *
	 * @param array $overrides Override default values.
	 *
	 * @return array
	 */
	private function make_args( array $overrides = array() ): array {
		return array_merge(
			array(
				'event_id'        => 10,
				'user_id'         => 1,
				'qty'             => 2,
				'coupon_code'     => '',
				'payment_method'  => 'stripe',
				'idempotency_key' => 'idem_abc123',
				'meta'            => array(),
			),
			$overrides
		);
	}

	/**
	 * Build a mock CheckoutQuote.
	 *
	 * @param array $overrides Field overrides.
	 *
	 * @return CheckoutQuote
	 */
	private function make_quote( array $overrides = array() ): CheckoutQuote {
		$d = array_merge(
			array(
				'subtotal'       => '200.0000',
				'discount_total' => '0.0000',
				'tax_rate'       => '0.0000',
				'tax_total'      => '0.0000',
				'total'          => '200.0000',
				'currency'       => 'USD',
				'coupon_id'      => null,
				'coupon_code'    => null,
				'tax_label'      => 'Tax',
			),
			$overrides
		);

		return new CheckoutQuote(
			$d['subtotal'],
			$d['discount_total'],
			$d['tax_rate'],
			$d['tax_total'],
			$d['total'],
			$d['currency'],
			$d['coupon_id'],
			$d['coupon_code'],
			$d['tax_label']
		);
	}

	// ─── Idempotency ────────────────────────────────────────────────

	/** @test */
	public function test_idempotency_short_circuit_returns_existing_booking(): void {
		$existing = BookingTableModel::from_row( array(
			'id'             => 42,
			'event_id'       => 10,
			'status'         => 'ea-completed',
			'payment_status' => 'paid',
			'created_at_gmt' => '2026-01-01 00:00:00',
			'updated_at_gmt' => '2026-01-01 00:00:00',
		) );

		$this->bookings->shouldReceive( 'find_by_idempotency_key' )
			->once()
			->with( 'idem_abc123' )
			->andReturn( $existing );

		$result = $this->svc->create_checkout( $this->make_args() );

		$this->assertTrue( $result->success );
		$this->assertSame( 42, $result->booking_id );
		$this->assertSame( 'free_completed', $result->next_step );
	}

	// ─── Free booking ───────────────────────────────────────────────

	/** @test */
	public function test_free_booking_completes_immediately_without_payment_method(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote( array( 'total' => '0.0000' ) );
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );

		$this->inventory->shouldReceive( 'reserve' )->once()->with( 10, 2 )->andReturn( true );
		$this->bookings->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$this->status->shouldReceive( 'mark_completed' )->once()->with( 1, 'free_booking' )->andReturn( true );

		$result = $this->svc->create_checkout( $this->make_args( array( 'payment_method' => '' ) ) );

		$this->assertTrue( $result->success );
		$this->assertSame( 1, $result->booking_id );
		$this->assertSame( 'free_completed', $result->next_step );
	}

	// ─── Payment method required ────────────────────────────────────

	/** @test */
	public function test_paid_checkout_requires_payment_method(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote( array( 'total' => '200.0000' ) );
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );

		$result = $this->svc->create_checkout( $this->make_args( array( 'payment_method' => '' ) ) );

		$this->assertFalse( $result->success );
		$this->assertSame( 'payment_method_required', $result->error_code );
	}

	// ─── Out of stock ───────────────────────────────────────────────

	/** @test */
	public function test_out_of_stock_returns_failure_without_inserting(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote();
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );
		$this->inventory->shouldReceive( 'reserve' )->once()->with( 10, 2 )->andReturn( false );
		$this->bookings->shouldNotReceive( 'insert' );

		$result = $this->svc->create_checkout( $this->make_args() );

		$this->assertFalse( $result->success );
		$this->assertSame( 'out_of_stock', $result->error_code );
	}

	// ─── Coupon commit failure ──────────────────────────────────────

	/** @test */
	public function test_coupon_commit_failure_rolls_back_booking_and_releases_hold(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote( array(
			'total'          => '180.0000',
			'discount_total' => '20.0000',
			'coupon_id'      => 5,
			'coupon_code'    => 'SAVE20',
		) );
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );

		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );
		$this->bookings->shouldReceive( 'insert' )->once()->andReturn( 1 );

		$coupon = CouponModel::from_row( array(
			'id'             => 5,
			'code'           => 'SAVE20',
			'discount_type'  => 'amount',
			'amount_value'   => '20.0000',
			'status'         => 'active',
			'created_at_gmt' => '2026-01-01 00:00:00',
			'updated_at_gmt' => '2026-01-01 00:00:00',
		) );
		$this->coupon_repo->shouldReceive( 'find' )->once()->with( 5 )->andReturn( $coupon );
		$this->coupons->shouldReceive( 'commit_usage' )->once()->andReturn( false );

		// Rollback side effects.
		$this->inventory->shouldReceive( 'release_hold' )->once()->with( 10, 2 );

		$result = $this->svc->create_checkout( $this->make_args() );

		$this->assertFalse( $result->success );
		$this->assertSame( 'coupon_no_longer_available', $result->error_code );
	}

	// ─── Online checkout ────────────────────────────────────────────

	/** @test */
	public function test_online_checkout_enqueues_sync_queue_row(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote();
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );

		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );
		$this->bookings->shouldReceive( 'insert' )->once()->andReturn( 1 );

		$this->status->shouldReceive( 'transition' )->once()->with( 1, 'ea-processing', 'pending' )->andReturn( true );
		$this->sync_queue->shouldReceive( 'enqueue' )->once()->with(
			1,
			'stripe',
			null,
			\Mockery::type( \DateTimeImmutable::class )
		);

		$result = $this->svc->create_checkout( $this->make_args( array( 'payment_method' => 'stripe' ) ) );

		$this->assertTrue( $result->success );
		$this->assertSame( 1, $result->booking_id );
		$this->assertSame( 'redirect', $result->next_step );
	}

	// ─── Offline checkout ───────────────────────────────────────────

	/** @test */
	public function test_offline_checkout_does_not_enqueue_sync(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote();
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );

		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );
		$this->bookings->shouldReceive( 'insert' )->once()->andReturn( 1 );

		$this->status->shouldReceive( 'transition' )->once()->with( 1, 'ea-processing', 'pending' )->andReturn( true );
		$this->sync_queue->shouldNotReceive( 'enqueue' );

		$result = $this->svc->create_checkout( $this->make_args( array( 'payment_method' => 'manual' ) ) );

		$this->assertTrue( $result->success );
		$this->assertSame( 'offline_pending', $result->next_step );
	}

	// ─── Hold expiry ────────────────────────────────────────────────

	/** @test */
	public function test_hold_expiry_30_min_for_paypal(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote();
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );
		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );

		$this->bookings->shouldReceive( 'insert' )
			->once()
			->with( \Mockery::on( function ( array $data ) {
				// Hold should be ~30 min from now (1800 seconds).
				$expiry  = strtotime( $data['hold_expires_at_gmt'] );
				$expected = time() + 1800;
				$this->assertEqualsWithDelta( $expected, $expiry, 5 );
				return true;
			} ) )
			->andReturn( 1 );

		$this->status->shouldReceive( 'transition' )->once()->andReturn( true );
		$this->sync_queue->shouldReceive( 'enqueue' )->once();

		$this->svc->create_checkout( $this->make_args( array( 'payment_method' => 'paypal' ) ) );
	}

	/** @test */
	public function test_hold_expiry_48_hours_for_manual(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote();
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );
		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );

		$this->bookings->shouldReceive( 'insert' )
			->once()
			->with( \Mockery::on( function ( array $data ) {
				// Hold should be ~48 hours from now (172800 seconds).
				$expiry   = strtotime( $data['hold_expires_at_gmt'] );
				$expected = time() + 172800;
				$this->assertEqualsWithDelta( $expected, $expiry, 5 );
				return true;
			} ) )
			->andReturn( 1 );

		$this->status->shouldReceive( 'transition' )->once()->andReturn( true );

		$this->svc->create_checkout( $this->make_args( array( 'payment_method' => 'manual' ) ) );
	}

	/** @test */
	public function test_hold_expiry_null_for_free(): void {
		$this->bookings->shouldReceive( 'find_by_idempotency_key' )->andReturn( null );

		$quote = $this->make_quote( array( 'total' => '0.0000' ) );
		$this->quotes->shouldReceive( 'quote' )->once()->andReturn( $quote );
		$this->inventory->shouldReceive( 'reserve' )->once()->andReturn( true );

		$this->bookings->shouldReceive( 'insert' )
			->once()
			->with( \Mockery::on( function ( array $data ) {
				$this->assertNull( $data['hold_expires_at_gmt'] );
				return true;
			} ) )
			->andReturn( 1 );

		$this->status->shouldReceive( 'mark_completed' )->once()->andReturn( true );

		$this->svc->create_checkout( $this->make_args( array( 'payment_method' => '' ) ) );
	}
}
