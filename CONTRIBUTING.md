# Contributing to GW2 Guild Login

Thank you for your interest in contributing to GW2 Guild Login 2.6.0! We appreciate your time and effort in helping improve this plugin.

## How to Contribute

1. Fork the repository
2. Create a new branch for your feature/fix: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Run tests if applicable
5. Submit a pull request

## Code Standards & Best Practices

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Write meaningful commit messages
- Document your code with PHPDoc blocks
- Add or update tests when applicable (especially for caching, multi-guild logic, and new features)
- Use proper input sanitization and output escaping for all user data
- Always check nonces and user capabilities for privileged actions
- Ensure all user/admin-facing strings are wrapped in translation functions and properly escaped (see I18n section below)
- Maintain consistent naming conventions for classes, functions, and variables

## Reporting Issues & Security

When reporting issues, please include:
- WordPress version
- PHP version
- Steps to reproduce the issue
- Any error messages
- Screenshots if applicable

For security vulnerabilities, please follow the [Security Policy](SECURITY.md) and do not post publicly.

## Development Environment

- PHP 7.4+ (8.0+ recommended)
- WordPress 5.8+
- Composer for dependency management
- Node.js 14+ (for asset building)

## Running Static Analysis & Tests

### PHPStan (Static Analysis)

Run static analysis before submitting a pull request:

```bash
composer install
vendor/bin/phpstan analyse
```

### Tests

```bash
composer test
```

## Internationalization (I18n)

- All user/admin-facing strings must be wrapped in translation functions: `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, etc.
- Escape all output using `esc_html()`, `esc_attr()`, `esc_url()` as appropriate.
- Use the plugin text domain: `gw2-guild-login`.
- See the [WordPress I18n Handbook](https://developer.wordpress.org/plugins/internationalization/) for more info.

## Naming Conventions

- Classes: `GW2_*` or `GW2GuildLogin\*`
- Functions: `gw2_*` or camelCase within classes
- Variables: descriptive, snake_case or camelCase (consistent within context)
- Constants: UPPERCASE_WITH_UNDERSCORES

## Code of Conduct

Be respectful and considerate of others. We follow the [WordPress Code of Conduct](https://wordpress.org/about/conduct/).
