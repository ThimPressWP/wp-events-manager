<?php
/**
 * The Template for displaying content in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/content.php
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

<div class="entry-content">
	<?php the_content(); ?>
</div>
