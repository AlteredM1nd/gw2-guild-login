<?php
declare(strict_types=1);
// Deprecated: rank mapping moved to User Management.
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'The Rank Access page is deprecated. Please use User Management → Add New for rank mapping.', 'gw2-guild-login' ) . '</p></div>';
