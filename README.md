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

## Getting Started

### Prerequisites

Before installing the plugin, you'll need:

1. A self-hosted WordPress installation (version 5.6 or higher)
2. A Guild Wars 2 account with an active API key
3. The guild ID of the guild you want to verify membership against

### Finding Your Guild ID

To find your Guild ID:

1. **Using the Guild Panel in-game (easiest method):**
   - Open your Guild Panel in Guild Wars 2 (default key: `G`)
   - Select the guild you want to use
   - Look at the URL in your browser's address bar
   - The URL will contain your guild ID in this format: `https://guildwars2.com/guild/[GUILD_ID]/`

2. **Using the GW2 API (alternative method):**
   - Log in to your Guild Wars 2 account at [account.arena.net](https://account.arena.net/)
   - Create an API key with the `guilds` permission
   - Visit this URL in your browser (replace `YOUR_API_KEY` with your actual API key):
     ```
     https://api.guildwars2.com/v2/account/guilds?access_token=YOUR_API_KEY
     ```
   - This will return a list of guild IDs your account has access to

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

### Initial Setup

1. After activation, navigate to **Settings > GW2 Guild Login** in your WordPress admin panel
2. You'll see the main configuration page with the following sections:

### Required Settings

- **Target Guild ID**
  - Enter the Guild ID you found earlier
  - This is the guild that users must be a member of to log in
  - Example: `A1B2C3D4-1234-1234-1234-1234567890AB`

- **Default User Role**
  - Select the default WordPress role for new users
  - Recommended: `Subscriber` for most cases
  - This role will be assigned to users when they first log in

### Optional Settings

- **Auto-register New Users**
  - When enabled: Automatically creates a WordPress account for users who don't have one
  - When disabled: Only existing WordPress users can log in
  - Recommended: Enable this if you want to allow new members to join without manual account creation

- **API Cache Expiry**
  - How long to cache API responses (in seconds)
  - Default: `3600` (1 hour)
  - Lower values mean more up-to-date guild membership but more API calls
  - Higher values reduce server load but may show outdated guild information

3. Click **Save Changes** to apply your settings

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

## Usage

## Complete Usage Guide

### Shortcodes

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
