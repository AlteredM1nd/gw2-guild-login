# GW2 Guild Login

A secure WordPress plugin that allows users to log in using their Guild Wars 2 API key, with guild membership verification, user role management, and enhanced security features.

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

### 🔒 Authentication & Security
- **GW2 API Login**: Secure authentication using Guild Wars 2 API keys
- **Two-Factor Authentication**: Optional TOTP-based 2FA with support for authenticator apps
- **Secure Session Management**: Custom session handler with security headers
- **Rate Limiting**: Protection against brute force attacks
- **API Key Encryption**: All keys are encrypted before storage

### 👥 Guild Integration
- **Guild Membership Verification**: Restrict access to specific guilds
- **Rank-Based Access**: Control content visibility based on guild ranks
- **Guild Role Mapping**: Automatically assign WordPress roles based on guild rank
- **Cached API Calls**: Efficient API usage with response caching

### 🛠️ User Management
- **Auto-Registration**: Automatically create accounts for new GW2 players
- **User Dashboard**: View account details, guild memberships, and active sessions
- **Session Control**: Monitor and manage active login sessions
- **Customizable User Roles**: Fine-grained permission control

### 🎨 Frontend Features
- **Shortcode Support**: Easy integration with any page or post
- **Responsive Design**: Works on all devices
- **Customizable Templates**: Override default templates in your theme
- **AJAX Forms**: Smooth form handling without page reloads

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- A Guild Wars 2 account with API key generation access
- (Optional) A guild ID for guild-specific features

## Quick Start

1. **Install the Plugin**
   - Download the latest release from [GitHub](https://github.com/AlteredM1nd/gw2-guild-login)
   - Upload and activate through WordPress admin > Plugins > Add New
   - Or install via FTP to `/wp-content/plugins/`

2. **Basic Configuration**
   - Go to **Settings > GW2 Guild Login**
   - Enter your Guild ID (optional)
   - Configure default user roles and permissions
   - Set up 2FA if desired

3. **Add Login Form**
   Use the shortcode `[gw2_login]` in any post or page to display the login form.

## Configuration

### Guild Settings
- **Guild ID**: Restrict login to specific guild members (leave blank to allow any GW2 account)
- **Default Role**: Role assigned to new users upon registration
- **Enable 2FA**: Require two-factor authentication for all users
- **Session Length**: Control how long login sessions remain active

### Security Settings
- **API Key Permissions**: Recommended: `account`, `guilds`, `characters`
- **Rate Limiting**: Configure login attempt limits
- **Session Management**: Control how sessions are handled

## Usage

### Shortcodes

#### Login Form
```
[gw2_login]
```

#### Restrict Content by Rank
```
[gw2_restricted rank="Officer"]
This content is only visible to guild officers.
[/gw2_restricted]
```

#### Login/Logout Links
```
[gw2_loginout]
```

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

For detailed documentation, please see the [Complete Usage Guide](docs/USAGE.md).

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

Contributions are welcome! Please read our [contributing guidelines](CONTRIBUTING.md) before submitting pull requests.

## License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## Complete Documentation

For complete documentation, including advanced configuration options, troubleshooting, and developer guides, please see the [Complete Usage Guide](docs/USAGE.md).

For support, feature requests, or bug reports, please [open an issue](https://github.com/AlteredM1nd/gw2-guild-login/issues) on GitHub.

## Credits

Developed by [AlteredM1nd](https://github.com/AlteredM1nd)

Guild Wars 2 is a registered trademark of ArenaNet, LLC. This plugin is not affiliated with or endorsed by ArenaNet, LLC.
