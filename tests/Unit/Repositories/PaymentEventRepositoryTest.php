<?php
/**
 * Unit tests for WPEMS\Repositories\PaymentEventRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use InvalidArgumentException;
use WPEMS\Repositories\PaymentEventRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\PaymentEventRepository
 */
class PaymentEventRepositoryTest extends TestCase {

	private PaymentEventRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';
		$wpdb->insert_id = 0;
		$GLOBALS['wpdb'] = $wpdb;
		$this->repo = new PaymentEventRepository();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	// ─── was_processed() ────────────────────────────────────────────

	/** @test */
	public function test_was_processed_returns_false_for_empty_gateway(): void {
		$this->assertFalse( $this->repo->was_processed( '', 'evt_123' ) );
	}

	/** @test */
	public function test_was_processed_returns_false_for_empty_event(): void {
		$this->assertFalse( $this->repo->was_processed( 'stripe', '' ) );
	}

	/** @test */
	public function test_was_processed_returns_false_for_unseen(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $m;

		$this->assertFalse( $this->repo->was_processed( 'stripe', 'evt_new' ) );
	}

	/** @test */
	public function test_was_processed_returns_true_after_record(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '1' );
		$GLOBALS['wpdb'] = $m;

		$this->assertTrue( $this->repo->was_processed( 'stripe', 'evt_123' ) );
	}

	// ─── record() ───────────────────────────────────────────────────

	/** @test */
	public function test_record_validates_required_keys(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'gateway_id' );

		$this->repo->record( array( 'event_id' => 'e', 'event_type' => 't' ) );
	}

	/** @test */
	public function test_record_returns_new_id_on_insert(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 42;

		$m->shouldReceive( 'suppress_errors' )->twice();
		$m->shouldReceive( 'insert' )->once()->andReturn( true );

		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->record(
			array(
				'gateway_id' => 'stripe',
				'event_id'   => 'evt_abc',
				'event_type' => 'payment_intent.succeeded',
			)
		);

		$this->assertSame( 42, $id );
	}

	/** @test */
	public function test_record_is_idempotent_on_same_event_id(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 0; // Duplicate — no new ID.

		$m->shouldReceive( 'suppress_errors' )->twice();
		$m->shouldReceive( 'insert' )->once()->andReturn( false );

		// Fetch existing.
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '42' );

		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->record(
			array(
				'gateway_id' => 'stripe',
				'event_id'   => 'evt_abc',
				'event_type' => 'payment_intent.succeeded',
			)
		);

		$this->assertSame( 42, $id );
	}

	/** @test */
	public function test_record_computes_payload_hash(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 50;

		$m->shouldReceive( 'suppress_errors' )->twice();
		$m->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_wpems_payment_events',
				\Mockery::on( function ( array $data ) {
					$this->assertNotNull( $data['payload_hash'] );
					$this->assertSame( 64, strlen( $data['payload_hash'] ) ); // SHA-256 hex.
					$this->assertArrayNotHasKey( 'raw_payload', $data );
					return true;
				} )
			)
			->andReturn( true );

		$GLOBALS['wpdb'] = $m;

		$this->repo->record(
			array(
				'gateway_id'  => 'paypal',
				'event_id'    => 'WH-123',
				'event_type'  => 'PAYMENT.CAPTURE.COMPLETED',
				'raw_payload' => '{"id":"WH-123","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
			)
		);
	}
}
