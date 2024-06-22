<?php
// Fired when the plugin is uninstalled.
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete the settings.
delete_option('mb-wof-settings');
delete_option('mabel-wheel-of-fortune_license');
delete_option('wof-pro-dev-version');

// Delete the logs table.
$path = plugin_dir_path(__FILE__) . 'code/services/class-log-service.php';
include_once $path;
\MABEL_WOF\Code\Services\Log_Service::drop_logs();

// Delete the wheels.
global $wpdb;
$wpdb->delete($wpdb->posts, [ 'post_type' => 'mb_woc_wheel' ] );
