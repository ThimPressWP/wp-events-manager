<?php
namespace WPEMS\Tests\Unit\Models;

use WPEMS\Models\BookingRefundSummary;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Models\BookingRefundSummary
 */
class BookingRefundSummaryTest extends TestCase {

	public function test_none_returns_zero_and_not_refunded(): void {
		$summary = BookingRefundSummary::none();

		$this->assertSame( '0.0000', $summary->get_refunded_total() );
		$this->assertSame( BookingRefundSummary::STATUS_NOT_REFUNDED, $summary->get_refund_status() );
		$this->assertNull( $summary->get_payment_completed_at_gmt() );
		$this->assertFalse( $summary->is_fully_refunded() );
		$this->assertFalse( $summary->is_partially_refunded() );
	}

	public function test_from_totals_zero_refund_returns_not_refunded(): void {
		$summary = BookingRefundSummary::from_totals( '0', '100.0000', '2026-01-01 00:00:00' );

		$this->assertSame( '0.0000', $summary->get_refunded_total() );
		$this->assertSame( BookingRefundSummary::STATUS_NOT_REFUNDED, $summary->get_refund_status() );
		$this->assertFalse( $summary->is_fully_refunded() );
		$this->assertFalse( $summary->is_partially_refunded() );
	}

	public function test_from_totals_partial_refund_returns_partial(): void {
		$summary = BookingRefundSummary::from_totals( '40.0000', '100.0000', '2026-01-01 00:00:00' );

		$this->assertSame( '40.0000', $summary->get_refunded_total() );
		$this->assertSame( BookingRefundSummary::STATUS_PARTIAL, $summary->get_refund_status() );
		$this->assertFalse( $summary->is_fully_refunded() );
		$this->assertTrue( $summary->is_partially_refunded() );
	}

	public function test_from_totals_full_refund_returns_refunded(): void {
		$summary = BookingRefundSummary::from_totals( '100.0000', '100.0000', '2026-01-01 00:00:00' );

		$this->assertSame( '100.0000', $summary->get_refunded_total() );
		$this->assertSame( BookingRefundSummary::STATUS_REFUNDED, $summary->get_refund_status() );
		$this->assertTrue( $summary->is_fully_refunded() );
		$this->assertFalse( $summary->is_partially_refunded() );
	}

	public function test_from_totals_over_refund_returns_refunded(): void {
		$summary = BookingRefundSummary::from_totals( '110.0000', '100.0000', '2026-01-01 00:00:00' );

		$this->assertSame( '110.0000', $summary->get_refunded_total() );
		$this->assertSame( BookingRefundSummary::STATUS_REFUNDED, $summary->get_refund_status() );
		$this->assertTrue( $summary->is_fully_refunded() );
	}

	public function test_from_totals_normalises_to_four_decimals(): void {
		$summary = BookingRefundSummary::from_totals( '10.5', '100.0000', null );

		$this->assertSame( '10.5000', $summary->get_refunded_total() );
	}

	public function test_to_array(): void {
		$summary = BookingRefundSummary::from_totals( '50.0000', '100.0000', '2026-01-01 12:00:00' );

		$expected = array(
			'refunded_total'           => '50.0000',
			'refund_status'            => BookingRefundSummary::STATUS_PARTIAL,
			'payment_completed_at_gmt' => '2026-01-01 12:00:00',
		);

		$this->assertSame( $expected, $summary->to_array() );
	}
}
