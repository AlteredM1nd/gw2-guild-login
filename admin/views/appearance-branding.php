<?php
declare(strict_types=1);
if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission
settings_errors('appearance_messages');
if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['appearance_nonce']) && wp_verify_nonce($_POST['appearance_nonce'], 'gw2_appearance')) {
    $settings = get_option('gw2gl_settings', []);
    $settings['appearance_primary_color'] = sanitize_hex_color($_POST['appearance_primary_color'] ?? '') ?: '#1976d2';
    $settings['appearance_accent_color'] = sanitize_hex_color($_POST['appearance_accent_color'] ?? '') ?: '#26c6da';
    $settings['appearance_logo'] = esc_url_raw($_POST['appearance_logo'] ?? '');
    $settings['appearance_welcome_text'] = sanitize_textarea_field($_POST['appearance_welcome_text'] ?? '');
    $settings['appearance_force_dark'] = isset($_POST['appearance_force_dark']) ? 1 : 0;
    update_option('gw2gl_settings', $settings);
    add_settings_error('appearance_messages', 'saved', __('Appearance settings saved.', 'gw2-guild-login'), 'updated');
}

// Fetch current values
$settings = get_option('gw2gl_settings', []);
$primary = $settings['appearance_primary_color'] ?? '#1976d2';
$accent = $settings['appearance_accent_color'] ?? '#26c6da';
$logo = $settings['appearance_logo'] ?? '';
$welcome = $settings['appearance_welcome_text'] ?? '';
$force_dark = !empty($settings['appearance_force_dark']);
?>
<div class="wrap gw2-admin-appearance-branding">
    <h1><?php esc_html_e('Appearance & Branding', 'gw2-guild-login'); ?></h1>
    <?php settings_errors('appearance_messages'); ?>
    <form method="post">
        <?php wp_nonce_field('gw2_appearance', 'appearance_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="appearance_primary_color"><?php esc_html_e('Primary Color', 'gw2-guild-login'); ?></label></th>
                <td>
                    <input type="text" id="appearance_primary_color" name="appearance_primary_color" value="<?php echo esc_attr($primary); ?>" class="gw2-color-field" />
                </td>
            </tr>
            <tr>
                <th><label for="appearance_accent_color"><?php esc_html_e('Accent Color', 'gw2-guild-login'); ?></label></th>
                <td>
                    <input type="text" id="appearance_accent_color" name="appearance_accent_color" value="<?php echo esc_attr($accent); ?>" class="gw2-color-field" />
                </td>
            </tr>
            <tr>
                <th><label for="appearance_logo"><?php esc_html_e('Custom Logo URL', 'gw2-guild-login'); ?></label></th>
                <td>
                    <input type="text" id="appearance_logo" name="appearance_logo" value="<?php echo esc_attr($logo); ?>" class="regular-text" />
                    <button class="button" id="upload_logo_button"><?php esc_html_e('Upload Logo', 'gw2-guild-login'); ?></button>
                    <?php if ($logo) : ?>
                        <p><img src="<?php echo esc_url($logo); ?>" style="max-width:150px;height:auto;" /></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="appearance_welcome_text"><?php esc_html_e('Welcome Text', 'gw2-guild-login'); ?></label></th>
                <td>
                    <textarea id="appearance_welcome_text" name="appearance_welcome_text" rows="4" cols="50"><?php echo esc_textarea($welcome); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Force Dark Mode', 'gw2-guild-login'); ?></th>
                <td>
                    <label><input type="checkbox" name="appearance_force_dark" value="1" <?php checked($force_dark); ?> /> <?php esc_html_e('Enable dark theme for all users', 'gw2-guild-login'); ?></label>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Save Appearance Settings', 'gw2-guild-login')); ?>
    </form>
</div>
<script>
jQuery(function($){
    $('.gw2-color-field').wpColorPicker();
    $('#upload_logo_button').on('click', function(e){
        e.preventDefault();
        var frame = wp.media({title: '<?php echo esc_js(__('Select Logo','gw2-guild-login')); ?>', button:{ text:'<?php echo esc_js(__('Use this logo','gw2-guild-login')); ?>'}, multiple:false});
        frame.on('select', function(){ var attachment=frame.state().get('selection').first().toJSON(); $('#appearance_logo').val(attachment.url); });
        frame.open();
    });
});
</script>
