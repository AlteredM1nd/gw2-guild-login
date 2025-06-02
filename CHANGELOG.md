# Changelog

All notable changes to the GW2 Guild Login plugin will be documented in this file.

## [2.6.1] - 2025-06-02

### Improved
- **Strict Type Safety & Static Analysis:**
  - Comprehensive refactor of `GW2_User_Handler` for full PHPStan compliance and strict type safety.
  - All variables from WordPress APIs and plugin methods are now strictly typed before use.
  - Dynamic property access on WordPress user objects is guarded and annotated for static analysis.
  - All output and function calls now use strictly typed variables, eliminating mixed-type errors.
  - **Static analysis suppressions:** Added robust PHPStan ignore rules to eliminate unavoidable template/static analysis warnings in WordPress context (e.g., variable scope in templates, unknown WP classes).
  - All persistent PHPStan warnings are now either real bugs or intentionally suppressed for WordPress template edge cases.
  - Documentation (README, USAGE, CONTRIBUTING) updated to reflect new static analysis and type safety practices.
- **Security & Maintainability:**
  - Improved code clarity and future-proofing without changing any business logic or user-facing behavior.
  - Enhanced static analysis ensures safer plugin updates and easier auditing.

### Notes
- No business logic or feature changes; this release is a code quality and security hardening update.
- All static analysis suppressions are intentional and documented; future real bugs will stand out in PHPStan output.

## [2.6.0] - 2025-06-01

### Added
- Support for multiple target Guild IDs (comma-separated) in admin settings
- Robust API response caching using WordPress transients (configurable, can be bypassed/cleared)
- Developer utility to clear API cache for a given endpoint and API key
- PHPUnit test coverage for API caching and cache clearing logic
- Polyfill for cache functions in PHPUnit for non-WordPress test environments
- Comprehensive documentation updates

### Breaking
- **PHP 8.0+ Required:** As of v2.6.0, PHP 8.0 or higher is required for security and 2FA dependencies.

### Improved
- **Security Dashboard:** Now displays encryption status (✔ Active/✖ Insecure), brute-force stats, and admin warnings for weak/missing keys.
- **Password/API Key Recovery:** Magic-link reset system via `/gw2-recovery/` (JWT-based, 1-hour expiry); FAQ and recovery page added.
- **API Key Encryption at Rest:** All API keys are now encrypted using AES-256-CBC. Migration utility automatically encrypts all existing plaintext keys; legacy keys are securely deleted post-migration.
- **Cache Management:** User-specific cache keys prevent collisions; cache is auto-invalidated on login, logout, API key update, and guild membership changes.
- **Brute-force Protection:** Login attempts are rate-limited; repeated failures result in temporary lockout (5 attempts in 15 minutes = 10 minute block). All events are logged and stats shown on the dashboard.
- **Debugging:** Security and cache events are logged in debug mode for easier troubleshooting.
- Admins are proactively warned about weak or missing encryption keys.
- Enhanced error handling and security for API key management and user meta.
- Updated admin UI and settings for clarity on multi-guild and caching features.
- Improved README and documentation for clarity and completeness.
- UX & Polish: Login button shortcode, dashboard widget, cache controls, and clearer settings.

## [2.5.0] - 2025-06-01
### Added/Changed
- **Admin Appearance Customization**: New "Appearance & Branding" section in settings
  - Primary and accent color pickers (with live preview)
  - Custom logo upload (shown on login and dashboard)
  - Custom welcome/help text (shown on login and dashboard)
  - Force dark mode toggle (override user preference)
- **Frontend Support**
  - Login and dashboard pages now display the selected logo and welcome text
  - Styles automatically update based on admin color/dark mode settings
- **Modernized Admin UI**
  - New admin CSS with CSS variables and dark mode support
  - Smoother transitions, improved accessibility, and mobile/tablet polish
- **Accessibility & UX**
  - Improved ARIA attributes, screen reader text, and keyboard navigation
  - Floating labels and better focus states for form fields
- **Bugfixes & Code Quality**
  - Fixed all CSS syntax and lint errors
  - Refactored JS output for media uploader to avoid linter confusion
  - General code cleanup and documentation improvements

## [2.4.1] - 2025-05-31
### Added/Changed
- Fully refactored main plugin file to be object-oriented; removed all procedural code from bootstrap.
- All shortcodes, AJAX handlers, and hooks are now registered via dedicated classes.
- Legacy procedural functions for login/logout and content protection are now handled by GW2_Login_Shortcode and GW2_2FA_Handler classes.
- PHPUnit test files updated to include Composer autoload and resolve unknown class warnings.
- Syntax errors and stray code blocks removed from main plugin file.
- All plugin initialization and registration logic delegated to singleton classes.
- Improved code maintainability and adherence to WordPress best practices.
- Cleaned up messaging and TODOs for future class-based user messaging refactor.
- Updated documentation to reflect new OOP architecture.

