<?php

namespace WWBP\App\Helpers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WooCommerce
{
    /**
     * Check the WooCommerce is active or not.
     * @return bool
     */
    public static function isActive()
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins, false) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }

    /**
     * Display Woocommerce Missing Notice.
     * @return void
     */
    public static function missingNotice()
    {
        add_action('admin_notices', function () {
            $plugin = '<strong>' . __(WWBP_PLUGIN_NAME, 'weight-based-pricing-woocommerce') . '</strong>';
            $woocommerce = '<a href="https://wordpress.org/plugins/woocommerce" target="_blank">' . __("WooCommerce", 'weight-based-pricing-woocommerce') . '</a>';
            ?>
                <div class="notice notice-error">
                    <p>
                        <?php echo str_replace(['[plugin]', '[woocommerce]'], [$plugin, $woocommerce], __("[plugin] requires the [woocommerce] plugin to be installed and active.", 'weight-based-pricing-woocommerce')); ?>
                    </p>
                </div>
            <?php
        }, 1);
    }
}
