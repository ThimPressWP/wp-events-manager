<?php
/**
 * Coupon admin action handlers.
 *
 * @package WPEMS\Admin\Coupons
 */

namespace WPEMS\Admin\Coupons;

use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Services\CouponService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles form POST actions for coupon CRUD.
 */
class CouponAdminActions {

	const CAPABILITY = 'manage_options';

	/** @var CouponRepository */
	private CouponRepository $coupons;

	/** @var CouponEventRepository */
	private CouponEventRepository $coupon_events;

	/** @var CouponUsageRepository */
	private CouponUsageRepository $coupon_usage;

	/** @var CouponService */
	private CouponService $coupon_service;

	/**
	 * Constructor.
	 *
	 * @param CouponRepository      $coupons       Coupon repository.
	 * @param CouponEventRepository $coupon_events Event scope repository.
	 * @param CouponUsageRepository $coupon_usage  Usage repository.
	 * @param CouponService         $coupon_service Coupon service.
	 */
	public function __construct(
		CouponRepository $coupons,
		CouponEventRepository $coupon_events,
		CouponUsageRepository $coupon_usage,
		CouponService $coupon_service
	) {
		$this->coupons        = $coupons;
		$this->coupon_events  = $coupon_events;
		$this->coupon_usage   = $coupon_usage;
		$this->coupon_service = $coupon_service;
	}

	// ─────────────────────────────────────────────
	// Security helpers
	// ─────────────────────────────────────────────

	/**
	 * Verify nonce and capability.
	 *
	 * @param string $nonce_action Nonce action.
	 *
	 * @return void
	 */
	protected function check_security( string $nonce_action ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : ( $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? '' );
		$nonce = sanitize_text_field( wp_unslash( $raw ) );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die(
				esc_html__( 'Invalid nonce.', 'wp-events-manager' ),
				'',
				array( 'response' => 403 )
			);
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'Permission denied.', 'wp-events-manager' ),
				'',
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * PRG redirect with message code.
	 *
	 * @param string   $code      Message code.
	 * @param string   $type      'success' or 'error'.
	 * @param int|null $coupon_id Optional coupon ID for edit redirect.
	 *
	 * @return never
	 */
	protected function redirect_with_message( string $code, string $type, ?int $coupon_id = null ): void {
		$args = array(
			'page'         => AdminCouponManager::PAGE_SLUG,
			'wpems_notice' => $code,
			'wpems_type'   => $type,
		);

		if ( $coupon_id ) {
			$args['action']    = 'edit';
			$args['coupon_id'] = $coupon_id;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		wp_die( '', '', array( 'response' => 200 ) );
	}

	// ─────────────────────────────────────────────
	// Sanitization
	// ─────────────────────────────────────────────

	/**
	 * Sanitize POST data into coupon fields.
	 *
	 * @param array $post Raw POST data.
	 *
	 * @return array{coupon: array, event_ids: int[]}
	 */
	protected function sanitize_post( array $post ): array {
		$discount_type = in_array( $post['discount_type'] ?? '', array( CouponModel::TYPE_PERCENT, CouponModel::TYPE_AMOUNT, CouponModel::TYPE_HYBRID ), true )
			? $post['discount_type'] : CouponModel::TYPE_PERCENT;

		$applies_to = in_array( $post['applies_to'] ?? '', array( CouponModel::APPLIES_ALL, CouponModel::APPLIES_SPECIFIC ), true )
			? $post['applies_to'] : CouponModel::APPLIES_ALL;

		return array(
			'coupon' => array(
				'code'                 => strtoupper( sanitize_text_field( $post['code'] ?? '' ) ),
				'description'          => wp_kses_post( $post['description'] ?? '' ),
				'discount_type'        => $discount_type,
				'percent_value'        => isset( $post['percent_value'] ) && is_numeric( $post['percent_value'] )
					? bcadd( (string) $post['percent_value'], '0', 4 ) : null,
				'amount_value'         => isset( $post['amount_value'] ) && is_numeric( $post['amount_value'] )
					? bcadd( (string) $post['amount_value'], '0', 4 ) : null,
				'max_discount_amount'  => isset( $post['max_discount_amount'] ) && is_numeric( $post['max_discount_amount'] )
					? bcadd( (string) $post['max_discount_amount'], '0', 4 ) : null,
				'applies_to'           => $applies_to,
				'usage_limit'          => '' !== ( $post['usage_limit'] ?? '' ) ? max( 0, (int) $post['usage_limit'] ) : null,
				'usage_limit_per_user' => '' !== ( $post['usage_limit_per_user'] ?? '' ) ? max( 0, (int) $post['usage_limit_per_user'] ) : null,
				'min_order_amount'     => isset( $post['min_order_amount'] ) && is_numeric( $post['min_order_amount'] )
					? bcadd( (string) $post['min_order_amount'], '0', 4 ) : null,
				'starts_at_gmt'        => ! empty( $post['starts_at_gmt'] )
					? gmdate( 'Y-m-d H:i:s', strtotime( $post['starts_at_gmt'] ) ) : null,
				'expires_at_gmt'       => ! empty( $post['expires_at_gmt'] )
					? gmdate( 'Y-m-d H:i:s', strtotime( $post['expires_at_gmt'] ) ) : null,
				'status'               => 'yes' === ( $post['status'] ?? '' ) ? CouponModel::STATUS_ACTIVE : CouponModel::STATUS_INACTIVE,
			),
			'event_ids' => array_filter( array_map( 'absint', (array) ( $post['event_ids'] ?? array() ) ) ),
		);
	}

	// ─────────────────────────────────────────────
	// Handlers
	// ─────────────────────────────────────────────

	/**
	 * Handle save (insert or update).
	 *
	 * @return void
	 */
	public function handle_save(): void {
		$this->check_security( 'wpems_coupon_save' );

		$coupon_id = absint( $_POST['coupon_id'] ?? 0 );
		$data = $this->sanitize_post( wp_unslash( $_POST ) );

		// Validate code.
		if ( '' === $data['coupon']['code'] ) {
			$this->redirect_with_message( 'code_required', 'error' );
		}

		try {
			if ( 0 === $coupon_id ) {
				$coupon_id = $this->coupons->insert( $data['coupon'] );
				$is_new    = true;
			} else {
				// Verify coupon exists.
				$existing = $this->coupons->find( $coupon_id );
				if ( ! $existing ) {
					$this->redirect_with_message( 'not_found', 'error' );
				}
				$this->coupons->update( $coupon_id, $data['coupon'] );
				$is_new = false;
			}
		} catch ( \RuntimeException $e ) {
			if ( 'duplicate_code' === $e->getMessage() ) {
				$this->redirect_with_message( 'duplicate_code', 'error', $coupon_id ?: null );
			}
			throw $e;
		}

		// Event scope.
		if ( CouponModel::APPLIES_SPECIFIC === $data['coupon']['applies_to'] ) {
			$this->coupon_events->set_events( $coupon_id, $data['event_ids'] );
		} else {
			$this->coupon_events->set_events( $coupon_id, array() );
		}

		$this->redirect_with_message(
			$is_new ? 'coupon_created' : 'coupon_saved',
			'success',
			$coupon_id
		);
	}

	/**
	 * Handle delete.
	 *
	 * @return void
	 */
	public function handle_delete(): void {
		$coupon_id = absint( $_GET['coupon_id'] ?? 0 );

		$this->check_security( 'wpems_coupon_delete_' . $coupon_id );

		$coupon = $this->coupons->find( $coupon_id );
		if ( ! $coupon ) {
			$this->redirect_with_message( 'not_found', 'error' );
		}

		// Void existing usage first.
		global $wpdb;
		$table = \WPEMS\Tables\TableNames::coupon_usage();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$booking_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT booking_id FROM {$table} WHERE coupon_id = %d AND voided_at_gmt IS NULL",
				$coupon_id
			)
		);

