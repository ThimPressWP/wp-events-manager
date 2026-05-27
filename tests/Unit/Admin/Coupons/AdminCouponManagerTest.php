<?php
/**
 * AdminCouponManager tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Coupons
 */

namespace WPEMS\Tests\Unit\Admin\Coupons;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Coupons\AdminCouponManager;
use WPEMS\Admin\Coupons\CouponAdminActions;
use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Tests\Unit\TestCase;

class AdminCouponManagerTest extends TestCase {

	/** @var CouponRepository&\Mockery\MockInterface */
	private $coupons;
	/** @var CouponEventRepository&\Mockery\MockInterface */
	private $coupon_events;
	/** @var CouponUsageRepository&\Mockery\MockInterface */
	private $coupon_usage;
	/** @var CouponAdminActions&\Mockery\MockInterface */
	private $actions;
	/** @var AdminCouponManager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->coupons       = Mockery::mock( CouponRepository::class );
		$this->coupon_events = Mockery::mock( CouponEventRepository::class );
		$this->coupon_usage  = Mockery::mock( CouponUsageRepository::class );
		$this->actions       = Mockery::mock( CouponAdminActions::class );

		$this->manager = new AdminCouponManager(
			$this->coupons,
			$this->coupon_events,
			$this->coupon_usage,
			$this->actions
		);

		Functions\when( 'admin_url' )->alias( fn( $p = '' ) => 'http://example.com/wp-admin/' . $p );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->alias( fn( $t ) => print $t );
		Functions\when( 'esc_attr_e' )->alias( fn( $t ) => print $t );
		Functions\when( 'add_query_arg' )->alias( fn( $a, $u = '' ) => $u . '?' . http_build_query( $a ) );
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => 'wpems-coupons' ) );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_register_hooks_admin_menu(): void {
		Actions\expectAdded( 'admin_menu' )->whenHappen( fn( $cb, $p ) => $this->assertSame( 31, $p ) );
		Actions\expectAdded( 'admin_enqueue_scripts' );
		Actions\expectAdded( 'admin_post_wpems_coupon_save' );
		Actions\expectAdded( 'admin_post_wpems_coupon_delete' );
		Actions\expectAdded( 'admin_post_wpems_coupon_toggle_status' );

		$this->manager->register();
		$this->assertTrue( has_action( 'admin_menu' ) );
	}

	public function test_render_dispatches_to_list_by_default(): void {
		$_GET = array( 'page' => 'wpems-coupons' );

		$this->coupons->shouldReceive( 'count' )->once()->andReturn( 0 );
		$this->coupons->shouldReceive( 'query' )->once()->andReturn( array() );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Add New', $output );
	}

	public function test_render_dispatches_to_edit_for_action_edit(): void {
		$_GET = array( 'page' => 'wpems-coupons', 'action' => 'edit', 'coupon_id' => 5 );

		$coupon = CouponModel::from_row( array(
			'id'                   => 5,
			'code'                 => 'TEST',
			'description'          => null,
			'discount_type'        => CouponModel::TYPE_PERCENT,
			'percent_value'        => '10.0000',
			'amount_value'         => null,
			'max_discount_amount'  => null,
			'applies_to'           => CouponModel::APPLIES_ALL,
			'usage_limit'          => null,
			'usage_count'          => 0,
			'usage_limit_per_user' => null,
			'min_order_amount'     => null,
			'starts_at_gmt'        => null,
			'expires_at_gmt'       => null,
			'status'               => CouponModel::STATUS_ACTIVE,
			'created_at_gmt'       => '2026-01-01 00:00:00',
			'updated_at_gmt'       => '2026-01-01 00:00:00',
		) );

		$this->coupons->shouldReceive( 'find' )->with( 5 )->once()->andReturn( $coupon );
		$this->coupon_events->shouldReceive( 'get_event_ids' )->with( 5 )->once()->andReturn( array() );

		Functions\when( 'esc_textarea' )->returnArg( 1 );
		Functions\when( 'wp_nonce_field' )->alias( fn( $a, $n = '_wpnonce' ) => print '<input type="hidden" name="' . $n . '" />' );
		Functions\when( 'checked' )->alias( fn( $c, $e = true ) => ( (string)$c === (string)$e ? print ' checked' : '' ) );
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
		Functions\when( 'wp_kses_post' )->returnArg( 1 );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'TEST', $output );
		$this->assertStringContainsString( 'Edit Coupon', $output );
	}

	public function test_render_dispatches_to_new_for_action_new(): void {
		$_GET = array( 'page' => 'wpems-coupons', 'action' => 'new' );

		Functions\when( 'esc_textarea' )->returnArg( 1 );
		Functions\when( 'wp_nonce_field' )->alias( fn( $a, $n = '_wpnonce' ) => print '' );
		Functions\when( 'checked' )->alias( fn() => '' );
		Functions\when( 'get_the_title' )->justReturn( '' );
		Functions\when( 'wp_kses_post' )->returnArg( 1 );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Add New Coupon', $output );
	}
}
