<?php

namespace WWBP\App\Views\Frontend;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class PricingSection
{
    /**
     * Simple Product Pricing section.
     */
    public static function simpleProduct($product, $product_data)
    {
        $weight_label = __('Weight','weight-based-pricing-woocommerce');
        //$sale_price_label = get_option('wwbp_sale_price_label', 'Sale Price');
        $sale_price_label = 'Total';
        $actual_weight_label = get_option('wwbp_actual_weight_label', 'Actual Weight');

        $sale_price_label = str_replace('[sale_price_label]', $sale_price_label, __('[sale_price_label]', 'weight-based-pricing-woocommerce'));
        $actual_weight_label = str_replace('[actual_weight_label]', $actual_weight_label, __('[actual_weight_label]', 'weight-based-pricing-woocommerce'));

        $custom_input_is_enable = get_option('wwbp_custom_input_is_enable', 'yes') == 'yes';
        $actual_weight_is_enable = get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes';

        $currency_symbol = get_woocommerce_currency_symbol();
        $price_decimals = wc_get_price_decimals();

        if (get_option('woocommerce_tax_display_shop') == 'incl') {
            $product_price = wc_get_price_including_tax($product);
        } else {
            $product_price = wc_get_price_excluding_tax($product);
        }

        $weight_unit = isset($product_data['wwbp_weight_unit'][0]) ? $product_data['wwbp_weight_unit'][0] : get_option('wwbp_default_weight_unit', 'kg');
        $min_qty = isset($product_data['wwbp_min_qty'][0]) && $product_data['wwbp_min_qty'][0] != '' ? $product_data['wwbp_min_qty'][0] : 1;
        $max_qty = isset($product_data['wwbp_max_qty'][0]) && $product_data['wwbp_max_qty'][0] != '' ? $product_data['wwbp_max_qty'][0] : '';
        $interval = isset($product_data['wwbp_intervel'][0]) && $product_data['wwbp_intervel'][0] != '' ? $product_data['wwbp_intervel'][0] : 1;
        if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
            $wastage_percentage = isset($product_data['wwbp_wastage_percentage'][0]) && $product_data['wwbp_wastage_percentage'][0] > 0 ? $product_data['wwbp_wastage_percentage'][0] : 0;
        } else {
            $wastage_percentage = 0;
        }

        $actual_weight = (float)number_format($min_qty - (($wastage_percentage / 100) * $min_qty), 3, '.', '');
        $sale_price = number_format($min_qty * $product_price, $price_decimals, '.', '');

        $data = [];
        $data['price'] = $product_price;
        $data['price_decimals'] = $price_decimals;
        $data['currency_symbol'] = $currency_symbol;
        $data['wastage_percentage'] = $wastage_percentage;

        $pricing_rules = isset($product_data['wwbp_pricing_rule'][0]) && !empty(unserialize($product_data['wwbp_pricing_rule'][0])) ? unserialize($product_data['wwbp_pricing_rule'][0]) : [];

        $escaped_pricing_rules = [];
        foreach ($pricing_rules as $rule_key => $pricing_rule) {
            foreach ($pricing_rule as $data_key => $pricing_data) {
                $escaped_pricing_rules[$rule_key][$data_key] = esc_html($pricing_data);
            }
        }

        $i = 0;
        $rules = [];
        foreach ($escaped_pricing_rules as $pricing_rule) {
            $rules[$i] = $pricing_rule;
            $i++;
        }

        $data['pricing_rules'] = $rules;

        $data_json = json_encode($data);
        ?>
            <div class="wwbp_calculation_section">
                <div id="wwbp_data" style="display: none"><?php echo esc_js($data_json); ?></div>
                
                <table>
                    <tr>
                        <td id="weight_label"><?php echo esc_html($weight_label . " (" . $weight_unit) . ")"; ?></td>
                        <td id="weight_selector">
                            <span class="weight">
                            <select name="wwbp_weight_qty" id="wwbp_weight" class="weight-dropdown">
                            <?php
                            $allowed_weights = array(3.5, 7, 14, 28);
                            $custom_labels = array(
                                3.5 => '3.5g - 1/8th',
                                7 => '7g - Quarter',
                                14 => '14g - Half',
                                28 => '28g - Ounce'
                            );
                            
