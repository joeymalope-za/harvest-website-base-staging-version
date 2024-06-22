<?php
/*

Copyright 2008 MagicToolbox (email : support@magictoolbox.com)
Plugin Name: Magic Zoom Plus for WooCommerce
Plugin URI: http://www.magictoolbox.com/magiczoomplus/?utm_source=TrialVersion&utm_medium=WooCommerce&utm_content=plugins-page-plugin-url-link&utm_campaign=MagicZoomPlus
Description: Sell more from your store by revealing stunning zoomed product images on hover. Click for extraordinary full-screen images. Perfectly refine your zoom with <a href="admin.php?page=WooCommerceMagicZoomPlus-config-page">35 easy customisation options</a>.
Version: 6.9.3
Author: Magic Toolbox
Author URI: http://www.magictoolbox.com/?utm_source=TrialVersion&utm_medium=WooCommerce&utm_content=plugins-page-author-url-link&utm_campaign=MagicZoomPlus


*/

/*
    WARNING: DO NOT MODIFY THIS FILE!

    NOTE: If you want change Magic Zoom Plus settings
            please go to plugin page
            and click 'Magic Zoom Plus Configuration' link in top navigation sub-menu.
*/

if(!function_exists('magictoolbox_WooCommerce_MagicZoomPlus_init')) {
    /* Include MagicToolbox plugins core funtions */
    require_once(dirname(__FILE__)."/magiczoomplus-woocommerce/plugin.php");
}

//MagicToolboxPluginInit_WooCommerce_MagicZoomPlus ();
register_activation_hook( __FILE__, 'WooCommerce_MagicZoomPlus_activate');

register_deactivation_hook( __FILE__, 'WooCommerce_MagicZoomPlus_deactivate');

register_uninstall_hook(__FILE__, 'WooCommerce_MagicZoomPlus_uninstall');

magictoolbox_WooCommerce_MagicZoomPlus_init();
?>