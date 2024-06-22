<?php
/**
 * Plugin Name: Harvest Flower Qty Adjuster
 * Description: Adjusts the quantity of a product in the "Flower" category based on the value in the custom field 'Ordered Weight' when an order is submitted. This is a workaround for Zoho feeds to ensure the correct quantity is passed back to Zoho after a sale.
 * Version: 1.0
 * Author: //Aaron B 🔥
 */

// This hook fires after the checkout form is processed
add_action('woocommerce_checkout_order_processed', 'adjust_flower_qty', 10, 1);

function adjust_flower_qty($order_id) {
    // Get the order
    $order = wc_get_order($order_id);

    // Loop through the items in the order
    foreach ($order->get_items() as $item_id => $item) {
        // Get the product
        $product = $item->get_product();

        // Check if the product is in the "Flower" category
        if (has_term(178, 'product_cat', $product->get_id())) {
            // Get the value in the custom field 'Ordered Weight' from the order item
            $ordered_weight = $item->get_meta(__('Ordered Weight', 'weight-based-pricing-woocommerce'), true);

            // Remove any non-numeric characters from the string
            $ordered_weight = preg_replace('/[^0-9.]/', '', $ordered_weight);

            // Check if the ordered weight is a positive number
            if (is_numeric($ordered_weight) && $ordered_weight > 0) {
                // Overwrite the quantity of the line item with the ordered weight
                $item->set_quantity($ordered_weight);
                $item->save();
            }
        }
    }
}