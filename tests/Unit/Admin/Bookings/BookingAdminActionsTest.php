<?php
/**
 * BookingAdminActions tests.
 *
 * @package WPEMS\Tests\Unit\Admin\Bookings
 */

namespace WPEMS\Tests\Unit\Admin\Bookings;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Bookings\BookingAdminActions;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test booking admin actions.
 */
class BookingAdminActionsTest extends TestCase {

	/** @var BookingRepository&\Mockery\MockInterface */
	private $bookings;
	/** @var BookingStatusService&\Mockery\MockInterface */
	private $status;
	/** @var PaymentSyncService&\Mockery\MockInterface */
	private $sync;
	/** @var BookingAdminActions */
	private $actions;

	/** @var bool */
	private $did_redirect = false;
	/** @var string */
	private $redirect_url = '';

	protected function setUp(): void {
		parent::setUp();

		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->status   = Mockery::mock( BookingStatusService::class );
		$this->sync     = Mockery::mock( PaymentSyncService::class );
		$this->actions  = new BookingAdminActions( $this->bookings, $this->status, $this->sync );

		$this->did_redirect = false;
		$this->redirect_url = '';

		// Ensure wp_die throws (may be overridden by other mocks).
		Functions\when( 'wp_die' )->alias(
			function ( $msg = '' ) {
				throw new \RuntimeException( 'wp_die: ' . ( is_string( $msg ) ? $msg : '' ) );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_unslash' )->returnArg( 1 );

		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'http://example.com/wp-admin/' . $path;
			}
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
		Functions\when( 'wp_get_current_user' )->justReturn(
			(object) array( 'user_login' => 'admin' )
		);
		Functions\when( 'get_userdata' )->justReturn(
			(object) array(
				'user_email' => 'test@example.com',
			)
		);
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
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
	 * Configure nonce to be valid for test.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return void
	 */
	private function allowNonce( string $action ): void {
		Functions\when( 'wp_verify_nonce' )->alias(
			function ( $nonce, $expected_action ) use ( $action ) {
				return $expected_action === $action && $nonce === 'valid_nonce';
			}
		);
	}

	/**
	 * Configure nonces to be invalid.
	 *
	 * @return void
	 */
	private function denyAllNonces(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( false );
	}

	/**
	 * It rejects mark_paid without valid nonce.
	 *
	 * @return void
	 */
	public function test_handle_mark_paid_rejects_without_nonce(): void {
		$this->denyAllNonces();
		$_POST = array(
			'wpems_booking_action' => 'mark_paid',
			'_wpnonce'             => 'bad',
			'booking_id'           => 1,
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die: Invalid nonce.' );

		$this->actions->handle_mark_paid();
	}

	/**
	 * It rejects mark_paid without capability.
	 *
	 * @return void
	 */
	public function test_handle_mark_paid_rejects_without_capability(): void {
		$this->allowNonce( 'wpems_booking_mark_paid' );

		Functions\when( 'current_user_can' )->justReturn( false );

		$_POST = array(
			'wpems_booking_action' => 'mark_paid',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 1,
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die: Permission denied.' );

		$this->actions->handle_mark_paid();
	}

	/**
	 * It returns not_found for missing booking.
	 *
	 * @return void
	 */
	public function test_handle_mark_paid_returns_not_found_for_missing_booking(): void {
		$this->allowNonce( 'wpems_booking_mark_paid' );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->did_redirect = true;
				$this->redirect_url = $url;
				return true;
			}
		);

		$_POST = array(
			'wpems_booking_action' => 'mark_paid',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 999,
		);

		$this->bookings->shouldReceive( 'find' )
			->with( 999 )->once()->andReturn( null );

		// handle_mark_paid calls redirect_with_message which calls exit.
		// We need to catch that.
		try {
			$this->actions->handle_mark_paid();
		} catch ( \RuntimeException $e ) {
			// exit throws in test context.
		}

		$this->assertTrue( $this->did_redirect );
		$this->assertStringContainsString( 'not_found', $this->redirect_url );
	}

	/**
	 * It returns already_paid for paid booking.
	 *
	 * @return void
	 */
	public function test_handle_mark_paid_returns_already_paid_for_paid_booking(): void {
		$this->allowNonce( 'wpems_booking_mark_paid' );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->did_redirect = true;
				$this->redirect_url = $url;
				return true;
			}
		);

		$_POST = array(
			'wpems_booking_action' => 'mark_paid',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 1,
		);

		$booking = $this->makeBooking(
			array(
				'payment_status' => 'paid',
				'status'         => 'ea-completed',
			)
		);

		$this->bookings->shouldReceive( 'find' )
			->with( 1 )->once()->andReturn( $booking );

		try {
			$this->actions->handle_mark_paid();
		} catch ( \RuntimeException $e ) {
			// exit.
		}

		$this->assertTrue( $this->did_redirect );
		$this->assertStringContainsString( 'already_paid', $this->redirect_url );
	}

