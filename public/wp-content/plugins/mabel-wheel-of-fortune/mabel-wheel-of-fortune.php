<?php
/*
 * Plugin Name: WP Optin Wheel Pro
 * Plugin URI: https://studiowombat.com/plugin/wheel-of-fortune/
 * Description: Gamified popup to grab your visitor's email, with exit-intent. Woocommerce compatible.
 * Version: 4.1.1
 * Author: StudioWombat
 * Author URI: https://studiowombat.com/
 * Text Domain: mabel-wheel-of-fortune
 * WC requires at least: 3.6.0
 * WC tested up to: 8.6
*/

if(!defined('ABSPATH')) die;

/**
 * Auto loader for Plugin classes
 *
 * @param string $class_name Name of the class that shall be loaded
 */
function MABEL_WOF_auto_loader ($class_name) {
	// Not loading a class from our plugin.
	if ( !is_int(strpos( $class_name, 'MABEL_WOF')) )
		return;
	// Remove root namespace as we don't have that as a folder.
	$class_name = str_replace('MABEL_WOF\\','',$class_name);
	$class_name = str_replace('\\','/',strtolower($class_name)) .'.php';
	// Get only the file name.
	$pos =  strrpos($class_name, '/');
	$file_name = is_int($pos) ? substr($class_name, $pos + 1) : $class_name;
	// Get only the path.
	$path = str_replace($file_name,'',$class_name);
	// Append 'class-' to the file name and replace _ with -
	$new_file_name = 'class-'.str_replace('_','-',$file_name);
	// Construct file path.
	$file_path = plugin_dir_path(__FILE__)  . str_replace('\\', DIRECTORY_SEPARATOR, $path . strtolower($new_file_name));

	if (file_exists($file_path))
		require_once($file_path);
}

spl_autoload_register('MABEL_WOF_auto_loader');

function run_MABEL_WOF()
{
	$plugin = new \MABEL_WOF\Wheel_Of_Fortune(
		plugin_dir_path( __FILE__ ),
		plugin_dir_url( __FILE__ ),
		plugin_basename( __FILE__ ),
		'WP Optin Wheel Pro',
		'4.1.1',
		'mb-wof-settings'
	);
	$plugin->run();
}

run_MABEL_WOF();


// Declare HPOS compatibility.
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );