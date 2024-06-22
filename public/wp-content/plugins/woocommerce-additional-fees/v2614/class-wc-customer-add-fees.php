<?php
/**
 * WC Core fix: WC requires session since 2.2.
 * 
 * Create default customer data like in version 2.1.12
 *
 * @author Guenter Schoenmann
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Customer_Add_Fees extends WC_Customer
{
	
	/**
	 * 
	 * @param WC_Order_Add_Fees $order
	 */
	public function __construct( WC_Order_Add_Fees $order ) 
	{
		if( empty( WC()->session ) ) 
		{
			$default = apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) );

        	if ( strstr( $default, ':' ) ) 
			{
        		list( $country, $state ) = explode( ':', $default );
        	} 
			else 
			{
        		$country = $default;
        		$state   = '';
        	}
			
			/**
			 * For compatibility with EU-VAT-Number plugin we have to check VAT Number from order
			 * 
			 * Currently we ignore the validity check in post meta
			 */
			$vat_number = get_post_meta( $order->id , '_vat_number', true );
			
//			$vat_number_is_valid = get_post_meta( $order->id , '_vat_number_is_valid', true );
//			if( ! in_array( $vat_number_is_valid, array( 'true', 'false' ) ) )
//			{
//				$vat_number_is_valid = get_post_meta( $order->id , '_vat_number_is_validated', true );
//			}
//			$eu_vat_checked = get_post_meta( $order->id , '_eu_vat_checked', true );
//			
//			$vat_number_is_valid = ( 'true' == $vat_number_is_valid ) ? true : false;
//			$eu_vat_checked = ( 'true' == $eu_vat_checked ) ? true : false;
//			$is_vat_exempt = ( ( ! empty( $vat_number ) ) && $vat_number_is_valid && $eu_vat_checked ) ? true : false;
			
			$is_vat_exempt = ( ! empty( $vat_number ) ) ? true : false;

			$this->_data = array(
				'country' 				=> esc_html( $country ),
				'state' 				=> '',
				'postcode' 				=> '',
				'city'					=> '',
				'address' 				=> '',
				'address_2' 			=> '',
				'shipping_country' 		=> esc_html( $country ),
				'shipping_state' 		=> '',
				'shipping_postcode' 	=> '',
				'shipping_city'			=> '',
				'shipping_address'		=> '',
				'shipping_address_2'	=> '',
				'is_vat_exempt' 		=> $is_vat_exempt,
				'calculated_shipping'	=> false
			);
		} 
		else 
		{
			parent::__construct();
		}
	}
}
