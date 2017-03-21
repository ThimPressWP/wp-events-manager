<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$content = wpems_get_option( $field['id'] );
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
		<?php if ( isset( $field['id'] ) ) : ?>
			<?php wp_editor( $content, $field['id'], isset( $field['options'] ) ? $field['options'] : array() ); ?>

			<?php if ( isset( $field['desc'] ) ) : ?>
                <div class="description"><?php echo esc_html( $field['desc'] ) ?></div>
			<?php endif; ?>
		<?php endif; ?>
    </td>
</tr>