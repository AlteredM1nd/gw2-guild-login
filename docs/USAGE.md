# GW2 Guild Login - Complete Usage Guide

A comprehensive guide to installing, configuring, and customizing the GW2 Guild Login plugin for WordPress.

## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
- [Shortcodes](#shortcodes)
- [User Management](#user-management)
- [Troubleshooting](#troubleshooting)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Performance Optimization](#performance-optimization)
- [Security Best Practices](#security-best-practices)
- [Frequently Asked Questions](#frequently-asked-questions)

## Installation

### Prerequisites
- WordPress 5.6 or higher
- PHP 7.4 or higher
- A Guild Wars 2 account with API key generation access
- MySQL 5.7+ or MariaDB 10.3+
- SSL certificate (highly recommended for security)

### Installation Methods

#### Method 1: WordPress Admin (Recommended)
1. **Download the Plugin**
   - Get the latest stable release from [GitHub](https://github.com/AlteredM1nd/gw2-guild-login/releases)
   - Download the `gw2-guild-login.zip` file

2. **Install via WordPress Admin**
   - Navigate to **Plugins > Add New > Upload Plugin**
   - Click **Choose File** and select the downloaded ZIP file
   - Click **Install Now**
   - After installation, click **Activate Plugin**

#### Method 2: Manual Installation
1. **Download and Extract**
   ```bash
   wget https://github.com/AlteredM1nd/gw2-guild-login/archive/refs/tags/v1.0.0.zip
   unzip v1.0.0.zip -d /path/to/wordpress/wp-content/plugins/
   ```

2. **Rename the Directory**
   ```bash
   mv /path/to/wordpress/wp-content/plugins/gw2-guild-login-1.0.0 /path/to/wordpress/wp-content/plugins/gw2-guild-login
   ```

3. **Activate the Plugin**
   - Go to **WordPress Admin > Plugins**
   - Find "GW2 Guild Login" in the list
   - Click **Activate**

### Post-Installation Checklist
- [ ] Verify the plugin is active in **Plugins > Installed Plugins**
- [ ] Check for any activation errors in the WordPress debug log
- [ ] Verify the plugin's database tables were created successfully
- [ ] Test the login functionality with a test account

## Configuration

### Initial Setup
1. Navigate to **Settings > GW2 Guild Login**
2. Click on the **General** tab

### Basic Settings

#### Guild Configuration
- **Guild ID**: 
  - Enter your Guild Wars 2 Guild ID (e.g., `F1A2B3C4-D5E6-7890-1A2B-3C4D5E6F7G8H`)
  - Leave empty to allow any GW2 account
  - Find your Guild ID using the [GW2 API](https://api.guildwars2.com/v2/guild/search?name=Your%20Guild%20Name)

#### User Settings
- **Default User Role**: 
  - Select the default WordPress role for new users
  - Recommended: `Subscriber` for most cases
  - Use `GW2_Member` if you've created a custom role

#### Security Settings
- **Enable Two-Factor Authentication (2FA)**:
  - When enabled, users must set up TOTP (Time-based One-Time Password)
  - Uses Google Authenticator or any TOTP-compatible app
  - Backup codes are provided during setup

- **Session Management**:
  - **Session Length**: 14 days (default)
  - **Limit Concurrent Sessions**: Prevent multiple logins from different devices
  - **Inactive Session Timeout**: 30 minutes (recommended)

#### API Settings
- **GW2 API Endpoint**: `https://api.guildwars2.com/v2/` (default)
- **Request Timeout**: 10 seconds (adjust based on server performance)
- **Enable Debug Mode**: Logs API requests and responses (for development only)

### Advanced Configuration

#### Rate Limiting
- **Login Attempts**: 5 per 15 minutes (prevents brute force attacks)
- **API Request Limit**: 300 requests per minute (GW2 API limit is 600)
- **Cache Expiration**: 1 hour for guild data (reduces API calls)

#### Email Notifications
- **New User Registration**: Notify admin when new users register
- **Failed Login Attempts**: Get alerts for potential security issues
- **Account Activity**: Weekly summary of user activity

### Configuration via wp-config.php
For advanced users, you can set configuration options in `wp-config.php`:

```php
define('GW2GL_GUILD_ID', 'F1A2B3C4-D5E6-7890-1A2B-3C4D5E6F7G8H');
define('GW2GL_DEFAULT_ROLE', 'subscriber');
define('GW2GL_ENABLE_2FA', true);
define('GW2GL_API_TIMEOUT', 15); // seconds
```

## Shortcodes

### Login Form
Display the GW2 login form with optional parameters:

```
[gw2_login 
    redirect="/members-area/" 
    show_logo="yes"
    button_text="Login with GW2"
    class="custom-login-form"
]
```

**Parameters:**
- `redirect`: URL to redirect to after login (default: current page)
- `show_logo`: Display GW2 logo (yes/no, default: yes)
- `button_text`: Customize the login button text
- `class`: Add custom CSS classes to the form

### Login/Logout Links
Show dynamic login/logout links:

```
[gw2_loginout 
    login_redirect="/members/" 
    logout_redirect="/goodbye/"
    login_text="Sign In"
    logout_text="Sign Out"
]
```

### Content Restriction

#### By Guild Rank
```
[gw2_restricted rank="Officer,Leader"]
This content is only visible to guild officers and leaders.
[gw2_restricted_else]
You need to be an officer or leader to view this content.
[/gw2_restricted]
```

#### By Guild Membership
```
[gw2_restricted]
Welcome, guild member! This content is only visible to guild members.
[gw2_restricted_else]
Please join our guild to access this content.
[/gw2_restricted]
```

### User Profile Display
Show user's GW2 information:
```
[gw2_profile 
    show_avatar="yes"
    show_rank="yes"
    show_characters="3"
    layout="horizontal"
]
```

### Guild Roster
Display a list of guild members:
```
[gw2_roster 
    show_rank="yes"
    show_join_date="yes"
    sort_by="rank"
    limit="50"
]
```

## User Management

### User Registration Flow
1. **First-Time Login**
   - User clicks login button
   - Redirected to GW2 API authorization
   - On approval, account is created with default role
   - Welcome email is sent (if enabled)
   - Redirected to specified page

2. **Existing Users**
   - Can link GW2 account in their profile
   - Can unlink account (admin can restrict this)
   - Can manage 2FA settings

### Admin Management

#### User List Enhancements
- **GW2 Account Status**: Shows linked status
- **Last Login**: Date of last successful login
- **Account Age**: How long since account was created
- **Guild Rank**: Current guild rank (if applicable)

#### Bulk Actions
- Link multiple users to GW2 accounts
- Reset 2FA for users
- Export user data (GDPR compliant)
- Send mass emails to guild members

### User Roles and Capabilities

#### Default Roles
- **GW2 Member**: Basic access to guild content
- **GW2 Officer**: Can moderate guild content
- **GW2 Leader**: Full administrative access

#### Custom Role Creation
```php
function add_gw2_roles() {
    add_role('gw2_recruit', 'GW2 Recruit', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ));
}
add_action('init', 'add_gw2_roles');
```

### Profile Integration
Users can manage their GW2 account in their WordPress profile:
- View linked GW2 account
- Manage 2FA settings
- View guild membership
- See login history
- Manage notification preferences

## Troubleshooting

### Common Issues and Solutions

#### API Connection Issues

**Symptoms:**
- "Unable to connect to Guild Wars 2 API"
- Timeout errors during login

**Solutions:**
1. Verify server can reach `api.guildwars2.com`:
   ```bash
   curl -I https://api.guildwars2.com/v2/tokeninfo
   ```
2. Check firewall settings
3. Verify SSL certificates are up to date
4. Try increasing API timeout in settings

#### Guild Data Not Updating

**Symptoms:**
- Old guild ranks showing
- Missing members in roster

**Solutions:**
1. Manually clear the cache:
   ```bash
   wp gw2-guild-login clear-cache
   ```
2. Check API key permissions
3. Verify guild ID is correct

### Debugging

#### Enable Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('GW2GL_DEBUG', true);
```

#### Check Logs
- WordPress debug log: `wp-content/debug.log`
- Server error logs
- Browser console for JavaScript errors

### Common Error Messages

| Error Message | Possible Cause | Solution |
|--------------|----------------|-----------|
| Invalid API Key | Key revoked or incorrect | Generate new key |
| Guild Not Found | Wrong guild ID | Verify ID via API |
| Rate Limited | Too many requests | Wait 1 minute |
| SSL Error | Outdated certificates | Update OpenSSL |
| 500 Error | Plugin conflict | Disable other plugins |

## Advanced Usage

### Custom Templates
Create a `gw2-guild-login` directory in your theme to override these templates:

1. **Login Form** (`login-form.php`)
   - Customize the login form HTML
   - Add custom fields or styling
   - Integrate with third-party services

2. **User Dashboard** (`dashboard.php`)
   - Customize the member dashboard
   - Add custom widgets or sections
   - Display guild statistics

3. **Restricted Content** (`restricted-message.php`)
   - Customize access denied messages
   - Show different messages based on user role
   - Add call-to-action buttons

### Theme Integration

#### Custom CSS
Add to your theme's `style.css`:
```css
.gw2-login-form {
    max-width: 400px;
    margin: 2em auto;
    padding: 2em;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

#### Template Functions
Use in your theme's template files:
```php
<?php if (function_exists('gw2gl_is_user_in_guild')) : ?>
    <?php if (gw2gl_is_user_in_guild(get_current_user_id())) : ?>
        <!-- Guild-only content -->
    <?php endif; ?>
<?php endif; ?>
```

### Hooks and Filters

#### Actions
```php
// Add custom content before login form
add_action('gw2gl_before_login_form', function() {
    echo '<div class="login-notice">Guild members only!</div>';
});

// After successful authentication
add_action('gw2gl_user_authenticated', function($user_id, $api_key) {
    // Log the login
    error_log("User $user_id logged in with GW2 account");
}, 10, 2);
```

#### Filters
```php
// Modify default user role
add_filter('gw2gl_default_user_role', function($role) {
    return 'gw2_member'; // Your custom role
});

// Custom login redirect
add_filter('gw2gl_login_redirect', function($redirect_url, $user_id) {
    if (user_can($user_id, 'manage_options')) {
        return admin_url();
    }
    return $redirect_url;
}, 10, 2);
```

### WP-CLI Commands

#### Available Commands
```bash
# Sync all guild members
wp gw2-guild-login sync-members --force

# Clear all cached API responses
wp gw2-guild-login clear-cache --all

# Get user's GW2 info
wp gw2-guild-login get-user --user=admin

# Link a user to GW2 account
wp gw2-guild-login link-account --user=5 --api-key=ABC-123

# Check API status
wp gw2-guild-login check-api
```

#### Scheduled Tasks
Setup a cron job to keep guild data in sync:
```bash
# Run every 6 hours
0 */6 * * * cd /path/to/wordpress && wp gw2-guild-login sync-members --quiet
```

## API Reference

### Endpoints
- `GET /gw2-guild-login/v1/profile` - Get current user's GW2 profile
- `POST /gw2-guild-login/v1/link-account` - Link GW2 account
- `GET /gw2-guild-login/v1/guild-members` - List guild members (admin only)

### Webhooks
Configure webhooks for these events:
- `user.registered` - New user signs up
- `user.logged_in` - User logs in
- `guild.joined` - New member joins guild
- `guild.left` - Member leaves guild

## Performance Optimization

### Caching Strategy
- API responses cached for 1 hour by default
- User sessions stored in WordPress transients
- Guild roster cached separately for faster loading

### Database Optimization
Run monthly maintenance:
```sql
OPTIMIZE TABLE wp_gw2gl_user_data;
ANALYZE TABLE wp_gw2gl_login_logs;
```

## Security Best Practices

### API Security
- Always use HTTPS
- Rotate API keys quarterly
- Implement rate limiting
- Validate all API responses

### Data Protection
- Encrypt sensitive data at rest
- Regular security audits
- GDPR compliance features
- Data export/erase functionality

## Frequently Asked Questions

### How do I reset a user's 2FA?
```bash
wp gw2-guild-login reset-2fa --user=username
```

### Can I use multiple guild IDs?
Yes, separate them with commas in the Guild ID field:
```
Guild1ID,Guild2ID,Guild3ID
```

### How do I migrate users from another system?
1. Export user data to CSV
2. Use WP-CLI to import:
   ```bash
   wp gw2-guild-login import-users users.csv
   ```

## Support

### Getting Help
- [Documentation](https://github.com/AlteredM1nd/gw2-guild-login/wiki)
- [GitHub Issues](https://github.com/AlteredM1nd/gw2-guild-login/issues)

### Reporting Security Issues
Please report security vulnerabilities to gw2-guild-login@protonmail.com

---

*GW2 Guild Login v2.4.0 | [Changelog](CHANGELOG.md) | [License](LICENSE)*
