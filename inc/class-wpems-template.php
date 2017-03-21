<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class WPEMS_Template {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		add_filter( 'template_include', array( $this, 'template_loader' ) );
	}

	public function template_loader( $template ) {
		$post_type = get_post_type();

		$file = '';
		$find = array();
		if ( $post_type !== 'tp_event' )
			return $template;

		if ( is_post_type_archive( 'tp_event' ) || is_tax( 'tp_event_category' ) ) {
			$file   = 'archive-event.php';
			$find[] = $file;
			$find[] = wpems_template_path() . '/' . $file;
		} else if ( is_single() ) {
			$file   = 'single-event.php';
			$find[] = $file;
			$find[] = wpems_template_path() . '/' . $file;
		}

		if ( $file ) {
			$find[]   = wpems_template_path() . $file;
			$template = locate_template( array_unique( $find ) );
			if ( !$template ) {
				$template = untrailingslashit( WPEMS_PATH ) . '/templates/' . $file;
			}
		}

		return $template;
	}
}

new WPEMS_Template();
