<?php
class  WPEMS_Event_Template {
	public static function input_text_template( $key, $id_class, $placeholder ) {
		?>
			<input name="<?php echo esc_attr( $key ); ?>" 
				id=<?php echo $id_class; ?> class=<?php echo $id_class; ?> type='text' placeholder='<?php echo esc_attr__( $placeholder ); ?>' >
		<?php
	}

	public static function input_number_template( $key, $id_class, $placeholder ) {
		?>
			<input name="<?php echo esc_attr( $key ); ?>" 
				id=<?php echo $id_class; ?> class=<?php echo $id_class; ?> type='number' min='0' placeholder='<?php echo esc_attr__( $placeholder ); ?>' >
		<?php
	}

	public static function select_template( $key, $id_class, $placeholder, $array ) {
		?>
			<select name='<?php echo esc_attr( $key ); ?>' id=<?php echo $id_class; ?> class=<?php echo $id_class; ?> >
					<option value=""><?php echo esc_html__( $placeholder ); ?></option>
				<?php
				foreach ( $array as $key => $value ) {
					?>
							<option value="<?php echo  esc_attr( $value->name ); ?>"><?php echo esc_html__( $value->name ); ?></option>
						<?php
				}
				?>
			</select>
		<?php
	}

	public static function price_filter( $class, $numbers ) {
		?>
			<div class='<?php echo $class; ?>'>
				<ul>
					<?php
					foreach ( $numbers as $number ) {
						?>
							<li data-min-value=<?php echo esc_attr( $number ); ?>>$<?php echo esc_html( $number ); ?></li>																	
						<?php
					}
					?>
				</ul>
			</div>
		<?php
	}
}
