<?php
/**
 * Two-Factor Authentication login functionality.
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

declare(strict_types=1);

use GW2GuildLogin\GW2_2FA_Handler;

/**
 * Handles 2FA during the login process.
 */
class GW2_2FA_Login {
	/**
	 * @var GW2_2FA_Handler
	 */
	private $handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->handler = GW2_2FA_Handler::instance();

		// Add 2FA form to login page.
		add_action( 'login_form', array( $this, 'add_2fa_field' ) );

		// Verify 2FA code during authentication.
		add_filter( 'authenticate', array( $this, 'verify_2fa' ), 30, 3 );

		// Handle 2FA verification form submission.
		add_action( 'login_form_2fa_verify', array( $this, 'handle_2fa_verification' ) );

		// Enqueue login page scripts.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_scripts' ) );
	}

	/**
	 * Add 2FA field to login form.
	 */
	public function add_2fa_field(): void {
		// Only show if user exists and 2FA is enabled.
		$user = wp_get_current_user();
		if ( method_exists( $user, 'exists' ) && $user->exists() && is_int( $user->ID ) && $this->handler->is_2fa_enabled( $user->ID ) ) {
			?>
			<p>
				<label for="gw2-2fa-code"><?php _e( 'Authentication Code', 'gw2-guild-login' ); ?><br>
				<input type="text" name="gw2_2fa_code" id="gw2-2fa-code" class="input" value="" size="20" autocomplete="off" autofocus>
				</label>
			</p>
			<p class="gw2-2fa-actions">
				<a href="#" id="gw2-use-backup-code"><?php _e( 'Use a backup code', 'gw2-guild-login' ); ?></a>
			</p>
			<?php
		}
	}

	/**
	 * Verify 2FA code during authentication.
	 *
	 * @param WP_User|WP_Error $user The user object or WP_Error.
	 * @param string           $username The username.
	 * @param string           $password The password.
	 * @return WP_User|WP_Error
	 */
	public function verify_2fa( \WP_User|\WP_Error $user, string $username, string $password ): \WP_User|\WP_Error {
		// Don't interfere with other authentication methods.
		// $user is WP_User or WP_Error. Only allow WP_User with valid ID.
		if ( ! ( $user instanceof \WP_User ) || ! $user->exists() || ! is_int( $user->ID ) || 0 >= $user->ID ) {
			return $user;
		}

		// Check if 2FA is enabled for this user.
		if ( ! $this->handler->is_2fa_enabled( $user->ID ) ) {
			return $user;
		}

		// Verify the 2FA code.
		$code = isset( $_POST['gw2_2fa_code'] ) && is_string( $_POST['gw2_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['gw2_2fa_code'] ) ) : '';
		if ( '' === $code ) {
			return new WP_Error(
				'2fa_required',
				__( '<strong>Error</strong>: Two-factor authentication code is required.', 'gw2-guild-login' )
			);
		}

		// Get the user's 2FA secret.
		global $wpdb;
		/** @var \wpdb $wpdb */
		if ( ! $wpdb instanceof \wpdb ) {
			return new WP_Error(
				'2fa_error',
				__( '<strong>Error</strong>: Database connection not available.', 'gw2-guild-login' )
			);
		}

		/** @phpstan-ignore-next-line */
		$secret_row = $wpdb->get_row(
			/** @phpstan-ignore-next-line */
			$wpdb->prepare(
				'SELECT secret FROM ' . $wpdb->prefix . 'gw2_2fa_secrets WHERE user_id = %d',
				$user->ID
			)
		);
		$secret_val = ( is_object( $secret_row ) && isset( $secret_row->secret ) && is_string( $secret_row->secret ) ) ? $secret_row->secret : '';
		if ( '' === $secret_val ) {
			return new WP_Error(
				'2fa_error',
				__( '<strong>Error</strong>: Two-factor authentication is not properly configured for your account.', 'gw2-guild-login' )
			);
		}

		$secret = $this->handler->decrypt_secret( $secret_val );
		if ( ! is_string( $secret ) || '' === $secret ) {
			return new WP_Error(
				'2fa_error',
				__( '<strong>Error</strong>: Two-factor authentication is not properly configured for your account.', 'gw2-guild-login' )
			);
		}

		// Verify the code.
		if ( ! $this->handler->verify_totp( $secret, $code ) ) {
			// Check backup codes.
			$backup_codes = $this->handler->get_backup_codes_for_user( $user->ID );
			if ( ! is_array( $backup_codes ) || empty( $backup_codes ) || ! in_array( $code, $backup_codes, true ) ) {
				return new WP_Error(
					'2fa_invalid_code',
					__( '<strong>Error</strong>: Invalid authentication code.', 'gw2-guild-login' )
				);
			}

			$backup_codes = array_values( array_diff( $backup_codes, array( $code ) ) );
			$this->handler->set_backup_codes_for_user( $user->ID, $backup_codes );
			if ( empty( $backup_codes ) ) {
				$new_codes = $this->handler->generate_backup_codes();
				if ( empty( $new_codes ) ) {
					return new WP_Error(
						'2fa_error',
						__( '<strong>Error</strong>: Unable to generate new backup codes.', 'gw2-guild-login' )
					);
				}
				$this->handler->enable_2fa( $user->ID, $secret, $new_codes );
				$this->send_backup_codes_email( $user, $new_codes );
			}
		}

		return $user;
	}

	/**
	 * Handle 2FA verification form submission.
	 */
	public function handle_2fa_verification(): void {
		// Nonce verification for 2FA form.
		$nonce_raw = filter_input( INPUT_POST, '_2fa_nonce', FILTER_SANITIZE_SPECIAL_CHARS );
		$nonce     = is_string( $nonce_raw ) ? sanitize_key( $nonce_raw ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, '2fa_verify' ) ) {
			$error = new WP_Error(
				'2fa_nonce_invalid',
				__( '<strong>Error</strong>: Security verification failed. Please try again.', 'gw2-guild-login' )
			);
			// Log error and redirect instead of returning.
			wp_safe_redirect( wp_login_url() . '?error=2fa_nonce_invalid' );
			exit;
		}

		if ( empty( $_POST['log'] ) || empty( $_POST['pwd'] ) ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		$user = wp_authenticate_username_password(
			null,
			isset( $_POST['log'] ) && is_string( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
			isset( $_POST['pwd'] ) && is_string( $_POST['pwd'] ) ? sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) : ''
		);

		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( add_query_arg( 'login', 'failed', wp_login_url() ) );
			exit;
		}

		// If 2FA is not enabled, log the user in.
		if ( ! $this->handler->is_2fa_enabled( $user->ID ) ) {
			wp_set_auth_cookie( $user->ID, isset( $_POST['rememberme'] ) && ! empty( $_POST['rememberme'] ) );

			$redirect_to_raw = isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url();
			$redirect_to     = apply_filters(
				'login_redirect',
				$redirect_to_raw,
				$redirect_to_raw,
				$user
			);

			$redirect_url = is_string( $redirect_to ) ? $redirect_to : admin_url();
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Show 2FA verification form.
		$this->show_2fa_form( $user );
		exit;
	}

	/**
	 * Display the 2FA verification form.
	 *
	 * @param WP_User $user The user object.
	 */
	private function show_2fa_form( WP_User $user ): void {
		// Load the login header with empty error message.
		login_header(
			__( 'Two-Factor Authentication', 'gw2-guild-login' ),
			'',
			null // No error message by default.
		);
		?>
		<form name="2faform" id="2faform" action="<?php echo esc_url( site_url( 'wp-login.php?action=2fa_verify', 'login_post' ) ); ?>" method="post" autocomplete="off">
			<p><?php _e( 'Please enter the verification code from your authenticator app.', 'gw2-guild-login' ); ?></p>
			
			<p>
				<label for="gw2-2fa-code"><?php _e( 'Verification Code', 'gw2-guild-login' ); ?><br>
				<input type="text" name="gw2_2fa_code" id="gw2-2fa-code" class="input" value="" size="20" autocomplete="off" autofocus>
				</label>
			</p>
			
			<p class="gw2-2fa-actions">
				<a href="#" id="gw2-use-backup-code"><?php _e( 'Use a backup code', 'gw2-guild-login' ); ?></a>
			</p>
			
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Verify', 'gw2-guild-login' ); ?>">
				<input type="hidden" name="log" value="<?php echo esc_attr( isset( $user->user_login ) && is_string( $user->user_login ) ? $user->user_login : '' ); ?>">
				<input type="hidden" name="pwd" value="<?php echo esc_attr( isset( $_POST['pwd'] ) && is_string( $_POST['pwd'] ) ? sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) : '' ); ?>">
				<input type="hidden" name="rememberme" value="<?php echo ( isset( $_POST['rememberme'] ) && ! empty( $_POST['rememberme'] ) ) ? '1' : '0'; ?>">
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '' ); ?>">
				<input type="hidden" name="testcookie" value="1">
				<?php wp_nonce_field( '2fa_verify', '_2fa_nonce' ); ?>
			</p>
		</form>
		
		<p id="backtoblog">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php _e( '← Go to site', 'gw2-guild-login' ); ?>
			</a>
		</p>
		
		<script type="text/javascript">
		document.getElementById('gw2-use-backup-code').addEventListener('click', function(e) {
			e.preventDefault();
			var codeField = document.getElementById('gw2-2fa-code');
			codeField.placeholder = '<?php echo esc_js( __( 'Enter backup code', 'gw2-guild-login' ) ); ?>';
			codeField.focus();
			this.parentNode.removeChild(this);
		});
		document.getElementById('gw2-2fa-code').focus();
		</script>
		<?php
		login_footer();
	}

	/**
	 * Send backup codes to user's email.
	 *
	 * @param \WP_User           $user The user object.
	 * @param array<int, string> $codes The backup codes array.
	 * @return bool
	 */
	private function send_backup_codes_email( \WP_User $user, array $codes ): bool {
		// Ensure $codes is an array of integers.
		$codes = array_map( 'intval', $codes );

		$blog_name_raw = get_option( 'blogname' );
		$blog_name     = is_string( $blog_name_raw ) ? wp_specialchars_decode( $blog_name_raw, ENT_QUOTES ) : 'WordPress Site';
		$blog_name_str = is_string( $blog_name ) ? $blog_name : 'WordPress Site';
		$subject       = sprintf( __( '[%s] New Backup Codes', 'gw2-guild-login' ), $blog_name_str );

		$display_name = is_string( $user->display_name ) ? $user->display_name : 'User';
		$message      = sprintf( __( 'Hello %s,', 'gw2-guild-login' ), $display_name ) . "\r\n\r\n";
		$message     .= __( 'You have used your last backup code for two-factor authentication. Here are your new backup codes:', 'gw2-guild-login' ) . "\r\n\r\n";

		foreach ( $codes as $code ) {
			$message .= $code . "\r\n";
		}

		$message .= "\r\n" . __( 'Each code can only be used once. Save these codes in a safe place.', 'gw2-guild-login' ) . "\r\n\r\n";
		$message .= sprintf( __( 'If you did not request new backup codes, please secure your account immediately by visiting %s', 'gw2-guild-login' ), admin_url( 'profile.php' ) ) . "\r\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Enqueue login page scripts.
	 */
	public function enqueue_login_scripts(): void {
		wp_enqueue_style(
			'gw2-2fa-login',
			plugins_url( 'assets/css/gw2-2fa-login.css', __DIR__ ),
			array(),
			GW2_GUILD_LOGIN_VERSION
		);

		wp_enqueue_script(
			'gw2-2fa-login',
			plugins_url( 'assets/js/gw2-2fa-login.js', __DIR__ ),
			array( 'jquery' ),
			GW2_GUILD_LOGIN_VERSION,
			true
		);
	}
}
