<?php
/**
 * Check offline payment gateway.
 *
 * @package WPEMS\Gateways
 * @since   3.0.0
 */

namespace WPEMS\Gateways;

defined( 'ABSPATH' ) || exit;

use BadMethodCallException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;

/**
 * Check payment gateway.
 */
class CheckGateway extends AbstractPaymentGateway {

	/**
	 * Gateway feature flags.
	 *
	 * @var string[]
	 */
	protected const FEATURES = array( 'offline_checkout' );

	/**
	 * Gateway identifier.
	 *
	 * @var string
	 */
	public $id = 'check';

	/**
	 * Get gateway title (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Check Payment', 'wp-events-manager' );
	}

	/**
	 * Get gateway description (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Pay by mailed check.', 'wp-events-manager' );
	}

	/**
	 * Whether check payment is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->get_setting( 'enable', 'no' );
	}

	/**
	 * Gateway admin fields.
	 *
	 * @return array
	 */
	public function admin_fields(): array {
		return array(
			array(
				'type'  => 'section_start',
				'id'    => 'check_settings',
				'title' => __( 'Check Payment', 'wp-events-manager' ),
			),
			array(
				'type'    => 'yes_no',
				'id'      => $this->setting_id( 'enable' ),
				'title'   => __( 'Enable check payment', 'wp-events-manager' ),
				'default' => 'no',
			),
			array(
				'type'    => 'textarea',
				'id'      => $this->setting_id( 'instructions' ),
				'title'   => __( 'Customer instructions', 'wp-events-manager' ),
				'desc'    => __( 'Shown on the order-received page and emailed to the customer.', 'wp-events-manager' ),
				'default' => '',
			),
			array(
				'type' => 'section_end',
				'id'   => 'check_settings',
			),
		);
	}

	/**
	 * Create an offline checkout result.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return CheckoutResult
	 */
	public function create_checkout( BookingTableModel $booking ): CheckoutResult {
		return CheckoutResult::offline( 'offline' );
	}

	/**
	 * Check payment has no return request flow.
	 *
	 * @param array $request Sanitized request data.
	 *
	 * @return PaymentResult
	 */
	public function handle_return_request( array $request ): PaymentResult {
		return PaymentResult::unknown( 0, 'check_has_no_return' );
	}

	/**
	 * Check payment has no webhook request flow.
	 *
	 * @param array $request Sanitized request data.
	 *
	 * @return PaymentResult
	 *
	 * @throws BadMethodCallException Always, because check payment has no webhooks.
	 */
	public function handle_webhook_request( array $request ): PaymentResult {
		throw new BadMethodCallException( 'check_no_webhook' );
	}

	/**
	 * Check payment status is never auto-synced.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $source  Sync source.
	 *
	 * @return PaymentResult
	 */
	public function sync_payment_status( BookingTableModel $booking, string $source = 'manual' ): PaymentResult {
		return PaymentResult::unknown( $booking->get_id(), 'check_no_sync' );
	}

	/**
	 * Get sanitized customer instructions.
	 *
	 * @return string
	 */
	public function get_instructions_html(): string {
		return wp_kses_post( (string) $this->get_setting( 'instructions', '' ) );
	}
}
