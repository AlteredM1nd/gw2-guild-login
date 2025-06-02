<?php
declare(strict_types=1);
// Retrieve and ensure string types for settings
$guild_id_raw = get_option('gw2_guild_id', '');
$guild_id = is_string($guild_id_raw) ? $guild_id_raw : '';
$api_key_raw = get_option('gw2_api_key', '');
$api_key = is_string($api_key_raw) ? $api_key_raw : '';
?>

<div class="wrap gw2-admin-rank-access">
    <h1>Guild Rank Access</h1>
    
    <?php settings_errors('gw2_messages'); ?>
    
    <div class="gw2-admin-cards">
        <div class="gw2-admin-card gw2-admin-card-wide">
            <h2>Guild Configuration</h2>
            <div class="gw2-admin-card-content">
                <form method="post">
                    <?php wp_nonce_field('gw2_guild_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="gw2_guild_id">Guild ID</label></th>
                            <td>
                                <input type="text" id="gw2_guild_id" name="gw2_guild_id" 
                                       value="<?php echo esc_attr($guild_id); ?>" class="regular-text">
                                <p class="description">Your guild's UUID (found in guild panel URL)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="gw2_api_key">API Key</label></th>
                            <td>
                                <input type="password" id="gw2_api_key" name="gw2_api_key" 
                                       value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                                <p class="description">GW2 API key with 'guild' permission</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Save Settings'); ?>
                </form>
            </div>
        </div>
        
        <div class="gw2-admin-card">
            <h2>Shortcode Usage</h2>
            <div class="gw2-admin-card-content">
                <p>Restrict content to specific guild ranks:</p>
                <pre><code>[gw2_restricted rank="Officer"]
This content is only visible to officers.
[/gw2_restricted]</code></pre>
                
                <p>With custom message:</p>
                <pre><code>[gw2_restricted rank="Member" message="Members only!"]
This content is visible to all members.
[/gw2_restricted]</code></pre>
            </div>
        </div>
    </div>
</div>
