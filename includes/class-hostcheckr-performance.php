<?php
/**
 * Performance Diagnostics for HostCheckr
 *
 * @package HostCheckr
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance Diagnostics Class
 */
class HostCheckr_Performance {
    
    /**
     * Get comprehensive performance diagnostics
     *
     * @return array Performance issues and recommendations
     */
    public function diagnose_performance() {
        $issues = [];
        
        // Check database performance
        $db_issues = $this->check_database_performance();
        if (!empty($db_issues)) {
            $issues = array_merge($issues, $db_issues);
        }
        
        // Check plugin performance
        $plugin_issues = $this->check_plugin_performance();
        if (!empty($plugin_issues)) {
            $issues = array_merge($issues, $plugin_issues);
        }
        
        // Check theme performance
        $theme_issues = $this->check_theme_performance();
        if (!empty($theme_issues)) {
            $issues = array_merge($issues, $theme_issues);
        }
        
        // Check caching
        $cache_issues = $this->check_caching();
        if (!empty($cache_issues)) {
            $issues = array_merge($issues, $cache_issues);
        }
        
        // Check server resources
        $resource_issues = $this->check_server_resources();
        if (!empty($resource_issues)) {
            $issues = array_merge($issues, $resource_issues);
        }
        
        // Check autoload data
        $autoload_issues = $this->check_autoload_data();
        if (!empty($autoload_issues)) {
            $issues = array_merge($issues, $autoload_issues);
        }
        
        return $issues;
    }
    
    /**
     * Check database performance
     */
    private function check_database_performance() {
        global $wpdb;
        $issues = [];
        $live_db_settings = get_option('hostcheckr_live_db_settings', []);
        $lightweight_mode = is_array($live_db_settings) && !empty($live_db_settings['lightweight_mode']);

        if ($lightweight_mode) {
            $issues[] = [
                'title' => __('Lightweight Mode Active', 'hostcheckr'),
                'severity' => 'info',
                'value' => __('Enabled', 'hostcheckr'),
                'description' => __('Heavy database probes are skipped to reduce load on restrictive/shared hosting.', 'hostcheckr'),
                'recommendation' => __('Disable lightweight mode in Live Monitor Settings if you need full database size/overhead analysis.', 'hostcheckr'),
            ];
        }
        
        // Check database size
        if (!$lightweight_mode) {
            $db_size_query = $wpdb->prepare(
                'SELECT SUM(data_length + index_length) / 1024 / 1024 AS size FROM information_schema.TABLES WHERE table_schema = %s',
                DB_NAME
            );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $db_size = $wpdb->get_var($db_size_query);
        
            if ($db_size > 1000) {
                $issues[] = [
                    'title' => __('Large Database Size', 'hostcheckr'),
                    'severity' => 'warning',
                    'value' => round($db_size, 2) . ' MB',
                    'description' => __('Your database is quite large which can slow down queries.', 'hostcheckr'),
                    'recommendation' => __('Consider cleaning up old revisions, spam comments, and transients.', 'hostcheckr'),
                ];
            }
        }
        
        // Check for table overhead
        if (!$lightweight_mode) {
            $overhead_query = $wpdb->prepare(
                'SELECT table_name, data_free FROM information_schema.TABLES WHERE table_schema = %s AND data_free > 0',
                DB_NAME
            );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $overhead = $wpdb->get_results($overhead_query);
            
            if (!empty($overhead)) {
                $total_overhead = 0;
                foreach ($overhead as $table) {
                    $total_overhead += $table->data_free;
                }
                
                if ($total_overhead > 10485760) { // 10MB
                    $issues[] = [
                        'title' => __('Database Tables Need Optimization', 'hostcheckr'),
                        'severity' => 'warning',
                        'value' => round($total_overhead / 1024 / 1024, 2) . ' MB overhead',
                        'description' => __('Your database tables have overhead that can be optimized.', 'hostcheckr'),
                        'recommendation' => __('Run database optimization using a plugin like WP-Optimize.', 'hostcheckr'),
                    ];
                }
            }
        }
        
        // Check post revisions
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $revisions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        
        if ($revisions > 1000) {
            $issues[] = [
                'title' => __('Excessive Post Revisions', 'hostcheckr'),
                'severity' => 'warning',
                'value' => $revisions . ' revisions',
                'description' => __('Too many post revisions can bloat your database.', 'hostcheckr'),
                'recommendation' => __('Limit revisions by adding define(\'WP_POST_REVISIONS\', 5); to wp-config.php', 'hostcheckr'),
                ];
        }
        
        return $issues;
    }
    
