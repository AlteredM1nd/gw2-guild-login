# Security Policy

## Supported Versions

| Version | Supported          | Security Updates Until |
| ------- | ------------------ | --------------------- |
| 2.4.x   | :white_check_mark: | TBD                   |
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
- **Data Sanitization**: Comprehensive input validation and output escaping
- **Secure Cookies**: HTTP-only, secure, and SameSite=Lax flags set for all cookies

### Authentication Security
- **Two-Factor Authentication (2FA)**: Basic TOTP-based 2FA support (Phase 1)
  - Time-based One-Time Password (TOTP) algorithm (RFC 6238)
  - Compatible with TOTP-compatible authenticator apps
  - Fixed 6-digit codes
  - 30-second time step (not currently configurable)
  - Basic trusted device support via cookies
  - Backup code generation and verification
- **Rate Limiting**: Protection against brute force attacks with exponential backoff
- **Session Management**:
  - Session ID regeneration on login and privilege changes
  - Configurable session lifetime
  - Concurrent session control
  - Device fingerprinting for session binding
- **Secure Credential Storage**:
  - API keys encrypted with AES-256-CBC
  - Passwords hashed using WordPress's built-in password hashing (PHP's password_hash() with bcrypt)
  - Secure storage of 2FA secrets and backup codes
- **CSRF Protection**: Nonce verification for all form submissions and AJAX requests
- **Trusted Device Management**:
  - Secure cookie-based device recognition
  - Configurable trust duration (default: 30 days)
  - Device revocation capability

### API Security
- **Input Validation**: Strict validation of all API inputs
- **Output Escaping**: Proper escaping of all dynamic content
- **Error Handling**: Generic error messages to prevent information leakage
- **Rate Limiting**: Respects GW2 API rate limits with local caching

### WordPress Integration
- **Capability Checks**: Proper user capability verification
- **Nonce Verification**: For all form submissions and AJAX requests
- **Data Sanitization**: WordPress core functions for data handling
- **Hooks and Filters**: Secure extension points for developers

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
