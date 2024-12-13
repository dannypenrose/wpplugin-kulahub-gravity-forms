<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('kulahub_api_key');
delete_option('kulahub_gf_version');

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kulahub_failed_submissions");

// Delete logs directory
$logs_dir = plugin_dir_path(__FILE__) . 'logs';
if (is_dir($logs_dir)) {
    array_map('unlink', glob("$logs_dir/*.*"));
    rmdir($logs_dir);
}