<?php
// array(
// 	'type'		=> 'multiradio',
// 	'label'		=> __( 'Sex', 'tp-event' ),
// 	'atts'		=> array(
// 			'id'	=> 'decimals',
// 			'class'	=> 'decimals'
// 		),
// 	'name'		=> 'currency_num_decimal',
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
        <?php unset( $option['id'] ); ?>
        <p>
            <input type="radio" name="<?php echo esc_attr( $this->get_field_name( $field['name'] ) ) ?>" value="<?php printf( '%s', $option['value'] ) ?>"<?php echo $this->render_atts( $field['atts'] ) ?> id="<?php echo esc_attr( $this->get_field_id( $field['name'] ) . $option['value'] ); ?>"
            <?php echo $option['value'] === $this->get( $field['name'] ) ? ' checked="checked"' : '' ?>
                   />
            <label for="<?php echo esc_attr( $this->get_field_id( $field['name'] ) ) . $option['value']; ?>"><?php printf( '%s', $option['label'] ) ?></label>
        </p>
    <?php endforeach; ?>

<?php endif; ?>