<?php
/**
 * Uninstall HostCheckr
 *
 * @package HostCheckr
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('hostcheckr_version');

// Delete any transients
delete_transient('hostcheckr_system_info');

// Note: We don't delete user data or settings as per WordPress guidelines
// Users may want to keep their configuration if they reinstall the plugin