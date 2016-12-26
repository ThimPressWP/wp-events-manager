<?php
defined( 'ABSPATH' ) || exit();

class Event_Admin_Settings {

    private static $messages = array();

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
    }

    /**
     * Get Setting Page
     */
    public static function get_setting_pages() {
        $settings = array();
        $settings[] = require_once TP_EVENT_INC . 'admin/settings/class-event-admin-setting-general.php';
        return apply_filters( 'event_admin_setting_pages', $settings );
    }

    /**
     * Add message
     * @param type $message
     * @since 1.5
     */
    public static function add_message( $message = '' ) {
        self::$messages[] = $message;
    }

    /**
     * Display messages
     * @since 1.5
     */
    public static function show_messages() {
        foreach ( self::$messages as $message ) {
            echo '<div class="updated inline"><p>' . esc_html( $message ) . '</p></div>';
        }
    }

    /**
     * Save event setting
     * @since 1.5
     */
    public static function save() {
        if ( empty( $_POST['tp-event-settings-nonce'] ) || !wp_verify_nonce( $_POST['tp-event-settings-nonce'], 'tp-event-settings' ) ) {
            return false;
        }
        global $current_tab;

        do_action( 'event_admin_setting_update_' . $current_tab );
        do_action( 'event_admin_setting_update', $current_tab );

        self::add_message( __( 'Your settings have been saved.', 'tp-event' ) );
        do_action( 'event_admin_settings_updated', $_POST );
    }

    /**
     * Output page setting
     * @since 1.5
     */
    public static function output() {
        global $current_tab, $current_section;
        self::get_setting_pages();
        $tabs = apply_filters( 'event_admin_settings_tabs_array', array() );
        $current_tab = isset( $_GET['tab'] ) && $_GET['tab'] ? $_GET['tab'] : current( array_keys( $tabs ) );
        $current_section = isset( $_GET['section'] ) && $_GET['section'] ? $_GET['section'] : '';
        if ( !empty( $_POST ) ) {
            self::save();
        }

        require_once ( TP_EVENT_INC . 'admin/views/html-admin-settings.php' );
    }

    /**
     * Render fields
     * @param type $fields
     * @return type mixed
     */
    public static function render_fields( $fields = array() ) {
        if ( empty( $fields ) ) {
            return;
        }
        foreach ( $fields as $k => $field ) {
            $field = wp_parse_args( $field, array(
                'id' => '',
                'class' => '',
                'title' => '',
                'desc' => '',
                'default' => '',
                'type' => '',
                'placeholder' => '',
                'options' => '',
                'atts' => array()
                    ) );

            $custom_attr = '';
            if ( !empty( $field['atts'] ) ) {
                foreach ( $field['atts'] as $k => $val ) {
                    $custom_attr .= $k . '="' . $val . '"';
                }
            }
            switch ( $field['type'] ) {
                case 'section_start':
                    ?>
                    <?php if ( isset( $field['title'] ) ) : ?>
                        <h3><?php echo esc_html( $field['title'] ); ?></h3>
                        <?php if ( isset( $field['desc'] ) ) : ?>
                            <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                        <?php endif; ?>
                        <table class="form-table">
                        <?php endif; ?>
                        <?php
                        break;

                    case 'section_end':
                        ?>
                        <?php do_action( 'event_setting_field_' . $field['id'] . '_end' ); ?>
                    </table>
                    <?php do_action( 'event_setting_field_' . $field['id'] . '_after' ); ?>
                    <?php
                    break;

                case 'select':
                case 'multiselect':
                    $selected = event_get_option( $field['id'], isset( $field['default'] ) ? $field['default'] : array()  );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <?php if ( isset( $field['options'] ) ) : ?>
                                <select name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?><?php echo $field['type'] === 'multiselect' ? '[]' : '' ?>"
                                        id="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>"
                                        <?php echo ( $field['type'] === 'multiple' ) ? 'multiple="multiple"' : '' ?>
                                        >
                                            <?php foreach ( $field['options'] as $val => $text ) : ?>
                                        <option value="<?php echo esc_attr( $val ) ?>"
                                        <?php echo ( is_array( $selected ) && in_array( $val, $selected ) ) || $selected === $val ? ' selected' : '' ?>
                                                >
                                                    <?php echo esc_html( $text ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ( isset( $field['desc'] ) ) : ?>
                                    <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'text':
                case 'number':
                case 'email':
                case 'password':
                    $value = event_get_option( $field['id'] );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <input
                                type="<?php echo esc_attr( $field['type'] ) ?>"
                                name="<?php echo esc_attr( $field['id'] ) ?>"
                                value="<?php echo esc_attr( $value ) ?>"
                                class="regular-text"
                                placeholder="<?php echo esc_attr( $field['placeholder'] ) ?>"
                                <?php if ( $field['type'] === 'number' ) : ?>

                                    <?php echo isset( $field['min'] ) && is_numeric( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '' ?>
                                    <?php echo isset( $field['max'] ) && is_numeric( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '' ?>
                                    <?php echo isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : '' ?>

                                <?php endif; ?>
                                />
                                <?php if ( isset( $field['desc'] ) ) : ?>
                                <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'checkbox':
                    $val = event_get_option( $field['id'] );
                    ?>
                    <tr valign="top"<?php echo isset( $field['trclass'] ) ? ' class="' . implode( '', $field['trclass'] ) . '"' : '' ?>>
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <input type="hidden" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>" value="0"/>
                            <input type="checkbox" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>" value="1" <?php echo $custom_attr ?><?php checked( $val, $field['default'] ); ?>/>

                            <?php if ( isset( $field['desc'] ) ) : ?>
                                <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'radio':
                    $selected = event_get_option( $field['id'], isset( $field['default'] ) ? $field['default'] : ''  );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <?php if ( isset( $field['options'] ) ) : ?>
                                <?php foreach ( $field['options'] as $val => $text ) : ?>

                                    <label>
                                        <input type="radio" name="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>"<?php selected( $selected, $val ); ?>/>
                                        <?php echo esc_html( $text ) ?>
                                    </label>

                                <?php endforeach; ?>

                                <?php if ( isset( $field['desc'] ) ) : ?>
                                    <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'image_size':
                    $width = event_get_option( $field['id'] . '_width', isset( $field['default']['width'] ) ? $field['default']['width'] : 270  );
                    $height = event_get_option( $field['id'] . '_height', isset( $field['default']['height'] ) ? $field['default']['height'] : 270  );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <?php if ( isset( $field['id'] ) && isset( $field['options'] ) ) : ?>

                                <?php if ( isset( $field['options']['width'] ) ) : ?>
                                    <input
                                        type="number"
                                        name="<?php echo esc_attr( $field['id'] ) ?>_width"
                                        value="<?php echo esc_attr( $width ) ?>"
                                        /> x
                                    <?php endif; ?>
                                    <?php if ( isset( $field['options']['height'] ) ) : ?>
                                    <input
                                        type="number"
                                        name="<?php echo esc_attr( $field['id'] ) ?>_height"
                                        value="<?php echo esc_attr( $height ) ?>"
                                        /> px
                                    <?php endif; ?>

                                <?php if ( isset( $field['desc'] ) ) : ?>
                                    <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'textarea':
                    $content = event_get_option( $field['id'] );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <?php if ( isset( $field['id'] ) ) : ?>
                                <?php wp_editor( $content, $field['id'], isset( $field['options'] ) ? $field['options'] : array()  ); ?>

                                <?php if ( isset( $field['desc'] ) ) : ?>
                                    <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'select_page':
                    $selected = event_get_option( $field['id'], 0 );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <?php if ( isset( $field['title'] ) ) : ?>
                                <label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : '' ?>">
                                    <?php echo esc_html( $field['title'] ) ?>
                                </label>
                            <?php endif; ?>
                        </th>
                        <td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ) ?>">
                            <?php if ( isset( $field['id'] ) ) : ?>
                                <?php
                                wp_dropdown_pages(
                                        array(
                                            'show_option_none' => __( '---Select page---', 'tp-event' ),
                                            'option_none_value' => 0,
                                            'name' => $field['id'],
                                            'selected' => $selected
                                        )
                                );
                                ?>

                                <?php if ( isset( $field['desc'] ) ) : ?>
                                    <span class="description"><?php echo esc_html( $field['desc'] ) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                default:
                    do_action( 'event_setting_field_' . $field['id'], $field );
                    break;
            }
        }
    }

    /**
     * Save fields options
     * @param type $settings
     * @since 1.5
     */
    public static function save_fields( $settings = array() ) {
        foreach ( $settings as $setting ) {
            if ( isset( $setting['id'] ) && array_key_exists( $setting['id'], $_POST ) ) {
                update_option( $setting['id'], $_POST[$setting['id']] );
            }
        }
    }

    public static function register_setting() {
        register_setting( 'thimpress_events', 'thimpress_events' );
    }

}

Event_Admin_Settings::init();
