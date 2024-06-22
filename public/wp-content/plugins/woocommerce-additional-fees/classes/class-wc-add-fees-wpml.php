<?php
/**
 * Connects this plugin to WPML
 *
 * @author Guenter Schönmann
 * @version 1.0.0
 * @since 3.1.8
 * 
 * @needs WPML 4.2.7.1  (might work with earlier versions also - but not tested)
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Add_Fees_WPML 
{
	const WPML_PLUGIN_NAME = 'sitepress-multilingual-cms/sitepress.php';
	const MIN_WPML_VERSION = '4.2.7';
	
	/**
	 * Needed to ensure, that initialisation is not run before all plugins are loaded
	 * 
	 * @since 3.1.8
	 * @var bool
	 */
	public $is_init;
	
	/**
	 * true, if Plugin WPML is acrtivated and version is OK
	 * 
	 * @since 3.1.8
	 * @var bool
	 */
	public $active;
	
	/**
	 *
	 * @var bool
	 */
	public $version_conflict;

	/**
	 * Language information array
	 * 
	 * @since 3.1.8
	 * @var array
	 */
	public $langs;
	
	/**
	 * @since 3.1.8
	 */
	public function __construct() 
	{
		$this->is_init = false;
		$this->active = false;
		$this->version_conflict = true;
		
		$this->langs = array();
		
		add_filter( 'wc_add_fees_option_gateway_default', array( $this, 'handler_wc_add_fees_option_gateway_default'), 10, 4 );
		add_filter( 'wc_add_fees_get_settings_options_gateway_default', array( $this, 'handler_wc_add_fees_get_settings_options_gateway_default' ), 10, 4 );
		add_filter( 'wc_add_fees_save_settings_options_gateway_default', array( $this, 'handler_wc_add_fees_save_settings_options_gateway_default'), 10, 4 );

		add_filter( 'wc_add_fees_fee_output_text', array( $this, 'handler_wc_add_fees_fee_output_text' ), 10, 2 );
	}
	
	/**
	 * @since 3.1.8
	 */
	public function __destruct() 
	{
		unset( $this->langs );
	}
	
	/**
	 * Check, if WPML is active and set state of this class
	 * 
	 * @since 3.1.8
	 */
	public function init()
	{		
		if( $this->is_init ) 
		{
			return;
		}
				
			//	also checks for network activation
		if ( ! function_exists( 'is_plugin_active' ) )
		{
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		if ( ! is_plugin_active( self::WPML_PLUGIN_NAME ) ) 
		{
			return;
		}
		
		if( ! defined( 'ICL_SITEPRESS_VERSION' ) )
		{   
			return;  
		}
		
		if( ! version_compare( self::MIN_WPML_VERSION, ICL_SITEPRESS_VERSION, '<=' ) )
		{   
			return;  
		}
		
		$this->version_conflict = false;
		
//		if( ! function_exists( 'wpml_add_translatable_content' ) )
//		{
//			if( ! file_exists( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' ) )
//			{   
//				return;  
//			}
//			
//			include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );
//		}
//		
//			//	check, that all functions exist, otherwise keep object inactive
//		if( ! function_exists( 'wpml_add_translatable_content' ) ) 
//		{   
//			return;  
//		}
		
		if( ! function_exists( 'icl_object_id' ) )
		{   
			return;  
		}
		
		$this->active = true;
		$this->is_init = true;
		
				//	get all defined languages - fallback for older versions
		$this->langs = apply_filters( 'wpml_active_languages', null, null );
		if( empty( $this->langs ) )
		{
			$this->langs = ( function_exists( 'icl_get_languages' ) ) ? icl_get_languages( 'skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str' ) : array();
		}
	}
	
	/**
	 * Extend options for WPML
	 * 
	 * @since 3.1.8
	 * @param array $options
	 * @param array $option_gateway
	 * @param string $gateway_name
	 * @param boolean $for_postmeta
	 * @return array
	 */
	public function handler_wc_add_fees_option_gateway_default( array $options, array $option_gateway, $gateway_name = '', $for_postmeta = false )
	{
		//	Do not remove in case user deactivates plugin temp.
//		if( ! $this->active ) 
//		{
//			return $options;
//		}
		
		if( ! isset( $options['outputtext_wpml'] ) || ! is_array( $options['outputtext_wpml'] ) )
		{
			$options['outputtext_wpml'] = array();
		}

		return $options;
	}
	
	/**
	 * 
	 * @since 3.1.8
	 * @param array $fields
	 * @param string $key
	 * @param WC_Gateway $gateway
	 * @param WC_Add_Fees_Panel_Admin $panel
	 * @return array
	 */
	public function handler_wc_add_fees_get_settings_options_gateway_default( array $fields, $key, $gateway, WC_Add_Fees_Panel_Admin $panel )
	{
		if( ! $this->active) 
		{
			return $fields;
		}
		
		global $sitepress;
		
		foreach ( $sitepress->get_active_languages() as $lang ) 
		{
			$default = isset( $panel->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ]['outputtext_wpml'][ $lang['code'] ] ) ? $panel->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ]['outputtext_wpml'][ $lang['code'] ] : '';
			$name = $panel->create_unique_html_name( $key, 'outputtext_wpml', $lang['code'] );
			$fields[] = array(
						'type' => 'text',
						'id' => $name,
						'title' => sprintf( __( 'Output text (%s):', WC_Add_Fees::TEXT_DOMAIN ), $lang['display_name'] ),
						'default' => $default,
						'desc' => __( 'Enter translated text for fee to display as explanation to Fee. If empty original text will be used', WC_Add_Fees::TEXT_DOMAIN ),
						'desc_tip' => true,
						'css' => "width:300px;"
					);

		}
		
		return $fields;
	}

	/**
	 * Add WPML options to options array
	 * 
	 * @since 3.1.8
	 * @param array $options
	 * @param string $key
	 * @param WC_Gateway $gateway
	 * @param WC_Add_Fees_Panel_Admin $panel
	 * @return array
	 */
	public function handler_wc_add_fees_save_settings_options_gateway_default( array $options, $key, $gateway, WC_Add_Fees_Panel_Admin $panel )
	{	
		if( ! $this->active) 
		{
			return $options;
		}
		
		global $sitepress;
		
		foreach ( $sitepress->get_active_languages() as $lang ) 
		{
			$name = $panel->create_unique_html_name( $key, 'outputtext_wpml' );
			$value = isset( $_REQUEST[ $name ][ $lang['code'] ] ) ? stripslashes( $_REQUEST[ $name ][ $lang['code'] ] ) : '';
			$options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ]['outputtext_wpml'][ $lang['code'] ] = $value;
		
		}
		
		return $options;
	}
	
	/**
	 * Returns the translated fee text or the original if empty
	 * 
	 * @since 3.1.8
	 * @param string $fee_text
	 * @param WC_Fee_Add_Fees $fee
	 * @return string
	 */
	public function handler_wc_add_fees_fee_output_text( $fee_text, WC_Fee_Add_Fees $fee )
	{	
		if( ! $this->active) 
		{
			return $fee_text;
		}
		
		global $sitepress;
		
		$current = $sitepress->get_current_language();
		
		if( '' == $current )
		{
			return $fee_text;
		}
		
		if( ! empty( $fee->gateway_option['outputtext_wpml'][ $current ] ) )
		{
			$fee_text = $fee->gateway_option['outputtext_wpml'][ $current ];
		}
		
		return $fee_text;
	}
}
