<?php
/**
 * Unit tests for WPEMS\Admin\Tools\BookingMigrationPage.
 *
 * @package WPEMS\Tests\Unit\Admin\Tools
 */

namespace WPEMS\Tests\Unit\Admin\Tools;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Admin\Tools\BookingMigrationPage;
use WPEMS\Migrations\BatchResult;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Migrations\InventoryRebuildReport;
use WPEMS\Migrations\VerifyReport;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Admin\Tools\BookingMigrationPage
 */
class BookingMigrationPageTest extends TestCase {

	/**
	 * Migrator mock.
	 *
	 * @var Mockery\MockInterface|BookingMigrator
	 */
	private $migrator;

	/**
	 * Page under test.
	 *
	 * @var BookingMigrationPage
	 */
	private BookingMigrationPage $page;

	/**
	 * Set up mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		defined( 'WPEMS_ASSETS_URI' ) || define( 'WPEMS_ASSETS_URI', 'https://example.test/wp-content/plugins/wp-events-manager/assets/' );

		$this->migrator = Mockery::mock( BookingMigrator::class );
		$this->page     = new BookingMigrationPage( $this->migrator );
	}

	/**
	 * @test
	 */
	public function test_register_adds_menu_enqueue_and_ajax_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_menu', array( $this->page, 'add_menu' ), 20 )
			->andReturn( true );
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_enqueue_scripts', array( $this->page, 'enqueue_assets' ) )
			->andReturn( true );

		foreach ( array( 'count', 'run_batch', 'verify', 'rebuild_inventory' ) as $action ) {
			Functions\expect( 'add_action' )
				->once()
				->with( 'wp_ajax_wpems_migration_' . $action, Mockery::type( 'array' ) )
				->andReturn( true );
		}

