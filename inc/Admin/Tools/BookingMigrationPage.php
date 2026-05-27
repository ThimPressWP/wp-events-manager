<?php
/**
 * Admin tool for migrating legacy booking posts.
 *
 * @package WPEMS\Admin\Tools
 * @since   3.0.0
 */

namespace WPEMS\Admin\Tools;

defined( 'ABSPATH' ) || exit;

use WPEMS\Migrations\BookingMigrator;

/**
 * Browser-driven booking migration page.
 */
class BookingMigrationPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'wpems-migrate-bookings';

	/**
	 * Required capability.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * AJAX nonce action.
	 */
	public const NONCE_ACTION = 'wpems_migration';

	/**
	 * Script handle.
	 */
	private const SCRIPT_HANDLE = 'wpems-migration-runner';

	/**
	 * Style handle.
	 */
	private const STYLE_HANDLE = 'wpems-migration-runner';

	/**
	 * Booking migrator.
	 *
	 * @var BookingMigrator
	 */
	private BookingMigrator $migrator;

	/**
	 * Constructor.
	 *
	 * @param BookingMigrator $migrator Booking migrator.
	 */
	public function __construct( BookingMigrator $migrator ) {
		$this->migrator = $migrator;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wpems_migration_count', array( $this, 'ajax_count' ) );
		add_action( 'wp_ajax_wpems_migration_run_batch', array( $this, 'ajax_run_batch' ) );
		add_action( 'wp_ajax_wpems_migration_verify', array( $this, 'ajax_verify' ) );
		add_action( 'wp_ajax_wpems_migration_rebuild_inventory', array( $this, 'ajax_rebuild_inventory' ) );
	}

	/**
	 * Add the migration submenu page.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'tp-event-setting',
			__( 'Migrate bookings', 'wp-events-manager' ),
			__( 'Migrate bookings', 'wp-events-manager' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue page-specific assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		$script_path = WPEMS_PATH . 'assets/dist/js/admin/migration-runner.min.js';
		$script_url  = WPEMS_ASSETS_URI . 'dist/js/admin/migration-runner.min.js';
		$asset_path  = WPEMS_PATH . 'assets/dist/js/admin/migration-runner.min.asset.php';

		if ( ! file_exists( $script_path ) ) {
			$script_path = WPEMS_PATH . 'assets/src/js/admin/migration-runner.js';
			$script_url  = WPEMS_ASSETS_URI . 'src/js/admin/migration-runner.js';
			$asset_path  = '';
		}

		$asset = array(
			'dependencies' => array(),
			'version'      => file_exists( $script_path ) ? (string) filemtime( $script_path ) : WPEMS_VER,
		);

		if ( $asset_path && file_exists( $asset_path ) ) {
			$loaded_asset = include $asset_path;
			if ( is_array( $loaded_asset ) ) {
				$asset = array_merge( $asset, $loaded_asset );
			}
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$script_url,
			(array) ( $asset['dependencies'] ?? array() ),
			(string) ( $asset['version'] ?? WPEMS_VER ),
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wpemsMigration',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'batchSize' => 200,
				'i18n'      => array(
					'idle'      => __( 'Idle', 'wp-events-manager' ),
					'counting'  => __( 'Counting...', 'wp-events-manager' ),
					'running'   => __( 'Running...', 'wp-events-manager' ),
					'verifying' => __( 'Verifying...', 'wp-events-manager' ),
					'done'      => __( 'Done', 'wp-events-manager' ),
					'error'     => __( 'Error: %s', 'wp-events-manager' ),
				),
			)
		);

		$style_path = WPEMS_PATH . 'assets/admin/migration-runner.css';
		wp_enqueue_style(
			self::STYLE_HANDLE,
			WPEMS_ASSETS_URI . 'admin/migration-runner.css',
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : WPEMS_VER
		);
	}

	/**
	 * Render the migration page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		?>
		<div class="wrap" id="wpems-migrate-bookings">
			<h1><?php echo esc_html__( 'Migrate bookings', 'wp-events-manager' ); ?></h1>
			<p class="description">
				<?php echo esc_html__( 'Imports legacy event_auth_book posts into the new bookings table. Each batch runs in one AJAX request so the browser can safely resume if interrupted.', 'wp-events-manager' ); ?>
			</p>

			<div class="wpems-migration-summary">
				<span><?php echo esc_html__( 'Pending:', 'wp-events-manager' ); ?> <strong data-count="pending">&mdash;</strong></span>
				<span><?php echo esc_html__( 'Imported (this session):', 'wp-events-manager' ); ?> <strong data-count="imported">0</strong></span>
				<span><?php echo esc_html__( 'Failed (this session):', 'wp-events-manager' ); ?> <strong data-count="failed">0</strong></span>
			</div>

			<div class="wpems-migration-progress" aria-hidden="true">
				<div class="bar" data-progress="0"></div>
			</div>

			<div class="wpems-migration-actions">
				<button type="button" class="button" data-action="count"><?php echo esc_html__( 'Count pending', 'wp-events-manager' ); ?></button>
				<button type="button" class="button button-primary" data-action="run"><?php echo esc_html__( 'Run batch (200)', 'wp-events-manager' ); ?></button>
				<button type="button" class="button" data-action="auto"><?php echo esc_html__( 'Auto-run', 'wp-events-manager' ); ?></button>
				<button type="button" class="button" data-action="stop" disabled><?php echo esc_html__( 'Stop', 'wp-events-manager' ); ?></button>
				<button type="button" class="button" data-action="verify"><?php echo esc_html__( 'Verify', 'wp-events-manager' ); ?></button>
				<button type="button" class="button" data-action="rebuild"><?php echo esc_html__( 'Rebuild inventory', 'wp-events-manager' ); ?></button>
			</div>

			<pre class="wpems-migration-log" aria-live="polite"></pre>
		</div>
		<?php
	}

	/**
	 * Count pending legacy bookings.
	 *
	 * @return void
	 */
	public function ajax_count(): void {
		if ( ! $this->check_security() ) {
			return;
		}

		$this->json_success(
			array(
				'pending' => $this->migrator->count_pending(),
			)
		);
	}

	/**
	 * Run one migration batch.
	 *
	 * @return void
	 */
	public function ajax_run_batch(): void {
		if ( ! $this->check_security() ) {
			return;
		}

		$raw_batch_size = isset( $_POST['batch_size'] ) ? wp_unslash( $_POST['batch_size'] ) : 200;
		$batch_size     = max( 10, min( 1000, absint( $raw_batch_size ) ) );
		$result         = $this->migrator->run_batch( $batch_size );

		$this->json_success(
			array(
				'imported'  => $result->imported,
				'skipped'   => $result->skipped,
				'failed'    => $result->failed,
				'errors'    => $result->errors,
				'remaining' => $result->remaining,
			)
		);
	}

	/**
	 * Verify migrated bookings.
	 *
	 * @return void
	 */
	public function ajax_verify(): void {
		if ( ! $this->check_security() ) {
			return;
		}

		$report = $this->migrator->verify();

		$this->json_success(
			array(
				'ok'                => $report->ok,
				'legacy_total'      => $report->legacy_total,
				'new_total'         => $report->new_total,
				'diff_count'        => $report->diff_count,
				'status_diff'       => $report->status_diff,
				'total_amount_diff' => $report->total_amount_diff,
			)
		);
	}

	/**
	 * Rebuild inventory counters for migrated bookings.
	 *
	 * @return void
	 */
	public function ajax_rebuild_inventory(): void {
		if ( ! $this->check_security() ) {
			return;
		}

		$report = $this->migrator->rebuild_inventory();

		$this->json_success(
			array(
				'events_processed' => $report->events_processed,
				'rows_updated'     => $report->rows_updated,
			)
		);
	}

	/**
	 * Check AJAX nonce and capability.
	 *
	 * @return bool
	 */
	private function check_security(): bool {
		if ( false === check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			$this->json_error( 'invalid_nonce', __( 'Invalid migration nonce.', 'wp-events-manager' ), 403 );
			return false;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			$this->json_error( 'forbidden', __( 'You do not have permission to migrate bookings.', 'wp-events-manager' ), 403 );
			return false;
		}

		return true;
	}

	/**
	 * Send a successful JSON response.
	 *
	 * @param mixed $data Response data.
	 *
	 * @return void
	 */
	private function json_success( $data ): void {
		wp_send_json_success( $data );
	}

	/**
	 * Send an error JSON response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $http    HTTP status.
	 *
	 * @return void
	 */
	private function json_error( string $code, string $message, int $http = 400 ): void {
		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$http
		);
	}
}
