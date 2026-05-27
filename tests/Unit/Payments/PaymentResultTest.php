<?php
/**
 * Unit tests for WPEMS\Payments\PaymentResult.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use WPEMS\Payments\PaymentResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Payments\PaymentResult
 */
class PaymentResultTest extends TestCase {

	/** @test */
	public function test_paid_factory_marks_success_true(): void {
		$txn = array(
			'type'                   => 'charge',
			'gateway_transaction_id' => 'pi_123',
			'amount'                 => '100.0000',
			'currency'               => 'USD',
			'status'                 => 'completed',
		);

		$result = PaymentResult::paid( 42, $txn, 'evt_abc' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 42, $result->get_booking_id() );
		$this->assertSame( '', $result->get_message() );
		$this->assertSame( $txn, $result->get_transaction_data() );
		$this->assertSame( 'evt_abc', $result->get_gateway_event_id() );
	}

	/** @test */
	public function test_failed_factory_marks_success_false(): void {
		$result = PaymentResult::failed( 10, 'Card declined', 'evt_xyz' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
		$this->assertSame( 10, $result->get_booking_id() );
		$this->assertSame( 'Card declined', $result->get_message() );
		$this->assertSame( array(), $result->get_transaction_data() );
		$this->assertSame( 'evt_xyz', $result->get_gateway_event_id() );
	}

	/** @test */
	public function test_pending_factory_marks_success_true_but_status_pending(): void {
		$result = PaymentResult::pending( 5, 'Awaiting confirmation', 'evt_pend' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_PENDING, $result->get_status() );
		$this->assertSame( 5, $result->get_booking_id() );
		$this->assertSame( 'Awaiting confirmation', $result->get_message() );
		$this->assertSame( 'evt_pend', $result->get_gateway_event_id() );
	}

	/** @test */
	public function test_cancelled_factory_marks_success_false(): void {
		$result = PaymentResult::cancelled( 7, 'User cancelled' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_CANCELLED, $result->get_status() );
		$this->assertSame( 7, $result->get_booking_id() );
		$this->assertSame( 'User cancelled', $result->get_message() );
	}

	/** @test */
	public function test_refunded_factory_marks_success_true(): void {
		$txn = array(
			'type'                   => 'refund',
			'gateway_transaction_id' => 're_456',
			'amount'                 => '50.0000',
			'currency'               => 'USD',
			'status'                 => 'completed',
		);

		$result = PaymentResult::refunded( 20, $txn, 'evt_ref' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_REFUNDED, $result->get_status() );
		$this->assertSame( 20, $result->get_booking_id() );
		$this->assertSame( $txn, $result->get_transaction_data() );
		$this->assertSame( 'evt_ref', $result->get_gateway_event_id() );
	}

	/** @test */
	public function test_unknown_factory_marks_success_false(): void {
		$result = PaymentResult::unknown( 99, 'Unrecognised status: xyz' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 99, $result->get_booking_id() );
		$this->assertSame( 'Unrecognised status: xyz', $result->get_message() );
		$this->assertNull( $result->get_gateway_event_id() );
	}

	/** @test */
	public function test_gateway_event_id_optional(): void {
		// paid without gateway_event_id.
		$result = PaymentResult::paid( 1, array() );
		$this->assertNull( $result->get_gateway_event_id() );

		// pending without gateway_event_id.
		$result2 = PaymentResult::pending( 1 );
		$this->assertNull( $result2->get_gateway_event_id() );
	}

	/** @test */
	public function test_transaction_data_round_trip(): void {
		$txn = array(
			'type'                   => 'charge',
			'gateway_transaction_id' => 'pi_roundtrip',
			'amount'                 => '250.5000',
			'currency'               => 'EUR',
			'status'                 => 'completed',
			'extra_field'            => 'preserved',
		);

		$result = PaymentResult::paid( 1, $txn );

		$this->assertSame( $txn, $result->get_transaction_data() );
		$this->assertArrayHasKey( 'extra_field', $result->get_transaction_data() );
	}
}
