<?php

namespace WWBP\App\Controllers\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Views\Admin\PricingRule;
use WWBP\App\Helpers\ParseInput;

class SimpleProducts
{
    /**
     * Simple Products construct.
     */
    public function __construct()
    {
        if (get_option('wwbp_is_enable', 'yes') == 'yes') 
        {
            add_action('woocommerce_product_options_general_product_data', array($this, 'settings'), 10, 2);
            add_action('woocommerce_process_product_meta', array($this, 'saveValues'), 99, 2);
        }
    }

    /**
     * Simple Product Settings.
     */
    public static function settings()
    {
        global $post;

        $product_id = $post->ID;
        $product_data = get_post_meta($product_id);

        $is_enable = isset($product_data['wwbp_is_enable'][0]) ? $product_data['wwbp_is_enable'][0] : '';
        $wwbp_weight_unit = isset($product_data['wwbp_weight_unit'][0]) ? $product_data['wwbp_weight_unit'][0] : get_option('wwbp_default_weight_unit', 'kg');
        $wwbp_min_qty = isset($product_data['wwbp_min_qty'][0]) ? $product_data['wwbp_min_qty'][0] : 1;
        $wwbp_max_qty = isset($product_data['wwbp_max_qty'][0]) ? $product_data['wwbp_max_qty'][0] : 1000;
        $wwbp_intervel = isset($product_data['wwbp_intervel'][0]) ? $product_data['wwbp_intervel'][0] : 1;
        $wastage_percentage = isset($product_data['wwbp_wastage_percentage'][0]) ? $product_data['wwbp_wastage_percentage'][0] : 0;

        ?>
            <div class="options_group show_if_simple wwbp_simple_product_options">
            <div class="wwbp_simple_enable">
        <?php

        woocommerce_wp_checkbox(array(
            'id' => 'wwbp_is_enable',
            'name' => 'wwbp_is_enable',
            'value' => $is_enable,
            'label' => esc_html__('Weight Based Pricing', 'weight-based-pricing-woocommerce'),
            'description' => esc_html__('Enable Weight Based Pricing', 'weight-based-pricing-woocommerce'),
        ));

        ?>
            </div>
            <div class="wwbp_simple_options" <?php if($is_enable == '') echo "style='display:none'"; ?>>
        <?php

        woocommerce_wp_select(array(
            'id' => 'wwbp_weight_unit',
            'name' => 'wwbp_weight_unit',
            'class' => 'select short',
            'options' => array(
                'kg' => __('Kilogram (kg)', 'weight-based-pricing-woocommerce'),
                'g' => __('Gram (g)', 'weight-based-pricing-woocommerce'),
                'lb' => __('Pound (lb)', 'weight-based-pricing-woocommerce'),
                'oz' => __('Ounce (oz)', 'weight-based-pricing-woocommerce'),
            ),
            'value' => $wwbp_weight_unit,
            'label' => esc_html__('Weight Unit', 'weight-based-pricing-woocommerce'),
        ));

        woocommerce_wp_text_input(array(
            'id' => 'wwbp_min_qty',
            'name' => 'wwbp_min_qty',
            'class' => 'short',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            ),
            'value' => $wwbp_min_qty,
            'label' => esc_html__('Minimum Weight', 'weight-based-pricing-woocommerce'),
        ));

        woocommerce_wp_text_input(array(
            'id' => 'wwbp_max_qty',
            'name' => 'wwbp_max_qty',
            'class' => 'short',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            ),
            'value' => $wwbp_max_qty,
            'label' => esc_html__('Maximum Weight', 'weight-based-pricing-woocommerce'),
        ));

        woocommerce_wp_text_input(array(
            'id' => 'wwbp_intervel',
            'name' => 'wwbp_intervel',
            'class' => 'short',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            ),
            'value' => $wwbp_intervel,
            'label' => esc_html__('Weight Intervals', 'weight-based-pricing-woocommerce'),
        ));

        if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
            woocommerce_wp_text_input(array(
                'id' => 'wwbp_wastage_percentage',
                'name' => 'wwbp_wastage_percentage',
                'class' => 'short',
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0'
                ),
                'value' => $wastage_percentage,
                'label' => esc_html__('Wastage Percentage', 'weight-based-pricing-woocommerce'),
            ));
        }

        PricingRule::simpleProduct($product_data);
        ?>
            </div>
            </div>
        <?php
    }

    /**
     * Simple Product Save.
     */
    public static function saveValues($post_id, $post)
    {
        if (!isset($_POST['wwbp_is_enable'])) {
            $format_meta_array = array(
                'wwbp_is_enable',
                'wwbp_weight_unit',
                'wwbp_min_qty',
                'wwbp_max_qty',
                'wwbp_intervel',
                'wwbp_wastage_percentage',
                'wwbp_pricing_rule',
            );

            foreach ($format_meta_array as $meta_value => $meta_key) {
                delete_post_meta($post_id, $meta_key);
            }

            return;
        }

        if (isset($_POST['wwbp_is_enable']) && $_POST['wwbp_is_enable'] != '') {
            $format_meta_array = array(
                'wwbp_is_enable',
                'wwbp_weight_unit',
                'wwbp_min_qty',
                'wwbp_max_qty',
                'wwbp_intervel',
                'wwbp_wastage_percentage',
                'wwbp_pricing_rule',
            );

            foreach ($format_meta_array as $meta_value => $meta_key) {
                if ($meta_key == 'wwbp_pricing_rule') {
                    $pricing_rules = isset($_POST[$meta_key]) ? $_POST[$meta_key] : [];

                    $parsed_pricing_rules = [];
                    foreach ($pricing_rules as $rule_key => $pricing_rule) {
                        foreach ($pricing_rule as $data_key => $pricing_data) {
                            $parsed_value = ParseInput::number($pricing_data);
                            if ($parsed_value != null) {
                                $parsed_pricing_rules[$rule_key][$data_key] = $parsed_value;
                            }
                        }
                    }

                    update_post_meta($post_id, $meta_key, $parsed_pricing_rules);
                }
                elseif (in_array($meta_key, ['wwbp_is_enable', 'wwbp_weight_unit'])) {
                    $parsed_value = ParseInput::text($_POST[$meta_key]);

                    if ($meta_key == 'wwbp_is_enable') {
                        if ($parsed_value == 'yes') {
                            update_post_meta($post_id, $meta_key, $parsed_value);
                        } else {
                            $default_value = '';
                            update_post_meta($post_id, $meta_key, $default_value); 
                        }
                    }

                    if ($meta_key == 'wwbp_weight_unit') {
                        if (in_array($parsed_value, ['kg', 'g', 'lb', 'oz'])) {
                            update_post_meta($post_id, $meta_key, $parsed_value);
                        } else {
                            $default_value = get_option('wwbp_default_weight_unit', 'kg');
                            update_post_meta($post_id, $meta_key, $default_value);
                        }
                    }
                } 
                else {
                    $parsed_value = ParseInput::number($_POST[$meta_key]);
                    if ($parsed_value != null) {
                        update_post_meta($post_id, $meta_key, $parsed_value);
                    }
                }
            }
        }
    }
}