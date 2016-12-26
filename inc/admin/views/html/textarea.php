<textarea name="<?php echo esc_attr( $this->get_field_name( $field['name'] ) ) ?>"<?php echo $this->render_atts( $field['atts'] ) ?>>
    <?php echo esc_textarea( trim( $this->get( $field['name'] ) ) ) ?>
</textarea>