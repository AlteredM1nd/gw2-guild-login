<?php
// Exit if accessed directly or user lacks capability
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
?>
<div class="wrap gw2-admin-appearance-branding">
    <h1><?php esc_html_e('Appearance & Branding', 'gw2-guild-login'); ?></h1>
    <?php settings_errors(); ?>
    <form action="options.php" method="post">
        <?php
        settings_fields('gw2gl_settings_group');
        do_settings_sections('gw2-appearance-branding');
        submit_button(__('Save Appearance & Branding', 'gw2-guild-login'));
        ?>
    </form>
</div>
<!-- Field callbacks enqueue their own JS for color picker and media uploader -->
