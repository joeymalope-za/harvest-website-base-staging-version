<?php
/**
 * Plugin Name: Harvest Toggle Membership
 * Description: Adds member pricing, a checkout toggle for membership selection. Adds consult fees to orders at checkout. Places a High Rollers Club Membership product to cart on pageload of checkout. Works with WooCommerce Supscriptions plugin. <a href="/wp-content/plugins/toggle-membership/Readme.txt">More details</a>.
 * Version: 2.0
 * Author: //Aaron B 🔥
 * License: GPL2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The code that runs during plugin activation.
 */
function activate_woocommerce_checkout_toggle() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-toggle.php';
    $toggle = new Toggle();
    $toggle->activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woocommerce_checkout_toggle() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-toggle.php';
    $toggle = new Toggle();
    $toggle->deactivate();
}

register_activation_hook(__FILE__, 'activate_woocommerce_checkout_toggle');
register_deactivation_hook(__FILE__, 'deactivate_woocommerce_checkout_toggle');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-product-handler.php';

// Instantiate the Product_Handler class
new Product_Handler();
?>