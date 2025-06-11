<?php
/**
 * Login shortcode functionality.
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 *
 * @phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 */

namespace GW2GuildLogin;

/**
 * Handles the login form shortcode and related functionality.
 */
class GW2_Login_Shortcode {
	/**
	 * The single instance of the class
	 *
	 * @var GW2_Login_Shortcode
	 */
	protected static $instance = null;

	/**
	 * Main Instance
	 *
	 * @return GW2_Login_Shortcode
	 */
	public static function instance(): GW2_Login_Shortcode {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'gw2_login', array( $this, 'render_login_form' ) );
		add_shortcode( 'gw2_loginout', array( $this, 'render_loginout_link' ) );
		add_shortcode( 'gw2_guild_only', array( $this, 'render_guild_only_content' ) );

		// Handle form submission.
		add_action( 'init', array( $this, 'handle_login_submission' ) );

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Render the login form.
	 *
	 * @return string The rendered login form HTML.
	 */
	public function render_login_form(): string {
		// Don't show the form to logged-in users.
		if ( is_user_logged_in() ) {
			return $this->render_user_status();
		}

		$settings_mixed = get_option( 'gw2gl_settings', array() );
		$settings       = is_array( $settings_mixed ) ? $settings_mixed : array();
		$logo           = isset( $settings['appearance_logo'] ) && is_string( $settings['appearance_logo'] ) ? $settings['appearance_logo'] : '';
		$welcome        = isset( $settings['appearance_welcome_text'] ) && is_string( $settings['appearance_welcome_text'] ) ? $settings['appearance_welcome_text'] : '';

		ob_start();
		?>
		<div class="gw2-login-branding">
			<?php if ( $logo ) : ?>
				<div class="gw2-login-logo"><img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Site Logo', 'gw2-guild-login' ); ?>" class="gw2-admin-custom-logo" /></div>
			<?php endif; ?>
			<?php if ( $welcome ) : ?>
				<div class="gw2-login-welcome-text"><?php echo wp_kses_post( $welcome ); ?></div>
			<?php endif; ?>
		</div>
		<div class="gw2-login-form-container">
			<?php $this->display_messages(); ?>
			<form id="gw2-login-form" method="post" class="gw2-login-form">
				<?php wp_nonce_field( 'gw2_login_action', 'gw2_login_nonce' ); ?>
				
				<div class="form-group">
	<label for="gw2_api_key">
		<?php esc_html_e( 'GW2 API Key:', 'gw2-guild-login' ); ?>
	</label>
	<input type="password" 
			name="gw2_api_key" 
			id="gw2_api_key" 
			class="form-control" 
			required 
			aria-describedby="gw2_api_key_help"
			placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxxxxxxxxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
			autocomplete="off">
	<small id="gw2_api_key_help" class="form-text text-muted">
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %s: Link to API key generation */
				__( 'Requires "account" and "guilds" permissions. %s', 'gw2-guild-login' ),
				'<a href="https://account.arena.net/applications" target="_blank" rel="noopener noreferrer">' .
				esc_html__( 'Get an API key', 'gw2-guild-login' ) . '</a>'
			),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			)
		); // All params are strings and translation-safe.
		?>
	</small>
