# GW2 Guild Login

A secure WordPress plugin that allows users to log in using their Guild Wars 2 API key, with guild membership verification, user role management, and enhanced security features.

## Features

### Core Features
- **Two-Factor Authentication (2FA)**: [Basic TOTP-based 2FA](docs/TWO_FACTOR_AUTH.md) for enhanced security
  - Works with any TOTP-compatible authenticator app (Google Authenticator, Authy, etc.)
  - Generate and verify backup codes for account recovery
  - Basic trusted device support via cookies (30-day remember me)
  - Simple setup interface (full wizard coming in a future update)
  - Basic admin controls for enabling/disabling 2FA per user
  - Secure encrypted storage of 2FA secrets
- **Guild Rank Access Control**: Restrict content based on guild ranks
  - Simple shortcode implementation for any post or page
  - Configure guild ID and API key in WordPress admin
  - Caching system to minimize API calls to Guild Wars 2 servers
  - Customizable access denied messages
  - Supports all guild ranks defined in your guild
- **Secure GW2 API Integration**: Authenticate users using their Guild Wars 2 API keys with proper validation
- **Guild Membership Verification**: Restrict access to users who are members of specific guilds
- **User Role Management**: Assign WordPress user roles based on guild membership and rank
- **User Dashboard**: Comprehensive dashboard showing account details, guild memberships, and active sessions
- **Session Management**: View and manage active login sessions with the ability to revoke access
- **Auto-Registration**: Automatically create WordPress user accounts for new GW2 players
- **Secure Session Management**: Custom session handler for enhanced security
- **Rate Limiting**: Built-in protection against brute force attacks

### Security Features
- **Two-Factor Authentication**: Optional TOTP-based 2FA for enhanced account security
- **API Key Encryption**: All API keys are encrypted before storage
- **Secure Session Handling**: Custom session management with proper security headers
- **CSRF Protection**: Nonce verification for all form submissions
- **Input Validation**: Comprehensive validation of all user inputs with proper sanitization
- **Type Safety**: Strict type checking for all user-related operations
- **Error Handling**: Secure error handling without exposing sensitive information
- **Secure Cookies**: HTTP-only and secure flags set for all cookies
- **Parameter Validation**: Enhanced validation for all function parameters
- **Return Type Safety**: Ensured proper return types for all methods

### User Experience
- **2FA Setup Wizard**: Step-by-step guide for enabling two-factor authentication
- **Backup Code Management**: Easy access to view and regenerate backup codes
- **Trusted Devices**: Option to remember devices to reduce 2FA prompts
- **User Dashboard**: Centralized location to view account details, guild memberships, and active sessions
- **Session Management**: Easily view and manage active login sessions from different devices
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **AJAX Form Submission**: Smooth form handling without page reloads
- **Customizable Messages**: Tailor feedback messages to your users
- **Remember Me**: Option to keep users logged in
- **Redirect After Login**: Customizable redirect URLs after successful login

### Shortcodes
- `[gw2_login]` - Display the GW2 login form with optional 2FA support
- `[gw2_loginout]` - Show login/logout links
- `[gw2_guild_only]` - Protect content for guild members only

### Dashboard Features
- **Account Overview**: View your GW2 account details at a glance
- **Guild Memberships**: See all guilds you belong to with rank information
- **Active Sessions**: Monitor and manage all active login sessions
- **Security Controls**: Revoke suspicious sessions with a single click
- **Responsive Design**: Works on desktop and mobile devices

### Developer Friendly
- **Action & Filter Hooks**: Extend functionality with custom code
- **WP-CLI Commands**: Manage plugin via command line
- **Comprehensive Logging**: Debug issues with detailed logs
- **Multisite Support**: Works in WordPress Multisite installations

## Admin Interface

The plugin features a comprehensive admin interface accessible from the main WordPress admin menu:

### Dashboard
- Overview of guild status and recent activity
- Quick access to important features
- System status and configuration checks

### Guild Settings
- Configure your Guild ID and API key
- Set default user roles and permissions
- Manage global plugin settings

### Rank Access
- Configure guild rank-based access control
- View and manage rank restrictions
- Shortcode generator for easy implementation

