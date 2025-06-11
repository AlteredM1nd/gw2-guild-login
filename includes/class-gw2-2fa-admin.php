<?php
/**
 * Two-Factor Authentication admin functionality.
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

declare(strict_types=1);

use GW2GuildLogin\GW2_2FA_Handler;

/**
 * GW2_2FA_Admin
 *
 * Handles the admin UI for Two-Factor Authentication (2FA) in the GW2 Guild Login plugin.
 * Responsible for rendering user profile sections, saving 2FA settings, enqueuing admin scripts, and handling AJAX actions related to 2FA.
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */
class GW2_2FA_Admin {
	/**
	 * @var GW2_2FA_Handler
	 */
	private $handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->handler = GW2_2FA_Handler::instance();

		// Add AJAX handler for regenerating backup codes.
		add_action( 'wp_ajax_gw2_regenerate_backup_codes', array( $this, 'ajax_regenerate_backup_codes' ) );

		// Add 2FA section to user profile.
		add_action( 'show_user_profile', array( $this, 'add_2fa_profile_section' ) );
		add_action( 'edit_user_profile', array( $this, 'add_2fa_profile_section' ) );

		// Handle 2FA form submission.
		add_action( 'personal_options_update', array( $this, 'save_2fa_settings' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_2fa_settings' ) );
		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add 2FA section to user profile.
	 *
	 * @param WP_User $user The user object.
	 * @return void
	 */
	public function add_2fa_profile_section( WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		$is_enabled   = $this->handler->is_2fa_enabled( $user->ID );
		$backup_codes = $this->handler->get_backup_codes_for_user( $user->ID );
		// Generate a new secret if 2FA is being set up.
		$secret      = '';
		$qr_code_url = '';
		$show_setup  = false;
		if ( isset( $_GET['setup-2fa'] ) && ! $is_enabled ) {
			$secret      = $this->handler->generate_secret();
			$qr_code_url = $this->handler->get_qr_code_url( $secret, isset( $user->user_login ) && is_string( $user->user_login ) ? $user->user_login : '' ); // $qr_code_url will be escaped on output.
			$show_setup  = true;
		}
		?>
		<h2><?php esc_html_e( 'Two-Factor Authentication', 'gw2-guild-login' ); ?></h2>
		<?php wp_nonce_field( 'gw2_2fa_profile_action', 'gw2_2fa_profile_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Status', 'gw2-guild-login' ); ?></th>
				<td>
					<?php if ( $is_enabled ) : ?>
						<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
						<?php esc_html_e( 'Two-factor authentication is enabled.', 'gw2-guild-login' ); ?>
						<p class="description">
							<a href="#" id="gw2-show-backup-codes">
								<?php esc_html_e( 'View backup codes', 'gw2-guild-login' ); ?>
							</a>
						</p>
						<div id="gw2-backup-codes" style="display: none; margin-top: 10px;">
							<p><?php esc_html_e( 'Save these backup codes in a safe place. Each code can be used only once.', 'gw2-guild-login' ); ?></p>
							<ul style="font-family: monospace; font-size: 14px; line-height: 1.8;">
						<?php foreach ( $backup_codes as $code ) : ?>
		<li><?php echo esc_html( $code ); ?></li>
	<?php endforeach; ?>
</ul>
							<p>
								<button type="button" class="button button-secondary" id="gw2-regenerate-codes">
									<?php esc_html_e( 'Generate New Codes', 'gw2-guild-login' ); ?>
								</button>
							</p>
						</div>
						<p>
							<button type="submit" name="disable_2fa" class="button button-secondary">
								<?php esc_html_e( 'Disable Two-Factor Authentication', 'gw2-guild-login' ); ?>
							</button>
						</p>
					<?php else : ?>
						<span class="dashicons dashicons-no" style="color: #dc3232;"></span>
						<?php _e( 'Two-factor authentication is not enabled.', 'gw2-guild-login' ); ?>
						<?php if ( ! $show_setup ) : ?>
							<p>
								<a href="?setup-2fa=1" class="button button-primary">
									<?php _e( 'Set Up Two-Factor Authentication', 'gw2-guild-login' ); ?>
								</a>
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			
			<?php if ( $show_setup ) : ?>
				<tr>
					<th><?php esc_html_e( 'Scan QR Code', 'gw2-guild-login' ); ?></th>
					<td>
						<div style="background: white; padding: 20px; border: 1px solid #ddd; display: inline-block;">
							<img src="<?php echo esc_url( $qr_code_url ); ?>" alt="<?php _e( 'QR Code', 'gw2-guild-login' ); ?>" id="gw2-qr-code">
						</div>
						<p class="description"><?php _e( 'Scan this QR code with your authenticator app.', 'gw2-guild-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="gw2-verification-code"><?php _e( 'Verification Code', 'gw2-guild-login' ); ?></label></th>
					<td>
						<input type="text" name="2fa_code" id="gw2-2fa-code" class="regular-text" autocomplete="off" maxlength="6" pattern="\d{6}" placeholder="<?php esc_attr_e( 'Enter the 6-digit code', 'gw2-guild-login' ); ?>">
						<input type="hidden" name="gw2_2fa_secret" value="<?php echo esc_attr( $secret ); ?>">
						<p>
							<button type="submit" name="enable_2fa" class="button button-primary">
								<?php _e( 'Verify and Enable', 'gw2-guild-login' ); ?>
							</button>
							<a href="" class="button button-secondary">
								<?php _e( 'Cancel', 'gw2-guild-login' ); ?>
							</a>
						</p>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Save 2FA settings
	 *
	 * @param int $user_id
	 */ /**
		 * Save 2FA settings.
		 *
		 * @param int $user_id The user ID.
		 * @return void
		 */
	public function save_2fa_settings( int $user_id ): void {
		if ( 0 >= $user_id ) {
			return;
		}
		$nonce = isset( $_POST['gw2_2fa_profile_nonce'] ) && is_string( $_POST['gw2_2fa_profile_nonce'] ) ? sanitize_key( wp_unslash( $_POST['gw2_2fa_profile_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'gw2_2fa_profile_action' ) ) {
			return;
		}
		// Handle enabling 2FA.
		if ( isset( $_POST['enable_2fa'] ) && isset( $_POST['2fa_secret'] ) && isset( $_POST['2fa_code'] ) ) {
			$secret = isset( $_POST['2fa_secret'] ) && is_string( $_POST['2fa_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['2fa_secret'] ) ) : '';
			$code   = isset( $_POST['2fa_code'] ) && is_string( $_POST['2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['2fa_code'] ) ) : '';
			if ( '' === $secret || '' === $code ) {
				add_action(
					'user_profile_update_errors',
					function ( $errors ) {
						$errors->add( '2fa_error', __( 'Invalid verification code.', 'gw2-guild-login' ) );
					}
				);
				return;
			}
			if ( $this->handler->verify_totp( $secret, $code ) ) {
				$backup_codes = $this->handler->generate_backup_codes();
				$result       = $this->handler->enable_2fa( $user_id, $secret, $backup_codes );
				if ( is_wp_error( $result ) ) {
					add_action(
						'user_profile_update_errors',
						function ( $errors ) use ( $result ) {
							$errors->add( '2fa_error', $result->get_error_message() );
						}
					);
				} else {
					add_action(
						'admin_notices',
						function () {
							echo '<div class="notice notice-success"><p>' .
							__( 'Two-factor authentication has been enabled.', 'gw2-guild-login' ) .
							'</p></div>';
						}
					);
				}
			} else {
				add_action(
					'user_profile_update_errors',
					function ( $errors ) {
						$errors->add( '2fa_error', __( 'Invalid verification code.', 'gw2-guild-login' ) );
					}
				);
			}
		} elseif ( isset( $_POST['disable_2fa'] ) ) {
			$result = $this->handler->disable_2fa( $user_id );
			if ( is_wp_error( $result ) ) {
				add_action(
					'user_profile_update_errors',
					function ( $errors ) use ( $result ) {
						$errors->add( '2fa_error', $result->get_error_message() );
					}
				);
			} else {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-success"><p>' .
						__( 'Two-factor authentication has been disabled.', 'gw2-guild-login' ) .
						'</p></div>';
					}
				);
			}
		}
	}
	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		$current_user_id = get_current_user_id();
		if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'gw2-2fa-admin',
			plugins_url( 'assets/css/gw2-2fa-admin.css', __DIR__ ),
			array(),
			GW2_GUILD_LOGIN_VERSION
		);

		wp_enqueue_script(
			'gw2-2fa-admin',
			plugins_url( 'assets/js/gw2-2fa-admin.js', __DIR__ ),
			array( 'jquery' ),
			GW2_GUILD_LOGIN_VERSION,
			true
		);

		wp_localize_script(
			'gw2-2fa-admin',
			'gw22fa',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gw2_2fa_nonce' ),
				'user_id'  => $current_user_id,
				'i18n'     => array(
					'generating'         => __( 'Generating new codes...', 'gw2-guild-login' ),
					'error'              => __( 'An error occurred. Please try again.', 'gw2-guild-login' ),
					'confirm_regenerate' => __( 'Are you sure you want to generate new backup codes? Old codes will be invalidated.', 'gw2-guild-login' ),
					'codes_regenerated'  => __( 'New backup codes have been generated.', 'gw2-guild-login' ),
				),
			)
		);
	}
	/**
	 * AJAX handler for regenerating backup codes.
	 *
	 * @return void
	 */
	public function ajax_regenerate_backup_codes(): void {
		// Check nonce.
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'gw2_2fa_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'gw2-guild-login' ) ), 403 );
		}
		$user_id_raw = isset( $_POST['user_id'] ) && is_string( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '0';
		$user_id     = is_numeric( $user_id_raw ) ? intval( $user_id_raw ) : 0;
		if ( 0 >= $user_id || get_current_user_id() !== $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'gw2-guild-login' ) ), 403 );
		}
		// Generate and set new backup codes.
		$codes  = $this->handler->generate_backup_codes();
		$result = $this->handler->set_backup_codes_for_user( $user_id, $codes );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'codes' => $codes ) );
	}
}
