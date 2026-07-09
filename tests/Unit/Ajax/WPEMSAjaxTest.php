<?php
/**
 * Ajax handler tests.
 *
 * @package WPEMS\Tests\Unit\Ajax
 */

namespace WPEMS\Tests\Unit\Ajax;

use Brain\Monkey\Functions;
use Mockery;
use stdClass;
use WPEMS\Databases\EventDB;
use WPEMS\Models\BookingPostModel;
use WPEMS\Models\EventPostModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test Ajax helper behavior.
 */
class WPEMSAjaxTest extends TestCase {

	/**
	 * Reset model caches.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( EventPostModel::class, 'instances', array() );
		$this->resetStaticProperty( BookingPostModel::class, 'instances', array() );
		$this->resetStaticProperty( EventDB::class, 'instance', null );
	}

	/**
	 * It returns the plugin login URL when guests submit protected actions.
	 *
	 * @return void
	 */
	public function test_must_login_uses_wpems_login_url(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wpems_login_url' )->justReturn( 'https://example.test/login' );
		require_once WPEMS_INC . 'class-wpems-ajax.php';

		$handler = new \WPEMS_Ajax();

		Functions\expect( 'wp_send_json' )
			->once()
			->andReturnUsing(
				function ( $response ) {
					$this->assertFalse( $response['status'] );
					$this->assertStringContainsString( 'https://example.test/login', $response['message'] );

					throw new \Error( 'wp_send_json intercepted' );
				}
			);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'wp_send_json intercepted' );

		$handler->must_login();
	}

	/**
	 * It registers a free event through model-backed booking flow.
	 *
	 * @return void
	 */
	public function test_event_auth_register_uses_models_for_free_booking(): void {
		global $wpdb;

		Functions\when( 'add_action' )->justReturn( true );
		require_once WPEMS_INC . 'class-wpems-ajax.php';

		$_SERVER['REQUEST_METHOD']          = 'POST';
		$_POST['action']                    = 'event_auth_register';
		$_POST['event_id']                  = '88';
		$_POST['qty']                       = '2';
		$_POST['event_auth_register_nonce'] = 'nonce';

		$wpdb           = Mockery::mock( stdClass::class );
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->users    = 'wp_users';
		$wpdb->shouldReceive( 'prepare' )->twice()->andReturn( 'prepared quantity sql' );
		$wpdb->shouldReceive( 'get_var' )->twice()->with( 'prepared quantity sql' )->andReturn( '0' );

		$user = (object) array(
			'ID'            => 44,
			'user_nicename' => 'demo_user',
		);

		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'wp_get_current_user' )->justReturn( $user );
		Functions\when( 'wpems_get_option' )->alias(
			function ( $key, $default = '' ) {
				if ( 'email_register_times' === $key ) {
					return 'once';
				}

				return $default;
			}
		);
		Functions\when( 'wpems_payment_gateways' )->justReturn( array() );
		Functions\when( 'wpems_get_currency' )->justReturn( 'USD' );
		Functions\when( 'wpems_format_ID' )->alias(
			function ( $id ) {
				return '#' . $id;
			}
		);
		Functions\when( 'wpems_add_notice' )->justReturn( true );
		Functions\when( 'wpems_account_url' )->justReturn( 'https://example.test/account' );

		Functions\when( 'get_post' )->alias(
			function ( $post_id ) {
				if ( 88 === $post_id ) {
					return $this->makePost( 88, 'tp_event', 'Free event' );
				}

				if ( 901 === $post_id ) {
					return $this->makePost( 901, 'event_auth_book', 'Booking #901', 'ea-pending' );
				}

				return null;
			}
		);

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key ) {
				$this->assertSame( 88, $post_id );
				$meta = array(
					'tp_event_price' => '0',
					'tp_event_qty'   => '10',
				);

				return $meta[ $key ] ?? '';
			}
		);

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturnUsing(
				function ( $data, $wp_error ) {
					$this->assertTrue( $wp_error );
					$this->assertSame( 'event_auth_book', $data['post_type'] );
					$this->assertSame( 88, $data['meta_input']['ea_booking_event_id'] );
					$this->assertSame( 44, $data['meta_input']['ea_booking_user_id'] );
					$this->assertSame( 2, $data['meta_input']['ea_booking_qty'] );
					$this->assertSame( 0.0, $data['meta_input']['ea_booking_price'] );
					$this->assertSame( 'USD', $data['meta_input']['ea_booking_currency'] );

					return 901;
				}
			);

		Functions\expect( 'get_post_status' )
			->once()
			->with( 901 )
			->andReturn( 'ea-pending' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				array(
					'ID'          => 901,
					'post_status' => 'ea-completed',
				)
			)
			->andReturn( 901 );

		Functions\when( 'do_action' )->justReturn( true );

		Functions\expect( 'get_userdata' )
			->once()
			->with( 44 )
			->andReturn( (object) array( 'user_email' => 'buyer@example.com' ) );

		Functions\expect( 'wp_send_json' )
			->once()
			->andReturnUsing(
				function ( $response ) {
					$this->assertTrue( $response['status'] );
					$this->assertSame( 'https://example.test/account', $response['url'] );

					throw new \Error( 'wp_send_json intercepted' );
				}
			);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'wp_send_json intercepted' );

		$handler = new \WPEMS_Ajax();
		$handler->event_auth_register();
	}

	/**
	 * It rejects zero quantity before creating a booking.
	 *
	 * @return void
	 */
	public function test_event_auth_register_rejects_zero_quantity(): void {
		Functions\when( 'add_action' )->justReturn( true );
		require_once WPEMS_INC . 'class-wpems-ajax.php';

		$_SERVER['REQUEST_METHOD']          = 'POST';
		$_POST['action']                    = 'event_auth_register';
		$_POST['event_id']                  = '88';
		$_POST['qty']                       = '0';
		$_POST['event_auth_register_nonce'] = 'nonce';

		Functions\expect( 'check_ajax_referer' )
			->once()
			->with( 'event_auth_register_nonce', 'event_auth_register_nonce' )
			->andReturn( true );

		Functions\expect( 'wpems_add_notice' )
			->once()
			->with( 'error', 'Quantity must integer' )
			->andReturn( true );

		Functions\expect( 'wpems_print_notices' )
			->once()
			->andReturnUsing(
				function () {
					echo 'Quantity must integer';
				}
			);

		Functions\expect( 'get_post' )->never();
		Functions\expect( 'wp_insert_post' )->never();

		Functions\expect( 'wp_send_json' )
			->once()
			->andReturnUsing(
				function ( $response ) {
					$this->assertFalse( $response['status'] );
					$this->assertStringContainsString( 'Quantity must integer', $response['message'] );

					throw new \Error( 'wp_send_json intercepted' );
				}
			);

		$buffer_level = ob_get_level();
		ob_start();

		try {
			$this->expectException( \Error::class );
			$this->expectExceptionMessage( 'wp_send_json intercepted' );

			$handler = new \WPEMS_Ajax();
			$handler->event_auth_register();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}
	}
}
