<?php
declare(strict_types=1);
if (!current_user_can('manage_options')) {
    return;
}

// Render the rank-to-role mapping interface
GW2_Guild_Ranks::instance()->render_settings_page();
