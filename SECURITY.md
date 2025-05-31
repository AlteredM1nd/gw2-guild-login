# Security Policy

_Last audited: 2025-05-31_

## Supported Versions

**2.4.1:** Main plugin file is now fully object-oriented. All authentication, shortcode, and 2FA logic is handled by dedicated classes for improved security and maintainability. This version introduces a class-based architecture, significantly enhancing the plugin's security posture.


| Version | Supported          | Security Updates Until |
| ------- | ------------------ | --------------------- |
| 2.4.1   | :white_check_mark: | 2026-05-31            |
| 2.3.x   | :white_check_mark: | 2025-11-29            |
| 2.2.x   | :x:                | 2025-08-29            |
| < 2.2   | :x:                | -                     |

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

## Known Security Considerations

### API Security
- The plugin requires the `account` and `guilds` permissions from the GW2 API
- API keys are stored encrypted in the database using AES-256-CBC
- All API requests are made over HTTPS
- API responses are cached to respect rate limits

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
9. See [docs/USAGE.md](docs/USAGE.md) and [docs/TWO_FACTOR_AUTH.md](docs/TWO_FACTOR_AUTH.md) for security-related usage and configuration details
