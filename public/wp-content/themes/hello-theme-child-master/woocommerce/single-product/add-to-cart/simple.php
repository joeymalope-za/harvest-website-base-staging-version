<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

require_once get_stylesheet_directory().'/wco/classes/class-orders.php';

defined( 'ABSPATH' ) || exit;

global $product;

// Get users prescription
$orders = new Orders();

$user_id = get_current_user_id();
$active_prescription = get_user_meta($user_id, 'active_prescription', true) ?: 0;
$validate_thc_content = get_field('validate_thc_content');
$thc_content = get_field('thc_content');

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>

		<?php 
		/** 
		* Check if product item needs thc_content validation
		* Validation of thc_content applies to flower product only
		* The value if this product if needs to be validated is found in woocommerce admin product single page
		*/
		if($validate_thc_content){
			$prescription_allowed = false;
			$date_expired = false;

			// Check if user has a prescription
			if(is_array($active_prescription)){

				// If user has prescription, check expiry date
				foreach($active_prescription as $prescription_item){
					$expiry_date = $prescription_item['expiration_date'];
	
					// expiry date is required to all users with prescription
					$is_expired = $expiry_date ? $orders->is_prescription_expired($expiry_date) : null; 
					$current_thc = $prescription_item['thc_content']===$thc_content;
	
					if($current_thc){
						$prescription_allowed = true;
					}
	
					if($current_thc && $is_expired){
						$date_expired = true;
					}
				}
	
				// Check product thc_content matches with user's prescription
				if($prescription_allowed && !$date_expired){?>
					<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
				<?php }
				else{
					esc_html_e('<p class="custom-notice">Hold up! The strength of THC doesn`t match with your prescription or prescription date is expired. You can check your limit in <a href="/my-account/">my account</a>. Error? Please contact support.</p>', 'woocommerce');
				}
			}
			else{
				esc_html_e('<p class="custom-notice">Hold up! You have no prescription to purchase this product. Get doctors prescription via our harvest web app. Error? Please contact support.</p>', 'woocommerce');
			}
		}
		else{?>
			<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
		<?php } ?>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
