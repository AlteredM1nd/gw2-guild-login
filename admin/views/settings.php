<div class="wrap gw2-admin-settings">
    <h1>GW2 Guild Settings</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('gw2gl_settings_group');
        do_settings_sections('gw2-guild-login');
        submit_button();
        ?>
    </form>
</div>
