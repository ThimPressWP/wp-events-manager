<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$value = wpems_get_option( $field['id'] ) ? wpems_get_option( $field['id'] ) : esc_attr( $field['default'] );
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
        <input
            type="<?php echo esc_attr( $field['type'] ) ?>"
            name="<?php echo esc_attr( $field['id'] ) ?>"
            value="<?php echo esc_attr( $value ) ?>"
            class="regular-text"
            placeholder="<?php echo esc_attr( $field['placeholder'] ) ?>"
			<?php if ( $field['type'] === 'number' ) : ?>

				<?php echo isset( $field['min'] ) && is_numeric( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '' ?>
				<?php echo isset( $field['max'] ) && is_numeric( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '' ?>
				<?php echo isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : '' ?>

			<?php endif; ?>
        />
		<?php if ( isset( $field['desc'] ) ) : ?>
            <div class="description"><?php echo esc_html( $field['desc'] ) ?></div>
		<?php endif; ?>
    </td>
</tr>