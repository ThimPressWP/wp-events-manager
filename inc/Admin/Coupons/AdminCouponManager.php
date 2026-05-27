<?php
/**
 * Admin coupon manager — list and edit views.
 *
 * @package WPEMS\Admin\Coupons
 */

namespace WPEMS\Admin\Coupons;

use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponQuery;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Top-level admin page for coupons (CRUD).
 */
class AdminCouponManager {

	const PAGE_SLUG  = 'wpems-coupons';
	const CAPABILITY = 'manage_options';

	/** @var CouponRepository */
	private CouponRepository $coupons;

	/** @var CouponEventRepository */
	private CouponEventRepository $coupon_events;

	/** @var CouponUsageRepository */
	private CouponUsageRepository $coupon_usage;

	/** @var CouponAdminActions */
	private CouponAdminActions $actions;

	/**
	 * Constructor.
	 *
	 * @param CouponRepository      $coupons       Coupon repository.
	 * @param CouponEventRepository $coupon_events Event scope repository.
	 * @param CouponUsageRepository $coupon_usage  Usage repository.
	 * @param CouponAdminActions    $actions       Action handlers.
	 */
	public function __construct(
		CouponRepository $coupons,
		CouponEventRepository $coupon_events,
		CouponUsageRepository $coupon_usage,
		CouponAdminActions $actions
	) {
		$this->coupons       = $coupons;
		$this->coupon_events = $coupon_events;
		$this->coupon_usage  = $coupon_usage;
		$this->actions       = $actions;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 31 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wpems_coupon_save', array( $this->actions, 'handle_save' ) );
		add_action( 'admin_post_wpems_coupon_delete', array( $this->actions, 'handle_delete' ) );
		add_action( 'admin_post_wpems_coupon_toggle_status', array( $this->actions, 'handle_toggle_status' ) );
	}

	/**
	 * Add submenu page under Events Manager.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'tp-event',
			__( 'Coupons', 'wp-events-manager' ),
			__( 'Coupons', 'wp-events-manager' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue assets for the coupons page.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'wpems-admin-coupons',
			WPEMS_ASSETS_URI . 'css/admin/coupons.css',
			array(),
			WPEMS_VER
		);
	}

	/**
	 * Render the coupons page (list or edit).
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-events-manager' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action    = sanitize_key( $_GET['action'] ?? 'list' );
		$coupon_id = isset( $_GET['coupon_id'] ) ? absint( $_GET['coupon_id'] ) : 0;

		switch ( $action ) {
			case 'new':
				$this->render_edit( 0 );
				break;
			case 'edit':
				$this->render_edit( $coupon_id );
				break;
			default:
				$this->render_list();
				break;
		}
	}

	/**
	 * Render the coupon list view.
	 *
	 * @return void
	 */
	protected function render_list(): void {
		$query = $this->build_query_from_request( $_GET );
		$list  = new CouponListTable( $this->coupons, $query );
		$list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Coupons', 'wp-events-manager' ); ?></h1>
			<a href="<?php echo esc_url( $this->new_coupon_url() ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-events-manager' ); ?>
			</a>
			<hr class="wp-header-end" />

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>" />
				<?php $list->search_box( __( 'Search', 'wp-events-manager' ), 'coupon' ); ?>
			</form>

			<form method="post">
				<?php $list->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the coupon edit form.
	 *
	 * @param int $coupon_id Coupon ID (0 for new).
	 *
	 * @return void
	 */
	protected function render_edit( int $coupon_id ): void {
		$coupon    = $coupon_id > 0 ? $this->coupons->find( $coupon_id ) : null;
		$event_ids = $coupon_id > 0 ? $this->coupon_events->get_event_ids( $coupon_id ) : array();

		include __DIR__ . '/views/coupon-edit.php';
	}

	/**
	 * Build a CouponQuery from request parameters.
	 *
	 * @param array $request Request array (e.g., $_GET).
	 *
	 * @return CouponQuery
	 */
	public function build_query_from_request( array $request ): CouponQuery {
		$q = new CouponQuery();

		if ( ! empty( $request['status'] ) ) {
			$q->status = sanitize_key( $request['status'] );
		}
		if ( ! empty( $request['discount_type'] ) ) {
			$q->discount_type = sanitize_key( $request['discount_type'] );
		}
		if ( ! empty( $request['s'] ) ) {
			$q->search = sanitize_text_field( $request['s'] );
		}
		if ( ! empty( $request['paged'] ) ) {
			$q->offset = ( absint( $request['paged'] ) - 1 ) * $q->limit;
		}

		return $q;
	}

	/**
	 * Get the "new coupon" URL.
	 *
	 * @return string
	 */
	protected function new_coupon_url(): string {
		return add_query_arg(
			array(
				'page'   => self::PAGE_SLUG,
				'action' => 'new',
			),
			admin_url( 'admin.php' )
		);
	}
}
