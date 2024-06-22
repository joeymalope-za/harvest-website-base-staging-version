<?php

namespace WWBP\App\Controllers\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Views\Admin\PricingRule;
use WWBP\App\Helpers\ParseInput;

class AjaxRequests
{
    /**
     * Ajax Requests construct.
     */
    public function __construct() 
    {
        add_action('wp_ajax_wwbp_ajax_simple_range_add', array($this, 'addPricingRuleRowForSimpleProduct'));
        add_action('wp_ajax_wwbp_ajax_simple_range_remove', array($this, 'removePricingRuleRowForSimpleProduct'));
        add_action('wp_ajax_wwbp_ajax_variable_range_add', array($this, 'addPricingRuleRowForVariableProduct'));
        add_action('wp_ajax_wwbp_ajax_variable_range_remove', array($this, 'removePricingRuleRowForVariableProduct'));
    }

    /**
     * Simple Product Add Weight Based Pricing Rule.
     */
    public static function addPricingRuleRowForSimpleProduct()
    {
        // To verify ajax nonce then proceed
        check_ajax_referer('wwbp_admin_ajax');

        if (!isset($_POST['general_data']) && !isset($_POST['post_id'])) {return;}
        
        $row_unique_id = ParseInput::id($_POST['general_data'], true);
        $post_id = ParseInput::id($_POST['post_id'], true);

        PricingRule::addNewRowForSimpleProduct($post_id, '', $row_unique_id);
        wp_die();
    }

    /**
     * Simple Product Remove Weight Based Pricing Rule.
     */
    public static function removePricingRuleRowForSimpleProduct()
    {
        // To verify ajax nonce then proceed
        check_ajax_referer('wwbp_admin_ajax');

        if (!isset($_POST['unique_id']) && !isset($_POST['post_data'])) {return;}

        $unique_id = ParseInput::id($_POST['unique_id'], true);
        $post_id = ParseInput::id($_POST['post_data'], true);

        $measure_ranges = get_post_meta($post_id, 'measure_range', true);

        if (is_array($measure_ranges) && !empty($measure_ranges)) {
            unset($measure_ranges[$unique_id]);
        }

        // To check post id is valid
        if (get_post_status($post_id)) {
            update_post_meta($post_id, 'measure_range', $measure_ranges);
            wp_send_json_success($unique_id);
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Variable Product Add Weight Based Pricing Rule.
     */
    public static function addPricingRuleRowForVariableProduct()
    {
        // To verify ajax nonce then proceed
        check_ajax_referer('wwbp_admin_ajax');

        if (!isset($_POST['general_data']) && !isset($_POST['var_data'])) {return;}
        
        $row_unique_id = ParseInput::id($_POST['general_data'], true);
        $variation_id = ParseInput::id($_POST['var_data'], true);

        PricingRule::addNewRowForVariableProduct($variation_id, '', $row_unique_id);
        wp_die();
    }

    /**
     * Variable Product Remove Weight Based Pricing Rule.
     */
    public static function removePricingRuleRowForVariableProduct()
    {
        // To verify ajax nonce then proceed
        check_ajax_referer('wwbp_admin_ajax');

        if (!isset($_POST['unique_id']) && !isset($_POST['post_data'])) {return;}

        $unique_id = ParseInput::id($_POST['unique_id'], true);
        $post_id = ParseInput::id($_POST['post_data'], true);

        $measure_ranges = get_post_meta($post_id, 'measure_range', true);

        if (is_array($measure_ranges) && !empty($measure_ranges)) {
            unset($measure_ranges[$unique_id]);
        }

        // To check post id is valid
        if (get_post_status($post_id)) {
            update_post_meta($post_id, 'measure_range', $measure_ranges);
            wp_send_json_success($unique_id);
        } else {
            wp_send_json_error();
        }
    }
}