<?php
/**
 * Plugin Name: Harvest Custom Pricing by Weight
 * Description: Weight Based Product Pricing for Flower **DO NOT UPDATE**
 * Version: 1.1.5
 * Author: //Aaron B 🔥
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// This plugin constants
defined('WWBP_PLUGIN_NAME') or define('WWBP_PLUGIN_NAME', 'Harvest Custom Pricing by Weight');
defined('WWBP_PLUGIN_VERSION') or define('WWBP_PLUGIN_VERSION', '1.1.5');

defined('WWBP_PLUGIN_FILE') or define('WWBP_PLUGIN_FILE', __FILE__);
defined('WWBP_PLUGIN_PATH') or define('WWBP_PLUGIN_PATH', plugin_dir_path(__FILE__));

defined('WWBP_PLUGIN_URL') or define('WWBP_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('WWBP_WP_ADMIN_URL') or define('WWBP_WP_ADMIN_URL', admin_url('admin.php'));
defined('WWBP_WP_ADMIN_AJAX_URL') or define('WWBP_WP_ADMIN_AJAX_URL', admin_url('admin-ajax.php'));

defined('WWBP_PLUGIN_SLUG') or define('WWBP_PLUGIN_SLUG', 'weight-based-pricing-woocommerce');
defined('WWBP_PLUGIN_TEXT_DOMAIN') or define('WWBP_PLUGIN_TEXT_DOMAIN', 'weight-based-pricing-woocommerce');

// Required dependencies version
defined('WWBP_REQUIRED_PHP_VERSION') or define('WWBP_REQUIRED_PHP_VERSION', 5.6);
defined('WWBP_REQUIRED_WC_VERSION') or define('WWBP_REQUIRED_WC_VERSION', '3.0.0');

// Initialize MVC Framework
require_once WWBP_PLUGIN_PATH . '/vendor/autoload.php';
new WWBP\App\Boot(); // Start Plugin
