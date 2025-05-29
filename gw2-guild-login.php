<?php
/**
 * Plugin Name:       GW2 Guild Login
 * Plugin URI:        https://yourwebsite.com/
 * Description:       Allows users to log in using their GW2 API key to verify guild membership.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gw2-guild-login
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define your target guild ID
define('TARGET_GUILD_ID', 'YOUR_ACTUAL_GUILD_ID_HERE'); // IMPORTANT: Replace this!

// Start session if not already started (needed for storing login state)
if ( ! session_id() ) {
    session_start();
}

// --- Plugin code will go here ---
function gw2_login_form_shortcode() {
    ob_start();

    // Display messages (errors or success)
    if (isset($_SESSION['gw2_login_message'])) {
        echo '<p class="gw2-login-message">' . esc_html($_SESSION['gw2_login_message']) . '</p>';
        unset($_SESSION['gw2_login_message']); // Clear message after display
    }

    // If already logged in, show logout option or a message
    if (isset($_SESSION['gw2_guild_member']) && $_SESSION['gw2_guild_member'] === true) {
        echo '<p>You are logged in as a guild member.</p>';
        echo '<form method="post">';
        wp_nonce_field('gw2_logout_action', 'gw2_logout_nonce');
        echo '<input type="submit" name="gw2_logout" value="Logout">';
        echo '</form>';
        return ob_get_clean();
    }

    ?>
    <form id="gw2-login-form" method="post">
        <p>
            <label for="gw2_api_key">GW2 API Key:</label><br>
            <input type="text" name="gw2_api_key" id="gw2_api_key" style="width: 100%; max-width: 400px;" required>
            <small>Requires 'account' and 'guilds' permissions. Create one at <a href="https://account.arena.net/applications" target="_blank" rel="noopener noreferrer">account.arena.net</a>.</small>
        </p>
        <?php wp_nonce_field('gw2_login_action', 'gw2_login_nonce'); ?>
        <p>
            <input type="submit" name="gw2_submit_login" value="Login">
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('gw2_login', 'gw2_login_form_shortcode');

// Simple CSS for messages (optional, add to your theme's CSS or plugin's CSS file)
function gw2_login_styles() {
    echo '<style>
        .gw2-login-message { border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; }
        .gw2-login-message.error { border-color: red; color: red; }
        .gw2-login-message.success { border-color: green; color: green; }
    </style>';
}
add_action('wp_head', 'gw2_login_styles');

function gw2_handle_login_submission() {
    // Handle Logout
    if (isset($_POST['gw2_logout']) && isset($_POST['gw2_logout_nonce'])) {
        if (wp_verify_nonce($_POST['gw2_logout_nonce'], 'gw2_logout_action')) {
            unset($_SESSION['gw2_guild_member']);
            unset($_SESSION['gw2_account_name']);
            $_SESSION['gw2_login_message'] = 'You have been logged out.';
            // Redirect to the same page to prevent form resubmission issues
            wp_redirect(wp_get_referer() ? wp_get_referer() : home_url());
            exit;
        } else {
            $_SESSION['gw2_login_message'] = 'Security check failed. Could not log out.';
        }
        return;
    }

    // Handle Login
    if (isset($_POST['gw2_submit_login']) && isset($_POST['gw2_api_key']) && isset($_POST['gw2_login_nonce'])) {
        if (!wp_verify_nonce($_POST['gw2_login_nonce'], 'gw2_login_action')) {
            $_SESSION['gw2_login_message'] = 'Security check failed. Please try again.';
            return;
        }

        $api_key = sanitize_text_field($_POST['gw2_api_key']);

        if (empty($api_key)) {
            $_SESSION['gw2_login_message'] = 'API Key cannot be empty.';
            return;
        }

        // 1. Validate API Key and Permissions
        $tokeninfo_url = 'https://api.guildwars2.com/v2/tokeninfo?access_token=' . urlencode($api_key);
        $token_response = wp_remote_get($tokeninfo_url);

        if (is_wp_error($token_response)) {
            $_SESSION['gw2_login_message'] = 'Error contacting GW2 API (tokeninfo): ' . $token_response->get_error_message();
            return;
        }

        $token_body = wp_remote_retrieve_body($token_response);
        $token_data = json_decode($token_body, true);

        if (wp_remote_retrieve_response_code($token_response) !== 200 || !$token_data) {
            $_SESSION['gw2_login_message'] = 'Invalid API Key or API error (tokeninfo). Code: '.wp_remote_retrieve_response_code($token_response);
            return;
        }

        $required_permissions = ['account', 'guilds'];
        $missing_permissions = array_diff($required_permissions, $token_data['permissions']);

        if (!empty($missing_permissions)) {
            $_SESSION['gw2_login_message'] = 'API Key is missing required permissions: ' . implode(', ', $missing_permissions) . '. Please ensure your key has "account" and "guilds" enabled.';
            return;
        }

        // 2. Fetch Account Guilds
        $account_url = 'https://api.guildwars2.com/v2/account?access_token=' . urlencode($api_key);
        $account_response = wp_remote_get($account_url);

        if (is_wp_error($account_response)) {
            $_SESSION['gw2_login_message'] = 'Error contacting GW2 API (account): ' . $account_response->get_error_message();
            return;
        }

        $account_body = wp_remote_retrieve_body($account_response);
        $account_data = json_decode($account_body, true);

        if (wp_remote_retrieve_response_code($account_response) !== 200 || !$account_data) {
            $_SESSION['gw2_login_message'] = 'Could not fetch account details from GW2 API. Code: '.wp_remote_retrieve_response_code($account_response);
            return;
        }
        
        $account_name = isset($account_data['name']) ? $account_data['name'] : 'Unknown Account';

        // 3. Check for Guild Membership
        if (isset($account_data['guilds']) && is_array($account_data['guilds'])) {
            if (in_array(TARGET_GUILD_ID, $account_data['guilds'])) {
                $_SESSION['gw2_guild_member'] = true;
                $_SESSION['gw2_account_name'] = $account_name; // Store account name if you want to display it
                $_SESSION['gw2_login_message'] = 'Login successful! Welcome, ' . esc_html($account_name) . '.';
                // Redirect to the same page or a members-only page
                 wp_redirect(wp_get_referer() ? wp_get_referer() : home_url()); // Or use a specific URL
                 exit;
            } else {
                $_SESSION['gw2_login_message'] = 'You are not a member of the required guild.';
            }
        } else {
            $_SESSION['gw2_login_message'] = 'Could not retrieve guild information from your account.';
        }
    }
}
add_action('init', 'gw2_handle_login_submission'); // 'init' or 'wp_loaded' are good hooks

function gw2_guild_content_shortcode($atts, $content = null) {
    if (isset($_SESSION['gw2_guild_member']) && $_SESSION['gw2_guild_member'] === true) {
        // If you want to personalize, you can use the stored account name:
        // $greeting = 'Hello ' . esc_html($_SESSION['gw2_account_name']) . '!<br>';
        // return $greeting . do_shortcode($content); // Process nested shortcodes
        return do_shortcode($content);
    } else {
        // Optional: Message for non-members or just return empty
        return '<p>You must be logged in as a guild member to view this content. Please use the login form.</p>';
        // return ''; // Or return nothing
    }
}
add_shortcode('gw2_guild_only', 'gw2_guild_content_shortcode');