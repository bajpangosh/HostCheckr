<?php
/**
 * Plugin Constants
 *
 * @package HostCheckr
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
if (!defined('HOSTCHECKR_VERSION')) {
    define('HOSTCHECKR_VERSION', '1.0.0');
}

// Plugin paths
if (!defined('HOSTCHECKR_PLUGIN_URL')) {
    define('HOSTCHECKR_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
}

if (!defined('HOSTCHECKR_PLUGIN_PATH')) {
    define('HOSTCHECKR_PLUGIN_PATH', plugin_dir_path(dirname(__FILE__)));
}

if (!defined('HOSTCHECKR_PLUGIN_FILE')) {
    define('HOSTCHECKR_PLUGIN_FILE', dirname(__FILE__) . '/hostcheckr.php');
}

// Plugin requirements
if (!defined('HOSTCHECKR_MIN_PHP_VERSION')) {
    define('HOSTCHECKR_MIN_PHP_VERSION', '7.4');
}

if (!defined('HOSTCHECKR_MIN_WP_VERSION')) {
    define('HOSTCHECKR_MIN_WP_VERSION', '5.0');
}