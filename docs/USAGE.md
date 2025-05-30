# GW2 Guild Login - Complete Usage Guide

A comprehensive guide to installing, configuring, and customizing the GW2 Guild Login plugin for WordPress.

## Table of Contents
- [Quick Start Guide](#quick-start-guide)
  - [For Administrators](#for-administrators)
  - [For Developers](#for-developers)
- [System Requirements](#system-requirements)
  - [Minimum Requirements](#minimum-requirements)
  - [Recommended Requirements](#recommended-requirements)
- [Before You Begin](#before-you-begin)
- [Installation](#installation)
  - [Prerequisites](#prerequisites)
  - [Installation Methods](#installation-methods)
    - [Method 1: WordPress Admin (Recommended)](#method-1-wordpress-admin-recommended)
    - [Method 2: Manual Installation](#method-2-manual-installation)
  - [Post-Installation Checklist](#post-installation-checklist)
- [Configuration](#configuration)
  - [Initial Setup](#initial-setup)
  - [Basic Settings](#basic-settings)
    - [Guild Configuration](#guild-configuration)
    - [User Settings](#user-settings)
    - [Security Settings](#security-settings)
    - [API Settings](#api-settings)
  - [Advanced Configuration](#advanced-configuration)
    - [Rate Limiting](#rate-limiting)
    - [Email Notifications](#email-notifications)
  - [Configuration via wp-config.php](#configuration-via-wp-configphp)
- [Admin Interface](#admin-interface)
  - [Main Menu](#main-menu)
  - [Submenus](#submenus)
    - [Dashboard](#dashboard)
    - [Guild Settings](#guild-settings)
    - [User Management](#user-management)
    - [Guild Roster](#guild-roster)
    - [Reports](#reports)
    - [Tools](#tools)
  - [Admin Bar Integration](#admin-bar-integration)
- [Page Templates](#page-templates)
  - [Available Templates](#available-templates)
  - [Using Page Templates](#using-page-templates)
- [Shortcodes](#shortcodes)
  - [Login Form](#login-form)
  - [Login/Logout Links](#loginlogout-links)
  - [Content Restriction](#content-restriction)
    - [By Guild Rank](#by-guild-rank)
    - [By Guild Membership](#by-guild-membership)
  - [User Profile Display](#user-profile-display)
  - [Guild Roster](#guild-roster-1)
- [User Management](#user-management-1)
  - [User Registration Flow](#user-registration-flow)
  - [Admin Management](#admin-management)
    - [User List Enhancements](#user-list-enhancements)
    - [Bulk Actions](#bulk-actions)
  - [User Roles and Capabilities](#user-roles-and-capabilities)
    - [Default Roles](#default-roles)
    - [Custom Role Creation](#custom-role-creation)
  - [Profile Integration](#profile-integration)
- [Troubleshooting](#troubleshooting)
  - [Common Issues and Solutions](#common-issues-and-solutions)
    - [API Connection Issues](#api-connection-issues)
    - [Guild Data Not Updating](#guild-data-not-updating)
  - [Debugging](#debugging)
    - [Enable Debug Mode](#enable-debug-mode)
    - [Check Logs](#check-logs)
  - [Common Error Messages](#common-error-messages)
- [Advanced Usage](#advanced-usage)
  - [Custom Templates](#custom-templates)
  - [Theme Integration](#theme-integration)
    - [Custom CSS](#custom-css)
    - [Template Functions](#template-functions)
  - [Hooks and Filters](#hooks-and-filters)
    - [Actions](#actions)
    - [Filters](#filters)
  - [WP-CLI Commands](#wp-cli-commands)
    - [Available Commands](#available-commands)
    - [Scheduled Tasks](#scheduled-tasks)
- [API Reference](#api-reference)
  - [Endpoints](#endpoints)
  - [Webhooks](#webhooks)
- [Performance Optimization](#performance-optimization)
  - [Caching Strategy](#caching-strategy)
  - [Database Optimization](#database-optimization)
- [Security Best Practices](#security-best-practices)
  - [API Security](#api-security)
  - [Data Protection](#data-protection)
- [Frequently Asked Questions](#frequently-asked-questions)
  - [How do I reset a user's 2FA?](#how-do-i-reset-a-users-2fa)
  - [Can I use multiple guild IDs?](#can-i-use-multiple-guild-ids)
  - [How do I migrate users from another system?](#how-do-i-migrate-users-from-another-system)
- [Common Recipes](#common-recipes)
  - [Restrict Content to Specific Ranks](#restrict-content-to-specific-ranks)
  - [Custom Login Redirects](#custom-login-redirects)
  - [Error Reference](#error-reference)
- [Version History](#version-history)
- [Getting Help](#getting-help)
  - [Support Channels](#support-channels)
  - [Reporting Security Issues](#reporting-security-issues)
- [Contributing](#contributing)
- [Known Issues](#known-issues)

## Quick Start Guide

### For Administrators
1. [Install the plugin](#installation)
2. [Configure basic settings](#configuration)
3. [Set up user roles](#user-management)
4. [Create login pages](#page-templates)

### For Developers
1. [Explore available hooks](#advanced-usage)
2. [Customize templates](#custom-templates)
3. [Use the API](#api-reference)

## System Requirements

### Minimum Requirements
- WordPress 5.6+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- SSL Certificate (highly recommended)
- cURL extension enabled
- JSON extension enabled

### Recommended Requirements
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.5+
- OPcache enabled
- Redis or Memcached for object caching

## Before You Begin

1. **Backup Your Site**
   - Database backup
   - File system backup
   - Note current plugin settings

2. **Check Compatibility**
   - Verify theme compatibility
   - Check for plugin conflicts
   - Test in staging environment first



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

## Admin Interface

The plugin adds a comprehensive admin interface for managing all aspects of your guild's authentication system.

### Main Menu

1. **GW2 Guild Login**
   - Dashboard with system status
   - Quick access to common tasks
   - Activity feed

### Submenus

#### 1. Dashboard
- System status overview
- Recent activity
- Quick links to common tasks
- Server environment information

#### 2. Guild Settings
- **General**
  - Guild ID configuration
  - API key management
  - Default user roles

- **Security**
  - 2FA settings
  - Session management
  - Rate limiting
  - Login attempt limits

#### 3. User Management
- **All Users**
  - Filter by guild membership
  - Bulk actions
  - Export functionality

- **Add New**
  - Manual user creation
  - Role assignment
  - Guild rank mapping

#### 4. Guild Roster
- Member list with filtering
- Rank management
- Join date tracking
- Last login information

#### 5. Reports
- Login activity
- Failed login attempts
- User engagement metrics
- Security events

#### 6. Tools
- **Import/Export**
  - User data import
  - Settings backup/restore
  - Guild member sync

- **System Tools**
  - Clear cache
  - Reset settings
  - Debug information

### Admin Bar Integration
Quick access to common functions:
- View guild status
- Access user management
- Check for updates
- View documentation

## Page Templates

The plugin provides several page templates that can be used to create custom layouts for different sections of your guild site:

### Available Templates

1. **GW2 Login** (`gw2-login.php`)
   - Displays the login form
   - Automatically redirects logged-in users
   - Customizable login redirects

2. **Member Dashboard** (`gw2-dashboard.php`)
   - User profile overview
   - Guild membership details
   - Account settings
   - Active sessions

3. **Guild Roster** (`gw2-roster.php`)
   - Complete list of guild members
   - Sortable columns
   - Search functionality
   - Pagination support

### Using Page Templates

1. **Create a New Page**
   - Go to **Pages > Add New**
   - Enter a title (e.g., "Member Login")
   - In the Page Attributes section, select the desired template
   - Publish the page

2. **Template Parameters**
   Some templates accept additional parameters:
   ```
   [gw2_roster show_rank="yes" show_join_date="yes" sort_by="name"]
   ```

3. **Template Overrides**
   To customize a template, copy it to your theme's directory:
   ```
   /wp-content/themes/your-theme/gw2-guild-login/template-name.php
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

## Common Recipes

### Restrict Content to Specific Ranks
```php
if (function_exists('gw2gl_user_has_rank')) {
    if (gw2gl_user_has_rank(get_current_user_id(), 'Officer')) {
        // Show officer content
    }
}
```

### Custom Login Redirects
```php
add_filter('gw2gl_login_redirect', function($redirect, $user_id) {
    if (gw2gl_user_has_rank($user_id, 'Leader')) {
        return '/leader-dashboard/';
    }
    return $redirect;
}, 10, 2);
```

### Error Reference

| Error Code | Description | Solution |
|------------|-------------|-----------|
| GW2-1001 | Invalid API Key | [See API Key Setup](#api-settings) |
| GW2-1002 | Guild Not Found | [Verify Guild ID](#guild-configuration) |
| GW2-1003 | Rate Limited | [Adjust Rate Limiting](#rate-limiting) |
| GW2-1004 | Authentication Failed | [Check API Permissions](#api-settings) |
| GW2-1005 | Session Expired | [Adjust Session Settings](#session-management) |

## Version History

[View complete changelog](CHANGELOG.md)

## Getting Help

### Support Channels
- [GitHub Issues](https://github.com/AlteredM1nd/gw2-guild-login/issues)

### Reporting Security Issues
Please report security vulnerabilities to gw2-guild-login@protonmail.com

## Contributing

We welcome contributions! Here's how you can help:

1. Report bugs and submit feature requests
2. Submit pull requests
3. Improve documentation
4. Help test new releases

See our [Contributing Guidelines](CONTRIBUTING.md) for more details.

## Known Issues

- See [GitHub Issues](https://github.com/AlteredM1nd/gw2-guild-login/issues) for current issues

---

*GW2 Guild Login v2.4.0 | [Changelog](CHANGELOG.md) | [License](LICENSE) | [Contribute](CONTRIBUTING.md)*
