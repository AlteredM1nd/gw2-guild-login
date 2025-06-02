![Security](https://img.shields.io/badge/Security-A+-green?style=flat)
![PHPStan Level](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)
![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![License](https://img.shields.io/github/license/AlteredM1nd/gw2-guild-login)
![Release](https://img.shields.io/github/v/release/AlteredM1nd/gw2-guild-login)

# GW2 Guild Login

A secure, modern WordPress plugin enabling users to log in using their Guild Wars 2 API key. Features robust guild membership verification, role management, advanced 2FA (with backup codes), and industry-leading security and coding standards compliance.

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## Features

### 🔒 Security & Authentication Features
- **Military-grade API Key Encryption:** All API keys are encrypted at rest using AES-256-CBC. Automatic migration utility encrypts any existing plaintext keys on upgrade, with a persistent flag to ensure migration only runs once. Admins are notified if the encryption key is missing or weak, and should ensure `SECURE_AUTH_KEY` is set in `wp-config.php`. A strong encryption key (32+ chars) is required; admins will see a warning if their key is missing or weak.
- **Brute-force Protection & Rate Limiting:** Login attempts are rate-limited and repeated failures result in a temporary lockout (5 attempts in 15 minutes = 10 minute block). All events are logged and stats are shown on the security dashboard.
- **Magic-link Password/API Key Recovery:** Secure recovery via `/gw2-recovery/` (JWT, 1-hour expiry).
- **Two-Factor Authentication (2FA):** TOTP-based 2FA with authenticator app support and secure backup codes. Admins can enforce or recommend 2FA for users and regenerate backup codes via AJAX.
- **User-specific Cache Keys & Invalidation:** User API cache is auto-cleared on login, logout, API key update, and guild membership changes. Efficient API usage with robust response caching (configurable, can be cleared by admin or developer, filterable for debugging).
- **Security Dashboard:** Displays encryption status (✔ Active/✖ Insecure), brute-force stats, and admin warnings for weak/missing keys.
- **Admin Notices & Warnings:** Proactive admin notices for weak/missing encryption keys and other security issues.
- **Session Management:** Custom session handler with security headers, session revocation, device/session listing, and ability to revoke other sessions.
- **Input Sanitization & Output Escaping:** All user input/output is sanitized and escaped per WordPress security best practices.
- **Nonce & Capability Checks:** Nonce verification and capability checks on all sensitive actions.
- **I18n & Accessibility:** All user/admin-facing strings are translation-ready and properly escaped.
- **Automatic Legacy Key Cleanup:** Legacy plaintext API keys are securely deleted after migration.
- **Automated Test Coverage:** Automated test coverage for all critical features.
- **Strict Type Safety & Static Analysis:** Full PHPStan/static analysis compliance (v2.6.1+). All code, including dynamic WordPress templates, is statically analyzed to the greatest extent possible. Persistent static analysis warnings are either real bugs or intentionally suppressed for WordPress edge cases. See the Contributing and Usage docs for details.
- **Improved Debug Logging:** Security and cache events are logged in debug mode for easier troubleshooting.
- **See the [Security](SECURITY.md) and [Usage](docs/USAGE.md) docs for details.**

### 👥 Guild Integration
- **Guild Membership Verification**: Restrict access to specific guilds
- **Rank-Based Access**: Control content visibility based on guild ranks
- **Guild Role Mapping**: Automatically assign WordPress roles based on guild rank
- **Cached API Calls**: Efficient API usage with robust response caching (configurable, can be cleared by admin or developer, filterable for debugging)

### 🛠️ User Management
- **Auto-Registration**: Automatically create accounts for new GW2 players
- **User Dashboard**: Modern dashboard for viewing account details, guild memberships, and active sessions

### ✨ UX & Polish
- **Login Button Shortcode**: Easily add a "Log in with GW2" button anywhere using `[gw2_login]`
- **Dashboard Widget**: Guild membership and account info shown on user dashboard
- **Admin UI Improvements**: Multi-guild support, cache controls, and clearer settings
- **Cache Clearing Utility**: Developers and admins can clear API cache for specific endpoints
- **Developer Filter**: `gw2gl_disable_api_cache` filter for debugging

- **Session Control**: Monitor, revoke, and manage active login sessions and devices
- **Customizable User Roles**: Fine-grained permission control
- **Profile Integration**: Add GW2 account info and 2FA settings to user profile pages

### 🎨 Frontend Features
- **Shortcode Support**: Easy integration with any page or post
- **Restrict Content by Guild Rank**: Use shortcodes to restrict content to specific guild ranks or display custom messages
- **Responsive Design**: Works on all devices
- **Customizable Templates**: Override default templates in your theme
- **AJAX Forms**: Smooth form handling without page reloads
- **Appearance & Branding**: Customizable login/dashboard logo, welcome text, primary/accent colors, and dark mode

## Requirements

- WordPress 5.8 or higher (latest recommended)
- PHP 8.0 or higher (tested with PHP 8.4.7)
- Composer (for development)
- Node.js 14+ (for asset building)
- A Guild Wars 2 account with API key generation access
- (Optional) A guild ID for guild-specific features

## Quick Start

1. **Install the Plugin**
   - Download the latest release from [GitHub](https://github.com/AlteredM1nd/gw2-guild-login)
   - Upload and activate through WordPress admin > Plugins > Add New
   - Or install via FTP to `/wp-content/plugins/`

2. **Basic Configuration**
   - Go to **Settings > GW2 Guild Login** (or **GW2 Guild** in the admin menu)
   - Enter your Guild ID (optional)
   - Configure user roles, permissions, 2FA enforcement, and appearance settings (logo, colors, welcome text, dark mode)
   - Set up 2FA for your admin account via your user profile

3. **Add Login Form**
   Use the shortcode `[gw2_login]` in any post or page to display the login form.

4. **Enable 2FA and Backup Codes**
   - Users can enable 2FA and manage backup codes from their profile or dashboard
   - Admins can regenerate backup codes via AJAX

## Configuration

### Guild Settings
- **Guild ID**: Restrict login to specific guild members (leave blank to allow any GW2 account)
- **Default Role**: Role assigned to new users upon registration
- **Enable 2FA**: Require two-factor authentication for all users
- **Session Length**: Control how long login sessions remain active

### Appearance & Branding
- **Primary/Accent Colors**: Choose your own color theme for login and dashboard
- **Custom Logo**: Upload a logo to display on login and dashboard
- **Welcome Text**: Show a custom message to users
- **Force Dark Mode**: Override user/device preference with a dark theme

### Security Settings
- **API Key Permissions**: Recommended: `account`, `guilds`, `characters`
- **Rate Limiting**: Configure login attempt limits
- **Session Management**: Control how sessions are handled

## Usage

### Page Templates

The plugin provides two ready-to-use page templates for seamless integration with your WordPress site:

- [`dashboard.php`](templates/dashboard/dashboard.php): Securely renders the logged-in user's Guild Wars 2 account overview, session/device management, and profile details. Used for the member dashboard page. Fully type-safe and extensible via hooks/filters. Can be overridden by copying to your theme or child theme.
- [`template-guild-only.php`](templates/template-guild-only.php): Restricts page content to verified guild members only. Displays a customizable access denied message to non-members. Useful for members-only pages, event calendars, or resource libraries.

**How to use or override:**
1. Assign the template to a page in the WordPress editor ("Template" dropdown).
2. To customize, copy the template file into your active theme or child theme (preserving the path structure) and modify as needed.
3. Both templates support WordPress hooks and can be extended for custom layouts, additional fields, or branding.

> **Security Note:** These templates enforce all plugin security features (authentication, guild checks, output escaping) and are safe for use in production environments.

### Shortcodes

#### Login Form
```[gw2_login]```
> **Note:** The login and dashboard pages will display your custom logo and welcome text if set in Appearance & Branding settings.

#### Restrict Content by Rank
```[gw2_restricted rank="Officer"]Only officers see this.[/gw2_restricted]```

#### Custom Access Denied Message
```[gw2_restricted rank="Member" message="Members only!"]Content[/gw2_restricted]```

#### Login/Logout Links
```[gw2_loginout]```

## Finding Your Guild ID

1. **Using Guild Search** (easiest):
   ```
   https://api.guildwars2.com/v2/guild/search?name=YOUR_GUILD_NAME
   ```
   Replace spaces with `%20` in your guild name.

2. **Using Your Account** (requires API key):
   ```
   https://api.guildwars2.com/v2/account/guilds?access_token=YOUR_API_KEY
   ```

## Troubleshooting

### Common Issues
- **"Invalid API Key"**: Ensure your API key has the required permissions (`account`, `guilds`)
- **Guild Not Found**: Verify the Guild ID is correct and your API key has guild access
- **Login Failures**: Check your server's PHP version and error logs

For detailed documentation, see the [Complete Usage Guide](docs/USAGE.md), [2FA Guide](docs/TWO_FACTOR_AUTH.md), and [Security Policy](SECURITY.md).

## Development

### Prerequisites
- Node.js 14+
- Composer
- PHP 7.4+

### Setup
```bash
# Install dependencies
composer install
npm install

# Build assets
npm run build
```

## Contributing

Contributions are welcome! Please read our [contributing guidelines](CONTRIBUTING.md) before submitting pull requests. Please follow our PHPDoc, I18n, and coding standards.

## License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## Complete Documentation

For complete documentation, including advanced configuration options, troubleshooting, and developer guides, please see the [Complete Usage Guide](docs/USAGE.md).

For support, feature requests, or bug reports, please [open an issue](https://github.com/AlteredM1nd/gw2-guild-login/issues) on GitHub.

## Credits

Developed by [AlteredM1nd](https://github.com/AlteredM1nd)

Guild Wars 2 is a registered trademark of ArenaNet, LLC. This plugin is not affiliated with or endorsed by ArenaNet, LLC.
