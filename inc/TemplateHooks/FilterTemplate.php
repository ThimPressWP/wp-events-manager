<?php

namespace WPEMS\TemplateHooks;
use WPEMS\Model as Model;
use WP_Query;

class FilterTemplate {
	public $pagination;
	public function __construct() {
		$this->pagination = Model\PaginationModel::getInstance();
	}

	/**
	 * For input type=text element
	 * @param string $name , $id_class, $placeholder, $value
	 * @return string html element
	 */
	public  function html_input_text( $wrapper_class, $name, $id_class, $placeholder, $value ): string {
		$html_template = '<div class="%s"><input name="%s" id="%s" class="%s" type="text" value="%s" placeholder="%s"></div>';

		return sprintf(
			$html_template,
			esc_attr( $wrapper_class ),
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
	public function html_select( $wrapper_class, $name, $id_class, $default, $array, $selected_value ): string {
		$html_template = '<div class="%s"><select name="%s" id="%s" class="%s"><option value="">%s</option>%s</select></div>';

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
			esc_attr( $wrapper_class ),
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
	 * For showing how many posts are storing in the database, the start and the end of quantity's posts on the screen
	 * @param WP_Query  $getPosts from wp_query method
	 */
	public function showResult( WP_Query $getPosts ) {
		$pag       = [];
		$start     = 0;
		$end       = 0;
		$totalPost = 0;

		if ( isset( $this->pagination ) && $getPosts !== null ) {
			$pag       = $this->pagination->pagination( $getPosts );
			$start     = $pag['current_item_start'];
			$end       = $pag['current_item_end'];
			$totalPost = $pag['totalPost'];
		}

		if ( $getPosts->posts === null || count( $getPosts->posts ) === 0 ) {
			?>
				<p><?php echo esc_html__( 'Showing 0 results.' ); ?></p>
			<?php
		}
		if ( is_array( $getPosts->posts ) ) {
			?>
				<p><?php echo esc_html( 'Showing ' . $start . ' - ' . $end . ' of ' . $totalPost . ' results ' ); ?> </p> 
			<?php
		};
	}
}
