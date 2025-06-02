# Security Policy

## API Key Encryption and Migration (v2.6.0)
- All API keys are now encrypted at rest using AES-256-CBC. On upgrade, a migration utility encrypts any existing plaintext API keys. A persistent flag (`gw2gl_api_key_migrated_260`) ensures migration only runs once. Admin notices are shown if the encryption key is missing or weak. **Ensure `SECURE_AUTH_KEY` is set in your `wp-config.php` for secure encryption.**
- Admins are warned if the encryption key is missing or weak (see admin notice).
- **Brute-force Protection:** Login attempts are rate-limited and repeated failures result in a temporary lockout (5 attempts in 15 minutes = 10 minute block). All events are logged and stats shown on the dashboard.
- **Automatic Cache Invalidation:** User API cache is now auto-cleared on login, logout, API key update, and guild membership changes. This prevents stale data and improves reliability.
- **Improved Debug Logging:** Security and cache events are logged in debug mode for easier troubleshooting.

_Last audited: 2025-05-31_

**Note:** As of v2.6.0, PHP 8.0 or higher is required for all security features and dependencies, including 2FA.

## Security Features

- Encrypted API key storage (AES-256-CBC)
- Brute-force login protection with lockout and logging
- Magic-link password/API key recovery (see /gw2-recovery/ page)
- Admin dashboard encryption status indicator
- User-specific cache keying for robust invalidation

## Supported Versions

**v2.6.00:** Main plugin file is now fully object-oriented. All authentication, shortcode, and 2FA logic is handled by dedicated classes for improved security and maintainability. This version introduces a class-based architecture, significantly enhancing the plugin's security posture.


| Version | Supported          | Security Updates Until |
| ------- | ------------------ | --------------------- |
| v2.6.00   | :white_check_mark: | 2026-05-31            |
| v2.6.00   | :white_check_mark: | 2026-05-31            |
| v2.6.00   | :white_check_mark: | 2026-05-31            |
| < v2.6.00   | :x:                | -                     |

## Reporting a Vulnerability

If you discover a security vulnerability within GW2 Guild Login, please follow these steps:

1. **Do not** create a public GitHub issue for security vulnerabilities
2. Email the security team directly at [gw2-guild-login@protonmail.com](mailto:gw2-guild-login@protonmail.com)
3. Include the following details:
   - A description of the vulnerability
   - Steps to reproduce the issue
   - Your WordPress and PHP version
   - Any error messages

Please see the [Contributing Guide](CONTRIBUTING.md) for secure coding practices.

## Response Time

- We will acknowledge your email within 48 hours
- We'll keep you informed of the progress toward fixing the vulnerability
- After the vulnerability is addressed, we will credit you in the release notes (unless you prefer to remain anonymous)

## Security Best Practices

- Always use the latest version of WordPress and PHP
- Keep the plugin updated to the latest version
- Use strong, unique API keys
- Regularly audit user accounts and permissions
- Follow the principle of least privilege when assigning user roles

## Security Features

### Data Protection
- **API Key Encryption**: All API keys are encrypted using AES-256-CBC before storage
- **2FA Secrets**: TOTP secrets are encrypted using AES-256-CBC
- **Backup Codes**: Stored using one-way hashing (bcrypt)
- **Secure Session Management**: Custom session handler with proper security headers
- **Data Sanitization & Output Escaping**: All user input is sanitized and all output is escaped using WordPress core functions
- **Internationalization (I18n)**: All user/admin-facing strings are translation-ready and properly escaped
- **Secure Cookies**: HTTP-only, secure, and SameSite=Lax flags set for all cookies

### Authentication Security
- **Two-Factor Authentication (2FA)**: TOTP-based 2FA with backup codes and trusted device support
- **Rate Limiting**: Protection against brute force attacks with exponential backoff
- **Session Management**: Session ID regeneration, concurrent session control, device fingerprinting
- **Secure Credential Storage**: API keys encrypted, passwords hashed, 2FA secrets/backup codes securely stored
- **CSRF Protection**: Nonce verification for all form submissions and AJAX requests
- **Trusted Device Management**: Secure cookie-based device recognition and revocation

### API Security
- **Input Validation**: Strict validation of all API and user inputs
- **Output Escaping**: Proper escaping of all dynamic content
- **Error Handling**: Generic error messages to prevent information leakage
- **Rate Limiting**: Respects GW2 API rate limits with local caching

### WordPress Integration & Coding Standards
- **Capability Checks**: Proper user capability verification for all privileged actions
- **Nonce Verification**: For all form submissions and AJAX requests
- **Data Sanitization**: WordPress core functions for all data handling
- **Hooks and Filters**: Secure extension points for developers
- **PHPDoc & Static Analysis**: All code is documented and analyzed with PHPStan
- **Naming Consistency**: Classes, methods, and variables follow strict naming conventions

## Changelog

- **v2.6.0**: Added robust API key encryption and automatic migration for existing keys. Admin notice warns if encryption key is missing or weak.

## Known Security Considerations

### API Security
- The plugin requires the `account` and `guilds` permissions from the GW2 API
- API keys are stored encrypted in the database using AES-256-CBC
- All API requests are made over HTTPS
- API responses are robustly cached to respect rate limits and improve performance (see docs/USAGE.md)
- Cache can be cleared by admin or developer utility
- Caching logic is tested and polyfilled for non-WordPress environments

### Session Management
- User sessions are managed with enhanced security measures
- Session data is stored server-side with minimal client-side storage
- Session IDs are regenerated on login and privilege changes
- Session lifetime is configurable with secure defaults

### Data Protection
- All sensitive data is properly escaped before output
- User inputs are validated and sanitized
- Error messages are generic to prevent information disclosure
- Database queries are properly prepared to prevent SQL injection

### WordPress Integration
- Follows WordPress coding standards and security best practices
- Proper capability checks before performing privileged operations
- Nonce verification for all form submissions and AJAX requests
- Proper escaping of all dynamic content

### Recommendations
1. Always use the latest version of WordPress and PHP
2. Keep the plugin updated to the latest version
3. Use strong, unique API keys with minimal required permissions
4. Regularly audit user accounts and permissions
5. Implement HTTPS for all site traffic
6. Use a web application firewall (WAF) for additional protection
7. Regularly monitor your site for suspicious activity
8. Review the [Contributing Guide](CONTRIBUTING.md) for secure coding standards and I18n practices
9. See [docs/USAGE.md](docs/USAGE.md), [docs/TWO_FACTOR_AUTH.md](docs/TWO_FACTOR_AUTH.md), and [CHANGELOG.md](CHANGELOG.md) for security-related usage, configuration details, and recent changes
