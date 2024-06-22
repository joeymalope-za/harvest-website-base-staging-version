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
	 * @param WC_Abstract_Order $order
	 */
	public function __construct( WC_Abstract_Order $order ) 
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
			$vat_number = $order->meta_exists( '_vat_number' ) ? $order->get_meta( '_vat_number', true ) : '';
			
//			$vat_number_is_valid = $order->meta_exists( '_vat_number_is_valid' ) ? $order->get_meta( '_vat_number_is_valid', true ) : 'false';
//			if( ! in_array( $vat_number_is_valid, array( 'true', 'false' ) ) )
//			{
//				$vat_number_is_valid = get_post_meta( $order->id , '_vat_number_is_validated', true );
//			}
//			
			
			$this->set_billing_country( $order->get_billing_country() ); 
			$this->set_billing_state( $order->get_billing_state() );
			$this->set_shipping_country( $order->get_shipping_country() );
			$this->set_calculated_shipping( false );
			
			$this->set_is_vat_exempt( ! empty( $vat_number ) );
		} 
		else 
		{
			parent::__construct();
		}
	}
}
