<?php
/**
 * GW2_Password_Reset
 *
 * Implements magic-link based password reset for GW2 Guild Login plugin.
 *
 * @package GW2_Guild_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GW2_Password_Reset {
	const RESET_META_KEY        = 'gw2gl_password_reset_token';
	const RESET_EXPIRY_META_KEY = 'gw2gl_password_reset_expiry';
	const RESET_LINK_LIFETIME   = 3600; // 1 hour

	public static function init() {
		add_action( 'wp_ajax_nopriv_gw2gl_request_password_reset', array( __CLASS__, 'handle_request_reset' ) );
		add_action( 'wp_ajax_nopriv_gw2gl_redeem_magic_link', array( __CLASS__, 'handle_redeem_magic_link' ) );
		add_shortcode( 'gw2gl_password_reset', array( __CLASS__, 'render_reset_form' ) );
	}

	/**
	 * Handle password reset request. Generates a magic link and emails it to the user.
	 */
	public static function handle_request_reset() {
		check_ajax_referer( 'gw2gl_password_reset_nonce', 'nonce' );
		$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		if ( ! is_string( $email ) || empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'gw2-guild-login' ) );
		}
		$user = get_user_by( 'email', $email );
		if ( ! is_object( $user ) || ! isset( $user->ID ) || ! is_int( $user->ID ) ) {
			wp_send_json_success( __( 'If your account exists, a reset link has been sent.', 'gw2-guild-login' ) );
		}
		$user_id = is_object( $user ) && isset( $user->ID ) && is_int( $user->ID ) ? $user->ID : 0;
		$token   = wp_generate_password( 32, true, true );
		$expiry  = time() + self::RESET_LINK_LIFETIME;
		update_user_meta( $user_id, self::RESET_META_KEY, $token );
		update_user_meta( $user_id, self::RESET_EXPIRY_META_KEY, $expiry );
		$magic_link = add_query_arg(
			array(
				'gw2gl_reset' => 1,
				'uid'         => $user_id,
				'token'       => $token,
			),
			site_url( '/' )
		);
		wp_mail(
			$email,
			__( 'Your GW2 Guild Login Password Reset Link', 'gw2-guild-login' ),
			sprintf( __( 'Click to reset your login: %s\n\nThis link is valid for 1 hour and can only be used once.', 'gw2-guild-login' ), $magic_link )
		);
		wp_send_json_success( __( 'If your account exists, a reset link has been sent.', 'gw2-guild-login' ) );
	}

	/**
	 * Handle redeeming the magic link.
	 */
	public static function handle_redeem_magic_link() {
		$user_id = isset( $_POST['uid'] ) && is_numeric( $_POST['uid'] ) ? intval( $_POST['uid'] ) : 0;
		$token   = isset( $_POST['token'] ) && is_string( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		if ( ! is_int( $user_id ) || $user_id <= 0 || ! is_string( $token ) || empty( $token ) ) {
			wp_send_json_error( __( 'Invalid reset link.', 'gw2-guild-login' ) );
		}
		$saved_token_mixed = get_user_meta( $user_id, self::RESET_META_KEY, true );
		$saved_token       = is_string( $saved_token_mixed ) ? $saved_token_mixed : '';
		$expiry_mixed      = get_user_meta( $user_id, self::RESET_EXPIRY_META_KEY, true );
		$expiry            = is_numeric( $expiry_mixed ) ? (int) $expiry_mixed : 0;
		if ( ! is_string( $saved_token ) || $token !== $saved_token || time() > $expiry ) {
			wp_send_json_error( __( 'This reset link is invalid or expired.', 'gw2-guild-login' ) );
		}
		// Invalidate token
		delete_user_meta( $user_id, self::RESET_META_KEY );
		delete_user_meta( $user_id, self::RESET_EXPIRY_META_KEY );
		// Log in the user
		wp_set_auth_cookie( $user_id, true );
		wp_send_json_success( __( 'You have been logged in. You may now set a new API key.', 'gw2-guild-login' ) );
	}

	/**
	 * Render password reset form via shortcode.
	 */
	public static function render_reset_form( $atts = array() ) {
		ob_start();
		?>
		<form id="gw2gl-password-reset-form">
			<input type="email" name="email" placeholder="<?php esc_attr_e( 'Your email address', 'gw2-guild-login' ); ?>" required />
			<button type="submit"><?php esc_html_e( 'Send Magic Link', 'gw2-guild-login' ); ?></button>
			<div id="gw2gl-reset-message"></div>
		</form>
		<script>
		jQuery(function($){
			$('#gw2gl-password-reset-form').on('submit', function(e){
				e.preventDefault();
				var email = $(this).find('[name=email]').val();
				var nonce = '<?php echo wp_create_nonce( 'gw2gl_password_reset_nonce' ); ?>';
				$.post(ajaxurl, {
					action: 'gw2gl_request_password_reset',
					email: email,
					nonce: nonce
				}, function(res){
					$('#gw2gl-reset-message').text(res.data);
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}
}

GW2_Password_Reset::init();
