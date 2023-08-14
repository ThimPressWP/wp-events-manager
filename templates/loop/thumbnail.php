<?php
/**
 * The Template for displaying thumbnail in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/thumbnail.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( has_post_thumbnail() ) : ?>

	<div class="entry-thumbnail">
		<?php if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) : ?>
		<a href="<?php the_permalink(); ?>">
			<?php endif; ?>
			<?php the_post_thumbnail(); ?>
			<?php if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) : ?>
		</a>
	<?php endif; ?>
	</div>

<?php endif; ?>
