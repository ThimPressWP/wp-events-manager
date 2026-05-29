<?php
/**
 * The Template for displaying booking form in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/booking-form.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.3.0
 */
use WPEMS\Models\EventPostModel;
use WPEMS\Payments\PaymentGatewayRegistry;

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$event = EventPostModel::find( absint( $event_id ) );
if ( ! $event ) {
	return;
}

$user_reg     = $event->booked_quantity( get_current_user_id() );
$is_free      = $event->is_free();
$max_slots    = $event->get_slot_available();
$single_qty   = $is_free && 0 === $user_reg && wpems_get_option( 'email_register_times' ) === 'once';
$gateways     = $is_free
	? array()
	: ( class_exists( PaymentGatewayRegistry::class )
		? PaymentGatewayRegistry::instance()->available()
		: array() );
$currency     = function_exists( 'wpems_get_currency' ) ? wpems_get_currency() : '';
$has_payment  = $is_free || ! empty( $gateways );
?>

<div class="event_register_area">

	<h2><?php echo esc_html( $event->get_title() ); ?></h2>

	<form
		name="event_register"
		class="wpems-checkout-form"
		method="POST"
		data-wpems-checkout-form
		data-event-id="<?php echo esc_attr( $event_id ); ?>"
	>

		<?php if ( $single_qty ) : ?>
			<input type="hidden" name="qty" value="1" />
		<?php else : ?>
			<div class="event_auth_form_field">
				<label for="event_register_qty"><?php esc_html_e( 'Quantity', 'wp-events-manager' ); ?></label>
				<input
					type="number"
					name="qty"
					id="event_register_qty"
					value="1"
					min="1"
					max="<?php echo esc_attr( $max_slots ); ?>"
				/>
			</div>
		<?php endif; ?>

		<?php if ( ! $is_free ) : ?>
			<div class="event_auth_form_field event_auth_coupon_field">
				<label for="event_register_coupon"><?php esc_html_e( 'Coupon code', 'wp-events-manager' ); ?></label>
				<input
					type="text"
					name="coupon_code"
					id="event_register_coupon"
					value=""
					autocomplete="off"
				/>
				<span class="event_auth_coupon_message" data-coupon-message></span>
			</div>
		<?php endif; ?>

		<?php if ( ! $is_free ) : ?>
			<?php if ( ! empty( $gateways ) ) : ?>
				<ul class="event_auth_payment_methods">
					<?php $i = 0; ?>
					<?php foreach ( $gateways as $gateway ) : ?>
						<?php $gid = $gateway->get_id(); ?>
						<li>
							<input
								id="payment_method_<?php echo esc_attr( $gid ); ?>"
								type="radio"
								name="payment_method"
								value="<?php echo esc_attr( $gid ); ?>"
								<?php echo 0 === $i ? 'checked' : ''; ?>
							/>
							<label for="payment_method_<?php echo esc_attr( $gid ); ?>">
								<?php echo esc_html( $gateway->get_title() ); ?>
							</label>
						</li>
						<?php ++$i; ?>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<?php wpems_print_notice( 'error', esc_html__( 'There are no payment gateway available. Please contact administrator to setup it.', 'wp-events-manager' ) ); ?>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! $is_free ) : ?>
			<div class="event_register_summary" data-wpems-summary>
				<div class="event_register_summary_row">
					<span class="label"><?php esc_html_e( 'Subtotal', 'wp-events-manager' ); ?></span>
					<span class="value" data-summary-subtotal>0</span>
				</div>
				<div class="event_register_summary_row">
					<span class="label"><?php esc_html_e( 'Discount', 'wp-events-manager' ); ?></span>
					<span class="value" data-summary-discount>0</span>
				</div>
				<div class="event_register_summary_row">
					<span class="label" data-summary-tax-label><?php esc_html_e( 'Tax', 'wp-events-manager' ); ?></span>
					<span class="value" data-summary-tax>0</span>
				</div>
				<div class="event_register_summary_row event_register_summary_total">
					<span class="label"><?php esc_html_e( 'Total', 'wp-events-manager' ); ?></span>
					<span class="value" data-summary-total>0 <?php echo esc_html( $currency ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<div class="event_register_foot">
			<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
			<div class="event_register_error" data-form-error></div>
			<button
				type="submit"
				class="event_register_submit event_auth_button"
				<?php echo $has_payment ? '' : 'disabled="disabled"'; ?>
			>
				<?php esc_html_e( 'Register Now', 'wp-events-manager' ); ?>
			</button>
		</div>

	</form>

</div>
