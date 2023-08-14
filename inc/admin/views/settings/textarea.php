<?php
/**
 * WP Events Manager Textarea setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$content = wpems_get_option( $field['id'] ) == '' ? $field['default'] : html_entity_decode( wpems_get_option( $field['id'] ) );
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
		<?php if ( isset( $field['id'] ) ) : ?>
			<?php wp_editor( $content, $field['id'], isset( $field['options'] ) ? $field['options'] : array() ); ?>

			<?php if ( isset( $field['desc'] ) ) : ?>
				<div class="description"><?php echo esc_html( $field['desc'] ); ?></div>
			<?php endif; ?>
			<?php if ( isset( $field['allow_tags'] ) ) : ?>
				<ol class="event-form-email-variables"
					data-target="<?php echo esc_attr( sanitize_key( $field['id'] ) ); ?>">
					<?php foreach ( $field['allow_tags'] as $variable ) : ?>
						<li data-variable="<?php echo esc_attr( $variable ); ?>">
							<code><?php echo $variable; ?></code></li>
					<?php endforeach; ?>
				</ol>
				<p class="description">
					<?php esc_html_e( 'These variables are allowed to use in email content.', 'wp-events-manager' ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</td>
</tr>
