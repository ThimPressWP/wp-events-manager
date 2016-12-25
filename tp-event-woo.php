<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !defined( 'TP_EVENT_WOO_FILE' ) ) {
	define( 'TP_EVENT_WOO_FILE', __FILE__ );
	require_once dirname( __FILE__ ) . 'includes/event-woo-constants.php';
}

/**
 * Class TP_Event_Woo
 */
class TP_Event_Woo {

	/**
	 * Hold the instance of TP_Event_Woo
	 *
	 * @var null
	 */
	protected static $_instance = null;

	protected static $_error = 0;


	/**
	 * Constructor
	 */
	function __construct() {

	}

	/**
	 *
	 */
	public function admin_notice() { ?>
        <div class="notice notice-error">
            <p>
				<?php switch ( self::$_error ) {
					case 1:
						echo esc_html__( 'Thim Event WooCommerce requires Thim Event plugin was installed. Please install and active it before you can using this add-on.', 'tp-event-woo' );
						break;
					case 2:
						echo esc_html__( 'Thim Event WooCommerce requires Thim Event Authentication plugin was installed. Please install and active it before you can using this add-on.', 'tp-event-woo' );
						break;
					case 3:
						echo esc_html__( wp_kses( 'Thim Event WooCommerce requires <a href="http://wordpress.org/plugins/woocommerce">Woocommerce</a> plugin was installed. Please install and active it before you can using this add-on.', array( 'a' => array( 'href' => array() ) ) ), 'tp-event-woo' );
						break;
				}
				?>
            </p>
        </div>
		<?php
	}

	/**
	 * Load TP_Event_Woo plugin
	 */
	public static function load() {

		if ( !function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( !( class_exists( 'TP_Event' ) && ( is_plugin_active( 'tp-event/tp-event.php' ) || is_plugin_active( 'wp-event/wp-event.php' ) ) ) ) {
			self::$_error = 1;
		}
		if ( !( class_exists( 'TP_Event_Auth' ) && ( is_plugin_active( 'tp-event-auth/tp-event-auth.php' ) || is_plugin_active( 'wp-event-auth/wp-event-auth.php' ) ) ) ) {
			self::$_error = 2;
		}
		if ( !( class_exists( 'WC_Install' ) && ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) ) ) {
			self::$_error = 3;
		}

		if ( self::$_error ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			return false;
		}

		TP_Event_Woo::instance();

	}

}