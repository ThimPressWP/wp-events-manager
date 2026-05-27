<?php
namespace WPEMS\Tests\Unit\Admin\Coupons;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Coupons\CouponAdminActions;
use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Services\CouponService;
use WPEMS\Tests\Unit\TestCase;

class CouponAdminActionsTest extends TestCase {

	private $coupons, $coupon_events, $coupon_usage, $coupon_service, $actions;

	protected function setUp(): void {
		parent::setUp();
		$this->coupons        = Mockery::mock( CouponRepository::class );
		$this->coupon_events  = Mockery::mock( CouponEventRepository::class );
		$this->coupon_usage   = Mockery::mock( CouponUsageRepository::class );
		$this->coupon_service = Mockery::mock( CouponService::class );
		$this->actions        = new CouponAdminActions( $this->coupons, $this->coupon_events, $this->coupon_usage, $this->coupon_service );

		Functions\when( 'wp_die' )->alias( fn( $m = '' ) => throw new \RuntimeException( (string) $m ) );
		Functions\when( 'admin_url' )->alias( fn( $p = '' ) => 'http://ex.com/wp-admin/' . $p );
		Functions\when( 'add_query_arg' )->alias( fn( $a, $u = '' ) => $u . '?' . http_build_query( $a ) );
		Functions\when( 'wp_safe_redirect' )->alias( fn( $u ) => throw new \RuntimeException( 'rd:' . $u ) );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->alias( fn( $v ) => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'wp_kses_post' )->returnArg( 1 );
		Functions\when( 'wp_get_current_user' )->justReturn( (object) [ 'user_login' => 'admin' ] );
	}

	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	private function makeCoupon( array $o = [] ): CouponModel {
		return CouponModel::from_row( array_merge( [
			'id'=>1,'code'=>'SAVE10','description'=>'','discount_type'=>CouponModel::TYPE_PERCENT,
			'percent_value'=>'10.0000','amount_value'=>null,'max_discount_amount'=>null,
			'applies_to'=>CouponModel::APPLIES_ALL,'usage_limit'=>100,'usage_count'=>0,
			'usage_limit_per_user'=>null,'min_order_amount'=>null,'starts_at_gmt'=>null,
			'expires_at_gmt'=>null,'status'=>CouponModel::STATUS_ACTIVE,
			'created_at_gmt'=>'2026-01-01 00:00:00','updated_at_gmt'=>'2026-01-01 00:00:00',
		], $o ) );
	}

	public function test_handle_save_creates_new_coupon(): void {
		$_POST = [ 'coupon_id'=>'0', '_wpnonce'=>'x', 'code'=>'NEWCODE', 'discount_type'=>'percent', 'percent_value'=>'15', 'applies_to'=>'all', 'status'=>'yes' ];
		$this->coupons->shouldReceive( 'insert' )->once()->andReturn( 99 );
		$this->coupon_events->shouldReceive( 'set_events' )->with( 99, [] )->once();
		try { $this->actions->handle_save(); $this->fail('Expected redirect'); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'coupon_created', $e->getMessage() );
			$this->assertStringContainsString( 'coupon_id=99', $e->getMessage() );
		}
	}

	public function test_handle_save_uppercases_code(): void {
		$_POST = [ 'coupon_id'=>'0', '_wpnonce'=>'x', 'code'=>'lowercase', 'discount_type'=>'percent', 'percent_value'=>'10', 'applies_to'=>'all', 'status'=>'yes' ];
		$cap = null;
		$this->coupons->shouldReceive( 'insert' )->once()->andReturnUsing( function($d) use(&$cap) { $cap=$d; return 1; } );
		$this->coupon_events->shouldReceive( 'set_events' )->once();
		try { $this->actions->handle_save(); } catch ( \RuntimeException $e ) {}
		$this->assertIsArray( $cap );
		$this->assertSame( 'LOWERCASE', $cap['code'] );
	}

	public function test_handle_save_rejects_empty_code(): void {
		$_POST = [ 'coupon_id'=>'0', '_wpnonce'=>'x', 'code'=>'', 'discount_type'=>'percent' ];
		try { $this->actions->handle_save(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'code_required', $e->getMessage() );
		}
	}

	public function test_handle_save_clears_event_scope_when_applies_to_all(): void {
		$_POST = [ 'coupon_id'=>'5', '_wpnonce'=>'x', 'code'=>'GLOBAL', 'discount_type'=>'percent', 'percent_value'=>'5', 'applies_to'=>'all', 'status'=>'yes' ];
		$c = $this->makeCoupon( [ 'id'=>5, 'code'=>'GLOBAL' ] );
		$this->coupons->shouldReceive( 'find' )->with(5)->once()->andReturn($c);
		$this->coupons->shouldReceive( 'update' )->once()->andReturn(true);
		$this->coupon_events->shouldReceive( 'set_events' )->with(5,[])->once();
		try { $this->actions->handle_save(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'coupon_saved', $e->getMessage() );
		}
	}

	public function test_handle_delete_voids_active_usage(): void {
		$_GET = [ 'coupon_id'=>'3', '_wpnonce'=>'ok' ];
		$c = $this->makeCoupon( [ 'id'=>3 ] );
		$this->coupons->shouldReceive( 'find' )->with(3)->once()->andReturn($c);
		global $wpdb;
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( [10,20] );
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn($s) => $s );
		$this->coupon_service->shouldReceive( 'void_usage' )->with(10,'coupon_deleted')->once()->andReturn(true);
		$this->coupon_service->shouldReceive( 'void_usage' )->with(20,'coupon_deleted')->once()->andReturn(true);
		$this->coupon_events->shouldReceive( 'delete_all_for_coupon' )->with(3)->once();
		$this->coupons->shouldReceive( 'delete' )->with(3)->once()->andReturn(true);
		try { $this->actions->handle_delete(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'coupon_deleted', $e->getMessage() );
		}
	}

	public function test_handle_toggle_status_inverts(): void {
		$_GET = [ 'coupon_id'=>'1', '_wpnonce'=>'ok' ];
		$c = $this->makeCoupon( [ 'id'=>1, 'status'=>CouponModel::STATUS_ACTIVE ] );
		$this->coupons->shouldReceive( 'find' )->with(1)->once()->andReturn($c);
		$this->coupons->shouldReceive( 'update' )->with(1,['status'=>CouponModel::STATUS_INACTIVE])->once()->andReturn(true);
		try { $this->actions->handle_toggle_status(); } catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'coupon_inactive', $e->getMessage() );
		}
	}

	public function test_handle_bulk_action_aggregates_counts(): void {
		$this->coupons->shouldReceive( 'update' )->with(1,['status'=>'active'])->once()->andReturn(true);
		$this->coupons->shouldReceive( 'update' )->with(2,['status'=>'active'])->once()->andReturn(false);
		$this->coupons->shouldReceive( 'update' )->with(3,['status'=>'active'])->once()->andReturn(true);
		$r = $this->actions->handle_bulk_action( 'activate', [1,2,3] );
		$this->assertSame( 2, $r['success'] );
		$this->assertSame( 1, $r['failed'] );
	}

	public function test_handle_save_rejects_without_nonce(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( false );
		$_POST = [ '_wpnonce'=>'bad' ];
		$this->expectException( \RuntimeException::class );
		$this->actions->handle_save();
	}
}
