<?php
/**
 * WP Events Manager Admin Setting Emails class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Admin_Setting_Emails extends WPEMS_Abstract_Setting {

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
		$this->id    = 'event_emails';
		$this->label = __( 'Emails', 'wp-events-manager' );
		parent::__construct();
	}

	/**
	 * Get options setting page
	 * @return type array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';

		$register_event_mail = wpems_get_option( 'email_enable' );

		return apply_filters(
			'event_admin_setting_page_' . $this->id,
			array(
				array(
					'type'  => 'section_start',
					'id'    => 'email_settings',
					'title' => __( 'Email Notifications', 'wp-events-manager' ),
				),
				array(
					'type'    => 'yes_no',
					'title'   => __( 'Event register', 'wp-events-manager' ),
					'desc'    => __( 'Send email to admin and user when user registers event', 'wp-events-manager' ),
					'id'      => $prefix . 'email_enable',
					'default' => 'yes',
				),
				array(
					'type'        => 'text',
					'title'       => __( 'From name', 'wp-events-manager' ),
					'placeholder' => get_option( 'blogname' ),
					'id'          => $prefix . 'email_from_name',
					'default'     => get_option( 'blog_name' ),
					'class'       => 'email-setting-form-name' . ( $register_event_mail == 'no' ? ' hide-if-js' : '' ),
				),
				array(
					'type'        => 'email',
					'title'       => __( 'Email from', 'wp-events-manager' ),
					'placeholder' => get_option( 'admin_email' ),
					'id'          => $prefix . 'admin_email',
					'default'     => get_option( 'admin_email' ),
					'class'       => 'email-setting-email-form' . ( $register_event_mail == 'no' ? ' hide-if-js' : '' ),
				),
				array(
					'type'        => 'text',
					'title'       => __( 'Subject', 'wp-events-manager' ),
					'placeholder' => __( 'Register event', 'wp-events-manager' ),
					'id'          => $prefix . 'email_subject',
					'default'     => '',
					'class'       => 'email-setting-subject' . ( $register_event_mail == 'no' ? ' hide-if-js' : '' ),
				),
				array(
					'type'       => 'textarea',
					'title'      => __( 'Message Body', 'wp-events-manager' ),
					//'placeholder' => __( 'Register event', 'wp-events-manager' ),
					'id'         => $prefix . 'email_body',
					'default'    => $this->wpems_render(),
					'class'      => 'email-setting-body' . ( $register_event_mail == 'no' ? ' hide-if-js' : '' ),
					'options'    => array(
						'media_buttons' => false,
					),
					'allow_tags' => array(
						'{user_displayname}',
						'{user_link}',
						'{event_title}',
						'{event_link}',
						'{event_type}',
						'{booking_id}',
						'{booking_quantity}',
						'{booking_price}',
						'{booking_payment_method}',
						'{booking_status}',
					),
				),
				array(
					'type'    => 'checkbox',
					'title'   => __( 'Account register', 'wp-events-manager' ),
					'desc'    => __( 'Send notify when user register account', 'wp-events-manager' ),
					'id'      => $prefix . 'register_notify',
					'default' => false,
				),
				array(
					'type' => 'section_end',
					'id'   => 'email_settings',
				),
			)
		);
	}

	function wpems_render() {
		$content = wpems_get_template_content( 'emails/register-event-body.php' );

		return $content;
	}

}

return new WPEMS_Admin_Setting_Emails();
