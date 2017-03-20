<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$selected = wpems_get_option( $field['id'], isset( $field['default'] ) ? $field['default'] : array() );
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
		<?php if ( isset( $field['options'] ) ) : ?>
            <select name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?><?php echo $field['type'] === 'multiselect' ? '[]' : '' ?>"
                    id="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>"
				<?php echo ( $field['type'] === 'multiple' ) ? 'multiple="multiple"' : '' ?>
            >
				<?php foreach ( $field['options'] as $val => $text ) : ?>
                    <option value="<?php echo esc_attr( $val ) ?>"
						<?php echo ( is_array( $selected ) && in_array( $val, $selected ) ) || $selected === $val ? ' selected' : '' ?>
                    >
						<?php echo esc_html( $text ) ?>
                    </option>
				<?php endforeach; ?>
            </select>
			<?php if ( isset( $field['desc'] ) ) : ?>
                <div class="description"><?php echo esc_html( $field['desc'] ) ?></div>
			<?php endif; ?>
		<?php endif; ?>
    </td>
</tr>