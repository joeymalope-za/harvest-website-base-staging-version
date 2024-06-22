<?php
/**
 * Description of WC_Add_Fees
 *
 * Call init_values before using any members and properties of this class to connect to woocommerce data !!!
 *
 * @author Schoenmann Guenter
 * @version 2.2.7
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Add_Fees
{
	const VERSION = '2.2.7';
	const TEXT_DOMAIN = 'woocommerce_additional_fees';

	const OPTIONNAME = 'woocommerce_additional_fees';
	const KEY_POSTMETA_PRODUCT = '_woocommerce_add_fees_product';
	const KEY_POSTMETA_ORDER = '_woocommerce_add_fees_order';

	const OPT_VERSION = 'version';
	const OPT_DEL_ON_DEACTIVATE = 'delete_on_deactivate';
	const OPT_DEL_ON_UNINSTALL = 'delete_on_uninstall';
	const OPT_ENABLE_ALL = 'enable_all';
	const OPT_ENABLE_PROD_FEES = 'enable_prod_fees';
	const OPT_ENABLE_PROD = 'enable_prod';
	const OPT_GATEWAY_PREFIX = 'gateways';			//	Main option entry => gateway key => .....

	const OPT_KEY_ENABLE = 'enable';
	const OPT_KEY_TAXCLASS = 'taxclass';
	const OPT_KEY_ADD_VALUE_TYPE = 'addvaluetype';
	const OPT_KEY_VALUE_TO_ADD = 'addvalue';
	const OPT_KEY_VALUE_TO_ADD_FIXED = 'addvalue_fix';
	const OPT_KEY_FIXED_VALUE_POS = 'fixed_val_pos';
	const OPT_KEY_MAX_VALUE = 'maxvalue';
	const OPT_KEY_OUTPUT = 'outputtext';
	
	const OPT_ENABLE_RECALC = 'recalc_fee';
	const OPT_ENABLE_RECALC_SAVE_ORDER = 'recalc_fee_save_order';
	const OPT_FIXED_GATEWAY = 'fixed_gateway';
	const OPT_KEY_FEE_ITEMS = 'fee_items';

	const VAL_FIXED = 'fixed_value';
	const VAL_ADD_PERCENT = 'add_percent';
	const VAL_INCLUDE_PERCENT = 'include_percent';

	const VAL_TAX_NONE = 'tax_none';
	const VAL_TAX_STANDARD = 'Standard';		//	woocommerce default

	const AJAX_NONCE = 'add_fee_nonce';
	const AJAX_JS_VAR = 'add_fee_vars';

	/**
	 * @var WC_Add_Fees The single instance of the class
	 * @since 2.2
	 */
	static public $_instance = null;

	/**
	 * key => value for selectbox for type of additional fees
	 *
	 * @var array
	 */
	static public $value_type_to_add = array();
	
	/**
	 * key => value for selectbox in which order the fixed value is added
	 *
	 * @var array
	 */
	static public $order_add_fixed_value = array();

	/**
	 * If true, deactivation checkbox is shown
	 *
	 * @var bool
	 */
	static public $show_activation;

	/**
	 * If true, uninstall checkbox is shown
	 *
	 * @var bool
	 */
	static public $show_uninstall;

	/**
	 *
	 * @var string
	 */
	static public $plugin_url;

	/**
	 *
	 * @var string
	 */
	static public $plugin_path;
	
	/**
	 *
	 * @var string 
	 */
	static public $plugin_base_name;

	/**
	 * All available tax classes
	 *
	 * @var array string
	 */
	public $tax_classes;

	/**
	 * All available gateways
	 *
	 * @var array WC_Payment_Gateways
	 */
	public $gateways;

	/**
	 * Current requested gateway key
	 *
	 * @var string
	 */
	public $payment_gateway_key;
	
	/**
	 * Default gateway, if no gateway selected or invalid
	 * 
	 * @var string 
	 */
	public $default_payment_gateway_key;
	
	/**
	 * Option additional fee for selected $payment_gateway_key
	 *
	 * @var array
	 */
	public $payment_gateway_option;

	/**
	 * Option array for plugin
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Set to true, if form request data was loaded already and members are initialied
	 *
	 * @var bool
	 */
	protected $request_data_loaded;

	/**
	 * WC option variable for precision 
	 * @var int
	 */
	public $dp;
	
	/**
	 * WC option where to round tax
	 * 
	 * @var boolean
	 */
	public $round_at_subtotal;
	
	/**
	 * All plugins cause errors using function payment_gateways->get_available_payment_gateways()
	 * Set this array in the constructor
	 * 
	 * @var array
	 */
	public $gateway_bugfix_array;
	
	/**
	 * Set to true, if payment gateways have to be loaded directly due to errors i third party plugins
	 * 
	 * @var boolean
	 */
	public $gateway_bugfix;

	/**
	 * a unique product line counter to make each line unique 
	 * http://www.woothemes.com/products/gravity-forms-add-ons/ allows the same product in different lines (not the WC standard behaviour)
	 * 
	 * @var int
	 */
	private $prod_fee_cnt;
	
	
	/**
	 *
	 * @since 3.1.8
	 * @var WC_Add_Fees_WPML 
	 */
	public $wpml;
	
	
	/**
	 * Main WC_Add_Fees Instance
	 *
	 * Ensures only one instance of wc_email_att is loaded or can be loaded.
	 *
	 * @return WC_Add_Fees - Main instance
	 */
	public static function instance() 
	{
		if ( is_null( self::$_instance ) ) 
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.2
	 */
	public function __clone() 
	{
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_Add_Fees::TEXT_DOMAIN ), '2.2' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.2
	 */
	public function __wakeup() 
	{
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_Add_Fees::TEXT_DOMAIN ), '2.2' );
	}
	
	public function __construct()
	{
		spl_autoload_register( 'WC_Add_Fees::autoload' );
		
		if( ! isset( self::$show_activation ) )
		{
			self::$show_activation = true;
		}

		if( ! isset( self::$show_uninstall ) )
		{
			self::$show_uninstall = true;
		}
		
		if( ! isset( self::$plugin_path ) )
		{
			self::$plugin_path = '';
		}
		
		if( ! isset( self::$plugin_url ) )
		{
			self::$plugin_url = '';
		}
		
		if( ! isset( self::$plugin_base_name ) )
		{
			self::$plugin_base_name = '';
		}
		
		$this->options = self::get_options_default();

		$this->payment_gateway_key = '';
		$this->default_payment_gateway_key = '';
		$this->payment_gateway_option = array();
		$this->tax_classes = array();
		$this->gateways = array();
		$this->request_data_loaded = false;
		
		$this->dp                = (int) get_option( 'woocommerce_price_num_decimals' );
		$this->round_at_subtotal = get_option( 'woocommerce_tax_round_at_subtotal' ) == 'yes';

			//	add all plugins that produce an error on payment_gateways->get_available_payment_gateways()
		$this->gateway_bugfix_array = array(
					'woocommerce-account-funds/woocommerce-account-funds.php'
				);
		$this->gateway_bugfix = false;
		$this->prod_fee_cnt = 0;
		$this->wpml = new WC_Add_Fees_WPML();
		
		if( is_admin() )
		{
			new WC_Add_Fees_Admin();
		}
		
		add_action( 'init', array( $this, 'handler_wp_load_textdomains' ), 1 );
		add_action( 'init', array( $this, 'handler_wp_init' ), 1 );
		add_action( 'init', array( $this, 'handler_wp_register_scripts' ), 10 );
		
		add_action( 'wp_print_styles', array( $this, 'handler_wp_print_styles' ), 1000 );
		
			//	removed with 2.4 as it fires a PHP notice !!!
//		add_action( 'woocommerce_init', array( $this, 'handler_wc_init' ), 500 );
		$version = ( function_exists( 'WC' ) ) ? WC()->version : '2.1.6';
		$priority = 500;
		if( is_admin() && version_compare( $version, '3.7', '>=' ) )
		{
			$priority = 1;
		}
		add_action( 'wp_loaded', array( $this, 'handler_wc_init' ), $priority );

		if( $this->options[self::OPT_ENABLE_ALL] )
		{
			$this->attach_to_woocommerce();
		}
		
		add_action( 'wp_ajax_nopriv_add_fee_calc_fee_pay_order', array( $this, 'handler_ajax_calc_fee_pay_order' ) );
		add_action( 'wp_ajax_add_fee_calc_fee_pay_order', array( $this, 'handler_ajax_calc_fee_pay_order' ) );
	}

	public function __destruct()
	{
		unset( $this->options );
		unset( $this->payment_gateway_key );
		unset( $this->payment_gateway_option );
		unset( $this->tax_classes );
		unset( $this->gateways );
		unset( $this->gateway_bugfix_array );
	}
	
	/**
	 * This function is called by the parser when it finds a class, that is not loaded already.
	 * Needed, because WC Classes might be loaded after our plugin.
	 *
	 * @param string $class_name		classname to load rendered by php-parser
	 */
	static public function autoload( $class_name )
	{
		$class_name = strtolower( $class_name );
		$filename = str_replace( '_', '-', $class_name );
		
			//	insert all folders, where class files may be found.
			//	files must follow following naming convention, all lowercase: 
			//				'class-' . $filename . '.php'
			//
		$folders_php = array(
					WC_Add_Fees::$plugin_path . 'classes/',
					WC_Add_Fees::$plugin_path . 'classes/panels/'
			);
		
		foreach( $folders_php as $folder )
		{
			$file = $folder . 'class-' . $filename . '.php';
			if( file_exists( $file ) )
			{
				require_once $file;
				return;
			}
		}
	}
	
	/**
	 * Override plugin uri with filters hooked by other plugins
	 */
	public function handler_wp_init()
	{
		self::$plugin_url = trailingslashit( plugins_url( '', plugin_basename( dirname( __FILE__ ) ) ) );
		
		//	init WPML and reload options to fill default values of WPML
		$this->wpml->init();
	}
	
	/**
	 * Localisation
	 **/
	public function handler_wp_load_textdomains()
	{
		$pos = strrpos( self::$plugin_base_name, '/' );
		if( $pos === false )
		{
			$pos = strrpos( self::$plugin_base_name, '\\' );
		}
		
		$language_path = ( $pos === false ) ? 'languages' : trailingslashit ( substr( self::$plugin_base_name, 0, $pos + 1 ) ) . 'languages';		
		load_plugin_textdomain( self::TEXT_DOMAIN, false, $language_path );
	}

	/**
	 *
	 */
	public function handler_wp_register_scripts()
	{
		wp_register_script( 'wc_additional_fees_script', self::$plugin_url . 'js/wc_additional_fees.js', array( 'woocommerce' ) );
	}

	/**
	 *
	 */
	public function handler_wp_print_styles()
	{
		$var = array( 
			'add_fee_ajaxurl' => admin_url( 'admin-ajax.php' ),
			self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE ),
			'alert_ajax_error' => __( 'An internal server error occured in processing a request. Please try again or contact us. Thank you. ', self::TEXT_DOMAIN )
			);		
		
		wp_enqueue_script( 'wc_additional_fees_script' );
		wp_localize_script( 'wc_additional_fees_script', self::AJAX_JS_VAR, $var );
		
	}

	/**
	 * Possible bugfix - status of post is sometimes reset to old style without wc- when recalc of order
	 * Reupdates the status to new value
	 * 
	 * @param int $post_ID
	 * @param WP_Post $post
	 * @param bool $update
	 */
	public function handler_wp_save_post_shop_order( $post_ID, WP_Post $post, $update )
	{
		global $wpdb;
		
		$arr_stat = array_keys( wc_get_order_statuses() );
		
		if( in_array( $post->post_status, $arr_stat) ) 
		{
			return;
		}
		
		$new_stat = 'wc-' . $post->post_status;
		
			//	skip not registered status
		if( ! in_array( $new_stat, $arr_stat) ) 
		{
			return;
		}
		
		$wpdb->update( $wpdb->posts, 
						array( 'post_status' => $new_stat), 
						array( 'ID' => $post_ID ) 
					);
	}
	
	/**
	 * Attach objects to WooCommerce Data
	 */
	public function handler_wc_init()
	{
		$this->init_values();
	}


	/**
	 * Attach class to WooCommerce hooks
	 */
	protected function attach_to_woocommerce()
	{
		/**
		 * Attach to add fees applied to single products (works only when a cart is existong)
		 *
		 * classes/class-wc-cart
		 * do_action( 'woocommerce_before_calculate_totals', $this );
		 */
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'handler_wc_cart_calculate_fees' ), 500, 1 );
		

		/**
		 * Attach to add fees applied to total cart (works only when a cart is existing)
		 *
		 * classes/class-wc-cart
		 * previous do_action( 'woocommerce_calculate_totals', $this );
		 * now changed to do apply_filter( 'woocommerce_calculated_total', $total); because of compatibility issues with subscription plugin
		 */
		add_filter( 'woocommerce_calculated_total', array( $this, 'handler_wc_calculated_totals' ), 500, 2 );
		
		/**
		 * Attach to modify fee entry on checkout
		 * 
		 * @since 3.0.0
		 * woocommerce\includes\class-wc-checkout.php
		 * apply_filters( 'woocommerce_checkout_create_order_fee_item', $item, $fee_key, $fee, $order );
		 */
		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'handler_wc_checkout_create_order_fee_item' ), 500, 4 );
		
		/**
		 * Needed to properly set selected payment gateway radiobox on form-pay page for the order
		 * (wc-core only selects default gateway)
		 * 
		 * includes/shortcodes/class-WC-Shortcode-Checkout
		 * do_action( 'before_woocommerce_pay' );
		 */
		add_action( 'before_woocommerce_pay', array( $this, 'handler_wc_before_pay' ), 500 );
		
		/**
		 * Order items are deleted - Removes the information about our fees
		 * 
		 * includes/class-wc-checkout.php
		 * do_action( 'woocommerce_resume_order', $order_id );
		 */
		add_action( 'woocommerce_resume_order', array( $this, 'handler_wc_resume_order' ), 500, 1 );
		
		/**
		 * Possible bugfix - status of post is sometimes reset to old style without wc- when recalc of order
		 * WC()->version > '2.2.0'
		 * 
		 * do_action( "save_post_{$post->post_type}", $post_ID, $post, $update );
		 */
		add_action( 'save_post_shop_order', array( $this, 'handler_wp_save_post_shop_order' ), 5000, 3 );
		
		
		/**
		 * Bugfix Subscription Plugin:
		 * We have to remove our fees on Pay for order page from existing order
		 * 
		 * @since 3.0.5
		 */		
