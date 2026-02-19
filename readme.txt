=== HostCheckr ===
Contributors: bajpangosh
Tags: hosting, system-info, performance, health-check, server-monitoring
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Instantly check if your hosting is slowing down your WordPress. Know Your Hosting. Instantly.

== Description ==

HostCheckr is a comprehensive WordPress plugin that provides instant insights into your hosting environment and server performance. Whether you're a developer, site administrator, or hosting provider, HostCheckr gives you the tools to quickly assess your WordPress hosting setup.

**Key Features:**

* **System Health Overview** - Get an instant snapshot of your server's health status
* **PHP Configuration Analysis** - Check PHP settings against WordPress requirements
* **Extension Monitoring** - Verify all required and recommended PHP extensions
* **Server Resource Monitoring** - Monitor memory, CPU, and storage usage
* **Hosting Environment Detection** - Identify your hosting provider and server details
* **WordPress Compatibility Check** - Ensure your setup meets WordPress standards
* **Export System Reports** - Generate detailed reports for troubleshooting
* **Real-time Monitoring** - Refresh system information on demand
* **Restricted Hosting Safe Fallbacks** - Clear warnings for blocked probes with safe fallback checks
* **WP-Config Fix Assistant** - Backup-first `wp-config.php` snippets with one-click copy support
* **Live DB Monitor Settings** - Configure polling interval, pattern count, and Lightweight Mode from dashboard

**Perfect For:**

* WordPress developers debugging hosting issues
* Site administrators monitoring server health
* Hosting providers offering WordPress support
* Anyone wanting to optimize their WordPress performance

**Technical Highlights:**

* Clean, modern admin interface
* Mobile-responsive design
* Accessibility compliant
* Translation ready
* No external dependencies
* Lightweight and fast

== Installation ==

**Automatic Installation (Recommended)**
1. Log in to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "HostCheckr"
4. Click "Install Now" and then "Activate"

**Manual Installation**
1. Download the plugin zip file
2. Upload the plugin files to the `/wp-content/plugins/hostcheckr` directory
3. Activate the plugin through the 'Plugins' screen in WordPress

**Getting Started**
1. After activation, navigate to 'HostCheckr' in your WordPress admin menu
2. View your system health overview on the main dashboard
3. Explore different tabs for detailed information
4. Use the export feature to generate system reports

== Frequently Asked Questions ==

= Does this plugin affect my site's performance? =

No, HostCheckr only runs in the WordPress admin area and doesn't affect your front-end site performance. It only gathers system information when you access the plugin dashboard.

= Can I export system reports? =

Yes, you can export detailed system reports in text format for sharing with hosting providers or developers.

= Is this plugin compatible with all hosting providers? =

Yes, HostCheckr works with any hosting provider that supports WordPress. It automatically detects your hosting environment and provides relevant information.

= My host blocks some server reads/commands. Will HostCheckr still work? =

Yes. HostCheckr detects restricted hosting environments, shows clear warnings, and uses safe fallback checks where available.

= What is Lightweight Mode? =

Lightweight Mode skips heavy database probes (`information_schema` and `SHOW STATUS` queries) to reduce load on restrictive/shared hosting. It is enabled by default for new installs and can be changed in Live Monitor Settings.

= Does this plugin require any external services? =

No, HostCheckr works entirely within your WordPress installation and doesn't connect to any external services.

= Can I customize the requirements and recommendations? =

The current version uses WordPress-recommended standards. Future versions may include customization options.

= Can HostCheckr edit wp-config.php automatically? =

No. HostCheckr provides copy-ready `wp-config.php` snippets and backup-first guidance. Apply changes manually via File Manager, SFTP, or SSH.

== Screenshots ==

1. System Health Overview - Get an instant snapshot of your server's status with comprehensive health monitoring and quick access to professional optimization services
2. PHP Configuration Analysis - Detailed view of PHP settings with recommendations to optimize your WordPress hosting environment
3. PHP Extensions Monitor - Check all required and recommended PHP extensions with clear status indicators and installation guidance
4. Server Resource Monitoring - Monitor memory, CPU, and storage usage with real-time performance indicators and optimization suggestions
5. Hosting Environment Detection - Comprehensive hosting provider identification and server environment analysis for better optimization decisions
6. WordPress Compatibility Check - Ensure your WordPress setup meets all requirements with detailed configuration analysis and recommendations

== Changelog ==

= 1.0.3 =
* Fixed PHP deprecation warning in server uptime parsing
* Restored Performance page rendering when deprecations are displayed as notices

= 1.0.2 =
* Live Database Monitor with real-time polling in Performance tab
* Configurable Live Monitor Settings (enable/disable, interval, max pattern count)
* Slow query pattern analysis with normalized signatures
* Lightweight Mode option to skip heavy probes on restrictive/shared hosting
* Lightweight Mode enabled by default for new installs
* Restricted-host warning panel and safer fallback behavior
* `wp-config.php` fix copy button with backup-first guidance
* Accessibility, mobile UX, and mobile performance improvements

= 1.0.1 =
* **Performance Check Tab**: New comprehensive performance diagnostics feature
* Database performance analysis (size, overhead, revisions)
* Plugin count monitoring and recommendations
* Theme and page builder detection
* Caching configuration checks (object cache and page cache)
* Server resource validation (PHP memory, execution time, PHP version)
* Autoload data size monitoring
* Detailed recommendations for each performance issue
* Severity-based issue categorization (Critical, Warning, Info)
* Visual performance dashboard with issue statistics
* Better performance issue detection and reporting
* Actionable recommendations for WordPress optimization
* Improved user interface for performance diagnostics
* Restricted hosting warnings and safe fallbacks for blocked environments
* Backup-first `wp-config.php` recommendation snippets with one-click copy support
* Improved dashboard accessibility, keyboard navigation, and mobile UX performance
* Live Database Monitor with configurable polling and slow query pattern analysis
* Lightweight Mode (default ON for new installs) for better performance on shared/restrictive hosting

= 1.0.0 =
* Initial release
* System health overview dashboard
* PHP configuration analysis
* Extension monitoring
* Server resource monitoring
* Hosting environment detection
* WordPress compatibility checks
* Export functionality
* Mobile-responsive interface

== Upgrade Notice ==

= 1.0.3 =
Fixes a deprecation warning that could break Performance page rendering on some hosts.

= 1.0.2 =
Live DB Monitor, settings controls, and Lightweight Mode (default ON) for better shared-host performance.

= 1.0.1 =
New Performance Check tab and comprehensive system diagnostics.

= 1.0.0 =
Initial release of HostCheckr - Know Your Hosting. Instantly.

== Developer Notes ==

HostCheckr is developed by Bajpan Gosh for KloudBoy. The plugin follows WordPress coding standards and best practices.

For support, feature requests, or bug reports, please visit: https://hostcheckr.kloudboy.com/support

== Privacy Policy ==

HostCheckr does not collect, store, or transmit any personal data. All system information is processed locally within your WordPress installation.
