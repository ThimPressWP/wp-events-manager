<?php
/**
 * Unit tests for WPEMS\Pricing\CheckoutQuote.
 *
 * @package WPEMS\Tests\Unit\Pricing
 */

namespace WPEMS\Tests\Unit\Pricing;

use WPEMS\Pricing\CheckoutQuote;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Pricing\CheckoutQuote
 */
class CheckoutQuoteTest extends TestCase {

	/** @test */
	public function test_constructor_round_trip_through_to_array(): void {
		$quote = new CheckoutQuote(
			'200.0000',
			'10.0000',
			'10.0000',
			'19.0000',
			'209.0000',
			'USD',
			5,
			'SAVE10',
			'VAT'
		);

		$arr = $quote->to_array();

		$this->assertSame( '200.0000', $arr['subtotal'] );
		$this->assertSame( '10.0000', $arr['discount_total'] );
		$this->assertSame( '10.0000', $arr['tax_rate'] );
		$this->assertSame( '19.0000', $arr['tax_total'] );
		$this->assertSame( '209.0000', $arr['total'] );
		$this->assertSame( 'USD', $arr['currency'] );
		$this->assertSame( 5, $arr['coupon_id'] );
		$this->assertSame( 'SAVE10', $arr['coupon_code'] );
		$this->assertSame( 'VAT', $arr['tax_label'] );

		// Getters match.
		$this->assertSame( '200.0000', $quote->get_subtotal() );
		$this->assertSame( '10.0000', $quote->get_discount_total() );
		$this->assertSame( 'USD', $quote->get_currency() );
		$this->assertSame( 5, $quote->get_coupon_id() );
		$this->assertSame( 'VAT', $quote->get_tax_label() );
	}

	/** @test */
	public function test_nullable_coupon_fields_preserved(): void {
		$quote = new CheckoutQuote(
			'100.0000',
			'0.0000',
			'0.0000',
			'0.0000',
			'100.0000',
			'EUR',
			null,
			null,
			'Tax'
		);

		$arr = $quote->to_array();

		$this->assertNull( $arr['coupon_id'] );
		$this->assertNull( $arr['coupon_code'] );
		$this->assertNull( $quote->get_coupon_id() );
		$this->assertNull( $quote->get_coupon_code() );
	}
}