//		add_action( 'woocommerce_generated_manual_renewal_order', array( $this, 'handler_wc_generated_manual_renewal_order' ), 10, 1 );
//		add_action( 'woocommerce_subscription_renewal_payment_failed',  array( $this, 'handler_wc_subscription_renewal_payment_failed' ), 10, 2 );
		
					// When a failed/pending renewal order is paid for via checkout, ensure a new order isn't created due to mismatched cart hashes
		add_filter( 'woocommerce_create_order', array( &$this, 'handler_woocommerce_create_order' ), 100, 2 );
		
		add_action( 'woocommerce_adjust_order_fees_for_setup_cart_for_subscription_initial_payment', array( $this, 'handler_wc_adjust_order_fees_for_subscription_initial_payment' ), 10, 2 );
		add_action( 'woocommerce_adjust_order_fees_for_setup_cart_for_subscription_renewal', array( $this, 'handler_wc_adjust_order_fees_for_subscription_renewal' ), 10, 2 );
		add_action( 'woocommerce_adjust_order_fees_for_setup_cart_for_subscription_resubscribe', array( $this, 'handler_wc_adjust_order_fees_for_subscription_resubscribe' ), 10, 2 );
		
		/**
		 * WCGM Bug fixes: adds all fee taxes to total taxes - result is, that our fee taxes are added twice -> substract fee taxes
		 */		
		add_filter( 'woocommerce_cart_get_taxes', array( $this, 'handler_wc_add_fee_to_cart_tax_totals' ), 500, 2 );
		add_filter( 'woocommerce_order_get_tax_totals', array( $this, 'handler_wc_add_fee_to_order_get_tax_totals' ), 500, 2 );
				//	higher priority than WCGM to detach WCGM handler !!!!!
		add_action( 'woocommerce_saved_order_items', array( $this, 'handler_wc_re_calculate_tax_on_save_order_items' ), 5, 1 );	
	}
	

	/**
	 * Gets the options for this plugin and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_options_default()
	{
		$default = array(
			WC_Add_Fees::OPT_VERSION => WC_Add_Fees::VERSION,
			WC_Add_Fees::OPT_DEL_ON_DEACTIVATE => false,
			WC_Add_Fees::OPT_DEL_ON_UNINSTALL => true,
			WC_Add_Fees::OPT_ENABLE_ALL => true,
			WC_Add_Fees::OPT_ENABLE_PROD_FEES => true,
			WC_Add_Fees::OPT_GATEWAY_PREFIX => array()
			);

		if( isset( self::$_instance) && ( count( WC_Add_Fees::instance()->gateways) > 0 ) )
		{
			foreach ( WC_Add_Fees::instance()->gateways as $key => $gateway )
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default( $option_gateway, $gateway->title );
				$default[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] = $go;
			}
		}

		$options = get_option( self::OPTIONNAME, array() );

		$go = array();
		if( isset( $options[self::OPT_GATEWAY_PREFIX] ) )
		{
			$go = $options[self::OPT_GATEWAY_PREFIX];
		}
		
		$new_go = array();
		foreach ( $default[self::OPT_GATEWAY_PREFIX] as $gateway_key => $value ) 
		{
			$new_go[$gateway_key] = isset( $go[ $gateway_key ] ) ? wp_parse_args( $go[ $gateway_key ], $value ) : $value;
		}
		foreach ( $go as $gateway_key => $value ) 
		{
			if( ! isset( $new_go[ $gateway_key ] ) )
			{
				$new_go[ $gateway_key ] = $value;
			}
		}
		
		$new_options = wp_parse_args( $options, $default );
		$new_options[self::OPT_GATEWAY_PREFIX] = $new_go;

		$old_opt = serialize( $options );
		$new_opt = serialize( $new_options );

		if( version_compare( $new_options[self::OPT_VERSION], self::VERSION, '!=' ) || ( $old_opt != $new_opt ) )
		{
			$new_options[self::OPT_VERSION] = self::VERSION;
			update_option( WC_Add_Fees::OPTIONNAME, $new_options );
		}

		return $new_options;
	}
	

	/**
	 * Gets the post meta for this product and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_post_meta_product_default( $post_id )
	{
		$default = array(
			WC_Add_Fees::OPT_ENABLE_PROD => 'yes',
			WC_Add_Fees::OPT_GATEWAY_PREFIX => array()
			);

		if( isset( self::$_instance ) && ( count( WC_Add_Fees::instance()->gateways ) > 0) )
		{
			foreach ( WC_Add_Fees::instance()->gateways as $key => $gateway )
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default( $option_gateway, $gateway->title, true );
				$default[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] = $go;
			}
		}
		
		$default = apply_filters( 'wc_add_fees_post_meta_product_default', $default, $post_id );

		$pm = get_post_meta( $post_id, self::KEY_POSTMETA_PRODUCT, true );

		$g_pm = array();
		if( isset( $pm[self::OPT_GATEWAY_PREFIX] ) )
		{
			$g_pm = $pm[self::OPT_GATEWAY_PREFIX];
		}

		$new_g_pm = wp_parse_args( $g_pm, $default[self::OPT_GATEWAY_PREFIX] );
		$new_pm = wp_parse_args( $pm, $default );
		$new_pm[self::OPT_GATEWAY_PREFIX] = $new_g_pm;

		$old_opt = serialize( $pm );
		$new_opt = serialize( $new_pm );

		if( $old_opt != $new_opt )
		{
			update_post_meta( $post_id, self::KEY_POSTMETA_PRODUCT, $new_pm );
		}

		return $new_pm;
	}
	
	/**
	 * Gets the post meta for this order and returns an array filled with all needed values initialised
	 * 
	 * OPT_KEY_FEE_ITEMS array:   (order item #) => wc_calc_add_fee
	 *
	 * @return array
	 */
	static public function &get_post_meta_order_default( $post_id )
	{
		$default = array(
			WC_Add_Fees::OPT_ENABLE_RECALC => 'yes',
			WC_Add_Fees::OPT_ENABLE_RECALC_SAVE_ORDER => 'yes',
			WC_Add_Fees::OPT_FIXED_GATEWAY => 'no',
			WC_Add_Fees::OPT_KEY_FEE_ITEMS => array()
			);
		
		$default = apply_filters( 'wc_add_fees_post_meta_order_default', $default, $post_id );
		
		$pm = get_post_meta( $post_id, self::KEY_POSTMETA_ORDER, true );
		$new_pm = wp_parse_args( $pm, $default );
		
		$new_pm = apply_filters( 'wc_add_fees_post_meta_order', $new_pm, $post_id );
		
		$old_opt = serialize( $pm );
		$new_opt = serialize( $new_pm );
		
		if( $old_opt != $new_opt )
		{
			update_post_meta( $post_id, self::KEY_POSTMETA_ORDER, $new_pm );
		}

		return $new_pm;
	}

	/**
	 * Returns the initialized option array
	 *
	 * @param array $option_gateway
	 * @param string $gateway_name
	 * @param bool $for_postmeta
	 * @return array
	 */
	static public function &get_option_gateway_default( array $option_gateway, $gateway_name = '', $for_postmeta = false )
	{
		$text = __( 'Additional Fee', WC_Add_Fees::TEXT_DOMAIN );
		if( is_string( $gateway_name ) && ! empty( $gateway_name ) )
		{
			$text = __( 'Fee for ', WC_Add_Fees::TEXT_DOMAIN ) . $gateway_name;
		}
		$text .= ':';

		$enable = ( $for_postmeta ) ? 'no' : false;		//	disable by default
		
		$default = array(
					WC_Add_Fees::OPT_KEY_ENABLE				=> $enable,
					WC_Add_Fees::OPT_KEY_OUTPUT				=> $text,
					WC_Add_Fees::OPT_KEY_TAXCLASS			=> WC_Add_Fees::VAL_TAX_STANDARD,
					WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE		=> WC_Add_Fees::VAL_ADD_PERCENT,
					WC_Add_Fees::OPT_KEY_VALUE_TO_ADD		=> 0,
					WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED => 0,
					WC_Add_Fees::OPT_KEY_FIXED_VALUE_POS	=> 'after',			//	'after' | 'before'
					WC_Add_Fees::OPT_KEY_MAX_VALUE			=> 0,
					'minvalue'								=> 0,
					'minimum_fee'							=> 0
			);
		
		/**
		 * @used_by WC_Add_Fees_WPML				10
		 * @since 3.1.8
		 * @param array $option_gateway
		 * @param string $gateway_name
		 * @param bool $for_postmeta
		 * @return array
		 */
		$default = apply_filters( 'wc_add_fees_option_gateway_default', $default, $option_gateway, $gateway_name, $for_postmeta );
		
		$new_options = shortcode_atts( $default, $option_gateway );
		return $new_options;
	}
	
	/**
	 * Hook in pay for order page to set cart hash in order to avoid creating a new order
	 * 
	 * @since 3.0.5
	 * @param int|null		$order_id
	 * @param WC_Checkout	$checkout
	 * @return int|null
	 */
	public function handler_woocommerce_create_order( $order_id, WC_Checkout $checkout )
	{
		global $wp;
		
		if( ! class_exists( 'WC_Subscription' ) )
		{
			return $order_id;
		}
		
		if( ! defined( 'WOOCOMMERCE_CHECKOUT' ) || ( true !== WOOCOMMERCE_CHECKOUT ) )
		{
			return $order_id;
		}
		
		if ( empty( WC()->cart->cart_contents ) ) 
		{
			return $order_id;
		}
			
			
		$is_subscription = false;
		
		foreach ( WC()->cart->cart_contents as $cart_item ) 
		{
			if ( isset( $cart_item['subscription_initial_payment'] ) || isset( $cart_item['subscription_renewal'] ) || isset( $cart_item['subscription_resubscribe'] ) ) 
			{
				$is_subscription = true;
			}
		}
	
		if( ! $is_subscription )
		{
			return $order_id;
		}
		
		
		$curr_order_id      = absint( WC()->session->get( 'order_awaiting_payment' ) );
		if( $curr_order_id <= 0 )
		{
			return $order_id;
		}
		
		$cart_hash          = md5( json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total );
		
		$order = wc_get_order( $curr_order_id );
		if( false === $order )
		{
			return $order_id;
		}
		
		$order->set_cart_hash( $cart_hash );
		$order->save();
		
		return $order_id;
	}

		/**
	 * Bugfix with Subscription plugin: we have to remove all our fees of existing order
	 * see https://github.com/woocommerce/woocommerce-subscriptions/pull/450#issuecomment-304106454
	 * 
	 * @since v3.0.3
	 * @param int $order_id
	 */
	public function handler_wc_generated_manual_renewal_order( $order_id ) 
	{
		$order = wc_get_order( $order_id );

		foreach ( $order->get_fees() as $fee ) 
		{
			$meta = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
			if( $meta == self::OPTIONNAME )
			{ 
				$order->remove_item( $fee->get_id() );
			}
		}

//		$order->save();
	}
	
	/**
	 * Bugfix with Subscription plugin: we have to remove all our fees of existing order
	 * 
	 * @since v3.0.3
	 * @param type $subscription
	 * @param WC_Abstract_Order $order
	 */
	public function handler_wc_subscription_renewal_payment_failed( $subscription, WC_Abstract_Order $order )
	{
		$this->handler_wc_generated_manual_renewal_order( $order->get_id() );
	}
	

	/**
	 * Bugfix with Subscription plugin: Remove our fees from order before building cart
	 *  
	 * @since v3.0.5
	 * @param WC_Abstract_Order $order
	 * @param WC_Cart $cart
	 */
	public function handler_wc_adjust_order_fees_for_subscription_initial_payment( WC_Abstract_Order $order, WC_Cart $cart )
	{
		foreach ( $order->get_fees() as $fee ) 
		{
			$meta = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
			if( $meta == self::OPTIONNAME )
			{ 
				$order->remove_item( $fee->get_id() );
			}
		}
	}
	
	/**
	 * Bugfix with Subscription plugin: Remove our fees from order before building cart
	 *  
	 * @since v3.0.5
	 * @param WC_Abstract_Order $order
	 * @param WC_Cart $cart
	 */
	public function handler_wc_adjust_order_fees_for_subscription_renewal( WC_Abstract_Order $order, WC_Cart $cart )
	{
		$this->handler_wc_adjust_order_fees_for_subscription_initial_payment( $order, $cart );
	}
	
	/**
	 * Bugfix with Subscription plugin: Remove our fees from order before building cart
	 *  
	 * @since v3.0.5
	 * @param WC_Abstract_Order $order
	 * @param WC_Cart $cart
	 */
	public function handler_wc_adjust_order_fees_for_subscription_resubscribe( WC_Abstract_Order $order, WC_Cart $cart )
	{
		$this->handler_wc_adjust_order_fees_for_subscription_initial_payment( $order, $cart );
	}
	
	
	/**
	 * Called before starting calculating fees. All additional fees for products are added to additional fees of cart
	 *
	 * @param WC_Cart $obj_wc_cart
	 */
	public function handler_wc_cart_calculate_fees( WC_Cart $obj_wc_cart )
	{
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return;
		}
		
		if( ! $this->request_data_loaded )	
		{
			$this->load_request_data();
		}
		

			//	loop through each product and add fee for each item in cart - takes care of cupons
		if ( sizeof( $obj_wc_cart->cart_contents ) > 0 )
		{
			foreach ( $obj_wc_cart->cart_contents as $cart_item_key => $values )
			{
				$_product = $values['data'];
				if( ! ( $_product instanceof WC_Product) ) 
				{
					continue;
				}
				
					//	allows to skip adding fees for a product by third party
				if( ! apply_filters( 'wc_add_fees_cart_before_add_product_fee', true, $_product, $obj_wc_cart ))
				{
					continue;
				}
				
				$total_excl = $values['line_total'];
				$tax = $values['line_tax'];
				$total_incl = $total_excl + $tax;
				
				$fees_calc = $this->calculate_gateway_fee_product( $_product, $obj_wc_cart->prices_include_tax, $total_excl, $total_incl, $values['quantity'] );

				if( ! empty( $fees_calc ) )
				{
					$this->add_fee_to_cart( $fees_calc, $obj_wc_cart );
				}	
			}
		}
		
	}


	/**
	 * Called before calculating final totals. As we need the complete calculated values of
	 * the cart we also have to alter the tax values.
	 *
	 * @param float $total 
	 * @param WC_Cart $obj_wc_cart
	 * @return float
	 */
	public function handler_wc_calculated_totals( $total, WC_Cart $obj_wc_cart )
	{
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return $total;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return $total;
		}

		if( ! $this->request_data_loaded)	
		{
			$this->load_request_data();
		}
		
				//	allows to skip adding total fees by third party
		if( ! apply_filters( 'wc_add_fees_cart_before_add_total_fee', true, $obj_wc_cart ))
		{
			return $total;
		}
		
		// Grand Total as calculated by WC - other plugins may have changed total value at this point:
		// 
		//	Discounted product prices, discounted tax, shipping cost + tax, and any discounts to be added after tax (e.g. store credit)
		$total_incl_tax = max( 0, round( $obj_wc_cart->cart_contents_total + $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total + $obj_wc_cart->shipping_total + $obj_wc_cart->fee_total, $obj_wc_cart->dp ) );

		//	tax_total includes tax of fees but not shipping tax, therefore add it
		$total_tax = round( $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total, $obj_wc_cart->dp );
		$total_excl_tax = round( $total_incl_tax - $total_tax, $obj_wc_cart->dp );
		
		$fee_total = $this->calculate_gateway_fee_total( $obj_wc_cart->prices_include_tax, $total_excl_tax, $total_incl_tax );
		if( ! isset( $fee_total ) )
		{
			return $total;
		}
		
		$this->add_fee_to_cart( $fee_total, $obj_wc_cart );
		
		$obj_wc_cart->fee_total += $fee_total->amount_no_tax;
		$fee_sum_tax = 0.0;
		
		if( $fee_total->taxable )
		{
			if( isset( $fee_total->tax_amount ) )
			{
				$obj_wc_cart->tax_total += $fee_total->tax_amount;
				$fee_sum_tax += $fee_total->tax_amount;
			}

			$taxes = isset( $fee_total->taxes) ? $fee_total->taxes : array();

					// Tax rows - merge the totals we just got
			
			if( version_compare( WC()->version, '3.2', '<' ) )
			{
				foreach ( array_keys( $obj_wc_cart->taxes + $taxes ) as $key ) 
				{
					$obj_wc_cart->taxes[ $key ] = ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 ) + ( isset( $obj_wc_cart->taxes[ $key ] ) ? $obj_wc_cart->taxes[ $key ] : 0 );
				}
			}
			else
			{
				$tx = $obj_wc_cart->get_cart_contents_taxes();
				foreach ( array_keys( $tx + $taxes ) as $key ) 
				{
					$tx[ $key ] = ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 ) + ( isset( $tx[ $key ] ) ? $tx[ $key ] : 0 );
				}
			
				$obj_wc_cart->set_cart_contents_taxes( $tx );
			}
		}		
		
		$total += $fee_total->amount_no_tax + $fee_sum_tax;
		
		/**
		 * Bug: Our added fees for products are added twice
		 * 
		 */
		if( class_exists( 'Woocommerce_German_Market' ) && version_compare( Woocommerce_German_Market::$version, '3.5', '<' ) )
		{
			$fees = $obj_wc_cart->get_fees();
			
			foreach( $fees as $key => $fee )
			{
				if( ! $fee->data_source instanceof WC_Fee_Add_Fees )
				{
					continue;
				}
				
				if( 0 !== stripos( $fee->id, 'ADD_FEE' ) )
				{
					continue;
				}
				
				if( false === stripos( $fee->id, 'ADD_FEE_TOTAL' ) )
				{
					$total -= $fee->data_source->tax_amount;
				}
			}
		}
		
		return $total;	
	}
	
	/**
	 * WCGM adds all fee taxes to total taxes - result is, that our fee taxes are added twice -> substract fee taxes
	 * 
	 * @param   array $taxes
	 * @param   WC_Cart $cart
	 *
	 * @return  array $taxes
	 */
	public function handler_wc_add_fee_to_cart_tax_totals( array $taxes, WC_Cart $cart )
	{
		if( ! class_exists( 'Woocommerce_German_Market') )
		{
			return $taxes;
		}
		
		if( version_compare( Woocommerce_German_Market::$version, '3.5', '>=' ) )
		{
			return $taxes;
		}
		
		if( WGM_Tax::is_kur() )
		{
			return $taxes;
		}
		
		$fees = $cart->get_fees();
		
			// looping through all fees in cart and subtract our fees
		foreach ( $fees as $key => $fee ) 
		{
			if( ! isset ( $fee->data_source ) || ( ! $fee->data_source instanceof WC_Fee_Add_Fees ) )
			{
				continue;
			}
					
			if ( ! empty( $fee->tax_data ) ) 
			{
					// if tax is not empty, loop through all taxes and add them to taxes array
				foreach ( $fee->tax_data as $rate_id => $tax ) {
					if ( !array_key_exists( $rate_id, $taxes ) ) {
						$taxes[ $rate_id ] = 0;
					}
					$taxes[ $rate_id ] -= $tax;
				}
			}
		}

		return $taxes;
	}
	
	/**
	 * WCGM adds all fee taxes to total taxes - result is, that our fee taxes are added twice -> substract fee taxes
	 * 
	 * @param   array $tax_totals
	 * @param   WC_Abstract_Order $order
	 *
	 * @return  array $taxes
	 */
	public function handler_wc_add_fee_to_order_get_tax_totals( array $tax_totals, WC_Abstract_Order $order )
	{
		if( ! class_exists( 'Woocommerce_German_Market') )
		{
			return $tax_totals;
		}
		
		if( version_compare( Woocommerce_German_Market::$version, '3.5', '>=' ) )
		{
			return $tax_totals;
		}
		
		if( WGM_Tax::is_kur() ){
			return $tax_totals;
		}
			//	bug: in this case the total taxes are correct
		$tax_is_ok = array(
				'woocommerce_calc_line_taxes',
				'woocommerce_save_order_items'
			);
		
		if( isset( $_REQUEST['action'] ) && ( in_array( $_REQUEST['action'], $tax_is_ok ) ) )
		{
			return $tax_totals;
		}

        $use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

        if( $use_split_tax == 'off' ){
            return $tax_totals;
        }
		
			// looping through all existing fees
		foreach( $order->get_fees() as $key => $fee ) 
		{
				//	bug: fees are added twice on update order from backend
			if( ! ( isset( $_REQUEST['action'] ) && ( 'edit' == $_REQUEST['action'] ) ) )
			{
						//	when saving order, our fees are added twice
				$meta = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
				if( $meta != self::OPTIONNAME )
				{
					continue;
				}
			}
			
			if( $fee instanceof WC_Order_Item_Fee )
			{
				$fee_id = $fee->get_id();
				$total = $fee->get_total();
			} 
			else if ( $fee && isset( $fee->id ) ) 
			{
				$fee_id = $fee->id;
				$total = $fee[ 'line_total' ];
			}
			else 
			{
				$fee_id = null;
				$total = 0;
			}
			
			$bypass_digital = FALSE;
//			if ( $fee_id == WGM_Fee::get_cod_fee_id() )
//				$bypass_digital = TRUE;

			$taxes = WGM_Tax::calculate_split_rate( $total, $order, $bypass_digital, $fee_id, 'fee' );

			// looping through all found taxes
			foreach( $taxes[ 'rates' ] as $rate_id => $item ) {

				// getting the unique rate_code
				$rate_code = WC_Tax::get_rate_code( $rate_id );

				if ( !array_key_exists( $rate_code, $tax_totals ) ) {
					continue;
				}

				// add the new amount to the current amount
				$new_amount                         = $tax_totals[ $rate_code ]->amount - $item[ 'sum' ];
				$tax_totals[ $rate_code ]->amount   = $new_amount;

				// create the new formatted amount
				$tax_totals[ $rate_code ]->formatted_amount = wc_price(
					wc_round_tax_total( $new_amount ),
					array('currency' => $order->get_currency() )
				);
			}
		}

		return $tax_totals;
	}
	
	/**
	 * WCGM removes all fees and adds them new -> we loose the reference to our fees and they are added again
	 * 
	 * @param int $order_id
	 */
	public function handler_wc_re_calculate_tax_on_save_order_items( $order_id )
	{
		if( ! class_exists( 'Woocommerce_German_Market') )
		{
			return;
		}
		
		/**
		 * German Market changed calculation of tax -> we need not take care of this any longer
		 */
		if( version_compare( Woocommerce_German_Market::$version, '3.5', '>=' ) )
		{
			return;
		}
		
			//	We have to remove this function and add our own copy of it
		remove_action( 'woocommerce_saved_order_items', array( 'WGM_Fee', 're_calculate_tax_on_save_order_items' ) );
		
			//	==================================================================
			//	modified code from WGM_Fee::re_calculate_tax_on_save_order_items
		
		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

        if( $use_split_tax == 'off' ){
            return;
        }

		$order = wc_get_order( $order_id );

			// getting all fees and remove them from order
		$all_fees = $order->get_fees();
		$order->remove_order_items( 'fee' );

		// loop through all fees and create new ones with the split tax
		foreach ( $all_fees as $key => $fee ) 
		{
			if ( ! apply_filters( 'woocommerce_de_show_gateway_fees_tax', true, $fee ) ) {
				continue;
			}
			
			$bypass_digital = FALSE;
			//if ( $fee->id == WGM_Fee::get_cod_fee_id() )
			//	$bypass_digital = TRUE;
			
			if( $fee instanceof WC_Order_Item_Fee )
			{
				$fee_id = $fee->get_id();
				$total = $fee->get_total();
			} 
			else if ( $fee && isset( $fee->id ) ) 
			{
				$fee_id = $fee->id;
				$total = $fee[ 'line_total' ];
			}
			else 
			{
				$fee_id = null;
				$total = 0;
			}
			
			$taxes = WGM_Tax::calculate_split_rate( $total, $order, $bypass_digital, $fee_id, 'fee' );
			
			$new_fee = new WC_Order_Item_Fee();
			$new_fee->set_props( array(
								'name'			=> $fee->get_name(),
								'tax_status'	=>	'taxable',
								'tax_class'		=> $fee->get_tax_class(),
								'total'			=> $fee->get_total(),
								'total_tax'		=> $taxes[ 'sum' ],
				//				'taxes'     => array(
				//					'total' => $fee->tax_data,
//								),
								'order_id'		=> $fee->get_id(),
							) );
				
			$new_fee->set_taxes( $taxes );
			
			$meta = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
			if( $meta == self::OPTIONNAME )
			{
				$new_fee->add_meta_data( '_added_by', self::OPTIONNAME, true );
			}
				
			$new_fee->save();
			$order->add_item( $new_fee );
		}
		
	}
	
	/**
	 * Called before pay for order form is created.
	 * 
	 * Fixes bug from WC Core, that payment gateway is set to default gateway and not to order gateway (by js code)
	 * Saves order ID to allow recalculating of fees when payment gateway changes via ajax
	 * 
	 */
	public function handler_wc_before_pay()
	{
		global $wp;
		
		if( empty( $wp->query_vars['order-pay'] ) ) 
		{
			return;
		}
		
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return;
		}

		if( ! $this->request_data_loaded )	
		{
			$this->load_request_data();
		}
				
		$order_id = absint( $wp->query_vars['order-pay'] );
		
		// Pay for existing order only
		if ( ! ( isset( $_REQUEST['pay_for_order'] ) && isset( $_REQUEST['key'] ) && $order_id ) ) 
		{
			return;
		}
		
		$order = wc_get_order( $order_id );
		
		$payment_method = ! empty( $order->get_payment_method() ) ? $order->get_payment_method() : $this->default_payment_gateway_key;
		$pm = WC_Add_Fees::get_post_meta_order_default( $order_id );
		
		$pay_for_order = $_REQUEST['pay_for_order'];
		$key = $_REQUEST['key'];
		
		$info = 'id="add_fee_info_pay" ';
		$info .= 'add_fee_action="add_fee_calc_fee_pay_order" ';
		$info .= 'add_fee_order="' . esc_attr( $order_id ) . '" ';
		$info .= 'add_fee_pay="' . esc_attr( $pay_for_order ) . '" ';
		$info .= 'add_fee_paymethod="' . esc_attr( $payment_method ) . '" ';
		$info .= 'add_fee_key="' . esc_attr( $key ) . '" ';
		$info .= 'add_fee_fixed_gateway="' . esc_attr( $pm[WC_Add_Fees::OPT_FIXED_GATEWAY] ) . '" ';
		
		echo '<div ';
			echo $info;
		echo ' style="display: none;">';
		echo '</div>';
		return;
	}
	
	/**
	 * Called, when an existing order is updated from cart. All items are deleted and
	 * later refilled. Therefore any reference to our fees must be removed and will be
	 * restored later.
	 * 
	 * @param int $order_id
	 */
	public function handler_wc_resume_order( $order_id )
	{	
		delete_post_meta( $order_id, self::KEY_POSTMETA_ORDER );
	}
	
	/**
	 * Add our data to fee item
	 * 
	 * @since 3.0.0
	 * @param WC_Order_Item_Fee $item
	 * @param string $fee_key
	 * @param stdClass $fee
	 * @param WC_Abstract_Order $order
	 * @return WC_Order_Item_Fee
	 */
	public function handler_wc_checkout_create_order_fee_item( WC_Order_Item_Fee $item, $fee_key, stdClass $fee, WC_Abstract_Order $order )
	{
		if( empty( $fee->data_source) ) 
		{
			return;
		}
		
		if( ! $fee->data_source instanceof WC_Fee_Add_Fees ) 
		{
			return;
		}
		
		if( $fee->data_source->source != self::OPTIONNAME ) 
		{
			return;
		}
		
		$item->add_meta_data( '_added_by', self::OPTIONNAME, true );
		
		return;
	}

	/**
	 * Called from pay for order page, recalculates the fees for the order, updates the order and reloads
	 * the new order data
	 * 
	 */
	public function handler_ajax_calc_fee_pay_order()
	{
		check_ajax_referer( self::AJAX_NONCE, self::AJAX_NONCE );
		
			// response output
		header( "Content-Type: application/json" );
		$response = array( self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE ) );
		
		$response ['alert'] = __( 'An error occured in calculation of fees for your selected payment gateway. Kindly contact us to recheck your invoice or try to use another payment gateway. ', self::TEXT_DOMAIN );
		$response ['recalc'] = true;
		
		$error_div = '<div id="addfeeerror" style="color: red; font-size: 3em; line-height: 1.2;">';
		
		$order_id = isset( $_REQUEST['add_fee_order'] ) ? absint( $_REQUEST['add_fee_order'] ) : 0;
		$pay_for_order = $_REQUEST[ 'add_fee_pay' ];
		$order_key = $_REQUEST[ 'add_fee_key' ];
		$add_fee_new_paymethod = $_REQUEST[ 'add_fee_paymethod' ];		
		
			// Check for handle payment
		if ( ! ( isset( $_REQUEST['add_fee_pay'] ) && isset( $_REQUEST['add_fee_key'] ) && isset ( $_REQUEST[ 'add_fee_paymethod' ] ) && $order_id ) ) 
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid pay order parameters. ', self::TEXT_DOMAIN ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', self::TEXT_DOMAIN ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
		
		//	skip, if all disabled globally
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			$response ['success'] = true;
			$response ['recalc'] = false;
			echo json_encode( $response );
			exit;
		}
		
		$pm = self::get_post_meta_order_default( $order_id );
		if( $pm[WC_Add_Fees::OPT_ENABLE_RECALC] != 'yes' )
		{
			$response ['success'] = true;
			$response ['recalc'] = false;
			echo json_encode( $response );
			exit;
		}	
		
		$add_fee_order = new WC_Order_Add_Fees( $order_id );
		$order = $add_fee_order->order;
		
		if( ! $this->request_data_loaded)	
		{
			$this->load_request_data( $add_fee_new_paymethod );
		}
		
		if( ! isset( $this->gateways[$this->payment_gateway_key] ) )
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid payment gateway selected - it is no longer available. ', self::TEXT_DOMAIN ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', self::TEXT_DOMAIN ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
			
			// Pay for existing order		
		$allowed = array( 'pending', 'failed' );
		$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', $allowed, $order );
		
		/**
		 * Fix to allow 3-rd party to add additional valid order status not returnd correctly by woocommerce_valid_order_statuses_for_payment
		 * 
		 * @since 3.1.7
		 * @param WC_Order $order
		 * @param array
		 * @return array
		 */
		$valid_order_statuses = apply_filters( 'wc_add_fees_pay_for_order__valid_order_statuses', $valid_order_statuses, $order );
		
		if ( ! current_user_can( 'pay_for_order', $order_id ) ) 
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid order. ', self::TEXT_DOMAIN ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', self::TEXT_DOMAIN ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
		
		//	output order using WC default template checkout/form-pay.php
		ob_start();
		$template_loaded = true;
		
		if ( $order->get_id() == $order_id ) 
		{
			$order_status = $order->get_status();
			
			/**
			 * Fix for 3-rd party to force continue of calculation regardless of order status - add a class to check for
			 *		- subscription plugin
			 * 
			 * @since 3.1.7
			 * @param array
			 * @param WC_Order $order
			 * @return array
			 */
			$force_continue_classes = apply_filters( 'wc_add_fees_pay_for_order_force_continue_classes', array( 'WCS_Autoloader' ), $order );
			$force_continue = false;
			
			foreach( $force_continue_classes as $force_continue_class )
			{
				if( class_exists( $force_continue_class ) )
				{
					$force_continue = true;
					break;
				}
			}
				
			/**
			 * Fallback fix for 3-rd party to force continue of calculation
			 * 
			 * @since 3.1.7
			 * @param boolean
			 * @param WC_Order $order
			 * @return boolean
			 */
			$force_continue = apply_filters( 'wc_add_fees_pay_for_order_force_continue', $force_continue, $order );
			
			if( in_array( $order_status, $valid_order_statuses ) || $force_continue )
			{
				// Set customer location to order location
				if ( ! empty( $order->get_billing_country() ) )
				{
					WC()->customer->set_billing_country( $order->get_billing_country() );
				}
				if (  ! empty( $order->get_billing_state() ) )
				{
					WC()->customer->set_billing_state( $order->get_billing_state() );
				}
				if (  ! empty( $order->get_billing_postcode() ) )
				{
					WC()->customer->set_billing_postcode( $order->get_billing_postcode() );
				}
				
				/**
				 * For compatibility with EU-VAT-Number plugin we have to check VAT Number from order
				 * 
				 * We do not check for valididity of EU-VAT-Number with post meta values
				 * see class WC_Customer_Add_Fees
				 */
				$vat_number = $order->meta_exists( '_vat_number' ) ? $order->get_meta( '_vat_number', true ) : '';
				WC()->customer->set_is_vat_exempt( ( ! empty( $vat_number ) ) ? true : false );
				$order->is_vat_exempt = ( ! empty( $vat_number ) ) ? 'yes' : 'no';
			
					//	maipulate order recalculating fee based on new payment gateway
				$this->calculate_gateway_fees_order( $order_id, $add_fee_order );
				
				$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
				if ( sizeof( $available_gateways ) ) 
				{
					current( $available_gateways )->set_current();
				}
				
				wc_get_template( 'checkout/form-pay.php', array(
										'order'              => $order,
										'available_gateways' => $available_gateways,
										'order_button_text'  => apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', self::TEXT_DOMAIN ) )
							) );
			} 
			else 
			{
				$template_loaded = false;
				$curr_status = $order->get_status();
				
				$status_defined = wc_get_order_statuses();
				$status_print = ' ( ' . implode( ', ', array_keys( $status_defined ) ) . ') ';
				$status = isset( $status_defined[ $curr_status ] ) ? $status_defined[ $curr_status ] : __( 'unknown status: ', self::TEXT_DOMAIN ) . $curr_status . $status_print;
				
				wc_add_notice( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance. ', self::TEXT_DOMAIN ), $status ), 'error' );
			}
		} 
		else 
		{
			$template_loaded = false;
			wc_add_notice( __( 'Sorry, this order is invalid and cannot be paid for. ', self::TEXT_DOMAIN ), 'error' );
		}
		
		if( ! $template_loaded )
		{
			wc_print_notices();
		}
		
		$buffer = ob_get_contents();
		ob_end_clean();
		
		//	remove parts of template not needed
		if( $template_loaded )
		{
			$buffer = $this->extract_order_template( $buffer );
		}
		else
		{
			$buffer = $error_div . $buffer . '</div>';
		}
		
		$response ['success'] = $template_loaded;
		$response ['message'] = $buffer;
		
		echo json_encode( $response );
		exit;
	}

	/**
	 * Calculates the fee for a given value. Takes care of tax calculation.
	 *
	 * @param boolean	$cart_includes_tax			//	cart->->prices_include_tax
	 * @param float		$value						//	total cart value including tax !!
	 * @param array		$gateway
	 * @param int		$quantity
	 * @param array		$tax_rates_base				//	added with 2.2 for recalculating orders
	 * @return WC_Fee_Add_Fees|null
	 */
	protected function calculate_fees( $cart_includes_tax, $value, array $gateway, $quantity = 1, $tax_rates_base = array() )
	{
		if( abs( $value ) < 0.01 )
		{
			return null;
		}
		
		//	global settings for prices
		$prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'yes' ) == 'yes';
		
			//	get tax rates
		$taxclass = ( $gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD) ? '' : $gateway[self::OPT_KEY_TAXCLASS];
