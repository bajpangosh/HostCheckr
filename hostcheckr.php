<?php
/**
 * Plugin Name: HostCheckr
 * Plugin URI: https://hostcheckr.kloudboy.com
 * Description: Instantly check if your hosting is slowing down your WordPress. Know Your Hosting. Instantly.
 * Version: 1.0.3
 * Author: Bajpan Gosh
 * Author URI: https://kloudboy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hostcheckr
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4

 * 
 * HostCheckr - Know Your Hosting. Instantly.
 * Developed by Bajpan Gosh for KloudBoy
 * 
 * This plugin provides comprehensive WordPress hosting environment analysis,
 * system health monitoring, and performance optimization recommendations.
 * 
 * @package HostCheckr
 * @author Bajpan Gosh
 * @since 1.0.0
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HOSTCHECKR_VERSION', '1.0.3');
define('HOSTCHECKR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HOSTCHECKR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HOSTCHECKR_PLUGIN_FILE', __FILE__);

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'hostcheckr_activate');
register_deactivation_hook(__FILE__, 'hostcheckr_deactivate');

/**
 * Plugin activation function
 */
function hostcheckr_activate() {
    // Check minimum requirements
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('HostCheckr requires PHP 7.4 or higher.', 'hostcheckr'));
    }
    
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('HostCheckr requires WordPress 5.0 or higher.', 'hostcheckr'));
    }
    
    // Set default options if needed
    if (!get_option('hostcheckr_version')) {
        add_option('hostcheckr_version', HOSTCHECKR_VERSION);
    }

    if (false === get_option('hostcheckr_live_db_settings', false)) {
        add_option('hostcheckr_live_db_settings', [
            'enabled' => true,
            'interval' => 10,
            'max_patterns' => 5,
            'lightweight_mode' => true,
        ]);
    }
}

/**
 * Plugin deactivation function
 */
function hostcheckr_deactivate() {
    // Clean up if needed (but preserve user data)
    delete_transient('hostcheckr_system_info');
}

class HostCheckr
{
    const TYPE_OK = true;
    const TYPE_ERROR = false;
    const TYPE_WARNING = null;
    
    const TYPE_SUCCESS_CLASS = 'success';
    const TYPE_ERROR_CLASS = 'error';
    const TYPE_INFO_CLASS = 'info';
    const TYPE_WARNING_CLASS = 'warning';

    protected $requirements = [
        'versions' => [
            'php' => '7.4',
            'mysql' => '5.6',
        ],
        'extensions' => [
            'curl' => true,
            'dom' => true,
            'exif' => false,
            'fileinfo' => true,
            'hash' => true,
            'json' => true,
            'mbstring' => true,
            'mysqli' => true,
            'libsodium' => false,
            'openssl' => true,
            'pcre' => true,
            'imagick' => false,
            'zip' => true,
            'filter' => true,
            'gd' => true,
            'iconv' => false,
            'mcrypt' => false,
            'simplexml' => true,
            'xmlreader' => true,
            'zlib' => true,
        ],
        'config' => [
            'file_uploads' => true,
            'max_input_vars' => 1000,
            'memory_limit' => '128M',
            'post_max_size' => '32M',
            'upload_max_filesize' => '32M',
            'max_execution_time' => 30,
            'max_input_time' => 60,
        ],
    ];

