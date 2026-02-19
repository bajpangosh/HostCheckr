# Changelog

All notable changes to HostCheckr will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-02-19

### Fixed
- Resolved PHP deprecation warning in server uptime parsing (`float-string` implicit conversion precision loss)
- Restored Performance page rendering on environments with strict deprecation/error display settings

## [1.0.2] - 2026-02-19

### Added
- Live Database Monitor in the Performance tab with real-time polling
- Live monitor metrics for DB response time, autoload size, revisions, thread count, slow queries, and database size
- Slow query pattern analysis with normalized signatures and top-pattern grouping
- Live monitor settings panel with controls for enable/disable, polling interval, and max pattern count
- Optional Lightweight Mode toggle to skip heavy `information_schema` and `SHOW STATUS` probes
- Lightweight Mode default enabled for new installs
- In-dashboard restricted-hosting warning panel for blocked probes and limited capabilities
- One-click copy button for generated `wp-config.php` fix snippets

### Enhanced
- Improved submenu routing for tab-based navigation in wp-admin
- Better accessibility and keyboard navigation for tab interfaces
- Improved empty states with reset actions
- Mobile UX improvements for spacing, tap targets, and small-screen readability
- Mobile performance tuning by reducing expensive effects on touch devices
- Live monitor fallback behavior for restricted/shared hosting

### Security
- Added secure AJAX endpoint for saving live monitor settings with nonce and capability checks
- Improved SQL safety by using prepared queries for schema lookups where applicable
- Additional runtime hardening for restricted environments (`shell_exec`/file access guards)

## [1.0.1] - 2024-11-23

### Added
- **Performance Check Tab**: New comprehensive performance diagnostics feature
- Database performance analysis (size, overhead, revisions)
- Plugin count monitoring and recommendations
- Theme and page builder detection
- Caching configuration checks (object cache and page cache)
- Server resource validation (PHP memory, execution time, PHP version)
- Autoload data size monitoring
- Detailed recommendations for each performance issue
- Severity-based issue categorization (Critical, Warning, Info)
- Visual performance dashboard with issue statistics

### Enhanced
- Better performance issue detection and reporting
- Actionable recommendations for WordPress optimization
- Improved user interface for performance diagnostics

## [1.0.0] - 2024-01-01

### Added
- Initial release of HostCheckr
- System health overview dashboard
- PHP configuration analysis with requirements checking
- PHP extension monitoring and status reporting
- Server resource monitoring (memory, CPU, storage)
- Hosting environment detection and information
- WordPress compatibility checks
- Real-time system information refresh
- Export functionality for system reports
- Mobile-responsive admin interface
- Translation ready with .pot file
- Accessibility compliant design
- Clean, modern WordPress admin interface
- Support for WordPress 5.0+ and PHP 7.4+

### Security
- Proper capability checks for admin access
- Input sanitization and output escaping
- Nonce verification for AJAX requests
- Direct file access prevention

### Developer Features
- WordPress coding standards compliance
- Proper plugin activation/deactivation hooks
- Clean uninstall process
- Extensible architecture for future enhancements
