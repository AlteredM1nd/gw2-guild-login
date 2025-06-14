<?php
/**
 * Template Name: Guild Members Only
 * Template Post Type: page
 *
 * A custom page template that restricts access to guild members only.
 * Non-guild members will be redirected to the login page or home page.
 *
 * @package GW2_Guild_Login
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the main plugin class.
$gw2_plugin = GW2_Guild_Login();

// Check if user is logged in and a guild member.
$is_guild_member = false;
$wp_current_user = wp_get_current_user();
// Guard for PHPStan: ensure user ID is always int.
/** @phpstan-ignore-next-line */
$current_user_id = (int) ( is_object( $wp_current_user ) ? $wp_current_user->ID : 0 );

if ( $current_user_id > 0 ) {
	try {
		// Get the user handler instance.
		/** @phpstan-ignore-next-line */
		$user_handler = is_object( $gw2_plugin ) && method_exists( $gw2_plugin, 'get_user_handler' ) ? $gw2_plugin->get_user_handler() : null;

		if ( $user_handler && method_exists( $user_handler, 'current_user_is_guild_member' ) ) {
			// Check if user is a guild member using the user handler.
			$membership_check = $user_handler->current_user_is_guild_member();

			// Handle the result.
			if ( is_wp_error( $membership_check ) ) {
				// Log the error but continue with access denied.

				$is_guild_member = false;
			} else {
				$is_guild_member = (bool) $membership_check;
			}
		} else {
			// No user handler available.
			$is_guild_member = false;
		}
	} catch ( Exception $e ) {
		// Error occurred during membership check.
		$is_guild_member = false;
	}
}

// If not a guild member, handle the redirect.
if ( ! $is_guild_member ) {
	// Store the current URL for redirect after login.
	$redirect_to_url             = get_permalink() ? get_permalink() : '';
	$_SESSION['gw2_redirect_to'] = $redirect_to_url;

	// Get the login page URL or use home URL as fallback.
	/** @phpstan-ignore-next-line */
	$login_page_id  = (int) get_option( 'gw2_guild_login_page', 0 );
	$login_page_url = 0 < $login_page_id ? get_permalink( $login_page_id ) : '';
	$redirect_url   = '' !== $login_page_url ? $login_page_url : home_url();

	// Message for non-members.
	$message = is_user_logged_in()
		? __( 'You need to be a member of the guild to view this page.', 'gw2-guild-login' )
		: __( 'Please log in with your GW2 API key to view this page.', 'gw2-guild-login' );

	$_SESSION['gw2_login_message'] = $message;
	$_SESSION['gw2_message_type']  = 'error';

	// Redirect.
	if ( is_string( $redirect_url ) && '' !== $redirect_url ) {
		wp_safe_redirect( $redirect_url );
	}
	exit;
}

// If we get here, the user is a guild member - proceed with normal page load.
get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php
		// Start the loop for the page content.
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'page' );

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
		endwhile; // End of the loop.
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