### User Management
- View and manage guild members
- Monitor user activity and sessions
- Manage user roles and permissions

## Guild Rank Access

Restrict content based on guild ranks using simple shortcodes:

```
[gw2_restricted rank="Officer"]
This content is only visible to guild officers.
[/gw2_restricted]

[gw2_restricted rank="Member" message="Members only! Join our guild to see this content."]
This content is visible to all guild members.
[/gw2_restricted]
```

### Configuration
1. Go to GW2 Guild → Rank Access in your WordPress admin
2. Enter your Guild ID and API key with `guilds` permission
3. The plugin will automatically fetch and cache your guild's rank structure

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- A Guild Wars 2 account with API key generation access

## Getting Started

### Prerequisites

Before installing the plugin, you'll need:

1. A self-hosted WordPress installation (version 5.6 or higher)
2. A Guild Wars 2 account with an active API key
3. The guild ID of the guild you want to verify membership against

### Finding Your Guild ID

To find your Guild ID, you can use one of these methods:

1. **Using the Guild Search API (easiest method):**
   - Visit this URL in your browser (replace `GUILD_NAME` with your guild's exact name, with spaces as `%20`):
     ```
     https://api.guildwars2.com/v2/guild/search?name=GUILD_NAME
     ```
   - Example for guild "Lunar Melodies":
     ```
     https://api.guildwars2.com/v2/guild/search?name=Lunar%20Melodies
     ```
   - This will return your guild's ID in the format: `["GUILD_ID_HERE"]`

2. **Using Your Account's Guilds (requires API key):**
   - Log in to your Guild Wars 2 account at [account.arena.net](https://account.arena.net/)
   - Create an API key with the `guilds` permission
   - Visit this URL in your browser (replace `YOUR_API_KEY` with your actual API key):
     ```
     https://api.guildwars2.com/v2/account/guilds?access_token=YOUR_API_KEY
     ```
   - This will return a list of guild IDs your account has access to

## Installation

### Download the Plugin

1. Visit the [GW2 Guild Login GitHub repository](https://github.com/AlteredM1nd/gw2-guild-login)
2. Click the green "Code" button
3. Select "Download ZIP"
4. Save the file to your computer

### Method 1: WordPress Admin Panel

1. Log in to your WordPress admin panel
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Select the new plugin ZIP file and click **Install Now**
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Go to Settings > GW2 Guild Login to configure the plugin

### Updating the Plugin

To update the plugin to the latest version:

1. Download the latest version using the steps above
2. In your WordPress admin, go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Select the new plugin ZIP file and click **Install Now**
5. WordPress will automatically detect it's an update and prompt you to replace the current version
6. Click **Replace current with uploaded**

**Note**: Your plugin settings will be preserved during the update as they are stored in the WordPress database.

## Configuration

### Initial Setup

1. After activation, navigate to **Settings > GW2 Guild Login** in your WordPress admin panel
2. You'll see the main configuration page with the following sections:

### Guild Settings

- **Target Guild ID**
  - Enter the Guild ID you found earlier
  - This is the guild that users must be a member of to log in
  - Leave empty to allow any GW2 player to register

### User Registration

- **Default User Role**
  - Select the default WordPress role for new users
  - Recommended: 'Subscriber' for most sites

- **Auto-Create Accounts**
  - Enable to automatically create WordPress accounts for new GW2 players
  - If disabled, users must be manually created first

### Security Settings

- **Session Management**
  - Session Lifetime: Set how long login sessions last (in seconds)
  - Regenerate Session ID: Enable to regenerate session ID on login
  - Secure Cookies: Enable to only send cookies over HTTPS

- **Rate Limiting**
  - Login Attempts: Maximum number of failed login attempts before blocking
  - Lockout Time: How long to block login attempts after reaching the limit

### Appearance

- **Login Form**
  - Custom CSS: Add custom styles for the login form
  - Show Remember Me: Enable/disable the "Remember Me" checkbox
  - Redirect After Login: Set a custom redirect URL after successful login

### Advanced

- **API Settings**
  - API Cache TTL: How long to cache API responses (in seconds)
  - Debug Mode: Enable for detailed error logging (only for development)

- **Maintenance**
  - Clear Cache: Clear all cached API responses
  - Reset Settings: Reset all settings to defaults

### Testing Your Setup

1. Log out of WordPress
2. Visit a page with the `[gw2_login]` shortcode
3. Try logging in with your GW2 API key
4. If auto-registration is enabled, a new WordPress account will be created
5. Verify that you can access protected content with the `[gw2_guild_only]` shortcode

### Troubleshooting Common Issues

- **"Invalid API Key" error**
  - Double-check that you've copied the entire API key
  - Ensure the API key has the required permissions (`account` and `guilds`)
  - Try generating a new API key

- **"Not a member of the required guild" error**
  - Verify the Guild ID in your settings matches your guild's ID exactly
  - Ensure your API key has the `guilds` permission
  - Check that your character is still a member of the guild in-game

- **Login form not appearing**
  - Make sure you're logged out of WordPress
  - Check for JavaScript errors in your browser's console
  - Ensure your theme's `wp_footer()` function is called in the footer.php file

## Complete Usage Guide

### Guild Members Only Template

This template completely restricts access to a page, showing it only to guild members. Non-members will be redirected to the login page.

**How to use:**

1. In the WordPress admin, go to **Pages > Add New**
2. Add a title and content for your page
3. In the Page Attributes box (usually on the right side), find the **Template** dropdown
4. Select **Guild Members Only** from the dropdown
5. Publish or Update the page

**Features:**
- Completely blocks access to non-guild members
- Automatically redirects to the login page if not logged in
- Shows an error message if the user is logged in but not a guild member
- Maintains the original URL for clean navigation

### Shortcodes

### Shortcode vs. Page Template

| Feature | Shortcode | Page Template |
|---------|-----------|---------------|
| Shows page to non-members | Yes | No |
| Shows page to logged-out users | Yes | No |
| Redirects non-members | No | Yes |
| Good for public pages with protected sections | ✓ |  |
| Good for completely private pages |  | ✓ |

#### 1. Basic Login Form

The most common way to use the plugin is with the login form shortcode:

```
[gw2_login]
```

This will display a simple login form where users can enter their GW2 API key.

#### 2. Customizing the Login Form

You can customize the login form with additional attributes:

```
[gw2_login 
    redirect="/members-only/"
    label="GW2 API Key"
    button_text="Connect with GW2"
    remember="true"
]
```

- `redirect`: URL to redirect to after successful login (default: current page)
- `label`: Custom label for the API key field
- `button_text`: Custom text for the submit button
- `remember`: Whether to show "Remember Me" checkbox (true/false)

#### 3. Protecting Content

Restrict content to logged-in guild members:

```
[gw2_guild_only]
    <h2>Welcome, Guild Member!</h2>
    <p>This content is only visible to members of our guild.</p>
    <p>You can include any HTML content here.</p>
[/gw2_guild_only]
```

#### 4. Showing Different Content to Members and Guests

```
[gw2_guild_only]
    <p>Welcome back, guild member!</p>
[gw2_guild_else]
    <p>Please log in with your GW2 API key to view this content.</p>
    [gw2_login]
[/gw2_guild_only]
```

#### 5. Login/Logout Links

Add a dynamic login/logout link:

```
[gw2_loginout]
```

Customize the text:

```
[gw2_loginout login_text="Sign In" logout_text="Sign Out"]
```

### Advanced Usage

#### Custom Redirects After Login

Set a default redirect URL in the plugin settings, or use the `redirect` parameter:

```
[gw2_login redirect="/members-area/"]
```

#### Styling the Login Form

Add this CSS to your theme's `style.css` or Customizer:

```css
/* Container */
.gw2-login-form {
    max-width: 400px;
    margin: 2em auto;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Input fields */
.gw2-login-form input[type="text"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

/* Submit button */
.gw2-login-form .button {
    background: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

/* Error messages */
.gw2-login-error {
    color: #d32f2f;
    margin: 10px 0;
    padding: 10px;
    background: #ffebee;
    border-left: 4px solid #d32f2f;
}

/* Success messages */
.gw2-login-success {
    color: #388e3c;
    margin: 10px 0;
    padding: 10px;
    background: #e8f5e9;
    border-left: 4px solid #388e3c;
}
```

### Managing Users

#### Viewing GW2 Information

After users log in, their GW2 account information is stored in their WordPress user profile:

1. Go to **Users** in the WordPress admin
2. Click on a user to edit their profile
3. Scroll down to the "GW2 Information" section
4. You'll see their account name, guild membership status, and other details

#### Manual User Management

- To remove a user's access, simply remove their GW2 API key from their profile
- To block a user, change their WordPress role to one that doesn't have access to protected content
- To delete a user, use the standard WordPress user management tools

### API Rate Limiting

The plugin respects the Guild Wars 2 API rate limits. By default, it will:
- Cache API responses for 1 hour
- Automatically handle rate limiting
- Show appropriate error messages if the API is unavailable

You can adjust the cache duration in the plugin settings if needed.

### Getting a GW2 API Key

1. Log in to your Guild Wars 2 account at [account.arena.net](https://account.arena.net/)
2. Navigate to **Applications**
3. Click **New Key**
4. Enter a name for the key (e.g., "My WordPress Site")
5. Check the following permissions:
   - `account` - Required for basic account information
   - `guilds` - Required for guild membership verification
6. Click **Create API Key**
7. Copy the generated key and use it to log in

## Customization

### Styling

You can customize the appearance of the login form by adding CSS to your theme's `style.css` file or through the WordPress Customizer. The form uses the following CSS classes:

```css
.gw2-login-form {
    /* Form container */
}

.gw2-login-form input[type="text"],
.gw2-login-form input[type="password"] {
    /* Input fields */
}

.gw2-login-form .button {
    /* Submit button */
}

.gw2-login-status {
    /* Logged-in status message */
}
```

### Hooks and Filters

The plugin provides several WordPress hooks for customization:

#### Actions

- `gw2gl_before_login_form` - Fires before the login form
- `gw2gl_after_login_form` - Fires after the login form
- `gw2gl_user_authenticated` - Fires after a user is successfully authenticated
  - Parameters: `$user_id`, `$api_key`

#### Filters

- `gw2gl_allowed_guilds` - Filter the list of allowed guild IDs
  - Parameters: `$guild_ids`
- `gw2gl_user_roles` - Filter the list of available user roles
  - Parameters: `$roles`
- `gw2gl_login_redirect` - Filter the login redirect URL
  - Parameters: `$redirect_url`, `$user_id`

## Troubleshooting

### Common Issues

#### API Key Not Working
- Ensure the API key has the required permissions (`account` and `guilds`)
- Verify the API key is entered correctly (copy-paste is recommended)
- Check if the API key has been revoked or regenerated

#### Guild Membership Not Detected
- Verify the guild ID is correct in the plugin settings
- Ensure the user's API key has the `guilds` permission
- Check if the user is still a member of the guild in Guild Wars 2

#### Login Form Not Displaying
- Make sure the shortcode is entered correctly: `[gw2_login]`
- Check if there are any JavaScript errors in the browser console
- Verify that the user is not already logged in

## Frequently Asked Questions

### Can I use this with any WordPress theme?
Yes, the plugin is designed to work with any WordPress theme. Basic styling is included, but you may want to add custom CSS to match your theme's design.

### Is my API key stored securely?
Yes, API keys are encrypted before being stored in the database using WordPress's built-in security functions.

### Can I restrict content to specific guild ranks?
The current version only verifies guild membership. For rank-based restrictions, you would need to extend the plugin or use custom code with the provided filters.

### How can I customize the login form?
You can customize the login form by:
1. Adding custom CSS to your theme
2. Using the provided hooks to modify the form HTML
3. Creating a template override in your theme

## Support

For support, feature requests, or bug reports, please [open an issue](https://github.com/AlteredM1nd/gw2-guild-login/issues) on GitHub.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [AlteredM1nd](https://github.com/AlteredM1nd)

Guild Wars 2 is a registered trademark of ArenaNet, LLC. This plugin is not affiliated with or endorsed by ArenaNet, LLC.
