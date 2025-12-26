# Changelog

All notable changes to the ChronifyAI plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2025-01-27

### Fixed
- Fixed critical PHP error in setup wizard - undefined $form variable (#5)
- Fixed PostgreSQL compatibility in transcript queries - added c.timemodified to SELECT (#3)
- Added 22 missing language strings for wizard, settings, privacy, and UI components (#6)
- Replaced 20 hard-coded strings with proper get_string() calls for full internationalization (#4)
- Fixed incorrect usage of constants::PLUGIN_NAME in get_string() calls

### Added
- Implemented modern Hooks API for Moodle 4.4+ compatibility (#7)
- Added complete GitHub Actions CI/CD pipeline for automated testing (#1)
- Added hook_callbacks.php for modern notification system
- Added db/hooks.php for hook registration
- Added comprehensive language strings for all task and service messages
- Added .github/workflows/ci.yml for automated testing across PHP 8.0, 8.1 and Moodle 4.01, 4.02, 4.03

### Changed
- Improved error messages throughout the application
- Enhanced internationalization support - all strings now use language API
- Updated documentation for hook implementation
- Improved code quality and Moodle standards compliance

### Technical Details
- PostgreSQL query now includes all ORDER BY columns in SELECT list (DISTINCT requirement)
- Setup wizard now properly initializes form variable before use
- All user-facing strings now translatable
- Backward compatible with legacy callback for Moodle < 4.4

## [1.1.0] - 2025-01-20

### Added
- Initial stable release
- Course backup and restore functionality
- Automated transcript export
- Privacy API implementation
- GDPR/FERPA compliance features
- Setup wizard interface
- API authentication
- Scheduled tasks for automation

### Security
- Comprehensive privacy controls
- User data protection mechanisms
- Secure API authentication
- Audit logging

## [1.0.0-beta] - 2025-01-10

### Added
- Beta release for testing
- Core backup/restore features
- Basic API integration
- Initial privacy implementation