    protected $recommended = [
        'versions' => [
            'php' => '8.1',
            'mysql' => '8.0',
        ],
        'extensions' => [
            'curl' => true,
            'dom' => true,
            'exif' => true,
            'fileinfo' => true,
            'hash' => true,
            'json' => true,
            'mbstring' => true,
            'mysqli' => true,
            'libsodium' => true,
            'openssl' => true,
            'pcre' => true,
            'imagick' => true,
            'zip' => true,
            'filter' => true,
            'gd' => true,
            'iconv' => true,
            'mcrypt' => false,
            'simplexml' => true,
            'xmlreader' => true,
            'zlib' => true,
        ],
        'config' => [
            'file_uploads' => true,
            'max_input_vars' => 3000,
            'memory_limit' => '512M',
            'post_max_size' => '128M',
            'upload_max_filesize' => '128M',
            'max_execution_time' => 300,
            'max_input_time' => 300,
        ],
    ];

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('wp_ajax_hostcheckr_live_db_metrics', array($this, 'ajax_live_db_metrics'));
        add_action('wp_ajax_hostcheckr_save_live_db_settings', array($this, 'ajax_save_live_db_settings'));

    }



    /**
     * Add plugin to main admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('HostCheckr - Know Your Hosting. Instantly.', 'hostcheckr'),
            __('HostCheckr', 'hostcheckr'),
            'manage_options',
            'hostcheckr',
            array($this, 'admin_page'),
            $this->get_menu_icon(),
            30 // Position after Dashboard
        );

        // Add submenu items
        add_submenu_page(
            'hostcheckr',
            __('Dashboard', 'hostcheckr'),
            __('Dashboard', 'hostcheckr'),
            'manage_options',
            'hostcheckr',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'hostcheckr',
            __('System Health', 'hostcheckr'),
            __('System Health', 'hostcheckr'),
            'manage_options',
            'hostcheckr-overview',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'hostcheckr',
            __('Server Resources', 'hostcheckr'),
            __('Server Resources', 'hostcheckr'),
            'manage_options',
            'hostcheckr-resources',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'hostcheckr',
            __('Hosting Info', 'hostcheckr'),
            __('Hosting Info', 'hostcheckr'),
            'manage_options',
            'hostcheckr-hosting',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'hostcheckr',
            __('Performance Check', 'hostcheckr'),
            __('Performance Check', 'hostcheckr'),
            'manage_options',
            'hostcheckr-performance',
            array($this, 'admin_page')
        );
    }

    /**
     * Get menu icon (base64 encoded SVG)
     */
    private function get_menu_icon()
    {
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 2L3 7V18H7V13H13V18H17V7L10 2Z" fill="#a7aaad"/>
                <circle cx="10" cy="10" r="2" fill="#a7aaad"/>
                <path d="M8 15H12V17H8V15Z" fill="#a7aaad"/>
            </svg>
        ');
    }



    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_styles($hook)
    {
        if (strpos($hook, 'hostcheckr') === false) {
            return;
        }
        
        wp_enqueue_style(
            'hostcheckr-admin', 
            HOSTCHECKR_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            HOSTCHECKR_VERSION
        );
        
        wp_enqueue_script(
            'hostcheckr-admin', 
            HOSTCHECKR_PLUGIN_URL . 'assets/js/admin.js', 
            array('jquery'), 
            HOSTCHECKR_VERSION, 
            true
        );
        
        // Localize script for translations and AJAX
        wp_localize_script('hostcheckr-admin', 'hostcheckr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hostcheckr_nonce'),
            'poll_interval' => 10000,
            'live_db_settings' => $this->getLiveDbSettings(),
            'strings' => array(
                'loading' => __('Loading...', 'hostcheckr'),
                'error' => __('Error occurred', 'hostcheckr'),
                'copied' => __('Copied to clipboard!', 'hostcheckr'),
                'export_success' => __('Report exported successfully!', 'hostcheckr'),
                'refreshing' => __('Refreshing system information...', 'hostcheckr'),
                'live_db_unavailable' => __('Live database monitoring is temporarily unavailable.', 'hostcheckr'),
                'live_db_saved' => __('Live monitor settings saved.', 'hostcheckr'),
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'hostcheckr'));
        }
        
        $overall_status = $this->getOverallStatus();
        $access_restriction_warnings = $this->getAccessRestrictionWarnings();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab routing
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'hostcheckr';
        $tab_map = [
            'hostcheckr-overview' => 'overview',
            'hostcheckr-resources' => 'resources',
            'hostcheckr-hosting' => 'hosting',
            'hostcheckr-performance' => 'performance',
        ];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation doesn't require nonce
        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : ($tab_map[$current_page] ?? 'overview');
        ?>
        <div class="wrap hostcheckr-wrap">
            <!-- Branded Header -->
            <div class="hostcheckr-header">
                <div class="header-content">
                    <div class="header-text">
                        <div class="brand-section">
                            <div class="brand-logo">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="40" height="40" rx="8" fill="url(#gradient)"/>
                                    <path d="M20 8L10 16V32H16V24H24V32H30V16L20 8Z" fill="white"/>
                                    <circle cx="20" cy="20" r="3" fill="white"/>
                                    <path d="M18 28H22V30H18V28Z" fill="white"/>
                                    <defs>
                                        <linearGradient id="gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#667eea"/>
                                            <stop offset="1" stop-color="#764ba2"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </div>
                            <div class="brand-info">
                                <h1><?php esc_html_e('HostCheckr', 'hostcheckr'); ?></h1>
                                <p class="tagline"><?php esc_html_e('Know Your Hosting. Instantly.', 'hostcheckr'); ?></p>
                            </div>
                        </div>
                        <p class="description"><?php esc_html_e('Instantly check if your hosting is slowing down your WordPress', 'hostcheckr'); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="button button-secondary refresh-btn">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Refresh', 'hostcheckr'); ?>
                        </button>
                        <button class="button button-primary export-btn">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Report', 'hostcheckr'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($access_restriction_warnings)): ?>
                <div class="hostcheckr-restriction-warning">
                    <h3><?php esc_html_e('Limited Server Access Detected', 'hostcheckr'); ?></h3>
                    <p><?php esc_html_e('Your hosting security settings are blocking some system probes. HostCheckr will continue with safe fallbacks where possible.', 'hostcheckr'); ?></p>
                    <ul>
                        <?php foreach ($access_restriction_warnings as $warning): ?>
                            <li><?php echo esc_html($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Quick Stats Bar -->
            <div class="quick-stats-bar">
                <div class="stat-item">
                    <span class="stat-icon dashicons dashicons-wordpress"></span>
                    <div class="stat-content">
                        <span class="stat-label"><?php esc_html_e('WordPress', 'hostcheckr'); ?></span>
                        <span class="stat-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon dashicons dashicons-editor-code"></span>
                    <div class="stat-content">
                        <span class="stat-label"><?php esc_html_e('PHP', 'hostcheckr'); ?></span>
                        <span class="stat-value"><?php echo esc_html(PHP_VERSION); ?></span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon dashicons dashicons-database"></span>
                    <div class="stat-content">
                        <span class="stat-label"><?php esc_html_e('Database', 'hostcheckr'); ?></span>
                        <span class="stat-value"><?php 
                        global $wpdb; 
                        $db_version = wp_cache_get('hostcheckr_db_version');
                        if (false === $db_version) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Getting database version for system info
                            $db_version = $wpdb->get_var("SELECT VERSION()");
                            wp_cache_set('hostcheckr_db_version', $db_version, '', 3600);
                        }
                        echo esc_html(explode('-', $db_version)[0]); 
                        ?></span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon dashicons dashicons-admin-generic"></span>
                    <div class="stat-content">
                        <span class="stat-label"><?php esc_html_e('Server', 'hostcheckr'); ?></span>
                        <span class="stat-value"><?php echo esc_html($this->getWebServer()); ?></span>
                    </div>
                </div>
            </div>

            <!-- System Health Overview -->
            <div class="system-health-overview">
                <div class="health-card main-status status-<?php echo esc_attr($overall_status['class']); ?>">
                    <div class="health-header">
                        <div class="health-icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($overall_status['icon']); ?>"></span>
                        </div>
                        <div class="health-title">
                            <h2><?php esc_html_e('System Health', 'hostcheckr'); ?></h2>
                            <p class="health-status"><?php echo esc_html($overall_status['class'] === 'success' ? __('Excellent', 'hostcheckr') : ($overall_status['class'] === 'warning' ? __('Good', 'hostcheckr') : __('Needs Attention', 'hostcheckr'))); ?></p>
                        </div>
                        <?php if (!empty($overall_status['details'])): ?>
                            <button class="toggle-details-btn" data-target="health-details">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="health-summary">
                        <p><?php echo esc_html($overall_status['message']); ?></p>
                        <?php if (isset($overall_status['summary'])): ?>
                            <div class="health-metrics">
                                <?php if ($overall_status['summary']['critical'] > 0): ?>
                                    <span class="metric critical">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php 
                                        /* translators: %d: number of critical issues */
                                        printf(esc_html__('%d Critical', 'hostcheckr'), (int) $overall_status['summary']['critical']); 
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($overall_status['summary']['warnings'] > 0): ?>
                                    <span class="metric warning">
                                        <span class="dashicons dashicons-info"></span>
                                        <?php 
                                        /* translators: %d: number of warnings */
                                        printf(esc_html__('%d Warnings', 'hostcheckr'), (int) $overall_status['summary']['warnings']); 
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($overall_status['summary']['total'] === 0): ?>
                                    <span class="metric success">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php esc_html_e('All Good', 'hostcheckr'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Professional Support Section -->
            <div class="professional-support-section">
                <div class="support-card">
                    <div class="support-header">
                        <div class="support-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="support-content">
                            <h3><?php esc_html_e('Need Professional Support?', 'hostcheckr'); ?></h3>
                            <p><?php esc_html_e('Get expert WordPress optimization services from KloudBoy', 'hostcheckr'); ?></p>
                        </div>
                        <div class="support-action">
                            <a href="<?php echo esc_url('https://hostcheckr.kloudboy.com/support'); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary support-btn">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e('Contact KloudBoy', 'hostcheckr'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="support-details">
                        <div class="support-services">
                            <div class="service-item">
                                <span class="dashicons dashicons-performance"></span>
                                <span><?php esc_html_e('WordPress Performance Optimization', 'hostcheckr'); ?></span>
                            </div>
                            <div class="service-item">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <span><?php esc_html_e('Server Configuration & Tuning', 'hostcheckr'); ?></span>
                            </div>
                            <div class="service-item">
                                <span class="dashicons dashicons-shield"></span>
                                <span><?php esc_html_e('Security Hardening & Monitoring', 'hostcheckr'); ?></span>
                            </div>
                            <div class="service-item">
                                <span class="dashicons dashicons-cloud"></span>
                                <span><?php esc_html_e('Hosting Migration & Setup', 'hostcheckr'); ?></span>
                            </div>
                        </div>
                        <div class="support-cta">
                            <p class="cta-text">
                                <?php esc_html_e('Let our WordPress experts optimize your hosting environment for maximum performance and security.', 'hostcheckr'); ?>
                            </p>
                            <div class="cta-features">
                                <span class="feature-badge">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Expert Analysis', 'hostcheckr'); ?>
                                </span>
                                <span class="feature-badge">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Custom Solutions', 'hostcheckr'); ?>
                                </span>
                                <span class="feature-badge">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Ongoing Support', 'hostcheckr'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
    <!-- Health Details (Expandable) -->
            <?php if (!empty($overall_status['details'])): ?>
                <div id="health-details" class="health-details-section" style="display: none;">
                    <?php 
                    $critical_items = array_filter($overall_status['details'], function($item) {
                        return $item['severity'] === 'critical';
                    });
                    $warning_items = array_filter($overall_status['details'], function($item) {
                        return $item['severity'] === 'warning';
                    });
                    ?>
                    
                    <?php if (!empty($critical_items)): ?>
                        <div class="health-issues-section critical">
                            <div class="section-header">
                                <h3>
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e('Critical Issues', 'hostcheckr'); ?>
                                </h3>
                                <span class="issue-count"><?php 
                /* translators: %d: number of items */
                printf(esc_html__('%d items', 'hostcheckr'), count($critical_items)); 
                ?></span>
                            </div>
                            <div class="issues-grid">
                                <?php foreach ($critical_items as $item): ?>
                                    <div class="issue-card critical">
                                        <div class="issue-header">
                                            <div class="issue-title">
                                                <h4><?php echo esc_html($item['item']); ?></h4>
                                                <span class="issue-type"><?php echo esc_html($item['type']); ?></span>
                                            </div>
                                            <span class="severity-badge critical"><?php esc_html_e('Critical', 'hostcheckr'); ?></span>
                                        </div>
                                        <div class="issue-comparison">
                                            <div class="comparison-item current">
                                                <span class="label"><?php esc_html_e('Current', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($item['current'])); ?></span>
                                            </div>
                                            <div class="comparison-arrow">→</div>
                                            <div class="comparison-item required">
                                                <span class="label"><?php esc_html_e('Required', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($item['required'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="issue-solution">
                                            <?php echo wp_kses_post($this->getRecommendation($item)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($warning_items)): ?>
                        <div class="health-issues-section warning">
                            <div class="section-header">
                                <h3>
                                    <span class="dashicons dashicons-info"></span>
                                    <?php esc_html_e('Optimization Opportunities', 'hostcheckr'); ?>
                                </h3>
                                <span class="issue-count"><?php 
                                /* translators: %d: number of items */
                                printf(esc_html__('%d items', 'hostcheckr'), count($warning_items)); 
                                ?></span>
                            </div>
                            <div class="issues-grid">
                                <?php foreach ($warning_items as $item): ?>
                                    <div class="issue-card warning">
                                        <div class="issue-header">
                                            <div class="issue-title">
                                                <h4><?php echo esc_html($item['item']); ?></h4>
                                                <span class="issue-type"><?php echo esc_html($item['type']); ?></span>
                                            </div>
                                            <span class="severity-badge warning"><?php esc_html_e('Warning', 'hostcheckr'); ?></span>
                                        </div>
                                        <div class="issue-comparison">
                                            <div class="comparison-item current">
                                                <span class="label"><?php esc_html_e('Current', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($item['current'])); ?></span>
                                            </div>
                                            <div class="comparison-arrow">→</div>
                                            <div class="comparison-item recommended">
                                                <span class="label"><?php esc_html_e('Recommended', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($item['recommended'] ?? $item['required'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="issue-solution">
                                            <?php echo wp_kses_post($this->getRecommendation($item)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-container">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="system-info-search" placeholder="<?php esc_html_e('Search settings, extensions, or values...', 'hostcheckr'); ?>" />
                </div>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all"><?php esc_html_e('All', 'hostcheckr'); ?></button>
                    <button class="filter-tab" data-filter="issues"><?php esc_html_e('Issues Only', 'hostcheckr'); ?></button>
                    <button class="filter-tab" data-filter="success"><?php esc_html_e('Passed', 'hostcheckr'); ?></button>
                </div>
            </div>

            <!-- Main Content Tabs -->
            <div class="content-tabs-container">
                <div class="content-tabs-nav">
                    <button class="content-tab <?php echo $current_tab === 'overview' ? 'active' : ''; ?>" data-tab="overview">
                        <span class="dashicons dashicons-dashboard"></span>
                        <span class="tab-label"><?php esc_html_e('Overview', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'versions' ? 'active' : ''; ?>" data-tab="versions">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span class="tab-label"><?php esc_html_e('System Versions', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'config' ? 'active' : ''; ?>" data-tab="config">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span class="tab-label"><?php esc_html_e('PHP Configuration', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'extensions' ? 'active' : ''; ?>" data-tab="extensions">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <span class="tab-label"><?php esc_html_e('PHP Extensions', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'server' ? 'active' : ''; ?>" data-tab="server">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <span class="tab-label"><?php esc_html_e('Server Info', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'resources' ? 'active' : ''; ?>" data-tab="resources">
                        <span class="dashicons dashicons-performance"></span>
                        <span class="tab-label"><?php esc_html_e('System Resources', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'hosting' ? 'active' : ''; ?>" data-tab="hosting">
                        <span class="dashicons dashicons-cloud"></span>
                        <span class="tab-label"><?php esc_html_e('Hosting Info', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'wordpress' ? 'active' : ''; ?>" data-tab="wordpress">
                        <span class="dashicons dashicons-wordpress-alt"></span>
                        <span class="tab-label"><?php esc_html_e('WordPress Info', 'hostcheckr'); ?></span>
                    </button>
                    <button class="content-tab <?php echo $current_tab === 'performance' ? 'active' : ''; ?>" data-tab="performance">
                        <span class="dashicons dashicons-chart-line"></span>
                        <span class="tab-label"><?php esc_html_e('Performance Check', 'hostcheckr'); ?></span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="content-tabs-content">
                    <!-- Overview Tab -->
                    <div id="tab-overview" class="tab-content <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
                        <div class="overview-grid">
                            <div class="overview-section">
                                <h3>
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <?php esc_html_e('System Requirements', 'hostcheckr'); ?>
                                </h3>
                                <div class="requirement-checks">
                                    <?php 
                                    $version_checks = $this->getVersions();
                                    foreach ($version_checks as $label => $data):
                                        if (count($data) > 1):
                                            $status_class = $this->toHtmlClass($data);
                                    ?>
                                        <div class="requirement-item <?php echo esc_attr($status_class); ?>">
                                            <span class="requirement-icon dashicons dashicons-<?php echo $status_class === 'success' ? 'yes-alt' : ($status_class === 'warning' ? 'info' : 'warning'); ?>"></span>
                                            <div class="requirement-details">
                                                <span class="requirement-name"><?php echo esc_html($label); ?></span>
                                                <span class="requirement-value"><?php echo esc_html($this->toString($data[2])); ?></span>
                                            </div>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="overview-section">
                                <h3>
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    <?php esc_html_e('Critical Extensions', 'hostcheckr'); ?>
                                </h3>
                                <div class="extension-checks">
                                    <?php 
                                    $extensions = $this->getPhpExtensions();
                                    $critical_extensions = ['cURL', 'MySQLi', 'GD', 'OpenSSL', 'JSON'];
                                    foreach ($extensions as $label => $data):
                                        if (in_array($label, $critical_extensions)):
                                            $status_class = $this->toHtmlClass($data);
                                    ?>
                                        <div class="extension-item <?php echo esc_attr($status_class); ?>">
                                            <span class="extension-icon dashicons dashicons-<?php echo $status_class === 'success' ? 'yes-alt' : 'warning'; ?>"></span>
                                            <div class="extension-details">
                                                <span class="extension-name"><?php echo esc_html($label); ?></span>
                                                <span class="extension-status"><?php echo $data[2] ? esc_html__('Installed', 'hostcheckr') : esc_html__('Missing', 'hostcheckr'); ?></span>
                                            </div>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Versions Tab -->
                    <div id="tab-versions" class="tab-content <?php echo $current_tab === 'versions' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e('System Versions & Server Information', 'hostcheckr'); ?>
                            </h2>
                            <div class="table-container">
                                <table class="wp-list-table widefat fixed striped modern-table">
                                    <thead>
                                        <tr>
                                            <th class="manage-column column-setting"><?php esc_html_e('Setting', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-required"><?php esc_html_e('Required', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-recommended"><?php esc_html_e('Recommended', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-current"><?php esc_html_e('Current', 'hostcheckr'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->getVersions() as $label => $data) : ?>
                                            <?php if (count($data) === 1) : ?>
                                                <tr>
                                                    <td class="column-setting">
                                                        <strong><?php echo esc_html($label); ?></strong>
                                                    </td>
                                                    <td colspan="3" class="status-cell <?php echo esc_attr($this->toHtmlClass($data)); ?>">
                                                        <span class="status-indicator"></span>
                                                        <?php echo esc_html($this->toString($data[0])); ?>
                                                    </td>
                                                </tr>
                                            <?php else : ?>
                                                <tr>
                                                    <td class="column-setting">
                                                        <strong><?php echo esc_html($label); ?></strong>
                                                    </td>
                                                    <td class="column-required"><?php echo esc_html($this->toString($data[0])); ?></td>
                                                    <td class="column-recommended"><?php echo esc_html($this->toString($data[1])); ?></td>
                                                    <td class="status-cell <?php echo esc_attr($this->toHtmlClass($data)); ?>">
                                                        <span class="status-indicator"></span>
                                                        <?php echo esc_html($this->toString($data[2])); ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- PHP Configuration Tab -->
                    <div id="tab-config" class="tab-content <?php echo $current_tab === 'config' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('PHP Configuration Settings', 'hostcheckr'); ?>
                            </h2>
                            <div class="table-container">
                                <table class="wp-list-table widefat fixed striped modern-table">
                                    <thead>
                                        <tr>
                                            <th class="manage-column column-setting"><?php esc_html_e('Setting', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-required"><?php esc_html_e('Required', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-recommended"><?php esc_html_e('Recommended', 'hostcheckr'); ?></th>
                                            <th class="manage-column column-current"><?php esc_html_e('Current', 'hostcheckr'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->getPhpConfig() as $label => $data) : ?>
                                            <tr>
                                                <td class="column-setting">
                                                    <strong><?php echo esc_html(str_replace('_', ' ', ucwords($label, '_'))); ?></strong>
                                                    <code><?php echo esc_html($label); ?></code>
                                                </td>
                                                <td class="column-required"><?php echo esc_html($this->toString($data[0])); ?></td>
                                                <td class="column-recommended"><?php echo esc_html($this->toString($data[1])); ?></td>
                                                <td class="status-cell <?php echo esc_attr($this->toHtmlClass($data)); ?>">
                                                    <span class="status-indicator"></span>
                                                    <?php echo esc_html($this->toString($data[2])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- PHP Extensions Tab -->
                    <div id="tab-extensions" class="tab-content <?php echo $current_tab === 'extensions' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-admin-plugins"></span>
                                <?php esc_html_e('PHP Extensions Status', 'hostcheckr'); ?>
                            </h2>
                            <div class="extensions-grid">
                                <?php foreach ($this->getPhpExtensions() as $label => $data) : ?>
                                    <div class="extension-card <?php echo esc_attr($this->toHtmlClass($data)); ?>">
                                        <div class="extension-header">
                                            <h4><?php echo esc_html($label); ?></h4>
                                            <span class="status-badge <?php echo esc_attr($this->toHtmlClass($data)); ?>">
                                                <?php echo esc_html($this->toString($data[2])); ?>
                                            </span>
                                        </div>
                                        <div class="extension-details">
                                            <div class="detail-row">
                                                <span class="label"><?php esc_html_e('Required:', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($data[0])); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label"><?php esc_html_e('Recommended:', 'hostcheckr'); ?></span>
                                                <span class="value"><?php echo esc_html($this->toString($data[1])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Server Info Tab -->
                    <div id="tab-server" class="tab-content <?php echo $current_tab === 'server' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                                <?php esc_html_e('Server Configuration & Environment', 'hostcheckr'); ?>
                            </h2>
                            <div class="server-info-grid">
                                <?php foreach ($this->getServerInfo() as $label => $value) : ?>
                                    <div class="server-info-item">
                                        <div class="server-info-label"><?php echo esc_html($label); ?></div>
                                        <div class="server-info-value" title="<?php echo esc_attr($value); ?>">
                                            <?php echo esc_html($this->truncateString($value, 50)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- System Resources Tab -->
                    <div id="tab-resources" class="tab-content <?php echo $current_tab === 'resources' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-performance"></span>
                                <?php esc_html_e('System Resources & Performance', 'hostcheckr'); ?>
                            </h2>
                            
                            <!-- Resource Overview Cards -->
                            <div class="resource-overview">
                                <?php 
                                $resources = $this->getSystemResources();
                                $resource_categories = [
                                    'memory' => [
                                        'title' => __('Memory (RAM)', 'hostcheckr'),
                                        'icon' => 'dashicons-database-view',
                                        'items' => ['Total RAM', 'Available RAM', 'Used RAM', 'RAM Usage %']
                                    ],
                                    'cpu' => [
                                        'title' => __('CPU Performance', 'hostcheckr'),
                                        'icon' => 'dashicons-performance',
                                        'items' => ['CPU Model', 'CPU Cores', 'CPU Usage %', 'CPU Load Average']
                                    ],
                                    'storage' => [
                                        'title' => __('Storage Information', 'hostcheckr'),
                                        'icon' => 'dashicons-media-default',
                                        'items' => ['Total Disk Space', 'Free Disk Space', 'Used Disk Space', 'Disk Usage %', 'Storage Type', 'File System']
                                    ],
                                    'network' => [
                                        'title' => __('Network Configuration', 'hostcheckr'),
                                        'icon' => 'dashicons-networking',
                                        'items' => ['Network Interfaces', 'Default Gateway', 'DNS Servers']
                                    ]
                                ];
                                
                                foreach ($resource_categories as $category => $config): ?>
                                    <div class="resource-card <?php echo esc_attr($category); ?>">
                                        <div class="resource-header">
                                            <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                                            <h3><?php echo esc_html($config['title']); ?></h3>
                                        </div>
                                        <div class="resource-details">
                                            <?php foreach ($config['items'] as $item): ?>
                                                <?php if (isset($resources[$item])): ?>
                                                    <div class="resource-item">
                                                        <span class="resource-label"><?php echo esc_html($item); ?></span>
                                                        <span class="resource-value <?php echo strpos($item, '%') !== false ? 'percentage' : ''; ?>">
                                                            <?php echo esc_html($resources[$item]); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Hosting Info Tab -->
                    <div id="tab-hosting" class="tab-content <?php echo $current_tab === 'hosting' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-cloud"></span>
                                <?php esc_html_e('Hosting Provider & Infrastructure', 'hostcheckr'); ?>
                            </h2>
                            
                            <div class="hosting-info-container">
                                <?php 
                                $hosting_info = $this->getHostingInfo();
                                $hosting_sections = [
                                    'provider' => [
                                        'title' => __('Hosting Provider', 'hostcheckr'),
                                        'icon' => 'dashicons-cloud',
                                        'items' => ['Hosting Provider', 'Provider Confidence']
                                    ],
                                    'location' => [
                                        'title' => __('Server Location', 'hostcheckr'),
                                        'icon' => 'dashicons-location-alt',
                                        'items' => ['Server Location', 'Data Center', 'ISP']
                                    ],
                                    'performance' => [
                                        'title' => __('Performance Metrics', 'hostcheckr'),
                                        'icon' => 'dashicons-chart-line',
                                        'items' => ['Response Time', 'Database Response', 'File System Speed']
                                    ],
                                    'security' => [
                                        'title' => __('Security Information', 'hostcheckr'),
                                        'icon' => 'dashicons-shield-alt',
                                        'items' => ['SSL Certificate', 'SSL Issuer', 'SSL Expiry', 'Security Headers']
                                    ]
                                ];
                                
                                foreach ($hosting_sections as $section => $config): ?>
                                    <div class="hosting-section <?php echo esc_attr($section); ?>">
                                        <div class="hosting-section-header">
                                            <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                                            <h3><?php echo esc_html($config['title']); ?></h3>
                                        </div>
                                        <div class="hosting-section-content">
                                            <?php foreach ($config['items'] as $item): ?>
                                                <?php if (isset($hosting_info[$item])): ?>
                                                    <div class="hosting-info-row">
                                                        <span class="hosting-label"><?php echo esc_html($item); ?></span>
                                                        <span class="hosting-value">
                                                            <?php echo esc_html($hosting_info[$item]); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- WordPress Info Tab -->
                    <div id="tab-wordpress" class="tab-content <?php echo $current_tab === 'wordpress' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <h2>
                                <span class="dashicons dashicons-wordpress-alt"></span>
                                <?php esc_html_e('WordPress Installation Details', 'hostcheckr'); ?>
                            </h2>
                            <div class="wordpress-info-grid">
                                <?php foreach ($this->getWordPressInfo() as $label => $value) : ?>
                                    <div class="info-item">
                                        <div class="info-label"><?php echo esc_html($label); ?></div>
                                        <div class="info-value"><?php echo esc_html($value); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Check Tab -->
                    <div id="tab-performance" class="tab-content <?php echo $current_tab === 'performance' ? 'active' : ''; ?>">
                        <div class="info-section">
                            <div class="performance-header">
                                <h2>
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e('Why Is My WordPress Slow?', 'hostcheckr'); ?>
                                </h2>
                                <p class="performance-description">
                                    <?php esc_html_e('This diagnostic tool analyzes your WordPress installation to identify performance bottlenecks and provides actionable recommendations.', 'hostcheckr'); ?>
                                </p>
                            </div>

                            <div class="live-db-monitor" id="live-db-monitor">
                                <div class="live-db-header">
                                    <h3><?php esc_html_e('Live Database Monitor', 'hostcheckr'); ?></h3>
                                    <div class="live-db-actions">
                                        <span class="live-db-last-updated" id="live-db-last-updated"><?php esc_html_e('Waiting for first sample...', 'hostcheckr'); ?></span>
                                        <button type="button" class="button button-secondary live-db-refresh-btn" id="live-db-refresh-btn">
                                            <?php esc_html_e('Refresh Now', 'hostcheckr'); ?>
                                        </button>
                                    </div>
                                </div>
                                <p class="live-db-description">
                                    <?php esc_html_e('Real-time database health signals from your current server session. Values update automatically while this tab is open.', 'hostcheckr'); ?>
                                </p>
                                <div class="live-db-grid" id="live-db-grid">
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('DB Response Time', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="response_ms"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('Autoload Size', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="autoload_mb"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('Estimated DB Size', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="db_size_mb"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('Post Revisions', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="revisions"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('Active DB Threads', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="threads_connected"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                    <div class="live-db-card">
                                        <span class="live-db-label"><?php esc_html_e('Slow Queries', 'hostcheckr'); ?></span>
                                        <span class="live-db-value" data-db-metric="slow_queries"><?php esc_html_e('Loading...', 'hostcheckr'); ?></span>
                                    </div>
                                </div>
                                <div class="live-db-warnings" id="live-db-warnings" style="display:none;"></div>
                                <div class="live-db-patterns" id="live-db-patterns" style="display:none;"></div>
                                <div class="live-db-settings">
                                    <h4><?php esc_html_e('Live Monitor Settings', 'hostcheckr'); ?></h4>
                                    <div class="live-db-settings-grid">
                                        <label class="live-db-setting-item">
                                            <span><?php esc_html_e('Enable Live Monitoring', 'hostcheckr'); ?></span>
                                            <input type="checkbox" id="live-db-enabled" />
                                        </label>
                                        <label class="live-db-setting-item">
                                            <span><?php esc_html_e('Polling Interval (seconds)', 'hostcheckr'); ?></span>
                                            <input type="number" id="live-db-interval" min="5" max="120" step="1" />
                                        </label>
                                        <label class="live-db-setting-item">
                                            <span><?php esc_html_e('Max Slow Query Patterns', 'hostcheckr'); ?></span>
                                            <input type="number" id="live-db-max-patterns" min="1" max="20" step="1" />
                                        </label>
                                        <label class="live-db-setting-item">
                                            <span><?php esc_html_e('Lightweight Mode (skip heavy DB probes)', 'hostcheckr'); ?></span>
                                            <input type="checkbox" id="live-db-lightweight-mode" />
                                        </label>
                                    </div>
                                    <div class="live-db-settings-actions">
                                        <button type="button" class="button button-secondary" id="live-db-save-settings">
                                            <?php esc_html_e('Save Live Monitor Settings', 'hostcheckr'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <?php
                            require_once HOSTCHECKR_PLUGIN_PATH . 'includes/class-hostcheckr-performance.php';
                            $performance = new HostCheckr_Performance();
                            $performance_issues = $performance->diagnose_performance();
                            
                            if (empty($performance_issues)) {
                                ?>
                                <div class="performance-success">
                                    <div class="success-icon">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                    </div>
                                    <h3><?php esc_html_e('Great News!', 'hostcheckr'); ?></h3>
                                    <p><?php esc_html_e('No major performance issues detected. Your WordPress site appears to be well-optimized!', 'hostcheckr'); ?></p>
                                </div>
                                <?php
                            } else {
                                // Group issues by severity
                                $critical_issues = array_filter($performance_issues, function($issue) {
                                    return $issue['severity'] === 'critical';
                                });
                                $warning_issues = array_filter($performance_issues, function($issue) {
                                    return $issue['severity'] === 'warning';
                                });
                                $info_issues = array_filter($performance_issues, function($issue) {
                                    return $issue['severity'] === 'info';
                                });
                                ?>
                                
                                <div class="performance-summary">
                                    <div class="summary-stats">
                                        <?php if (!empty($critical_issues)): ?>
                                            <div class="stat-box critical">
                                                <span class="stat-number"><?php echo count($critical_issues); ?></span>
                                                <span class="stat-label"><?php esc_html_e('Critical Issues', 'hostcheckr'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($warning_issues)): ?>
                                            <div class="stat-box warning">
                                                <span class="stat-number"><?php echo count($warning_issues); ?></span>
                                                <span class="stat-label"><?php esc_html_e('Warnings', 'hostcheckr'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($info_issues)): ?>
                                            <div class="stat-box info">
                                                <span class="stat-number"><?php echo count($info_issues); ?></span>
                                                <span class="stat-label"><?php esc_html_e('Info', 'hostcheckr'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($critical_issues)): ?>
                                    <div class="performance-issues-section critical-section">
                                        <h3>
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php esc_html_e('Critical Performance Issues', 'hostcheckr'); ?>
                                        </h3>
                                        <div class="performance-issues-grid">
                                            <?php foreach ($critical_issues as $issue): ?>
                                                <div class="performance-issue-card critical">
                                                    <div class="issue-header">
                                                        <h4><?php echo esc_html($issue['title']); ?></h4>
                                                        <span class="severity-badge critical"><?php esc_html_e('Critical', 'hostcheckr'); ?></span>
                                                    </div>
                                                    <div class="issue-value">
                                                        <strong><?php esc_html_e('Current:', 'hostcheckr'); ?></strong> 
                                                        <?php echo esc_html($issue['value']); ?>
                                                    </div>
                                                    <div class="issue-description">
                                                        <?php echo esc_html($issue['description']); ?>
                                                    </div>
                                                    <div class="issue-recommendation">
                                                        <strong><?php esc_html_e('Recommendation:', 'hostcheckr'); ?></strong>
                                                        <p><?php echo esc_html($issue['recommendation']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($warning_issues)): ?>
                                    <div class="performance-issues-section warning-section">
                                        <h3>
                                            <span class="dashicons dashicons-info"></span>
                                            <?php esc_html_e('Performance Warnings', 'hostcheckr'); ?>
                                        </h3>
                                        <div class="performance-issues-grid">
                                            <?php foreach ($warning_issues as $issue): ?>
                                                <div class="performance-issue-card warning">
                                                    <div class="issue-header">
                                                        <h4><?php echo esc_html($issue['title']); ?></h4>
                                                        <span class="severity-badge warning"><?php esc_html_e('Warning', 'hostcheckr'); ?></span>
                                                    </div>
                                                    <div class="issue-value">
                                                        <strong><?php esc_html_e('Current:', 'hostcheckr'); ?></strong> 
                                                        <?php echo esc_html($issue['value']); ?>
                                                    </div>
                                                    <div class="issue-description">
                                                        <?php echo esc_html($issue['description']); ?>
                                                    </div>
                                                    <div class="issue-recommendation">
                                                        <strong><?php esc_html_e('Recommendation:', 'hostcheckr'); ?></strong>
                                                        <p><?php echo esc_html($issue['recommendation']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($info_issues)): ?>
                                    <div class="performance-issues-section info-section">
                                        <h3>
                                            <span class="dashicons dashicons-info-outline"></span>
                                            <?php esc_html_e('Additional Information', 'hostcheckr'); ?>
                                        </h3>
                                        <div class="performance-issues-grid">
                                            <?php foreach ($info_issues as $issue): ?>
                                                <div class="performance-issue-card info">
                                                    <div class="issue-header">
                                                        <h4><?php echo esc_html($issue['title']); ?></h4>
                                                        <span class="severity-badge info"><?php esc_html_e('Info', 'hostcheckr'); ?></span>
                                                    </div>
                                                    <div class="issue-value">
                                                        <strong><?php esc_html_e('Current:', 'hostcheckr'); ?></strong> 
                                                        <?php echo esc_html($issue['value']); ?>
                                                    </div>
                                                    <div class="issue-description">
                                                        <?php echo esc_html($issue['description']); ?>
                                                    </div>
                                                    <div class="issue-recommendation">
                                                        <strong><?php esc_html_e('Recommendation:', 'hostcheckr'); ?></strong>
                                                        <p><?php echo esc_html($issue['recommendation']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="hostcheckr-footer">
                <div class="footer-content">
                    <div class="footer-branding">
                        <p>
                            <?php esc_html_e('Powered by HostCheckr', 'hostcheckr'); ?> |
                            <?php esc_html_e('Developed by', 'hostcheckr'); ?>
                            <a href="<?php echo esc_url('https://kloudboy.com'); ?>" target="_blank" rel="noopener noreferrer">Bajpan Gosh</a> |
                            <?php esc_html_e('Company:', 'hostcheckr'); ?>
                            <a href="<?php echo esc_url('https://kloudboy.com'); ?>" target="_blank" rel="noopener noreferrer">KloudBoy</a>
                        </p>
                    </div>
                    <div class="footer-links">
                        <a href="<?php echo esc_url('https://hostcheckr.kloudboy.com'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Visit Plugin Site', 'hostcheckr'); ?></a>
                        <a href="<?php echo esc_url('https://hostcheckr.kloudboy.com/support'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get Support', 'hostcheckr'); ?></a>
                        <a href="<?php echo esc_url('https://hostcheckr.kloudboy.com/docs'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'hostcheckr'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get versions data
     *
     * @return array
     */
    public function getVersions()
    {
        global $wpdb;
        
        $data = [
            'Web Server' => [$this->getWebServer()],
            'PHP SAPI' => [
                strpos(PHP_SAPI, 'cgi') !== false ?
                'CGI' :
                (strpos(PHP_SAPI, 'fpm') !== false ? 'PHP-FPM (Recommended)' : 'Apache Module')
            ],
        ];

        $data['PHP Version'] = [
            $this->requirements['versions']['php'],
            $this->recommended['versions']['php'],
            PHP_VERSION,
            version_compare(PHP_VERSION, $this->recommended['versions']['php'], '>=') ?
            self::TYPE_OK : (
                version_compare(PHP_VERSION, $this->requirements['versions']['php'], '>=') ?
                self::TYPE_WARNING :
                self::TYPE_ERROR
            )
        ];

        // MySQL Version
        $mysql_version = wp_cache_get('hostcheckr_mysql_version');
        if (false === $mysql_version) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Getting MySQL version for system requirements check
            $mysql_version = $wpdb->get_var("SELECT VERSION()");
            wp_cache_set('hostcheckr_mysql_version', $mysql_version, '', 3600);
        }
        $data['MySQL Version'] = [
            $this->requirements['versions']['mysql'],
            $this->recommended['versions']['mysql'],
            $mysql_version,
            version_compare($mysql_version, $this->recommended['versions']['mysql'], '>=') ?
            self::TYPE_OK : (
                version_compare($mysql_version, $this->requirements['versions']['mysql'], '>=') ?
                self::TYPE_WARNING :
                self::TYPE_ERROR
            )
        ];

        return $data;
    }

    /**
     * Get php extensions data
     *
     * @return array
     */
    public function getPhpExtensions()
    {
        $data = [];
        $extensions = [
            'cURL' => 'curl',
            'DOM' => 'dom',
            'Exif' => 'exif',
            'File Info' => 'fileinfo',
            'Hash' => 'hash',
            'JSON' => 'json',
            'Multibyte String' => 'mbstring',
            'MySQLi' => 'mysqli',
            'Sodium' => 'libsodium',
            'OpenSSL' => 'openssl',
            'PCRE' => 'pcre',
            'ImageMagick' => 'imagick',
            'Zip' => 'zip',
            'Filter' => 'filter',
            'GD' => 'gd',
            'Iconv' => 'iconv',
            'SimpleXML' => 'simplexml',
            'XMLReader' => 'xmlreader',
            'Zlib' => 'zlib',
        ];

        foreach ($extensions as $label => $extension) {
            if (!isset($this->requirements['extensions'][$extension])) {
                continue;
            }
            
            $loaded = extension_loaded($extension);
            $data[$label] = [
                $this->requirements['extensions'][$extension],
                $this->recommended['extensions'][$extension],
                $loaded
            ];
        }

        return $data;
    }

    /**
     * Get php config data
     *
     * @return array
     */
    public function getPhpConfig()
    {
        $data = [];
        
        // Boolean configs
        $bool_configs = ['file_uploads'];
        foreach ($bool_configs as $config) {
            if (!isset($this->requirements['config'][$config])) {
                continue;
            }
            
            $value = (bool) ini_get($config);
            $data[$config] = [
                $this->requirements['config'][$config],
                $this->recommended['config'][$config],
                $value
            ];
        }

        // Size/numeric configs
        $size_configs = [
            'max_input_vars',
            'memory_limit',
            'post_max_size',
            'upload_max_filesize',
            'max_execution_time',
            'max_input_time',
        ];
        
        foreach ($size_configs as $config) {
            if (!isset($this->requirements['config'][$config])) {
                continue;
            }
            
            $value = ini_get($config);
            $current_bytes = $this->toBytes($value);
            $required_bytes = $this->toBytes($this->requirements['config'][$config]);
            $recommended_bytes = $this->toBytes($this->recommended['config'][$config]);
            
            if ($current_bytes >= $recommended_bytes) {
                $result = self::TYPE_OK;
            } elseif ($current_bytes >= $required_bytes) {
                $result = self::TYPE_WARNING;
            } else {
                $result = self::TYPE_ERROR;
            }

            $data[$config] = [
                $this->requirements['config'][$config],
                $this->recommended['config'][$config],
                $value,
                $result,
            ];
        }

        return $data;
    }

    /**
     * Get overall system status
     *
     * @return array
     */
    public function getOverallStatus()
    {
        $issues = [];
        $warnings = [];
        $critical_issues = 0;
        $warning_count = 0;
        
        // Check versions
        foreach ($this->getVersions() as $label => $data) {
            if (count($data) > 1) {
                $status = $this->toHtmlClass($data);
                if ($status === self::TYPE_ERROR_CLASS) {
                    $critical_issues++;
                    $issues[] = [
                        'type' => 'Version',
                        'item' => $label,
                        'current' => $data[2],
                        'required' => $data[0],
                        'severity' => 'critical'
                    ];
                } elseif ($status === self::TYPE_WARNING_CLASS) {
                    $warning_count++;
                    $warnings[] = [
                        'type' => 'Version',
                        'item' => $label,
                        'current' => $data[2],
                        'recommended' => $data[1],
                        'severity' => 'warning'
                    ];
                }
            }
        }
        
        // Check PHP config
        foreach ($this->getPhpConfig() as $label => $data) {
            $status = $this->toHtmlClass($data);
            if ($status === self::TYPE_ERROR_CLASS) {
                $critical_issues++;
                $issues[] = [
                    'type' => 'Configuration',
                    'item' => $label,
                    'current' => $data[2],
                    'required' => $data[0],
                    'severity' => 'critical'
                ];
            } elseif ($status === self::TYPE_WARNING_CLASS) {
                $warning_count++;
                $warnings[] = [
                    'type' => 'Configuration',
                    'item' => $label,
                    'current' => $data[2],
                    'recommended' => $data[1],
                    'severity' => 'warning'
                ];
            }
        }
        
        // Check extensions
        foreach ($this->getPhpExtensions() as $label => $data) {
            $status = $this->toHtmlClass($data);
            if ($status === self::TYPE_ERROR_CLASS) {
                $critical_issues++;
                $issues[] = [
                    'type' => 'Extension',
                    'item' => $label,
                    'current' => $data[2] ? 'Installed' : 'Missing',
                    'required' => $data[0] ? 'Required' : 'Optional',
                    'severity' => 'critical'
                ];
            } elseif ($status === self::TYPE_WARNING_CLASS) {
                $warning_count++;
                $warnings[] = [
                    'type' => 'Extension',
                    'item' => $label,
                    'current' => $data[2] ? 'Installed' : 'Missing',
                    'recommended' => $data[1] ? 'Recommended' : 'Optional',
                    'severity' => 'warning'
                ];
            }
        }
        
        // Determine overall status
        if ($critical_issues > 0) {
            /* translators: %d: number of critical issues */
            $message = sprintf(_n('%d critical issue found that needs immediate attention', '%d critical issues found that need immediate attention', $critical_issues, 'hostcheckr'), $critical_issues);
            if ($warning_count > 0) {
                /* translators: %d: number of warnings */
                $message .= sprintf(_n(' and %d warning', ' and %d warnings', $warning_count, 'hostcheckr'), $warning_count);
            }
            
            return [
                'class' => 'error',
                'icon' => 'warning',
                'message' => $message,
                'details' => array_merge($issues, $warnings),
                'summary' => [
                    'critical' => $critical_issues,
                    'warnings' => $warning_count,
                    'total' => $critical_issues + $warning_count
                ]
            ];
        } elseif ($warning_count > 0) {
            return [
                'class' => 'warning',
                'icon' => 'info',
                /* translators: %d: number of warnings */
                'message' => sprintf(_n('%d warning found - consider optimization for better performance', '%d warnings found - consider optimization for better performance', $warning_count, 'hostcheckr'), $warning_count),
                'details' => $warnings,
                'summary' => [
                    'critical' => 0,
                    'warnings' => $warning_count,
                    'total' => $warning_count
                ]
            ];
        } else {
            return [
                'class' => 'success',
                'icon' => 'yes-alt',
                'message' => __('All systems are running optimally! Your WordPress installation meets all requirements.', 'hostcheckr'),
                'details' => [],
                'summary' => [
                    'critical' => 0,
                    'warnings' => 0,
                    'total' => 0
                ]
            ];
        }
    }

    /**
     * Get WordPress specific information
     *
     * @return array
     */
    public function getWordPressInfo()
    {
        global $wp_version;
        
        $data = [
            'WordPress Version' => $wp_version,
            'WordPress Debug Mode' => WP_DEBUG ? __('Enabled', 'hostcheckr') : __('Disabled', 'hostcheckr'),
            'WordPress Memory Limit' => WP_MEMORY_LIMIT,
            'WordPress Max Memory Limit' => WP_MAX_MEMORY_LIMIT,
            'WordPress Multisite' => is_multisite() ? __('Yes', 'hostcheckr') : __('No', 'hostcheckr'),
            'Active Theme' => wp_get_theme()->get('Name'),
            'Active Plugins' => count(get_option('active_plugins', [])),
            'WordPress Language' => get_locale(),
            'WordPress Timezone' => get_option('timezone_string') ?: get_option('gmt_offset'),
            'WordPress URL' => get_option('siteurl'),
            'Home URL' => get_option('home'),
        ];

        return $data;
    }

    /**
     * Get comprehensive server and hosting information
     *
     * @return array
     */
    public function getServerInfo()
    {
        $data = [
            'Server Software' => $this->getWebServer(),
            'Server OS' => $this->getServerOS(),
            'Server Architecture' => $this->getServerArchitecture(),
            'Server IP Address' => $this->getServerIP(),
            'Document Root' => isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : __('Not available', 'hostcheckr'),
            'Server Admin' => isset($_SERVER['SERVER_ADMIN']) ? sanitize_email(wp_unslash($_SERVER['SERVER_ADMIN'])) : __('Not available', 'hostcheckr'),
            'Server Port' => isset($_SERVER['SERVER_PORT']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_PORT'])) : __('Not available', 'hostcheckr'),
            'HTTPS' => $this->isHTTPS() ? __('Enabled', 'hostcheckr') : __('Disabled', 'hostcheckr'),
            'Server Protocol' => isset($_SERVER['SERVER_PROTOCOL']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_PROTOCOL'])) : __('Not available', 'hostcheckr'),
            'Request Method' => isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : __('Not available', 'hostcheckr'),
            'User Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : __('Not available', 'hostcheckr'),
            'Server Load' => $this->getServerLoad(),
            'Uptime' => $this->getServerUptime(),
        ];

        return $data;
    }

    /**
     * Get system resources information (RAM, CPU, Storage)
     *
     * @return array
     */
    public function getSystemResources()
    {
        $data = [];
        
        // Memory Information
        $memory_info = $this->getMemoryInfo();
        $data['Total RAM'] = $memory_info['total'];
        $data['Available RAM'] = $memory_info['available'];
        $data['Used RAM'] = $memory_info['used'];
        $data['RAM Usage %'] = $memory_info['usage_percent'];
        
        // CPU Information
        $cpu_info = $this->getCPUInfo();
        $data['CPU Model'] = $cpu_info['model'];
        $data['CPU Cores'] = $cpu_info['cores'];
        $data['CPU Usage %'] = $cpu_info['usage'];
        $data['CPU Load Average'] = $cpu_info['load_average'];
        
        // Storage Information
        $storage_info = $this->getStorageInfo();
        $data['Total Disk Space'] = $storage_info['total'];
        $data['Free Disk Space'] = $storage_info['free'];
        $data['Used Disk Space'] = $storage_info['used'];
        $data['Disk Usage %'] = $storage_info['usage_percent'];
        $data['Storage Type'] = $storage_info['type'];
        $data['File System'] = $storage_info['filesystem'];
        
        // Network Information
        $network_info = $this->getNetworkInfo();
        $data['Network Interfaces'] = $network_info['interfaces'];
        $data['Default Gateway'] = $network_info['gateway'];
        $data['DNS Servers'] = $network_info['dns'];
        
        return $data;
    }

    /**
     * Get hosting provider information
     *
     * @return array
     */
    public function getHostingInfo()
    {
        $data = [];
        
        // Detect hosting provider
        $hosting_provider = $this->detectHostingProvider();
        $data['Hosting Provider'] = $hosting_provider['name'];
        $data['Provider Confidence'] = $hosting_provider['confidence'];
        
        // Server location
        $location = $this->getServerLocation();
        $data['Server Location'] = $location['country'] . ', ' . $location['city'];
        $data['Data Center'] = $location['datacenter'];
        $data['ISP'] = $location['isp'];
        
        // Performance metrics
        $performance = $this->getPerformanceMetrics();
        $data['Response Time'] = $performance['response_time'];
        $data['Database Response'] = $performance['db_response'];
        $data['File System Speed'] = $performance['fs_speed'];
        
        // Security information
        $security = $this->getSecurityInfo();
        $data['SSL Certificate'] = $security['ssl_status'];
        $data['SSL Issuer'] = $security['ssl_issuer'];
        $data['SSL Expiry'] = $security['ssl_expiry'];
        $data['Security Headers'] = $security['headers_count'];
        
        return $data;
    }

    /**
     * Get detailed memory information
     *
     * @return array
     */
    protected function getMemoryInfo()
    {
        $memory = [
            'total' => __('Not available', 'hostcheckr'),
            'available' => __('Not available', 'hostcheckr'),
            'used' => __('Not available', 'hostcheckr'),
            'usage_percent' => __('Not available', 'hostcheckr')
        ];

        if (function_exists('sys_getloadavg') && is_readable('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $total_match);
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $available_match);
                
                if ($total_match && $available_match) {
                    $total_kb = (int) $total_match[1];
                    $available_kb = (int) $available_match[1];
                    $used_kb = $total_kb - $available_kb;
                    
                    $memory['total'] = $this->formatBytes($total_kb * 1024);
                    $memory['available'] = $this->formatBytes($available_kb * 1024);
                    $memory['used'] = $this->formatBytes($used_kb * 1024);
                    $memory['usage_percent'] = round(($used_kb / $total_kb) * 100, 1) . '%';
                }
            }
        } elseif (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
            // Windows memory detection
            $output = shell_exec('wmic computersystem get TotalPhysicalMemory /value');
            if ($output && preg_match('/TotalPhysicalMemory=(\d+)/', $output, $matches)) {
                $total_bytes = (int) $matches[1];
                $memory['total'] = $this->formatBytes($total_bytes);
            }
        }

        return $memory;
    }

    /**
     * Get CPU information
     *
     * @return array
     */
    protected function getCPUInfo()
    {
        $cpu = [
            'model' => __('Not available', 'hostcheckr'),
            'cores' => __('Not available', 'hostcheckr'),
            'usage' => __('Not available', 'hostcheckr'),
            'load_average' => __('Not available', 'hostcheckr')
        ];

        // Get CPU model and cores from /proc/cpuinfo
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo) {
                // Get CPU model
                if (preg_match('/model name\s*:\s*(.+)/', $cpuinfo, $matches)) {
                    $cpu['model'] = trim($matches[1]);
                }
                
                // Count CPU cores
                $core_count = substr_count($cpuinfo, 'processor');
                $cpu['cores'] = $core_count;
            }
        }

        // Get load average
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load) {
                $cpu['load_average'] = implode(', ', array_map(function($l) { return round($l, 2); }, $load));
                
                // Calculate approximate CPU usage percentage
                $cores = is_numeric($cpu['cores']) ? $cpu['cores'] : 1;
                $usage_percent = ($load[0] / $cores) * 100;
                $cpu['usage'] = round(min($usage_percent, 100), 1) . '%';
            }
        }

        return $cpu;
    }

    /**
     * Get storage information
     *
     * @return array
     */
    protected function getStorageInfo()
    {
        $storage = [
            'total' => __('Not available', 'hostcheckr'),
            'free' => __('Not available', 'hostcheckr'),
            'used' => __('Not available', 'hostcheckr'),
            'usage_percent' => __('Not available', 'hostcheckr'),
            'type' => __('Unknown', 'hostcheckr'),
            'filesystem' => __('Unknown', 'hostcheckr')
        ];

        $path = ABSPATH;
        
        if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
            $total_bytes = disk_total_space($path);
            $free_bytes = disk_free_space($path);
            
            if ($total_bytes !== false && $free_bytes !== false) {
                $used_bytes = $total_bytes - $free_bytes;
                
                $storage['total'] = $this->formatBytes($total_bytes);
                $storage['free'] = $this->formatBytes($free_bytes);
                $storage['used'] = $this->formatBytes($used_bytes);
                $storage['usage_percent'] = round(($used_bytes / $total_bytes) * 100, 1) . '%';
            }
        }

        // Detect storage type (SSD vs HDD)
        $storage['type'] = $this->detectStorageType();
        
        // Get filesystem type
        if (is_readable('/proc/mounts')) {
            $mounts = file_get_contents('/proc/mounts');
            if ($mounts && preg_match('/(\S+)\s+' . preg_quote(realpath($path), '/') . '\s+(\S+)/', $mounts, $matches)) {
                $storage['filesystem'] = $matches[2];
            }
        }

        return $storage;
    }

    /**
     * Detect storage type (SSD vs HDD)
     *
     * @return string
     */
    protected function detectStorageType()
    {
        // Try to detect SSD vs HDD on Linux
        if (is_readable('/sys/block')) {
            $blocks = glob('/sys/block/sd*');
            foreach ($blocks as $block) {
                $rotational_file = $block . '/queue/rotational';
                if (is_readable($rotational_file)) {
                    $rotational = trim(file_get_contents($rotational_file));
                    return $rotational === '0' ? 'SSD' : 'HDD';
                }
            }
        }

        // Fallback: Try to detect based on performance characteristics
        $start_time = microtime(true);
        $test_file = ABSPATH . 'wp-content/temp_storage_test.tmp';

        // Write/read probe only when writable to avoid noisy warnings on locked-down hosts.
        if (!is_writable(dirname($test_file))) {
            return __('Unknown', 'hostcheckr');
        }

        $write_ok = file_put_contents($test_file, str_repeat('x', 1024 * 100)); // 100KB
        if ($write_ok === false) {
            return __('Unknown', 'hostcheckr');
        }

        file_get_contents($test_file);
        wp_delete_file($test_file);
        
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        // SSD typically faster than 0.01 seconds for this test
        return $duration < 0.01 ? __('Likely SSD', 'hostcheckr') : __('Likely HDD', 'hostcheckr');
    }

    /**
     * Get network information
     *
     * @return array
     */
    protected function getNetworkInfo()
    {
        $network = [
            'interfaces' => __('Not available', 'hostcheckr'),
            'gateway' => __('Not available', 'hostcheckr'),
            'dns' => __('Not available', 'hostcheckr')
        ];
        $gateway = '';

        // Get network interfaces (Linux)
        if (is_readable('/proc/net/dev')) {
            $interfaces = [];
            $net_dev = file_get_contents('/proc/net/dev');
            if ($net_dev) {
                $lines = explode("\n", $net_dev);
                foreach ($lines as $line) {
                    if (preg_match('/^\s*(\w+):/', $line, $matches)) {
                        $interface = $matches[1];
                        if ($interface !== 'lo') { // Skip loopback
                            $interfaces[] = $interface;
                        }
                    }
                }
                $network['interfaces'] = implode(', ', $interfaces);
            }
        }

        // Get default gateway
        $gateway_cmd = 'ip route | grep default';
        if (function_exists('shell_exec')) {
            $gateway_output = shell_exec($gateway_cmd);
            if ($gateway_output && preg_match('/default via (\S+)/', $gateway_output, $matches)) {
                $gateway = $matches[1];
            }
        }
        
        // Fallback when shell_exec is blocked: parse Linux route table directly.
        if (empty($gateway)) {
            $gateway = $this->getGatewayFromProcRoute();
        }
        if (!empty($gateway)) {
            $network['gateway'] = $gateway;
        }

        // Get DNS servers
        if (is_readable('/etc/resolv.conf')) {
            $resolv = file_get_contents('/etc/resolv.conf');
            if ($resolv) {
                preg_match_all('/nameserver\s+(\S+)/', $resolv, $matches);
                if ($matches[1]) {
                    $network['dns'] = implode(', ', $matches[1]);
                }
            }
        }

        return $network;
    }

    /**
     * Get default gateway from /proc/net/route for locked-down Linux hosts.
     *
     * @return string
     */
    protected function getGatewayFromProcRoute()
    {
        if (PHP_OS_FAMILY !== 'Linux' || !is_readable('/proc/net/route')) {
            return '';
        }

        $route_data = file_get_contents('/proc/net/route');
        if (!$route_data) {
            return '';
        }

        $lines = explode("\n", trim($route_data));
        array_shift($lines); // Drop header line.

        foreach ($lines as $line) {
            $columns = preg_split('/\s+/', trim($line));
            if (!is_array($columns) || count($columns) < 3) {
                continue;
            }

            // Destination 00000000 indicates the default route.
            if ($columns[1] !== '00000000') {
                continue;
            }

            $gateway_hex = $columns[2];
            if (strlen($gateway_hex) !== 8) {
                continue;
            }

            $gateway_dec = hexdec(substr($gateway_hex, 6, 2) . substr($gateway_hex, 4, 2) . substr($gateway_hex, 2, 2) . substr($gateway_hex, 0, 2));
            $gateway_ip = long2ip($gateway_dec);

            if (!empty($gateway_ip)) {
                return $gateway_ip;
            }
        }

        return '';
    }

    /**
     * Detect capability restrictions imposed by hosting security policies.
     *
     * @return array
     */
    protected function getAccessRestrictionWarnings()
    {
        $warnings = [];
        $disabled_functions = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (!function_exists('shell_exec') || in_array('shell_exec', $disabled_functions, true)) {
            $warnings[] = __('Command execution is disabled (`shell_exec`). Some network and hardware checks may be limited.', 'hostcheckr');
        }

        if (!function_exists('stream_socket_client') || in_array('stream_socket_client', $disabled_functions, true)) {
            $warnings[] = __('Outbound socket checks are restricted. SSL certificate issuer/expiry detection may be incomplete.', 'hostcheckr');
        }

        if (PHP_OS_FAMILY === 'Linux') {
            if (!is_readable('/proc/meminfo')) {
                $warnings[] = __('Cannot read `/proc/meminfo`. RAM details are restricted by the host.', 'hostcheckr');
            }
            if (!is_readable('/proc/cpuinfo')) {
                $warnings[] = __('Cannot read `/proc/cpuinfo`. CPU model/core details are restricted by the host.', 'hostcheckr');
            }
            if (!is_readable('/proc/net/dev')) {
                $warnings[] = __('Cannot read `/proc/net/dev`. Network interface visibility is restricted.', 'hostcheckr');
            }
            if (!is_readable('/proc/net/route') && (!function_exists('shell_exec') || in_array('shell_exec', $disabled_functions, true))) {
                $warnings[] = __('Default gateway cannot be detected because both shell access and route-table access are restricted.', 'hostcheckr');
            }
        }

        if (defined('WP_CONTENT_DIR') && !is_writable(WP_CONTENT_DIR)) {
            $warnings[] = __('`wp-content` is not writable. File system speed probes are skipped for safety.', 'hostcheckr');
        }

        return array_unique($warnings);
    }

    /**
     * Detect hosting provider
     *
     * @return array
     */
    protected function detectHostingProvider()
    {
        $indicators = [
            'SiteGround' => ['siteground', 'sg-server'],
            'Bluehost' => ['bluehost', 'hostmonster'],
            'GoDaddy' => ['godaddy', 'secureserver'],
            'HostGator' => ['hostgator', 'gator'],
            'WP Engine' => ['wpengine', 'wpenginepowered'],
            'Kinsta' => ['kinsta', 'gcp'],
            'Cloudways' => ['cloudways'],
            'A2 Hosting' => ['a2hosting'],
            'InMotion' => ['inmotionhosting'],
            'DreamHost' => ['dreamhost'],
            'Namecheap' => ['namecheap'],
            'DigitalOcean' => ['digitalocean'],
            'Linode' => ['linode'],
            'Vultr' => ['vultr'],
            'AWS' => ['amazonaws', 'aws'],
            'Google Cloud' => ['googlecloud', 'gcp'],
            'Microsoft Azure' => ['azure', 'microsoft'],
        ];

        $server_info = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))) : '';
        $hostname_raw = gethostname();
        $hostname = is_string($hostname_raw) ? strtolower($hostname_raw) : '';
        $server_name = isset($_SERVER['SERVER_NAME']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))) : '';
        
        foreach ($indicators as $provider => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($server_info, $keyword) !== false || 
                    strpos($hostname, $keyword) !== false || 
                    strpos($server_name, $keyword) !== false) {
                    return ['name' => $provider, 'confidence' => __('High', 'hostcheckr')];
                }
            }
        }

        return ['name' => __('Unknown', 'hostcheckr'), 'confidence' => __('N/A', 'hostcheckr')];
    }

    /**
     * Get server location information
     *
     * @return array
     */
    protected function getServerLocation()
    {
        $location = [
            'country' => __('Unknown', 'hostcheckr'),
            'city' => __('Unknown', 'hostcheckr'),
            'datacenter' => __('Unknown', 'hostcheckr'),
            'isp' => __('Unknown', 'hostcheckr')
        ];

        $server_ip = $this->getServerIP();
        
        // Try to get location from IP (this would typically use an external service)
        // For demo purposes, we'll use some basic detection
        if ($server_ip && $server_ip !== '127.0.0.1') {
            // This is a simplified example - in production, you'd use a geolocation service
            $location['country'] = __('Detected from IP', 'hostcheckr');
            $location['city'] = __('City from IP', 'hostcheckr');
        }

        return $location;
    }

    /**
     * Get performance metrics
     *
     * @return array
     */
    protected function getPerformanceMetrics()
    {
        $performance = [];
        
        // Measure response time
        $start_time = microtime(true);
        // Simulate some work
        usleep(1000);
        $end_time = microtime(true);
        $performance['response_time'] = round(($end_time - $start_time) * 1000, 2) . ' ms';
        
        // Database response time
        global $wpdb;
        $db_start = microtime(true);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->get_var("SELECT 1");
        $db_end = microtime(true);
        $performance['db_response'] = round(($db_end - $db_start) * 1000, 2) . ' ms';
        
        // File system speed test
        $fs_start = microtime(true);
        $test_file = ABSPATH . 'wp-content/temp_fs_test.tmp';
        $fs_speed = __('Not available', 'hostcheckr');
        if (is_writable(dirname($test_file))) {
            $write_ok = file_put_contents($test_file, 'test');
            if ($write_ok !== false) {
                file_get_contents($test_file);
                wp_delete_file($test_file);
                $fs_end = microtime(true);
                $fs_speed = round(($fs_end - $fs_start) * 1000, 2) . ' ms';
            }
        }
        $performance['fs_speed'] = $fs_speed;
        
        return $performance;
    }

    /**
     * Get security information
     *
     * @return array
     */
    protected function getSecurityInfo()
    {
        $security = [
            'ssl_status' => __('Not available', 'hostcheckr'),
            'ssl_issuer' => __('Not available', 'hostcheckr'),
            'ssl_expiry' => __('Not available', 'hostcheckr'),
            'headers_count' => 0
        ];

        // Check SSL
        if ($this->isHTTPS()) {
            $security['ssl_status'] = __('Enabled', 'hostcheckr');
            
            // Get SSL certificate info
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $stream = @stream_socket_client(
                isset($_SERVER['HTTP_HOST']) ? 'ssl://' . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) . ':443' : 'ssl://localhost:443',
                $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
            
            if ($stream) {
                $params = stream_context_get_params($stream);
                if (isset($params['options']['ssl']['peer_certificate'])) {
                    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                    if ($cert) {
                        $security['ssl_issuer'] = $cert['issuer']['CN'] ?? __('Unknown', 'hostcheckr');
                        $security['ssl_expiry'] = gmdate('Y-m-d H:i:s', $cert['validTo_time_t']);
                    }
                }
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($stream);
            }
        } else {
            $security['ssl_status'] = __('Disabled', 'hostcheckr');
        }

        // Count security headers
        $security_headers = ['X-Frame-Options', 'X-XSS-Protection', 'X-Content-Type-Options', 'Strict-Transport-Security'];
        $headers_present = 0;
        foreach ($security_headers as $header) {
            if (isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
                $headers_present++;
            }
        }
        $security['headers_count'] = $headers_present . '/' . count($security_headers);

        return $security;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes)
    {
        if ($bytes === false || $bytes === null) {
            return __('Not available', 'hostcheckr');
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get server uptime
     *
     * @return string
     */
    protected function getServerUptime()
    {
        if (is_readable('/proc/uptime')) {
            $uptime_raw = file_get_contents('/proc/uptime');
            if ($uptime_raw === false) {
                return __('Not available', 'hostcheckr');
            }

            $uptime_parts = explode(' ', trim($uptime_raw));
            $uptime_seconds = isset($uptime_parts[0]) ? (int) floor((float) $uptime_parts[0]) : 0;
            
            $days = floor($uptime_seconds / 86400);
            $hours = floor(($uptime_seconds % 86400) / 3600);
            $minutes = floor(($uptime_seconds % 3600) / 60);
            
            /* translators: 1: days, 2: hours, 3: minutes */
            return sprintf(__('%1$d days, %2$d hours, %3$d minutes', 'hostcheckr'), $days, $hours, $minutes);
        }
        
        return __('Not available', 'hostcheckr');
    }

    /**
     * Get server load
     *
     * @return string
     */
    protected function getServerLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load) {
                return implode(', ', array_map(function($l) { return round($l, 2); }, $load));
            }
        }
        
        return __('Not available', 'hostcheckr');
    }

    /**
     * Get server IP address
     *
     * @return string
     */
    protected function getServerIP()
    {
        $server_addr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '';
        $local_addr = isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : '';
        return $server_addr ?: ($local_addr ?: __('Not available', 'hostcheckr'));
    }

    /**
     * Check if HTTPS is enabled
     *
     * @return bool
     */
    protected function isHTTPS()
    {
        $https = isset($_SERVER['HTTPS']) ? sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) : '';
        $server_port = isset($_SERVER['SERVER_PORT']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_PORT'])) : '';
        $forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';
        
        return (!empty($https) && $https !== 'off') || 
               $server_port == 443 ||
               (!empty($forwarded_proto) && $forwarded_proto === 'https');
    }

    /**
     * Get server OS information
     *
     * @return string
     */
    protected function getServerOS()
    {
        if (function_exists('php_uname')) {
            return php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')';
        }
        
        return PHP_OS;
    }

    /**
     * Get server architecture
     *
     * @return string
     */
    protected function getServerArchitecture()
    {
        if (function_exists('php_uname')) {
            return php_uname('m');
        }
        
        return __('Unknown', 'hostcheckr');
    }

    /**
     * Truncate string to specified length
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function truncateString($string, $length = 50)
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }

    /**
     * Convert PHP variable (G/M/K) to bytes
     *
     * @param mixed $value
     * @return integer
     */
    public function toBytes($value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim($value);
        $val = (int) $value;
        $last = strtolower($value[strlen($value)-1]);
        
        switch ($last) {
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Transform value to string
     *
     * @param mixed $value Value
     * @return string
     */
    public function toString($value)
    {
        if ($value === true) {
            return __('Yes', 'hostcheckr');
        } elseif ($value === false) {
            return __('No', 'hostcheckr');
        } elseif ($value === null) {
            return __('N/A', 'hostcheckr');
        }

        return strval($value);
    }

    /**
     * Get html class for WordPress admin
     *
     * @param array $data
     * @return string
     */
    public function toHtmlClass(array $data)
    {
        if (count($data) === 1 && !is_bool($data[0])) {
            return self::TYPE_INFO_CLASS;
        }

        if (count($data) === 1 && is_bool($data[0])) {
            $result = $data[0];
        } elseif (array_key_exists(3, $data)) {
            $result = $data[3];
        } else {
            if ($data[2] >= $data[1]) {
                $result = self::TYPE_OK;
            } elseif ($data[2] >= $data[0]) {
                $result = self::TYPE_WARNING;
            } else {
                $result = self::TYPE_ERROR;
            }
        }

        if ($result === false) {
            return self::TYPE_ERROR_CLASS;
        }

        if ($result === null) {
            return self::TYPE_WARNING_CLASS;
        }

        return self::TYPE_SUCCESS_CLASS;
    }

    /**
     * Get recommendation for a specific issue
     *
     * @param array $item Issue details
     * @return string HTML recommendation
     */
    protected function getRecommendation($item)
    {
        $recommendations = [
            'Version' => [
                'PHP Version' => __('Update PHP through your hosting provider or server administrator. Newer PHP versions offer better performance and security.', 'hostcheckr'),
                'MySQL Version' => __('Contact your hosting provider to upgrade MySQL/MariaDB for improved performance and security features.', 'hostcheckr'),
            ],
            'Configuration' => [
                'memory_limit' => __('Increase PHP memory limit in php.ini or contact your hosting provider. Higher memory allows WordPress to handle more complex operations.', 'hostcheckr'),
                'max_execution_time' => __('Increase max execution time in php.ini to prevent script timeouts during intensive operations.', 'hostcheckr'),
                'post_max_size' => __('Increase post_max_size in php.ini to allow larger form submissions and file uploads.', 'hostcheckr'),
                'upload_max_filesize' => __('Increase upload_max_filesize in php.ini to allow larger file uploads through WordPress media library.', 'hostcheckr'),
                'max_input_vars' => __('Increase max_input_vars in php.ini to handle forms with many fields, especially useful for theme customizers.', 'hostcheckr'),
                'max_input_time' => __('Increase max_input_time in php.ini to allow more time for processing form data.', 'hostcheckr'),
                'file_uploads' => __('Enable file_uploads in php.ini to allow file uploads through WordPress.', 'hostcheckr'),
            ],
            'Extension' => [
                'cURL' => __('Install php-curl extension. Required for HTTP requests, plugin updates, and external API communications.', 'hostcheckr'),
                'GD' => __('Install php-gd extension. Required for image processing, thumbnail generation, and image editing features.', 'hostcheckr'),
                'MySQLi' => __('Install php-mysqli extension. Required for database connectivity and operations.', 'hostcheckr'),
                'OpenSSL' => __('Install php-openssl extension. Required for secure connections and SSL/TLS communications.', 'hostcheckr'),
                'Multibyte String' => __('Install php-mbstring extension. Required for proper handling of international characters and UTF-8 text.', 'hostcheckr'),
                'JSON' => __('Install php-json extension. Required for data exchange and API communications.', 'hostcheckr'),
                'File Info' => __('Install php-fileinfo extension. Required for file type detection and security validation.', 'hostcheckr'),
                'Zip' => __('Install php-zip extension. Required for plugin/theme installation and backup operations.', 'hostcheckr'),
                'DOM' => __('Install php-dom extension. Required for XML processing and HTML manipulation.', 'hostcheckr'),
                'SimpleXML' => __('Install php-simplexml extension. Required for XML parsing and RSS feed processing.', 'hostcheckr'),
                'XMLReader' => __('Install php-xmlreader extension. Required for efficient XML processing.', 'hostcheckr'),
                'ImageMagick' => __('Install php-imagick extension. Recommended for advanced image processing and better image quality.', 'hostcheckr'),
                'Sodium' => __('Install php-sodium extension. Recommended for modern cryptographic operations and enhanced security.', 'hostcheckr'),
                'Exif' => __('Install php-exif extension. Recommended for reading image metadata and EXIF data.', 'hostcheckr'),
            ]
        ];
        
        $type = $item['type'];
        $itemName = $item['item'];
        $base_recommendation = '';
        
        if (isset($recommendations[$type][$itemName])) {
            $base_recommendation = $recommendations[$type][$itemName];
        } else {
            // Generic recommendations based on type
            switch ($type) {
                case 'Version':
                    $base_recommendation = __('Contact your hosting provider or system administrator to upgrade this component.', 'hostcheckr');
                    break;
                case 'Configuration':
                    $base_recommendation = __('Modify your php.ini file or contact your hosting provider to adjust this setting.', 'hostcheckr');
                    break;
                case 'Extension':
                    $base_recommendation = __('Install this PHP extension through your package manager or contact your hosting provider.', 'hostcheckr');
                    break;
                default:
                    $base_recommendation = __('Please consult the WordPress documentation or contact your hosting provider for assistance.', 'hostcheckr');
                    break;
            }
        }

        $html = '<p class="recommendation-text">' . $base_recommendation . '</p>';

        $wp_config_snippet = $this->getWpConfigFixSnippet($item);
        if (!empty($wp_config_snippet)) {
            $html .= '<div class="recommendation-snippet">';
            $html .= '<p class="recommendation-backup"><strong>' . esc_html__('Important:', 'hostcheckr') . '</strong> ' . esc_html__('Back up your wp-config.php before applying this fix.', 'hostcheckr') . '</p>';
            $html .= '<p class="recommendation-insert">' . esc_html__("Add this above the line that says \"/* That's all, stop editing! Happy publishing. */\" in wp-config.php:", 'hostcheckr') . '</p>';
            $html .= '<pre><code>' . esc_html($wp_config_snippet) . '</code></pre>';
            $html .= '<div class="recommendation-actions"><button type="button" class="button button-secondary copy-config-btn" data-copy="' . esc_attr($wp_config_snippet) . '">' . esc_html__('Copy wp-config fix', 'hostcheckr') . '</button></div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * AJAX endpoint for live database monitor metrics.
     */
    public function ajax_live_db_metrics()
    {
        check_ajax_referer('hostcheckr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'hostcheckr')], 403);
        }

        wp_send_json_success($this->getLiveDatabaseMetrics());
    }

    /**
     * AJAX endpoint to save live DB monitor settings.
     */
    public function ajax_save_live_db_settings()
    {
        check_ajax_referer('hostcheckr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'hostcheckr')], 403);
        }

        $enabled_raw = isset($_POST['enabled']) ? sanitize_text_field(wp_unslash($_POST['enabled'])) : '1';
        $interval_raw = isset($_POST['interval']) ? sanitize_text_field(wp_unslash($_POST['interval'])) : '10';
        $max_patterns_raw = isset($_POST['max_patterns']) ? sanitize_text_field(wp_unslash($_POST['max_patterns'])) : '5';
        $lightweight_mode_raw = isset($_POST['lightweight_mode']) ? sanitize_text_field(wp_unslash($_POST['lightweight_mode'])) : '0';

        $settings = [
            'enabled' => $enabled_raw === '1',
            'interval' => max(5, min(120, (int) $interval_raw)),
            'max_patterns' => max(1, min(20, (int) $max_patterns_raw)),
            'lightweight_mode' => $lightweight_mode_raw === '1',
        ];

        update_option('hostcheckr_live_db_settings', $settings, false);
        wp_cache_delete('hostcheckr_live_db_metrics', 'hostcheckr');

        wp_send_json_success([
            'settings' => $settings,
            'message' => __('Live monitor settings saved.', 'hostcheckr'),
        ]);
    }

    /**
     * Collect live database metrics with safe fallbacks.
     *
     * @return array
     */
    protected function getLiveDatabaseMetrics()
    {
        global $wpdb;
        $settings = $this->getLiveDbSettings();

        if (!$settings['enabled']) {
            return [
                'response_ms' => __('Disabled', 'hostcheckr'),
                'autoload_mb' => __('Disabled', 'hostcheckr'),
                'db_size_mb' => __('Disabled', 'hostcheckr'),
                'revisions' => __('Disabled', 'hostcheckr'),
                'threads_connected' => __('Disabled', 'hostcheckr'),
                'slow_queries' => __('Disabled', 'hostcheckr'),
                'warnings' => [__('Live database monitoring is disabled in settings.', 'hostcheckr')],
                'slow_query_patterns' => [],
                'sampled_at' => current_time('mysql'),
                'sampled_unix' => time(),
            ];
        }

        $cache_key = 'hostcheckr_live_db_metrics';
        $cached_metrics = wp_cache_get($cache_key, 'hostcheckr');
        if (is_array($cached_metrics) && isset($cached_metrics['sampled_unix']) && ((time() - (int) $cached_metrics['sampled_unix']) < 5)) {
            return $cached_metrics;
        }

        $metrics = [
            'response_ms' => __('Not available', 'hostcheckr'),
            'autoload_mb' => __('Not available', 'hostcheckr'),
            'db_size_mb' => __('Not available', 'hostcheckr'),
            'revisions' => __('Not available', 'hostcheckr'),
            'threads_connected' => __('Not available', 'hostcheckr'),
            'slow_queries' => __('Not available', 'hostcheckr'),
            'warnings' => [],
            'slow_query_patterns' => [],
            'sampled_at' => current_time('mysql'),
            'sampled_unix' => time(),
        ];

        $start = microtime(true);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $probe = $wpdb->get_var('SELECT 1');
        if ($probe !== null) {
            $metrics['response_ms'] = round((microtime(true) - $start) * 1000, 2) . ' ms';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $autoload_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
        if ($autoload_size !== null) {
            $metrics['autoload_mb'] = round(((float) $autoload_size) / 1024 / 1024, 2) . ' MB';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $revisions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        if ($revisions !== null) {
            $metrics['revisions'] = number_format_i18n((int) $revisions);
        }

        // MySQL status is often blocked by hosting policies.
        if (empty($settings['lightweight_mode'])) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $threads_row = $wpdb->get_row("SHOW STATUS LIKE 'Threads_connected'");
            if (is_object($threads_row) && isset($threads_row->Value)) {
                $metrics['threads_connected'] = (string) $threads_row->Value;
            } else {
                $metrics['warnings'][] = __('`SHOW STATUS` is restricted on this host. Thread metrics are unavailable.', 'hostcheckr');
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $slow_row = $wpdb->get_row("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            if (is_object($slow_row) && isset($slow_row->Value)) {
                $metrics['slow_queries'] = (string) $slow_row->Value;
            } else {
                $metrics['warnings'][] = __('Global status metrics are restricted. Slow query counters are unavailable.', 'hostcheckr');
            }

            $db_size_query = $wpdb->prepare(
                'SELECT SUM(data_length + index_length) / 1024 / 1024 AS size FROM information_schema.TABLES WHERE table_schema = %s',
                DB_NAME
            );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $db_size = $wpdb->get_var($db_size_query);
            if ($db_size !== null) {
                $metrics['db_size_mb'] = round((float) $db_size, 2) . ' MB';
            } else {
                $metrics['warnings'][] = __('`information_schema` access is limited. Database size is estimated as unavailable.', 'hostcheckr');
            }
        } else {
            $metrics['warnings'][] = __('Lightweight mode is enabled. Status and information_schema probes are skipped to reduce load.', 'hostcheckr');
        }

        $slow_query_samples = $this->getSlowQuerySamples();
        if (!empty($slow_query_samples)) {
            $metrics['slow_query_patterns'] = $this->buildSlowQueryPatterns($slow_query_samples, (int) $settings['max_patterns']);
        } else {
            $metrics['warnings'][] = __('No slow query samples found. Connect a slow-query source to enable pattern analysis.', 'hostcheckr');
        }

        wp_cache_set($cache_key, $metrics, 'hostcheckr', 5);

        return $metrics;
    }

    /**
     * Get slow query samples from known storage, then allow external providers via filter.
     *
     * @return array
     */
    protected function getSlowQuerySamples()
    {
        $samples = get_option('hostcheckr_slow_query_samples', []);

        // External integrations can provide samples:
        // [
        //   ['query' => 'SELECT ...', 'time' => 0.345],
        //   ...
        // ]
        $samples = apply_filters('hostcheckr_slow_query_samples', $samples);

        if (!is_array($samples)) {
            return [];
        }

        $normalized = [];
        foreach ($samples as $sample) {
            if (!is_array($sample) || empty($sample['query'])) {
                continue;
            }

            $normalized[] = [
                'query' => (string) $sample['query'],
                'time' => isset($sample['time']) ? (float) $sample['time'] : 0.0,
            ];
        }

        return $normalized;
    }

    /**
     * Build grouped slow-query signatures for dashboard display.
     *
     * @param array $samples Slow query sample rows.
     * @return array
     */
    protected function buildSlowQueryPatterns(array $samples, $limit = 5)
    {
        $patterns = [];

        foreach ($samples as $sample) {
            $query = $sample['query'];
            $signature = $this->normalizeSlowQuery($query);
            if (empty($signature)) {
                continue;
            }

            if (!isset($patterns[$signature])) {
                $patterns[$signature] = [
                    'signature' => $signature,
                    'count' => 0,
                    'total_time' => 0.0,
                    'max_time' => 0.0,
                ];
            }

            $patterns[$signature]['count']++;
            $patterns[$signature]['total_time'] += $sample['time'];
            $patterns[$signature]['max_time'] = max($patterns[$signature]['max_time'], $sample['time']);
        }

        if (empty($patterns)) {
            return [];
        }

        uasort($patterns, static function($a, $b) {
            return $b['total_time'] <=> $a['total_time'];
        });

        $safe_limit = max(1, min(20, (int) $limit));
        $top_patterns = array_slice(array_values($patterns), 0, $safe_limit);
        foreach ($top_patterns as &$pattern) {
            $pattern['avg_time'] = $pattern['count'] > 0 ? round($pattern['total_time'] / $pattern['count'], 4) : 0.0;
            $pattern['total_time'] = round($pattern['total_time'], 4);
            $pattern['max_time'] = round($pattern['max_time'], 4);
        }
        unset($pattern);

        return $top_patterns;
    }

    /**
     * Read and sanitize live DB monitor settings.
     *
     * @return array
     */
    protected function getLiveDbSettings()
    {
        $defaults = [
            'enabled' => true,
            'interval' => 10,
            'max_patterns' => 5,
            'lightweight_mode' => true,
        ];

        $settings = get_option('hostcheckr_live_db_settings', []);
        if (!is_array($settings)) {
            return $defaults;
        }

        return [
            'enabled' => isset($settings['enabled']) ? (bool) $settings['enabled'] : $defaults['enabled'],
            'interval' => isset($settings['interval']) ? max(5, min(120, (int) $settings['interval'])) : $defaults['interval'],
            'max_patterns' => isset($settings['max_patterns']) ? max(1, min(20, (int) $settings['max_patterns'])) : $defaults['max_patterns'],
            'lightweight_mode' => isset($settings['lightweight_mode']) ? (bool) $settings['lightweight_mode'] : $defaults['lightweight_mode'],
        ];
    }

    /**
     * Normalize SQL into a stable query signature.
     *
     * @param string $query SQL query.
     * @return string
     */
    protected function normalizeSlowQuery($query)
    {
        $query = strtolower(trim((string) $query));
        if ($query === '') {
            return '';
        }

        // Remove comments and collapse whitespace.
        $query = preg_replace('/\/\*.*?\*\//s', '', $query);
        $query = preg_replace('/--.*$/m', '', $query);
        $query = preg_replace('/\s+/', ' ', $query);

        // Replace string and numeric literals.
        $query = preg_replace("/'[^']*'/", "'%s'", $query);
        $query = preg_replace('/\b\d+\b/', '%d', $query);

        // Keep signatures compact for UI.
        if (strlen($query) > 180) {
            $query = substr($query, 0, 177) . '...';
        }

        return $query;
    }

    /**
     * Build wp-config.php snippet for supported configuration fixes.
     *
     * @param array $item Issue details
     * @return string
     */
    protected function getWpConfigFixSnippet($item)
    {
        if (!isset($item['type']) || $item['type'] !== 'Configuration' || empty($item['item'])) {
            return '';
        }

        $setting = $item['item'];
        $target = isset($item['recommended']) ? $item['recommended'] : (isset($item['required']) ? $item['required'] : null);

        if (empty($target)) {
            return '';
        }

        switch ($setting) {
            case 'memory_limit':
                $target_memory = preg_replace('/[^0-9A-Za-z]/', '', (string) $target);
                return "define( 'WP_MEMORY_LIMIT', '" . $target_memory . "' );\ndefine( 'WP_MAX_MEMORY_LIMIT', '" . $target_memory . "' );";
            case 'max_execution_time':
            case 'max_input_time':
            case 'max_input_vars':
            case 'post_max_size':
            case 'upload_max_filesize':
                $target_value = preg_replace('/[^0-9A-Za-z]/', '', (string) $target);
                return "@ini_set( '" . $setting . "', '" . $target_value . "' );";
            case 'file_uploads':
                return "@ini_set( 'file_uploads', '1' );";
            default:
                return '';
        }
    }

    /**
     * Detect Web server
     *
     * @return string
     */
    protected function getWebServer()
    {
        if (!isset($_SERVER['SERVER_SOFTWARE'])) {
            return __('Not detected', 'hostcheckr');
        }
        
        $server = sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']));
        
        if (stristr($server, 'Apache') !== false) {
            return 'Apache';
        } elseif (stristr($server, 'LiteSpeed') !== false) {
            return 'LiteSpeed';
        } elseif (stristr($server, 'Nginx') !== false) {
            return 'Nginx';
        } elseif (stristr($server, 'lighttpd') !== false) {
            return 'lighttpd';
        } elseif (stristr($server, 'IIS') !== false) {
            return 'Microsoft IIS';
        }

        return __('Not detected', 'hostcheckr');
    }
}

/**
 * Initialize the plugin
 */
function hostcheckr_init() {
    if (is_admin()) {
        new HostCheckr();
    }
}
add_action('plugins_loaded', 'hostcheckr_init');

/**
 * Add plugin action links
 */
function hostcheckr_plugin_action_links($links) {
    $action_links = array(
        'dashboard' => '<a href="' . esc_url(admin_url('admin.php?page=hostcheckr')) . '">' . esc_html__('Dashboard', 'hostcheckr') . '</a>',
        'support' => '<a href="' . esc_url('https://hostcheckr.kloudboy.com/support') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Support', 'hostcheckr') . '</a>',
    );
    return array_merge($action_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'hostcheckr_plugin_action_links');
