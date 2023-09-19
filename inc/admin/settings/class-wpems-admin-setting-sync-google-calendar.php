<?php
/**
 * WP Events Manager Admin Setting Sync Google Calendar class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
use WPEMS\Database as DB;
use WPEMS\Event_Sync as Sync;

class WPEMS_Admin_Setting_Sync_Google_Calendar extends WPEMS_Abstract_Setting {

	/**
	 * ID
	 * @var type mixed
	 */
	public $id = null;

	/**
	 * Title
	 * @var type string
	 */
	public $label = null;

	public function __construct() {
		$this->id    = 'event_sync_google_calendar';
		$this->label = __( 'Sync Calendar', 'wp-events-manager' );
		parent::__construct();

	}

	public function output( $tabs ) {
		wp_enqueue_script( 'wpems-sync-google-js' );
		// wp_add_inline_script(
		// 	'wpems-sync-google-js',
		// 	'const ROUTER = ' . json_encode(
		// 		array(
		// 			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
		// 			'nonce' => 'tp-event-sync-google-calendar-nonce',
		// 		)
		// 	),
		// 	'before'
		// );

		$client_id_db     = '';
		$client_secret_db = '';

		$code      = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		$state_url = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

		if ( ! empty( $state_url ) ) {
			$state         = explode( '--', $state_url );
			$client_id     = $state[0] ?? '';
			$client_secret = $state[1] ?? '';
			$events        = new DB\WPEMS_Event_DB();
			$events->save_user_info( $client_id, $client_secret, $code );
		}

		$id               = get_current_user_id();
		$client_id_db     = get_user_meta( $id, 'google_client_id', true );
		$client_secret_db = get_user_meta( $id, 'google_client_secret', true );

		$sync = new Sync\WPEMS_Admin_Sync_Google_Calendar();
		$sync->sync_google_calendar();
		var_dump( $sync->sync_google_calendar() );

		// if(!isset($client_id_db) || empty($client_id)) {
		?>
			<div class="wrap">
					<table>
						<tr>
							<td>Client ID</td>
							<td>
								<input name="client_id" value="<?php echo $client_id_db; ?>" id="client_id" type="text" placeholder="Client ID">
							</td>
						</tr>
						<tr>
							<td>Client Secret</td>
							<td>
								<input name="client_secret" value="<?php echo $client_secret_db; ?>" id="client_secret" type="text" placeholder="Client Secret">
							</td>
						</tr>
					</table>

					<?php wp_nonce_field( 'tp-event-sync-google-calendar-nonce', 'tp-event-sync-google-calendar-action' ); ?>

					<button type="button"  id="authorization_button"><?php esc_attr_e( 'Authorization', 'wp-events-manager' ); ?></button>
			</div>
			<?php

			// } else {
			?>
				<!-- <p>Your events has been pushed to your google calendar automatically.</p> -->
			<?php
			// }

	}



}

return new WPEMS_Admin_Setting_Sync_Google_Calendar();

