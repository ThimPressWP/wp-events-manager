<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$width  = wpems_get_option( $field['id'] . '_width', isset( $field['default']['width'] ) ? $field['default']['width'] : 270 );
$height = wpems_get_option( $field['id'] . '_height', isset( $field['default']['height'] ) ? $field['default']['height'] : 270 );
?>
<tr valign="top" <?php echo $field['class'] ? 'class="' . $field['class'] . '"' : ''; ?>>
    <th scope="row">
		<?php if ( isset( $field['title'] ) ) : ?>
            <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
				<?php echo esc_html( $field['title'] ) ?>
            </label>
		<?php endif; ?>
    </th>
    <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
		<?php if ( isset( $field['id'] ) && isset( $field['options'] ) ) : ?>

			<?php if ( isset( $field['options']['width'] ) ) : ?>
                <input
                    type="number"
                    name="<?php echo esc_attr( $field['id'] ) ?>_width"
                    value="<?php echo esc_attr( $width ) ?>"
                /> x
			<?php endif; ?>
			<?php if ( isset( $field['options']['height'] ) ) : ?>
                <input
                    type="number"
                    name="<?php echo esc_attr( $field['id'] ) ?>_height"
                    value="<?php echo esc_attr( $height ) ?>"
                /> px
			<?php endif; ?>

			<?php if ( isset( $field['desc'] ) ) : ?>
                <div class="description"><?php echo esc_html( $field['desc'] ) ?></div>
			<?php endif; ?>
		<?php endif; ?>
    </td>
</tr>