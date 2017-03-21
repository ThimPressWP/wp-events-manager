<?php
defined( 'ABSPATH' ) || exit();

class WPEMS_Admin_Settings {

	private static $messages = array();

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
	}

	/**
	 * Get Setting Page
	 */
	public static function get_setting_pages() {
		$settings   = array();
		$settings[] = require_once WPEMS_INC . 'admin/settings/class-wpems-admin-setting-general.php';
		$settings[] = require_once WPEMS_INC . 'admin/settings/class-wpems-admin-setting-pages.php';
		$settings[] = require_once WPEMS_INC . 'admin/settings/class-wpems-admin-setting-emails.php';
		$settings[] = require_once WPEMS_INC . 'admin/settings/class-wpems-admin-setting-checkout.php';
		return apply_filters( 'event_admin_setting_pages', $settings );
	}

	/**
	 * Add message
	 *
	 * @param type $message
	 *
	 * @since 2.0
	 */
	public static function add_message( $message = '' ) {
		self::$messages[] = $message;
	}

	/**
	 * Display messages
	 * @since 2.0
	 */
	public static function show_messages() {
		foreach ( self::$messages as $message ) {
			echo '<div class="updated inline"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Save event setting
	 * @since 2.0
	 */
	public static function save() {
		if ( empty( $_POST['tp-event-settings-nonce'] ) || !wp_verify_nonce( $_POST['tp-event-settings-nonce'], 'tp-event-settings' ) ) {
			return false;
		}
		global $current_tab;

		do_action( 'event_admin_setting_update_' . $current_tab );
		do_action( 'event_admin_setting_update', $current_tab );

		self::add_message( __( 'Your settings have been saved.', 'wp-events-manager' ) );
		do_action( 'event_admin_settings_updated', $_POST );
	}

	/**
	 * Output page setting
	 * @since 2.0
	 */
	public static function output() {
		global $current_tab, $current_section;
		self::get_setting_pages();
		$tabs            = apply_filters( 'event_admin_settings_tabs_array', array() );
		$current_tab     = isset( $_GET['tab'] ) && $_GET['tab'] ? sanitize_text_field($_GET['tab']) : current( array_keys( $tabs ) );
		$current_section = isset( $_GET['section'] ) && $_GET['section'] ? sanitize_text_field($_GET['section']) : '';
		if ( !empty( $_POST ) ) {
			self::save();
		}
		if ( $tabs ): ?>
            <div class="wrap">
                <form method="POST" name="tp_event_options" action="">
                    <h2 class="nav-tab-wrapper">
						<?php foreach ( $tabs as $key => $title ): ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tp-event-setting&tab=' . $key ) ); ?>" class="nav-tab<?php echo $current_tab === $key ? ' nav-tab-active' : '' ?>" data-tab="<?php echo esc_attr( $key ) ?>">
								<?php printf( '%s', $title ) ?>
                            </a>
						<?php endforeach; ?>
                    </h2>
                    <div class="tp_event_wrapper_content">
						<?php do_action( 'event_admin_setting_sections_' . $current_tab ); ?>
                        <!--Display message updated || error-->
						<?php self::show_messages(); ?>
						<?php do_action( 'event_admin_setting_' . $current_tab ); ?>
                    </div>
                    <p class="submit">
						<?php wp_nonce_field( 'tp-event-settings', 'tp-event-settings-nonce' ); ?>
                        <input name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'wp-events-manager' ); ?>" />
                    </p>
                </form>
            </div>
		<?php endif;
	}

	/**
	 * Render fields
	 *
	 * @param type $fields
	 *
	 * @return type mixed
	 */
	public static function render_fields( $fields = array() ) {
		if ( empty( $fields ) ) {
			return;
		}
		foreach ( $fields as $k => $field ) {
			$field = wp_parse_args( $field, array(
				'id'          => '',
				'class'       => '',
				'title'       => '',
				'desc'        => '',
				'default'     => '',
				'type'        => '',
				'placeholder' => '',
				'options'     => '',
				'atts'        => array()
			) );

			$custom_attr = '';
			if ( !empty( $field['atts'] ) ) {
				foreach ( $field['atts'] as $k => $val ) {
					$custom_attr .= $k . '="' . $val . '"';
				}
			}
			switch ( $field['type'] ) {
				case 'section_start':
					include( WPEMS_INC . 'admin/views/settings/section-start.php' );
					break;
				case 'section_end':
					include( WPEMS_INC . 'admin/views/settings/section-end.php' );
					break;

				case 'select':
				case 'multiselect':
					include( WPEMS_INC . 'admin/views/settings/select.php' );
					break;

				case 'text':
				case 'number':
				case 'email':
				case 'password':
					include( WPEMS_INC . 'admin/views/settings/text.php' );
					break;

				case 'checkbox':
					include( WPEMS_INC . 'admin/views/settings/checkbox.php' );
					break;

				case 'yes_no':
					include( WPEMS_INC . 'admin/views/settings/yes-no.php' );
					break;

				case 'radio':
					include( WPEMS_INC . 'admin/views/settings/radio.php' );
					break;

				case 'image_size':
					include( WPEMS_INC . 'admin/views/settings/image-size.php' );
					break;

				case 'textarea':
					include( WPEMS_INC . 'admin/views/settings/textarea.php' );
					break;

				case 'select_page':
					include( WPEMS_INC . 'admin/views/settings/select-page.php' );
					break;

				default:
					do_action( 'tp_event_setting_field_' . $field['id'], $field );
					break;
			}
		}
	}

	/**
	 * Save fields options
	 *
	 * @param type $settings
	 *
	 * @since 2.0
	 */
	public static function save_fields( $settings = array() ) {
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && array_key_exists( $setting['id'], $_POST ) ) {
				update_option( $setting['id'], sanitize_text_field($_POST[$setting['id']]) );
			}
		}
	}

	public static function register_setting() {
		register_setting( 'thimpress_events', 'thimpress_events' );
	}

}

WPEMS_Admin_Settings::init();