### Security, I18n, and Code Quality Audit
- **Comprehensive Security Audit**: Systematic review and hardening of all input sanitization, output escaping, nonce verification, and capability checks across the plugin
- **Internationalization (I18n) Coverage**: All user/admin-facing strings in PHP and templates now fully wrapped in translation and escaping functions
- **Template & AJAX Handler Review**: Ensured all templates and AJAX endpoints use proper escaping and localization
- **PHPDoc & Coding Standards**: Improved PHPDoc documentation, fixed inline comments, and enforced WordPress coding standards throughout
- **Naming Consistency**: Standardized class, method, and variable names for maintainability
- **Static Analysis**: Ran PHPStan at max level; resolved all reported issues
- **Debug Code Removal**: Eliminated all debug/error_log code and unreachable code
- **Changelog & Documentation**: Updated all documentation for new features, security, and code quality improvements

## [2.4.0] - 2025-05-30

### Added
- **New Admin Interface**: Completely redesigned admin area with a dedicated dashboard
  - Centralized access to all plugin features
  - Intuitive navigation with a top-level menu
  - At-a-glance guild status and activity
  - Responsive design for all screen sizes

- **Two-Factor Authentication (2FA)**: Initial implementation of two-factor authentication for enhanced security
  - Basic TOTP support for authenticator apps (Google Authenticator, Authny, etc.)
  - Backup code generation and verification
  - Basic trusted device support via cookies (30-day remember me)
  - Simple setup interface (full wizard coming in a future update)
  - Admin controls for enabling/disabling 2FA per user

- **Guild Rank Access Control**: Restrict content based on guild rank
  - Shortcode support for rank-restricted content
  - Admin settings for guild ID and API key configuration
  - Caching system to minimize API calls
  - Customizable access denied messages

- **User Management**
  - New user dashboard with account overview
  - Guild membership information display
  - Active sessions management with the ability to revoke sessions
  - Improved user profile integration

- **Security Enhancements**
  - Encrypted storage of 2FA secrets
  - Rate limiting for login attempts
  - Secure backup code generation and handling
  - Automatic session management for trusted devices

## [2.3.0] - 2025-05-29

### Fixed
- Fixed parameter mismatch in `login_user` method to properly handle remember me functionality
- Updated `update_user_meta` to return proper success/error responses
- Enhanced `get_user_by_gw2_account_id` with better type checking and error handling
- Fixed potential issues with user session management
- Addressed PHP warnings and notices in the user handler class
- Improved error handling and validation throughout the authentication process
- Ensured proper return types for all user-related methods
- Added input sanitization for improved security
- Fixed issues with the welcome email sending process
- Improved error messages and user feedback
- Fixed potential security vulnerabilities in form handling
- Resolved issues with user session management
- Fixed compatibility issues with various WordPress themes
- Addressed PHP warnings and notices
- Fixed issues with user role assignments
- Resolved caching-related issues with API responses
- Fixed issues with the login/logout process
- Addressed issues with redirects after login 

### Added
- New `GW2_Login_Shortcode` class for better code organization
- Comprehensive form validation with client and server-side checks
- AJAX form submission for better user experience
- Responsive login form with improved UI/UX
- Enhanced security with nonce verification and input sanitization
- Session management system for secure user authentication
- Rate limiting to prevent brute force attacks
- Custom error messages and user feedback system
- Support for custom redirects after login
- "Remember Me" functionality
- Mobile-friendly design with responsive breakpoints
- Comprehensive documentation in README.md
- New shortcode attributes for better customization
- Support for custom user meta storage
- Developer hooks and filters for extensibility

### Changed
- Refactored login form handling into a dedicated class
- Improved error handling and user feedback
- Enhanced security measures throughout the codebase
- Better organization of frontend assets (CSS/JS)
- Updated documentation with new features and examples
- Improved code quality and maintainability
- Enhanced session management and security
- Better handling of API responses and errors

## [2.2.1] - 2025-05-29

### Added
- Added comprehensive documentation files (CONTRIBUTING.md, SECURITY.md, CHANGELOG.md)
- Added GitHub issue templates for bugs and feature requests
- Added reference to CHANGELOG.md in README.md

### Changed
- Moved changelog from README.md to dedicated CHANGELOG.md
- Improved project documentation structure

## [2.2.0] - 2025-05-29

### Added
- Added PHPStan static analysis tool for code quality assurance
- Configured PHP_CodeSniffer for WordPress coding standards compliance
- Added proper documentation for all functions and methods
- Added proper WordPress stubs for better IDE support
- Added more detailed error logging

### Changed
- Improved error handling and type safety throughout the codebase
- Enhanced plugin initialization process
- Updated development dependencies
- Improved error messages and debugging information

### Fixed
- Fixed constant definition issues in the main plugin file
- Fixed potential issues with path definitions

## [2.1.1] - 2025-05-29

### Fixed
- Fixed user handler initialization and access
- Improved error handling in guild membership verification
- Fixed duplicate method declarations in GW2_Guild_Login class
- Enhanced template security with better error handling
- Added proper type checking and return types

## [2.1.0] - 2025-05-28

### Added
- Added Guild Members Only page template for full page access restriction
- Added template registration and loading system

### Changed
- Improved template handling and documentation
- Updated admin interface to support page templates

## [2.0.0] - 2025-05-28

### Added
- Complete rewrite with improved architecture
- Added admin interface for settings
- Added guild membership verification
- Added shortcode for protected content

### Changed
- Improved security and error handling

## [1.0.0] - 2025-05-28

### Added
- Initial release
- Basic API key authentication
- Simple login form
- Enhanced security features
