<?php

namespace WWBP\App\Views\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class PricingRule
{
    /**
     * Simple Product Weight Range Pricing Table.
     */
    public static function simpleProduct($product_data)
    {
        global $post;

        $post_id = $post->ID;

        $measure_ranges = isset($product_data['wwbp_pricing_rule'][0]) ? unserialize($product_data['wwbp_pricing_rule'][0]) : '';

        ?>
            <div class="woocommerce_attribute_data wc-metabox-content wwbp_simple_pricing_rules">
                <table id="wwbp_pricing_rules_table">
                    <tr>
                        <th>From/To Range</th>
                        <th>Regular price</th>
                        <th>Sale Price</th>
                        <th></th>
                    </tr>

                    <?php  
                        if (is_array($measure_ranges) && !empty($measure_ranges)) 
                        {
                            foreach ($measure_ranges as $range_key => $each_range) {
                                self::addNewRowForSimpleProduct($post_id, $product_data, $range_key);
                            }
                        } else {
                            ?>
                                <tr class='empty_row'><td colspan='4'>No Rules Found</td></tr>
                            <?php 
                        }
                    ?>
                </table>

                <table class="wwbp_add_pricing_rule">
                    <tr>
                        <td align="right">
                            <button type="button" class="button-primary" id="add_weight_ranges" data-post_id="<?php esc_html_e($post_id); ?>"><span class="dashicons dashicons-plus"></span> Add Pricing Rule</button>
                        </td>
                    </tr>
                </table>
            </div>
        <?php
    }

