<?php
/**
 * AdminBookingManager tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Bookings
 */

namespace WPEMS\Tests\Unit\Admin\Bookings;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Bookings\AdminBookingManager;
use WPEMS\Admin\Bookings\BookingAdminActions;
use WPEMS\Models\BookingRefundSummary;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test admin booking manager.
 */
class AdminBookingManagerTest extends TestCase {

	/** @var BookingRepository&\Mockery\MockInterface */
	private $bookings;
	/** @var PaymentTransactionRepository&\Mockery\MockInterface */
	private $txns;
	/** @var BookingStatusService&\Mockery\MockInterface */
	private $status;
	/** @var PaymentSyncService&\Mockery\MockInterface */
	private $sync;
	/** @var BookingAdminActions&\Mockery\MockInterface */
	private $actions;
	/** @var BookingMetaRepository&\Mockery\MockInterface */
	private $meta;
	/** @var AdminBookingManager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->txns     = Mockery::mock( PaymentTransactionRepository::class );
		$this->status   = Mockery::mock( BookingStatusService::class );
		$this->sync     = Mockery::mock( PaymentSyncService::class );
		$this->actions  = Mockery::mock( BookingAdminActions::class );
		$this->meta     = Mockery::mock( BookingMetaRepository::class );

		$this->manager = new AdminBookingManager(
			$this->bookings,
			$this->txns,
			$this->status,
			$this->sync,
			$this->actions,
			$this->meta
		);

		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'http://example.com/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_nonce_url' )->alias(
			function ( $url, $action ) {
				return $url . '&_wpnonce=mock';
			}
		);
		Functions\when( 'wp_create_nonce' )->justReturn( 'mock_nonce' );
		Functions\when( 'wp_die' )->alias(
			function ( $msg = '' ) {
				throw new \RuntimeException( 'wp_die: ' . ( is_string( $msg ) ? $msg : '' ) );
			}
		);
		Functions\when( 'wp_redirect' )->justReturn( true );
		Functions\when( 'wp_safe_redirect' )->justReturn( true );
		Functions\when( 'check_admin_referer' )->alias(
			function ( $action ) {
				// Pass through — tests will call maybe_handle_action and we control the behavior.
			}
		);
		Functions\when( 'remove_query_arg' )->alias(
			function ( $args ) {
				return 'http://example.com/wp-admin/admin.php?page=wpems-bookings';
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( $args, $url = '' ) {
				if ( is_array( $args ) ) {
					$url = empty( $url ) ? 'http://example.com/wp-admin/admin.php?page=wpems-bookings' : $url;
					foreach ( $args as $k => $v ) {
						$url .= '&' . $k . '=' . $v;
					}
				}
				return $url;
			}
		);
		Functions\when( 'get_admin_page_title' )->justReturn( 'Bookings' );
		Functions\when( 'get_edit_post_link' )->justReturn( 'http://example.com/wp-admin/post.php?post=1&action=edit' );
		Functions\when( 'get_edit_user_link' )->justReturn( 'http://example.com/wp-admin/user-edit.php?user_id=2' );
		Functions\when( 'get_userdata' )->justReturn(
			(object) array( 'display_name' => 'Test User' )
		);
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
		Functions\when( 'get_current_screen' )->justReturn(
			(object) array( 'id' => 'toplevel_page_wpems-bookings' )
		);
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
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
		Functions\when( 'wp_nonce_field' )->alias(
			function ( $action = -1, $name = '_wpnonce' ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="mock" />';
			}
		);
		Functions\when( 'selected' )->alias(
			function ( $selected, $current ) {
				if ( (string) $selected === (string) $current ) {
					echo ' selected="selected"';
				}
			}
		);
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

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * It registers admin_menu hook at priority 30.
	 *
	 * @return void
	 */
	public function test_register_hooks_admin_menu_at_30(): void {
		Actions\expectAdded( 'admin_menu' )
			->whenHappen(
				function ( $callback, $priority ) {
					$this->assertSame( 30, $priority );
				}
			);

		Actions\expectAdded( 'admin_enqueue_scripts' );
		Actions\expectAdded( 'admin_post_wpems_booking_export_csv' );

		$this->manager->register();

		// Verify add_action was called — Brain Monkey tracks these.
		$this->assertTrue( has_action( 'admin_menu' ) );
	}

