<?php
/**
 * Unit tests for WPEMS\Repositories\PaymentTransactionRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use Brain\Monkey\Functions;
use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\BookingRefundSummary;
use WPEMS\Models\PaymentTransactionModel;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\PaymentTransactionRepository
 */
class PaymentTransactionRepositoryTest extends TestCase {

	private PaymentTransactionRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';
		$wpdb->insert_id = 0;
		$wpdb->rows_affected = 0;
		$GLOBALS['wpdb'] = $wpdb;
		$this->repo = new PaymentTransactionRepository();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function make_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                        => 1,
				'booking_id'                => 100,
				'type'                      => 'paypal_capture',
				'gateway_transaction_id'    => 'TXN-123',
				'gateway_capture_id'        => null,
				'gateway_charge_id'         => null,
				'gateway_payment_intent_id' => null,
				'parent_transaction_id'     => null,
				'amount'                    => '50.0000',
				'currency'                  => 'USD',
				'status'                    => 'completed',
				'raw_response'              => '{"ok":true}',
				'created_at_gmt'            => '2026-01-01 00:00:00',
			),
			$overrides
		);
	}

	// ─── insert() ───────────────────────────────────────────────────

	/** @test */
	public function test_insert_validates_required_keys(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'booking_id' );
		$this->repo->insert( array( 'type' => 'paypal_capture' ) );
	}

	/** @test */
	public function test_insert_encodes_array_raw_response(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 10;

		$m->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_wpems_payment_transactions',
				\Mockery::on( function ( array $data ) {
					// raw_response should be JSON string, not array.
					$this->assertIsString( $data['raw_response'] );
					$decoded = json_decode( $data['raw_response'], true );
					$this->assertSame( 'bar', $decoded['foo'] );
					return true;
				} )
			)
			->andReturn( true );

		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->insert(
			array(
				'booking_id'   => 100,
				'type'         => 'paypal_capture',
				'amount'       => '50.0000',
				'currency'     => 'USD',
				'status'       => 'pending',
				'raw_response' => array( 'foo' => 'bar' ),
			)
		);

		$this->assertSame( 10, $id );
	}

	/** @test */
	public function test_insert_throws_on_db_error(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = 'DB error';
		$m->insert_id = 0;
		$m->shouldReceive( 'insert' )->once()->andReturn( false );
		$GLOBALS['wpdb'] = $m;

		$this->expectException( RuntimeException::class );
		$this->repo->insert(
			array(
				'booking_id' => 100,
				'type'       => 'paypal_capture',
				'amount'     => '50.0000',
				'currency'   => 'USD',
				'status'     => 'pending',
			)
		);
	}

	// ─── find_by_txn_id() ───────────────────────────────────────────

	/** @test */
	public function test_find_by_txn_id_returns_null_for_empty(): void {
		$this->assertNull( $this->repo->find_by_txn_id( '', 'TXN' ) );
		$this->assertNull( $this->repo->find_by_txn_id( 'paypal_capture', '' ) );
	}

	/** @test */
	public function test_find_by_txn_id_returns_correct_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row() );
		$GLOBALS['wpdb'] = $m;

		$model = $this->repo->find_by_txn_id( 'paypal_capture', 'TXN-123' );
		$this->assertInstanceOf( PaymentTransactionModel::class, $model );
		$this->assertSame( 'TXN-123', $model->get_gateway_transaction_id() );
	}

	// ─── mark_completed() ───────────────────────────────────────────

	/** @test */
	public function test_mark_completed_updates_status(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'update' )
			->once()
			->with(
				'wp_wpems_payment_transactions',
				\Mockery::on( function ( array $data ) {
					$this->assertSame( 'completed', $data['status'] );
					return true;
				} ),
				array( 'id' => 5 )
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $m;

		$this->assertTrue( $this->repo->mark_completed( 5 ) );
	}

	// ─── record_refund() ────────────────────────────────────────────

	/** @test */
	public function test_record_refund_negates_amount(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 20;

		// find() for parent.
		$m->shouldReceive( 'prepare' )->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row() );

		// insert() for refund.
		$m->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_wpems_payment_transactions',
				\Mockery::on( function ( array $data ) {
					$this->assertStringStartsWith( '-', $data['amount'] );
					return true;
				} )
			)
			->andReturn( true );

		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->record_refund( 1, '50.0000', 'REFUND-1', array( 'ok' => true ) );
		$this->assertSame( 20, $id );
	}

	/** @test */
	public function test_record_refund_classifies_full_vs_partial(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		// Full refund (same amount).
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 30;
		$m->shouldReceive( 'prepare' )->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row( array( 'amount' => '50.0000' ) ) );
		$m->shouldReceive( 'insert' )
			->once()
			->with(
				\Mockery::any(),
				\Mockery::on( function ( array $data ) {
					$this->assertSame( 'refund', $data['type'] );
					return true;
				} )
			)
			->andReturn( true );
		$GLOBALS['wpdb'] = $m;
		$this->repo->record_refund( 1, '50.0000', 'R1', array() );

		// Partial refund (lesser amount).
		$m2 = \Mockery::mock( 'wpdb' );
		$m2->prefix = 'wp_';
		$m2->last_error = '';
		$m2->insert_id = 31;
		$m2->shouldReceive( 'prepare' )->andReturn( 'SQL' );
		$m2->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row( array( 'amount' => '50.0000' ) ) );
		$m2->shouldReceive( 'insert' )
			->once()
			->with(
				\Mockery::any(),
				\Mockery::on( function ( array $data ) {
					$this->assertSame( 'partial_refund', $data['type'] );
					return true;
				} )
			)
			->andReturn( true );
		$GLOBALS['wpdb'] = $m2;
		$this->repo->record_refund( 1, '25.0000', 'R2', array() );
	}

	/** @test */
	public function test_record_refund_throws_for_missing_parent(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $m;

		$this->expectException( RuntimeException::class );
		$this->repo->record_refund( 999, '10.0000', 'R', array() );
	}

	// ─── get_refund_summary() ───────────────────────────────────────

	/** @test */
	public function test_get_refund_summary_returns_none_for_no_transactions(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'refunded_total' => '0', 'first_completed' => null )
		);
		$GLOBALS['wpdb'] = $m;

		$summary = $this->repo->get_refund_summary( 100, '50.0000' );
		$this->assertInstanceOf( BookingRefundSummary::class, $summary );
		$this->assertSame( BookingRefundSummary::STATUS_NOT_REFUNDED, $summary->get_refund_status() );
	}

	/** @test */
	public function test_get_refund_summary_returns_partial(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'refunded_total' => '25.0000', 'first_completed' => '2026-01-01 12:00:00' )
		);
		$GLOBALS['wpdb'] = $m;

		$summary = $this->repo->get_refund_summary( 100, '50.0000' );
		$this->assertSame( BookingRefundSummary::STATUS_PARTIAL, $summary->get_refund_status() );
	}

	/** @test */
	public function test_get_refund_summary_returns_refunded(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'refunded_total' => '50.0000', 'first_completed' => '2026-01-01 12:00:00' )
		);
		$GLOBALS['wpdb'] = $m;

		$summary = $this->repo->get_refund_summary( 100, '50.0000' );
		$this->assertSame( BookingRefundSummary::STATUS_REFUNDED, $summary->get_refund_status() );
	}
}
