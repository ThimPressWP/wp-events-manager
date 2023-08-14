<?php
/**
 * WP Events Manager Radio setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$selected = wpems_get_option( $field['id'], isset( $field['default'] ) ? $field['default'] : '' );
?>
<tr valign="top" <?php echo $field['class'] ? 'class="' . $field['class'] . '"' : ''; ?>>
	<th scope="row">
		<?php if ( isset( $field['title'] ) ) : ?>
			<label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : ''; ?>">
				<?php echo esc_html( $field['title'] ); ?>
			</label>
		<?php endif; ?>
	</th>
	<td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ); ?>">
		<?php if ( isset( $field['options'] ) ) : ?>
			<?php foreach ( $field['options'] as $val => $text ) : ?>

				<label>
					<input type="radio" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : ''; ?>"<?php selected( $selected, $val ); ?>/>
					<?php echo esc_html( $text ); ?>
				</label>

			<?php endforeach; ?>

			<?php if ( isset( $field['desc'] ) ) : ?>
				<div class="description"><?php echo esc_html( $field['desc'] ); ?></div>
			<?php endif; ?>
		<?php endif; ?>
	</td>
</tr>