	/**
	 * It renders list when no booking_id.
	 *
	 * @return void
	 */
	public function test_render_list_when_no_booking_id(): void {
		$_GET['booking_id'] = 0;

		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 0 );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( array() );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Export CSV', $output );
		$this->assertStringContainsString( 'wpems-bookings', $output );
	}

	/**
	 * It dies when detail booking is missing.
	 *
	 * @return void
	 */
	public function test_render_detail_dies_when_booking_missing(): void {
		$_GET['booking_id'] = 999;

		$this->bookings->shouldReceive( 'find' )
			->with( 999 )
			->once()
			->andReturn( null );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die: Booking not found.' );

		$this->manager->render();
	}

	/**
	 * It passes refund summary to the detail view.
	 *
	 * @return void
	 */
	public function test_render_detail_passes_refund_summary_to_view(): void {
		$_GET['booking_id'] = 1;

		$booking = $this->makeBooking(
			array(
				'qty'            => 2,
				'subtotal'       => '100.00',
				'discount_total' => '10.00',
				'tax_rate'       => '5.0000',
				'tax_total'      => '4.50',
				'total'          => '94.50',
			)
		);

		$this->bookings->shouldReceive( 'find' )->with( 1 )->once()->andReturn( $booking );

		$this->txns->shouldReceive( 'find_by_booking' )
			->with( 1 )->once()->andReturn( array() );

		$refund_summary = BookingRefundSummary::none();
		$this->txns->shouldReceive( 'get_refund_summary' )
			->with( 1, '94.50' )->once()->andReturn( $refund_summary );

		$this->meta->shouldReceive( 'get_all' )
			->with( 1 )->once()->andReturn( array() );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'not_refunded', $output );
		$this->assertStringContainsString( '0.00', $output );
		$this->assertStringContainsString( 'PAY-123', $output );
	}

	/**
	 * It sanitizes input when building BookingQuery from request.
	 *
	 * @return void
	 */
	public function test_build_query_from_request_sanitizes_inputs(): void {
		$request = array(
			'status'         => 'ea-pending',
			'payment_status' => 'pending<script>',
			'payment_method' => 'paypal',
			'event_id'       => '123abc',
			'date_from'      => '2026-01-01',
			'date_to'        => '2026-06-30<b>',
			's'              => 'PAY-456',
			'paged'          => '3',
		);

		$q = $this->manager->build_query_from_request( $request );

		$this->assertSame( 'ea-pending', $q->status );
		$this->assertSame( 'pendingscript', $q->payment_status );
		$this->assertSame( 'paypal', $q->payment_method );
		$this->assertSame( 123, $q->event_id );
		$this->assertSame( '2026-01-01', $q->date_from );
		$this->assertStringNotContainsString( '<b>', $q->date_to );
		$this->assertSame( 'PAY-456', $q->search );
	}

	/**
	 * It rejects action without nonce (no wpems_booking_action in POST).
	 *
	 * @return void
	 */
	public function test_maybe_handle_action_rejects_without_action(): void {
		$_POST = array();

		// Should return early without any action.
		$this->manager->maybe_handle_action();

		// If we get here without exception, the method returned early.
		$this->assertTrue( true );
	}

	/**
	 * It builds query with default values when request is empty.
	 *
	 * @return void
	 */
	public function test_build_query_from_empty_request(): void {
		$q = $this->manager->build_query_from_request( array() );

		$this->assertSame( '', $q->status );
		$this->assertSame( '', $q->payment_status );
		$this->assertSame( 0, $q->event_id );
		$this->assertSame( 'created_at_gmt', $q->order_by );
		$this->assertSame( 20, $q->limit );
		$this->assertSame( 0, $q->offset );
	}

	/**
	 * It renders detail without transactions.
	 *
	 * @return void
	 */
	public function test_render_detail_without_transactions(): void {
		$_GET['booking_id'] = 1;

		$booking = $this->makeBooking(
			array(
				'payment_method'   => 'manual',
				'payment_mode'     => null,
				'gateway_order_id' => null,
				'status'           => 'ea-completed',
				'payment_status'   => 'paid',
				'currency'         => 'EUR',
				'total'            => '50.00',
				'subtotal'         => '50.00',
				'created_at_gmt'   => '2026-06-01 08:00:00',
			)
		);

		$this->bookings->shouldReceive( 'find' )->with( 1 )->once()->andReturn( $booking );
		$this->txns->shouldReceive( 'find_by_booking' )->with( 1 )->once()->andReturn( array() );

		$refund = BookingRefundSummary::none();
		$this->txns->shouldReceive( 'get_refund_summary' )
			->with( 1, '50.00' )->once()->andReturn( $refund );

		$this->meta->shouldReceive( 'get_all' )
			->with( 1 )->once()->andReturn( array( 'note' => 'Test note' ) );

		ob_start();
		$this->manager->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No transactions recorded.', $output );
		$this->assertStringContainsString( 'Test note', $output );
		$this->assertStringContainsString( 'ea-completed', $output );
	}
}
