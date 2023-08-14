<?php
/**
 * WP Events Manager Session class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Session {

	/**
	 * array key of session or cookie
	 * @var type string
	 */
	private $_name = null;

	/**
	 * is change is TRUE if has change $_data
	 * @var type bool
	 */
	private $_is_changed = false;

	/**
	 * session data
	 * @var type array
	 */
	private $_data = array();

	/**
	 * live time of cookie
	 * @var type timestamp
	 */
	private $_live_time = null;

	public function __construct() {
		$this->_name = 'event_auth_session_' . COOKIEHASH;
		$this->_data = $this->get_session_data();
		add_action( 'shutdown', array( $this, 'maybe_save_data' ), 0 );
	}

	/**
	 * get data
	 *
	 * @param type $name
	 */
	public function __get( $name ) {
		$this->get( $name );
	}

	/**
	 * set data
	 *
	 * @param type $name
	 * @param type $value
	 */
	public function __set( $name, $value ) {
		$this->set( $name, $value );
	}

	/**
	 * isset array key data
	 *
	 * @param type $name
	 *
	 * @return type bool
	 */
	public function __isset( $name ) {
		return isset( $this->_data[ $name ] );
	}

	/**
	 * unset item data
	 *
	 * @param type $name
	 */
	public function __unset( $name ) {
		if ( isset( $this->_data[ $name ] ) ) {
			unset( $this->_data[ $name ] );
			$this->_is_changed = true;
		}
	}

	/**
	 * get data
	 *
	 * @param type $name
	 * @param type $default
	 *
	 * @return type
	 */
	public function get( $name = null, $default = null ) {
		return isset( $this->_data[ $name ] ) ? maybe_unserialize( $this->_data[ $name ] ) : $default;
	}

	/**
	 * set data
	 *
	 * @param type $name
	 * @param type $value
	 */
	public function set( $name = '', $value = '' ) {
		if ( $name && $value !== $this->get( $name ) ) {
			$this->_data[ $name ] = maybe_serialize( $value );
			$this->_is_changed    = true;
			$this->maybe_save_data();
		}
	}

	/**
	 * save session data
	 */
	public function maybe_save_data() {
		if ( $this->_is_changed ) {
			$_SESSION[ $this->_name ] = maybe_serialize( $this->_data );
		}
	}

	/**
	 * get session data
	 * @return array
	 */
	private function get_session_data() {
		return isset( $_SESSION[ $this->_name ] ) ? maybe_unserialize( $_SESSION[ $this->_name ] ) : array();
	}

}
