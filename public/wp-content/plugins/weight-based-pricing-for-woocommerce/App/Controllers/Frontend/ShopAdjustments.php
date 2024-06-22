<?php

namespace WWBP\App\Controllers\Frontend;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class ShopAdjustments
{
    /**
     * Shop Adjustments construct.
     */
    public function __construct() 
    {
        add_filter('woocommerce_product_supports', array($this, 'removeAddToCartButton'), 99, 3);
        add_filter('woocommerce_product_add_to_cart_url', array($this, 'replaceAddToCartButtonUrl'), 99, 2);
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'replaceAddToCartButtonText'), 99, 2);
    }

    /*
    * Remove Ajax Cart Button.
    */
    public static function removeAddToCartButton($bool, $feature, $product)
    {
        if ('ajax_add_to_cart' != $feature) return $bool;

        if ($product->is_type('simple')) {
            $product_id = $product->get_id();
            if (get_post_meta($product_id, 'wwbp_is_enable', true) == 'yes') {
                return false;
            }
        }
        return $bool;
    }

    /*
    * Replace Add to Cart URL into Single Product URL (Only for Simple Products).
    */
    public static function replaceAddToCartButtonUrl($link, $product)
    {
        if ($product->is_type('simple')) {
            $product_id = $product->get_id();
            if (get_post_meta($product_id, 'wwbp_is_enable', true) == 'yes') {
                return esc_url($product->get_permalink());
            }
        }
        return $link;
    }

    /*
    * Replace Add To Cart Button Text (Only for Simple Products).
    */
    public static function replaceAddToCartButtonText($text, $product)
    {
        if ($product->is_type('simple')) {
            $product_id = $product->get_id();
            if (get_post_meta($product_id, 'wwbp_is_enable', true) == 'yes') {
                return get_option('wwbp_add_to_cart_button_label', 'Go To Product');
            }
        }
        return $text;
    }
}
