<?php
namespace WPEMS\Tests\Unit\Frontend;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Frontend\CheckoutAjax;
use WPEMS\Models\CouponModel;
use WPEMS\Pricing\CheckoutQuote;
use WPEMS\Services\BookingCheckoutService;
use WPEMS\Services\CheckoutDispatchResult;
use WPEMS\Services\CheckoutQuoteService;
use WPEMS\Services\CouponService;
use WPEMS\Services\CouponValidationResult;
use WPEMS\Tests\Unit\TestCase;

class CheckoutAjaxTest extends TestCase {

	private $quotes, $checkout, $coupons, $ajax;
	private $sentData, $sentError;

	protected function setUp(): void {
		parent::setUp();

		$this->quotes   = Mockery::mock( CheckoutQuoteService::class );
		$this->checkout = Mockery::mock( BookingCheckoutService::class );
		$this->coupons  = Mockery::mock( CouponService::class );
		$this->ajax     = new CheckoutAjax( $this->quotes, $this->checkout, $this->coupons );
		$this->sentData  = null;
		$this->sentError = null;

		Functions\when( 'wp_send_json_success' )->alias( function ( $d ) { throw new \RuntimeException( 'ok:' . json_encode( $d ) ); } );
		Functions\when( 'wp_send_json_error' )->alias( function ( $d, $c = 400 ) { throw new \RuntimeException( 'err:' . $c . ':' . json_encode( $d ) ); } );
		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->alias( fn( $v ) => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'sanitize_key' )->alias( fn( $v ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ) );
		Functions\when( 'get_current_user_id' )->justReturn( 2 );
		Functions\when( 'get_post_meta' )->justReturn( '50' );
		Functions\when( 'add_query_arg' )->alias( fn( $a, $u ) => $u . '?' . http_build_query( $a ) );
		Functions\when( 'home_url' )->justReturn( 'http://example.com' );
	}

	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	// ─── quote ───

	public function test_ajax_quote_returns_quote_array(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>2, 'coupon_code'=>'SAVE10' ];

		$quote = new CheckoutQuote( '100', '10', '5', '4.5', '94.5', 'USD', 1, 'SAVE10', 'Tax' );
		$this->quotes->shouldReceive( 'quote' )->with( 10, 2, 2, 'SAVE10' )->once()->andReturn( $quote );

		try { $this->ajax->ajax_quote(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'subtotal', $e->getMessage() );
			$this->assertStringContainsString( '94.5', $e->getMessage() );
		}
	}

	public function test_ajax_quote_rejects_without_nonce(): void {
		Functions\when( 'check_ajax_referer' )->alias( fn() => throw new \RuntimeException( 'nonce' ) );
		$_POST = [ 'event_id'=>10 ];

		try { $this->ajax->ajax_quote(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'nonce_failed', $e->getMessage() );
		}
	}

	public function test_ajax_quote_rejects_zero_event_id(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>0 ];

		try { $this->ajax->ajax_quote(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'event_required', $e->getMessage() );
		}
	}

	// ─── submit ───

	public function test_ajax_submit_returns_redirect_for_paid_gateway(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'payment_method'=>'paypal', 'idempotency_key'=>str_repeat( 'a', 32 ) ];

		$result = CheckoutDispatchResult::success( 5, 'redirect', 'https://paypal.com/approve' );
		$this->checkout->shouldReceive( 'create_checkout' )->once()->andReturn( $result );

		try { $this->ajax->ajax_submit(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'redirect', $e->getMessage() );
			$this->assertStringContainsString( 'paypal.com', $e->getMessage() );
		}
	}

	public function test_ajax_submit_returns_order_received_for_free(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'payment_method'=>'', 'idempotency_key'=>str_repeat( 'a', 32 ) ];

		$result = CheckoutDispatchResult::success( 7, 'free_completed' );
		$this->checkout->shouldReceive( 'create_checkout' )->once()->andReturn( $result );

		try { $this->ajax->ajax_submit(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'order-received', $e->getMessage() );
			$this->assertStringContainsString( 'booking_id=7', $e->getMessage() );
		}
	}

	public function test_ajax_submit_rejects_short_idempotency_key(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'idempotency_key'=>'short' ];

		try { $this->ajax->ajax_submit(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'idempotency_key_invalid', $e->getMessage() );
		}
	}

	// ─── validate coupon ───

	public function test_ajax_validate_coupon_returns_valid(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'coupon_code'=>'VALID' ];

		$coupon = CouponModel::from_row( [ 'id'=>1, 'code'=>'VALID', 'description'=>'', 'discount_type'=>'percent', 'percent_value'=>'10.0000', 'amount_value'=>null, 'max_discount_amount'=>null, 'applies_to'=>'all', 'usage_limit'=>null, 'usage_count'=>0, 'usage_limit_per_user'=>null, 'min_order_amount'=>null, 'starts_at_gmt'=>null, 'expires_at_gmt'=>null, 'status'=>'active', 'created_at_gmt'=>'2026-01-01', 'updated_at_gmt'=>'2026-01-01' ] );

		$vr = CouponValidationResult::success( $coupon, '5.0000' );
		$this->coupons->shouldReceive( 'validate' )->with( 'VALID', 10, 2, 1, '50.0000' )->once()->andReturn( $vr );

		try { $this->ajax->ajax_validate_coupon(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( '"valid":true', $e->getMessage() );
			$this->assertStringContainsString( 'VALID', $e->getMessage() );
		}
	}

	public function test_ajax_validate_coupon_returns_error_message(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'coupon_code'=>'INVALID' ];

		$vr = CouponValidationResult::failure( 'not_found', 'Coupon not found' );
		$this->coupons->shouldReceive( 'validate' )->with( 'INVALID', 10, 2, 1, '50.0000' )->once()->andReturn( $vr );

		try { $this->ajax->ajax_validate_coupon(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( '"valid":false', $e->getMessage() );
			$this->assertStringContainsString( 'not_found', $e->getMessage() );
		}
	}

	// ─── submit failure ───

	public function test_ajax_submit_returns_error_on_failure(): void {
		$_POST = [ 'nonce'=>'x', 'event_id'=>10, 'qty'=>1, 'payment_method'=>'paypal', 'idempotency_key'=>str_repeat( 'a', 32 ) ];

		$result = CheckoutDispatchResult::failure( 'sold_out', 'No tickets available' );
		$this->checkout->shouldReceive( 'create_checkout' )->once()->andReturn( $result );

		try { $this->ajax->ajax_submit(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'sold_out', $e->getMessage() );
		}
	}
}
