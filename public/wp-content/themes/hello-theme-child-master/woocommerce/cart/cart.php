<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

require_once get_stylesheet_directory().'/wco/classes/class-orders.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_cart' ); ?>

<!-- Loading Overlay -->
<div class="loading-overlay" style="display: none;">Updating Cart...</div>


<!-- <h3 id="cart-headings">Cart</h3> -->

<div class="woocommerce-cart-form-wrapper">
    <!-- Subtotal at the top -->
    <div class="cart-subtotal-top">
        

        <h3>Subtotal <span>
        <?php
            $user = wp_get_current_user();
            $is_high_rollers_club_member = in_array('high_rollers_club', (array) $user->roles);

            $subtotal = 0;

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product = $cart_item['data'];
                // Determine the product weight and decide on the multiplier (use weight if available, otherwise quantity)
                $product_weight = $_product->get_weight();
                $multiplier = $product_weight > 0 ? $product_weight : $cart_item['quantity'];
                
                // Check if the product is on sale and the user has the "High Rollers Club" role
                if ($_product->is_on_sale() && $is_high_rollers_club_member) {
                    $subtotal += $_product->get_sale_price() * $multiplier;
                } else {
                    $subtotal += $_product->get_regular_price() * $multiplier;
                }
            }

            // Format the subtotal as a price
            $subtotal_html = wc_price($subtotal);

            echo $subtotal_html;

        ?>

        </span></h3>

        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button proceed-to-buy">
            Proceed to Buy
        </a>
    </div>

    <hr style="color: #d9d9d9; margin-bottom: 30px;">

    <!-- Cart items -->
    <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
        $_product = $cart_item['data'];
        $_product_id = $cart_item['product_id'];
        $product_permalink = $_product->get_permalink( $cart_item );
    ?>
        <div class="cart-item _<?php echo $_product_id ?>">
            <div class="product-details">
                <div class="item-cart-image">
                    <?php echo $_product->get_image(); // Product image ?>
                </div>
                
                <div class="item-info">
                    <span class="product-name"><?php echo $_product->get_name(); // Product name ?></span>
                    <span class="product-price">
                        <span class="user-role">Greenhorn Member</span> <?php echo wc_price( $_product->get_regular_price() ); ?>
                    </span>
                    <span class="product-sale-price">
                    <span class="user-role">High Rollers Club</span> <?php echo wc_price( $_product->get_sale_price() ); ?>
                    </span>
                </div>
            </div>
            <div class="cart-item-actions">
                <!-- Quantity input -->
                <div class="product-quantity">
                <button type="button" class="minus" data-quantity="minus" data-cart_item_key="<?php echo $cart_item_key; ?>" data-product_id="<?php echo $_product_id; ?>">-</button>
                <?php
                    if ($_product->is_sold_individually()) {
                        $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
                    } else {
                        $product_quantity = woocommerce_quantity_input(array(
                            'input_name'   => "cart[{$cart_item_key}][qty]",
                            'input_value'  => $cart_item['quantity'],
                            'max_value'    => $_product->get_max_purchase_quantity(),
                            'min_value'    => '0',
                        ), $_product, false);
                    }
                    echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                ?>
                <button type="button" class="plus" data-quantity="plus" data-cart_item_key="<?php echo $cart_item_key; ?>" data-product_id="<?php echo $_product_id; ?>">+</button>

                </div>
                <!-- Delete button -->
                <div class="button product-remove">
                    <?php
                        // Remove item link
                        echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                            '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">Delete</a>',
                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                            esc_html__( 'Remove this item', 'woocommerce' ),
                            esc_attr( $product_id ),
                            esc_attr( $_product->get_sku() )
                        ), $cart_item_key );
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Continue Shopping button -->
    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button continue-shopping">
        Continue Shopping
    </a>

    <?php do_action( 'woocommerce_after_cart_table' ); ?>
</div>

<!-- Nonce field and actions -->
<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
    <?php do_action( 'woocommerce_cart_actions' ); ?>
</form>


<?php do_action( 'woocommerce_after_cart' ); ?>