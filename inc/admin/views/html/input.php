<?php
// array(
// 	'type'		=> 'input',
// 	'label'		=> __( 'Number of Decimals', 'tp-event' ),
// 	'atts'		=> array(
// 			'type'	=> 'number',
// 			'id'	=> 'decimals',
// 			'class'	=> 'decimals'
// 		),
// 	'name'		=> 'currency_num_decimal',
// 	'default'	=> '2'
// ),
?>

<input name="<?php echo esc_attr( $this->get_field_name( $field['name'] ) ) ?>" value="<?php echo $this->get( $field['name'], $field['default'] ) ?>"<?php echo $this->render_atts( $field['atts'] ) ?>/>
