<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * register metabox
 */
abstract class Event_Abstract_Meta_Box {

    /**
     * id of the meta box
     * @var null
     */
    protected $_id = null;

    /**
     * meta key prefix
     * @var string
     */
    public $_prefix = 'tp_event_';

    /**
     * title of metabox
     * @var null
     */
    protected $_title = null;

    /**
     * array name
     * @var array
     */
    protected $_name = array();

    /**
     * layout file render metabox options
     * @var null
     */
    protected $_layout = null;

    /**
     * screen post, page, tp_event
     * @var array
     */
    public $_screen = array( 'tp_event' );

    public function __construct() {
        if ( !$this->_id )
            return;

        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'update' ), 10, 3 );
        add_action( 'delete_post', array( $this, 'delete' ) );
    }

    /**
     * add meta box
     */
    public function add_meta_box() {
        foreach ( $this->_screen as $post_type ) {
            add_meta_box(
                    $this->_id, $this->_title, array( $this, 'render' ), $post_type, 'normal', 'high'
            );
        }
    }

    /**
     * build meta box layout
     */
    public function render() {
        wp_nonce_field( 'thimpress_event', 'thimpress_event_metabox' );

        do_action( 'event_metabox_before_render', $this->_id );

        // require_once $this->_layout;
        $this->_fields = apply_filters( 'event_metabox_fields', $this->load_field(), $this->_id );

        if ( !empty( $this->_fields ) ) {
            $html = array();

            $html[] = '<ul class="event_metabox_setting" id="' . esc_attr( $this->_id ) . '">';
            $i = 0;
            foreach ( $this->_fields as $id => $group ) {
                if ( isset( $group['title'] ) ) {
                    $html[] = '<li><a href="#" id="' . esc_attr( $id ) . '"' . ( $i === 0 ? ' class="nav-tab-active"' : '' ) . '>' . sprintf( '%s', $group['title'] ) . '</a>';

                    if ( isset( $group['desc'] ) )
                        $html[] = '<p>' . sprintf( '%s', $group['desc'] ) . '</p>';

                    $html[] = '</li>';
                }
                $i++;
            }
            $html[] = '</ul>';

            $html[] = '<div class="event_metabox_setting_container">';
            foreach ( $this->_fields as $id => $group ) {
                $html[] = '<div class="event_metabox_setting_section" data-id="' . esc_attr( $id ) . '">';
                if ( !empty( $group['fields'] ) ) {
                    $html[] = '<table>';
                    foreach ( $group['fields'] as $type => $field ) {

                        if ( isset( $field['name'], $field['type'] ) ) {
                            $html[] = '<tr>';

                            // label
                            $html[] = '<th><label for="' . $this->get_field_id( $field['name'] ) . '">' . sprintf( '%s', $field['label'] ) . '</label>';

                            if ( isset( $field['desc'] ) ) {
                                $html[] = '<p><small>' . sprintf( '%s', $field['desc'] ) . '</small></p>';
                            }

                            $html[] = '</th>';
                            // end label
                            // field
                            $html[] = '<td>';

                            $default = array(
                                'type' => '',
                                'label' => '',
                                'desc' => '',
                                'atts' => array(
                                    'id' => '',
                                    'class' => ''
                                ),
                                'name' => '',
                                'group' => $this->_id ? $this->_id : null,
                                'options' => array(
                                ),
                                'default' => null
                            );

                            $field = wp_parse_args( $field, $default );

                            ob_start();
                            include TP_EVENT_INC . '/admin/views/html/' . $field['type'] . '.php';
                            $html[] = ob_get_clean();

                            $html[] = '</td>';
                            // end field

                            $html[] = '</tr>';
                        }
                    }
                    $html[] = '</table>';
                } else {
                    ob_start();
                    do_action( 'event_metabox_setting_section', $id );
                    $html[] = ob_get_clean();
                }
                $html[] = '</div>';
            }
            $html[] = '</div>';
            echo implode( '', $html );
        }
//        else if ( $this->_layout && file_exists( $this->_layout ) ) {
//            $this->_layout = apply_filters( 'event_metabox_layout', $this->_layout, $this->_id );
//            do_action( 'tp_event_before_metabox', $this->_id );
//            require_once $this->_layout;
//            do_action( 'tp_event_before_metabox', $this->_id );
//        }

        do_action( 'event_metabox_after_render', $this->_id );
    }

    /**
     * load field
     * @return array
     */
    public function load_field() {
        return array();
    }

    /**
     * get_field_name option
     * @param  string $name
     * @return string
     */
    public function get_field_name( $name = '' ) {
        return $this->_prefix . $name;
    }

    public function get_field_value( $name = '', $default = null ) {
        global $post;
        $meta = get_post_meta( $post->ID, $this->_prefix . $name, true );
        if ( !$meta ) {
            $meta = $default;
        }

        return $meta;
    }

    public function update( $post_id, $post, $update ) {
        if ( !isset( $_POST ) || empty( $_POST ) )
            return;

        if ( !in_array( $post->post_type, $this->_screen ) )
            return;

        foreach ( $_POST as $key => $val ) {
            if ( strpos( $key, $this->_prefix ) === 0 || strpos( $key, 'thimpress_event' ) === 0 ) {
                if ( is_string( $val ) ) {
                    $val = sanitize_text_field( trim( $val ) );
                } else if ( is_array( $val ) && !is_array( array_values( $val ) ) ) {
                    $val = array_map( 'trim', $val );
                }
                update_post_meta( $post_id, $key, $val );
            }
        }
    }

    /**
     * delete meta post within post
     * @return
     */
    public function delete( $post_id ) {
        delete_post_meta( $post_id, $this->_id );
    }

}
