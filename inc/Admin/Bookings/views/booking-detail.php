<?php
/**
 * Booking detail view.
 *
 * @package WPEMS\Admin\Bookings\Views
 *
 * @var \WPEMS\Models\BookingTableModel          $booking
 * @var \WPEMS\Models\PaymentTransactionModel[]   $txns
 * @var \WPEMS\Models\BookingRefundSummary        $refund
 * @var \WPEMS\Payments\AbstractPaymentGateway|null $gateway
 * @var bool                                      $can_sync
 * @var array                                     $meta
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %d: booking ID */
			esc_html__( 'Booking #%d', 'wp-events-manager' ),
			esc_html( $booking->get_id() )
		);
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPEMS\Admin\Bookings\AdminBookingManager::PAGE_SLUG ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Back to list', 'wp-events-manager' ); ?>
		</a>
	</h1>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<?php
				// --- 1. Summary ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Summary', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<table class="wp-list-table widefat fixed striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Booking ID', 'wp-events-manager' ); ?></th>
									<td>#<?php echo esc_html( $booking->get_id() ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Event', 'wp-events-manager' ); ?></th>
									<td>
										<?php
										$event_id = $booking->get_event_id();
										if ( $event_id ) {
											$event_title = get_the_title( $event_id ) ?: sprintf( '#%d', $event_id );
											$event_url   = get_edit_post_link( $event_id );
											if ( $event_url ) {
												printf(
													'<a href="%s">%s</a>',
													esc_url( $event_url ),
													esc_html( $event_title )
												);
											} else {
												echo esc_html( $event_title );
											}
										} else {
											echo '—';
										}
										?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Customer', 'wp-events-manager' ); ?></th>
									<td>
										<?php
										$user_id = $booking->get_user_id();
										if ( $user_id ) {
											$user = get_userdata( $user_id );
											if ( $user ) {
												$profile_url = get_edit_user_link( $user_id );
												printf(
													'<a href="%s">%s</a>',
													esc_url( $profile_url ),
													esc_html( $user->display_name )
												);
											} else {
												printf( '#%d', esc_html( $user_id ) );
											}
										} else {
											echo '—';
										}
										?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Quantity', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_qty() ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Created', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_created_at_gmt() ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<?php
				// --- 2. Pricing ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Pricing', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<table class="wp-list-table widefat fixed striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Subtotal', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_subtotal() ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Discount', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_discount_total() ); ?></td>
								</tr>
								<?php if ( (float) $booking->get_tax_rate() > 0 ) : ?>
								<tr>
									<th><?php esc_html_e( 'Tax', 'wp-events-manager' ); ?> (<?php echo esc_html( $booking->get_tax_rate() ); ?>%)</th>
									<td><?php echo esc_html( $booking->get_tax_total() ); ?></td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><strong><?php esc_html_e( 'Total', 'wp-events-manager' ); ?></strong></th>
									<td><strong><?php echo esc_html( $booking->get_total() ); ?></strong></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Currency', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_currency() ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<?php
				// --- 3. Payment ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Payment', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<table class="wp-list-table widefat fixed striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Method', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_payment_method() ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Mode', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_payment_mode() ?: '—' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Gateway Order ID', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $booking->get_gateway_order_id() ?: '—' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Status', 'wp-events-manager' ); ?></th>
									<td>
										<mark class="booking-status status-<?php echo esc_attr( $booking->get_status() ); ?>">
											<?php echo esc_html( $booking->get_status() ); ?>
										</mark>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Payment Status', 'wp-events-manager' ); ?></th>
									<td>
										<mark class="payment-status payment-<?php echo esc_attr( $booking->get_payment_status() ); ?>">
											<?php echo esc_html( $booking->get_payment_status() ); ?>
										</mark>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<?php
				// --- 4. Transactions ledger ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Transactions', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<?php if ( empty( $txns ) ) : ?>
							<p><?php esc_html_e( 'No transactions recorded.', 'wp-events-manager' ); ?></p>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'ID', 'wp-events-manager' ); ?></th>
										<th><?php esc_html_e( 'Type', 'wp-events-manager' ); ?></th>
										<th><?php esc_html_e( 'Amount', 'wp-events-manager' ); ?></th>
										<th><?php esc_html_e( 'Status', 'wp-events-manager' ); ?></th>
										<th><?php esc_html_e( 'Date', 'wp-events-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $txns as $txn ) : ?>
										<tr>
											<td><?php echo esc_html( $txn->get_id() ); ?></td>
											<td><?php echo esc_html( $txn->get_type() ); ?></td>
											<td><?php echo esc_html( $txn->get_amount() ); ?></td>
											<td><?php echo esc_html( $txn->get_status() ); ?></td>
											<td><?php echo esc_html( $txn->get_created_at_gmt() ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<?php
				// --- 5. Sync history ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Sync Status', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<?php if ( $can_sync ) : ?>
							<p>
								<?php esc_html_e( 'This booking supports payment status sync from the gateway.', 'wp-events-manager' ); ?>
							</p>
							<form method="post">
								<?php wp_nonce_field( 'wpems_booking_check_status' ); ?>
								<input type="hidden" name="wpems_booking_action" value="check_status" />
								<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->get_id() ); ?>" />
								<button type="submit" class="button">
									<?php esc_html_e( 'Check Payment Status', 'wp-events-manager' ); ?>
								</button>
							</form>
						<?php else : ?>
							<p><?php esc_html_e( 'Payment sync not supported for this payment method.', 'wp-events-manager' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<?php
				// --- 6. Refund status ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Refund Status', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<table class="wp-list-table widefat fixed striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Refunded Total', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $refund->get_refunded_total() ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Status', 'wp-events-manager' ); ?></th>
									<td><?php echo esc_html( $refund->get_refund_status() ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<?php
				// --- 7. Activity log ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Activity Log', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<?php if ( empty( $meta ) ) : ?>
							<p><?php esc_html_e( 'No activity recorded.', 'wp-events-manager' ); ?></p>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<tbody>
									<?php foreach ( $meta as $key => $value ) : ?>
										<tr>
											<th><?php echo esc_html( $key ); ?></th>
											<td><?php echo esc_html( (string) $value ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<?php
				// --- Actions sidebar ---
				?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Actions', 'wp-events-manager' ); ?></span></h2>
					<div class="inside">
						<form method="post" style="margin-bottom:8px;">
							<?php wp_nonce_field( 'wpems_booking_mark_paid' ); ?>
							<input type="hidden" name="wpems_booking_action" value="mark_paid" />
							<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->get_id() ); ?>" />
							<button type="submit" class="button button-primary" style="width:100%;">
								<?php esc_html_e( 'Mark as Paid', 'wp-events-manager' ); ?>
							</button>
						</form>

						<form method="post" style="margin-bottom:8px;">
							<?php wp_nonce_field( 'wpems_booking_mark_cancelled' ); ?>
							<input type="hidden" name="wpems_booking_action" value="mark_cancelled" />
							<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->get_id() ); ?>" />
							<button type="submit" class="button" style="width:100%;">
								<?php esc_html_e( 'Mark as Cancelled', 'wp-events-manager' ); ?>
							</button>
						</form>

						<form method="post" style="margin-bottom:8px;">
							<?php wp_nonce_field( 'wpems_booking_mark_failed' ); ?>
							<input type="hidden" name="wpems_booking_action" value="mark_failed" />
							<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->get_id() ); ?>" />
							<button type="submit" class="button" style="width:100%;">
								<?php esc_html_e( 'Mark as Failed', 'wp-events-manager' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
