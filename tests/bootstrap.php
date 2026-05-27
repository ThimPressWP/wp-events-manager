<?php
/**
 * WP Events Manager unit test bootstrap.
 *
 * @package WPEMS\Tests
 */

ini_set( 'display_errors', 'on' );
error_reporting( E_ALL );

if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
	$_SERVER['SERVER_NAME'] = 'localhost';
}

$plugin_dir = dirname( __DIR__ );

defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( dirname( dirname( dirname( $plugin_dir ) ) ) ) . '/' );
defined( 'WPEMS_PATH' ) || define( 'WPEMS_PATH', $plugin_dir . '/' );
defined( 'WPEMS_INC' ) || define( 'WPEMS_INC', WPEMS_PATH . 'inc/' );
defined( 'WPEMS_INC_URI' ) || define( 'WPEMS_INC_URI', 'https://example.test/wp-content/plugins/wp-events-manager/inc/' );
defined( 'WPEMS_VER' ) || define( 'WPEMS_VER', 'test' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'ARRAY_N' ) || define( 'ARRAY_N', 'ARRAY_N' );
defined( 'OBJECT' ) || define( 'OBJECT', 'OBJECT' );

require_once $plugin_dir . '/vendor/autoload.php';
require_once __DIR__ . '/Unit/TestCase.php';

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Minimal WP_Post test double.
	 */
	class WP_Post {
		/**
		 * Common WP_Post fields.
		 *
		 * @var mixed
		 */
		public $ID, $post_author, $post_date, $post_date_gmt, $post_modified, $post_modified_gmt, $post_content, $post_title, $post_excerpt, $post_status, $post_password, $post_name, $post_type, $post_parent, $filter;

		/**
		 * Map arbitrary post fields.
		 *
		 * @param array|object $data Post-like data.
		 */
		public function __construct( $data = array() ) {
			foreach ( (array) $data as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error test double.
	 */
	class WP_Error {
		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message = '';

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->message = $message;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WPEMS_Shortcodes' ) ) {
	/**
	 * Minimal shortcode renderer double.
	 */
	class WPEMS_Shortcodes {
		/**
		 * Last render call.
		 *
		 * @var array
		 */
		public static $last_render = array();

		/**
		 * Reset call state.
		 *
		 * @return void
		 */
		public static function reset() {
			self::$last_render = array();
		}

		/**
		 * Render a shortcode template.
		 *
		 * @param string $shortcode Shortcode key.
		 * @param string $template  Template file.
		 * @param array  $args      Template args.
		 *
		 * @return string
		 */
		public static function render( $shortcode = '', $template = '', $args = array() ): string {
			self::$last_render = func_get_args();

			return 'rendered:' . $shortcode;
		}

		/**
		 * Render list shortcode.
		 *
		 * @param array $atts Shortcode atts.
		 *
		 * @return string
		 */
		public static function list_event( $atts ): string {
			return self::render( 'list-event', 'list-event.php', array( 'atts' => $atts ) );
		}

		/**
		 * Render login shortcode.
		 *
		 * @param array $atts Shortcode atts.
		 *
		 * @return string
		 */
		public static function login( $atts ): string {
			return self::render( 'login', 'login.php', array( 'atts' => $atts ) );
		}

		/**
		 * Render account shortcode.
		 *
		 * @param array $atts Shortcode atts.
		 *
		 * @return string
		 */
		public static function account( $atts ): string {
			return self::render( 'account', 'account.php', array( 'atts' => $atts ) );
		}

		/**
		 * Render countdown shortcode.
		 *
		 * @param array $atts Shortcode atts.
		 *
		 * @return string
		 */
		public static function countdown( $atts ): string {
			return self::render( 'countdown', 'countdown.php', array( 'atts' => $atts ) );
		}

		/**
		 * Wrapper start placeholder.
		 *
		 * @return void
		 */
		public static function shortcode_wrapper_start() {}

		/**
		 * Wrapper end placeholder.
		 *
		 * @return void
		 */
		public static function shortcode_wrapper_end() {}

		/**
		 * Auto shortcode placeholder.
		 *
		 * @return void
		 */
		public static function auto_shortcode() {}
	}
}

/**
 * Compatibility wrapper for legacy test bootstrap callers.
 */
class WPEMS_Unit_Tests_Bootstrap {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Minimal WP_List_Table stub for unit tests.
	 */
	class WP_List_Table {
		public $items = array();
		protected $_column_headers = array();
		protected $_args = array();

		public function __construct( $args = array() ) {
			$this->_args = wp_parse_args( $args, array(
				'singular' => '',
				'plural'   => '',
				'ajax'     => false,
			) );
		}

		public function get_columns(): array { return array(); }
		public function get_sortable_columns(): array { return array(); }
		public function get_bulk_actions(): array { return array(); }
		public function prepare_items(): void {}
		public function display(): void {}
		public function display_filters(): void {}
		public function search_box( $text, $input_id ): void {}
		public function no_items(): void {}
		public function column_default( $item, $column_name ): string { return ''; }
		public function column_cb( $item ): string { return ''; }
		public function get_pagenum(): int { return 1; }
		public function get_items_per_page( $option, $default = 20 ): int { return $default; }
		public function set_pagination_args( $args ): void {}
		public function has_items(): bool { return ! empty( $this->items ); }
		protected function row_actions( $actions, $always_visible = false ): string {
			$html = '<div class="row-actions">';
			foreach ( $actions as $action => $link ) {
				$html .= '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
			}
			$html .= '</div>';
			return $html;
		}
	}
}

// WP-CLI stubs for unit tests (global namespace).
if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static function log( $msg ): void {}
		public static function success( $msg ): void {}
		public static function error( $msg ): void { throw new \RuntimeException( 'CLI error: ' . $msg ); }
		public static function warning( $msg ): void {}
		public static function add_command( $name, $class ): void {}
	}
}

WPEMS_Unit_Tests_Bootstrap::instance();
