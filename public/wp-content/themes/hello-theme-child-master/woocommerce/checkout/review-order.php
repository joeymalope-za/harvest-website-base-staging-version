<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}

// Access the global WC_Cart object
global $woocommerce;

?>

<div class="woocommerce-checkout-review-order-table">
	<div class="table-checkout-order-wrapper">

		<!-- Table Header -->
		<div class="order-table-row">
			<div class="product-name"></div>
			<div class="price-1" style="color:#000000; text-decoration:none;"><?php esc_html_e( 'Greenhorn Member', 'woocommerce' ); ?></div>
			<div class="price-2"><?php esc_html_e( 'High Rollers Club', 'woocommerce' ); ?></div>
		</div>

		<!-- Table Body -->
		<div class="order-table-body">
		<?php
			do_action('woocommerce_review_order_before_cart_contents');

			$user = wp_get_current_user();
			$is_high_rollers_club_member = in_array('high_rollers_club', (array) $user->roles);

			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
				$product_weight = $_product->get_weight();
				$quantity = $cart_item['quantity'];

				if ($_product && $_product->exists() && $quantity > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
					// Regular price calculation
					$regular_price_per_unit = wc_get_price_to_display($_product, ['price' => $_product->get_regular_price()]); // Base price per unit
					$regular_price_total = $regular_price_per_unit * $quantity; // Total regular price for non-weighted product
					$regular_price_html = wc_price($regular_price_total);

					// Sale price calculation
					if ($_product->is_on_sale()) {
						$sale_price_per_unit = wc_get_price_to_display($_product, ['price' => $_product->get_sale_price()]); // Base sale price per unit
						$sale_price_total = $sale_price_per_unit * ($product_weight > 0 ? $product_weight : 1) * $quantity; // Total sale price, adjusted for weight if applicable
						$sale_price_html = wc_price($sale_price_total);
					} else {
						$sale_price_html = ''; 
					}

					// For weighted products, adjust the regular price
					if ($product_weight > 0) {
						$regular_price_total = $regular_price_per_unit * $product_weight * $quantity; // Total regular price for weighted product
						$regular_price_html = wc_price($regular_price_total);
					}

					?>
					<div class="order-table-row">
						<div class="product-name">
							<?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)) . '&nbsp;'; ?>
							<?php echo apply_filters('woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $quantity) . '</strong>', $cart_item, $cart_item_key); ?>
							<?php echo wc_get_formatted_cart_item_data($cart_item); ?>
						</div>
						<div class="price-1">
							<?php echo $regular_price_html; // Display regular price ?>
						</div>
						<div class="price-2">
							<?php echo $sale_price_html; // Display sale price or regular price if not on sale ?>
						</div>
					</div>

					<?php
				}
			}

			do_action('woocommerce_review_order_after_cart_contents');
		?>


		<div class="order-table-row cart-subtotal">
			<div class="product-name"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></div>
			<div class="price-1">
				<?php 
				
				$shipping_total = $woocommerce->cart->get_shipping_total();
				// Display the shipping total
				echo wc_price($shipping_total);
				?>
			</div>
			<div class="price-2">
				<?php 
				echo wc_price($shipping_total);
				?>
			</div>
		</div>

		</div>

		<!-- Table Footer -->
		<div class="order-table-footer">
			<!-- Footer Rows -->
			
			<?php
				// Initialize the subtotal variables
				$regular_price_subtotal = $sale_price_subtotal = 0;

				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
					$quantity = $cart_item['quantity'];

					if ($_product && $_product->exists() && $quantity > 0) {
						// Determine the multiplier based on product weight
						$product_weight = $_product->get_weight();
						$multiplier = $product_weight > 0 ? $product_weight * $quantity : $quantity;

						// Calculate the subtotal for regular price
						$regular_price_per_unit = $_product->get_regular_price();
						$regular_price_subtotal += $regular_price_per_unit * $multiplier;

						// Calculate the subtotal for sale price
						if ($_product->is_on_sale()) {
							$sale_price_per_unit = $_product->get_sale_price();
						} else {
							$sale_price_per_unit = $regular_price_per_unit;
						}
						$sale_price_subtotal += $sale_price_per_unit * $multiplier;
					}
				}

				// Convert subtotals to price HTML
				$regular_price_subtotal_html = wc_price($regular_price_subtotal);
				$sale_price_subtotal_html = wc_price($sale_price_subtotal);

				// Calculate and convert total payments to price HTML
				$shipping_total = WC()->cart->get_shipping_total();
				$regular_price_payment_total = $regular_price_subtotal + $shipping_total;
				$sale_price_payment_total = $sale_price_subtotal + $shipping_total;

				$regular_price_payment_total_html = wc_price($regular_price_payment_total);
				$sale_price_payment_total_html = wc_price($sale_price_payment_total);
			?>


			<!-- Subtotal -->
			<div class="order-table-row cart-subtotal">
				<div class="product-name"><?php esc_html_e( 'Order total', 'woocommerce' ); ?></div>
				<div class="subtotal-1 price-1"><?php echo $regular_price_subtotal_html; ?></div>
				<div class="subtotal-2 price-2"><?php echo $sale_price_subtotal_html; ?></div>
			</div>

			<!-- Payment Total -->
			<div class="order-table-row payment-total">
				<div class="product-name"><?php esc_html_e( 'Payment Total', 'woocommerce' ); ?></div>
				<div class="total-1 price-1"><?php echo $regular_price_payment_total_html; ?></div>
				<div class="total-2 price-2"><?php echo $sale_price_payment_total_html;  ?></div>
			</div>

		</div>

	</div>

	<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

	<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

	<div class="custom-shipping-address">
		<?php
			$customer_id = get_current_user_id();
			$customer = new WC_Customer( $customer_id );

			// Get the shipping methods available for the current package.
			$packages = WC()->shipping()->get_packages();
			$first_package = reset( $packages );
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$full_name = WC()->customer->get_shipping_first_name() . ' ' . WC()->customer->get_shipping_last_name();

		?>
		<div class="custom-shipping-summary">
			<div class="custom-shipping-address">
				<h3>Shipping address</h3>
				<div class="address-container">
					<strong class="customer-name" style="width: 100%;"> <?php echo esc_html( $full_name ); ?></strong>
					<p class="shortened-address"><?php echo esc_html( WC()->customer->get_shipping_address() ); ?></p>
				</div>

				<div class="delivery-instructions-container">
					<p>Add delivery instructions</p>
					<span class="toggle-instructions fa fa-chevron-right"></span> 
					<textarea class="delivery-notes" name="order_comments" id="order_comments" placeholder="<?php echo esc_attr__( 'Notes about your order, e.g. special notes for delivery.', 'woocommerce' ); ?>"><?php echo esc_textarea( WC()->checkout->get_value( 'order_comments' ) ); ?></textarea>
				</div>
			</div>
		</div>
	</div>

	<?php wc_cart_totals_shipping_html(); ?>

	<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

	<?php endif; ?>
	<h3>Payment Information</h3>
</div>
