<?php
/**
 * Extends the base class with functions required for this plugin.
 *
 * @author Guenter Schoenmann
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

/**
 * Handles actions needed for a saved order
 * 
 * 
 */
class WC_Order_Add_Fees
{
	/**
	 *
	 * @since 3.0.0
	 * @var WC_Abstract_Order 
	 */
	public $order;
	
	/**
	 * 
	 * @param type $id
	 */
	public function __construct( $id = '' ) 
	{
		$this->order = wc_get_order( $id );
		
		$this->dp                = (int) get_option( 'woocommerce_price_num_decimals' );
		$this->round_at_subtotal = get_option( 'woocommerce_tax_round_at_subtotal' ) == 'yes';
	}
	
	public function __destruct()
	{
		unset( $this->order );
	}
	
	/**
	 * Adds the given array of fees (structure must be equivalent to the fees given in cart)
	 * 
	 * @param array $cart_fees structure as defined in WC-Cart
	 */
	public function add_new_fees( array &$cart_fees )
	{
		foreach ( $cart_fees as $fee_key => $fee ) 
		{
			$item                 = new WC_Order_Item_Fee();
			$item->legacy_fee     = $fee;				// @deprecated For legacy actions.
			$item->legacy_fee_key = $fee_key;			// @deprecated For legacy actions.
			$item->set_props( array(
									'name'      => $fee->name,
									'tax_class' => $fee->taxable ? sanitize_title( $fee->tax_class ) : 0,
									'total'     => $fee->amount,
									'total_tax' => $fee->tax,
									'taxes'     => array(
										'total' => $fee->tax_data,
										),
									) 
								);

			/**
			 * Action hook to adjust item for our fee before save.
			 * 
			 * @used_by:	WC_Add_Fees			500
			 * 
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_checkout_create_order_fee_item', $item, $fee_key, $fee, $this->order );

					// Add item to order and save.
			$this->order->add_item( $item );
		}
	}
	

	/**
	 * Updates the payment info for this order
	 * 
	 * @param string $payment_gateway_key
	 * @param string $payment_gateway_title
	 */
	public function update_payment_method( $payment_gateway_key, $payment_gateway_title )
	{
		$this->order->set_payment_method( $payment_gateway_key ); 
		$this->order->set_payment_method_title( $payment_gateway_title );
	}
	
	/**
	 * Recalculates the total values of the complete saved order. Does not recalculate the tax values of the entries.
	 * 
	 *		- tax totals are recalculated, deleted and new inserted
	 *		- totals are recalculated
	 *		- order is saved
	 * 
	 * @return float		calculated grand total.
	 */
	public function recalc_order_and_save()
	{
		/**
		 * Fix with EU VAT Plugin: does not take care for vat_exempt of order
		 * 
		 * @since 3.0.3
		 */
		$calc_taxes = ( 'yes' == $this->order->is_vat_exempt ) ? false : true;
		return $this->order->calculate_totals( $calc_taxes );
	}
	
}