                            foreach ($allowed_weights as $weight) {
                                $label = isset($custom_labels[$weight]) ? $custom_labels[$weight] : $weight . ' ' . esc_html($weight_unit);
                                echo '<option value="' . esc_html($weight) . '">' . esc_html($label) . '</option>';
                            }
                            ?>
                            </select>
                            </span>
                        </td>
                    </tr>
                    <tr <?php if (!$actual_weight_is_enable) echo 'style="display: none"'; ?>>
                        <td><?php echo esc_html($actual_weight_label); ?></td>
                        <td><span id="wwbp_actual_weight"><?php echo esc_html($actual_weight); ?></span><?php echo esc_html($weight_unit); ?></td>
                    </tr>
                    <tr>
                        <td id="total_price_label"><b><?php echo esc_html($sale_price_label); ?></b></td>
                        <td id="total_price_display">
                            <span id="wwbp_sale_price" style="text-decoration: line-through; opacity: 0.8;"></span>
                            <b><?php echo esc_html($currency_symbol); ?><span id="wwbp_product_price"><?php echo esc_html($sale_price); ?></span></b>
                        </td>
                    </tr>
                </table>
            </div>
        <?php
    }

    /**
     * Variable Product Pricing section.
     */
    public static function variableProduct($product)
    {
        $general_data = [];
        $variations_data = [];

        $weight_label = __('Weight','weight-based-pricing-woocommerce');
        $sale_price_label = get_option('wwbp_sale_price_label', 'Sale Price');
        $actual_weight_label = get_option('wwbp_actual_weight_label', 'Actual Weight');

        $sale_price_label = str_replace('[sale_price_label]', $sale_price_label, __('[sale_price_label]', 'weight-based-pricing-woocommerce'));
        $actual_weight_label = str_replace('[actual_weight_label]', $actual_weight_label, __('[actual_weight_label]', 'weight-based-pricing-woocommerce'));

        $general_data['wwbp_weight_label'] = $weight_label;
        $general_data['wwbp_sale_price_label'] = $sale_price_label;
        $general_data['wwbp_actual_weight_label'] = $actual_weight_label;
        $general_data['wwbp_custom_input_is_enable'] = get_option('wwbp_custom_input_is_enable', 'yes') == 'yes';
        $general_data['wwbp_actual_weight_is_enable'] = get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes';
        $general_data['wwbp_currency_symbol'] = get_woocommerce_currency_symbol();
        $general_data['wwbp_price_decimals'] = wc_get_price_decimals();

        foreach ($product->get_available_variations() as $key => $value)
        {
            $i = $value['variation_id'];
            $product_data = get_post_meta($i);

            if (isset($product_data['wwbp_is_enable'])) {
                $variations_data[$i]['wwbp_is_enable'] = "yes";

                if (get_option('woocommerce_tax_display_shop') == 'incl') {
                    $variations_data[$i]['wwbp_price'] = wc_get_price_including_tax(wc_get_product($i));
                } else {
                    $variations_data[$i]['wwbp_price'] = wc_get_price_excluding_tax(wc_get_product($i));
                }

                $weight_unit = isset($product_data['wwbp_weight_unit'][0]) ? $product_data['wwbp_weight_unit'][0] : get_option('wwbp_default_weight_unit', 'kg');
                $min_qty = isset($product_data['wwbp_min_qty'][0]) && $product_data['wwbp_min_qty'][0] != '' ? $product_data['wwbp_min_qty'][0] : 1;
                $max_qty = isset($product_data['wwbp_max_qty'][0]) && $product_data['wwbp_max_qty'][0] != '' ? $product_data['wwbp_max_qty'][0] : '';
                $interval = isset($product_data['wwbp_intervel'][0]) && $product_data['wwbp_intervel'][0] != '' ? $product_data['wwbp_intervel'][0] : 1;
                if ($general_data['wwbp_actual_weight_is_enable']) {
                    $wastage_percentage = isset($product_data['wwbp_wastage_percentage'][0]) && $product_data['wwbp_wastage_percentage'][0] > 0 ? $product_data['wwbp_wastage_percentage'][0] : 0;
                } else {
                    $wastage_percentage = 0;
                }

                $variations_data[$i]['wwbp_weight_unit'] = esc_html($weight_unit);
                $variations_data[$i]['wwbp_max_qty'] = esc_html($max_qty);
                $variations_data[$i]['wwbp_min_qty'] = esc_html($min_qty);
                $variations_data[$i]['wwbp_intervel'] = esc_html($interval);
                $variations_data[$i]['wwbp_wastage_percentage'] = esc_html($wastage_percentage);

                $variations_data[$i]['wwbp_actual_weight'] = esc_html((float) number_format($min_qty - (($wastage_percentage / 100) * $min_qty), 3, '.', ''));
                $variations_data[$i]['wwbp_sale_price'] = esc_html(number_format($min_qty * $product_data['_price'][0], wc_get_price_decimals(), '.', ''));
            
                $pricing_rules = isset($product_data['wwbp_pricing_rule'][0]) && !empty(unserialize($product_data['wwbp_pricing_rule'][0])) ? unserialize($product_data['wwbp_pricing_rule'][0]) : [];

                $escaped_pricing_rules = [];
                foreach ($pricing_rules as $rule_key => $pricing_rule) {
                    foreach ($pricing_rule as $data_key => $pricing_data) {
                        $escaped_pricing_rules[$rule_key][$data_key] = esc_html($pricing_data);
                    }
                }

                $j = 0;
                $rules = [];
                foreach ($escaped_pricing_rules as $pricing_rule) {
                    $rules[$j] = $pricing_rule;
                    $j++;
                }

                $variations_data[$i]['wwbp_pricing_rules'] = $rules;
            } else {
                $variations_data[$i]['wwbp_is_enable'] = "no";
            }
        }

        $general_data_json = json_encode($general_data);
        $variations_data_json = json_encode($variations_data);
        ?>
            <div class="wwbp_general_data" style="display:none"><?php echo esc_js($general_data_json); ?></div>
            <div class="wwbp_variations_data" style="display:none"><?php echo esc_js($variations_data_json); ?></div>
            <div class="wwbp_calculation_section"></div>
        <?php
    }
}
