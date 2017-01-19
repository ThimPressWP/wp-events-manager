<?php
/*
 * @Author : leehld
 * @Date   : 1/19/2017
 * @Last Modified by: leehld
 * @Last Modified time: 1/19/2017
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php if ( tp_event_get_note() ): ?>
    <div class="event-note">
        <h4><?php echo esc_html__('Event note:') ?></h4>
		<?php echo tp_event_get_note(); ?>
    </div>
<?php endif; ?>