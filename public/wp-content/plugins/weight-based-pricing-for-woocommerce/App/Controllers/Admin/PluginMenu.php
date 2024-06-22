<?php

namespace WWBP\App\Controllers\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Views\Admin\Settings;

class PluginMenu
{
    /**
     * Plugin Menu construct.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'addMenu'));
        
        if (isset($_GET['page']) && $_GET['page'] == WWBP_PLUGIN_SLUG) {
            add_filter('woocommerce_screen_ids', array($this, 'manageScreen'), 10, 1);
        } 
    }

    /**
     * Add Menu in WooCommerce Menu.
     */
    public function addMenu()
    {   
        global $submenu;

        if (isset($submenu['woocommerce'])) {
            add_submenu_page(
                'woocommerce',
                __("Weight Based Pricing", 'weight-based-pricing-woocommerce'),
                __("Weight Based Pricing", 'weight-based-pricing-woocommerce'),
                'manage_woocommerce',
                WWBP_PLUGIN_SLUG,
                array($this, 'page')
            );
        }
    }

    /**
     * Manage current screen.
     * 
     * @return array
     */
    public function manageScreen($screen_ids)
    {
        $screen = get_current_screen();
        $screen_ids[] = $screen->id;

        return $screen_ids;
    }

    /**
     * Plugin Settings Page (Display, Save and Reset).
     */
    public function page()
    {
        if (!empty($_POST['save'])) {
            if (empty($_REQUEST['wwbp_save_settings_nonce']) || !wp_verify_nonce($_REQUEST['wwbp_save_settings_nonce'], 'wwbp_save_settings')) {
                die(__('Action failed! Please refresh the page and retry.', 'weight-based-pricing-woocommerce'));
            }

            $options = Settings::options();
            woocommerce_update_options($options);

            do_action('woocommerce_update_options');

            wp_redirect(add_query_arg(array('saved' => 'true')));
        }

        if (!empty($_POST['reset'])) {
            if (empty($_REQUEST['wwbp_reset_settings_nonce']) || !wp_verify_nonce($_REQUEST['wwbp_reset_settings_nonce'], 'wwbp_reset_settings')) {
                die(__('Action failed! Please refresh the page and retry.', 'weight-based-pricing-woocommerce'));
            }

            $options = Settings::options();
            foreach ($options as $option) {
                if (isset($option['id']) && isset($option['std'])) {
                    delete_option($option['id']);
                    add_option($option['id'], $option['std']);
                }
            }
            
            wp_redirect(add_query_arg(array('reset' => 'true')));
        }

        $error = (empty($_GET['wc_error'])) ? '' : urldecode(stripslashes($_GET['wc_error']));
        $message = (empty($_GET['wc_message'])) ? '' : urldecode(stripslashes($_GET['wc_message']));

        if ($error || $message) {
            if ($error) {
                echo '<div id="message" class="error fade"><p><strong>' . esc_html($error) . '</strong></p></div>';
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . esc_html($message) . '</strong></p></div>';
            }
        } elseif (!empty($_GET['saved'])) {
            echo '<div id="message" class="updated fade"><p><strong>' . __('Your settings have been saved.', 'weight-based-pricing-woocommerce') . '</strong></p></div>';
        } elseif (!empty($_GET['reset'])) {
            echo '<div id="message" class="updated fade"><p><strong>' . __('Settings reset successfully.', 'weight-based-pricing-woocommerce') . '</strong></p></div>';
        }

        new Settings();
    }
}
