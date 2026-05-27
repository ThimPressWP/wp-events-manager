<?php
/**
 * Base contract for WPEMS payment gateways.
 *
 * @package WPEMS\Payments
 * @since   3.0.0
 */

namespace WPEMS\Payments;

defined( 'ABSPATH' ) || exit;

use BadMethodCallException;
use WPEMS\Admin\SettingsManager;
use WPEMS\Models\BookingTableModel;

/**
 * Common payment gateway contract.
 */
abstract class AbstractPaymentGateway {

	/**
	 * Gateway identifier.
	 *
	 * Public for compatibility with existing checkout settings rendering.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Gateway title.
	 *
	 * Public for compatibility with existing checkout settings rendering.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Gateway feature flags. Subclasses may override.
	 *
	 * Recognized features: redirect_checkout, offline_checkout, webhook,
	 * payment_sync, refund.
	 *
	 * @var string[]
	 */
	protected const FEATURES = array();

	/**
	 * Get gateway identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return (string) $this->id;
	}

	/**
	 * Get gateway title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return (string) $this->title;
	}

	/**
	 * Get gateway description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return (string) $this->description;
	}

	/**
	 * Get gateway icon URL.
	 *
	 * @return string
	 */
	public function get_icon_url(): string {
		return '';
	}

	/**
	 * Whether this gateway is enabled in settings.
	 *
	 * @return bool
	 */
	abstract public function is_enabled(): bool;

	/**
	 * Whether this gateway can currently be used.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->is_enabled();
	}

	/**
	 * Gateway admin field schema.
	 *
	 * @return array
	 */
	abstract public function admin_fields(): array;

	/**
	 * Create checkout for a booking.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return CheckoutResult
	 */
	abstract public function create_checkout( BookingTableModel $booking ): CheckoutResult;

	/**
	 * Handle a browser return request.
	 *
	 * @param array $request Sanitized request data.
	 *
	 * @return PaymentResult
	 */
	abstract public function handle_return_request( array $request ): PaymentResult;

	/**
	 * Handle a gateway webhook request.
	 *
	 * @param array $request Sanitized request data.
	 *
	 * @return PaymentResult
	 */
	abstract public function handle_webhook_request( array $request ): PaymentResult;

	/**
	 * Sync payment status for a booking.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $source  Sync source.
	 *
	 * @return PaymentResult
	 */
	abstract public function sync_payment_status( BookingTableModel $booking, string $source = 'manual' ): PaymentResult;

	/**
	 * Refund a payment.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $amount  Refund amount.
	 * @param string            $reason  Refund reason.
	 *
	 * @return PaymentResult
	 *
	 * @throws BadMethodCallException When refunds are unsupported.
	 */
	public function refund_payment( BookingTableModel $booking, string $amount, string $reason ): PaymentResult {
		throw new BadMethodCallException( 'Refund not supported by this gateway in v1.' );
	}

	/**
	 * Check whether the gateway supports a feature.
	 *
	 * @param string $feature Feature key.
	 *
	 * @return bool
	 */
	public function supports( string $feature ): bool {
		return in_array( $feature, static::FEATURES, true );
	}

	/**
	 * Read a namespaced gateway setting.
	 *
	 * @param string $key     Setting suffix.
	 * @param mixed  $fallback Default value.
	 *
	 * @return mixed
	 */
	protected function get_setting( string $key, $fallback = '' ) {
		return SettingsManager::get_option( $this->setting_id( $key ), $fallback );
	}

	/**
	 * Build a namespaced setting ID.
	 *
	 * @param string $suffix Setting suffix.
	 *
	 * @return string
	 */
	protected function setting_id( string $suffix ): string {
		return $this->get_id() . '_' . $suffix;
	}
}
