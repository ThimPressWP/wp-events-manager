<?php

/**
 * WP Events Manager Event Settings meta box view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
$post_id    = $post->ID;
$prefix     = 'tp_event_';
$date_start = get_post_meta( $post->ID, $prefix . 'date_start', true ) ? date( 'Y-m-d', strtotime( get_post_meta( $post->ID, $prefix . 'date_start', true ) ) ) : '';
$time_start = get_post_meta( $post->ID, $prefix . 'time_start', true ) ? date( 'H:i', strtotime( get_post_meta( $post->ID, $prefix . 'time_start', true ) ) ) : '';

$date_end = get_post_meta( $post->ID, $prefix . 'date_end', true ) ? date( 'Y-m-d', strtotime( get_post_meta( $post->ID, $prefix . 'date_end', true ) ) ) : '';
$time_end = get_post_meta( $post->ID, $prefix . 'time_end', true ) ? date( 'H:i', strtotime( get_post_meta( $post->ID, $prefix . 'time_end', true ) ) ) : '';

$registration_end_date = get_post_meta( $post->ID, $prefix . 'registration_end_date', true ) ? date( 'Y-m-d', strtotime( get_post_meta( $post->ID, $prefix . 'registration_end_date', true ) ) ) : '';
$registration_end_time = get_post_meta( $post->ID, $prefix . 'registration_end_time', true ) ? date( 'H:i', strtotime( get_post_meta( $post->ID, $prefix . 'registration_end_time', true ) ) ) : '';

$qty      = get_post_meta( $post_id, $prefix . 'qty', true );
$price    = get_post_meta( $post_id, $prefix . 'price', true );
$location = get_post_meta( $post_id, $prefix . 'location', true );
$today    = date( 'Y-m-d', strtotime( 'today' ) );
$tomorrow = date( 'Y-m-d', strtotime( 'tomorrow' ) );

$schedules_event = get_post_meta( $post_id, 'tp_event_schedules', true );
$schedules       = json_decode( $schedules_event, true );

$location_iframe = get_post_meta( $post_id, 'tp_event_iframe', true );


?>
<div class="event_meta_box_container">
	<div class="event_meta_panel">
		<?php do_action( 'tp_event_admin_event_metabox_before_fields', $post, $prefix ); ?>
		<div class="option_group">
			<p class="form-field">
				<label for="_qty"><?php _e( 'Quantity', 'wp-events-manager' ); ?></label>
				<input type="number" min="0" step="1" class="short" name="<?php echo esc_attr( $prefix ); ?>qty" id="_qty" value="<?php echo esc_attr( absint( $qty ) ); ?>">
			</p>
		</div>
		<div class="option_group">
			<p class="form-field">
				<label for="_price"><?php printf( '%s(%s)', __( 'Price', 'wp-events-manager' ), wpems_get_currency_symbol() ); ?></label>
				<input type="number" step="any" min="0" class="short" name="<?php echo esc_attr( $prefix ); ?>price" id="_price" value="<?php echo esc_attr( floatval( $price ) ); ?>" />
			</p>
			<p class="event-meta-notice">
				<?php echo esc_html__( 'Set 0 to make it becomes free event', 'wp-events-manager' ); ?>
			</p>
		</div>

		<div class="option_group">
			<div class="form-field" id="event-time-metabox">
				<label><?php echo esc_html__( 'Start/End', 'wp-events-manager' ); ?></label>
				<label hidden for="_date_start"></label>
				<input type="text" class="short date-start" name="<?php echo esc_attr( $prefix ); ?>date_start" id="_date_start" value="<?php echo $date_start ? esc_attr( $date_start ) : esc_attr( $today ); ?>">
				<label hidden for="_time_start"></label>
				<input type="text" class="short time-start" name="<?php echo esc_attr( $prefix ); ?>time_start" id="_time_start" value="<?php echo $time_start ? esc_attr( $time_start ) : ''; ?>">
				<span class="time-connect"> <?php echo esc_html__( 'to', 'wp-events-manager' ); ?></span>
				<label hidden for="_date_end"></label>
				<input type="text" class="short date-end" name="<?php echo esc_attr( $prefix ); ?>date_end" id="_date_end" value="<?php echo $date_end ? esc_attr( $date_end ) : esc_attr( $tomorrow ); ?>">
				<label hidden for="_time_end"></label>
				<input type="text" class="short time-end" name="<?php echo esc_attr( $prefix ); ?>time_end" id="_time_end" value="<?php echo $time_end ? esc_attr( $time_end ) : ''; ?>">
			</div>
		</div>

		<!-- Registration End Date -->
		<div class="option_group">
			<div class="form-field" id="event-registration-time-metabox">
				<label><?php echo esc_html__( 'Registration End Date', 'wp-events-manager' ); ?></label>
				<label hidden for="_registration_end_date"></label>
				<input type="text" class="short date-start" name="<?php echo esc_attr( $prefix ); ?>registration_end_date" id="_registration_end_date" value="<?php echo $registration_end_date ? esc_attr( $registration_end_date ) : esc_attr( $today ); ?>">
				<label hidden for="_registration_end_time"></label>
				<input type="text" class="short time-start" name="<?php echo esc_attr( $prefix ); ?>registration_end_time" id="_registration_end_time" value="<?php echo $registration_end_time ? esc_attr( $registration_end_time ) : ''; ?>">
			</div>
		</div>
		<!-- End Registration End Date -->

		<!-- Schedule -->
		<div class="option_group">
			<p class="form-field">
				<label for="_schedule"><?php _e( 'Schedule', 'wp-events-manager' ); ?></label>
				<input type="checkbox" class="short" name="<?php echo esc_attr( $prefix ); ?>schedule_checkbox" id="_schedule_checkbox">
				<span>Enable/Disable Schedule section on the frontend</span>
				<div class="form_info-container">
					<button id="add_form_info-btn">+ Add more</button>
					<?php
					if ( ! empty( $schedules ) ) :
						foreach ( $schedules as $schedule_id => $schedule_value ) :
							?>
							<div class="form_info" id="<?php echo $schedule_id; ?>">
								<div class="form_info-header">
									<div class="form_info-header-left">
										<div class="dashicons-before dashicons-move"></div>
									</div>
									<div class="form_info-header-right">
										<button class="dashicons-before dashicons-no"></button>
										<button class="dashicons-before dashicons-minus"></button>
									</div>
								</div>
								<div class="form_info-content">
									<div class="field-content-title">
										<label>Title:</label>
										<input type="text" value="<?php echo $schedule_value['title'] ? wp_kses_post( $schedule_value['title'] ) : ''; ?>">
									</div>
									<div class="field-content-desc">
										<label>Description:</label>
										<div class="custom-editor" data-form-id="<?php echo $schedule_id; ?>"><?php echo $schedule_value['description'] ? wp_kses_post( $schedule_value['description'] ) : ''; ?></div>
									</div>
								</div>
							</div>
							<?php
						endforeach;
					endif;
					?>
				</div>
				<input type="hidden" name="tp_event_schedules" id="tp_event_schedules" value="<?php echo isset( $schedules ) ? esc_html( json_encode( $schedules ) ) : ''; ?>">
			</p>
		</div>
		<!-- End Schedule -->

		<!-- Location -->
		<div class="option_group">
			<p class="form-field">
				<label for="_location"><?php _e( 'Location', 'wp-events-manager' ); ?></label>
				<input type="text" class="short" name="<?php echo esc_attr( $prefix ); ?>location" id="_location" value="<?php echo esc_attr( $location ); ?>">
			</p>
			<?php if ( ! wpems_get_option( 'google_map_api_key' ) ) : ?>
				<p class="event-meta-notice">
					<?php echo esc_html__( 'You need set up Google Map API Key to show map.', 'wp-events-manager' ); ?>
					<a href="<?php echo esc_url( get_admin_url() . '/admin.php?page=tp-event-setting&tab=event_general' ); ?>"><?php echo esc_html__( 'Set up here' ); ?></a>
				</p>
			<?php endif; ?>
			<p class="form-field">
				<label for="_location"></label>
				<textarea class="short ml-150" name="<?php echo esc_attr( $prefix ); ?>iframe" id="_iframe" cols="30" rows="2"><?php echo isset( $location_iframe ) ? esc_attr( $location_iframe ) : ''; ?></textarea>
				<?php if ( ! wpems_get_option( 'google_map_api_key' ) ) : ?>
					<p class="event-meta-notice">
						<?php echo esc_html__( 'Use iframe to show map.', 'wp-events-manager' ); ?>
					</p>
				<?php endif; ?>
				<div class="show_map_iframe"></div>
				<div class="error_message" style="color: red;"></div>
			</p>
		</div>
		<!-- End Location -->

		<div class="option_group">
			<p class="form-field">
				<label for="_shortcode"><?php _e( 'Shortcode', 'wp-events-manager' ); ?></label>
				<input type="text" class="short" id="_shortcode" value="<?php echo esc_attr( '[wp_event_countdown event_id="' . $post->ID . '"]' ); ?>" readonly>
			</p>
		</div>
		<?php wp_nonce_field( 'event_nonce', 'event-nonce' ); ?>
		<?php do_action( 'tp_event_admin_event_metabox_after_fields', $post, $prefix ); ?>
	</div>
</div>
