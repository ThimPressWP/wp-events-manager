<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

?>

<?php if ( $tabs ): ?>
    
<div class="wrap">

    <form method="POST" name="tp_event_options" action="">
         <!--        Tabs        -->
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $tabs as $key => $title ): ?>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tp-event-setting&tab=' . $key ) ); ?>" class="nav-tab<?php echo $current_tab === $key ? ' nav-tab-active' : '' ?>" data-tab="<?php echo esc_attr( $key ) ?>">
                    <?php printf( '%s', $title ) ?>
                </a>

            <?php endforeach; ?>
        </h2>

        <!-- 	Content 	-->
        <div class="tp_event_wrapper_content">
            <?php
                    do_action( 'event_admin_setting_sections_' . $current_tab );
                    /**
                     * Display message updated || error
                     */
                    self::show_messages();

                    do_action( 'event_admin_setting_' . $current_tab );
            ?>

        </div>

        <p class="submit">
            <?php wp_nonce_field( 'tp-event-settings', 'tp-event-settings-nonce' ); ?>
            <input name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'tp-event' ); ?>" />
        </p>

    </form>
</div>
<?php endif; ?>