	/**
	 * It calls status service to mark paid.
	 *
	 * @return void
	 */
	public function test_handle_mark_paid_calls_status_service(): void {
		$this->allowNonce( 'wpems_booking_mark_paid' );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->did_redirect = true;
				$this->redirect_url = $url;
				return true;
			}
		);

		$_POST = array(
			'wpems_booking_action' => 'mark_paid',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 1,
		);

		$booking = $this->makeBooking(
			array(
				'payment_status' => 'pending',
				'status'         => 'ea-pending',
			)
		);

		$this->bookings->shouldReceive( 'find' )
			->with( 1 )->once()->andReturn( $booking );

		$this->status->shouldReceive( 'mark_completed' )
			->with( 1, 'admin:admin' )
			->once()
			->andReturn( true );

		try {
			$this->actions->handle_mark_paid();
		} catch ( \RuntimeException $e ) {
			// exit.
		}

		$this->assertTrue( $this->did_redirect );
		$this->assertStringContainsString( 'marked_paid', $this->redirect_url );
	}

	/**
	 * It maps sync paid result.
	 *
	 * @return void
	 */
	public function test_handle_check_payment_status_maps_paid_result(): void {
		$this->allowNonce( 'wpems_booking_check_status' );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->did_redirect = true;
				$this->redirect_url = $url;
				return true;
			}
		);

		$_POST = array(
			'wpems_booking_action' => 'check_status',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 1,
		);

		$result = PaymentResult::paid( 1, array() );

		$this->sync->shouldReceive( 'sync_booking' )
			->with( 1, 'admin' )
			->once()
			->andReturn( $result );

		try {
			$this->actions->handle_check_status();
		} catch ( \RuntimeException $e ) {
			// exit.
		}

		$this->assertTrue( $this->did_redirect );
		$this->assertStringContainsString( 'sync_paid', $this->redirect_url );
	}

	/**
	 * It aggregates bulk mark cancelled counts.
	 *
	 * @return void
	 */
	public function test_handle_bulk_mark_cancelled_aggregates_counts(): void {
		$this->status->shouldReceive( 'mark_cancelled' )
			->with( 1, 'admin:bulk:admin' )
			->once()
			->andReturn( true );

		$this->status->shouldReceive( 'mark_cancelled' )
			->with( 2, 'admin:bulk:admin' )
			->once()
			->andReturn( false );

		$this->status->shouldReceive( 'mark_cancelled' )
			->with( 3, 'admin:bulk:admin' )
			->once()
			->andReturn( true );

		$counts = $this->actions->handle_bulk_mark_cancelled( array( 1, 2, 3 ) );

		$this->assertSame( 2, $counts['success'] );
		$this->assertSame( 1, $counts['failed'] );
	}

	/**
	 * It rejects CSV export without capability.
	 *
	 * @return void
	 */
	public function test_handle_export_csv_rejects_without_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$_GET['_wpnonce'] = 'valid';

		$this->expectException( \RuntimeException::class );

		$this->actions->handle_export_csv();
	}

	/**
	 * It handles check_status mapping for pending result.
	 *
	 * @return void
	 */
	public function test_handle_check_payment_status_maps_pending_result(): void {
		$this->allowNonce( 'wpems_booking_check_status' );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->did_redirect = true;
				$this->redirect_url = $url;
				return true;
			}
		);

		$_POST = array(
			'wpems_booking_action' => 'check_status',
			'_wpnonce'             => 'valid_nonce',
			'booking_id'           => 1,
		);

		$result = PaymentResult::pending( 1, 'Still processing' );

		$this->sync->shouldReceive( 'sync_booking' )
			->with( 1, 'admin' )
			->once()
			->andReturn( $result );

		try {
			$this->actions->handle_check_status();
		} catch ( \RuntimeException $e ) {
			// wp_die.
		}

		$this->assertTrue( $this->did_redirect );
		$this->assertStringContainsString( 'sync_pending', $this->redirect_url );
	}
}
