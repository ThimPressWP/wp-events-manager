<?php
// array(
// 	'type'		=> 'multicheckbox',
// 	'label'		=> __( 'Sex', 'tp-event' ),
// 	'atts'		=> array(
// 			'class'	=> 'decimals'
// 		),
// 	'name'		=> 'sex',
// 	'options'	=> array(
// 			array(
// 					'label'		=> __( 'Man', 'tp-event' ),
// 					'value'		=> '1'
// 				),
// 			array(
// 					'label'		=> __( 'Wowan', 'tp-event' ),
// 					'value'		=> '0'
// 				)
// 		),
// 	'default'	=> '2'
// ),
?>

<?php if ( !empty( $field['options'] ) ): ?>

    <?php foreach ( $field['options'] as $k => $option ): ?>
        <?php unset( $field['id'] ); ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( $field['name'] ) ) ?>[]" value="<?php printf( '%s', $option['value'] ) ?>"<?php echo $this->render_atts( $field['atts'] ) ?> id="<?php echo esc_attr( $this->get_field_id( $field['name'] ) . $option['value'] ); ?>"
            <?php echo in_array( $option['value'], $this->get( $field['name'], array() ) ) ? ' checked="checked"' : '' ?>
                   />
            <label for="<?php echo esc_attr( $this->get_field_id( $field['name'] ) ) . $option['value']; ?>"><?php printf( '%s', $option['label'] ) ?></label>
        </p>
    <?php endforeach; ?>

<?php endif; ?>