<?php

namespace WPEMS\Shortcodes\Sanitize;

class RequestPattern {

	public static function get_param( string $key, $default = '', string $sanitize_type = 'text', string $method = '' ) {
		switch ( strtolower( $method ) ) {
			case 'post':
				$values = $_POST ?? [];
				break;
			case 'get':
				$values = $_GET ?? [];
				break;
			default:
				$values = $_REQUEST ?? [];
		}

		return self::sanitize_params_submitted( $values[ $key ] ?? $default, $sanitize_type );
	}

	public static function sanitize_params_submitted( $value, string $type_content = 'text' ) {
		$value = wp_unslash( $value );

		if ( is_string( $value ) ) {
			switch ( $type_content ) {
				case 'html':
					$value = wp_kses_post( $value );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $value );
					break;
				case 'key':
					$value = sanitize_key( $value );
					break;
				case 'int':
					$value = (int) $value;
					break;
				case 'float':
					$value = (float) $value;
					break;
				default:
					$value = sanitize_text_field( $value );
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = self::sanitize_params_submitted( $v, $type_content );
			}
		}

		return $value;
	}

}
new RequestPattern();
