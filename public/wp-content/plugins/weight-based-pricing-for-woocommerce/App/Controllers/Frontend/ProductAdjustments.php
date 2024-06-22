<?php

namespace WWBP\App\Controllers\Frontend;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Views\Frontend\PricingSection;

class ProductAdjustments
{
    /**
     * Product Adjustments construct.
     */
    public function __construct()
    {
        if (get_option('wwbp_is_enable', 'yes') == 'yes') {
            add_action('woocommerce_before_add_to_cart_button', array($this, 'displaySimpleProductCalculationSection'), 10);
            add_action('woocommerce_single_variation', array($this, 'displayVariableProductCalculationSection'), 12);

            add_filter('woocommerce_get_price_html', array($this, 'addPriceSuffix'), 99, 2);
            add_filter('woocommerce_get_availability', array($this, 'changeInStockText'), 1, 2);
        }
    }

    /**
     * Simple Product - Display Calculation Section.
     */
    public function displaySimpleProductCalculationSection()
    {
        global $product;
        $product_id = $product->get_id();
        $stock_status = $product->get_stock_status();
        $quantity_max = $product->backorders_allowed() ? '' : $product->get_stock_quantity();

        if ('0' == $quantity_max || 'outofstock' == $stock_status) return;

        $product_data = get_post_meta($product_id);

        $is_enable = isset($product_data['wwbp_is_enable'][0]) ? $product_data['wwbp_is_enable'][0] : '';

        if ($is_enable != 'yes') return;

	    if (is_object($product) && !in_array($product->get_type(), ['variable', 'variable-subscription'])) {
		    PricingSection::simpleProduct($product, $product_data);
	    }
    }

    /**
     * Variable Product - Display Calculation Section.
     */
    public function displayVariableProductCalculationSection()
    {
		global $product;
		if (is_object($product) && in_array($product->get_type(), ['variable', 'variable-subscription'])) {
			PricingSection::variableProduct($product);
		}
    }

    /**
     * Add Price suffix.
     */
    public function addPriceSuffix($html, $product)
    {
        if (!is_object($product)) return $html;

        $product_id = $product->get_id();
        $product_data = get_post_meta($product_id);

        $is_enable = isset($product_data['wwbp_is_enable'][0]) ? $product_data['wwbp_is_enable'][0] : '';

        if ($is_enable != 'yes') return $html;

        $weight_unit = isset($product_data['wwbp_weight_unit'][0]) ? $product_data['wwbp_weight_unit'][0] : get_option('wwbp_default_weight_unit', 'kg');

        $html_with_unit = $html . '/' . $weight_unit;
        return apply_filters('wbp_wc_price_html', $html_with_unit, $html, $product, $weight_unit);
    }

    /**
     * Change in stock Text
     */
    public function changeInStockText($availability, $product)
    {
        if (!is_object($product)) return $availability;

        $product_id = $product->get_id();
        $product_data = get_post_meta($product_id);

        $is_enable = isset($product_data['wwbp_is_enable'][0]) ? $product_data['wwbp_is_enable'][0] : '';

        if ($is_enable != 'yes') return $availability;

        $weight_unit = isset($product_data['wwbp_weight_unit'][0]) ? $product_data['wwbp_weight_unit'][0] : get_option('wwbp_default_weight_unit', 'kg');

        if ($product->managing_stock() && $product->is_in_stock() && !$product->backorders_allowed()) {
            $stock_with_unit = $product->get_stock_quantity() . $weight_unit;
            $availability['availability'] = sprintf(__('%s in stock', 'woocommerce'), $stock_with_unit);
        }
        return $availability;
    }
}
