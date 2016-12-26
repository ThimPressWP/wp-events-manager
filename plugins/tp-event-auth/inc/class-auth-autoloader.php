<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class TP_Event_Auth_Autoload {

    private $include_path = null;

    public function __construct() {

        if ( function_exists( '__autoload' ) ) {
            spl_autoload_register( '__autoload' );
        }

        $this->include_path = untrailingslashit( TP_EVENT_AUTH_PATH ) . '/inc/';

        spl_autoload_register( array( $this, 'autoload' ) );
    }

    /**
     * Take a class name and turn it into a file name
     * @param  string $class
     * @return string
     */
    private function get_file_name_from_class( $class ) {
        return 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
    }

    /**
     * Include a class file
     * @param  string $path
     * @return bool successful or not
     */
    private function load_file( $path ) {
        if ( $path && is_readable( $path ) ) {
            include_once( $path );
            return true;
        }
        return false;
    }

    /**
     * Auto-load HB classes on demand to reduce memory consumption.
     *
     * @param string $class
     */
    public function autoload( $class ) {
        $class = strtolower( $class );
        $file  = $this->get_file_name_from_class( $class );
        $path  = $this->include_path;

        // payment gateways
        if ( strpos( $class, 'auth_payment_gateway_' ) === 0 ) {
            $path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class), strlen( 'auth_payment_gateway_' ) ) . '/';
        }

        // widgets
        if ( stripos( $class, 'auth_widget_' ) === 0 ) {
            $path = $this->include_path . '/widgets/';
        }

        // admin metaboxs
        if ( strpos( $class, 'auth_admin_metabox_' ) === 0 ) {
            $path = $this->include_path . 'admin/metaboxes/';
        }

        $this->load_file( $path . $file );
    }

}

new TP_Event_Auth_Autoload();