		$this->page->register();
		$this->addToAssertionCount( 6 );
	}

	/**
	 * @test
	 */
	public function test_add_menu_registers_under_events_manager_menu(): void {
		Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				'tp-event-setting',
				'Migrate bookings',
				'Migrate bookings',
				'manage_options',
				'wpems-migrate-bookings',
				array( $this->page, 'render_page' )
			)
			->andReturn( 'events-manager_page_wpems-migrate-bookings' );

		$this->page->add_menu();
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @test
	 */
	public function test_enqueue_assets_localizes_nonce_and_ajax_url(): void {
		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
			}
		);
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce-123' );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'wpems-migration-runner',
				Mockery::on(
					static function ( string $url ): bool {
						return false !== strpos( $url, 'migration-runner' );
					}
				),
				Mockery::type( 'array' ),
				Mockery::type( 'string' ),
				true
			)
			->andReturn( true );

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'wpems-migration-runner',
				'wpemsMigration',
				Mockery::on(
					static function ( array $data ): bool {
						return 'https://example.test/wp-admin/admin-ajax.php' === $data['ajaxUrl']
							&& 'nonce-123' === $data['nonce']
							&& 200 === $data['batchSize'];
					}
				)
			)
			->andReturn( true );

		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with( 'wpems-migration-runner', Mockery::type( 'string' ), array(), Mockery::type( 'string' ) )
			->andReturn( true );

		$this->page->enqueue_assets( 'events-manager_page_wpems-migrate-bookings' );
		$this->addToAssertionCount( 3 );
	}

	/**
	 * @test
	 */
	public function test_enqueue_assets_ignores_other_admin_pages(): void {
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_localize_script' )->never();

		$this->page->enqueue_assets( 'dashboard_page_other' );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @test
	 */
	public function test_render_page_outputs_runner_shell(): void {
		ob_start();
		$this->page->render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'id="wpems-migrate-bookings"', $html );
		$this->assertStringContainsString( 'data-action="count"', $html );
		$this->assertStringContainsString( 'data-action="run"', $html );
		$this->assertStringContainsString( 'data-action="auto"', $html );
		$this->assertStringContainsString( 'data-action="stop"', $html );
		$this->assertStringContainsString( 'data-action="verify"', $html );
		$this->assertStringContainsString( 'data-action="rebuild"', $html );
	}

	/**
	 * @test
	 */
	public function test_ajax_count_returns_pending_count(): void {
		$this->expect_security_ok();

		$this->migrator->shouldReceive( 'count_pending' )->once()->andReturn( 12 );
		$this->expect_success_response(
			static function ( array $data ): bool {
				return 12 === $data['pending'];
			}
		);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_success' );

		$this->page->ajax_count();
	}

	/**
	 * @test
	 */
	public function test_ajax_run_batch_passes_clamped_size(): void {
		$_POST['batch_size'] = '10000';
		$result              = new BatchResult();
		$result->imported    = 3;
		$result->skipped     = 1;
		$result->failed      = 0;
		$result->remaining   = 4;

		$this->expect_security_ok();
		$this->migrator->shouldReceive( 'run_batch' )->once()->with( 1000 )->andReturn( $result );
		$this->expect_success_response(
			static function ( array $data ): bool {
				return 3 === $data['imported']
					&& 1 === $data['skipped']
					&& 0 === $data['failed']
					&& 4 === $data['remaining'];
			}
		);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_success' );

		$this->page->ajax_run_batch();
	}

	/**
	 * @test
	 *
	 * @dataProvider ajax_method_provider
	 */
	public function test_ajax_endpoints_fail_without_nonce( string $method ): void {
		Functions\expect( 'check_ajax_referer' )
			->once()
			->with( BookingMigrationPage::NONCE_ACTION, 'nonce', false )
			->andReturn( false );
		Functions\expect( 'current_user_can' )->never();
		$this->expect_error_response( 'invalid_nonce', 403 );

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_error' );

		$this->page->{$method}();
	}

	/**
	 * @test
	 *
	 * @dataProvider ajax_method_provider
	 */
	public function test_ajax_endpoints_fail_without_capability( string $method ): void {
		Functions\expect( 'check_ajax_referer' )
			->once()
			->with( BookingMigrationPage::NONCE_ACTION, 'nonce', false )
			->andReturn( 1 );
		Functions\when( 'current_user_can' )->alias(
			static function ( string $capability ): bool {
				return BookingMigrationPage::CAPABILITY === $capability ? false : true;
			}
		);
		$this->expect_error_response( 'forbidden', 403 );

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_error' );

		$this->page->{$method}();
	}

	/**
	 * @test
	 */
	public function test_ajax_verify_returns_report(): void {
		$report                    = new VerifyReport();
		$report->ok                = true;
		$report->legacy_total      = 10;
		$report->new_total         = 10;
		$report->diff_count        = 0;
		$report->status_diff       = array();
		$report->total_amount_diff = array();

		$this->expect_security_ok();
		$this->migrator->shouldReceive( 'verify' )->once()->andReturn( $report );
		$this->expect_success_response(
			static function ( array $data ): bool {
				return true === $data['ok']
					&& 10 === $data['legacy_total']
					&& 10 === $data['new_total']
					&& 0 === $data['diff_count'];
			}
		);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_success' );

		$this->page->ajax_verify();
	}

	/**
	 * @test
	 */
	public function test_ajax_rebuild_inventory_returns_report(): void {
		$report                   = new InventoryRebuildReport();
		$report->events_processed = 2;
		$report->rows_updated     = 2;

		$this->expect_security_ok();
		$this->migrator->shouldReceive( 'rebuild_inventory' )->once()->andReturn( $report );
		$this->expect_success_response(
			static function ( array $data ): bool {
				return 2 === $data['events_processed']
					&& 2 === $data['rows_updated'];
			}
		);

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'json_success' );

		$this->page->ajax_rebuild_inventory();
	}

	/**
	 * AJAX endpoint provider.
	 *
	 * @return array<string, array{string}>
	 */
	public function ajax_method_provider(): array {
		return array(
			'count'             => array( 'ajax_count' ),
			'run_batch'         => array( 'ajax_run_batch' ),
			'verify'            => array( 'ajax_verify' ),
			'rebuild_inventory' => array( 'ajax_rebuild_inventory' ),
		);
	}

	/**
	 * Expect AJAX security to pass.
	 *
	 * @return void
	 */
	private function expect_security_ok(): void {
		Functions\expect( 'check_ajax_referer' )
			->once()
			->with( BookingMigrationPage::NONCE_ACTION, 'nonce', false )
			->andReturn( 1 );
	}

	/**
	 * Expect a successful JSON response.
	 *
	 * @param callable $assertion Response assertion callback.
	 *
	 * @return void
	 */
	private function expect_success_response( callable $assertion ): void {
		Functions\expect( 'wp_send_json_success' )
			->once()
			->andReturnUsing(
				function ( $data ) use ( $assertion ) {
					$this->assertTrue( $assertion( $data ) );

					throw new \Error( 'json_success' );
				}
			);
	}

	/**
	 * Expect an error JSON response.
	 *
	 * @param string $code Error code.
	 * @param int    $http HTTP status.
	 *
	 * @return void
	 */
	private function expect_error_response( string $code, int $http ): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->andReturnUsing(
				function ( $data, $status_code ) use ( $code, $http ) {
					$this->assertSame( $code, $data['code'] );
					$this->assertSame( $http, $status_code );

					throw new \Error( 'json_error' );
				}
			);
	}
}
