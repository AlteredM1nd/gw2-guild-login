# Changelog

All notable changes to the GW2 Guild Login plugin will be documented in this file.

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
