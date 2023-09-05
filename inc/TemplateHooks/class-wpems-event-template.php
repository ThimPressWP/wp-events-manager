<?php
namespace WPEMS\Event_Template;

class  WPEMS_Event_Template {
	public static function input_text( $name, $id_class, $placeholder, $value ): string {
		$html_template = '<input name="%s" id="%s" class="%s" type="text" value="%s" placeholder="%s">';

		return sprintf(
			$html_template,
			esc_attr( $name ),
			esc_attr( $id_class ),
			esc_attr( $id_class ),
			esc_attr( $value ),
			esc_attr__( ucfirst( $placeholder ), 'wp-events-manager' )
		);
	}

	public static function input_number( $name, $id_class, $placeholder, $value ): string {
		$html_template = '<input name="%s" id="%s" class="%s" value="%s" type="number" min="0" placeholder="%s">';

		return sprintf(
			$html_template,
			esc_attr( $name ),
			esc_attr( $id_class ),
			esc_attr( $id_class ),
			esc_attr( $value ),
			esc_attr__( ucfirst( $placeholder ), 'wp-events-manager' )
		);
	}

	public static function select( $name, $id_class, $default, $array, $selected_value ): string {
		$html_template = '<select name="%s" id="%s" class="%s"><option value="">%s</option>%s</select>';

		$options = '';
		foreach ( $array as $item ) {
			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $item->name ),
				selected( $selected_value, $item->name, false ),
				esc_html__( ucfirst( $item->name ), 'wp-events-manager' )
			);
		}

		return sprintf(
			$html_template,
			esc_attr( $name ),
			esc_attr( $id_class ),
			esc_attr( $id_class ),
			esc_html( ucfirst( $default ), 'wp-events-manager' ),
			$options
		);
	}

	public static function price_filter( $class, $numbers, $attr ): string {
		$html_template = '<div class="%s"><ul>%s</ul></div>';

		$list_items = '';
		foreach ( $numbers as $number ) {
			$list_items .= sprintf(
				'<li %s="%s">$%s</li>',
				esc_attr( $attr ),
				esc_attr( $number ),
				esc_html( $number )
			);
		}

		return sprintf( $html_template, esc_attr( $class ), $list_items );
	}

	public static function content( $content, $class ): string {
		$html_template = '<div class="%s">%s</div>';

		return sprintf( $html_template, esc_attr( $class ), esc_html__( ucfirst( $content ), 'wp-events-manager' ) );
	}

	public static function date( $time, $class ): string {
		$html_template = '<div class="%s">%s</div>';

		return sprintf( $html_template, esc_attr( $class ), esc_html( date( 'd', strtotime( $time ) ) ) );
	}

	public static function month( $time, $class ): string {
		$html_template = '<div class="%s">%s</div>';

		return sprintf( $html_template, esc_attr( $class ), esc_html__( date_i18n( 'M', strtotime( $time ) ), 'wp-events-manager' ) );
	}

	public static function time_start_end( $start, $end, $class ): string {
		$html_template = '<span class="%s">%s - %s</span>';

		return sprintf(
			$html_template,
			esc_attr( $class ),
			esc_html( date( 'g:i A', strtotime( $start ) ) ),
			esc_html( date( 'g:i A', strtotime( $end ) ) )
		);
	}

	public static function img( $src, $class, $alt ): string {
		$html_template = '<img src="%s" class="%s" alt="%s">';

		return sprintf(
			$html_template,
			esc_attr( get_the_post_thumbnail_url( $src, 'full' ) ),
			esc_attr( $class ),
			esc_attr( $alt )
		);
	}

	public static function get_template_file( $class, $url, $value ): string {
		$html_template = '<div class="%s">%s</div>';

		ob_start();
		wpems_get_template( $url, $value );
		$template_content = ob_get_clean();

		return sprintf( $html_template, esc_attr( $class ), $template_content );
	}

	public static function pagination( $class, $max_num_pages, $paged ): string {
		$html_template = '<div class="%s">%s</div>';

		$pagination = paginate_links(
			array(
				'total'   => $max_num_pages,
				'current' => $paged,
			)
		);

		return sprintf( $html_template, esc_attr( $class ), $pagination );
	}
}
