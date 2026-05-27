<?php
/**
 * CouponListTable tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Coupons
 */

namespace WPEMS\Tests\Unit\Admin\Coupons;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Coupons\CouponListTable;
use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponQuery;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Tests\Unit\TestCase;

class CouponListTableTest extends TestCase {

	/** @var CouponRepository&\Mockery\MockInterface */
	private $coupons;
	/** @var CouponQuery */
	private $query;

	protected function setUp(): void {
		parent::setUp();

		$this->coupons = Mockery::mock( CouponRepository::class );
		$this->query   = new CouponQuery();

		Functions\when( 'admin_url' )->alias( fn( $p = '' ) => 'http://example.com/wp-admin/' . $p );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->alias( fn( $t ) => print $t );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_query_arg' )->alias( fn( $a, $u = '' ) => $u . '?' . http_build_query( $a ) );
		Functions\when( 'wp_nonce_url' )->returnArg( 1 );
		Functions\when( 'esc_js' )->returnArg( 1 );
		Functions\when( 'get_date_from_gmt' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	private function makeCoupon( array $overrides = array() ): CouponModel {
		return CouponModel::from_row( array_merge( array(
			'id'                   => 1,
			'code'                 => 'SAVE10',
			'description'          => 'Test coupon',
			'discount_type'        => CouponModel::TYPE_PERCENT,
			'percent_value'        => '10.0000',
			'amount_value'         => null,
			'max_discount_amount'  => null,
			'applies_to'           => CouponModel::APPLIES_ALL,
			'usage_limit'          => 100,
			'usage_count'          => 5,
			'usage_limit_per_user' => null,
			'min_order_amount'     => null,
			'starts_at_gmt'        => '2026-01-01 00:00:00',
			'expires_at_gmt'       => '2026-12-31 23:59:59',
			'status'               => CouponModel::STATUS_ACTIVE,
			'created_at_gmt'       => '2026-01-01 00:00:00',
			'updated_at_gmt'       => '2026-01-01 00:00:00',
		), $overrides ) );
	}

	public function test_get_columns_includes_expected_keys(): void {
		$table   = new CouponListTable( $this->coupons, $this->query );
		$columns = $table->get_columns();

		$expected = array( 'cb', 'code', 'discount', 'usage', 'status', 'dates' );
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $columns );
		}
		$this->assertCount( count( $expected ), $columns );
	}

	public function test_column_code_renders_row_actions(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'code' => 'SAVE10' ) );

		$html = $table->column_code( $item );

		$this->assertStringContainsString( 'SAVE10', $html );
		$this->assertStringContainsString( 'Edit', $html );
		$this->assertStringContainsString( 'Delete', $html );
	}

	public function test_column_discount_percent(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'discount_type' => CouponModel::TYPE_PERCENT, 'percent_value' => '15.0000' ) );

		$html = $table->column_discount( $item );
		$this->assertStringContainsString( '15.0000%', $html );
	}

	public function test_column_discount_amount(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'discount_type' => CouponModel::TYPE_AMOUNT, 'amount_value' => '25.0000' ) );

		$html = $table->column_discount( $item );
		$this->assertStringContainsString( '25.0000', $html );
	}

	public function test_column_discount_hybrid(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'discount_type' => CouponModel::TYPE_HYBRID, 'percent_value' => '10.0000', 'max_discount_amount' => '50.0000' ) );

		$html = $table->column_discount( $item );
		$this->assertStringContainsString( '10.0000%', $html );
		$this->assertStringContainsString( 'cap', $html );
		$this->assertStringContainsString( '50.0000', $html );
	}

	public function test_column_usage_renders_infinity_for_unlimited(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'usage_limit' => null, 'usage_count' => 42 ) );

		$html = $table->column_usage( $item );
		$this->assertStringContainsString( '42', $html );
		$this->assertStringContainsString( '∞', $html );
	}

	public function test_column_usage_renders_limit(): void {
		$table = new CouponListTable( $this->coupons, $this->query );
		$item  = $this->makeCoupon( array( 'usage_limit' => 100, 'usage_count' => 42 ) );

		$html = $table->column_usage( $item );
		$this->assertStringContainsString( '42 / 100', $html );
	}

	public function test_prepare_items_pagination(): void {
		$table = new CouponListTable( $this->coupons, $this->query );

		$this->coupons->shouldReceive( 'count' )->once()->andReturn( 30 );
		$this->coupons->shouldReceive( 'query' )->once()->andReturn( array() );

		$table->prepare_items();

		$this->assertSame( 20, $this->query->limit );
		$this->assertSame( 0, $this->query->offset );
	}

	public function test_get_bulk_actions(): void {
		$table  = new CouponListTable( $this->coupons, $this->query );
		$bulk   = $table->get_bulk_actions();

		$this->assertArrayHasKey( 'activate', $bulk );
		$this->assertArrayHasKey( 'deactivate', $bulk );
		$this->assertArrayHasKey( 'delete', $bulk );
	}
}
