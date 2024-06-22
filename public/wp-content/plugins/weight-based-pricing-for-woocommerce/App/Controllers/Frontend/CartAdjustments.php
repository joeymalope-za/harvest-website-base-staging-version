<?php

namespace WWBP\App\Controllers\Frontend;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Helpers\ParseInput;

class CartAdjustments
{
    /**
     * Cart Adjustments construct.
     */
    public function __construct()
    {
        if (get_option('wwbp_is_enable', 'yes') == 'yes') {
            add_filter('woocommerce_add_to_cart_validation', array($this, 'cartValidation'), 10, 6);
            add_filter('woocommerce_add_cart_item_data', array($this, 'addCartItemData'), 10, 3);
            add_filter('woocommerce_get_item_data', array($this, 'addItemData'), 10, 2);

            add_filter('woocommerce_cart_item_price', array($this, 'cartItemPrice'), 10, 3);
            add_action('woocommerce_before_calculate_totals', array($this, 'modifyCartItemData'), 1000);
        }

        if (get_option('wwbp_show_total_savings', 'yes') == 'yes') {
            add_action('woocommerce_cart_totals_after_order_total', array($this, 'displayTotalSavings'));
            add_action('woocommerce_review_order_after_order_total', array($this, 'displayTotalSavings'));
        }
    }

    /**
     * Do cart item validation before add to cart.
     */
    public function cartValidation($status, $product_id, $quantity, $variation_id = 0, $variations = array(), $cart_items = array())
    {
        global $woocommerce;

        $product_id = $variation_id > 0 ? $variation_id : $product_id;

        if (get_post_meta($product_id, "wwbp_is_enable", true) != 'yes') {
            return $status;
        }

        if (empty($_POST['wwbp_weight_qty'])) {
            wc_add_notice(__('Please enter a weight quantity', 'weight-based-pricing-woocommerce'), 'error');
            return false;
        }

        $weight_qty = ParseInput::number($_POST['wwbp_weight_qty']);
        if ($weight_qty == null || $weight_qty <= 0) {
            wc_add_notice(__('Invalid weight quantity', 'weight-based-pricing-woocommerce'), 'error');
            return false;
        }

        $min_qty = get_post_meta($product_id, "wwbp_min_qty", true);
        $max_qty = get_post_meta($product_id, "wwbp_max_qty", true);
        $interval = get_post_meta($product_id, "wwbp_intervel", true);
        $weight_unit = get_post_meta($product_id, 'wwbp_weight_unit', true);
        if ($weight_qty < $min_qty) {
            $message = __('Weight quantity must be at least [minimum_quantity]', 'weight-based-pricing-woocommerce');
            wc_add_notice(str_replace('[minimum_quantity]', $min_qty . $weight_unit, $message), 'error');
            return false;
        }
        if (is_numeric($max_qty)) {
            if ($weight_qty > $max_qty) {
                $message = __('Weight quantity must not be more than [maximum_quantity]', 'weight-based-pricing-woocommerce');
                wc_add_notice(str_replace('[maximum_quantity]', $max_qty . $weight_unit, $message), 'error');
                return false;
            }
        }
        if (strlen(substr(strrchr($weight_qty, "."), 1)) > 3) {
            wc_add_notice(__('Invalid weight quantity', 'weight-based-pricing-woocommerce'), 'error');
            return false;
        }
        
        $product = wc_get_product($product_id);
        if ($product->is_in_stock() && $product->get_stock_quantity() > 0) {
            $product_stock_qty = $product->get_stock_quantity();
            $current_product_qty = $weight_qty * $quantity;
            $existing_product_qty = 0;
            
            foreach ($woocommerce->cart->get_cart() as $cart_item) {
                $cart_product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
                if ($product_id == $cart_product_id && isset($cart_item['wwbp_weight_qty'])) {
                    $existing_product_qty += $cart_item['wwbp_weight_qty'] * $cart_item['quantity'];
                }
            }

            $total_processable_qty = $current_product_qty + $existing_product_qty;

            if (is_numeric($product_stock_qty) && ($product_stock_qty < $total_processable_qty)) {
                $find = array('[current_quantity]', '[stock_quantity]', '[cart_quantity]');
                $replace = array($current_product_qty . $weight_unit, $product_stock_qty . $weight_unit, $existing_product_qty . $weight_unit);
                $message = __("You cannot add that [current_quantity] to the cart - We have [stock_quantity] in stock and you already have [cart_quantity] in your cart", 'weight-based-pricing-woocommerce');
                wc_add_notice(str_replace($find, $replace, $message), 'error');
                return false;
            }
        }

        return $status;
    }

    /**
     * Add cart item data (weight quantity).
     */
    public function addCartItemData($cart_item, $product_id, $variation_id)
    {
        $product_id = $variation_id > 0 ? $variation_id : $product_id;

        if (get_post_meta($product_id, 'wwbp_is_enable', true) != 'yes') {
            return $cart_item;
        }

        if (isset($_POST['wwbp_weight_qty']) && !empty($_POST['wwbp_weight_qty'])) {
            $weight_qty = ParseInput::number($_POST['wwbp_weight_qty']);
            if ($weight_qty != null) {
                $cart_item['wwbp_weight_qty'] = $weight_qty;
            }
        }

        return $cart_item;
    }

