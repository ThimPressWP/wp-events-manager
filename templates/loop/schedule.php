<?php
/**
 * The Template for displaying schedule in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/schedule.php
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

$schedules_f   = get_post_meta( $post->ID, 'tp_event_schedules', true );
$arr_schedules = json_decode( $schedules_f, true );
?>

<div class="entry-schedule">
	<h6 class="schedule_header">Schedule</h6>

	<?php foreach ( $arr_schedules as $key => $value ) : ?>
		<div class="schedule_body" id="<?php echo $key; ?>">
			<div class="schedule_body-header">
				<p class="schedule_title">
					<?php echo $value['title']; ?>
				</p>
				<div class="schedule_button">
					<span class="dashicons-before dashicons-minus"></span>
					<span class="dashicons-before dashicons-plus"></span>
				</div>
			</div>
			<div class="schedule_body-content">
				<p><?php echo $value['description']; ?></p>
			</div>
		</div>
	<?php endforeach; ?>
</div>
