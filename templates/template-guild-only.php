<?php
/**
 * Template Name: Guild Members Only
 * Template Post Type: page
 * 
 * A custom page template that restricts access to guild members only.
 * Non-guild members will be redirected to the login page or home page.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the main plugin class
$gw2_plugin = GW2_Guild_Login();

// Check if user is logged in and a guild member
$is_guild_member = false;
$current_user = wp_get_current_user();

if ($current_user->ID > 0) {
    try {
        // Get the user handler instance
        $user_handler = $gw2_plugin->get_user_handler();
        
        if ($user_handler && method_exists($user_handler, 'current_user_is_guild_member')) {
            // Check if user is a guild member using the user handler
            $membership_check = $user_handler->current_user_is_guild_member();
            
            // Handle the result
            if (is_wp_error($membership_check)) {
                // Log the error but continue with access denied
                error_log('GW2 Guild Login: Error checking guild membership - ' . $membership_check->get_error_message());
                $is_guild_member = false;
            } else {
                $is_guild_member = (bool) $membership_check;
            }
        } else {
            error_log('GW2 Guild Login: User handler not available or missing required method');
        }
    } catch (Exception $e) {
        error_log('GW2 Guild Login: Exception while checking guild membership - ' . $e->getMessage());
    }
}

// If not a guild member, handle the redirect
if (!$is_guild_member) {
    // Store the current URL for redirect after login
    if (!isset($_GET['redirect_to'])) {
        $_SESSION['gw2_redirect_to'] = get_permalink();
    }
    
    // Get the login page URL or use home URL as fallback
    $login_page = get_permalink(get_option('gw2_guild_login_page'));
    $redirect_url = $login_page ? $login_page : home_url();
    
    // Add a message for the user
    if (!is_user_logged_in()) {
        $message = 'Please log in with your GW2 API key to view this page.';
    } else {
        $message = 'You need to be a member of the guild to view this page.';
    }
    
    // Set the message in session
    $_SESSION['gw2_login_message'] = $message;
    $_SESSION['gw2_message_type'] = 'error';
    
    // Redirect
    wp_redirect($redirect_url);
    exit;
}

// If we get here, the user is a guild member - proceed with normal page load
get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        // Start the loop for the page content
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', 'page');
            
            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
        endwhile; // End of the loop.
        ?>
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