    /**
     * Variable Product Weight Range Pricing Table.
     */
    public static function variableProduct($variation_id, $product_data, $loop)
    {
        $measure_ranges = isset($product_data['wwbp_pricing_rule'][0]) ? unserialize($product_data['wwbp_pricing_rule'][0]) : '';

        ?>
            <div class="woocommerce_attribute_data wc-metabox-content wwbp_variable_pricing_rules">
                <div style="overflow: auto;">
                    <table id="wwbp_pricing_rules_table">
                        <tr>
                            <th>From/To Range</th>
                            <th>Regular price</th>
                            <th>Sale Price</th>
                            <th></th>
                        </tr>

                        <?php
                        if (is_array($measure_ranges) && !empty($measure_ranges)) 
                        {
                            foreach ($measure_ranges as $range_key => $each_range) {
                                self::addNewRowForVariableProduct($variation_id, $product_data, $range_key);
                            }
                        } else {
                            ?>
                                <tr class="empty_row"><td colspan="4">No Rules Found</td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>

                <table class="wwbp_add_pricing_rule">
                    <tr>
                        <td align="right">
                            <button type="button" class="button-primary" id="add_weight_ranges" data-var_id="<?php esc_html_e($variation_id); ?>"><span class="dashicons dashicons-plus"></span> Add Pricing Rule</button>
                        </td>
                    </tr>
                </table>
            </div>
        <?php
    }

    /**
     * Simple Product New Weight Range Pricing Row
     */
    public static function addNewRowForSimpleProduct($post_id, $product_data = '', $row_unique_id = '')
    {
        $from_range = '';
        $to_range = '';
        $unit_price = '';
        $sale_price = '';

        if (isset($product_data['wwbp_pricing_rule'][0])) 
        {
            $measure_ranges = unserialize($product_data['wwbp_pricing_rule'][0]);

            if (is_array($measure_ranges) && !empty($measure_ranges)) 
            {
                $range_datas = $measure_ranges["$row_unique_id"];
                $from_range = $range_datas['wwbp_from_weight'];
                $to_range = $range_datas['wwbp_to_weight'];
                $unit_price = $range_datas['wwbp_unit_price'];
                $sale_price = $range_datas['wwbp_sale_price'];
            }
        }

        ?>
            <tr id='row_<?php esc_html_e($row_unique_id); ?>' disabled="disabled" class="simple_range_row">
                <td>
                    <input type="number" step="any" id="wwbp_from_weight<?php esc_html_e($row_unique_id); ?>" name="wwbp_pricing_rule[<?php esc_html_e($row_unique_id); ?>][wwbp_from_weight]]" value="<?php esc_html_e($from_range); ?>" placeholder="From Range" required><br><br>
                    <input type="number" step="any" id="wwbp_to_weight<?php esc_html_e($row_unique_id); ?>" name="wwbp_pricing_rule[<?php esc_html_e($row_unique_id); ?>][wwbp_to_weight]" value="<?php esc_html_e($to_range); ?>" style="margin-top: 5px;" placeholder="To Range" required>
                </td>
                <td>
                    <input type="number" step="any" id="wwbp_unit_price<?php esc_html_e($row_unique_id); ?>" class="wc_input_price" name="wwbp_pricing_rule[<?php esc_html_e($row_unique_id); ?>][wwbp_unit_price]" value="<?php esc_html_e($unit_price); ?>" placeholder="Unit price">
                </td>
                <td>
                    <input type="number" step="any" id="wwbp_sale_price<?php esc_html_e($row_unique_id); ?>" class="wc_input_price" name="wwbp_pricing_rule[<?php esc_html_e($row_unique_id); ?>][wwbp_sale_price]" value="<?php esc_html_e($sale_price); ?>" placeholder="Sale price" required>
                </td>
                <td>
                    <button type="button" data-post_id='<?php esc_html_e($post_id); ?>' data-unique_id='<?php esc_html_e($row_unique_id); ?>' class="button-secondary delete_weight_ranges"><span class="dashicons dashicons-trash"></span></button>
                </td>
            </tr>
        <?php
    }

    /**
     * Variable Product New Weight Range Pricing Row
     */
    public static function addNewRowForVariableProduct($variation_id, $product_data = '', $row_unique_id = '')
    {

        $from_range = '';
        $to_range = '';
        $unit_price = '';
        $sale_price = '';

        if (isset($product_data['wwbp_pricing_rule'][0])) 
        {
            $measure_ranges = unserialize($product_data['wwbp_pricing_rule'][0]);

            if (is_array($measure_ranges) && !empty($measure_ranges)) 
            {
                $range_datas = $measure_ranges["$row_unique_id"];
                $from_range = $range_datas['wwbp_from_weight'];
                $to_range = $range_datas['wwbp_to_weight'];
                $unit_price = $range_datas['wwbp_unit_price'];
                $sale_price = $range_datas['wwbp_sale_price'];
            }
        }

        ?>
            <tr id='row_<?php esc_html_e($row_unique_id); ?>' disabled="disabled" class="variable_range_row">
                <td>
                    <input type="number" step="any" id="wwbp_from_weight<?php esc_html_e($row_unique_id); ?>" name="wwbp_pricing_rule_<?php esc_html_e($variation_id); ?>[<?php esc_html_e($row_unique_id); ?>][wwbp_from_weight]" value="<?php esc_html_e($from_range); ?>" placeholder="From Range" required><br><br>
                    <input type="number" step="any" id="wwbp_to_weight<?php esc_html_e($row_unique_id); ?>" name="wwbp_pricing_rule_<?php esc_html_e($variation_id); ?>[<?php esc_html_e($row_unique_id); ?>][wwbp_to_weight]" value="<?php esc_html_e($to_range); ?>" style="margin-top: 5px;" placeholder="To Range" required>
                </td>
                <td>
                    <input type="number" step="any" id="wwbp_unit_price<?php esc_html_e($row_unique_id); ?>" name="wwbp_pricing_rule_<?php esc_html_e($variation_id); ?>[<?php esc_html_e($row_unique_id); ?>][wwbp_unit_price]" value="<?php esc_html_e($unit_price); ?>" placeholder="Unit price">
                </td>
                <td>
                    <input type="number" step="any" id="wwbp_sale_price<?php esc_html_e($row_unique_id); ?>" class="wc_input_price" name="wwbp_pricing_rule_<?php esc_html_e($variation_id); ?>[<?php esc_html_e($row_unique_id); ?>][wwbp_sale_price]" value="<?php esc_html_e($sale_price); ?>" placeholder="Sale price" required>
                </td>
                <td>
                    <button type="button" data-post_id='<?php esc_html_e($variation_id); ?>' data-unique_id='<?php esc_html_e($row_unique_id); ?>' class="button-secondary delete_weight_ranges"><span class="dashicons dashicons-trash"></span></button>
                </td>
            </tr>
        <?php
    }
}