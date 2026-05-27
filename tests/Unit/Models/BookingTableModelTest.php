<?php
namespace WPEMS\Tests\Unit\Models;

use InvalidArgumentException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Models\BookingTableModel
 */
class BookingTableModelTest extends TestCase {

	private function fullRow( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                  => 42,
				'legacy_post_id'      => 999,
				'event_id'            => 10,
				'user_id'             => 5,
				'qty'                 => 2,
				'subtotal'            => '100.0000',
				'discount_total'      => '10.0000',
				'tax_rate'            => '8.5000',
				'tax_total'           => '7.6500',
				'total'               => '97.6500',
				'currency'            => 'USD',
				'coupon_id'           => 3,
				'payment_method'      => 'paypal',
				'payment_mode'        => 'paypal_rest',
				'gateway_order_id'    => 'ORD-123',
				'status'              => 'ea-pending',
				'payment_status'      => 'unpaid',
				'hold_expires_at_gmt' => '2026-01-01 01:00:00',
				'idempotency_key'     => 'abc123',
				'created_at_gmt'      => '2026-01-01 00:00:00',
				'updated_at_gmt'      => '2026-01-01 00:00:00',
			),
			$overrides
		);
	}

	public function test_from_row_populates_all_properties(): void {
		$row   = $this->fullRow();
		$model = BookingTableModel::from_row( $row );

		$this->assertSame( 42, $model->get_id() );
		$this->assertSame( 999, $model->get_legacy_post_id() );
		$this->assertSame( 10, $model->get_event_id() );
		$this->assertSame( 5, $model->get_user_id() );
		$this->assertSame( 2, $model->get_qty() );
		$this->assertSame( '100.0000', $model->get_subtotal() );
		$this->assertSame( '10.0000', $model->get_discount_total() );
		$this->assertSame( '8.5000', $model->get_tax_rate() );
		$this->assertSame( '7.6500', $model->get_tax_total() );
		$this->assertSame( '97.6500', $model->get_total() );
		$this->assertSame( 'USD', $model->get_currency() );
		$this->assertSame( 3, $model->get_coupon_id() );
		$this->assertSame( 'paypal', $model->get_payment_method() );
		$this->assertSame( 'paypal_rest', $model->get_payment_mode() );
		$this->assertSame( 'ORD-123', $model->get_gateway_order_id() );
		$this->assertSame( 'ea-pending', $model->get_status() );
		$this->assertSame( 'unpaid', $model->get_payment_status() );
		$this->assertSame( '2026-01-01 01:00:00', $model->get_hold_expires_at_gmt() );
		$this->assertSame( 'abc123', $model->get_idempotency_key() );
		$this->assertSame( '2026-01-01 00:00:00', $model->get_created_at_gmt() );
		$this->assertSame( '2026-01-01 00:00:00', $model->get_updated_at_gmt() );
	}

	public function test_from_row_handles_nullable_columns(): void {
		$row   = $this->fullRow( array(
			'legacy_post_id'      => null,
			'coupon_id'           => null,
			'payment_mode'        => null,
			'gateway_order_id'    => null,
			'hold_expires_at_gmt' => null,
			'idempotency_key'     => null,
		) );
		$model = BookingTableModel::from_row( $row );

		$this->assertNull( $model->get_legacy_post_id() );
		$this->assertNull( $model->get_coupon_id() );
		$this->assertNull( $model->get_payment_mode() );
		$this->assertNull( $model->get_gateway_order_id() );
		$this->assertNull( $model->get_hold_expires_at_gmt() );
		$this->assertNull( $model->get_idempotency_key() );
	}

	public function test_from_row_missing_id_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		BookingTableModel::from_row( array( 'event_id' => 1 ) );
	}

	public function test_is_paid_true_when_payment_status_is_paid(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'payment_status' => 'paid' ) ) );
		$this->assertTrue( $model->is_paid() );
	}

	public function test_is_paid_false_when_unpaid(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'payment_status' => 'unpaid' ) ) );
		$this->assertFalse( $model->is_paid() );
	}

	/**
	 * @dataProvider terminalStatusProvider
	 */
	public function test_is_terminal_for_each_terminal_status( string $status ): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'status' => $status ) ) );
		$this->assertTrue( $model->is_terminal() );
	}

	public function terminalStatusProvider(): array {
		return array(
			array( 'ea-completed' ),
			array( 'ea-cancelled' ),
			array( 'ea-failed' ),
			array( 'ea-expired' ),
			array( 'ea-refunded' ),
		);
	}

	public function test_is_terminal_false_for_pending(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'status' => 'ea-pending' ) ) );
		$this->assertFalse( $model->is_terminal() );
	}

	public function test_requires_payment_sync_true_for_pending_paypal(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array(
			'payment_method' => 'paypal',
			'payment_status' => 'pending',
			'status'         => 'ea-processing',
		) ) );
		$this->assertTrue( $model->requires_payment_sync() );
	}

	public function test_requires_payment_sync_false_for_paid(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array(
			'payment_method' => 'paypal',
			'payment_status' => 'paid',
			'status'         => 'ea-completed',
		) ) );
		$this->assertFalse( $model->requires_payment_sync() );
	}

	public function test_requires_payment_sync_false_for_manual(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array(
			'payment_method' => 'manual',
			'payment_status' => 'pending',
			'status'         => 'ea-processing',
		) ) );
		$this->assertFalse( $model->requires_payment_sync() );
	}

	public function test_is_migrated_from_legacy_true(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'legacy_post_id' => 123 ) ) );
		$this->assertTrue( $model->is_migrated_from_legacy() );
	}

	public function test_is_migrated_from_legacy_false(): void {
		$model = BookingTableModel::from_row( $this->fullRow( array( 'legacy_post_id' => null ) ) );
		$this->assertFalse( $model->is_migrated_from_legacy() );
	}

	public function test_to_array_round_trip(): void {
		$row   = $this->fullRow();
		$model = BookingTableModel::from_row( $row );

		$this->assertSame( $row, $model->to_array() );
	}
}
