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

<?php if ( wpems_get_event_note() ): ?>
    <div class="entry-note">
        <h4><?php echo esc_html__('Event note:', 'wp-events-manager'); ?></h4>
		<?php echo wpems_get_event_note(); ?>
    </div>
<?php endif; ?>