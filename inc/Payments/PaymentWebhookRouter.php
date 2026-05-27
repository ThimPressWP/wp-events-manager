<?php
/**
 * Front controller for payment gateway webhooks.
 *
 * @package WPEMS\Payments
 * @since   3.0.0
 */

namespace WPEMS\Payments;

defined( 'ABSPATH' ) || exit;

use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Throwable;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\PaymentSyncService;

/**
 * Registers and dispatches payment webhook rewrite endpoints.
 */
final class PaymentWebhookRouter {

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static bool $bootstrapped = false;

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		if ( self::$bootstrapped ) {
			return;
		}

		self::$bootstrapped = true;

		add_action( 'init', array( self::class, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'add_query_vars' ) );
		add_action( 'parse_request', array( self::class, 'dispatch' ) );
	}

	/**
	 * Register webhook rewrite rules.
	 *
	 * @return void
	 */
	public static function register_rewrite_rules(): void {
		add_rewrite_rule( '^wpems-webhook/paypal-rest/?$', 'index.php?wpems_paypal_rest=1', 'top' );
		add_rewrite_rule( '^wpems-webhook/paypal-ipn/?$', 'index.php?wpems_paypal_ipn=1', 'top' );
		add_rewrite_rule( '^wpems-webhook/stripe/?$', 'index.php?wpems_stripe_webhook=1', 'top' );
	}

	/**
	 * Add webhook query vars.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public static function add_query_vars( array $vars ): array {
		$vars[] = 'wpems_paypal_rest';
		$vars[] = 'wpems_paypal_ipn';
		$vars[] = 'wpems_stripe_webhook';

		return array_values( array_unique( $vars ) );
	}

	/**
	 * Register rewrite rules and flush them on activation.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules(): void {
		self::register_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Dispatch the current WordPress request.
	 *
	 * @param object $wp WP request object.
	 *
	 * @return void
	 */
	public static function dispatch( $wp ): void {
		$status = self::handle_wp_request( $wp );

		if ( null === $status ) {
			return;
		}

		status_header( $status );
		exit;
	}

	/**
	 * Handle a WordPress request without exiting.
	 *
	 * @param object $wp WP request object.
	 *
	 * @return int|null HTTP status if handled; null otherwise.
	 */
	public static function handle_wp_request( $wp ): ?int {
		$query_vars = is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars )
			? $wp->query_vars
			: array();

		if ( ! empty( $query_vars['wpems_paypal_ipn'] ) ) {
			return self::handle_paypal_ipn();
		}

		if ( ! empty( $query_vars['wpems_paypal_rest'] ) ) {
			return self::handle_gateway_webhook( 'paypal', self::raw_webhook_request() );
		}

		if ( ! empty( $query_vars['wpems_stripe_webhook'] ) ) {
			return self::handle_gateway_webhook( 'stripe', self::raw_webhook_request() );
		}

		return null;
	}

	/**
	 * Handle PayPal Standard IPN.
	 *
	 * @return int
	 */
	private static function handle_paypal_ipn(): int {
		$gateway = PaymentGatewayRegistry::instance()->get( 'paypal' );
		if ( null === $gateway || ! method_exists( $gateway, 'handle_standard_ipn' ) ) {
			return 404;
		}

		try {
			$result = $gateway->handle_standard_ipn( wp_unslash( $_POST ) );
			self::apply_result_if_possible( $result, 'webhook' );
		} catch ( Throwable $e ) {
			return 500;
		}

		return 200;
	}

	/**
	 * Handle a signed JSON webhook gateway.
	 *
	 * @param string $gateway_id Gateway ID.
	 * @param array  $request    Request payload.
	 *
	 * @return int
	 */
	private static function handle_gateway_webhook( string $gateway_id, array $request ): int {
		$gateway = PaymentGatewayRegistry::instance()->get( $gateway_id );
		if ( null === $gateway ) {
			return 404;
		}

		try {
			$result = $gateway->handle_webhook_request( $request );
			self::apply_result_if_possible( $result, 'webhook' );
		} catch ( SignatureVerificationException $e ) {
			return 400;
		} catch ( RuntimeException $e ) {
			return 'invalid_signature' === $e->getMessage() ? 400 : 500;
		} catch ( Throwable $e ) {
			return 500;
		}

		return 200;
	}

	/**
	 * Apply a gateway result through the payment sync service.
	 *
	 * @param PaymentResult $result Payment result.
	 * @param string        $source Sync source.
	 *
	 * @return void
	 */
	private static function apply_result_if_possible( PaymentResult $result, string $source ): void {
		if ( $result->get_booking_id() <= 0 || PaymentResult::STATUS_UNKNOWN === $result->get_status() ) {
			return;
		}

		$booking = ( new BookingRepository() )->find( $result->get_booking_id() );
		if ( null === $booking ) {
			return;
		}

		PaymentSyncService::instance()->apply_result( $booking, $result, $source );
	}

	/**
	 * Build request data for raw webhook gateways.
	 *
	 * @return array
	 */
	private static function raw_webhook_request(): array {
		return array(
			'headers'     => self::headers(),
			'raw_payload' => (string) file_get_contents( 'php://input' ),
		);
	}

	/**
	 * Read request headers.
	 *
	 * @return array
	 */
	private static function headers(): array {
		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();

			return is_array( $headers ) ? $headers : array();
		}

		$headers = array();
		foreach ( $_SERVER as $key => $value ) {
			if ( 0 !== strpos( $key, 'HTTP_' ) ) {
				continue;
			}

			$name             = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
			$headers[ $name ] = $value;
		}

		return $headers;
	}
}
