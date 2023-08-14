<?php
/**
 * WP Events Manager Checkbox setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$val = wpems_get_option( $field['id'] );
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
        <input type="hidden" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>" value="0" />
        <input type="checkbox" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>" value="1"
			<?php echo empty( $val ) ? checked( $field['default'], 1 ) : checked( $val, 1, false ); ?>
        />

		<?php if ( isset( $field['desc'] ) ) : ?>
            <div class="description"><?php echo esc_html( $field['desc'] ) ?></div>
		<?php endif; ?>
    </td>
</tr>