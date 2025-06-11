<?php
/**
 * Rank Access admin view for GW2 Guild Login plugin.
 *
 * This page is deprecated; please use User Management → Add New for rank mapping.
 *
 * @package GW2_Guild_Login
 */

declare(strict_types=1);

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'The Rank Access page is deprecated. Please use User Management → Add New for rank mapping.', 'gw2-guild-login' ) . '</p></div>';
