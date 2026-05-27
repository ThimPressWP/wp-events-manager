<?php
/**
 * Registry for WPEMS payment gateway instances.
 *
 * @package WPEMS\Payments
 * @since   3.0.0
 */

namespace WPEMS\Payments;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use ReflectionClass;
use WPEMS\Gateways\CheckGateway;
use WPEMS\Gateways\ManualGateway;
use WPEMS\Gateways\PaypalGateway;
use WPEMS\Gateways\StripeGateway;

/**
 * Singleton payment gateway registry.
 */
final class PaymentGatewayRegistry {

	/**
	 * Default gateway classes registered during bootstrap when available.
	 *
	 * Some classes are introduced in later booking-system phases, so bootstrap
	 * checks each class before instantiating it.
	 *
	 * @var string[]
	 */
	private const DEFAULT_GATEWAY_CLASSES = array(
		ManualGateway::class,
		CheckGateway::class,
		PaypalGateway::class,
		StripeGateway::class,
	);

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether WordPress hooks have been registered.
	 *
	 * @var bool
	 */
	private static bool $bootstrapped = false;

	/**
	 * Gateway instances keyed by gateway ID.
	 *
	 * @var array<string, AbstractPaymentGateway>
	 */
	private array $gateways = array();

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a gateway instance.
	 *
	 * @param AbstractPaymentGateway $gateway Gateway instance.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If gateway ID is empty.
	 */
	public function register( AbstractPaymentGateway $gateway ): void {
		$id = $gateway->get_id();
		if ( '' === $id ) {
			throw new InvalidArgumentException( 'Payment gateway ID must not be empty.' );
		}

		$this->gateways[ $id ] = $gateway;
	}

	/**
	 * Get all registered gateways.
	 *
	 * @return AbstractPaymentGateway[]
	 */
	public function all(): array {
		return array_values( $this->gateways );
	}

	/**
	 * Get enabled gateways.
	 *
	 * @return AbstractPaymentGateway[]
	 */
	public function enabled(): array {
		return array_values(
			array_filter(
				$this->gateways,
				static function ( AbstractPaymentGateway $gateway ): bool {
					return $gateway->is_enabled();
				}
			)
		);
	}

	/**
	 * Get currently available gateways.
	 *
	 * @return AbstractPaymentGateway[]
	 */
	public function available(): array {
		return array_values(
			array_filter(
				$this->gateways,
				static function ( AbstractPaymentGateway $gateway ): bool {
					return $gateway->is_available();
				}
			)
		);
	}

	/**
	 * Get a gateway by ID.
	 *
	 * @param string $id Gateway ID.
	 *
	 * @return AbstractPaymentGateway|null
	 */
	public function get( string $id ): ?AbstractPaymentGateway {
		return $this->gateways[ $id ] ?? null;
	}

	/**
	 * Whether a gateway is registered.
	 *
	 * @param string $id Gateway ID.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->gateways[ $id ] );
	}

	/**
	 * Register WordPress hooks for the gateway registry.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		if ( self::$bootstrapped ) {
			return;
		}

		self::$bootstrapped = true;

		add_action(
			'plugins_loaded',
			static function (): void {
				$registry = self::instance();
				$registry->register_default_gateways();

				do_action( 'wpems_payment_gateway_registry_ready', $registry );
			},
			20
		);

		add_filter(
			'wpems_payment_gateways',
			static function ( $gateways ) {
				$gateways = is_array( $gateways ) ? $gateways : array();

				foreach ( self::instance()->all() as $gateway ) {
					$gateways[ $gateway->get_id() ] = $gateway;
				}

				return $gateways;
			},
			20
		);
	}

	/**
	 * Register default gateway classes that exist and match the new contract.
	 *
	 * @return void
	 */
	private function register_default_gateways(): void {
		$classes = apply_filters( 'wpems_payment_gateway_registry_classes', self::DEFAULT_GATEWAY_CLASSES );

		foreach ( (array) $classes as $class_name ) {
			if ( ! is_string( $class_name ) || '' === $class_name ) {
				continue;
			}

			if ( ! class_exists( $class_name ) || ! is_subclass_of( $class_name, AbstractPaymentGateway::class ) ) {
				continue;
			}

			$reflection  = new ReflectionClass( $class_name );
			$constructor = $reflection->getConstructor();

			if ( ! $reflection->isInstantiable() || ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) ) {
				continue;
			}

			$gateway = $reflection->newInstance();
			if ( $gateway instanceof AbstractPaymentGateway ) {
				$this->register( $gateway );
			}
		}
	}
}