</div>
				
				<div class="form-group form-check">
					<input type="checkbox" 
							name="rememberme" 
							id="rememberme" 
							class="form-check-input" 
							value="forever">
					<label class="form-check-label" for="rememberme">
						<?php esc_html_e( 'Remember Me', 'gw2-guild-login' ); ?>
					</label>
				</div>
				
				<input type="hidden" 
						name="redirect_to" 
						value="<?php echo esc_url( (string) $this->get_redirect_url() ); // PHPStan: ensure string. ?>">
				
				<div class="form-submit">
	<button type="submit" 
			name="gw2_submit_login" 
			class="btn btn-primary">
		<?php esc_html_e( 'Login with GW2', 'gw2-guild-login' ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Submit Guild Wars 2 login form', 'gw2-guild-login' ); ?></span>
	</button>
</div>
				
				<?php if ( get_option( 'users_can_register' ) ) : ?>
				<div class="register-link mt-3">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: Registration URL */
							__( "Don't have an account? %s", 'gw2-guild-login' ),
							'<a href="' . esc_url( (string) wp_registration_url() ) . '">' .
							esc_html__( 'Register', 'gw2-guild-login' ) . '</a>'
						),
						array( 'a' => array( 'href' => array() ) )
					); // All params are strings and translation-safe.
					?>
				</div>
				<?php endif; ?>
			</form>
		</div>
		<?php
		$result = ob_get_clean();
		return is_string( $result ) ? $result : '';
	}

	/**
	 * Render the user status for logged-in users.
	 *
	 * @return string The rendered user status HTML.
	 */
	protected function render_user_status(): string {
		$current_user = wp_get_current_user();
		// $current_user is WP_User (guaranteed by WordPress)
		$user_id           = is_int( $current_user->ID ) ? $current_user->ID : 0;
		$gw2_account_mixed = get_user_meta( $user_id, 'gw2_account_name', true );
		// get_user_meta returns string for 'gw2_account_name' or ''.
		$gw2_account = is_string( $gw2_account_mixed ) ? $gw2_account_mixed : '';

		$settings_mixed = get_option( 'gw2gl_settings', array() );
		$settings       = is_array( $settings_mixed ) ? $settings_mixed : array();
		$logo           = isset( $settings['appearance_logo'] ) && is_string( $settings['appearance_logo'] ) ? $settings['appearance_logo'] : '';
		$welcome        = isset( $settings['appearance_welcome_text'] ) && is_string( $settings['appearance_welcome_text'] ) ? $settings['appearance_welcome_text'] : ''; // PHPStan: always string.

		ob_start();
		?>
		<div class="gw2-login-branding">
			<?php if ( $logo ) : ?>
				<div class="gw2-login-logo"><img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Site Logo', 'gw2-guild-login' ); ?>" class="gw2-admin-custom-logo" /></div>
			<?php endif; ?>
			<?php if ( $welcome ) : ?>
				<div class="gw2-login-welcome-text"><?php echo wp_kses_post( $welcome ); ?></div>
			<?php endif; ?>
		</div>
		<div class="gw2-login-status">
			<p class="mb-2">
				<?php
				$display_name = isset( $current_user->display_name ) && is_string( $current_user->display_name ) ? $current_user->display_name : '';
				echo esc_html(
					sprintf(
					/* translators: 1: Display name, 2: GW2 account name */
						__( 'Logged in as %1$s (GW2: %2$s)', 'gw2-guild-login' ),
						$display_name,
						'' !== $gw2_account ? $gw2_account : __( 'No GW2 account linked', 'gw2-guild-login' )
					)
				);
				?>
			</p>
			<p class="mb-0">
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" 
					class="btn btn-secondary btn-sm">
					<?php esc_html_e( 'Logout', 'gw2-guild-login' ); ?>
				</a>
			</p>
		</div>
		<?php
		$result = ob_get_clean();
		return is_string( $result ) ? $result : '';
	}

	/**
	 * Display any messages to the user.
	 */
	protected function display_messages(): void {
		if ( isset( $_SESSION['gw2_login_message'] ) && is_string( $_SESSION['gw2_login_message'] ) && '' !== $_SESSION['gw2_login_message'] ) {
			$message_type = ( isset( $_SESSION['gw2_login_message_type'] ) && is_string( $_SESSION['gw2_login_message_type'] ) && '' !== $_SESSION['gw2_login_message_type'] )
				? sanitize_key( $_SESSION['gw2_login_message_type'] )
				: 'info';

			$allowed_html = array(
				'div'    => array(
					'class'     => array(),
					'role'      => array(),
					'aria-live' => array(),
				),
				'p'      => array(),
				'button' => array(
					'type'         => array(),
					'class'        => array(),
					'data-dismiss' => array(),
					'aria-label'   => array(),
				),
				'span'   => array(
					'aria-hidden' => array(),
					'class'       => array(),
				),
				'strong' => array(),
			);

			// Choose icon and color class based on message type.
			$icon_class  = 'dashicons-info';
			$color_class = 'gw2-msg-info';
			if ( 'error' === $message_type ) {
				$icon_class  = 'dashicons-warning';
				$color_class = 'gw2-msg-error';
			} elseif ( 'success' === $message_type ) {
				$icon_class  = 'dashicons-yes';
				$color_class = 'gw2-msg-success';
			}

			printf(
				'<div class="gw2-login-message alert alert-%1$s %4$s mb-3" aria-live="polite" role="status">
                    <span class="dashicons %2$s" aria-hidden="true"></span>
                    <span class="gw2-message-text">%3$s</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="%5$s"></button>
                </div>',
				esc_attr( $message_type ),
				esc_attr( $icon_class ),
				wp_kses( ( is_string( $_SESSION['gw2_login_message'] ) ? $_SESSION['gw2_login_message'] : '' ), 'post' ),
				esc_attr( $color_class ),
				esc_attr__( 'Close', 'gw2-guild-login' )
			);

			// Clear the message after displaying it.
			unset( $_SESSION['gw2_login_message'] );
			unset( $_SESSION['gw2_login_message_type'] );
		}
	}

	/**
	 * Get the redirect URL after login
	 *
	 * @return string
	 */
	protected function get_redirect_url(): string {
		$redirect_to = isset( $_GET['redirect_to'] ) && is_string( $_GET['redirect_to'] ) ? sanitize_url( wp_unslash( $_GET['redirect_to'] ) ) : '';
		return $redirect_to;
	}

	/**
	 * Handle the login form submission.
	 */
	public function handle_login_submission(): void {
		// Only process form submission.
		if ( ! isset( $_POST['gw2_submit_login'] ) ) {
			return;
		}

		// Verify nonce.
		$nonce = isset( $_POST['gw2_login_nonce'] ) && is_string( $_POST['gw2_login_nonce'] ) ? sanitize_key( wp_unslash( $_POST['gw2_login_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'gw2_login_action' ) ) {
			$this->set_message(
				__( 'Security verification failed. Please try again.', 'gw2-guild-login' ),
				'error'
			);
			return;
		}

		// Get API key.
		$api_key = ( isset( $_POST['gw2_api_key'] ) && is_string( $_POST['gw2_api_key'] ) ) ? sanitize_text_field( wp_unslash( $_POST['gw2_api_key'] ) ) : ''; // PHPStan: always string.
		if ( empty( $api_key ) ) {
			$this->set_message(
				__( 'Please enter your GW2 API key.', 'gw2-guild-login' ),
				'error'
			);
			return;
		}

		// Process login.
		$gw2_login = \GW2_Guild_Login();
		// Sanitize rememberme value (even though used as boolean, for clarity and future-proofing).
		$rememberme = ( isset( $_POST['rememberme'] ) && is_string( $_POST['rememberme'] ) ) ? sanitize_text_field( wp_unslash( $_POST['rememberme'] ) ) : ''; // PHPStan: always string.
		$handler    = $gw2_login->get_user_handler();
		if ( $handler instanceof \GW2_User_Handler ) {
			$result = $handler->process_login(
				$api_key,
				! empty( $rememberme )
			);
		} else {
			$this->set_message(
				__( 'User handler not available.', 'gw2-guild-login' ),
				'error'
			);
			return;
		}

		// Handle the result.
		if ( is_wp_error( $result ) ) {
			$this->set_message( $result->get_error_message(), 'error' );
			return;
		}

		// Login successful.
		$redirect_url = ( isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) && '' !== $_POST['redirect_to'] )
			? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
			: home_url( '/' );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Set a message to be displayed to the user.
	 *
	 * @param string $message The message text.
	 * @param string $type The message type.
	 */
	public function set_message( string $message, string $type = 'info' ): void {
		if ( ! session_id() ) {
			session_start();
		}

		$_SESSION['gw2_login_message']      = $message;
		$_SESSION['gw2_login_message_type'] = $type;
	}

	/**
	 * Render login/logout link shortcode
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_loginout_link( array $atts ): string {
		// $atts is always array due to type hint
		$atts = shortcode_atts(
			array(
				'login_text'  => __( 'Login', 'gw2-guild-login' ),
				'logout_text' => __( 'Logout', 'gw2-guild-login' ),
				'redirect'    => '',
				'class'       => '',
			),
			$atts,
			'gw2_loginout'
		);

		if ( is_user_logged_in() ) {
			$url  = wp_logout_url( $atts['redirect'] );
			$text = $atts['logout_text'];
		} else {
			$url  = wp_login_url( $atts['redirect'] );
			$text = $atts['login_text'];
		}

		return sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $atts['class'] ),
			esc_html( $text )
		);
	}

	/**
	 * Render content only for guild members.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @param string|null          $content The content to protect.
	 * @return string
	 */
	public function render_guild_only_content( array $atts, ?string $content = null ): string {
		if ( ! is_user_logged_in() ) {
			return $this->get_restricted_content_message( 'login_required' );
		}
		$options_mixed   = get_option( 'gw2gl_settings', array() );
		$options         = is_array( $options_mixed ) ? $options_mixed : array();
		$target_guild_id = isset( $options['target_guild_id'] ) && is_string( $options['target_guild_id'] ) ? $options['target_guild_id'] : '';
		// If no guild is set, show content to all logged-in users.
		if ( '' === $target_guild_id ) {
			return do_shortcode( $content ?? '' );
		}
		// Check if user is in the guild.
		$user_id         = get_current_user_id();
		$is_member_mixed = get_user_meta( $user_id, 'gw2_guild_member', true );
		$is_member       = is_string( $is_member_mixed ) ? $is_member_mixed : '';
		if ( '' !== $is_member ) {
			return do_shortcode( $content ?? '' );
		}
		return $this->get_restricted_content_message( 'guild_required' );
	}

	/**
	 * Get restricted content message.
	 *
	 * @param string $type The restriction type.
	 * @return string
	 */
	protected function get_restricted_content_message( string $type = 'guild_required' ): string {
		$options_mixed = get_option( 'gw2gl_settings', array() );
		$options       = is_array( $options_mixed ) ? $options_mixed : array();
		$message       = '';

		if ( 'login_required' === $type ) {
			$login_url = wp_login_url( get_permalink() ? get_permalink() : '' );
			$message   = isset( $options['login_required_message'] ) && is_string( $options['login_required_message'] )
				? $options['login_required_message']
				: __( 'You must be logged in to view this content.', 'gw2-guild-login' );

			if ( get_option( 'users_can_register' ) ) {
				$register_url = wp_registration_url();
				$message     .= ' <a href="' . esc_url( $login_url ) . '">' . __( 'Login', 'gw2-guild-login' ) . '</a>';
				$message     .= ' ' . __( 'or', 'gw2-guild-login' ) . ' ';
				$message     .= '<a href="' . esc_url( $register_url ) . '">' . __( 'Register', 'gw2-guild-login' ) . '</a>'; // PHPStan: message always string.
			} else {
				$message .= ' <a href="' . esc_url( $login_url ) . '">' . __( 'Login', 'gw2-guild-login' ) . '</a>';
			}
		} else {
			$message = isset( $options['guild_required_message'] ) && is_string( $options['guild_required_message'] )
				? $options['guild_required_message']
				: __( 'You must be a member of the guild to view this content.', 'gw2-guild-login' );
		}

		return '<div class="gw2-restricted-content">' . wp_kses_post( wpautop( $message ) ) . '</div>';
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_assets(): void {
		// Only load on pages with the shortcode.
		global $post;
		if ( ! isset( $post ) || ! is_a( $post, 'WP_Post' ) || ! is_string( $post->post_content ) || ! has_shortcode( $post->post_content, 'gw2_login' ) ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'gw2-login-styles',
			plugins_url( 'assets/css/gw2-login.css', \GW2_GUILD_LOGIN_FILE ),
			array(),
			\GW2_GUILD_LOGIN_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'gw2-login-script',
			plugins_url( 'assets/js/gw2-login.js', \GW2_GUILD_LOGIN_FILE ),
			array( 'jquery' ),
			\GW2_GUILD_LOGIN_VERSION,
			true
		);

		// Localize script with settings.
		wp_localize_script(
			'gw2-login-script',
			'gw2LoginVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gw2-login-nonce' ),
				'i18n'    => array(
					'invalid_api_key' => __( 'Please enter a valid API key', 'gw2-guild-login' ),
				),
			)
		);
	}
}

// Initialize the shortcode handler.
\GW2GuildLogin\GW2_Login_Shortcode::instance();

