<?php
/**
 * Checkout settings page.
 *
 * @package WPEMS\Admin\Settings
 */

namespace WPEMS\Admin\Settings;

use WPEMS\Admin\SettingsManager;
use WPEMS\Payments\PaymentGatewayRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout settings.
 */
class Checkout extends AbstractSetting {

	/**
	 * Setting page ID.
	 *
	 * @var string|null
	 */
	public $id = null;

	/**
	 * Setting page label.
	 *
	 * @var string|null
	 */
	public $label = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'event_checkout';
		$this->label = __( 'Checkout', 'wp-events-manager' );

		parent::__construct();
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';

		return apply_filters(
			'event_admin_setting_page_' . $this->id,
			array(
				array(
					'type'  => 'section_start',
					'id'    => 'general_settings',
					'title' => __( 'Checkout Process', 'wp-events-manager' ),
					'desc'  => __( 'General options for system', 'wp-events-manager' ),
				),
				array(
					'type'    => 'select',
					'title'   => __( 'Booking times free event/email', 'wp-events-manager' ),
					'desc'    => __( 'This controls how many time booking free event of an email', 'wp-events-manager' ),
					'id'      => $prefix . 'email_register_times',
					'options' => array(
						'once' => __( 'Once', 'wp-events-manager' ),
						'many' => __( 'Many', 'wp-events-manager' ),
					),
					'default' => 'many',
				),
				array(
					'type'        => 'number',
					'title'       => __( 'Cancel payment status', 'wp-events-manager' ),
					'desc'        => __( 'How long cancel a payment (hour)', 'wp-events-manager' ),
					'atts'        => array(
						'min'  => 0,
						'step' => 'any',
					),
					'id'          => $prefix . 'cancel_payment',
					'default'     => 12,
					'placeholder' => 12,
				),

				// --- Tax (new) ---
				array(
					'type'    => 'yes_no',
					'title'   => __( 'Enable tax', 'wp-events-manager' ),
					'id'      => $prefix . 'tax_enable',
					'default' => 'no',
				),
				array(
					'type'    => 'number',
					'title'   => __( 'Tax rate (%)', 'wp-events-manager' ),
					'desc'    => __( 'Percent applied to (subtotal − discount). 0 to disable.', 'wp-events-manager' ),
					'id'      => $prefix . 'tax_rate',
					'default' => '0',
					'atts'    => array(
						'min'  => 0,
						'max'  => 100,
						'step' => '0.0001',
					),
				),
				array(
					'type'    => 'text',
					'title'   => __( 'Tax label', 'wp-events-manager' ),
					'desc'    => __( 'Label shown on the order summary and receipts.', 'wp-events-manager' ),
					'id'      => $prefix . 'tax_label',
					'default' => __( 'Tax', 'wp-events-manager' ),
				),

				// --- Coupons (new) ---
				array(
					'type'    => 'yes_no',
					'title'   => __( 'Enable coupons', 'wp-events-manager' ),
					'desc'    => __( 'Show the coupon code field on checkout and apply discounts before tax.', 'wp-events-manager' ),
					'id'      => $prefix . 'coupon_enable',
					'default' => 'no',
				),

				array(
					'type' => 'section_end',
					'id'   => 'general_settings',
				),
			)
		);
	}

	/**
	 * Get sections.
	 *
	 * Iterates every registered gateway (regardless of `is_available()`) so the
	 * settings UI can always be reached to configure credentials — using
	 * `wpems_payment_gateways()` here would hide unconfigured gateways and make
	 * them impossible to set up.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections[''] = __( 'Checkout General', 'wp-events-manager' );

		foreach ( $this->all_gateways() as $gateway ) {
			$id    = method_exists( $gateway, 'get_id' ) ? $gateway->get_id() : ( $gateway->id ?? '' );
			$title = method_exists( $gateway, 'get_title' ) ? $gateway->get_title() : ( $gateway->title ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$sections[ $id ] = $title;
		}

		return $sections;
	}

	/**
	 * Output checkout or gateway settings.
	 *
	 * @param string $tab Current tab.
	 *
	 * @return void
	 */
	public function output( $tab ) {
		global $current_section;

		if ( ! $current_section ) {
			parent::output( $tab );
			return;
		}

		foreach ( $this->all_gateways() as $gateway ) {
			$id = method_exists( $gateway, 'get_id' ) ? $gateway->get_id() : ( $gateway->id ?? '' );
			if ( $current_section === $id ) {
				SettingsManager::output_fields( $gateway->admin_fields() );
				break;
			}
		}
	}

	/**
	 * Save checkout or gateway settings.
	 *
	 * @return void
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			parent::save();
			return;
		}

		foreach ( $this->all_gateways() as $gateway ) {
			$id = method_exists( $gateway, 'get_id' ) ? $gateway->get_id() : ( $gateway->id ?? '' );
			if ( $current_section === $id ) {
				SettingsManager::save_fields( $gateway->admin_fields() );
				break;
			}
		}
	}

	/**
	 * Return every registered gateway (configured or not).
	 *
	 * Prefers the new {@see PaymentGatewayRegistry} when available so unconfigured
	 * gateways still show up as settings tabs. Falls back to the legacy
	 * `wpems_payment_gateways` filter for back-compat with third-party add-ons.
	 *
	 * @return array
	 */
	private function all_gateways(): array {
		$gateways = array();

		if ( class_exists( PaymentGatewayRegistry::class ) ) {
			foreach ( PaymentGatewayRegistry::instance()->all() as $gateway ) {
				$id              = $gateway->get_id();
				$gateways[ $id ] = $gateway;
			}
		}

		// Merge any legacy or third-party-registered gateways exposed via the filter.
		$legacy = apply_filters( 'wpems_payment_gateways', array() );
		if ( is_array( $legacy ) ) {
			foreach ( $legacy as $id => $gateway ) {
				if ( ! isset( $gateways[ $id ] ) ) {
					$gateways[ $id ] = $gateway;
				}
			}
		}

		return $gateways;
	}
}
