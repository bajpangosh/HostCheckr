# HostCheckr

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.2-blue.svg)](https://github.com/bajpangosh/hostcheckr)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Know Your Hosting. Instantly.**

Instantly check if your hosting is slowing down your WordPress. HostCheckr provides comprehensive insights into your hosting environment and server performance with a beautiful, modern interface.

## 🚀 Try it Now

### WordPress Playground - Instant Demo

Test HostCheckr instantly in your browser without installing anything. This demo loads the latest version directly from GitHub.

**[Launch HostCheckr Live Demo →](https://playground.wordpress.net/?gh-ensure-auth=yes&ghexport-repo-url=https%3A%2F%2Fgithub.com%2Fbajpangosh%2FHostCheckr&ghexport-content-type=plugin&ghexport-plugin=hostcheckr&ghexport-playground-root=%2Fwordpress%2Fwp-content%2Fplugins%2Fhostcheckr&ghexport-pr-action=create&ghexport-allow-include-zip=no&blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fbajpangosh%2FHostCheckr%2Fmain%2Fblueprint.json)**

**Login credentials:**
- Username: `admin`
- Password: `password`

The playground will:
1. Load the latest code from GitHub
2. Install and activate HostCheckr
3. Set up a test environment

> **Note:** You may be asked to authorize GitHub access to clone the repository into the playground.

## ✨ Features

### 🎯 System Health Overview
- Instant snapshot of your server's health status
- Visual indicators for critical issues and warnings
- Comprehensive health scoring system

### ⚙️ PHP Configuration Analysis
- Check PHP settings against WordPress requirements
- Compare current vs. recommended values
- Detailed configuration recommendations

### 🔌 Extension Monitoring
- Verify all required and recommended PHP extensions
- Clear status indicators for each extension
- Installation guidance for missing extensions

### 📊 Server Resource Monitoring
- Monitor memory, CPU, and storage usage
- Real-time performance indicators
- Resource optimization suggestions

### 🏢 Hosting Environment Detection
- Identify your hosting provider automatically
- Server environment analysis
- Detailed server information

### ✅ WordPress Compatibility Check
- Ensure your setup meets WordPress standards
- Version compatibility verification
- Database configuration analysis

### 📤 Export System Reports
- Generate detailed reports for troubleshooting
- Share with hosting providers or developers
- Text format for easy reading

### 🚀 Performance Diagnostics
- **NEW!** Comprehensive "Why Is My WordPress Slow?" analysis
- Database performance checks (size, overhead, revisions)
- Plugin and theme performance monitoring
- Caching configuration validation
- Server resource optimization recommendations
- Autoload data analysis
- Severity-based issue reporting (Critical, Warning, Info)

### 🛡️ Restricted Hosting Safe Fallbacks
- Detects when hosting security blocks system probes
- Shows clear in-dashboard warnings for restricted capabilities
- Uses safe fallback methods where possible (for example, gateway detection without shell access)
- Prevents restricted environments from appearing as plugin failures
- Includes **Lightweight Mode** (default ON for new installs) to skip heavy DB probes on shared/restrictive hosting

### 🧩 WP-Config Fix Assistant (Backup-First)
- Generates ready-to-use `wp-config.php` fix snippets for key configuration issues
- Shows an explicit backup warning before applying config changes
- Includes one-click **Copy wp-config fix** buttons in recommendation cards
- Provides exact insertion guidance in `wp-config.php`

### 🎨 Modern Interface
- Clean, professional design
- Mobile-responsive layout
- Accessibility compliant
- Improved keyboard accessibility and tab navigation
- Better empty states with quick reset actions
- Mobile UX and performance optimized for touch devices

## 📸 Screenshots

![System Health Overview](assets/screenshots/screenshot-1.png)
*System Health Overview - Get an instant snapshot of your server's status*

![PHP Configuration](assets/screenshots/screenshot-2.png)
*PHP Configuration Analysis - Detailed view of PHP settings with recommendations*

![Extensions Monitor](assets/screenshots/screenshot-3.png)
*PHP Extensions Monitor - Check all required and recommended extensions*

## 🔧 Installation

### Automatic Installation (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Search for "HostCheckr"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/hostcheckr` directory
3. Activate through the **Plugins** screen in WordPress

### Via WP-CLI

```bash
wp plugin install hostcheckr --activate
```

## 🎯 Getting Started

1. After activation, navigate to **HostCheckr** in your WordPress admin menu
2. View your system health overview on the main dashboard
3. Explore different tabs for detailed information:
   - **Overview** - System health summary
   - **System Versions** - PHP, MySQL, WordPress versions
   - **PHP Configuration** - Detailed PHP settings
   - **PHP Extensions** - Installed extensions status
   - **Server Info** - Hosting environment details
   - **Performance Check** - Diagnose why WordPress is slow
4. Use the **Export Report** button to generate system reports
5. Click **Refresh** to update system information
6. For configuration issues, use **Copy wp-config fix** and apply changes only after a backup
7. In **Performance Check**, use **Live Monitor Settings** to control polling interval, pattern count, and Lightweight Mode

## 📋 Requirements

### Minimum Requirements
- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **User Role:** Administrator

### Recommended Requirements
- **WordPress:** Latest version
- **PHP:** 8.1 or higher
- **MySQL:** 8.0 or higher
- **Memory Limit:** 512M
- **Max Execution Time:** 300 seconds



## 🛠️ Development

### Local Development Setup

```bash
# Clone the repository
git clone https://github.com/bajpangosh/hostcheckr.git

# Navigate to your WordPress plugins directory
cd wp-content/plugins/hostcheckr

# Install dependencies (if any)
composer install

# Activate the plugin
wp plugin activate hostcheckr
```

### File Structure

```
hostcheckr/
├── admin/
│   ├── class-hostcheckr-admin.php
│   └── index.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-hostcheckr.php
│   ├── constants.php
│   └── index.php
├── languages/
│   └── hostcheckr.pot
├── docs/
│   └── installation.md
├── hostcheckr.php
├── uninstall.php
├── readme.txt
├── README.md
└── CHANGELOG.md
```

## 🌐 Translation

HostCheckr is translation-ready! Contribute translations:

1. Use the included `hostcheckr.pot` file in the `/languages` directory
2. Translate using [Poedit](https://poedit.net/) or similar tools
3. Save as `hostcheckr-{locale}.mo` and `hostcheckr-{locale}.po`
4. Submit via pull request

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use meaningful variable and function names
- Comment your code appropriately
- Test thoroughly before submitting

## 📝 Changelog

### 1.0.1 - 2024-11-23
- **NEW:** Performance Check tab - Diagnose why WordPress is slow
- Database performance analysis
- Plugin and theme performance monitoring
- Caching configuration checks
- Server resource validation
- Autoload data monitoring
- Detailed optimization recommendations

### 1.0.0 - 2024-11-23
- Initial release
- System health overview dashboard
- PHP configuration analysis
- Extension monitoring
- Server resource monitoring
- Hosting environment detection
- WordPress compatibility checks
- Export functionality
- Mobile-responsive interface

See [CHANGELOG.md](CHANGELOG.md) for complete version history.

## 🐛 Bug Reports

Found a bug? Please report it:

1. Check [existing issues](https://github.com/bajpangosh/hostcheckr/issues)
2. If not found, [create a new issue](https://github.com/bajpangosh/hostcheckr/issues/new)
3. Include:
   - WordPress version
   - PHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable

## 💬 Support

- **Documentation:** [https://hostcheckr.kloudboy.com/docs](https://hostcheckr.kloudboy.com/docs)
- **Support Forum:** [https://hostcheckr.kloudboy.com/support](https://hostcheckr.kloudboy.com/support)
- **Professional Services:** [Contact KloudBoy](https://kloudboy.com)

## 🔒 Privacy

HostCheckr respects your privacy:

- ✅ No data collection
- ✅ No external API calls
- ✅ All processing happens locally
- ✅ No tracking or analytics
- ✅ GDPR compliant

## 📄 License

HostCheckr is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Bajpan Gosh**
- Website: [KloudBoy](https://kloudboy.com)
- Plugin URI: [HostCheckr](https://hostcheckr.kloudboy.com)

## 🙏 Acknowledgments

- WordPress community for excellent documentation
- All contributors and testers
- Users who provide valuable feedback

---

**Made with ❤️ by [KloudBoy](https://kloudboy.com)**

*Know Your Hosting. Instantly.*
