<?php
/**
 * Settings admin view for GW2 Guild Login plugin.
 *
 * Provides the settings page for configuring the plugin.
 *
 * @package GW2_Guild_Login
 */

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
?>
<div class="wrap gw2-admin-settings">
	<h1><?php esc_html_e( 'GW2 Guild Settings', 'gw2-guild-login' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'gw2gl_settings_group' );
		do_settings_sections( 'gw2-guild-login' );
		submit_button();
		?>
	</form>
</div>
