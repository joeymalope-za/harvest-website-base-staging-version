<?php

namespace WWBP\App;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Controllers\Activator;
use WWBP\App\Controllers\Assets;
use WWBP\App\Controllers\Deactivator;
use WWBP\App\Controllers\OrderAdjustments;
use WWBP\App\Controllers\Settings;
use WWBP\App\Controllers\Admin\AjaxRequests;
use WWBP\App\Controllers\Admin\PluginMenu;
use WWBP\App\Controllers\Admin\SimpleProducts;
use WWBP\App\Controllers\Admin\VariableProducts;
use WWBP\App\Controllers\Frontend\CartAdjustments;
use WWBP\App\Controllers\Frontend\ProductAdjustments;
use WWBP\App\Controllers\Frontend\ShopAdjustments;
use WWBP\App\Helpers\WooCommerce;

class Boot
{
    /**
     * Boot constructor.
     */
    public function __construct()
    {
        new Activator();
        new Deactivator();
        $this->initHooks();
    }

    /**
     * Initialize all the plugin related hooks.
     */
    public function initHooks()
    {
        if (WooCommerce::isActive()) {
            add_action('woocommerce_init', array($this, 'initPlugin'));
        } else {
            WooCommerce::missingNotice();
        }

        add_action('init', array($this, 'loadTextdomain'));
    }

    /**
     * Initialize plugin.
     * This will run only if woocommerce is activated.
     */
    public function initPlugin()
    {
        new Assets();
        new Settings();

        if (is_admin()) {
            new PluginMenu();
            new AjaxRequests();
            new SimpleProducts();
            new VariableProducts();
        }

        if ((!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON')) {
            new ShopAdjustments();
            new ProductAdjustments();
            new CartAdjustments();
        }

        new OrderAdjustments();
    }

    /**
     * Load plugin Text Domain (for plugin translation).
     */
    public function loadTextdomain() {
        load_plugin_textdomain(WWBP_PLUGIN_TEXT_DOMAIN, false, WWBP_PLUGIN_PATH . '/i18n/languages');
    }
}
