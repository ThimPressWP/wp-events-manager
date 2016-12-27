<?php
defined( 'ABSPATH' ) || exit();

class Auth_Admin_Metaboxes {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 3 );
        add_action( 'event_admin_metabox_before_fields', array( __CLASS__, 'add_event_fields' ), 10, 2 );
        
        add_action( 'event_auth_process_update_event_auth_book_meta', array( 'Auth_Admin_Metabox_Booking_Information', 'save' ), 10, 3 );
    }

    public static function add_meta_boxes() {
        add_meta_box( 
            'booking-information',
            __( 'Booking Information', 'tp-event' ),
            array( 'Auth_Admin_Metabox_Booking_Information', 'render' ),
            'event_auth_book',
            'normal',
            'default'
        );
    }

    public static function add_event_fields( $post, $prefix ) {
        $post_id = $post->ID;
        $qty = get_post_meta( $post_id, $prefix . 'qty', true );
        $price = get_post_meta( $post_id, $prefix . 'price', true );
        $is_not_free = get_post_meta( $post_id, $prefix . 'is_not_free', true );
        $data_text = !$is_not_free ? __( 'Free', 'tp-event' ) : __( 'Set Price', 'tp-event' );
        $text = $is_not_free ? __( 'Free', 'tp-event' ) : __( 'Set Price', 'tp-event' );
        ?>
        <div class="option_group">
            <p class="form-field">
                <label for="_quantity"><?php _e( 'Qty', 'tp-event' ) ?></label>
                <input type="number" min="0" step="1" class="short" name="<?php echo esc_attr( $prefix ) ?>qty" id="_quantity" value="<?php echo esc_attr( absint( $qty ) ) ?>">
                <span class="description"><a href="#" data-target="set_price" class="open-extra" data-text="<?php echo esc_attr( $data_text ) ?>"><?php echo esc_html( $text ) ?></a></span>
            </p>
        </div>
        <div class="option_group<?php echo (!$is_not_free ) ? ' hide-if-js' : ''; ?>">
            <input id="set_price" type="hidden" value="<?php echo esc_attr( $is_not_free ) ?>" name="<?php echo esc_attr( $prefix ) ?>is_not_free" />
            <p class="form-field">
                <label for="_auth_cost"><?php printf( '%s(%s)', __( 'Price', 'tp-event' ), event_auth_get_currency_symbol() ) ?></label>
                <input type="number" step="any" min="0" class="short" name="<?php echo esc_attr( $prefix ) ?>price" id="_quantity" value="<?php echo esc_attr( floatval( $price ) ) ?>" />
            </p>
        </div>
        <?php
    }
    
    public static function save_post( $post_id, $post, $update ) {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        $post_type = get_post_type( $post_id );
        if ( in_array( $post_type, array( 'tp_event', 'event_auth_book' ) ) ) {
            do_action( 'event_auth_process_update_' . $post_type . '_meta', $post_id, $post, $update );
        }
    }

}

Auth_Admin_Metaboxes::init();