    /**
     * Check plugin performance
     */
    private function check_plugin_performance() {
        $issues = [];
        
        // Count active plugins
        $active_plugins = get_option('active_plugins', []);
        $plugin_count = count($active_plugins);
        
        if ($plugin_count > 30) {
            $issues[] = [
                'title' => __('Too Many Active Plugins', 'hostcheckr'),
                'severity' => 'critical',
                'value' => $plugin_count . ' plugins',
                'description' => __('Having too many plugins can significantly slow down your site.', 'hostcheckr'),
                'recommendation' => __('Deactivate and remove plugins you don\'t need. Consider combining functionality.', 'hostcheckr'),
            ];
        } elseif ($plugin_count > 20) {
            $issues[] = [
                'title' => __('Many Active Plugins', 'hostcheckr'),
                'severity' => 'warning',
                'value' => $plugin_count . ' plugins',
                'description' => __('Consider reducing the number of active plugins.', 'hostcheckr'),
                'recommendation' => __('Review your plugins and deactivate any that aren\'t essential.', 'hostcheckr'),
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check theme performance
     */
    private function check_theme_performance() {
        $issues = [];
        $theme = wp_get_theme();
        
        // Check if using a page builder
        $page_builders = ['elementor', 'divi', 'beaver-builder', 'wpbakery', 'oxygen'];
        $active_plugins = get_option('active_plugins', []);
        
        foreach ($page_builders as $builder) {
            foreach ($active_plugins as $plugin) {
                if (strpos($plugin, $builder) !== false) {
                    $issues[] = [
                        'title' => __('Page Builder Detected', 'hostcheckr'),
                        'severity' => 'info',
                        'value' => ucfirst(str_replace('-', ' ', $builder)),
                        'description' => __('Page builders can add overhead to your site.', 'hostcheckr'),
                        'recommendation' => __('Ensure you\'re using caching and consider optimizing page builder output.', 'hostcheckr'),
                    ];
                    break 2;
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Check caching configuration
     */
    private function check_caching() {
        $issues = [];
        
        // Check for object caching
        if (!wp_using_ext_object_cache()) {
            $issues[] = [
                'title' => __('No Object Caching', 'hostcheckr'),
                'severity' => 'critical',
                'value' => __('Not Active', 'hostcheckr'),
                'description' => __('Object caching can dramatically improve database performance.', 'hostcheckr'),
                'recommendation' => __('Install Redis or Memcached for object caching.', 'hostcheckr'),
            ];
        }
        
        // Check for page caching plugins
        $caching_plugins = ['wp-super-cache', 'w3-total-cache', 'wp-rocket', 'litespeed-cache', 'wp-fastest-cache'];
        $active_plugins = get_option('active_plugins', []);
        $has_cache_plugin = false;
        
        foreach ($caching_plugins as $cache_plugin) {
            foreach ($active_plugins as $plugin) {
                if (strpos($plugin, $cache_plugin) !== false) {
                    $has_cache_plugin = true;
                    break 2;
                }
            }
        }
        
        if (!$has_cache_plugin) {
            $issues[] = [
                'title' => __('No Page Caching Plugin', 'hostcheckr'),
                'severity' => 'critical',
                'value' => __('Not Active', 'hostcheckr'),
                'description' => __('Page caching is essential for WordPress performance.', 'hostcheckr'),
                'recommendation' => __('Install a caching plugin like WP Rocket, LiteSpeed Cache, or W3 Total Cache.', 'hostcheckr'),
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check server resources
     */
    private function check_server_resources() {
        $issues = [];
        
        // Check PHP memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->convert_to_bytes($memory_limit);
        
        if ($memory_bytes < 268435456) { // 256MB
            $issues[] = [
                'title' => __('Low PHP Memory Limit', 'hostcheckr'),
                'severity' => 'critical',
                'value' => $memory_limit,
                'description' => __('Low memory limit can cause slow performance and errors.', 'hostcheckr'),
                'recommendation' => __('Increase PHP memory_limit to at least 256M in php.ini or wp-config.php', 'hostcheckr'),
            ];
        }
        
        // Check max execution time
        $max_execution = ini_get('max_execution_time');
        if ($max_execution < 60 && $max_execution != 0) {
            $issues[] = [
                'title' => __('Low Max Execution Time', 'hostcheckr'),
                'severity' => 'warning',
                'value' => $max_execution . 's',
                'description' => __('Short execution time can cause timeouts.', 'hostcheckr'),
                'recommendation' => __('Increase max_execution_time to at least 60 seconds.', 'hostcheckr'),
            ];
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $issues[] = [
                'title' => __('Outdated PHP Version', 'hostcheckr'),
                'severity' => 'critical',
                'value' => PHP_VERSION,
                'description' => __('Older PHP versions are slower and less secure.', 'hostcheckr'),
                'recommendation' => __('Upgrade to PHP 8.0 or higher for better performance.', 'hostcheckr'),
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check autoload data
     */
    private function check_autoload_data() {
        global $wpdb;
        $issues = [];
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $autoload_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
        
        if ($autoload_size > 1048576) { // 1MB
            $issues[] = [
                'title' => __('Large Autoload Data', 'hostcheckr'),
                'severity' => 'critical',
                'value' => round($autoload_size / 1024 / 1024, 2) . ' MB',
                'description' => __('Too much autoloaded data slows down every page load.', 'hostcheckr'),
                'recommendation' => __('Review and clean up autoloaded options. Use a plugin like Query Monitor to identify large autoloaded options.', 'hostcheckr'),
            ];
        } elseif ($autoload_size > 524288) { // 512KB
            $issues[] = [
                'title' => __('Moderate Autoload Data', 'hostcheckr'),
                'severity' => 'warning',
                'value' => round($autoload_size / 1024, 2) . ' KB',
                'description' => __('Autoloaded data is getting large.', 'hostcheckr'),
                'recommendation' => __('Monitor autoloaded options and clean up if needed.', 'hostcheckr'),
            ];
        }
        
        return $issues;
    }
    
    /**
     * Convert size string to bytes
     */
    private function convert_to_bytes($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        $size = (int) $size;
        
        switch($last) {
            case 'g':
                $size *= 1024;
                // Fall through
            case 'm':
                $size *= 1024;
                // Fall through
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
}
