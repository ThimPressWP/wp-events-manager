<?php
/**
 * @Author: ducnvtt
 * @Date:   2016-02-24 16:43:35
 * @Last Modified by:   ducnvtt
 * @Last Modified time: 2016-03-24 09:04:55
 */
if ( !defined( 'ABSPATH' ) ) {
    exit();
}

$multiple = false;
if ( isset( $field['atts'], $field['atts']['multiple'] ) && $field['atts']['multiple'] ) {
    $multiple = true;
}

if ( !isset( $field['filter'] ) || !$field['filter'] ) :
    ?>
    <select name="<?php echo esc_attr( $this->get_field_name( $field['name'] ) ) . ( $multiple ? '[]' : '' ) ?>"<?php echo $this->render_atts( $field['atts'] ) ?>>

        <?php if ( $field['options'] ): ?>

            <?php foreach ( $field['options'] as $key => $value ): ?>

                <?php
                $val = $this->get( $field['name'] );
                if ( empty( $val ) && isset( $field['default'] ) ) {
                    $val = $field['default'];
                }
                ?>

                <?php if ( $multiple ): ?>
                    <!--Multi select-->
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo in_array( $key, $val ) ? ' selected="selected"' : '' ?>><?php printf( '%s', $value ) ?></option>
                <?php else: ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo $val == $key ? ' selected="selected"' : '' ?>><?php printf( '%s', $value ) ?></option>
                <?php endif; ?>

            <?php endforeach; ?>

        <?php endif; ?>

    </select>
<?php else: ?>

    <?php
    ob_start();
    $field['filter']( $field );
    echo ob_get_clean();
    ?>

<?php endif; ?>