# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.2.x   | :white_check_mark: |
| < 2.2   | :x:                |

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

- API key encryption
- Input validation and sanitization
- Nonce verification for forms
- Proper capability checks
- Secure session handling

## Known Security Considerations

- The plugin requires the `account` and `guilds` permissions from the GW2 API
- API keys are stored encrypted in the database
- User sessions are managed securely by WordPress
