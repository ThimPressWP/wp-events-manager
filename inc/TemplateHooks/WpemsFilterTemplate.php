<?php

namespace WPEMS\Templates;

class WpemsFilterTemplate {
	/**
	 * For input type=text element
	 * @param string $name , $id_class, $placeholder, $value
	 * @return string html element
	 */
	public  function html_input_text( $name, $id_class, $placeholder, $value ): string {
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

	/**
	 * For input type=number element
	 * @param string $name , $id_class, $placeholder, $value
	 * @return string html element
	 */
	public function html_input_number( $name, $id_class, $placeholder, $value ): string {
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

	/**
	 * For select element
	 * @param string $name , $id_class, $default, $placeholder, $value
	 * @param array $array that store the value of option element
	 * @param checked $selected_value for checking which option was selected
	 * @return string html element
	 */
	public function html_select( $name, $id_class, $default, $array, $selected_value ): string {
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

	/**
	 * For choosing price in price field
	 * @param string $class, $numbers is a list of number that will selected by user
	 * @param string $attr for min price or max price
	 * @return string html element
	 */
	public function html_price_filter( $class, $numbers, $attr ): string {
		$html_template = '<div class="%s"><ul>%s</ul></div>';
		$list_items    = '';
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

	/**
	 * The Pagination
	 *
	 * @param int $max_num_pages Total number of pages
	 * @param int $pageIndex Current page index
	 * @return string HTML element
	 */
	public function html_pagination( $max_num_pages, $pageIndex ): string {
		$html_template = '<div class="event-pagination"><div class="pagination">%s</div></div>';
		$pagination    = paginate_links(
			array(
				'total'   => $max_num_pages,
				'current' => $pageIndex,
			)
		);
		return sprintf( $html_template, $pagination );
	}

}