    /**
     * Add item data.
     */
    public function addItemData($item_data, $cart_item)
    {
        $product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];

        if (get_post_meta($product_id, 'wwbp_is_enable', true) != 'yes') {
            return $item_data;
        }

        $weight_qty = $cart_item['wwbp_weight_qty'];

        $weight_unit = get_post_meta($product_id, 'wwbp_weight_unit', true);

        if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
            $wastage_percentage = get_post_meta($product_id, 'wwbp_wastage_percentage', true) > 0 ? get_post_meta($product_id, 'wwbp_wastage_percentage', true) : 0;
        } else {
            $wastage_percentage = 0;
        }
        $total_wastage = ($wastage_percentage / 100) * $weight_qty;
        $actual_weight = (float)number_format($weight_qty - $total_wastage, 3, '.', '');

        $actual_weight_label = get_option('wwbp_actual_weight_label', 'Actual Weight');

        $item_data['wwbp_required_weight'] = array(
            'key' => __('Weight','weight-based-pricing-woocommerce') . " (" . $weight_unit . ")",
            'value' => $weight_qty
        );

        if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
            $item_data['wwbp_actual_weight'] = array(
                'key' => str_replace('[actual_weight_label]', $actual_weight_label, __('[actual_weight_label]', 'weight-based-pricing-woocommerce')) . " (" . $weight_unit . ")",
                'value' => $actual_weight
            );
        }

        return $item_data;
    }

    /** 
     * Custom Price calculation based on the weight and pricing rules.
     */
    public function customPrice($product_id, $product_price, $weight_qty)
    {
        $data = get_post_meta($product_id, 'wwbp_pricing_rule', false);
        if(isset($data[0]) && !empty($data[0])){
            $pricing_rules = $data[0];
        }else {
            $pricing_rules = [];
        }

        $i = 0;
        $rules = [];
        foreach ($pricing_rules as $pricing_rule) {
            $rules[$i] = $pricing_rule;
            $i++;
        }

        $special_price = 0;
        for ($i = 0; $i < count($rules); $i++) {
            $from_weight = (float) $rules[$i]['wwbp_from_weight'];
            $to_weight = (float) $rules[$i]['wwbp_to_weight'];

            if ($from_weight <= $weight_qty && $weight_qty <= $to_weight) {
                $special_price = (float) $rules[$i]['wwbp_sale_price'];
            }
        }

        if (0 < $special_price && $special_price < $product_price) {
            $custom_price = $special_price * $weight_qty;
        } else {
            $custom_price = $product_price * $weight_qty;
        }

        return (float) number_format($custom_price, wc_get_price_decimals(), '.', '');
    }

    /**
     * Cart item price.
     */
    public function cartItemPrice($price_html, $cart_item, $cart_item_key) {
        if (isset($cart_item['wwbp_weight_qty'])) {
            $product = $cart_item['data'];
            $product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
            $custom_price = $this->customPrice($product_id, $product->get_price(), 1);

            $args = array('price' => $custom_price);
            if (WC()->cart->display_prices_including_tax()) {
                $product_price = wc_get_price_including_tax($product, $args);
            } else {
                $product_price = wc_get_price_excluding_tax($product, $args);
            }
            return wc_price($product_price);
        }
        return $price_html;
    }

     /**
     * To modify cart item data.
     */
    public function modifyCartItemData($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['wwbp_weight_qty'])) {
                $product = $cart_item['data'];
                $product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
                $product_price = $product->get_price();
                $weight_qty = $cart_item['wwbp_weight_qty'];

                $custom_price = $this->customPrice($product_id, $product_price, $weight_qty);
                if ($custom_price > 0) {
                    $product->set_price($custom_price);
                }

                $product->set_weight($weight_qty);
            }
        }
    }

    /**
     * Display total savings row.
     */
    public static function displayTotalSavings()
    {
        global $woocommerce;

        $discount_total = 0;
        foreach ($woocommerce->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $sale_price = $product->get_price();
            $regular_price = $product->get_regular_price();

            if ($regular_price != '' && $sale_price != '') {
                if (isset($cart_item['wwbp_weight_qty'])) {
                    $discount_total += (($regular_price * $cart_item['wwbp_weight_qty']) - $sale_price) * $cart_item['quantity'];
                } else {
                    $discount_total += ($regular_price - $sale_price) * $cart_item['quantity'];
                }
            }
        }

        if ($discount_total > 0) {
            ?>
                <tr class="cart-discount wwbp-total-savings">
                    <th><?php _e('Your Savings', 'weight-based-pricing-woocommerce'); ?></th>
                    <td data-title="<?php _e('Total Savings', 'weight-based-pricing-woocommerce'); ?>">
                        <?php echo wc_price($discount_total + $woocommerce->cart->discount_cart); ?>
                    </td>
                </tr>
            <?php
        }
    }
}