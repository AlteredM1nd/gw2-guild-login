<?php
declare(strict_types=1);
// Use unified settings storage via Settings API
?>

<div class="wrap gw2-admin-rank-access">
    <h1>Guild Rank Access</h1>
    
    <?php settings_errors('gw2_messages'); ?>
    
    <div class="gw2-admin-cards">
        <div class="gw2-admin-card gw2-admin-card-wide">
            <h2>Guild Configuration</h2>
            <div class="gw2-admin-card-content">
                <form method="post" action="options.php">
    <?php
    settings_fields('gw2gl_settings_group');
    do_settings_sections('gw2-guild-login');
    submit_button('Save Settings');
    ?>
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
