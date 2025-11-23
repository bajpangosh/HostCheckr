# Performance Check Feature

## Overview

The Performance Check feature helps you diagnose why your WordPress site might be running slow. It analyzes various aspects of your WordPress installation and provides actionable recommendations to improve performance.

## What It Checks

### 1. Database Performance
- **Database Size**: Monitors total database size and alerts if it's too large
- **Table Overhead**: Identifies tables that need optimization
- **Post Revisions**: Checks for excessive post revisions that bloat the database

### 2. Plugin Performance
- **Plugin Count**: Monitors the number of active plugins
- Alerts when you have too many plugins (>30 critical, >20 warning)
- Recommends consolidating functionality

### 3. Theme & Page Builders
- **Page Builder Detection**: Identifies if you're using page builders like:
  - Elementor
  - Divi
  - Beaver Builder
  - WPBakery
  - Oxygen
- Provides optimization tips for page builder usage

### 4. Caching Configuration
- **Object Caching**: Checks if Redis or Memcached is active
- **Page Caching**: Detects popular caching plugins:
  - WP Super Cache
  - W3 Total Cache
  - WP Rocket
  - LiteSpeed Cache
  - WP Fastest Cache

### 5. Server Resources
- **PHP Memory Limit**: Ensures adequate memory allocation (minimum 256MB)
- **Max Execution Time**: Checks for sufficient execution time (minimum 60s)
- **PHP Version**: Recommends upgrading to PHP 8.0+ for better performance

### 6. Autoload Data
- **Autoload Size**: Monitors data loaded on every page request
- Alerts when autoload data exceeds 1MB (critical) or 512KB (warning)
- Recommends cleanup strategies

## Issue Severity Levels

### Critical (Red)
Issues that significantly impact performance and should be addressed immediately:
- No object caching
- No page caching plugin
- More than 30 active plugins
- PHP memory limit below 256MB
- Outdated PHP version (below 8.0)
- Autoload data over 1MB

### Warning (Orange)
Issues that may impact performance and should be addressed soon:
- Large database size (>1GB)
- Database tables with overhead
- Excessive post revisions (>1000)
- 20-30 active plugins
- Low max execution time
- Autoload data 512KB-1MB

### Info (Blue)
Informational notices about your configuration:
- Page builder detection
- General optimization tips

## How to Use

1. Navigate to **HostCheckr > Performance Check** in your WordPress admin
2. The page will automatically analyze your site
3. Review the issues categorized by severity
4. Follow the recommendations for each issue
5. Re-run the check after making changes to see improvements

## Common Recommendations

### For Database Issues
- Install and use **WP-Optimize** or similar plugin to clean up database
- Limit post revisions by adding to `wp-config.php`:
  ```php
  define('WP_POST_REVISIONS', 5);
  ```
- Regularly optimize database tables

### For Plugin Issues
- Audit your plugins and remove unused ones
- Look for plugins that combine multiple features
- Consider replacing multiple plugins with a single comprehensive solution

### For Caching Issues
- Install a caching plugin like **WP Rocket** or **LiteSpeed Cache**
- Set up Redis or Memcached for object caching
- Configure your caching plugin properly

### For Server Resource Issues
- Contact your hosting provider to increase PHP memory limit
- Upgrade to a better hosting plan if needed
- Consider upgrading PHP version (always test in staging first)

### For Autoload Data Issues
- Use **Query Monitor** plugin to identify large autoloaded options
- Review and clean up unnecessary autoloaded data
- Contact plugin developers if their plugins are adding excessive autoload data

## Best Practices

1. **Regular Monitoring**: Check performance regularly, especially after:
   - Installing new plugins
   - Theme changes
   - Major WordPress updates

2. **Staging Environment**: Always test optimizations in a staging environment first

3. **Backups**: Create backups before making significant changes

4. **Incremental Changes**: Make one change at a time to identify what helps most

5. **Professional Help**: For critical issues or if you're unsure, consider hiring a WordPress performance expert

## Technical Details

The Performance Check feature:
- Runs entirely on your server (no external API calls)
- Uses WordPress core functions and database queries
- Caches results where appropriate
- Is safe to run multiple times
- Does not modify your site (read-only analysis)

## Privacy & Security

- No data is sent to external servers
- All analysis happens locally on your WordPress installation
- Requires administrator privileges to access
- Results are only visible to site administrators

## Support

If you need help interpreting results or implementing recommendations:
- Visit: [https://hostcheckr.kloudboy.com/support](https://hostcheckr.kloudboy.com/support)
- Contact: [KloudBoy Professional Services](https://kloudboy.com)

---

**HostCheckr** - Know Your Hosting. Instantly.