//		$tax_rates = $obj_wc_tax->get_rates( $taxclass );
		
		if( empty( $tax_rates_base ) )
		{
			$tax_rates = WC_Tax::get_rates( $taxclass );
		}
		else
		{
			$tax_rates_base['tax_class'] = $taxclass;
			$tax_rates = WC_Tax::find_rates( $tax_rates_base );
		}
		
		$no_tax = false;
		if( ( $gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_NONE ) || WC()->customer->is_vat_exempt() || ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) )
		{
			$no_tax = true;
		}

		$tax_included = true;

		$add_fee = apply_filters( 'wc_add_fees_gateway_fee', (float) $gateway[self::OPT_KEY_VALUE_TO_ADD], $gateway, $value );
		$add_fee_fixed = apply_filters( 'wc_add_fees_gateway_fee_fixed', (float) $gateway[self::OPT_KEY_VALUE_TO_ADD_FIXED], $gateway, $value );
		$add_fee_minimum = apply_filters( 'wc_add_fees_gateway_fee_minimum', (float) $gateway['minimum_fee'], $gateway, $value );
		
		$add_fee_fixed_no_tax = $add_fee_fixed;
		$add_fee_fixed_tax = $add_fee_fixed;
		$tax_amount_fixed = 0.0;
		$taxes_add_fixed = array();
		
				//	calculate $add_fee_fixed amount according to setting: Prices entered w/o tax
		if( $add_fee_fixed > 0.0 )
		{
			$taxes_add_fixed = WC_Tax::calc_tax( $add_fee_fixed, $tax_rates, $prices_include_tax );
			$tax_amount_fixed = WC_Tax::get_tax_total( $taxes_add_fixed );
			
			if( ! $this->round_at_subtotal )
			{
				$tax_amount_fixed = round( $tax_amount_fixed, $this->dp );
			}
			
			if( $prices_include_tax )
			{
				$add_fee_fixed_no_tax = $add_fee_fixed - $tax_amount_fixed;
			}
			else 
			{
				$add_fee_fixed_no_tax = $add_fee_fixed;
			}
				//	reset tax amount to our custom settings
			if ( $no_tax )
			{
				$tax_amount_fixed = 0.0;
			}
			
			$add_fee_fixed_tax = $add_fee_fixed_no_tax + $tax_amount_fixed;
			
			/**
			 * Add a filter to add fixed amout prior to calculating the other fees
			 * 
			 * @since 3.0.8
			 * @return string	'yes'|'no'
			 */
			$add_fee_before = $gateway[WC_Add_Fees::OPT_KEY_FIXED_VALUE_POS] != 'before' ? 'no' : 'yes';
			if( 'no' != apply_filters( 'wc_add_fees_fixed_fee_before_normal_fee', $add_fee_before, $cart_includes_tax, $value, $gateway, $quantity, $tax_rates_base ) )
			{
				//	$value is inclusive tax !!
				$value += $add_fee_fixed_tax;
			}
		}
		else
		{
			$add_fee_fixed_no_tax = $add_fee_fixed_tax = $tax_amount_fixed = 0.0;
		}
		
		
		$add_fee_minimum_no_tax = $add_fee_minimum;
		$add_fee_minimum_tax = $add_fee_minimum;
		$tax_amount_minimum = 0.0;
		$taxes_add_minimum = array();
		
		//	calculate $add_fee_fixed amount according to setting: Prices entered w/o tax
		if( $add_fee_minimum > 0.0 )
		{
			$taxes_add_minimum = WC_Tax::calc_tax( $add_fee_minimum, $tax_rates, $prices_include_tax );
			$tax_amount_minimum = WC_Tax::get_tax_total( $taxes_add_minimum );
			
			if( ! $this->round_at_subtotal )
			{
				$tax_amount_minimum = round( $tax_amount_minimum, $this->dp );
			}
			
			if( $prices_include_tax )
			{
				$add_fee_minimum_no_tax = $add_fee_minimum - $tax_amount_minimum;
			}
			else 
			{
				$add_fee_minimum_no_tax = $add_fee_minimum;
			}
				//	reset tax amount to our custom settings
			if ( $no_tax )
			{
				$tax_amount_minimum = 0.0;
			}
			
			$add_fee_minimum_tax = $add_fee_minimum_no_tax + $tax_amount_minimum;
		}
		else
		{
			$add_fee_minimum_no_tax = $add_fee_minimum_tax = $tax_amount_minimum = 0.0;
		}
		
		
		switch ( $gateway[self::OPT_KEY_ADD_VALUE_TYPE] )
		{
			case self::VAL_FIXED:
				$add_fee *= $quantity;
				$tax_included = $prices_include_tax;
				break;
			case self::VAL_INCLUDE_PERCENT:
				if( ! $no_tax )
				{			//	include tax in percents to add
//					$add_fee_taxs = $obj_wc_tax->calc_tax( $add_fee, $tax_rates, false );
//					$add_fee += $obj_wc_tax->get_tax_total( $add_fee_taxs );
					$add_fee_taxs = WC_Tax::calc_tax( $add_fee, $tax_rates, false );
					$add_fee += WC_Tax::get_tax_total( $add_fee_taxs );
				}
				$add_fee = ( ( $value * 100.0) / ( 100.0 - $add_fee ) ) - $value;
				$tax_included = false;
				break;
			case self::VAL_ADD_PERCENT:
				$add_fee = ( $value * $add_fee ) / 100.0;
				$tax_included = false;
				break;
			default:
				$add_fee = 0.0;
				break;
		}
		$add_fee = round( $add_fee, $this->dp );
		
		/**
		 * Check for a minimum fee
		 */
		if( $add_fee_minimum > 0.0 )
		{
			if( $tax_included )
			{
				if( $add_fee < $add_fee_minimum_tax )
				{
					$add_fee = $add_fee_minimum_tax;
				}
			}
			else
			{
				if( $add_fee < $add_fee_minimum_no_tax )
				{
					$add_fee = $add_fee_minimum_no_tax;
				}
			}
		}
		
			//	calculate tax amount - for saving taxes object (rounding depends on $this->round_at_subtotal)
		$taxes = WC_Tax::calc_tax( $add_fee, $tax_rates, $tax_included );
		$tax_amount = WC_Tax::get_tax_total( $taxes );

		if( ! $this->round_at_subtotal )
		{
			$tax_amount = round( $tax_amount, $this->dp );
		}
		
		/**
		 * Allow to supress removing fixed fees when fees = 0
		 */
		if( ( 0 == $add_fee ) && ( 'remove' == apply_filters( 'wc_add_fees_remove_fixed_fees_on_no_fees', 'remove' ) ) )
		{
			$add_fee_fixed_no_tax = 0.0;
			$add_fee_fixed_tax = 0.0;
			$tax_amount_fixed = 0.0;
			$taxes_add_fixed = array();
		}

			//	calculate add_fee with and without tax
		switch ( $gateway[self::OPT_KEY_ADD_VALUE_TYPE] )
		{
			case self::VAL_FIXED:
			case self::VAL_ADD_PERCENT:
			case self::VAL_INCLUDE_PERCENT:
				if( $tax_included )
				{
					$fee_tax = $add_fee + $add_fee_fixed_tax;
					$fee_no_tax = $fee_tax - $tax_amount - $tax_amount_fixed;
				}
				else
				{
					$fee_no_tax = $add_fee + $add_fee_fixed_no_tax;
					$fee_tax = $fee_no_tax + $tax_amount + $tax_amount_fixed;
				}
				$tax_amount += $tax_amount_fixed;
				
					// Tax rows - merge the totals we just got
				foreach ( array_keys( $taxes + $taxes_add_fixed ) as $key ) 
				{
					$taxes[ $key ] = ( isset( $taxes_add_fixed[ $key ] ) ? $taxes_add_fixed[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
				}
				
				break;
//			case self::VAL_INCLUDE_PERCENT:
//				$fee_tax = $add_fee;
//				$fee_no_tax = ( $no_tax) ? $fee_tax : $fee_tax - $tax_amount;
//				break;
			default:
				$tax_amount = $fee_no_tax = $fee_tax = 0.0;
				$taxes = array();
				break;
		}
		
		/**
		 * Reset tax if not applicable
		 */
		if( $no_tax )
		{
			$tax_amount = 0.0;
			$fee_tax = $fee_no_tax;
			$taxes = array();
		}

		$calc_fee = new WC_Fee_Add_Fees();
		$calc_fee->amount_no_tax = $fee_no_tax;
		$calc_fee->amount_incl_tax = $fee_tax;
		$calc_fee->tax_amount = $tax_amount;
		$calc_fee->taxable = ( ! $no_tax );
		$calc_fee->taxes = $taxes;

		return $calc_fee;
	}

	/**
	 * Adds the fee to the cart fee array and also stores the additional information there.
	 * If required, also adds tax and sum values (only on cart checkout)
	 *
	 * @param WC_Fee_Add_Fees $fee
	 * @param WC_Cart $obj_wc_cart
	 */
	protected function add_fee_to_cart( WC_Fee_Add_Fees &$fee, WC_Cart $obj_wc_cart )
	{
		/**
		 * Manipulate displayed fee output text (e.g.allow to translate)
		 * Handles total cart and product level output text
		 * 
		 * @used_by WC_Add_Fees_WPML				10
		 * @since 3.1.7
		 * @param string
		 * @param WC_Fee_Add_Fees $fee
		 * @return string
		 */
		$name = apply_filters( 'wc_add_fees_fee_output_text', $fee->gateway_option[WC_Add_Fees::OPT_KEY_OUTPUT], $fee );
		
		//	add fee
		$amount = $fee->amount_no_tax;
		$taxable = $fee->taxable;
		$tax_class = $fee->gateway_option[WC_Add_Fees::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD ?  '' : $fee->gateway_option[WC_Add_Fees::OPT_KEY_TAXCLASS];

		/**
		 * WC introduced class WC_Cart_Fees - for backwards compatibility
		 */
		if( version_compare ( WC()->version, '3.2', '<' ) )
		{
			$obj_wc_cart->add_fee( $fee->id, $amount, $taxable, $tax_class );
			$fee_cart = &$obj_wc_cart->fees[ count( $obj_wc_cart->fees ) - 1 ];

			$fee_cart->tax_data = $fee->taxes;
			$fee_cart->tax = $fee->tax_amount;
			$fee_cart->name = $name;

					//	save source information for a possible chance to display later (maybe in order) to reconstruct calculation
			$fee_cart->data_source = $fee;
			
			return;
		}
			
		$fees_api = $obj_wc_cart->fees_api();
		
		$fee_val = array(
						'id'        => $fee->id,
						'name'      => $name,
						'tax_class' => $tax_class,
						'taxable'   => $taxable,
						'amount'    => $amount,
						'total'     => $amount,
					);
		
		$new_fee = $fees_api->add_fee( $fee_val );
		
		if( ! ( $new_fee instanceof WP_Error ) )
		{
			//	save source information for a possible chance to display later (maybe in order) to reconstruct calculation
			$new_fee->data_source = $fee;
			$new_fee->tax_data = $fee->taxes;
			$new_fee->tax = $fee->tax_amount;
		}
	}


	/**
	 * Initialise values that need translation and WooCommerce
	 *
	 */
	public function init_values()
	{
		if( ! isset( self::$value_type_to_add) || empty( self::$value_type_to_add ) )
		{
			self::$value_type_to_add = array(
					self::VAL_FIXED				=> __( 'Fixed amount', WC_Add_Fees::TEXT_DOMAIN ),
					self::VAL_ADD_PERCENT		=> __( 'add % to total amount', WC_Add_Fees::TEXT_DOMAIN ),
					self::VAL_INCLUDE_PERCENT	=> __( 'include % in total amount', WC_Add_Fees::TEXT_DOMAIN )
				);
		}
		
		if( ! isset( self::$order_add_fixed_value) || empty( self::$order_add_fixed_value ) )
		{
			self::$order_add_fixed_value = array(
					'after'		=>	__( 'Add value to add after calculating fee from total', WC_Add_Fees::TEXT_DOMAIN ),
					'before'	=>	__( 'Add value to add to total before calculating fee', WC_Add_Fees::TEXT_DOMAIN )
				);
		}
		
		if( empty( $this->gateways ) )
		{
			$this->gateways = WC()->payment_gateways->payment_gateways();
					//	set default gateway
			if( isset( $this->gateways[ get_option( 'woocommerce_default_gateway' ) ] ) )
			{
				$default = $this->gateways[ get_option( 'woocommerce_default_gateway' ) ];
			}
			else
			{
				reset( $this->gateways );
				$default = current( $this->gateways );
			}
			
			$this->default_payment_gateway_key = $default->id;
			$this->payment_gateway_key = $this->default_payment_gateway_key;
		}

		if( empty( $this->tax_classes ) )
		{
			$this->tax_classes = array();
			$this->tax_classes[self::VAL_TAX_NONE] = __( 'No Tax required', WC_Add_Fees::TEXT_DOMAIN );
			$this->tax_classes[self::VAL_TAX_STANDARD] = __( 'Standard', WC_Add_Fees::TEXT_DOMAIN );
				
			$version = ( function_exists( 'WC' ) ) ? WC()->version : '3.6.9';
			
			/**
			 * Backwards comp.
			 */
			if( version_compare( $version, '3.7', '<' ) )
			{
				$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
				
				if ( $tax_classes )
				{
					foreach ( $tax_classes as $class )
					{
	//					$this->tax_classes[ sanitize_title( $class) ] = $class;
						$this->tax_classes[ $class ] = $class;
					}
				}
			}
			else 
			{			
				$tax_class_slugs = WC_Tax::get_tax_class_slugs();
				
				foreach ( $tax_class_slugs as $tax_class_slug )
				{
					$tax_class = WC_Tax::get_tax_class_by( 'slug', $tax_class_slug );
					$this->tax_classes[ $tax_class['slug'] ] = $tax_class['name'];
				}
			}
		}

		$this->options = self::get_options_default();
		
			//	allow to add other plugins
		$this->gateway_bugfix_array = apply_filters( 'wc_add_fees_bugfix_array', $this->gateway_bugfix_array );
		
		foreach( $this->gateway_bugfix_array as $plugin )
		{
			if ( ! function_exists( 'is_plugin_active' ) ) 
			{
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if( is_plugin_active( $plugin ) )
			{
				$this->gateway_bugfix = true;
			}
		}

			//	allow other classes to access new wc data
		do_action( 'woocommerce_additional_fees_init' );
	}
	
	/**
	 * Loads the Request and Session data. If $posted_payment_gateway is set, uses this as gateway else
	 * takes session data gateway ( in cart only ) . Initialises the gateway and
	 * implements a fallback for option array.
	 * 
	 * @param string $posted_payment_gateway
	 */
	public function load_request_data( $posted_payment_gateway = '' )
	{
		$this->init_values();

		if( empty( $posted_payment_gateway ) )
		{
			$posted_payment_gateway = WC()->session->chosen_payment_method;
			
				//	possible fix, if calculate_... has been called before session data has been initialised
			if( function_exists( 'is_checkout' ) && is_checkout() && defined( 'WOOCOMMERCE_CHECKOUT' ) )
			{
				if( isset( $_REQUEST['payment_method'] ) )
				{
					$posted_payment_gateway = $_REQUEST['payment_method'];
				}
			}
			
		}
		
		$available_gateways = array();

		
		//	Bug in WC Gateway COD -> checks for function WC_Gateway_COD->needs_shipping - does not exist in order page and pay-for order
		//	Bug in woocommerce-account-funds: get_available_payment_gateways() produces endless loop due to calculate totals
		//			if statement was removed in 2.1.5
		//			
		//	In version 2.2.2 integrated $this->gateway_bugfix_array allows to filter plugins, that do not support get_available_payment_gateways()
		//	        if statement activated again
		if( isset( WC()->cart ) && ( ! $this->gateway_bugfix ) )
		{
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			
			/**
			 * Fix for WooCommerce PayPal Express Checkout Gateway
			 * Removes the gateway on checkout page (probably because we call this function to early. This avoids calculation of fees if selected
			 * 
			 * @since 3.1.1
			 */
			$gateways = WC()->payment_gateways()->payment_gateways();
			if( isset( $gateways['ppec_paypal'] ) && ( 'yes' == $gateways['ppec_paypal']->enabled ) && ! isset( $available_gateways['ppec_paypal'] ) )
			{
				$available_gateways['ppec_paypal'] = $gateways['ppec_paypal'];
			}
		}
		else
		{
					//	take all gateways that are enabled directly
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway )
			{
				if( 'yes' === $gateway->enabled )
				{
					$available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}
		
        if ( ! empty( $available_gateways ) )
        {
			if( in_array( $posted_payment_gateway, array( 'other', '' ) ) )
			{
				$this->payment_gateway_key = $posted_payment_gateway;
			}
            else if ( ! empty( $posted_payment_gateway ) && isset( $available_gateways[ $posted_payment_gateway ] ) )
            {
                $this->payment_gateway_key = $available_gateways[ $posted_payment_gateway ]->id;
            }
            else if( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) )
            {
                $this->payment_gateway_key = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ]->id;
            }
            else
            {
                $this->payment_gateway_key = current( $available_gateways )->id;
            }
        }

		if( ! empty( $this->payment_gateway_key ) && isset( $this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] ) )
		{
			$payment_gateway_option = $this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ];
		}
		else
		{
			$payment_gateway_option = array();
		}
		
		$desc = in_array( $this->payment_gateway_key, array( 'other', '' ) ) ? $this->payment_gateway_key : '';
		$this->payment_gateway_option = self::get_option_gateway_default( $payment_gateway_option, $desc );

		if( serialize( $this->payment_gateway_option ) != serialize( $payment_gateway_option ) )
		{				//	save option
			$this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] = $this->payment_gateway_option;
			update_option( self::OPTIONNAME, $this->options );
		}

		$this->request_data_loaded = true;
	}
	
	/**
	 * Calculates the fee for the total
	 * 
	 * @param boolean $includes_tax					$obj_wc_cart->prices_include_tax
	 * @param float $total_excl
	 * @param float $total_incl
	 * @param array $tax_rates_base   added with 2.2 for recalculation of order
	 * @return WC_Fee_Add_Fees|null
	 */
	protected function calculate_gateway_fee_total ( $includes_tax, $total_excl, $total_incl, $tax_rates_base = array() )
	{
		$fees_calc = null;
		
		//	if add fees for gateway is disabled
		if( ( false === $this->payment_gateway_option[self::OPT_KEY_ENABLE] )  )
		{
			return $fees_calc;
		}
		
		//	global settings for prices
		$prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'yes' ) == 'yes';
		
		$maxval = isset( $this->payment_gateway_option[self::OPT_KEY_MAX_VALUE] ) ? $this->payment_gateway_option[self::OPT_KEY_MAX_VALUE] : 0.0;
		
		/**
		 * Allow to alter maxval depending on cart
		 */
		$maxval = apply_filters( 'wc_add_fees_maximum_cart_order_value', $maxval, $total_excl, $total_incl, $tax_rates_base );
		$maxval = is_numeric( $maxval ) ? (float) $maxval : 0.0;
		
		if( $maxval > 0.0 )
		{
			$check_total = ( $prices_include_tax ) ? $total_incl : $total_excl;
			
			if( $check_total >= $maxval )
			{
				return $fees_calc;
			}
		}
		
		$minval = isset( $this->payment_gateway_option['minvalue'] ) ? $this->payment_gateway_option['minvalue'] : 0.0;
		
		/**
		 * Allow to alter maxval depending on cart
		 */
		$minval = apply_filters( 'wc_add_fees_minimum_cart_order_value', $minval, $total_excl, $total_incl, $tax_rates_base );
		$minval = is_numeric( $minval ) ? (float) $minval : 0.0;
		
		if( $minval > 0.0 )
		{
			$check_total = ( $prices_include_tax ) ? $total_incl : $total_excl;
			
			if( $check_total <= $minval )
			{
				return $fees_calc;
			}
		}
		
		//changed with 2.1.0 - replaced $total_excl with $total_incl
		$fees_calc = $this->calculate_fees( $includes_tax, $total_incl, $this->payment_gateway_option, 1, $tax_rates_base );
		
		/**
		 * Allows to filter and alter calculated fee
		 * 
		 * @since 2.2.21
		 */
		$fees_calc = apply_filters( 'wc_add_fees_calculated_fee', $fees_calc, 'total', $includes_tax, $total_incl, $this->payment_gateway_option, 1, $tax_rates_base );

		if( is_null( $fees_calc ) || ! $fees_calc instanceof WC_Fee_Add_Fees )
		{
			$fees_calc = null;
			return $fees_calc;
		}
		
		if( $fees_calc->amount_incl_tax < 0.01 )
		{
			return $fees_calc;
		}
		
		$fees_calc->id = substr( ( 'ADD_FEE_TOTAL' ), 0, 15 );		
		$fees_calc->source = self::OPTIONNAME;
		$fees_calc->type = WC_Fee_Add_Fees::VAL_TOTAL_CART_ADD_FEE;
		$fees_calc->gateway_key = $this->payment_gateway_key;
		$fees_calc->gateway_title = $this->gateways[ $this->payment_gateway_key ]->title;
		$fees_calc->gateway_option = $this->payment_gateway_option;
		
		return $fees_calc;
	}

	/**
	 * Recalculates the fees for a stored order based on the payment gateway set in local payment gateway members
	 * 
	 * @param int $order_id
	 * @param WC_Order_Add_Fees $add_fee_order
	 * @param boolean $ignore_recalc_option 
	 * @return boolean true, if recalculation was done
	 */
	public function calculate_gateway_fees_order( $order_id, WC_Order_Add_Fees &$add_fee_order, $ignore_recalc_option = false )
	{
		$order = $add_fee_order->order;
		$pm = self::get_post_meta_order_default( $order_id );
		
		if( ! $ignore_recalc_option )
		{
			if ( $pm[ WC_Add_Fees::OPT_ENABLE_RECALC ] != 'yes' ) 
			{
				return false;
			}
		}
		
			//	backward comp. for orders saved prior to 3.0.0
		$fees = $pm[ self::OPT_KEY_FEE_ITEMS ];
		if( empty( $fees ) )
		{
			$fees = $order->get_fees();
		}
		
		/**
		 * Since WC 2.2 refunds are possible. 
		 * 
		 * Since we delete our fee lines for recalc we loose the context to manually added refund(s) .
		 * WC does not support 'pay for order' with refunds -> so we can skip recalc without problem.
		 * On order page we have to disable checkbox and give the admin a warning in the box
		 */
		
		$total = 0.0;
		foreach ( $fees as $item_key => $fee ) 
		{
			$source = '';
			if( $fee instanceof WC_Order_Item_Fee )
			{
				$source = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
			}
			else
			{
				$source = $fee->source;
			}

			if( $source == self::OPTIONNAME )
			{
				$total += $order->get_total_refunded_for_item( $item_key, 'fee' );
			}
		}

		if( $total != 0 )
		{
			return false;
		}
		
		
			//	remove all fee entries from our plugin from order and from post meta and save
		foreach ( $fees as $item_key => $fee ) 
		{
			$source = '';
			if( $fee instanceof WC_Order_Item_Fee )
			{
				$source = $fee->meta_exists( '_added_by' ) ? $fee->get_meta( '_added_by', true ) : '';
			}
			else	//	for orders saved prior to 3.0.0
			{
				$source = $fee->source;
			}
			
			if( $source == self::OPTIONNAME )
			{
				$order->remove_item( $item_key );
			}
		}
				//	clear fees - used prior to v3.0.0
		$pm[ self::OPT_KEY_FEE_ITEMS ] = array();
		update_post_meta( $order_id, self::KEY_POSTMETA_ORDER, $pm );
		
		$items = $order->get_items();
		
		$taxes              = array();
		$tax_based_on       = get_option( 'woocommerce_tax_based_on' );

		if ( 'base' === $tax_based_on ) 
		{
			$default  = get_option( 'woocommerce_default_country' );
			$postcode = '';
			$city     = '';

			if ( strstr( $default, ':' ) ) 
			{
				list( $country, $state ) = explode( ':', $default );
			} 
			else 
			{
				$country = $default;
				$state   = '';
			}
		} 
		elseif ( 'billing' === $tax_based_on ) 
		{
			$country 	= $order->get_billing_country();
			$state 		= $order->get_billing_state();
			$postcode   = $order->get_billing_postcode();
			$city   	= $order->get_billing_city();
		} 
		else 
		{
			$country 	= $order->get_shipping_country();
			$state 		= $order->get_shipping_state();
			$postcode   = $order->get_shipping_postcode();
			$city   	= $order->get_shipping_city();
		}
		
		$tax_rates_base = array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
				);
		
		
		$new_fees = array();		//	save new fees temporarily to insert all fees later at once
		
		if ( sizeof( $items ) > 0  && $this->options[self::OPT_ENABLE_PROD_FEES] )
		{
			foreach ( $items as $item_key => $item ) 
			{
				$_product = $order->get_product_from_item( $item );
				if( ! $_product )	
				{	
					continue;
				}
				
						//	allows to skip adding fees for a product by third party
				if( ! apply_filters( 'wc_add_fees_order_before_add_product_fee', true, $_product, $order ) )
				{
					continue;
				}
				
				$total_excl = $item->get_subtotal();
				$total_incl = $item->get_subtotal() + $item->get_subtotal_tax();
				$quantity = $item->get_quantity();
				
				$fees_calc = $this->calculate_gateway_fee_product( $_product, $order->get_prices_include_tax(), $total_excl, $total_incl, $quantity, $tax_rates_base );
				
				if( ! empty( $fees_calc ) )
				{				
					$fees_calc->order_item_id[] = $item_key;
					$new_fees[] = $fees_calc;		
				}
			}
		}
		
		if( count( $new_fees ) > 0)
		{
			$cart = new WC_Cart();
			$rem = remove_action( 'shutdown', array( $cart, 'maybe_set_cart_cookies' ), 0 );

			foreach ( $new_fees as &$fee ) 
			{
				$this->add_fee_to_cart( $fee, $cart );
			}

			$wc_fees = $cart->get_fees();
			$add_fee_order->add_new_fees( $wc_fees );
			$new_fees = array();
			
			/**
			 * Bugfix: Workaround, because there is a hook that produces a call on WC_Cart with null
			 */
			if( ! ( class_exists( 'Follow_Up_Emails' ) || class_exists( 'WC_Subscriptions' ) ) )
			{
				$cart->empty_cart();
			}
			unset( $cart );
			$cart = null;
		}
		
		$add_fee_order->recalc_order_and_save();
		
				//	allows to skip adding total fees by third party
		if( ! apply_filters( 'wc_add_fees_order_before_add_total_fee', true, $order ))
		{
			$title = in_array( $this->payment_gateway_key, array( 'other', '' ) ) ? $this->payment_gateway_key : $this->gateways[ $this->payment_gateway_key ]->title;
			$add_fee_order->update_payment_method( $this->payment_gateway_key, $title );
			$add_fee_order->order->save();
			return true;	
		}
		
		$order_total = $order->get_total();
		$order_no_tax = $order_total - $order->get_total_tax();
		
		$fees_calc = $this->calculate_gateway_fee_total( $order->get_prices_include_tax(), $order_no_tax, $order_total, $tax_rates_base );
		if( ! empty( $fees_calc ) )
		{				
			$new_fees[] = $fees_calc;
		}
		
		$cart = new WC_Cart();
		$rem = remove_action( 'shutdown', array( $cart, 'maybe_set_cart_cookies' ), 0 );
		
		if( count( $new_fees ) > 0)
		{
			foreach ( $new_fees as &$fee ) 
			{
				$this->add_fee_to_cart( $fee, $cart );
			}
		
			$wc_fees = $cart->get_fees();
			$add_fee_order->add_new_fees( $wc_fees );
		}
		
		/**
		 * Bugfix: Workaround, because there is a hook that produces a call on WC_Cart with null
		 */
		if( ! ( class_exists( 'Follow_Up_Emails' ) || class_exists( 'WC_Subscriptions' ) ) )
		{
			$cart->empty_cart();
		}
		
		unset( $cart );
		$cart = null;
		
		$title = in_array( $this->payment_gateway_key, array( 'other', '' ) ) ? $this->payment_gateway_key : $this->gateways[ $this->payment_gateway_key ]->title;
		$add_fee_order->update_payment_method( $this->payment_gateway_key, $title );
		$add_fee_order->recalc_order_and_save();
		
		return true;		
	}
		
	/**
	 * 
	 * @param WC_Product $_product
	 * @param boolean $includes_tax
	 * @param float $total_excl
	 * @param float $total_incl
	 * @param int $quantity
	 * @param array $tax_rates_base			added with 2.2 -> for recalculating orders
	 * @return WC_Fee_Add_Fees|null,		null if no fee to add, else wc_calc_add_fee
	 */
	protected function calculate_gateway_fee_product( WC_Product $_product, $includes_tax, $total_excl, $total_incl, $quantity = 1, $tax_rates_base = array() )
	{
		$fees_calc = null;
		
		$prod_id = $_product->get_id();
		
		/**
		 * We only support fees for main product - all variations must have same fee
		 * 
		 * @since 3.1.4
		 */
		if( $_product instanceof WC_Product_Variation )
		{
			$prod_id = $_product->get_parent_id();
		}
		
		$pm_product = self::get_post_meta_product_default( $prod_id );
				
		//remove single product check - option doesn't exist any more
		//if( $pm_product[self::OPT_ENABLE_PROD] != 'yes' ) continue;

		if( ! empty( $this->payment_gateway_key ) && isset( $pm_product[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] ) )
		{
			$gateway = $pm_product[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ];
		}
		else
		{
			$gateway = array();
		}

		$gateway = self::get_option_gateway_default( $gateway, $this->payment_gateway_key, true );

		if( $gateway[self::OPT_KEY_ENABLE] != 'yes' ) 
		{
			return $fees_calc;
		}

		$maxval = 0.0;
		if( isset( $gateway[self::OPT_KEY_MAX_VALUE] ) )
		{
			$maxval = $gateway[self::OPT_KEY_MAX_VALUE];
		}

		if( ! is_numeric( $maxval ) )
		{
			$maxval = 0.0;
		}
		else
		{
			$maxval = (float) $maxval;
		}

		if( ! empty( $maxval ) )
		{
			$check_total = ( $includes_tax ) ? $total_incl : $total_excl;

			if( $check_total >= $maxval )
			{
				return $fees_calc;
			}
		}

			//changed with 2.1.0 - replaced $total_excl with $total_incl
		$fees_calc = $this->calculate_fees( $includes_tax, $total_incl, $gateway, $quantity, $tax_rates_base );
		
		/**
		 * Allows to filter and alter calculated fee
		 * 
		 * @since 2.2.21
		 */
		$fees_calc = apply_filters( 'wc_add_fees_calculated_fee', $fees_calc, 'product', $includes_tax, $total_incl, $gateway, $quantity, $tax_rates_base );

		if( is_null( $fees_calc ) || ! $fees_calc instanceof WC_Fee_Add_Fees )
		{
			$fees_calc = null;
			return null;
		}
		
		if( $fees_calc->amount_incl_tax < 0.01 )
		{
			$fees_calc = null;
			return $fees_calc;
		}
		
		$fees_calc->source = self::OPTIONNAME;
		$fees_calc->type = WC_Fee_Add_Fees::VAL_PRODUCT_ADD_FEE;
		$fees_calc->id = substr(( 'ADD_FEE_' . $this->prod_fee_cnt . '_' . $_product->get_id() ), 0, 15 );
		$this->prod_fee_cnt ++;
		$fees_calc->gateway_key = $this->payment_gateway_key;
		$fees_calc->gateway_title = $this->gateways[ $this->payment_gateway_key ]->title;
		$fees_calc->gateway_option = $gateway;
		$fees_calc->product_desc = $_product->get_title();

		return $fees_calc;
	}

	/**
	 * Extracts the portion <table>....</table> from the template to be able to replace it on the
	 * pay for order page with the revised content
	 * 
	 * @param string $buffer
	 * @return string
	 */
	protected function extract_order_template( $buffer )
	{
		$start = stripos( $buffer, '<table' );
		
		if ( $start === false) 
		{
			return $buffer;
		}
		
		$new_buffer = substr( $buffer, $start );
		
		$end = stripos( $new_buffer, '</table>' );
		
		if ( $end === false )
		{
			$ret = $new_buffer;
		}
		else
		{
			$ret = substr( $new_buffer, 0, $end );
		}
		
		$ret .= '</table>';
		
		return $ret;
	}

}


