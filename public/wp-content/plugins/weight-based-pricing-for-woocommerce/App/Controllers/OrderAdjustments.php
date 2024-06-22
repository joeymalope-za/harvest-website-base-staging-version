<?php

namespace WWBP\App\Controllers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class OrderAdjustments
{
    /**
     * Order Adjustment construct.
     */
    public function __construct()
    {
        add_action('woocommerce_new_order_item', array($this, 'addItemMeta'), 10, 3);

        add_filter('woocommerce_order_item_quantity', array($this, 'decreaseStockQuantity'), 10, 3);
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hiddenOrderItemData'), 10, 1);
    }

    /**
     * Add order meta (weight).
     */
    public function addItemMeta($item_id, $item, $order_id)
    {
        $legacy_values = is_object($item) && isset($item->legacy_values) ? $item->legacy_values : false;

        if (!is_array($legacy_values) || !isset($legacy_values['wwbp_weight_qty'])) return;

        $product_id = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];

        $weight_qty = $legacy_values['wwbp_weight_qty'];

        $weight_unit = get_post_meta($product_id, 'wwbp_weight_unit', true);

        if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
            $wastage_percentage = get_post_meta($product_id, 'wwbp_wastage_percentage', true) > 0 ? get_post_meta($product_id, 'wwbp_wastage_percentage', true) : 0;
        } else {
            $wastage_percentage = 0;
        }
        $total_wastage = ($wastage_percentage / 100) * $weight_qty;
        $actual_weight = (float) number_format($weight_qty - $total_wastage, 3, '.', '');

        if ($weight_qty > 0) {
            $item->add_meta_data(__('Ordered Weight', 'weight-based-pricing-woocommerce'), $weight_qty . $weight_unit);
            if (get_option('wwbp_actual_weight_is_enable', 'yes') == 'yes') {
                $item->add_meta_data(__('Actual Weight', 'weight-based-pricing-woocommerce'), $actual_weight . $weight_unit);
            }
            $item->add_meta_data('_wwbp_weight_qty', $weight_qty, true);
            $item->save_meta_data();
        }
    }

    /**
     * Decrease stock quantity.
     */
    public static function decreaseStockQuantity($qty, $order, $item)
    {
        $weight_qty = $item->get_meta('_wwbp_weight_qty');
        
        if ($weight_qty > 0) {
            $qty = $weight_qty * $qty;
        }

        return $qty;
    }

    /**
     * Hidden order item data.
     */
    public static function hiddenOrderItemData($array)
    {
        $array[] = '_wwbp_weight_qty';

        return $array;
    }
}
