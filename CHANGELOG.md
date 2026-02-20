# Changelog

All notable changes to the ChronifyAI plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [5.1.0] - 2026-02-17

### Changed
- Redesigned setup wizard Step 1 with onboarding guidance — added "Before you begin" checklist directing users to create a ChronifyAI account and locate API credentials before proceeding
- Updated Step 1 CTA from "Let's Set It Up" to "I Have My Credentials — Continue Setup" to set correct expectations
- Shortened Step 1 description to a single concise sentence
- Updated Step 2 description to reference the ChronifyAI Integrations page for credentials
- Updated Step 2 info box to point to Integrations page instead of "Settings → API Keys"
- Centered content on all four wizard steps with consistent layout (no dots, no background image, top-aligned)
- Removed feature cards (Course Backup, Course Restore, Transcript Export) from Step 3 — features are bundled under the enable toggle
- Removed copyright footer ("©2025 ChronifyAI, Inc. All Rights Reserved.") from all steps
- Restricted data transmission warning to plugin settings page only (was showing on all admin pages and wizard steps)
- Expanded supported Moodle versions from 4.0–4.3 to 4.0–5.1

### Added
- "← Previous" navigation button on Steps 2, 3, and 4
- New language strings for prereq checklist, create account link, and previous button
- CI testing for Moodle 4.4, 4.5, 5.0, and 5.1 with PHP 8.1–8.3

### Fixed
- Privacy provider interface ordering to satisfy Moodle CodeSniffer alphabetical requirement
- Bumped PostgreSQL CI image from 13 to 14 for Moodle 5.0+ compatibility
- Updated actions/checkout from v3 to v4

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
