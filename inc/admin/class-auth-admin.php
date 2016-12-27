<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Auth_Admin {

	public function __construct() {

		add_filter( 'event_admnin_menus', array( $this, 'user_menu' ), 9 );
		// add_action( 'parse_request', array( $this, 'load_booking_by_user' ) );
		$this->_includes();
		add_action( 'event_before_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * include needed files
	 */
	private function _includes() {
		TP_Event::instance()->_include( 'inc/admin/class-auth-admin-settings.php' );
		TP_Event::instance()->_include( 'inc/admin/class-auth-admin-metaboxes.php' );
	}

	/**
	 * Register scripts
	 *
	 * @param type $hook
	 */
	public function register_scripts( $hook ) {
		Event_Assets::register_style( 'event-auth-admin', TP_EVENT_ASSETS_URI . '/css/admin.css' );
	}

	public function user_menu( $menus ) {
		$menus[] = array( 'tp-event-setting', __( 'Users', 'tp-event' ), __( 'Users', 'tp-event' ), 'edit_others_tp_events', 'tp-event-users', array( $this, 'register_options_page' ) );
		return $menus;
	}

	public function register_options_page() {
		TP_Event()->_include( 'inc/admin/class-auth-user-table.php' );
		$user_table = new Auth_User_Table();
		?>
        <div class="wrap">

            <h2><?php _e( 'Event Users', 'tp-event-auth' ); ?></h2>

			<?php $user_table->prepare_items(); ?>
            <form method="post">
				<?php
				// $user_table->search_box( 'search', 'search_id' );
				$user_table->display();
				?>
            </form>

        </div>
		<?php
	}

	public function load_booking_by_user( $query ) {
		if ( is_admin() && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'event_auth_book' ) {
			if ( 'event_auth_book' === $query->query_vars['post_type'] ) {
				$query->query_vars['posts_per_page'] = 3;
			}
		}
		return $query;
	}

}

new Auth_Admin();
