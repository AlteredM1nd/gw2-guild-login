# GW2 Guild Login

A WordPress plugin that allows users to log in using their Guild Wars 2 API key, with optional guild membership verification and user role management.

## Features

- **GW2 API Integration**: Authenticate users using their Guild Wars 2 API keys
- **Guild Membership Verification**: Restrict access to users who are members of specific guilds
- **User Role Management**: Assign WordPress user roles based on guild membership
- **Auto-Registration**: Automatically create WordPress user accounts for new GW2 players
- **Secure API Key Storage**: Securely store API keys in the database
- **Shortcodes**: Easy-to-use shortcodes for login forms and protected content
- **Customizable Settings**: Configure guild requirements and user roles through the WordPress admin

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- A Guild Wars 2 account with API key generation access

## Installation

### Method 1: WordPress Admin Panel

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Click **Upload Plugin**
4. Upload the `gw2-guild-login.zip` file
5. Click **Install Now**
6. After installation, click **Activate Plugin**

### Method 2: Manual Installation

1. Download the plugin files
2. Extract the `gw2-guild-login` folder to your computer
3. Upload the `gw2-guild-login` folder to the `/wp-content/plugins/` directory
4. Log in to your WordPress admin panel
5. Navigate to **Plugins**
6. Find **GW2 Guild Login** in the list and click **Activate**

## Configuration

1. After activation, go to **Settings > GW2 Guild Login**
2. Configure the following settings:
   - **Target Guild ID**: Enter the Guild ID that users must be a member of
   - **Default User Role**: Select the default role for new users
   - **Auto-register New Users**: Enable to automatically create accounts for new users
   - **API Cache Expiry**: Set how long to cache API responses (in seconds)
3. Click **Save Changes**

## Usage

### Shortcodes

#### Login Form

Add a login form to any page or post using the `[gw2_login]` shortcode:

```
[gw2_login]
```

#### Protected Content

Restrict content to logged-in GW2 guild members using the `[gw2_guild_only]` shortcode:

```
[gw2_guild_only]
This content is only visible to guild members.
[/gw2_guild_only]
```

#### Login/Logout Link

Add a dynamic login/logout link with the `[gw2_loginout]` shortcode:

```
[gw2_loginout]
```

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

## Changelog

### 2.0.0
- Complete rewrite with improved architecture
- Added admin interface for settings
- Added guild membership verification
- Improved security and error handling
- Added shortcode for protected content

### 1.0.0
- Initial release
- Basic API key authentication
- Simple login form

## Support

For support, feature requests, or bug reports, please [open an issue](https://github.com/AlteredM1nd/gw2-guild-login/issues) on GitHub.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [AlteredM1nd](https://github.com/AlteredM1nd)

Guild Wars 2 is a registered trademark of ArenaNet, LLC. This plugin is not affiliated with or endorsed by ArenaNet, LLC.
