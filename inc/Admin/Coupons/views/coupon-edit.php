<?php
/**
 * Coupon edit form view.
 *
 * @package WPEMS\Admin\Coupons\Views
 *
 * @var \WPEMS\Models\CouponModel|null $coupon
 * @var int[]                          $event_ids
 */

defined( 'ABSPATH' ) || exit;

$is_new     = null === $coupon;
$save_url   = admin_url( 'admin-post.php?action=wpems_coupon_save' );
$list_url   = admin_url( 'admin.php?page=' . \WPEMS\Admin\Coupons\AdminCouponManager::PAGE_SLUG );
?>
<div class="wrap">
	<h1>
		<?php $is_new ? esc_html_e( 'Add New Coupon', 'wp-events-manager' ) : esc_html_e( 'Edit Coupon', 'wp-events-manager' ); ?>
		<a href="<?php echo esc_url( $list_url ); ?>" class="page-title-action">
			<?php esc_html_e( 'Back to list', 'wp-events-manager' ); ?>
		</a>
	</h1>

	<form method="post" action="<?php echo esc_url( $save_url ); ?>">
		<?php wp_nonce_field( 'wpems_coupon_save' ); ?>
		<input type="hidden" name="coupon_id" value="<?php echo $is_new ? '0' : esc_attr( (string) $coupon->get_id() ); ?>" />

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="wpems_coupon_code"><?php esc_html_e( 'Code', 'wp-events-manager' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input
						type="text"
						id="wpems_coupon_code"
						name="code"
						value="<?php echo $is_new ? '' : esc_attr( $coupon->get_code() ); ?>"
						class="regular-text"
						required
						style="text-transform:uppercase;"
					/>
					<p class="description"><?php esc_html_e( 'Unique coupon code (auto-uppercased).', 'wp-events-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_description"><?php esc_html_e( 'Description', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<textarea
						id="wpems_coupon_description"
						name="description"
						rows="3"
						class="large-text"
					><?php echo $is_new ? '' : esc_textarea( $coupon->get_description() ?: '' ); ?></textarea>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'wp-events-manager' ); ?></th>
				<td>
					<label>
						<input
							type="checkbox"
							name="status"
							value="yes"
							<?php checked( ! $is_new && $coupon->is_active() ); ?>
						/>
						<?php esc_html_e( 'Active', 'wp-events-manager' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Discount Type', 'wp-events-manager' ); ?></th>
				<td>
					<?php
					$current_type = $is_new ? 'percent' : $coupon->get_discount_type();
					$types = array(
						'percent' => __( 'Percentage', 'wp-events-manager' ),
						'amount'  => __( 'Fixed Amount', 'wp-events-manager' ),
						'hybrid'  => __( 'Hybrid (Percentage + Cap)', 'wp-events-manager' ),
					);
					foreach ( $types as $value => $label ) :
						?>
						<label style="margin-right:16px;">
							<input
								type="radio"
								name="discount_type"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( $current_type, $value ); ?>
								class="wpems-discount-type-toggle"
							/>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>

			<tr class="wpems-field-percent wpems-field-hybrid">
				<th scope="row">
					<label for="wpems_coupon_percent_value"><?php esc_html_e( 'Percent Value', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_percent_value"
						name="percent_value"
						value="<?php echo $is_new ? '' : esc_attr( (string) $coupon->get_percent_value() ); ?>"
						step="0.0001"
						min="0"
						max="100"
						class="small-text"
					/>
					<span>%</span>
				</td>
			</tr>

			<tr class="wpems-field-amount">
				<th scope="row">
					<label for="wpems_coupon_amount_value"><?php esc_html_e( 'Amount Value', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_amount_value"
						name="amount_value"
						value="<?php echo $is_new ? '' : esc_attr( (string) $coupon->get_amount_value() ); ?>"
						step="0.0001"
						min="0"
						class="small-text"
					/>
				</td>
			</tr>

			<tr class="wpems-field-hybrid">
				<th scope="row">
					<label for="wpems_coupon_max_discount"><?php esc_html_e( 'Max Discount Amount', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_max_discount"
						name="max_discount_amount"
						value="<?php echo $is_new ? '' : esc_attr( (string) $coupon->get_max_discount_amount() ); ?>"
						step="0.0001"
						min="0"
						class="small-text"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Applies To', 'wp-events-manager' ); ?></th>
				<td>
					<?php
					$current_applies = $is_new ? 'all' : $coupon->get_applies_to();
					?>
					<label style="margin-right:16px;">
						<input
							type="radio"
							name="applies_to"
							value="all"
							<?php checked( $current_applies, 'all' ); ?>
							class="wpems-applies-to-toggle"
						/>
						<?php esc_html_e( 'All Events', 'wp-events-manager' ); ?>
					</label>
					<label>
						<input
							type="radio"
							name="applies_to"
							value="specific"
							<?php checked( $current_applies, 'specific' ); ?>
							class="wpems-applies-to-toggle"
						/>
						<?php esc_html_e( 'Specific Events', 'wp-events-manager' ); ?>
					</label>

					<div class="wpems-field-specific-events" style="margin-top:8px;<?php echo 'specific' !== $current_applies ? 'display:none;' : ''; ?>">
						<select
							name="event_ids[]"
							multiple
							style="width:400px;"
							class="wpems-event-select"
						>
							<?php foreach ( $event_ids as $eid ) : ?>
								<?php $title = get_the_title( $eid ) ?: sprintf( '#%d', $eid ); ?>
								<option value="<?php echo esc_attr( (string) $eid ); ?>" selected>
									<?php echo esc_html( $title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select events this coupon applies to.', 'wp-events-manager' ); ?></p>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_usage_limit"><?php esc_html_e( 'Usage Limit', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_usage_limit"
						name="usage_limit"
						value="<?php echo $is_new ? '' : esc_attr( (string) ( $coupon->get_usage_limit() ?? '' ) ); ?>"
						min="0"
						class="small-text"
						placeholder="<?php esc_attr_e( 'Unlimited', 'wp-events-manager' ); ?>"
					/>
					<p class="description"><?php esc_html_e( 'Leave blank for unlimited global usage.', 'wp-events-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_usage_limit_per_user"><?php esc_html_e( 'Per-User Limit', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_usage_limit_per_user"
						name="usage_limit_per_user"
						value="<?php echo $is_new ? '' : esc_attr( (string) ( $coupon->get_usage_limit_per_user() ?? '' ) ); ?>"
						min="0"
						class="small-text"
						placeholder="<?php esc_attr_e( 'Unlimited', 'wp-events-manager' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_min_order"><?php esc_html_e( 'Min Order Amount', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="wpems_coupon_min_order"
						name="min_order_amount"
						value="<?php echo $is_new ? '' : esc_attr( (string) ( $coupon->get_min_order_amount() ?? '' ) ); ?>"
						step="0.0001"
						min="0"
						class="small-text"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_starts_at"><?php esc_html_e( 'Start Date', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="datetime-local"
						id="wpems_coupon_starts_at"
						name="starts_at_gmt"
						value="<?php echo $is_new ? '' : esc_attr( str_replace( ' ', 'T', substr( (string) $coupon->get_starts_at_gmt(), 0, 16 ) ) ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wpems_coupon_expires_at"><?php esc_html_e( 'Expiry Date', 'wp-events-manager' ); ?></label>
				</th>
				<td>
					<input
						type="datetime-local"
						id="wpems_coupon_expires_at"
						name="expires_at_gmt"
						value="<?php echo $is_new ? '' : esc_attr( str_replace( ' ', 'T', substr( (string) $coupon->get_expires_at_gmt(), 0, 16 ) ) ); ?>"
					/>
				</td>
			</tr>
		</table>

		<script>
		(function(){
			var typeRadios = document.querySelectorAll('.wpems-discount-type-toggle');
			var fields = {
				percent: document.querySelector('.wpems-field-percent'),
				amount:  document.querySelector('.wpems-field-amount'),
				hybrid:  document.querySelector('.wpems-field-hybrid')
			};
			function toggleFields() {
				var v = document.querySelector('.wpems-discount-type-toggle:checked').value;
				Object.keys(fields).forEach(function(k){
					if (fields[k]) fields[k].style.display = (k === 'percent' && v === 'hybrid') || k === v ? '' : 'none';
				});
			}
			typeRadios.forEach(function(r){ r.addEventListener('change', toggleFields); });
			toggleFields();

			var appliesRadios = document.querySelectorAll('.wpems-applies-to-toggle');
			var specificDiv = document.querySelector('.wpems-field-specific-events');
			appliesRadios.forEach(function(r){
				r.addEventListener('change', function(){
					specificDiv.style.display = this.value === 'specific' ? '' : 'none';
				});
			});
		})();
		</script>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php $is_new ? esc_html_e( 'Create Coupon', 'wp-events-manager' ) : esc_html_e( 'Save Changes', 'wp-events-manager' ); ?>
			</button>
		</p>
	</form>
</div>
