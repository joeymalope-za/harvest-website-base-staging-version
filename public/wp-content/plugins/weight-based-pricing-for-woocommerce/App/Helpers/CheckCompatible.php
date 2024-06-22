<?php

namespace WWBP\App\Helpers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class CheckCompatible
{
    /**
     * Check Compatible construct.
     */
    public function __construct()
    {
        if (!$this->isEnvironmentCompatible()) {
            /* translators: %s is replaced with required php version */
            exit(sprintf(__('This plugin can not be activated because it requires minimum PHP version of %s', 'weight-based-pricing-woocommerce'), WWBP_REQUIRED_PHP_VERSION));
        }
        if (!WooCommerce::isActive()) {
            exit;
        }
        if (!$this->isWooCommerceCompatible()) {
            /* translators: %s is replaced with required woocommerce version */
            exit(sprintf(__('This plugin requires at least WooCommerce %s', 'weight-based-pricing-woocommerce'), WWBP_REQUIRED_WC_VERSION));
        }
    }

    /**
     * Determines if the server environment is compatible with this plugin.
     * @return bool
     */
    public static function isEnvironmentCompatible()
    {
        return version_compare(PHP_VERSION, WWBP_REQUIRED_PHP_VERSION, '>=');
    }

    /**
     * Determines if the woocommerce version is compatible with this plugin.
     * @return bool
     */
    public static function isWooCommerceCompatible()
    {
        if (defined('WC_VERSION')) {
            return version_compare(WC_VERSION, WWBP_REQUIRED_WC_VERSION, '>=');
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_folder = get_plugins('/woocommerce');
        $plugin_file = 'woocommerce.php';
        $wc_installed_version = null;
        if (isset($plugin_folder[$plugin_file]['Version'])) {
            $wc_installed_version = $plugin_folder[$plugin_file]['Version'];
        }
        return version_compare($wc_installed_version, WWBP_REQUIRED_WC_VERSION, '>=');
    }
}
