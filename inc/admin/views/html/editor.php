<?php

wp_editor(
        $this->get( $field['name'] ), $this->get_field_id( $field['name'] ), array(
    'textarea_name' => $this->get_field_name( $field['name'] )
        )
);
?>