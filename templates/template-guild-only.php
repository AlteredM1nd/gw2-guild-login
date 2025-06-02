<?php
/**
 * Template Name: Guild Members Only
 * Template Post Type: page
 *
 * A custom page template that restricts access to guild members only.
 * Non-guild members will be redirected to the login page or home page.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the main plugin class.
$gw2_plugin = GW2_Guild_Login();

// Check if user is logged in and a guild member.
$is_guild_member = false;
$current_user = wp_get_current_user();
/** @phpstan-ignore-next-line */
$current_user_id = (is_object($current_user) && isset($current_user->ID) && is_int($current_user->ID)) ? $current_user->ID : 0;

if ( $current_user_id > 0 ) {
	try {
		// Get the user handler instance.
		$user_handler = is_object($gw2_plugin) && method_exists($gw2_plugin, 'get_user_handler') ? $gw2_plugin->get_user_handler() : null;

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
			
		}
	} catch ( Exception $e ) {
		
	}
}

// If not a guild member, handle the redirect.
if ( ! $is_guild_member ) {
	// Store the current URL for redirect after login.
	$redirect_to_url_mixed = get_permalink();
	$redirect_to_url = is_string($redirect_to_url_mixed) ? $redirect_to_url_mixed : '';
	if ( ! isset( $_GET['redirect_to'] ) && $redirect_to_url !== '' ) {
		$_SESSION['gw2_redirect_to'] = $redirect_to_url;
	}

	// Get the login page URL or use home URL as fallback.
	$login_page_id_mixed = get_option( 'gw2_guild_login_page' );
	$login_page_id = is_int($login_page_id_mixed) ? $login_page_id_mixed : (is_string($login_page_id_mixed) && ctype_digit($login_page_id_mixed) ? (int)$login_page_id_mixed : 0);
	$login_page_url_mixed = $login_page_id > 0 ? get_permalink( $login_page_id ) : '';
	$login_page_url = is_string($login_page_url_mixed) ? $login_page_url_mixed : '';
	$redirect_url = $login_page_url !== '' ? $login_page_url : home_url();

	// Add a message for the user.
	if ( ! is_user_logged_in() ) {
		$message = 'Please log in with your GW2 API key to view this page.';
	} else {
		$message = 'You need to be a member of the guild to view this page.';
	}

	// Set the message in session.
	$_SESSION['gw2_login_message'] = is_string($message) ? $message : '';
	$_SESSION['gw2_message_type']  = 'error';

	// Redirect.
	if ( is_string($redirect_url) && $redirect_url !== '' ) {
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
