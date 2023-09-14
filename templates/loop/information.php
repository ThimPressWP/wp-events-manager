<?php
/**
 * The Template for displaying information in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/information.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $post;

$start_time        = get_post_meta( $post->ID, 'tp_event_time_start', true );
$start_date        = get_post_meta( $post->ID, 'tp_event_date_start', true );
$end_time          = get_post_meta( $post->ID, 'tp_event_time_end', true );
$end_date          = get_post_meta( $post->ID, 'tp_event_date_end', true );
$register_end_time = get_post_meta( $post->ID, 'tp_event_registration_end_time', true );
$register_end_date = get_post_meta( $post->ID, 'tp_event_registration_end_date', true );
$location_f        = get_post_meta( $post->ID, 'tp_event_location', true );

?>
<div class="entry-information">
	<table>
		<tr>
			<td>
				<div class="title">
					<span class="dashicons dashicons-clock"></span>
					<h6>Start Time</h6>
				</div>
				<p class="content"><?php echo $start_time; ?> - <?php echo $start_date; ?></p>
			</td>
			<td>
				<div class="title">
					<span class="dashicons dashicons-flag"></span>
					<h6>End Time</h6>
				</div>
				<p class="content"><?php echo $end_time; ?> - <?php echo $end_date; ?></p>
			</td>
			<td>
				<div class="title">
					<span class="dashicons dashicons-location"></span>
					<h6>Location</h6>
				</div>
				<p class="content"><?php echo $location_f; ?></p>
			</td>
		</tr>
		<tr>
			<td>
				<div class="title">
					<span class="dashicons dashicons-hourglass"></span>
					<h6>Registration End Date</h6>
				</div>
				<p class="content"><?php echo $register_end_time; ?> - <?php echo $register_end_date; ?></p>
			</td>
			<td>
				<div class="title">
					<span class="dashicons dashicons-category"></span>
					<h6>Category</h6>
				</div>
				<p class="content">content</p>
			</td>
			<td>
				<div class="title">
					<span class="dashicons dashicons-editor-ul"></span>
					<h6>Type</h6>
				</div>
				<p class="content">content</p>
			</td>
		</tr>
	</table>
</div>
