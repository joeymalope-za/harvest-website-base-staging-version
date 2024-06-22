<?php

namespace WWBP\App\Controllers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Assets
{
    /**
     * Assets construct.
     */
    public function __construct() 
    {
        add_action('admin_enqueue_scripts', array($this, 'backendAssets'));
        add_action('wp_enqueue_scripts', array($this, 'frontendAssets'));
    }

    /**
     * Backend Assets.
     */
    public static function backendAssets()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, array('product', 'edit-product'))) {
            wp_enqueue_style('wwbp_admin', WWBP_PLUGIN_URL . '/Assets/CSS/wwbp_admin.css', array(), WWBP_PLUGIN_VERSION);

            wp_enqueue_script('wwbp_admin', WWBP_PLUGIN_URL . '/Assets/JS/wwbp_admin.js', array('jquery'), WWBP_PLUGIN_VERSION);
            wp_localize_script('wwbp_admin', 'wwbp_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce("wwbp_admin_ajax")
            ));
        }
    }

    /**
     * Frontend Assets.
     */
    public static function frontendAssets()
    {
        wp_enqueue_script('jquery');

        if (!is_admin() && get_option('wwbp_is_enable', 'yes') == 'yes') {
            wp_enqueue_style('wwbp_frontend', WWBP_PLUGIN_URL . '/Assets/CSS/wwbp_frontend.css', array(), WWBP_PLUGIN_VERSION);

            wp_enqueue_script('wwbp_frontend', WWBP_PLUGIN_URL . '/Assets/JS/wwbp_frontend.js', array('jquery'), WWBP_PLUGIN_VERSION);
        }
    }
}
