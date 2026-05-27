<?php
namespace WPEMS\Tests\Unit\Models;

use InvalidArgumentException;
use WPEMS\Models\PaymentTransactionModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Models\PaymentTransactionModel
 */
class PaymentTransactionModelTest extends TestCase {

	private function captureRow( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                        => 1,
				'booking_id'                => 42,
				'type'                      => PaymentTransactionModel::TYPE_PAYPAL_CAPTURE,
				'gateway_transaction_id'    => 'TXN-001',
				'gateway_capture_id'        => 'CAP-001',
				'gateway_charge_id'         => null,
				'gateway_payment_intent_id' => null,
				'parent_transaction_id'     => null,
				'amount'                    => '99.5000',
				'currency'                  => 'USD',
				'status'                    => PaymentTransactionModel::STATUS_COMPLETED,
				'raw_response'              => '{"id":"CAP-001"}',
				'created_at_gmt'            => '2026-01-01 00:00:00',
			),
			$overrides
		);
	}

	public function test_from_row_full_capture_row(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow() );

		$this->assertSame( 1, $model->get_id() );
		$this->assertSame( 42, $model->get_booking_id() );
		$this->assertSame( PaymentTransactionModel::TYPE_PAYPAL_CAPTURE, $model->get_type() );
		$this->assertSame( 'TXN-001', $model->get_gateway_transaction_id() );
		$this->assertSame( 'CAP-001', $model->get_gateway_capture_id() );
		$this->assertNull( $model->get_gateway_charge_id() );
		$this->assertNull( $model->get_gateway_payment_intent_id() );
		$this->assertNull( $model->get_parent_transaction_id() );
		$this->assertSame( '99.5000', $model->get_amount() );
		$this->assertSame( 'USD', $model->get_currency() );
		$this->assertSame( PaymentTransactionModel::STATUS_COMPLETED, $model->get_status() );
		$this->assertSame( '2026-01-01 00:00:00', $model->get_created_at_gmt() );
	}

	public function test_from_row_refund_row_has_negative_amount(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'type'                  => PaymentTransactionModel::TYPE_REFUND,
			'amount'                => '-50.0000',
			'parent_transaction_id' => 1,
		) ) );

		$this->assertSame( '-50.0000', $model->get_amount() );
		$this->assertSame( 1, $model->get_parent_transaction_id() );
	}

	public function test_from_row_missing_id_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		PaymentTransactionModel::from_row( array( 'booking_id' => 1 ) );
	}

	public function test_from_row_missing_booking_id_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		PaymentTransactionModel::from_row( array( 'id' => 1 ) );
	}

	public function test_is_refund_true_for_refund_type(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'type' => PaymentTransactionModel::TYPE_REFUND,
		) ) );
		$this->assertTrue( $model->is_refund() );
	}

	public function test_is_refund_true_for_partial_refund_type(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'type' => PaymentTransactionModel::TYPE_PARTIAL_REFUND,
		) ) );
		$this->assertTrue( $model->is_refund() );
	}

	public function test_is_refund_false_for_capture(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow() );
		$this->assertFalse( $model->is_refund() );
	}

	public function test_is_completed_true(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'status' => PaymentTransactionModel::STATUS_COMPLETED,
		) ) );
		$this->assertTrue( $model->is_completed() );
	}

	public function test_is_completed_false_for_pending(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'status' => PaymentTransactionModel::STATUS_PENDING,
		) ) );
		$this->assertFalse( $model->is_completed() );
	}

	public function test_decode_raw_response_returns_array(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'raw_response' => '{"id":"CAP-001","status":"COMPLETED"}',
		) ) );
		$decoded = $model->decode_raw_response();
		$this->assertSame( 'CAP-001', $decoded['id'] );
		$this->assertSame( 'COMPLETED', $decoded['status'] );
	}

	public function test_decode_raw_response_invalid_json_returns_empty_array(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'raw_response' => 'not json',
		) ) );
		$this->assertSame( array(), $model->decode_raw_response() );
	}

	public function test_decode_raw_response_null_returns_empty_array(): void {
		$model = PaymentTransactionModel::from_row( $this->captureRow( array(
			'raw_response' => null,
		) ) );
		$this->assertSame( array(), $model->decode_raw_response() );
	}

	public function test_to_array_round_trip(): void {
		$row   = $this->captureRow();
		$model = PaymentTransactionModel::from_row( $row );
		$this->assertSame( $row, $model->to_array() );
	}
}
