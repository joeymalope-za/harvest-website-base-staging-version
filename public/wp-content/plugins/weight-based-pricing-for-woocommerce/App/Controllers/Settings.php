<?php

namespace WWBP\App\Controllers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Settings
{
    /**
     * Settings construct.
     */
    public function __construct() 
    {
        add_action('init', array($this, 'enableStockFloatVal'));
    }

    /*
    * Enable Decimal Value to Product Inventory.
    */
    public static function enableStockFloatVal()
    {
        if (get_option('wwbp_is_enable', 'yes') == 'yes') 
        {
            remove_filter('woocommerce_stock_amount', 'intval');
            add_filter('woocommerce_stock_amount', 'floatval');
        }
    }
}
