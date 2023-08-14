<?php
/**
 * The Template for displaying title in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/title.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<div class="entry-title">
	<?php if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) : ?>
		<h4><a href="<?php the_permalink(); ?>">
	<?php else : ?>
		<h1>
	<?php endif; ?>
			<?php the_title(); ?>
	<?php if ( ! is_singular( 'tp_event' ) || ! in_the_loop() ) : ?>
		</a></h4>
	<?php else : ?>
		</h1>
	<?php endif; ?>
</div>
