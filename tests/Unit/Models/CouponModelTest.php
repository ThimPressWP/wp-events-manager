<?php
namespace WPEMS\Tests\Unit\Models;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use WPEMS\Models\CouponModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Models\CouponModel
 */
class CouponModelTest extends TestCase {

	private function fullRow( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                  => 1,
				'code'                => 'SAVE10',
				'description'         => 'Test coupon',
				'discount_type'       => CouponModel::TYPE_PERCENT,
				'percent_value'       => '10.0000',
				'amount_value'        => null,
				'max_discount_amount' => null,
				'applies_to'          => CouponModel::APPLIES_ALL,
				'usage_limit'         => 100,
				'usage_count'         => 5,
				'usage_limit_per_user' => 1,
				'min_order_amount'    => '50.0000',
				'starts_at_gmt'       => '2025-01-01 00:00:00',
				'expires_at_gmt'      => '2099-12-31 23:59:59',
				'status'              => CouponModel::STATUS_ACTIVE,
				'created_at_gmt'      => '2025-01-01 00:00:00',
				'updated_at_gmt'      => '2025-01-01 00:00:00',
			),
			$overrides
		);
	}

	public function test_from_row_populates_all_properties(): void {
		$row   = $this->fullRow();
		$model = CouponModel::from_row( $row );

		$this->assertSame( 1, $model->get_id() );
		$this->assertSame( 'SAVE10', $model->get_code() );
		$this->assertSame( 'Test coupon', $model->get_description() );
		$this->assertSame( CouponModel::TYPE_PERCENT, $model->get_discount_type() );
		$this->assertSame( '10.0000', $model->get_percent_value() );
		$this->assertNull( $model->get_amount_value() );
		$this->assertNull( $model->get_max_discount_amount() );
		$this->assertSame( CouponModel::APPLIES_ALL, $model->get_applies_to() );
		$this->assertSame( 100, $model->get_usage_limit() );
		$this->assertSame( 5, $model->get_usage_count() );
		$this->assertSame( 1, $model->get_usage_limit_per_user() );
		$this->assertSame( '50.0000', $model->get_min_order_amount() );
		$this->assertSame( '2025-01-01 00:00:00', $model->get_starts_at_gmt() );
		$this->assertSame( '2099-12-31 23:59:59', $model->get_expires_at_gmt() );
		$this->assertSame( CouponModel::STATUS_ACTIVE, $model->get_status() );
	}

	public function test_from_row_nullable_dates(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'starts_at_gmt'  => null,
			'expires_at_gmt' => null,
		) ) );

		$this->assertNull( $model->get_starts_at_gmt() );
		$this->assertNull( $model->get_expires_at_gmt() );
	}

	public function test_from_row_missing_id_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		CouponModel::from_row( array( 'code' => 'X' ) );
	}

	public function test_is_active_true_when_status_active(): void {
		$model = CouponModel::from_row( $this->fullRow( array( 'status' => 'active' ) ) );
		$this->assertTrue( $model->is_active() );
	}

	public function test_is_active_false_when_inactive(): void {
		$model = CouponModel::from_row( $this->fullRow( array( 'status' => 'inactive' ) ) );
		$this->assertFalse( $model->is_active() );
	}

	public function test_is_in_window_before_start_returns_false(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'starts_at_gmt' => '2099-01-01 00:00:00',
		) ) );
		$now = new DateTimeImmutable( '2026-06-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertFalse( $model->is_in_window( $now ) );
	}

	public function test_is_in_window_after_end_returns_false(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'expires_at_gmt' => '2020-01-01 00:00:00',
		) ) );
		$now = new DateTimeImmutable( '2026-06-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertFalse( $model->is_in_window( $now ) );
	}

	public function test_is_in_window_null_bounds_returns_true(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'starts_at_gmt'  => null,
			'expires_at_gmt' => null,
		) ) );
		$now = new DateTimeImmutable( '2026-06-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertTrue( $model->is_in_window( $now ) );
	}

	public function test_is_in_window_within_bounds_returns_true(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'starts_at_gmt'  => '2025-01-01 00:00:00',
			'expires_at_gmt' => '2099-12-31 23:59:59',
		) ) );
		$now = new DateTimeImmutable( '2026-06-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertTrue( $model->is_in_window( $now ) );
	}

	public function test_has_global_capacity_null_limit(): void {
		$model = CouponModel::from_row( $this->fullRow( array( 'usage_limit' => null ) ) );
		$this->assertTrue( $model->has_global_capacity() );
	}

	public function test_has_global_capacity_at_limit(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'usage_limit' => 5,
			'usage_count' => 5,
		) ) );
		$this->assertFalse( $model->has_global_capacity() );
	}

	public function test_has_global_capacity_under_limit(): void {
		$model = CouponModel::from_row( $this->fullRow( array(
			'usage_limit' => 10,
			'usage_count' => 3,
		) ) );
		$this->assertTrue( $model->has_global_capacity() );
	}

	public function test_to_array_round_trip(): void {
		$row   = $this->fullRow();
		$model = CouponModel::from_row( $row );
		$this->assertSame( $row, $model->to_array() );
	}
}
