<?php
/**
 * Coupon list table for the admin coupons page.
 *
 * @package WPEMS\Admin\Coupons
 */

namespace WPEMS\Admin\Coupons;

use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponQuery;
use WPEMS\Repositories\CouponRepository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Coupon list table.
 *
 * @extends \WP_List_Table
 */
class CouponListTable extends \WP_List_Table {

	/** @var string[] */
	private const ORDERBY_WHITELIST = array( 'code', 'usage_count', 'created_at_gmt' );

	/** @var string[] */
	private const ORDER_WHITELIST = array( 'ASC', 'DESC' );

	/** @var CouponRepository */
	private CouponRepository $coupons;

	/** @var CouponQuery */
	private CouponQuery $query;

	/**
	 * Construct.
	 *
	 * @param CouponRepository $coupons Coupon repository.
	 * @param CouponQuery      $query   Query filter.
	 */
	public function __construct( CouponRepository $coupons, CouponQuery $query ) {
		parent::__construct(
			array(
				'singular' => 'coupon',
				'plural'   => 'coupons',
				'ajax'     => false,
			)
		);

		$this->coupons = $coupons;
		$this->query   = $query;
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'       => '<input type="checkbox" />',
			'code'     => __( 'Code', 'wp-events-manager' ),
			'discount' => __( 'Discount', 'wp-events-manager' ),
			'usage'    => __( 'Usage', 'wp-events-manager' ),
			'status'   => __( 'Status', 'wp-events-manager' ),
			'dates'    => __( 'Validity', 'wp-events-manager' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'code'  => array( 'code', false ),
			'usage' => array( 'usage_count', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'activate'   => __( 'Activate', 'wp-events-manager' ),
			'deactivate' => __( 'Deactivate', 'wp-events-manager' ),
			'delete'     => __( 'Delete', 'wp-events-manager' ),
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		// Apply sorting from request (clamped to allow-lists).
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order   = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : '';

		if ( $orderby && in_array( $orderby, self::ORDERBY_WHITELIST, true ) ) {
			$this->query->order_by = $orderby;
		}
		if ( $order && in_array( $order, self::ORDER_WHITELIST, true ) ) {
			$this->query->order = $order;
		}

		$per_page     = $this->get_items_per_page( 'coupons_per_page', 20 );
		$current_page = $this->get_pagenum();

		$this->query->limit  = $per_page;
		$this->query->offset = ( $current_page - 1 ) * $per_page;

		$total_items = $this->coupons->count( $this->query );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->coupons->query( $this->query );
	}

	/**
	 * Render default column (fallback).
	 *
	 * @param mixed  $item        Coupon row.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return '';
	}

	/**
	 * Render checkbox column.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="coupon[]" value="%d" />',
			$item->get_id()
		);
	}

	/**
	 * Render code column with row actions.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_code( $item ): string {
		$edit_url = add_query_arg(
			array(
				'page'      => AdminCouponManager::PAGE_SLUG,
				'action'    => 'edit',
				'coupon_id' => $item->get_id(),
			),
			admin_url( 'admin.php' )
		);

		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'    => 'wpems_coupon_delete',
					'coupon_id' => $item->get_id(),
				),
				admin_url( 'admin-post.php' )
			),
			'wpems_coupon_delete_' . $item->get_id()
		);

		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'wp-events-manager' )
			),
			'delete' => sprintf(
				'<a href="%s" class="delete" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Delete this coupon? Existing usage rows will be voided.', 'wp-events-manager' ) ),
				esc_html__( 'Delete', 'wp-events-manager' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->get_code() ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render discount column.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_discount( $item ): string {
		switch ( $item->get_discount_type() ) {
			case CouponModel::TYPE_PERCENT:
				return esc_html( $item->get_percent_value() . '%' );
			case CouponModel::TYPE_AMOUNT:
				return esc_html( (string) $item->get_amount_value() );
			case CouponModel::TYPE_HYBRID:
				return esc_html( $item->get_percent_value() . '% (cap ' . $item->get_max_discount_amount() . ')' );
			default:
				return '—';
		}
	}

	/**
	 * Render usage column.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_usage( $item ): string {
		$limit = $item->get_usage_limit();
		if ( null === $limit ) {
			return esc_html( $item->get_usage_count() . ' / ∞' );
		}

		return esc_html( $item->get_usage_count() . ' / ' . $limit );
	}

	/**
	 * Render status column as badge.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_status( $item ): string {
		$labels = array(
			CouponModel::STATUS_ACTIVE   => __( 'Active', 'wp-events-manager' ),
			CouponModel::STATUS_INACTIVE => __( 'Inactive', 'wp-events-manager' ),
		);

		$status = $item->get_status();

		return sprintf(
			'<span class="wpems-status-badge wpems-coupon-%s">%s</span>',
			esc_attr( $status ),
			esc_html( $labels[ $status ] ?? $status )
		);
	}

	/**
	 * Render dates column.
	 *
	 * @param CouponModel $item Coupon.
	 *
	 * @return string
	 */
	public function column_dates( $item ): string {
		$from = $item->get_starts_at_gmt()
			? get_date_from_gmt( $item->get_starts_at_gmt(), 'Y-m-d' )
			: '—';

		$to = $item->get_expires_at_gmt()
			? get_date_from_gmt( $item->get_expires_at_gmt(), 'Y-m-d' )
			: '—';

		return esc_html( $from . ' → ' . $to );
	}

	/**
	 * Message when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No coupons found.', 'wp-events-manager' );
	}
}
