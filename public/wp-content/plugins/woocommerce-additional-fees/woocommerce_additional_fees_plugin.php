<?php
/**
 * Backwards compat.
 *
 * @since 3.1.5
 */
if ( ! defined( 'ABSPATH' ) )   {  exit;  }

$active_plugins = get_option( 'active_plugins', array() );

foreach ( $active_plugins as $key => $active_plugin ) 
{
	if ( strstr( $active_plugin, '/woocommerce_additional_fees_plugin.php' ) ) 
	{
		$active_plugins[ $key ] = str_replace( '/woocommerce_additional_fees_plugin.php', '/woocommerce-additional-fees.php', $active_plugin );
	}
}

update_option( 'active_plugins', $active_plugins );

