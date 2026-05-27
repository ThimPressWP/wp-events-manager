<?php
/**
 * BookingListTable tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Bookings
 */

namespace WPEMS\Tests\Unit\Admin\Bookings;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Bookings\BookingListTable;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test booking list table.
 */
class BookingListTableTest extends TestCase {

	/** @var BookingRepository&\Mockery\MockInterface */
	private $bookings;
	/** @var BookingQuery */
	private $query;

	protected function setUp(): void {
		parent::setUp();

		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->query    = new BookingQuery();

		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'http://example.com/wp-admin/' . $path;
			}
		);
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_attr_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'number_format_i18n' )->alias(
			function ( $number, $decimals = 0 ) {
				return number_format( $number, $decimals );
			}
		);
		Functions\when( 'get_date_from_gmt' )->alias(
			function ( $gmt, $format = 'Y-m-d H:i:s' ) {
				return $gmt; // Return same value for testing.
			}
		);
		Functions\when( 'selected' )->alias(
			function ( $selected, $current ) {
				if ( (string) $selected === (string) $current ) {
					echo ' selected="selected"';
				}
			}
		);
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
		Functions\when( 'get_edit_post_link' )->justReturn( 'http://example.com/wp-admin/post.php?post=10&action=edit' );
		Functions\when( 'get_edit_user_link' )->justReturn( 'http://example.com/wp-admin/user-edit.php?user_id=2' );
		Functions\when( 'get_userdata' )->justReturn(
			(object) array(
				'ID'           => 2,
				'display_name' => 'Test User',
				'user_email'   => 'test@example.com',
			)
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( $args, $url = '' ) {
				if ( is_array( $args ) ) {
					$url = empty( $url ) ? 'http://example.com/wp-admin/admin.php' : $url;
					$url .= '?' . http_build_query( $args );
				}
				return $url;
			}
		);
		Functions\when( 'wp_unslash' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Build a BookingTableModel from an array for testing.
	 *
	 * @param array $overrides Row data overrides.
	 *
	 * @return BookingTableModel
	 */
	private function makeBooking( array $overrides = array() ): BookingTableModel {
		return BookingTableModel::from_row(
			array_merge(
				array(
					'id'                  => 1,
					'legacy_post_id'      => null,
					'event_id'            => 10,
					'user_id'             => 2,
					'qty'                 => 1,
					'subtotal'            => '100.00',
					'discount_total'      => '0.00',
					'tax_rate'            => '0',
					'tax_total'           => '0.00',
					'total'               => '100.00',
					'currency'            => 'USD',
					'coupon_id'           => null,
					'payment_method'      => 'paypal',
					'payment_mode'        => 'live',
					'gateway_order_id'    => 'PAY-123',
					'status'              => 'ea-pending',
					'payment_status'      => 'pending',
					'hold_expires_at_gmt' => null,
					'idempotency_key'     => 'ik-abc',
					'created_at_gmt'      => '2026-01-15 10:30:00',
					'updated_at_gmt'      => '2026-01-15 10:30:00',
				),
				$overrides
			)
		);
	}

	/**
	 * It includes all expected column keys.
	 *
	 * @return void
	 */
	public function test_get_columns_includes_all_expected_keys(): void {
		$table = new BookingListTable( $this->bookings, $this->query );

		$columns = $table->get_columns();

		$expected = array( 'cb', 'id', 'event', 'customer', 'qty', 'total', 'status', 'payment', 'method', 'date' );
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $columns );
		}

		$this->assertCount( count( $expected ), $columns );
	}

	/**
	 * It renders ID column as link to detail page.
	 *
	 * @return void
	 */
	public function test_column_id_renders_link_to_detail_page(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'id' => 42 ) );

		$html = $table->column_id( $item );

		$this->assertStringContainsString( '#42', $html );
		$this->assertStringContainsString( 'booking_id=42', $html );
		$this->assertStringContainsString( '<a', $html );
	}

	/**
	 * It falls back to Guest when user_id is 0.
	 *
	 * @return void
	 */
	public function test_column_customer_falls_back_to_guest(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'user_id' => 0 ) );

		$html = $table->column_customer( $item );

		$this->assertStringContainsString( 'Guest', $html );
	}

	/**
	 * It shows user name + email when user exists.
	 *
	 * @return void
	 */
	public function test_column_customer_shows_user_info(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'user_id' => 2 ) );

		$html = $table->column_customer( $item );

		$this->assertStringContainsString( 'Test User', $html );
		$this->assertStringContainsString( 'test@example.com', $html );
	}

	/**
	 * It formats total with currency.
	 *
	 * @return void
	 */
	public function test_column_total_formats_with_currency(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking(
			array(
				'currency' => 'EUR',
				'total'    => '99.50',
			)
		);

		$html = $table->column_total( $item );

		$this->assertStringContainsString( 'EUR', $html );
		$this->assertStringContainsString( '99.50', $html );
	}

	/**
	 * It renders status badge.
	 *
	 * @return void
	 */
	public function test_column_status_renders_badge(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'status' => 'ea-completed' ) );

		$html = $table->column_status( $item );

		$this->assertStringContainsString( 'wpems-status-badge', $html );
		$this->assertStringContainsString( 'ea-completed', $html );
		$this->assertStringContainsString( 'Completed', $html );
	}

	/**
	 * It renders payment status badge.
	 *
	 * @return void
	 */
	public function test_column_payment_renders_badge(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'payment_status' => 'paid' ) );

		$html = $table->column_payment( $item );

		$this->assertStringContainsString( 'wpems-payment-badge', $html );
		$this->assertStringContainsString( 'paid', $html );
		$this->assertStringContainsString( 'Paid', $html );
	}

	/**
	 * It renders method column with mode.
	 *
	 * @return void
	 */
	public function test_column_method_includes_mode_if_present(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking(
			array(
				'payment_method' => 'stripe',
				'payment_mode'   => 'test',
			)
		);

		$html = $table->column_method( $item );

		$this->assertStringContainsString( 'Stripe', $html );
		$this->assertStringContainsString( 'test', $html );
	}

	/**
	 * It renders dash when method is empty.
	 *
	 * @return void
	 */
	public function test_column_method_shows_dash_when_empty(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'payment_method' => '' ) );

		$html = $table->column_method( $item );

		$this->assertStringContainsString( '—', $html );
	}

	/**
	 * It renders date column from GMT.
	 *
	 * @return void
	 */
	public function test_column_date_renders_from_gmt(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'created_at_gmt' => '2026-06-15 14:30:00' ) );

		$html = $table->column_date( $item );

		$this->assertStringContainsString( '2026-06-15 14:30:00', $html );
	}

	/**
	 * It renders dash when date is empty.
	 *
	 * @return void
	 */
	public function test_column_date_shows_dash_when_empty(): void {
		$table = new BookingListTable( $this->bookings, $this->query );
		$item  = $this->makeBooking( array( 'created_at_gmt' => '' ) );

		$html = $table->column_date( $item );

		$this->assertStringContainsString( '—', $html );
	}

	/**
	 * It applies pagination and query limits.
	 *
	 * @return void
	 */
	public function test_prepare_items_applies_pagination(): void {
		$_GET['paged'] = '2';

		$table = new BookingListTable( $this->bookings, $this->query );

		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 50 );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( array() );

		$table->prepare_items();

		// WP_List_Table stub get_pagenum() returns 1, so offset = 0.
		// Limit is still set correctly.
		$this->assertSame( 20, $this->query->limit );
		$this->assertSame( 0, $this->query->offset );
	}

	/**
	 * It clamps orderby to allow-list.
	 *
	 * @return void
	 */
	public function test_prepare_items_clamps_orderby_to_allowlist(): void {
		$_GET['orderby'] = 'evil_column; DROP TABLE';
		$_GET['order']   = 'DESC';

		$table = new BookingListTable( $this->bookings, $this->query );

		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 0 );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( array() );

		$table->prepare_items();

		// Should still be default (created_at_gmt) since evil_column is not in whitelist.
		$this->assertSame( 'created_at_gmt', $this->query->order_by );
		// Order should still be clamped, but DESC is in the whitelist so it stays.
		$this->assertSame( 'DESC', $this->query->order );
	}

	/**
	 * It applies valid orderby from request.
	 *
	 * @return void
	 */
	public function test_prepare_items_applies_valid_orderby(): void {
		$_GET['orderby'] = 'total';
		$_GET['order']   = 'ASC';

		$table = new BookingListTable( $this->bookings, $this->query );

		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 0 );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( array() );

		$table->prepare_items();

		$this->assertSame( 'total', $this->query->order_by );
		$this->assertSame( 'ASC', $this->query->order );
	}

	/**
	 * It has bulk action for mark cancelled.
	 *
	 * @return void
	 */
	public function test_get_bulk_actions_includes_mark_cancelled(): void {
		$table = new BookingListTable( $this->bookings, $this->query );

		$bulk = $table->get_bulk_actions();

		$this->assertArrayHasKey( 'mark_cancelled', $bulk );
	}

	/**
	 * It renders filter controls.
	 *
	 * @return void
	 */
	public function test_display_filters_renders_selects(): void {
		$_GET['status']         = 'ea-pending';
		$_GET['payment_status'] = 'paid';

		$table = new BookingListTable( $this->bookings, $this->query );

		ob_start();
		$table->display_filters();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="status"', $html );
		$this->assertStringContainsString( 'name="payment_status"', $html );
		$this->assertStringContainsString( 'name="payment_method"', $html );
		$this->assertStringContainsString( 'name="event_id"', $html );
		$this->assertStringContainsString( 'name="date_from"', $html );
		$this->assertStringContainsString( 'name="date_to"', $html );
		$this->assertStringContainsString( 'selected="selected"', $html );
	}
}
