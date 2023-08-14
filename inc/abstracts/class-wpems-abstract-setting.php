<?php
/**
 * WP Events Manager Abstract Setting class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

abstract class WPEMS_Abstract_Setting {

	/**
	 * Setting page id
	 * @var type string
	 */
	protected $id = null;

	/**
	 * Setting page title
	 * @var type string
	 */
	protected $label = null;

	public function __construct() {
		add_filter( 'event_admin_settings_tabs_array', array( $this, 'add_setting_tab' ) );
		add_action( 'event_admin_setting_sections_' . $this->id, array( $this, 'output_section' ) );
		add_action( 'event_admin_setting_update_' . $this->id, array( $this, 'save' ) );
		add_action( 'event_admin_setting_' . $this->id, array( $this, 'output' ) );
	}

	/**
	 * Get options setting page
	 * @return type array
	 */
	public function get_settings() {
		return apply_filters( 'event_admin_setting_page_' . $this->id, array() );
	}

	/**
	 * Get options setting page section
	 * @return type array
	 */
	public function get_sections() {
		return apply_filters( 'event_admin_setting_page_' . $this->id . '_section', array() );
	}

	/**
	 * Add admin setting page 'event_admin_settings_tabs_array' callback
	 *
	 * @param array $tabs
	 *
	 * @return type
	 */
	public function add_setting_tab( $tabs ) {
		$tabs[ $this->id ] = $this->label;
		return $tabs;
	}

	/**
	 * Display section tabs of setting page 'event_admin_setting_sections_' . $this->id callback
	 *
	 * @param type $tab
	 */
	public function output_section( $tab ) {
		global $current_section;
		$sections = $this->get_sections();
		if ( $sections ) {
			echo '<ul class="subsubsub">';
			$array_keys = array_keys( $sections );
			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . admin_url( 'admin.php?page=tp-event-setting&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}

			echo '</ul><br class="clear" />';
		}
	}

	/**
	 * Display Output settings
	 *
	 * @param type $tab
	 */
	public function output( $tab ) {
		$settings = $this->get_settings();
		WPEMS_Admin_Settings::render_fields( $settings );
	}

	/**
	 * Save action callback
	 * @since 2.0
	 */
	public function save() {
		$settings = $this->get_settings();
		WPEMS_Admin_Settings::save_fields( $settings );
	}

}