		foreach ( (array) $booking_ids as $booking_id ) {
			$this->coupon_service->void_usage( (int) $booking_id, 'coupon_deleted' );
		}

		$this->coupon_events->delete_all_for_coupon( $coupon_id );
		$this->coupons->delete( $coupon_id );

		$this->redirect_with_message( 'coupon_deleted', 'success' );
	}

	/**
	 * Handle toggle status.
	 *
	 * @return void
	 */
	public function handle_toggle_status(): void {
		$coupon_id = absint( $_GET['coupon_id'] ?? 0 );

		$this->check_security( 'wpems_coupon_toggle_status_' . $coupon_id );

		$coupon = $this->coupons->find( $coupon_id );
		if ( ! $coupon ) {
			$this->redirect_with_message( 'not_found', 'error' );
		}

		$new_status = $coupon->is_active() ? CouponModel::STATUS_INACTIVE : CouponModel::STATUS_ACTIVE;
		$this->coupons->update( $coupon_id, array( 'status' => $new_status ) );

		$this->redirect_with_message(
			'coupon_' . $new_status,
			'success',
			$coupon_id
		);
	}

	/**
	 * Handle bulk actions from list table.
	 *
	 * @param string $action     Bulk action name.
	 * @param int[]  $coupon_ids Coupon IDs.
	 *
	 * @return array{success: int, failed: int}
	 */
	public function handle_bulk_action( string $action, array $coupon_ids ): array {
		$counts = array(
			'success' => 0,
			'failed'  => 0,
		);

		foreach ( $coupon_ids as $id ) {
			$id = (int) $id;
			$ok = false;

			switch ( $action ) {
				case 'activate':
					$ok = $this->coupons->update( $id, array( 'status' => CouponModel::STATUS_ACTIVE ) );
					break;
				case 'deactivate':
					$ok = $this->coupons->update( $id, array( 'status' => CouponModel::STATUS_INACTIVE ) );
					break;
				case 'delete':
					// Void usage then delete (simplified per-item delete).
					global $wpdb;
					$table = \WPEMS\Tables\TableNames::coupon_usage();
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$booking_ids = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT booking_id FROM {$table} WHERE coupon_id = %d AND voided_at_gmt IS NULL",
							$id
						)
					);
					foreach ( (array) $booking_ids as $bid ) {
						$this->coupon_service->void_usage( (int) $bid, 'coupon_deleted_bulk' );
					}
					$this->coupon_events->delete_all_for_coupon( $id );
					$ok = $this->coupons->delete( $id );
					break;
			}

			if ( $ok ) {
				++$counts['success'];
			} else {
				++$counts['failed'];
			}
		}

		return $counts;
	}
}
