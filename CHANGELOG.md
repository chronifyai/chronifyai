# Changelog

All notable changes to the ChronifyAI Moodle plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.1] - 2025-12-23

### Added
- Initial beta release of ChronifyAI Moodle plugin
- Core functionality for course archiving and compliance management
- Integration with ChronifyAI API
- Setup wizard for configuration
- Course backup and restore capabilities
- Transcript export functionality
- Course report generation
- Privacy API implementation with GDPR/FERPA compliance
- User consent mechanisms for data transmission
- Comprehensive PRIVACY.md documentation

### Features
- **PHP 7.3+ Compatibility**: Full support for PHP 7.3, 7.4, 8.0, 8.1, and 8.2
  - No PHP 8.0+ specific features used
  - Compatible with legacy environments

- **Moodle 4.0+ Compatibility**: Designed for modern Moodle versions
  - Minimum: Moodle 4.0 (April 2022)
  - Uses modern `core_external\` namespace
  - Compatible through Moodle 4.x and beyond

- **Database Compatibility**: Works with various database systems
  - MySQL 5.7+ (with automatic fallback for window functions)
  - MySQL 8.0+ (full feature support)
  - MariaDB 10.1+ (with automatic fallback)
  - MariaDB 10.2+ (full feature support)
  - PostgreSQL 9.6+

- **Performance Optimizations**: Built-in safeguards for large installations
  - Query limits (10,000 records max)
  - Time-range restrictions (2 years for log queries)
  - Prevents performance issues on large Moodle sites

- **Data Validation**: Comprehensive error checking
  - Percentage calculations properly clamped to 0-100 range
  - Division-by-zero checks
  - Completion tracking validation
  - Proper null handling throughout
  - Added fallback logic for MySQL 5.7 and MariaDB < 10.2 that don't support window functions
  - Implemented try-catch approach for graceful degradation when LEAD/LAG functions unavailable
  - Added approximate time calculation fallback based on log entry count

- **Performance Improvements**: Added safeguards against logstore query performance issues
  - Limited logstore queries to last 2 years of data to prevent table scans on large installations
  - Added LIMIT clauses (10,000 records) to prevent runaway queries
  - Reduced risk of system hangs on large Moodle installations

- **Data Validation**: Added comprehensive percentage validation
  - All percentage calculations now properly clamped to 0-100 range
  - Added division-by-zero checks before percentage calculations
  - Prevents invalid percentage values from being returned

- **Completion Tracking**: Added proper completion tracking validation
  - Plugin now checks if completion tracking is enabled before querying completion data
  - Returns "Not Available" status when completion tracking is disabled
  - Prevents errors on courses without completion tracking enabled

### Technical Details

#### Files Included:
- `version.php` - Plugin metadata and version information
- `README.md` - Installation and setup guide
- `CHANGELOG.md` - Version history (this file)
- `DEPLOYMENT.md` - Deployment guide and validation checklist
- `PRIVACY.md` - Privacy and compliance documentation
- `API.md` - API integration documentation
- `classes/service/transcripts.php` - Transcript export functionality with:
  - PHP 7.3+ compatible syntax
  - Database compatibility (MySQL 5.7+, MariaDB, PostgreSQL)
  - Performance safeguards (query limits, time restrictions)
  - Comprehensive data validation
- `classes/external/connection.php` - API connection handling
- `classes/service/course_backup.php` - Course backup functionality
- And all other core plugin files

#### Installation Notes:
1. Extract to `{moodle_root}/local/chronifyai/`
2. Visit Site Administration → Notifications
3. Complete setup wizard
4. Configure API credentials
5. Test connection

#### Testing Recommendations:
After installation, administrators should test:
- Connection to ChronifyAI API (verify wizard works)
- Course backup operations
- Course restore operations
- Transcript export functionality
- Report generation

Particularly test on:
- Your target Moodle version (4.0+)
- Your PHP environment (7.3+)
- Your database system (MySQL/MariaDB/PostgreSQL)

---

## Future Releases

Version 0.0.2 and beyond will be documented here as the plugin evolves.

