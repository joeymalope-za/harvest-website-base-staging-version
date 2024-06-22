<?php

/**
 * Plugin Name: Sherpa Delivery for WooCommerce
 * Depends: WooCommerce
 * Description: Connects your WooCommerce store to Sherpa Delivery for automated 1hr, 2hr, 4hr & same day delivery in Australia.
 * Version: 3.1.1-beta-subscription-fix
 * Author: Sherpa Pty Ltd
 * Developer: Sherpa Pty Ltd
 * Text Domain: woocommerce-extension
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
	* Define WC Constants.
*/
define('SHERPA_PLUGIN_ID', 'sherpa');
define('SHERPA_PLUGIN_PAGE_ID', 'wc-sherpa');
define('SHERPA_PLUGIN_FILE', __FILE__);
define('SHERPA_PLUGIN_DIR', plugin_dir_path(__FILE__));
// define('SHERPA_PLUGIN_BASENAME', plugin_basename(__FILE__));
// define('SHERPA_ROUNDING_PRECISION', 4);

if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}

// Main plugin class
if (!class_exists('SherpaLogistics')) :
	class SherpaLogistics {

		private $version = '3.1.1-beta-subscription-fix';

		public function __construct() {

			if (!class_exists('WooCommerce')) {
				add_action('admin_notices', function () {
					echo '<div class="notice notice-error"><p>' . __('You need to install and activate WooCommerce before you can use the Sherpa Delivery plugin.', 'woocommerce-extension') . '</p></div>';
				});
				return;
			}

			// Init.
			$this->define_constants();
			$this->includes();
			$this->init_hooks();

			do_action('sherpa_loaded');
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 2.3
		 */
		private function init_hooks() {
			add_filter('woocommerce_shipping_chosen_method', '__return_false');

			add_action('woocommerce_email_after_order_table', array(
				$this,
				'sherpa_woocommerce_email_after_order_table'
			), 10, 4);

			add_action('wp_ajax_sherpa_credentials_action', array(
				$this,
				'sherpa_test_credentials_callback'
			));

			add_action('wp_ajax_sherpa_settings_action', array(
				$this,
				'sherpa_admin_save_callback'
			));

			add_action('wp_ajax_delivery_options_action_later', array(
				$this,
				'sherpa_deliver_options_later_ajax_callback'
			));

			add_action('wp_ajax_delivery_options_action', array(
				$this,
				'sherpa_deliver_options_ajax_callback'
			));

			add_action('admin_menu', array(
				$this,
				'register_my_sherpa_submenu_page'
			));

			add_action('woocommerce_shipping_init', array(
				$this,
				'sherpa_woocommerce_shipping_init'
			));

			add_filter('woocommerce_shipping_methods', array(
				$this,
				'sherpa_woocommerce_shipping_methods'
			));

			// Override woocommerce shipping method features
			add_action('init', array(
				$this,
				'sherpa_init'
			));

			// Enqueue admin scripts
			add_action('admin_enqueue_scripts', array(
				$this,
				'admin_init'
			));

			// Enqueue frontend scripts
			add_action('wp_enqueue_scripts', array(
				$this,
				'load_sherpa_frontend'
			));

			add_filter('woocommerce_locate_template', array(
				$this,
				'sherpa_woocommerce_locate_template'
			), 1, 9999);

			// cart functions
			add_action('wc_ajax_update_shipping_method', array(
				$this,
				'sherpa_woocommerce_update_shipping_method'
			));

			add_filter('woocommerce_cart_shipping_packages', array(
				$this,
				'sherpa_woocommerce_cart_shipping_packages'
			));

			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
				$this,
				'plugin_settings_link'
			));

			add_action('woocommerce_checkout_update_order_review', array(
				$this,
				'sherpa_woocommerce_update_order_review'
			));

			/**
			 * Prev hook -> woocommerce_payment_complete
			 */
      // This action must NOT be performed for subscription/virtual
      // products or sherpa disabled products
			add_action('woocommerce_pre_payment_complete', array(
				$this,
				'create_new_sherpa_delivery'
			));

			// Display Fields
			add_action('woocommerce_product_options_general_product_data', 'add_product_ship_via_sherpa_option');

			// Save Fields
			add_action('woocommerce_process_product_meta', 'save_product_ship_via_sherpa_option');

			// Shipping method name
			add_filter('woocommerce_order_get_items', array(
				$this,
				'order_shipping_description'
			));

			// add_action('woocommerce_before_checkout_process', array(
			// 	$this,
			// 	'validate_sherpa_checkout'
			// ), 10, 1);

			add_filter('woocommerce_shipping_method_add_rate', array(
				$this,
				'add_windows_to_shipping_rate'
			), 10, 3);

			add_action('admin_init', array(
				$this,
				'validate_sherpa_requirements'
			), 10, 1);

			add_action('woocommerce_checkout_process', array(
				$this,
				'validate_checkout_fields'
			));

			add_filter('woocommerce_cart_shipping_method_full_label', array(
				$this,
				'add_free_shipping_label'
			), 10, 2);

			// Add shipping method to order meta
			add_action('woocommerce_checkout_update_order_meta', array(
				$this,
				'add_order_shipping_method_meta'
			), 10, 1);

			// Sort package rates
			add_filter('woocommerce_package_rates', array(
				$this,
				'sherpa_woocommerce_package_rates'
			), 10, 2);

			// Add delivery information
			add_action('woocommerce_before_order_itemmeta', function ($item_id, $item) {
				if ('shipping' === $item->get_type() && 'sherpa' === $item->get_data()['method_id']) {
					$ready_at = get_post_meta($item->get_order_id(), '_sherpa_delivery_ready_at', true); //2023-10-20 00:00:00.000000
					$deliver_for = get_post_meta($item->get_order_id(), '_sherpa_delivery_deliver_for', true); //2023-10-20 15:30:00.000000
					$delivery_time_plain_text = get_post_meta($item->get_order_id(), '_sherpa_delivery_time_plain_text', true); //3:30 pm - 4:30 pm
          $order_id_new = $item->get_order_id();
          $updated_delivery_time_plain_text = get_post_meta($order_id_new, '_sherpa_delivery_time_plain_text_new', true);
          $delivery_time_plain_text = !empty($updated_delivery_time_plain_text) ? $updated_delivery_time_plain_text : $delivery_time_plain_text;

					if ($ready_at && $deliver_for) {
						$ready_at = new DateTime($ready_at, new DateTimeZone(wp_timezone_string()));
						$deliver_for = new DateTime($deliver_for, new DateTimeZone(wp_timezone_string()));

						echo sprintf(
							'<span style="color: #6c757d;">%s between %s</span>',
							$ready_at->format('d/m/Y'),
							$delivery_time_plain_text
						);
					}
				}
			}, 10, 2);

			add_action('woocommerce_order_status_completed', array(
				$this,
				'update_checkout_data_after_wc_order_completed'
			));
			add_action('woocommerce_order_action_sherpa_action', array($this, 'fired_my_sherpa_action'));
			add_action('woocommerce_order_actions', array($this, 'sherpa_wc_order_action'));
			add_action('init', array($this, 'post_send_to_sherp'));
			add_filter( 'manage_send_to_sherpa_posts_columns', array($this, 'sherpa_code_columns') );
			add_action( 'manage_send_to_sherpa_posts_custom_column',array($this, 'sherpa_custom_content_columns'), 10, 2);
			add_filter('bulk_actions-edit-send_to_sherpa', array($this, 'addCustomSherpaButton'));
			add_filter('bulk_actions-edit-shop_order', array($this, 'addCustomWooSherpaButton'));
			add_filter('handle_bulk_actions-edit-shop_order',array($this, 'handle_addCustomWooSherpaButton'),10,3);
			add_action('woocommerce_before_checkout_process', array($this, 'validate_sherpa_checkout'), 10, 1);
			add_action('admin_footer', array($this, 'popupmaker'));
			add_action('admin_footer', array($this, 'viewpopupmaker'));
			add_action('admin_notices', array($this, 'sherpa_admin_notice_action'));
			add_action('admin_notices', array($this, 'sherpa_admin_notice_error_action'));
			add_action('admin_notices', array($this, 'sherpa_order_notice_action'));
			// Add Button to Sherpa Post
			add_action('admin_notices', array($this, 'funct_generate_after_content'));
			add_action('wp_ajax_my_ajax_sherpa_post_action', array($this, 'my_ajax_sherpa_post_action_callback'));
			add_action('wp_ajax_my_ajax_set_sherpa_post_action', array($this, 'my_ajax_set_sherpa_post_action_callback'));
			add_action('wp_ajax_my_ajax_send_sherpa_action', array($this,'my_ajax_send_sherpa_action_callback'));
			add_action('wp_ajax_my_ajax_view_pop_up_shepa_action', array($this, 'my_ajax_view_pop_up_shepa_action_callback'));
			// add_action('wp_ajax_my_ajax_view_pop_up_shepa_action', array($this, 'my_ajax_delete_send_to_sherpa_orders_action_callback'));
			add_action('wp_ajax_my_ajax_view_update_sherpa_action', array($this, 'my_ajax_view_update_sherpa_action_callback'));
			add_action('wp_ajax_my_ajax_select_shepa_update_action', array($this, 'my_ajax_select_shepa_update_action_callback'));
			add_action('wp_ajax_my_ajax_edit_sherpa_post', array($this, 'my_ajax_edit_sherpa_post_callback'));
			add_action('wp_ajax_my_ajax_select_shepa_date_action', array($this, 'my_ajax_select_shepa_date_action_callback'));
			add_action('wp_ajax_my_ajax_time_sherpa_post', array($this, 'my_ajax_time_sherpa_post_callback'));
			add_action('wp_ajax_my_ajax_edit_sherpa_packages', array($this, 'my_ajax_edit_sherpa_packages_callback'));
			add_action('wp_ajax_my_ajax_edit_sherpa_options', array($this, 'my_ajax_edit_sherpa_options_callback'));
			add_action('wp_ajax_my_ajax_edit_sherpa_date_time', array($this, 'my_ajax_edit_sherpa_date_time_callback'));
			add_action('wp_ajax_my_ajax_send_to_sherpa_delete_action', array($this, 'my_ajax_send_to_sherpa_delete_action_callback')); // Callback to delete send to sherpa orders
		}

		public function update_checkout_data_after_wc_order_completed($order_id) {
			WC()->session->set('sherpa_delivery_time_plain_text', null);
		}
		
		
		/*
			WooCommerce order edit dropdown.
		*/
		
		public function sherpa_wc_order_action( $actions ) {
			if ( is_array( $actions ) ) {
				$actions['sherpa_action'] = __( 'Send To Sherpa' );
			}
			return $actions;
		}

		private function include_tracking_link() {
			$confObj = new Sherpa_Configurations();
			return ('1' == $confObj->getData('tracking_link'));
		}

		private function create_shipment() {
			$confObj = new Sherpa_Configurations();
			return ('1' == $confObj->getData('shipment'));
		}

		/**
		 * Check if an STS post exists for a woocommerce order
     *
     * @param string $wc_order_id is the original woocommerce order id.
     * @return bool true if the sherpa post exists for given order id, false otherwise
		 */

     public function check_sherpa_post_exists_for_wc_order($wc_order_id){

      // Check if a Send To Sherpa order exists for a given order id
      $args = array(
      'post_type' => 'send_to_sherpa',
      'post_parent' => $wc_order_id,
      'post_status' => 'any',
      'posts_per_page' => 1 // Limit to one post
      );
      $custom_posts = get_posts($args);

      if (!empty($custom_posts)) {
        // Custom post exists
        return true;
        // echo 'Exists';
      } else {
        // Custom post does not exist
        return false;
        // echo "Nope";
      }
    }

		
		/*
			WooCommerce order to send to sherpa post.
		*/
		public function fired_my_sherpa_action( $order ) {
			$order_id = trim(str_replace('#', '', $order->get_order_number()));
			$order = wc_get_order($order_id);
			$order_data = $order->get_data();
			$conf = new Sherpa_Configurations();
			$created_at = get_post_meta($order_id, '_sherpa_delivery_ready_at', true);
			// $dates = date_format($dates_format,"Y-m-d");
			$check_date = $created_at <= date("Y/m/d");

			if(strtotime($created_at) <= strtotime(date("Y/m/d"))){ 
				$created_at = date("Y/m/d");
				$dates_format = new DateTime($created_at);
				$dates = date_format($dates_format,"Y-m-d");
			} 

			/* $dates_format = new DateTime($created_at);
			$dates = date_format($dates_format,"Y-m-d"); */
			$delivery_options = get_post_meta($order_id, 'sherpa_shipping_method', true);
			
			if(isset($delivery_options) && !empty($delivery_options)){
				$delivery_option = explode('_', $delivery_options[0]);
				$delivery_option = $delivery_option[3];
			} else {
				$delivery_option = '2hr';
			}
			
			
			$delivery_time_plain_text = get_post_meta($order_id, '_sherpa_delivery_time_plain_text', true);
			if(empty($delivery_time_plain_text)){
				$delivery_time_plain_text = '12:15 pm - 2:15 pm';
			}
			$delivery_address_unit = !empty($order_data['shipping']['address_2'])?($order_data['shipping']['address_2']):($order_data['billing']['address_2']);
			$delivery_address = !empty($order_data['shipping']['address_1'])?($order_data['shipping']['address_1']):($order_data['billing']['address_1']);
			$delivery_address_contact_name = !empty($order_data['shipping']['first_name'])?($order_data['shipping']['first_name']):($order_data['billing']['first_name']);
			$delivery_address_contact_name_last = !empty($order_data['shipping']['last_name'])?($order_data['shipping']['last_name']):($order_data['billing']['last_name']);
			$delivery_address_phone_number = !empty($order_data['shipping']['phone'])?($order_data['shipping']['phone']):($order_data['billing']['phone']);
			$delivery_address_country_code = !empty($order_data['shipping']['country'])?($order_data['shipping']['country']):($order_data['billing']['country']);
			$delivery_address_state = !empty($order_data['shipping']['state'])?($order_data['shipping']['state']):($order_data['billing']['state']);
			$delivery_address_post_code = !empty($order_data['shipping']['postcode'])?($order_data['shipping']['postcode']):($order_data['billing']['postcode']);
			$delivery_address_city = !empty($order_data['shipping']['city'])?($order_data['shipping']['city']):($order_data['billing']['city']);
			$total_quantity = $order->get_item_count();
			$pickup_address_instructions = get_option('sherpa_settings_notes');
			$authority_to_leave = get_option('sherpa_settings_authority_to_leave');
			$send_sms = get_option('sherpa_settings_send_sms');
			$contains_alcohol = get_option('sherpa_settings_contains_alcohol');
			$contains_fragile = get_option('sherpa_settings_contains_fragile_items');
			$contains_tobacco = get_option('sherpa_settings_contains_tobacco');
			$requires_hi_vis_vest = get_option('sherpa_settings_requires_hi_vis_vest');
			$contain_medication = get_option('sherpa_settings_contains_scheduled_medication');
			$specified_recipient = get_option('sherpa_settings_specified_recipient');
			$requires_hi_vis_vest = get_option('sherpa_settings_requires_hi_vis_vest');
			$sherpa_origin = get_option('woocommerce_sherpa_settings');
			$pickup_address = $sherpa_origin['origin_address'];
			$pickup_city = $sherpa_origin['origin_city'];
			$pickup_postcode = $sherpa_origin['origin_postcode'];
			$pickup_state = $sherpa_origin['origin_state'];
			$default_country = get_option('woocommerce_default_country');
			$country_code = strtoupper(substr($default_country, 0, 2));
			$current_user = wp_get_current_user();
			$display_name = $current_user->display_name;
			if($total_quantity >= 10 ){
				$total_quantity = '10';
			}
			$param = array(
				'vehicle_id' => $conf->getVehicleId(),
				'item_description'=> $conf->getItemDescription(),
				'pickup_address'=> $pickup_address,
				// 'pickup_address_unit'=> $order_data['billing']['address_2'],
				'pickup_address_country_code'=> $country_code,
				'pickup_address_contact_name'=> $display_name,
				'pickup_address_instructions'=> $pickup_address_instructions,
				'delivery_address_unit'=> $delivery_address_unit,
				'delivery_address'=> $delivery_address,
				'delivery_address_contact_name'=> $delivery_address_contact_name,
				'delivery_address_contact_name_last'=> $delivery_address_contact_name_last,
				'delivery_address_phone_number'=> $delivery_address_phone_number,
				'delivery_address_country_code'=> $delivery_address_country_code,
				'delivery_address_state'=> $delivery_address_state,
				'delivery_address_post_code'=> $delivery_address_post_code,
				'delivery_address_city'=> $delivery_address_city,
				'delivery_options'=> $delivery_option,
				'delivery_window'=> $delivery_time_plain_text,
				'delivery_packages'=> $total_quantity,
				'ready_at'=>!empty($dates)? $dates:$created_at,
				'leave_unattended' => $authority_to_leave,
				'specified_recipient' => $specified_recipient,
				'fragile' => $contains_fragile,
				'prescription_meds' => $contain_medication,
				'send_sms' => $send_sms,
				'alcohol' => $contains_alcohol,
				'tobacco' => $contains_tobacco,
				'high_vis' => $requires_hi_vis_vest,
			);
							
			if($param['delivery_options'] == 'at'){
				$param['delivery_options'] = 'same_day';
			}	
			if($param['delivery_options'] == 'bulk'){
				$param['delivery_options'] = 'bulk_rate';
			}
			
			//set default del option 
			if(empty($param['delivery_options'])){
			    $param['delivery_options'] = '2hr';
			}			
			
			update_post_meta ($order_id, 'set_params' ,$param);	
			$check_post = get_post_meta($order_id, 'check_post',true);

      // Check if an STS order is available for given order id
      // $args = array(
      //   'post_type' => 'send_to_sherpa',
      //   'post_parent' => $order_id,
      //   'post_status' => 'any',
      //   'posts_per_page' => 1 // Limit to one post
      //   );
      //   $sts_posts = get_posts($args);
      //   $message = 'STS post for order 3123 ';
  
      //   if (!empty($sts_posts)) {
      //     // Custom post exists
      //      return true;
      //     //$message .= 'Exists';
      //   } else {
      //     // Custom post does not exist
      //      return false;
      //     //$message .= "does not exist";
      //   }
      if($this->check_sherpa_post_exists_for_wc_order($order_id)){
        error_log('STS Post exists for '.$order_id);
      }
      if( (get_post_meta($order_id, '_sherpa_delivery_response')) || get_post_meta($order_id, '_sherpa_delivery_note') ){ // Check if order is already logged with sherpa -> from order edit page
        error_log(get_post_status($order_id));
        $message = "This delivery has already been logged with Sherpa.";

				set_transient( 'sherpa_order_notice', $message, 3600);
        return false;
      }
			
      // send to STS page if not already queued
        if (!($this->check_sherpa_post_exists_for_wc_order($order_id)) && (!(get_post_meta($order_id, '_sherpa_delivery_response')) ) && ( !(get_post_meta($order_id, '_sherpa_delivery_note')) ) ){
			//if ( empty($check_post) ){
        // Create send to sherpa order post type
				update_post_meta ($order_id, 'check_post' ,true);
				$original_post = get_post(get_the_ID());
				$new_post = array(
					'post_type' => 'send_to_sherpa',
					'post_status' => 'publish',
					'post_password' => '',
					'post_title' => $original_post->ID,
					'post_parent' => $order_id,
				);
				wp_insert_post($new_post);
				//$sherpa_order_id = wp_insert_post($new_post);
        //update_post_meta($sherpa_order_id, '_woocommerce_order_id', $order_id);
				wp_redirect(admin_url('/edit.php?post_type=send_to_sherpa'));
				exit;
			} else {
        //not from bulk page
				$message = "This order is already in the Send to Sherpa queue. Navigate to WooCommerce > Send to Sherpa to log the delivery.";
				set_transient( 'sherpa_order_notice', $message, 3600);
			}
			
		}
		public function sherpa_woocommerce_email_after_order_table($order, $sent_to_admin, $plain_text, $email) {
			if ($sent_to_admin || false === $this->include_tracking_link())
				return;

			// Get a note
			$sherpa_delivery_note = get_post_meta($order->get_id(), '_sherpa_delivery_note', true);
			if (empty($sherpa_delivery_note))
				return;

			echo '<h2 style=\'color: #96588a; display: block; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left;\'>Shipping</h2>';
			echo sprintf(
				'<p style=\'display: block; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; margin: 0 0 40px; text-align: left; border: 1px solid #e5e5e5; padding: 12px;\'>%s</p>',
				trim($sherpa_delivery_note)
			);
		}
		
		/*
			WooCommerce register posttype for send to sherpa
		*/
		public function post_send_to_sherp() {
			$labels = array(
				'name'                  => __( 'Send to Sherpa', 'text-domain' ), // Page Heading Label
				'singular_name'         => __( 'Send to Sherpa', 'text-domain' ),
				'menu_name'             => __( 'Send to Sherpa', 'text-domain' ),
				'name_admin_bar'        => __( 'All Shippings', 'text-domain' ),
				'add_new'               => __( 'Add New', 'text-domain' ),
				'add_new_item'          => __( 'Add New Send to Sherpa', 'text-domain' ),
				'new_item'              => __( 'New Send to Sherpa', 'text-domain' ),
				'edit_item'             => __( 'Edit Send to Sherpa', 'text-domain' ),
				'view_item'             => __( 'View Send to Sherpa', 'text-domain' ),
				'all_items'             => __( 'Send to Sherpa', 'text-domain' ),
			);     
			$args = array(
				'labels' => $labels,
				'description' => false,
				'public' => true ,
				'show_ui' => true,
				'show_in_menu' =>  'woocommerce',
				'query_var' => false,
				'rewrite' => false,
				'capability_type' => 'post',
				'has_archive' => false,
				'hierarchical' => false,
				'menu_position' => null,
				'publicly_queryable' => true,
				'menu_icon' => "",
				'supports' => array( 'title')
			);
			register_post_type( 'Send_to_Sherpa', $args );
		}
		/*
				Send to sherpa page header columns label
		*/
		
		public function sherpa_code_columns( $columns ) {
		  unset($columns['date']);
		  unset($columns['title']);

      /**
       * Testing if a product is 
       * shippable by sherpa or
       * is a subscription product or
       * a virtual product
       * using the order id
       */

      //$test_order_id = 117; // Order 117 has 1 virtual subscription product, id: 17, Ship via Sherpa: No
      //$test_order_id = 132; // Order 132 has 2 products: 1 normal physical product and 1 virtual subscription product, id: 17, Ship via Sherpa: No
      //$test_order_id = 158; // Order 158 is a subscription order
      $test_order_id = 175; // Order 158 is a subscription order
      //echo "Test order id: ".$test_order_id."<br>";
      // Get the order object using order_id
      $order = wc_get_order($test_order_id);

      //Check if order has a subscription ID
      if($order->get_meta('_subscription_renewal')){
        //echo "Order has a subscription ID: ".$order->get_meta('_subscription_renewal')."<br>";
      }

      //Check if order has a parent order
      if ($order->get_parent_id()) {
        // This order has a parent order
        //echo "This order has a parent order with ID: " . $order->get_parent_id();
      } else {
        // This order does not have a parent order
        //echo "This order does not have a parent order.";
      }

      // Check if order object exists and is valid
      if(! $order){
        return;
      }

      // Convert the order object to an array
      $data_array = $order->get_data();
      echo "<pre>";
      //print_r($data_array['line_items']);
      foreach($data_array['line_items'] as $itemObject){
        $items_array = $itemObject->get_data();
        //print_r($items_array);
        //echo "Product id: ".$items_array['product_id']."<br>";
        //echo "Product name: ".$items_array['name']."<br>";
        $product = wc_get_product($items_array['product_id']);
        // Check if the product exists and if it's virtual
        if ($product && $product->is_virtual()) {
          //echo "Product is virtual"."<br><br>";
        }else{
          //echo "Product is not virtual"."<br><br>";
        }
        //print_r($product);
        //print_r($product->get_data());
      }
      echo "</pre>";


      // Loop through the order data
      foreach($order->get_items() as $item_id => $item){
        echo "<pre>";
        // fetch the products object
        if($order->get_product_from_item($item)){
          $productObject = $order->get_product_from_item($item);
          //print_r($productObject);

          // Convert product object to array
          $productArray = $productObject->get_data();
          //print_r($productArray);

          //Print the id of each product
          foreach($productArray as $p_array){
            //print_r($p_array);
          }

          // Fetch product meta data 
          foreach($productArray['meta_data'] as $product_meta_key => $product_meta_object){
            //print_r($productArray['meta_data']);
            //Convert to array
            $product_meta_array = $product_meta_object->get_data();
            //print_r($product_meta_array);

            //Look for _ship_via_sherpa key
            //echo $product_meta_array['key'].": ";
            //echo $product_meta_array['value']."<br>";

            // Check if sherpa is enabled for this product
            if($product_meta_array['key'] == '_ship_via_sherpa'){

              //Check if _ship_via_sherpa is enabled 
              $sherpa_enabled_product = $product_meta_array['value'];
              if($sherpa_enabled_product){
                //echo "Sherpa is enabled for this product";
              }else{
                //echo "Sherpa is disabled for this product";
                //return; // return results in an infinite loop of errors
                //exit;
              }
            }





            //print_r($product_meta_object->get_data());
            foreach($product_meta_array as $product_meta_object_key => $product_meta_object_value){
              //print_r($product_meta_object_value);
              //echo $product_meta_object_value."<br>";
              if($product_meta_array['key'] == '_ship_via_sherpa'){
                //echo $product_meta_object_value."<br>";
                //echo "Sherpa shiping is".$product_meta_array['value']."<br>";
              }
            }
          }
          // Loop through product array
          foreach($productArray as $productDetails){
            //print_r($productDetails);
            // if($productDetails->key == '_ship_via_sherpa'){
            //   echo "Sherpa:)";
            // }
          }
        }
        //echo $item_id;        
        //print_r($item);
        //print_r($data_array);
        echo "</pre>";

      }

      // //$test_id = '3210'; // Complted order
      // //$test_id = '3183'; // Completed order (remove from STS)
      // // $test_id = '3209'; // Sherpa enabled order in queue
      // //$test_id = '3181'; // Completed order
      // //$test_id = '3214'; 
      // $test_id = '3218';
      // $test_id = '3319';
      // $get_post_id = get_post($test_id);
      // $parent_id = $get_post_id->post_parent;
      // if ($parent_id == 0){
      //   $parent_id = $test_id;
      // }

      // echo '<pre>';
      // $localDate = wp_date('h:i:s')."<br />";
      // echo get_option('timezone_string').$localDate."<br />";
      // echo 'Timezone: '.get_option('gmt_offset')."<br />"; 
      // echo "Parent ".$parent_id;
      // //print_r(get_post_meta($test_id));
      // $meta_data = get_post_meta($test_id);
      // echo "For ".$test_id.": <br>";
      // echo "_sherpa_delivery_time_plain_text";
      // print_r($meta_data['_sherpa_delivery_time_plain_text']);
      // echo "<br>_sherpa_delivery_ready_at";
      // print_r($meta_data['_sherpa_delivery_ready_at']);
      // echo "<br>set_params<br>";
      // error_log("Testing id meta data");
     
      // //print_r($meta_data['set_params']);
      // $meta_params = get_post_meta($test_id, 'set_params');
      // print_r($meta_params);
      // foreach ($meta_params as $params){
      //   print_r($params['delivery_window']);
      //   print_r($params['ready_at']);
      // }
      // echo '</pre>';
      // //echo get_post_meta($test_id, 'shipped_by')[0];
      // //$sherpa_chosen_shipping_methods = get_post_meta($orderId, 'sherpa_shipping_method', TRUE);
      // $shipping_method = (get_post_meta($test_id, 'sherpa_shipping_method', TRUE)[0]);

      // // echo Sherpa_Sherpa::OPTION_SERVICE_1_HOUR .'<br>';
      // // echo Sherpa_Sherpa::OPTION_SERVICE_2_HOUR .'<br>';
      // // echo Sherpa_Sherpa::OPTION_SERVICE_4_HOUR .'<br>';
      // // echo Sherpa_Sherpa::OPTION_SERVICE_ANYTIME .'<br>';
      // // echo Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE .'<br>';
      // // echo Sherpa_Sherpa::DELIVERY_OTHER_THAN_SHERPA .'<br>';


      // $shipping_method = str_replace('_',' ',$shipping_method);
      // echo $shipping_method .'<br>';

      // $one_hr = str_replace('_',' ',Sherpa_Sherpa::OPTION_SERVICE_1_HOUR);
      // echo $one_hr .'<br>';

      // if(strpos($shipping_method,$one_hr) !== false){
      //   echo 'Yes 1 hr'.'<br>';
      // }

      // $sherpa_delivery_note = get_post_meta($test_id, '_sherpa_delivery_note') ?: null;
      // //if(isset($sherpa_delivery_note)){
      // if(!get_post_meta($test_id, '_sherpa_delivery_note')){
      //   echo 'No Note Found for <a target="_blank" href="http://localhost/sherpa-plugin-nz/wp-admin/post.php?post='.$test_id.'&action=edit">'.$test_id."</a>";
      // }else{
      //   echo 'Note exists for <a target="_blank" href="http://localhost/sherpa-plugin-nz/wp-admin/post.php?post='.$test_id.'&action=edit">'.$test_id.'</a> : '.$sherpa_delivery_note[0];
      // }

      // echo '<br>';

      // if (get_post_meta($test_id, '_sherpa_delivery_response')){
      //   echo 'Completed order';
      // } elseif (get_post_meta($test_id, '_sherpa_delivery_note')) {
      //   echo 'Sherpa enabled direct order in queue';
      // } elseif (get_post_meta($test_id, 'check_post') || get_post_meta($test_id, 'set_params')){
      //   echo 'Order in queue';
      // }  else {
      //   echo 'Sherpa disabled WC order';
      // }

      // if($this->check_sherpa_post_exists_for_wc_order($test_id)){
      //   echo'<br>STS also exists';
      // }else{
      //   echo "no sts";
      // }

		  $columns['order'] = '<strong>'.__( 'Order', 'text-domain' ).'</strong>';
      $columns['del_date'] = '<strong>'.__( 'Ready for Pickup', 'text-domain' ).'</strong> <br> <label>Date and time the item/s will be ready for pickup</label>';
      $columns['del_option'] = '<strong>'.__( 'Delivery Option', 'text-domain' ).'</strong> <br> <label>Sherpa delivery option</label>';
		   //$columns['del_win'] = __( 'Delivery Window', 'text-domain' );
		  // $columns['pickup_time'] = __( 'Pickup time', 'text-domain' );
		  $columns['packages'] = '<strong>'.__( 'No. of Packages', 'text-domain' ).'</strong>';
		  $columns['del_add'] = '<strong>'.__( 'Delivery Address', 'text-domain' ).'</strong>';
		  $columns['del_pref'] = '<strong>'.__( 'Delivery Pref', 'text-domain' ).'</strong>';
		  return $columns;
	  }	
	
	/*
		Send to sherpa page content data row.
	*/
	public function sherpa_custom_content_columns( $column, $post_id) {
		$get_parent_id = get_post($post_id);
		$order_id = $get_parent_id->post_parent;
		if($get_parent_id->post_parent == 0){
			$order_id = $post_id;
		}
		$order_detail = get_post_meta($order_id, 'set_params');
		$order = wc_get_order($order_id);
		$woocommerce_sherpa_settings_services = get_option('woocommerce_sherpa_settings', array());
		
		foreach($order_detail as $order_data){
			$dates_for = new DateTime($order_data['ready_at']);


      // Split the delivery window into start and end times
      $delivery_window_array = explode('-', $order_data['delivery_window']);

      // Time chosen at cart
      $ready_at_time = date('H:i', strtotime($delivery_window_array[0])); // start time

      //$dateTimeFormattedx = $order_data['ready_at'].'T'.$startTime; //'2023-06-29T19:14'
      $dates_string = (!empty($order_data['last_selected_time'])) ? $order_data['last_selected_time'] : $order_data['ready_at'].'T'.$ready_at_time;
      $dates = date('Y-m-d\TH:i', strtotime($dates_string));
      $last_updated_date = date('Y-m-d', strtotime($dates_string));
      $timezone = new DateTimeZone(wp_timezone_string());

      // Get the current time
      $currentDateTime = new DateTime('now', $timezone);

      // Extract the time from the given date-time string
      $givenDateTime = new DateTime($dates_string);
      $givenTime = $givenDateTime->format('H:i'); 

      // Step 3: Compare the extracted time with the current time
      $currentTime = $currentDateTime->format('H:i'); //add buffer here


      // if ($givenTime > $currentTime) {
      //   echo "<br>The given time ($givenTime) is AHEAD of the current time.";
      // } else {
      //     echo "<br>The given time ($givenTime) is NOT ahead of the current time.";
      // }
      //   echo '<br>dates_string '.$dates_string;
      

			//$dates = $dates_for->format('d F');
      ////$dates = $dates_for->format('Y-m-d\TH:i');
			//$dates = $dates_for->format('d F Y H:i:s');
			
			$date_to_check = !empty($last_updated_date) ? $last_updated_date : $order_data['ready_at'];
      $timezone = new DateTimeZone(wp_timezone_string());
      //$current_date = new DateTime('now', $timezone);

      //echo '<br>date to check'.$date_to_check;

      //$currentDateOnly = date('Y-m-d');
      $currentDateOnly = new DateTime('now', $timezone);
      $currentDateOnly = $currentDateOnly->format('Y-m-d');
      $currentDateTime = new DateTime($currentDateOnly." ".$currentTime);

      // Adds 5 minutes to current timestamp
      $currentDateTimePlus5Min = $currentDateTime->add(new DateInterval('PT5M'));

      //echo '<br>currentDateOnly'.$currentDateOnly;
             
      $dateErrorMessage = ' ';
      $bordercolor = '#8c8f94';
      //if ($current_date > new DateTime($date_to_check, $timezone)) {
      if (new DateTime($currentDateOnly) > new DateTime($date_to_check)) {
        $nextavaildatecustom = $this->nextavaildatecustom();
        $dates_for = new DateTime($nextavaildatecustom['next_available'], $timezone);
			  //$dates = $dates_for->format('d F');
			  $dates = $dates_for->format('Y-m-d\TH:i');
        $dates = $currentDateTimePlus5Min->format('Y-m-d\TH:i');

			  //$dates = $dates_for->format('d F Y H:i:s');
        // echo "<label style='color:red'>PAST order</label>";
        // $bordercolor = 'red';
      }

      if (new DateTime($currentDateOnly) == new DateTime($date_to_check)) {
        // echo "<label style='color:green'>TODAY's order</label>";
        // $bordercolor = 'green';
      }

      if (new DateTime($currentDateOnly) < new DateTime($date_to_check)) {
        // echo "<label style='color:orange'>FUTURE order</label>";
        // $bordercolor = 'cyan';
      }

      //print_r($order_data);
      // $originalDatetime = '28 June 2023 04:38:00';
      // $originalDatetime = '28 June 2023 04:38:00';
      // //$originalDatetime = $dates;
      // //$originalDatetime = $order_data['ready_at'];
      // //error_log($order_data);
      // $dateTimeObj = DateTime::createFromFormat('d F Y H:i:s', $originalDatetime, $timezone);
      // $dateTimeFormatted = $dateTimeObj->format('Y-m-d\TH:i');

      // Fetch delivery window  
      $delivery_window = $order_data['delivery_window'];
      $last_selected_date_time = (!empty($order_data['last_selected_time'])) ? $order_data['last_selected_time'] : $dates;

			$del_date	= '<input type="datetime-local" id="del_date_viewxx" class="del_date_viewddxx test" style="border-color:'.$bordercolor.';" sts-ordid="'.$post_id.'" data-ordid="'.$order_id.'"  name="del_date"  value="'.$dates.'" testing="'.$last_selected_date_time.'">';
      $del_day_error = '  <div id="error-message" style="color: #c4105a"></div>';
			$del_add	= (!empty($order_data['delivery_address'].', '.
									$order_data['delivery_address_country_code'].', '.
									$order_data['delivery_address_city'].', '.
									$order_data['delivery_address_post_code'].', '.
									$order_data['delivery_address_state']))
									? 
									$order_data['delivery_address'].', '.
									$order_data['delivery_address_city'].', '.
									$order_data['delivery_address_state'].', '.
									$order_data['delivery_address_country_code'].', '.
									$order_data['delivery_address_post_code']
									: 'Please set';
			$del_option = (!empty($order_data['delivery_options']))? $order_data['delivery_options'] : 'Please Set';
			$del_win = (!empty(	$order_data['delivery_window'])) ? $order_data['delivery_window'] : 'Please set';
			$packages = (!empty($order_data['delivery_packages'])) ? $order_data['delivery_packages'] : 'Please set';
			$first_name = (!empty(	$order_data['delivery_address_contact_name'])) ? $order_data['delivery_address_contact_name'] : 'Please Set';
			$last_name = (!empty(	$order_data['delivery_address_contact_name_last'])) ? $order_data['delivery_address_contact_name_last'] : 'Please Set';
		}
				
		$del_pref = '<div class="daily_pref '.$order_id.'" id="daily_pref" ><a href="#'.$order_id.'">View</a></div>';
		switch ( $column ) {
			case 'order' :
				$edit_order = '<div class="edit_order .'.$order_id.'" id="edit_order" ><a href="'.site_url().'/wp-admin/post.php?post='.$order_id.'&action=edit"> # '.$order_id.' '.$first_name.' '.$last_name.'</a></div>';
				echo $edit_order;
				break;
	 
			case 'del_date' :
				echo $del_date;
        echo $del_day_error;
				break;

			case 'del_option' :
?>

      <select id="del_options" class="del_options" data-ordid="<?php echo $order_id ?>" name="del_option">
        <?php
      	  $sherpa_shipping_method = get_post_meta($order_id,'sherpa_shipping_method',true);
      	  $sherpa_shipping_method = explode('_',$sherpa_shipping_method[0]);

      	  $all_options = array(
      	  	'2hr' => '2 Hour',
      	  	'4hr' => '4 Hour',
      	  );
        
      	  if(get_option('service_1hr_enabled')){
            $all_options['1hr'] = '1 Hour';
          }
          if(get_option('service_at_enabled')){
          	//$all_options['same_day'] = 'Same Day';
          	$all_options['at'] = 'Same Day';
          }					
          if(get_option('service_bulk_rate_enabled')){
          	$all_options['bulk_rate'] = 'Bulk Rate';	
          }
      	  $selected = '';
      	  //$services = include(SHERPA_PLUGIN_DIR . '/templates/adminhtml/shipping-services.php');
      	  $confObj = new Sherpa_Configurations();	
      	  natsort($all_options);
        
      	  foreach($all_options as $label=>$options) {
      	    if(!empty($sherpa_shipping_method[0])){
      	      if(!$woocommerce_sherpa_settings_services['services'][$sherpa_shipping_method[0].'_'.
              $sherpa_shipping_method[1]]['service_'.$label]['enabled']){
                continue;				       
          	  }
      	    } 
      	  	$selected = '';
      	  	if ($label== $del_option) {
      	  		$selected = 'selected';
      	  	} else if ($label == "at") {
              if ($del_option == "same_day") {
                $selected = 'selected';
              }
            }
      	  	//unset($services['service_sameday']);
      	  	/*	if(!empty(!empty($services))){
      	  			foreach($services as $kk => $val){
      	  				foreach($services[$kk]['services'] as $kkss => $valss){
                  
      	  					$methodid = $kk.'_service_'.$label;
                  
      	  					if (in_array($methodid, [
      	  						'service_later_service_bulk_rate',
      	  						'service_sameday_service_bulk_rate',
      	  					]) && 'FL' !== $confObj->getData('delivery_rates')) {
      	  						continue 3;
      	  					}
                  
      	  				}	
      	  			}	
              
      	  		} */
      ?>
        <option value="<?php echo $label; ?>" <?php echo $selected; ?> ><?php echo $options; ?></option>
      <?php 
          } 
      ?>
      </select>			
      <?php 
        break;
	      case 'del_win' :
?>
<div class="uniquedel_options">
	<select id="del_win_changes" class="del_win_changes disdelwin delwin_<?php echo $order_id ?>" data-ordid="<?php echo $order_id ?>" name="del_win">
		<?php
		$all_options = array(
			' ' => ' ',
		);
		$selected = '';
		if (in_array($del_win, $all_options) ) {
			$selected = 'selected';
			}
			?>
		<option value="" ></option>
		</select>
		<div class="loaderdisdelwin"></div>
		</div>
	<?php	
    break;
		case 'packages' :
	?>
	  <select id="packages" name="packages" class="del_packages" data-ordid="<?php echo $order_id ?>">
      <option value="default">Packages</option>
			  <?php
			  $all_options = array(
			  	'1' => '1',
			  	'2' => '2',
			  	'3' => '3',
			  	'4' => '4',
			  	'5' => '5',
			  	'6' => '6',
			  	'7' => '7',
			  	'8' => '8',
			  	'9' => '9',
			  	'10' => '10'
			  );
			  foreach($all_options as $label=>$options) {
			  	$selected = '';
			  	if ($label== $packages ) {
			  		$selected = 'selected';
			    }
			  ?>
		  <option value="<?php echo $label; ?>" <?php echo $selected; ?> ><?php echo $options; ?></option>
	<?php } ?>
	</select>
	<?php	
    break;
		case 'del_add' :
	    echo $del_add; 
	    break;
    case 'del_pref' :
      echo $del_pref; 
      break;
    }	
  }

	/**
	 * Adds "Import" button on module list page
	 */
	public function addCustomSherpaButton($bulk_array){
		unset( $bulk_array[ 'trash' ] );
		unset( $bulk_array[ 'edit' ] );
		if ( is_array( $bulk_array ) ) {
			$bulk_array['sherpa_edit'] = __( 'Bulk Edit' );
		}
		return $bulk_array;
	}

	/**
	 * Adds "Import" button on module list page
	 */
	public function addCustomWooSherpaButton($bulk_array){
		if ( is_array( $bulk_array ) ) {
			$bulk_array['sherpa_edit'] = __( 'Send to Sherpa' );
		}
		return $bulk_array;
	}
	
	/*
		WooCommerce bulk order send to sherpa page at once.
	*/
	public function handle_addCustomWooSherpaButton( $redirect, $doaction, $object_ids){
		if ( 'sherpa_edit' === $doaction ) {
      $orders_already_in_queue = [];
      $orders_already_sent = [];
			foreach($object_ids as $order_id){
				$order = wc_get_order($order_id);
				$order_data = $order->get_data();
				$conf = new Sherpa_Configurations();
				$created_at = get_post_meta($order_id, '_sherpa_delivery_ready_at', true);
				// $dates = date_format($dates_format,"Y-m-d");
				$check_date = $created_at <= date("Y/m/d");

				if(strtotime($created_at) <= strtotime(date("Y/m/d"))){ 
					$created_at = date("Y/m/d");
					$dates_format = new DateTime($created_at);
					$dates = date_format($dates_format,"Y-m-d");
				} 
        
				/* $dates_format = new DateTime($created_at);
				$dates = date_format($dates_format,"Y-m-d"); */
				$delivery_options = get_post_meta($order_id, 'sherpa_shipping_method', true);
				if(isset($delivery_options) && !empty($delivery_options)){
					$delivery_option = explode('_', $delivery_options[0]);
					$delivery_option = $delivery_option[3];
				} else {
					// $delivery_option = 'service_later_service_2hr';
					$delivery_option = '2hr';
				}
				$delivery_time_plain_text = get_post_meta($order_id, '_sherpa_delivery_time_plain_text', true);
				if(empty($delivery_time_plain_text)){
					$delivery_time_plain_text = '12:15 pm - 2:15 pm';
				}
				$delivery_address_unit = !empty($order_data['shipping']['address_2'])?($order_data['shipping']['address_2']):($order_data['billing']['address_2']);
				$delivery_address = !empty($order_data['shipping']['address_1'])?($order_data['shipping']['address_1']):($order_data['billing']['address_1']);
				$delivery_address_contact_name = !empty($order_data['shipping']['first_name'])?($order_data['shipping']['first_name']):($order_data['billing']['first_name']);
				$delivery_address_contact_name_last = !empty($order_data['shipping']['last_name'])?($order_data['shipping']['last_name']):($order_data['billing']['last_name']);
				$delivery_address_phone_number = !empty($order_data['shipping']['phone'])?($order_data['shipping']['phone']):($order_data['billing']['phone']);
				$delivery_address_country_code = !empty($order_data['shipping']['country'])?($order_data['shipping']['country']):($order_data['billing']['country']);
				$delivery_address_state = !empty($order_data['shipping']['state'])?($order_data['shipping']['state']):($order_data['billing']['state']);
				$delivery_address_post_code = !empty($order_data['shipping']['postcode'])?($order_data['shipping']['postcode']):($order_data['billing']['postcode']);
				$delivery_address_city = !empty($order_data['shipping']['city'])?($order_data['shipping']['city']):($order_data['billing']['city']);
				$total_quantity = $order->get_item_count();
				$pickup_address_instructions = get_option('sherpa_settings_notes');
				$authority_to_leave = get_option('sherpa_settings_authority_to_leave');
				$send_sms = get_option('sherpa_settings_send_sms');
				$contains_alcohol = get_option('sherpa_settings_contains_alcohol');
				$contains_fragile = get_option('sherpa_settings_contains_fragile_items');
				$contains_tobacco = get_option('sherpa_settings_contains_tobacco');
				$requires_hi_vis_vest = get_option('sherpa_settings_requires_hi_vis_vest');
				$contain_medication = get_option('sherpa_settings_contains_scheduled_medication');
				$specified_recipient = get_option('sherpa_settings_specified_recipient');
				$sherpa_origin = get_option('woocommerce_sherpa_settings');
				$pickup_address = $sherpa_origin['origin_address'];
				$pickup_city = $sherpa_origin['origin_city'];
				$pickup_postcode = $sherpa_origin['origin_postcode'];
				$pickup_state = $sherpa_origin['origin_state'];
				$default_country = get_option('woocommerce_default_country');
				$country_code = strtoupper(substr($default_country, 0, 2));
				$current_user = wp_get_current_user();
				$display_name = $current_user->display_name;
				
				if	($total_quantity >= 10){
					  $total_quantity = '10';
				}
				$param = array(
					'vehicle_id' => $conf->getVehicleId(),
					'item_description'=> $conf->getItemDescription(),
					'pickup_address'=> $pickup_address,
					// 'pickup_address_unit'=> $order_data['billing']['address_2'],
					'pickup_address_country_code'=> $country_code,
					'pickup_address_contact_name'=> $display_name,
					'pickup_address_instructions'=> $pickup_address_instructions,
					'delivery_address_unit'=> $delivery_address_unit,
					'delivery_address'=> $delivery_address,
					'delivery_address_contact_name'=> $delivery_address_contact_name,
					'delivery_address_contact_name_last'=> $delivery_address_contact_name_last,
					'delivery_address_phone_number'=> $delivery_address_phone_number,
					'delivery_address_country_code'=> $delivery_address_country_code,
					'delivery_address_state'=> $delivery_address_state,
					'delivery_address_post_code'=> $delivery_address_post_code,
					'delivery_address_city'=> $delivery_address_city,
					'delivery_options'=> $delivery_option,
					'delivery_window'=> $delivery_time_plain_text,
					'delivery_packages'=> $total_quantity,
					'ready_at'=>!empty($dates)? $dates:$created_at,
					'leave_unattended' => $authority_to_leave,
					'specified_recipient' => $specified_recipient,
					'fragile' => $contains_fragile,
					'prescription_meds' => $contain_medication,
					'send_sms' => $send_sms,
					'alcohol' => $contains_alcohol,
					'tobacco' => $contains_tobacco,
					'high_vis' => $requires_hi_vis_vest,
					);
					if($param['delivery_options'] == 'at'){
						$param['delivery_options'] = 'same_day';
					}
					if($param['delivery_options'] == 'bulk'){
						$param['delivery_options'] = 'bulk_rate';
					}
					update_post_meta ($order_id, 'set_params' ,$param);	
					$check_post = get_post_meta($order_id, 'check_post');

					// if ( empty($sts_posts) ){
            if($this->check_sherpa_post_exists_for_wc_order($order_id)){
              error_log($order_id. ' is queued');
            }else{
              error_log($order_id. ' is not queued');
            }
          
          // Check if order already logged with Sherpa -> from Orders page
          if ( ( get_post_meta($order_id, '_sherpa_delivery_response') ) || get_post_meta($order_id, '_sherpa_delivery_note') ) {  // If Logged

            // Creating array of orders already SENT to Sherpa
            array_push($orders_already_sent, $order_id);
            error_log($order_id.' is '.get_post_status($order_id));

            $order_already_sent_message = (count($orders_already_sent) > 1) ? ' The deliveries ' : ' The delivery ';

            if(count($orders_already_sent) > 1){
              error_log('Number of orders already sent '.count($orders_already_sent));
              foreach($orders_already_sent as $order_sent_key => $order_sent){
                error_log($order_sent.' already sent, ');
                $last_order_sent = end($orders_already_sent);
                $order_already_sent_message .= "<a href= 'post.php?post=".$order_sent."&action=edit' target=_blank>".$order_sent."</a>";
                
                if ($order_sent_key < count($orders_already_sent) -2){
                  $order_already_sent_message .= ', ';
                } else {
                  if($order_sent != $last_order_sent){
                    $order_already_sent_message .= ' and ';
                  }
                }

              }
            }else{
              $order_already_sent_message .= "<a href= 'post.php?post=".$order_id."&action=edit' target=_blank>".$order_id."</a>";
            }
            $order_already_sent_message .= (count($orders_already_sent) > 1) ? ' have ' : ' has ';
            $order_already_sent_message .= "already been logged with Sherpa.";
          } 
          
          // If not logged and no sts order found for order
          if (!($this->check_sherpa_post_exists_for_wc_order($order_id)) && (!(get_post_meta($order_id, '_sherpa_delivery_response')) ) && ( !(get_post_meta($order_id, '_sherpa_delivery_note')) ) ){

						update_post_meta ($order_id, 'check_post' ,true);
						$original_post = get_post(get_the_ID());
						$new_post = array(
							'post_type' => 'send_to_sherpa',
							'post_status' => 'publish',
							'post_password' => '',
							// 'post_title' => $original_post->ID,
							'post_parent' => $order_id,
						);
						wp_insert_post($new_post);
							
					} else
          //if (  ($this->check_sherpa_post_exists_for_wc_order($order_id)) )
           { // Check if order in queue
            /*
             Creating array of orders already in Sherpa queue and not completed yet
            */
            if ($this->check_sherpa_post_exists_for_wc_order($order_id)  &&  ( !(get_post_meta($order_id, '_sherpa_delivery_response')) || !(get_post_meta($order_id, '_sherpa_delivery_note')) ) ) {
              array_push($orders_already_in_queue, $order_id);
            }

            // Start creating a message when the last element is reached
            if ($order_id == end($object_ids) && count($orders_already_in_queue) > 0) {
              error_log('Last element reached '.$order_id);
              $message = null;
              $already_queued_message = (count($orders_already_in_queue) == 1) ? 'The order ' : 'The orders ';  
              
              foreach ($orders_already_in_queue as $order_in_queue_key => $order_in_queue) {
                $last_element = end($orders_already_in_queue);
                $already_queued_message .= "<a href= 'post.php?post=".$order_in_queue."&action=edit' target=_blank>".$order_in_queue."</a>";
                if ($order_in_queue_key < count($orders_already_in_queue) -2) {
                  $already_queued_message .= ', ';
                } elseif ($order_in_queue != $last_element) {
                  $already_queued_message .= ' and ';
                }
              }

              $already_queued_message .= (count($orders_already_in_queue) > 1) ? ' are ' : ' is ';
              $already_queued_message .= "already in the sherpa delivery queue.";

            }

            if (!empty($order_already_sent_message)) {
              $message = $order_already_sent_message;
            }

            if (!empty($already_queued_message)) {
              if (isset($message)) {
                $message .= ' '.$already_queued_message;
              } else {
                $message = $already_queued_message;
              }
            }
            
              // if(isset($message))  
						  // set_transient( 'sherpa_order_notice', $message, 3600);
            
          }
					
			}
      if (isset($message)) { 
       set_transient( 'sherpa_order_notice', $message, 3600);
      } elseif (isset($order_already_sent_message)) {
        set_transient( 'sherpa_order_notice', $order_already_sent_message, 3600);
      }
      wp_redirect( admin_url( '/edit.php?post_type=send_to_sherpa' ) ); // Redirect to STS page after looping through orders
      exit;
		} 
		return $redirect;
	}
	
	
		// Sort package rates
		public function sherpa_woocommerce_package_rates($rates, $package) {
			uasort($rates, function ($a, $b) {
				return ($a->id > $b->id) ? 1 : -1;
			});
			return $rates;
		}
		
		/*
			WooCommerce send to sherpa admin notices
		*/
		public function sherpa_admin_notice_action() {
			global $current_screen;
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
				$class = 'notice notice-success is-dismissible';
				if (!empty(get_transient( 'sherpa_admin_notice' ))){
					$message = __( get_transient( 'sherpa_admin_notice' ), 'sample-text-domain' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
					delete_transient( 'sherpa_admin_notice' );
				}
			}
		}

		public function sherpa_admin_notice_error_action() {
			global $current_screen;
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
				$class = 'notice notice-error';
				if (!empty(get_transient( 'sherpa_admin_notice_error' ))){
					$message = __( get_transient( 'sherpa_admin_notice_error' ), 'sample-text-domain' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
					delete_transient( 'sherpa_admin_notice_error' );
				}
			}
		}

		public function sherpa_order_notice_action() {
			$class = 'notice notice-error';
			
			if (!empty(get_transient( 'sherpa_order_notice' ))){
				$message = __( get_transient( 'sherpa_order_notice' ), 'sample-text-domain' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message  ); 
				
				delete_transient( 'sherpa_order_notice' );
			}
		}

		// Add shipping method to order meta
		public function add_order_shipping_method_meta($order_id) {
			$shipping_method = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : WC()->session->get('sherpa_chosen_shipping_methods');
			update_post_meta($order_id, 'sherpa_shipping_method', $shipping_method);

			if (WC()->session->get('sherpa_delivery_time_plain_text')) {
				update_post_meta($order_id, '_sherpa_delivery_time_plain_text', WC()->session->get('sherpa_delivery_time_plain_text'));

				add_post_meta($order_id, '_sherpa_delivery_ready_at', WC()->session->get('sherpa_prefer_ready_at_date'));

				add_post_meta($order_id, '_sherpa_delivery_deliver_for', WC()->session->get('sherpa_selected_method_option'));
			}
		}

		public function add_free_shipping_label($label, $method) {
			if ($method->cost == 0) {
				$label .= ': Free';
			}
			return $label;
		}

		public function check_if_sherpa_method($shipping_method) {
			if (empty($shipping_method))
				return false;

			$shipping_method = array_shift($shipping_method);
			if (false === strpos($shipping_method, 'service_sameday_') && false === strpos($shipping_method, 'service_later_')) { 
				return false;
      } else {
        return true;
      }
		}

		public function validate_checkout_fields() {
      // Check if default merchant or sherpa shipping method selected
			if (isset($_POST['shipping_method']) && false === $this->check_if_sherpa_method($_POST['shipping_method'])) {
				return;
			}

			$sherpa_method = isset($_POST['sherpa_selected_method']) ? $_POST['sherpa_selected_method'] : '';
			$sherpa_delivery_time = isset($_POST['sherpa_delivery_time']) ? $_POST['sherpa_delivery_time'] : '';
      $sherpa_billing_phone = isset($_POST['billing_phone']) ? $_POST['billing_phone']: '';
      $current_shipping_method = WC()->session->get('chosen_shipping_methods')[0];
			if ($sherpa_method == 'service_later') {
				$ready_at = isset($_POST['sherpa_ready_at']) ? $_POST['sherpa_ready_at'] : '';
				if (!$ready_at) {
					wc_add_notice(__('Please select a delivery date.'), 'error');
				}
			}

      // This should check if sherpa option is selected before asking for delivery time
      $is_sherpa_method = $this->check_if_sherpa_method($_POST['shipping_method']);
			if ($is_sherpa_method && empty($sherpa_delivery_time)) {
				wc_add_notice(__('Please select a delivery time.'), 'error');
      }

		}

		public function settings_notice() {
			$url    = admin_url("admin.php?page=wc-settings&tab=shipping&section=sherpa");
			$notice = __('Sherpa shipping method is not active, you can activate it', 'woocommerce-extension') . ' ' . '<a href="' . $url . '">' . __('here', 'woocommerce-extension') . '</a>.';
			echo "<div class='error'><p><strong>$notice</strong></p></div>";
		}

		public function validate_sherpa_requirements() {
			global $pagenow;

			if ('admin.php' === $pagenow && isset($_GET['section']) && 'sherpa' === $_GET['section']) {
				return;
			}

			$sherpa_settings = get_option("woocommerce_sherpa_settings");
			if (!isset($sherpa_settings['enabled']) || $sherpa_settings['enabled'] != 'yes') {
				add_action('admin_notices', array($this, 'settings_notice'));
			}
		}

		public function add_windows_to_shipping_rate($rate, $args, $method) {
			if (isset($args['windows'])) {
				$rate->add_meta_data('windows', $args['windows']);
			}
			return $rate;
		}

		public function validate_sherpa_checkout() {

			// Check if sherpa is actually active
      if(function_exists('WC')){
        if(WC()->session){
          $sesh = WC()->session;
          $test_method = $sesh->get('chosen_shipping_methods');
        }
      }
			$current_shipping_method = WC()->session->get('chosen_shipping_methods')[0];
			if (false == strpos($current_shipping_method, 'service_later') || false == strpos($current_shipping_method, 'service_later')) {
				return;
			}

			$confObj = new Sherpa_Configurations();

			// Get order shipping address1.
			$address1 = (isset($_POST['shipping_address_1']) && $_POST['shipping_address_1']) ? sanitize_text_field($_POST['shipping_address_1']) : sanitize_text_field($_POST['billing_address_1']);

			// Get order shipping address2.
			$address2 = (isset($_POST['shipping_address_2']) && $_POST['shipping_address_2']) ? sanitize_text_field($_POST['shipping_address_2']) : sanitize_text_field($_POST['billing_address_2']);

			// Get order shipping address2.
			$company = (isset($_POST['shipping_company']) && $_POST['shipping_company']) ? sanitize_text_field($_POST['shipping_company']) : sanitize_text_field($_POST['billing_company']);

			// Get order shipping state.
			$state = (isset($_POST['shipping_state']) && $_POST['shipping_state']) ? sanitize_text_field($_POST['shipping_state']) : sanitize_text_field($_POST['billing_state']);

			// Get order shipping postal code.
			$post_code = (isset($_POST['shipping_postcode']) && $_POST['shipping_postcode']) ? sanitize_text_field($_POST['shipping_postcode']) : sanitize_text_field($_POST['billing_postcode']);

			// Get order shipping country.
			$country = (isset($_POST['shipping_country']) && $_POST['shipping_country']) ? sanitize_text_field($_POST['shipping_country']) : sanitize_text_field($_POST['billing_country']);

			// Get order shipping city.
			$city = (isset($_POST['shipping_city']) && $_POST['shipping_city']) ? sanitize_text_field($_POST['shipping_city']) : sanitize_text_field($_POST['billing_city']);

			// Get Order's Customer First Name.
			$customer_first_name = isset($_POST['billing_first_name']) ? sanitize_text_field($_POST['billing_first_name']) : '';

			// Get Order's Customer Last Name.
			$customer_last_name = isset($_POST['billing_last_name']) ? sanitize_text_field($_POST['billing_last_name']) : '';

			// Get Order's Customer Phone Number.
			$customer_phone_number = isset($_POST['billing_phone']) ? filter_var(sanitize_text_field($_POST['billing_phone']), FILTER_SANITIZE_NUMBER_INT) : '';

			// Get Order's Recipient First Name.
			$recipient_first_name = isset($_POST['shipping_first_name']) ? sanitize_text_field($_POST['shipping_first_name']) : '';

			// Get Order's Recipient Last Name.
			$recipient_last_name = isset($_POST['shipping_last_name']) ? sanitize_text_field($_POST['shipping_last_name']) : '';

			// Get Order Comments
			$order_comments = isset($_POST['order_comments']) ? sanitize_text_field($_POST['order_comments']) : '';

			// Get Order's delivery instructions.
			$delivery_instructions = (strlen($order_comments) > 250) ? substr($order_comments, 0, 250) . '...' : '';
			$delivery_instructions = preg_replace('/\s+/', ' ', trim($delivery_instructions));
			$customer_name = trim($customer_first_name . ' ' . $customer_last_name);
			$recipient_name = trim($recipient_first_name . ' ' . $recipient_last_name);
			$recipient_name = $recipient_name != '' ? 'Recipient: ' . $recipient_name : '';

			$apiRequest = new Sherpa_Api_Request($confObj);
			$apiRequest->setVehicleId($confObj->getData('vehicle_id'))
				->setDestination($country, $post_code, $state, $city, $address1, $address2, $company)
				->setRecipientName($recipient_name)
				->setRecipientPhone($customer_phone_number)
				->setDeliveryInstructions($delivery_instructions)
				->setNotes('')
				->setItemDescription('Test Item Description')
				->setOrderId('23423');

			// @todo check methods form config
			try {
				$delivery = $apiRequest->validateDelivery();
			} catch (Exception $e) {
				$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Validate Exception -> ' . print_r($e->getMessage(), true));
				throw new Exception($e->getMessage());
			}
		}

		// Set shipping description
		public function order_shipping_description($items) {

			global $post;
			global $wp;
			global $woocommerce;

			$order_id = isset($post->ID) ? $post->ID : false;

			// Order success page
			if (is_checkout() && !empty($wp->query_vars['order-received'])) {
				$order_id = $wp->query_vars['order-received'];
			} else if (isset($post->post_name) && $post->post_name == 'checkout') {
				$order_id = $woocommerce->session->order_awaiting_payment;
			}

			if ($order_id) {
				foreach ($items as $item_id => $item) {

					$item_method_id = isset($item['item_meta']['method_id'][0]) ? $item['item_meta']['method_id'][0] : '';

					if (
						$item['type'] == 'shipping' && $item_method_id && in_array($item_method_id, array(
							Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE,
							Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE,
							Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR,
							Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR,
							Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR,
							Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR,
							Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR,
							Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR,
							Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME,
							Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME
						))
					) {
						$confObj = new Sherpa_Configurations();
						$commonObj = new Sherpa_Common($confObj);
						$shipping_description = $commonObj->getMethodTitleAdmin(
							$item['item_meta']['method_id'][0],
							get_post_meta($order_id, '_sherpa_ready_at', true)
						);
						$items[$order_id]['name'] = $shipping_description;
					}
				}
			}

			return $items;
		}

		/**
		 * Create a new sherpa delivery in sherpa console.
		 *
		 * @param int $order_id
		 *  Arguments for sherpa delivery.
		 * @return delivery_is A new delivery id from sherpa.
		 */
		public function create_new_sherpa_delivery($orderId) {
      /** This function is a callback fired when 
      * woocommerce pre payment function is executed
      * This function seems to be called during payment renewals
      */

      //Get the order object using order_id
      $order_object = wc_get_order($orderId);

      // Check if order object exists and is valid
      if($order_object){
        // Loop through the order data and get product
        foreach($order_object->get_items() as $item_id => $item){
          $productObject = $order_object->get_product_from_item($item);

          // Convert product object to array
          $productArray = $productObject->get_data();

          // Fetch product meta data 
          foreach($productArray['meta_data'] as $product_meta_key => $product_meta_object){
            //Convert meta object to array
            $product_meta_array = $product_meta_object->get_data();

            
            $sherpaProduct = true;        // Check if sherpa is enabled for this product
            $virtualProduct = false;      // Check if product is virtual
            $orderHasSubscriptionId = ($order_object->get_meta('_subscription_renewal')) ? true : false;  // Check if order is subscription (subscription orders don't show up in Woocommerce >> orders tab)

            if($sherpaProduct && !$virtualProduct && !$orderHasSubscriptionId){
              //return;
      //       }
      //     }
      //   }
      // }

      //Fetch products using order_id
      // Check if product is virtual|subscription|shippable
      //FETCH PRODUCTS IN ORDER USING ORDER ID
      //if(product is virtual || product is virtual&&subscription){
        // DONT RUN THIS FUNCTION
      //}else{
        // RUN THIS FUNCTION
      //}

			global $wpdb;
			global $wp_query;
			global $woocommerce;
			global $post;
			$confObj = new Sherpa_Configurations();
			$commonObj = new Sherpa_Common($confObj);
			$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Create Delivery -> ' . $orderId);

			if ($orderId) {
				$order = wc_get_order($orderId);
				if (in_array($order->get_status(), array('wc-failed', 'wc-cancelled'))) {
					$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Sherpa Create Delivery failed : Order Status -> ' . $order->get_status());
					return FALSE;
				}

				// get order shipping address1.
				$address1 = get_post_meta($orderId, '_shipping_address_1', true);

				// get order shipping address2.
				$address2 = get_post_meta($orderId, '_shipping_address_2', TRUE);

				// get order shipping address2.
				$company = get_post_meta($orderId, '_shipping_company', TRUE);

				// get order shipping state.
				$state = get_post_meta($orderId, '_shipping_state', TRUE);

				// get order shipping postal code.
				$post_code = get_post_meta($orderId, '_shipping_postcode', TRUE);

				// get order shipping country.
				$country = get_post_meta($orderId, '_shipping_country', TRUE);

				// get order shipping city.
				$city = get_post_meta($orderId, '_shipping_city', TRUE);

				// get Order's Customer First Name.
				$customer_first_name = get_post_meta($orderId, '_billing_first_name', TRUE);

				// get Order's Customer Last Name.
				$customer_last_name = get_post_meta($orderId, '_billing_last_name', TRUE);

				// get Order's Customer Phone Number.
				$customer_phone_number = get_post_meta($orderId, '_billing_phone', TRUE);

				// get Order's Recipient First Name.
				$recipient_first_name = get_post_meta($orderId, '_shipping_first_name', TRUE);

				// get Order's Recipient Last Name.
				$recipient_last_name = get_post_meta($orderId, '_shipping_last_name', TRUE);

				// get Order's delivery instructions.
				$delivery_instructions = (strlen($order->get_customer_note()) > 250) ? substr($order->get_customer_note(), 0, 250) . '...' : $order->get_customer_note();
				$delivery_instructions = preg_replace('/\s+/', ' ', trim($delivery_instructions));

				$sherpaSelectedMethod           = WC()->session->get('sherpa_selected_method');
				$sherpa_chosen_shipping_methods = get_post_meta($orderId, 'sherpa_shipping_method', TRUE);

				$sherpaReadyAtDate = $commonObj->getCreateReadyAtDate();
				$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Create Delivery $sherpaReadyAtDate -> ' . $sherpaReadyAtDate);
				$methodOptionServiceAt = Sherpa_Sherpa::OPTION_SERVICE_ANYTIME;
				$methodOptionService2hrs = Sherpa_Sherpa::OPTION_SERVICE_2_HOUR;
				$methodOptionService4hrs = Sherpa_Sherpa::OPTION_SERVICE_4_HOUR;
				$methodOptionService1hr = Sherpa_Sherpa::OPTION_SERVICE_1_HOUR;
				$methodOptionServiceBulkRate = Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE;

				switch ($sherpa_chosen_shipping_methods[0]) {
					case $sherpaSelectedMethod . '_' . $methodOptionServiceAt:
						$deliveryOption = 2;
						break;

					case $sherpaSelectedMethod . '_' . $methodOptionService4hrs:
						$deliveryOption = 1;
						break;

					case $sherpaSelectedMethod . '_' . $methodOptionService2hrs:
						$deliveryOption = 0;
						break;

					case $sherpaSelectedMethod . '_' . $methodOptionService1hr:
						$deliveryOption = 5;
						break;

					case $sherpaSelectedMethod . '_' . $methodOptionServiceBulkRate:
						$deliveryOption = 6;
						break;

					default:
						$deliveryOption = Sherpa_Sherpa::DELIVERY_OTHER_THAN_SHERPA;
						break;
				}

				$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Create Delivery $deliveryOption -> ' . $deliveryOption);
				$itemDescription = 'Order #: ' . $orderId . ' - Sender: ' . $customer_first_name . ' ' . $customer_last_name . ' - ' . $customer_phone_number . ' - ';
				$customer_name = trim($customer_first_name . ' ' . $customer_last_name);
				$recipient_name = trim($recipient_first_name . ' ' . $recipient_last_name);
				$recipient_name = $recipient_name != '' ? 'Recipient: ' . $recipient_name : '';

				// get order items from Order w.r.t Order Id.
				$order = new WC_Order($orderId);
				$items = $order->get_items();

				$itemsCount = count($items);
				$i = 1;
				$priceTotal = 0;

				// adding product name to items description.
				foreach ($items as $key => $val) {
					$itemDescription .= $items[$key]['name'];
					if ($i == $itemsCount) {
						$itemDescription .= ".";
						$priceTotal += $items[$key]['line_total'];
						break;
					} else {
						$itemDescription .= " ,";
						$priceTotal += $items[$key]['line_total'];
						$i++;
					}
				}

				// truncate at 250 character length
				if (strlen($itemDescription) > 250) {
					$itemDescription = substr($itemDescription, 0, 248);
					$itemDescription .= '...';
				}

				if (!empty($confObj->getItemDescription())) {
					$itemDescription = $confObj->getItemDescription();
				}

				try {
					// don't create Delivery for other than sherpa delivery and flat rate pickup
					if ($deliveryOption != Sherpa_Sherpa::DELIVERY_OTHER_THAN_SHERPA && $this->create_shipment()) {
						$apiRequest = new Sherpa_Api_Request($confObj);
						$apiRequest->setDeliveryOption($deliveryOption)
							->setExpectedItemPrice($priceTotal)
							->setVehicleId($confObj->getData('vehicle_id'))
							->setItemDescription($itemDescription)
							->setDestination($country, $post_code, $state, $city, $address1, $address2, $company)
							->setOrderId($orderId)
							->setRecipientName($recipient_name)
							->setRecipientPhone($customer_phone_number)
							->setNotes('')
							->setDeliveryInstructions($delivery_instructions)
							->setReadyAt($sherpaReadyAtDate);

						// @todo check methods form config
						
						$delivery = $apiRequest->createNewDelivery();
						$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, '$delivery object -> ' . print_r($delivery, TRUE));
					}

					//order note when shipment not created -- if delivery is not sent to Sherpa 
					if ($deliveryOption != Sherpa_Sherpa::DELIVERY_OTHER_THAN_SHERPA && !($this->create_shipment())) {
						$ready_at = get_post_meta($orderId, '_sherpa_delivery_ready_at', true);
						$ready_at = new DateTime($ready_at, new DateTimeZone(wp_timezone_string()));
						$delivery_time_plain_text = get_post_meta($orderId, '_sherpa_delivery_time_plain_text', true);
						$order->add_order_note(sprintf(
							'<strong>Order not shipped!</strong> Customer selected a Sherpa Delivery rate for this order <strong> %s between %s </strong> however it has not been sent. Please log the delivery with Sherpa manually.',
							$ready_at->format('d/m/Y'),
							$delivery_time_plain_text
						));
					}

					// prepareAndAddOrderNote
					if (isset($delivery) && !empty($order)) {
						$confObj->getLogger()->add(
							Sherpa_Sherpa::LOG_FILE_NAME,
							'include_tracking_link -> ' . print_r($this->include_tracking_link())
						);
						$this->addOrderMeta($delivery, $order);
						$this->prepareAndAddOrderNote($delivery, $order);
					}

					if (isset($delivery->delivery_tracking)) {

						$delivery_id  = $delivery->id;
						$tracking_url = '';

						// unset variables in session
						WC()->session->set('sherpa_ready_at_date', FALSE);
						WC()->session->set('sherpa_selected_method', FALSE);
						WC()->session->set('sherpa_selected_method_option', FALSE);

						// save ready at date
						add_post_meta($orderId, '_sherpa_ready_at', $sherpaReadyAtDate, TRUE);

						// saving delivery id from sherpa console.
						$sherpa_delivery_id = add_post_meta($orderId, '_sherpa_delivery_id', $delivery->id, TRUE);

						if (!$sherpa_delivery_id)
							throw new Exception("Sherpa Delivery Id insertion failed");

						// sherpa order tracking
						// saving delivery tracking info.
						if (!$confObj->getData("shipment")) {
							$token = add_post_meta($orderId, '_sherpa_order_tracking', $delivery->delivery_tracking->token, TRUE);

							if (!$token)
								throw new Exception("Sherpa order tracking insertion failed");

							$trackingUrl  = add_post_meta($orderId, '_sherpa_order_tracking_url', $delivery->delivery_tracking->url, TRUE);

							if (!$trackingUrl) {
								throw new Exception("Sherpa order tracking URL insertion failed");
							}
						}
					}
				} catch (Exception $e) {
					$confObj->getLogger()->add(
						Sherpa_Sherpa::LOG_FILE_NAME,
						'Exception Message -> ' . $e->getMessage()
					);
					$confObj->getLogger()->add(
						Sherpa_Sherpa::LOG_FILE_NAME,
						'Exception -> ' . $e->getTraceAsString()
					);
					return false;
				}
			} else {
				$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Message -> Order ID not found');
			}
		}
  }
}
}
}

		public function addOrderMeta($delivery, $order) {
			if (empty($delivery) || empty($order))
				return;

			add_post_meta(
				$order->get_id(),
				'_sherpa_delivery_ready_at',
				$delivery->ready_at,
				true
			);

			add_post_meta(
				$order->get_id(),
				'_sherpa_delivery_deliver_for',
				$delivery->deliver_for,
				true
			);
		}

		/**
		 * Prepares a note and adds it to the the order.
		 */
		public function prepareAndAddOrderNote($delivery, $order) {

			if (empty($delivery) || empty($order))
				return;

			$type = $delivery->type;
			$delivery_tracking = $delivery->delivery_tracking;
			$delivery_option = $delivery->delivery_option;
			$pickup_address = $delivery->pickup_address;
			$delivery_address = $delivery->delivery_address;
			$open_at = $delivery->open_at;
			$ready_at = $delivery->ready_at;
			$deliver_for = $delivery->deliver_for;
			$ready_at = new DateTime($ready_at, new DateTimeZone(wp_timezone_string()));
			$deliver_for = new DateTime($deliver_for, new DateTimeZone(wp_timezone_string()));
			$delivery_time_plain_text = get_post_meta($order->get_id(), '_sherpa_delivery_time_plain_text', true);

			// Note
			$ready_at_html = '';
			$window_html = '';
			$tracking_link_html = '';

			//If shipment is created -- delivery sent to sherpa
			if (!empty($ready_at) && $this->create_shipment()) {
				$ready_at_html .= 'Order has been sent to Sherpa for delivery on %s';
				$ready_at_html = sprintf($ready_at_html, $ready_at->format('d/m/Y'));

				if (!empty($delivery_time_plain_text)) {
					$window_html .= ' between %s';
					$window_html = sprintf(
						$window_html,
						$delivery_time_plain_text
					);
				}

				if (!empty($delivery->delivery_tracking->url)) {
					$tracking_link_html .= ' <a href="%s" target="_blank">Track delivery here</a>.';
					$tracking_link_html = sprintf($tracking_link_html, $delivery->delivery_tracking->url);
				}

				$delivery_note = sprintf(
					'%s%s.%s',
					$ready_at_html,
					$window_html,
					$tracking_link_html
				);

				// Save order note
				add_post_meta($order->get_id(), '_sherpa_delivery_note', $delivery_note, true);

				$order->add_order_note(sprintf(
					'%s%s.%s',
					$ready_at_html,
					$window_html,
					$tracking_link_html
				));
			}
		}

		public function sherpa_woocommerce_cart_shipping_packages($packages) {

			$post_data = array();
			if (isset($_POST['post_data'])) {
				parse_str($_POST['post_data'], $post_data);
			}

			$confObj = new Sherpa_Configurations(true);
			$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Message -> sherpa_woocommerce_cart_shipping_packages');

			// Set variables
			if ($post_data) {
				$sherpa_prefer_ready_at_date = isset($post_data['sherpa_prefer_ready_at']) ? wc_clean($post_data['sherpa_prefer_ready_at']) : WC()->session->get('sherpa_prefer_ready_at_date', NULL);
				$sherpa_selected_method = isset($post_data['sherpa_selected_method']) ? wc_clean($post_data['sherpa_selected_method']) : WC()->session->get('sherpa_selected_method', false);
				$sherpa_ready_at_date = isset($post_data['sherpa_ready_at']) ? wc_clean($post_data['sherpa_ready_at']) : WC()->session->get('sherpa_prefer_ready_at_date', NULL);
			} else {
				$sherpa_prefer_ready_at_date = isset($_POST['sherpa_prefer_ready_at']) ? wc_clean($_POST['sherpa_prefer_ready_at']) : WC()->session->get('sherpa_prefer_ready_at_date', NULL);
				$sherpa_selected_method = isset($_POST['sherpa_selected_method']) ? wc_clean($_POST['sherpa_selected_method']) : WC()->session->get('sherpa_selected_method', false);
				$sherpa_ready_at_date = isset($_POST['sherpa_ready_at']) ? wc_clean($_POST['sherpa_ready_at']) : WC()->session->get('sherpa_prefer_ready_at_date', NULL);
			}

			// Set session variables
			// WC()->session->set( 'sherpa_ready_at_date', $sherpa_ready_at_date );
			WC()->session->set('sherpa_prefer_ready_at_date', $sherpa_prefer_ready_at_date);
			WC()->session->set('sherpa_selected_method', $sherpa_selected_method);

			// Set package
			$packages[0]['sherpa_prefer_ready_at_date'] = $sherpa_prefer_ready_at_date;
			$packages[0]['sherpa_selected_method'] = $sherpa_selected_method;

			return $packages;
		}

		// Adding Sherpa settings link to plugin page
		public function plugin_settings_link($links) {

			if (class_exists('Sherpa_Sherpa')) {
				$sherpSettingPageSlug = Sherpa_Sherpa::SHERPA_SETTING_SLUG;
				$url = get_admin_url() . $sherpSettingPageSlug;
				$settings_link = '<a href="' . $url . '">' . __('Settings', 'sherpa') . '</a>';
				array_unshift($links, $settings_link);
			}

			return $links;
		}

		private function getNextAvailableReadyAt() {
			$sherpa_delivery_operating_day = get_option('sherpa_delivery_settings_operating_day', '1, 2, 3, 4, 5');
			$all_days = array(0, 1, 2, 3, 4, 5, 6);
			$operating_days_array = array_map('trim', explode(',', $sherpa_delivery_operating_day));
			$operating_days = array();

			foreach ($operating_days_array as $operating_day) {
				if ($operating_day == 7) {
					$operating_day = 0;
				}
				$operating_days[] = $operating_day;
			}

			$disabled_dates = array_diff($all_days, $operating_days);
			$next_available = '';
			$current_time = new DateTime('now', new DateTimeZone(wp_timezone_string()));
			$today_date = $current_time->format('Y-m-d');
			$next_date = $today_date;

			// Check availability for next 100 days
			$index = 0;
			while ($index < 100 && !$next_available) {
				$next_date = date('Y-m-d', strtotime($next_date . ' +1 day'));
				$dotw = (int) date('w', strtotime($next_date));
				if (!in_array($dotw, $disabled_dates)) {
					$next_available = $next_date;
					break;
				}

				$index++;
			}

			return $next_available;
		}

		public function sherpa_woocommerce_update_order_review($post_data) {

			$confObj = new Sherpa_Configurations(true);
			$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Message -> sherpa_woocommerce_update_order_review' . print_r($post_data, true));

			$method = '';
			$selected_method_option = '';
			parse_str($post_data, $post);

			if (isset($post['sherpa_delivery_time_plain_text'])) {
				WC()->session->set('sherpa_delivery_time_plain_text', trim(esc_sql($post['sherpa_delivery_time_plain_text'])));
			}

			if (isset($post['sherpa_estimate_method_select'])) {
				$method = $post['sherpa_estimate_method_select'];
				if (isset($post['shipping_method']) && $post['shipping_method']) {
					foreach ($post['shipping_method'] as $methodOption) {
						$selected_method_option = isset($post['sherpa_method_option_' . $methodOption])
							? $post['sherpa_method_option_' . $methodOption] : NULL;
					}
				}
			}

			$sherpa_chosen_shipping_methods = WC()->session->get('sherpa_chosen_shipping_methods');
			if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
				foreach ($_POST['shipping_method'] as $counter => $value) {
					$sherpa_chosen_shipping_methods[$counter] = wc_clean($value);
				}
			}

			$sherpa_prefer_ready_at_date = isset($post['sherpa_prefer_ready_at']) ? wc_clean($post['sherpa_prefer_ready_at'])  : WC()->session->get('sherpa_prefer_ready_at_date', false);

			if (isset($method) && 'service_later' == $method && $sherpa_prefer_ready_at_date == date('Y-m-d')) {
				$sherpa_prefer_ready_at_date = $this->getNextAvailableReadyAt();
			}

			// Set session variables
			WC()->session->set('sherpa_prefer_ready_at_date', $sherpa_prefer_ready_at_date);
			WC()->session->set('sherpa_chosen_shipping_methods', $sherpa_chosen_shipping_methods);
			WC()->session->set('sherpa_selected_method', $method);
			WC()->session->set('sherpa_selected_method_option', $selected_method_option);
			WC()->session->set('sherpa_address_error', false);

			return $post_data;
		}

		public static function sherpa_woocommerce_update_shipping_method() {

			$confObj = new Sherpa_Configurations(true);
			$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Message -> sherpa_woocommerce_update_shipping_method');

			check_ajax_referer('update-shipping-method', 'security');

			if (!defined('WOOCOMMERCE_CART')) {
				define('WOOCOMMERCE_CART', true);
			}

			// Get variables
			$method = WC()->session->get('sherpa_selected_method', false);
			$selected_method_option = WC()->session->set('sherpa_selected_method_option', false);

			// Extending with our post request
			if (isset($_POST['sherpa_selected_method_option'])) {
				$selected_method_option = wc_clean($_POST['sherpa_selected_method_option']);
			}

			if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
				foreach ($_POST['shipping_method'] as $counter => $value) {
					$sherpa_chosen_shipping_methods[$counter] = wc_clean($value);
				}
			}

			WC()->session->set('sherpa_chosen_shipping_methods', $sherpa_chosen_shipping_methods);
			WC()->session->set('sherpa_selected_method_option', $selected_method_option);

			// Recalculate
			WC()->cart->calculate_totals();

			woocommerce_cart_totals();

			wp_die();
		}

		public function sherpa_woocommerce_locate_template($template, $template_name, $template_path) {

			global $woocommerce;

			$_template = $template;
			if (!$template_path) {
				$template_path = $woocommerce->template_url;
			}

			$theme_override = false;

			// Sherpa plugin path to template files
			$plugin_path = $this->sherpa_plugin_path() . '/templates/frontend/';

			$current_theme = sanitize_title(wp_get_theme());
			$current_theme = str_replace('-child', '', $current_theme);

			if ($current_theme) {
				$theme_s_path = $plugin_path . $template_name;
				$theme_s_path = str_replace('.php', "-$current_theme.php", $theme_s_path);
				if (file_exists($theme_s_path)) {
					$template       = $theme_s_path;
					$theme_override = true;
				}
			}

			if (!$theme_override) {
				if (file_exists($plugin_path . $template_name)) {
					$template = $plugin_path . $template_name;
				}
			}

			// Use default template
			if (!$template) {
				$template = $_template;
			}

			// Return Sherpa plugin template
			return $template;
		}

		public function sherpa_plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		public function sherpa_woocommerce_shipping_init() {
			include_once('includes/Settings.php');
		}

		public function sherpa_woocommerce_shipping_methods($methods) {
			$methods['sherpa'] = 'Sherpa_Settings';
			return $methods;
		}

		public function sherpa_test_credentials_callback() {
			$username = isset($_POST['sherpa_credentials_account']) ? sanitize_text_field($_POST['sherpa_credentials_account']) : '';;
			$password = isset($_POST['sherpa_credentials_password']) ? sanitize_text_field($_POST['sherpa_credentials_password']) : '';
			$sandbox  = (isset($_POST['sherpa_credentials_sandbox']) && is_numeric($_POST['sherpa_credentials_sandbox'])) ? $_POST['sherpa_credentials_sandbox']  : 0;

			$has_error = true;

			try {
				$result   = array();
				$request  = new Sherpa_Api_Request(new Sherpa_Configurations(false, false));
				$response = $request->setUsername($username)->setPassword($password)->setSandbox($sandbox)->getVerification();

				if ($response) {
					$has_error = false;
					$result['alert_class'] = 'successMessage';
					$result['message']     = 'Connected successfully';
				} else {
					$has_error = true;
					$result['alert_class'] = 'apiErrorMessage';
					$result['message']     = 'The Sherpa username and password that you entered are incorrect.';
				}
			} catch (Exception $e) {
				$has_error = true;
				$result['alert_class'] = 'error-msg';
				$result['message']     = $e->getMessage();
			}

			// Save settings
			if (isset($_POST['button']) && 'test-save' === trim($_POST['button']) && false === $has_error) {
				$has_error = false;
				SherpaLogistics::sherpa_save_credentials();
				$result['alert_class'] = 'successMessage';
				$result['message']     = 'Connected successfully & settings saved';
			}

			// Output the result as JSON encoded
			echo json_encode($result);
			wp_die();
		}

		private static function sherpa_save_credentials() {
			foreach ($_POST as $po => $value) {
				switch ($po) {
					case 'sherpa_credentials_account':
					case 'sherpa_credentials_password':
					case 'sherpa_credentials_sandbox':
						update_option(trim($po), sanitize_text_field($value), 1);
						break;

					default:
						break;
				}
			}
			$confObj = new Sherpa_Configurations();
			$apiRequest = new Sherpa_Api_Request($confObj);
			$user = $apiRequest->getUserDetails();
			$user_details = json_decode(json_encode($user), true);
			$flat_rate_enabled = $user_details['user']['flat_rate_enabled'] ? "1" : "0";
			$express_rate_enabled = $user_details['user']['express_rate_enabled'] ? "1" : "0";
			$bulk_rate_enabled = $user_details['user']['bulk_rate_enabled'] ? "1" : "0";
			$sdd_rate_enabled = $user_details['user']['sdd_rate_enabled'] ? "1" : "0";

      update_option('flat_rate_enabled', $flat_rate_enabled, 1); //gets saved in sherpa_options table
			update_option('service_1hr_enabled', $express_rate_enabled, 1); //gets saved in sherpa_options table
			update_option('service_bulk_rate_enabled', $bulk_rate_enabled, 1); //gets saved in sherpa_options table
			update_option('service_at_enabled', $sdd_rate_enabled, 1); //gets saved in sherpa_options table

			$services = get_option('woocommerce_sherpa_settings', array());
			$services['services']['service_sameday']['service_1hr']['enabled'] = $express_rate_enabled;
			$services['services']['service_sameday']['service_bulk_rate']['enabled'] = $bulk_rate_enabled;
			$services['services']['service_sameday']['service_at']['enabled'] = $sdd_rate_enabled;

			$services['services']['service_later']['service_1hr']['enabled'] = $express_rate_enabled;
			$services['services']['service_later']['service_bulk_rate']['enabled'] = $bulk_rate_enabled;
			$services['services']['service_later']['service_at']['enabled'] = $sdd_rate_enabled;
			
			update_option('woocommerce_sherpa_settings', $services, 1);
		}

		public static function sherpa_admin_validate_callback() {

			$settingsNotes = isset($_POST['sherpa_settings_notes']) ? sanitize_text_field($_POST['sherpa_settings_notes']) : '';
			$deliveryRate = isset($_POST['sherpa_settings_delivery_rates']) ? sanitize_text_field($_POST['sherpa_settings_delivery_rates']) : false;

			// throw new Exception for notes length
			if (strlen($settingsNotes) > 250) {
				throw new Exception('Notes length should be less than 250 characters.');
			}

			// apply validation for deliveryRates
			if ($deliveryRate) {
				switch ($deliveryRate) {
					case Sherpa_Sherpa::DELIVERY_RATE_SHERPA:
					case Sherpa_Sherpa::DELIVERY_RATE_MARGIN:
						// do nothing
						break;

					case Sherpa_Sherpa::DELIVERY_RATE_FLAT:
						foreach ($_POST as $po => $values) {
							switch ($po) {
								case 'sherpa_settings_flat_rate_1_hour':
								case 'sherpa_settings_flat_rate_2_hour':
								case 'sherpa_settings_flat_rate_4_hour':
								case 'sherpa_settings_flat_rate_same_day':
								case 'sherpa_settings_flat_rate_bulk_rate':
									if (is_array($values) && count($values) > 0) {
										$distances = array();
										foreach ($values as $value) {
											$distance_group = (isset($value['distance_group']) && is_numeric($value['distance_group'])) ? $value['distance_group'] : 0;
											$price = (isset($value['price']) && is_numeric($value['price'])) ? (float) $value['price'] : 0;

											if ($distance_group && in_array($distance_group, $distances)) {
												$error_string = self::flat_rate_error_string($po);
												throw new Exception('Flat rate for ' . $error_string . ' has repeated distances.');
											} else {
												$distances[] = $distance_group;
												if ($price < 0) {
													$error_string = self::flat_rate_error_string($po);
													throw new Exception('Flat rate for ' . $error_string . ' should not be less than 0.');
												}
											}
										}
									} else {
                    $flat_rate_type = str_replace('sherpa_settings', '', $po);
                    $flat_rate_type = str_replace('_', ' ', $flat_rate_type);
										throw new Exception('At least one option should be selected for '.$flat_rate_type);
									}
									break;
							}
						}
						break;

					default:
						break;
				}
			}
		}

		// Explode delivery name to error message
		public static function flat_rate_error_string($delivery_key) {
			$key_parts = explode('_', $delivery_key);

			// Error message
			$error_message =  implode(' ', array_filter(array(
				isset($key_parts[4]) ? $key_parts[4] : '',
				isset($key_parts[5]) ? $key_parts[5] : '',
				'delivery'
			)));

			return $error_message;
		}

		public function sherpa_admin_save_callback() {
			if (isset($_POST) && count($_POST)) {

				try {
					$result = self::sherpa_admin_validate_callback();

					// Insert in to wp_option table
					foreach ($_POST as $po => $value) {
						switch ($po) {
							case 'sherpa_settings_flat_rate_1_hour':
							case 'sherpa_settings_flat_rate_2_hour':
							case 'sherpa_settings_flat_rate_4_hour':
							case 'sherpa_settings_flat_rate_same_day':
							case 'sherpa_settings_flat_rate_bulk_rate':
								$value = $value;
								if (is_array($value)) {
									foreach ($value as $key => $value_2) {
										if (isset($value_2['price']) && isset($value_2['distance_group'])) {
											if (!$value_2['price']) {
												$value[$key]['price'] = "0.00";
											}
										}
									}
								}
								break;

							case 'sherpa_settings_add_margin':
								$value = abs($value);
								break;

							case 'sherpa_settings_authority_to_leave':
							case 'sherpa_settings_send_sms':
							case 'sherpa_settings_specified_recipient':
							case 'sherpa_settings_contains_alcohol':
							case 'sherpa_settings_contains_fragile_items':
							case 'sherpa_settings_contains_scheduled_medication':
							case 'sherpa_settings_contains_tobacco':
							case 'sherpa_settings_requires_hi_vis_vest':
								$value = intval($value);
								break;

							default:
								if (is_array($value)) {
									$value = implode(',', $value);
								} else {
									$value = sanitize_text_field($value);
								}
								break;
						}

						update_option($po, $value, 1);
					}

					$this->adjustDeliveryPreferences();

					$result['message'] = 'Settings saved successfully.';
					$result['class']   = 'successMessage';
				} catch (EXCEPTION $e) {
					$result['message'] = $e->getMessage();
					$result['class']   = 'errorMessage';
				}

				$confObj = new Sherpa_Configurations(true);
				$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Message -> Settings saved -> result ' . print_r($result, true));

				echo json_encode($result);
				wp_die();
			}
		}

		private function adjustDeliveryPreferences() {

			if (!isset($_POST) || empty($_POST))
				return;

			// Delivery prefs
			$delivery_keys = [
				'sherpa_settings_authority_to_leave',
				'sherpa_settings_send_sms',
				'sherpa_settings_specified_recipient',
				'sherpa_settings_contains_alcohol',
				'sherpa_settings_contains_fragile_items',
				'sherpa_settings_contains_scheduled_medication',
				'sherpa_settings_contains_tobacco',
				'sherpa_settings_requires_hi_vis_vest',
			];

			// Remove unchecked items
			foreach (array_diff($delivery_keys, array_keys($_POST)) as $delivery_key) {
				delete_option($delivery_key);
			}

			// When Tobacco or Scheduled medication or Specified recipient is selected 'Authority to leave' cannot be selected
			if (array_intersect(array('sherpa_settings_contains_scheduled_medication', 'sherpa_settings_contains_tobacco', 'sherpa_settings_specified_recipient'), array_keys($_POST))) {
				delete_option('sherpa_settings_authority_to_leave');
			}

			// Contains scheduled (prescription) medication -> Check fragile by default
			if (array_intersect(['sherpa_settings_contains_scheduled_medication'], array_keys($_POST))) {
				update_option('sherpa_settings_contains_fragile_items', '1', 1);
			}

			// If checked 'Contains alcohol' -> Check 'Specified recipient' by default
			if (array_intersect(['sherpa_settings_contains_alcohol'], array_keys($_POST))) {
				update_option('sherpa_settings_specified_recipient', '1', 1);
			}
		}

		public function load_sherpa_frontend() {

			// Enqueue default styles
			wp_register_style(
				'sherpa-default',
				plugins_url('assets/css/default.css', SHERPA_PLUGIN_FILE)
			);

			wp_enqueue_style('sherpa-default');

			// Enqueue zebra datepicker
			wp_register_style(
				'sherpa-zebra',
				plugins_url('assets/css/zebra_datepicker/zebra_datepicker.min.css', SHERPA_PLUGIN_FILE)
			);

			wp_enqueue_style('sherpa-zebra');

			wp_register_script(
				'sherpa-zebra',
				plugins_url('assets/js/zebra_datepicker.min.js', SHERPA_PLUGIN_FILE),
				array('jquery')
			);

			wp_enqueue_script('sherpa-zebra');
		}

		public function sherpa_init() {

			// Cart actions
			remove_action('wp_ajax_woocommerce_update_shipping_method', array(
				$this,
				'update_shipping_method'
			));

			remove_action('wc_ajax_update_shipping_method', array(
				$this,
				'update_shipping_method'
			));
		}
public function admin_init($hook_suffix) {

			// Register our stylesheets & scripts only on Sherpa settings page
			if (strstr($hook_suffix, SHERPA_PLUGIN_PAGE_ID)) {

				// Enqueue jQuery UI Slider
				wp_enqueue_script('jquery-ui-slider');

				// Enqueue general admin styles
				wp_register_style(
					'sherpa-admin-ui-css',
					plugins_url('assets/css/sherpa-admin-ui.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-admin-ui-css');

				// Enqueue bootstrap
				wp_register_style(
					'sherpa-bootstrap',
					plugins_url('assets/css/bootstrap.min.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-bootstrap');

				// Enqueue bootstrap grid
				wp_register_style(
					'sherpa-bootstrap-grid',
					plugins_url('assets/css/bootstrap-grid.min.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-bootstrap-grid');

				// Enqueue fontawesome
				wp_register_style(
					'sherpa-fa',
					plugins_url('assets/css/font-awesome.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-fa');

				// Enqueue default styles
				wp_register_style(
					'sherpa-default',
					plugins_url('assets/css/default.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-default');

				// Enqueue popper
				wp_register_script(
					'popper-script',
					plugins_url('assets/js/popper.min.js', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_script('popper-script');
				
				// Enqueue boostrap JS
				wp_register_script(
					'bootstrap-script',
					plugins_url('assets/js/bootstrap.min.js', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_script('bootstrap-script');

				// Enqueue sherpa admin script
				wp_register_script(
					'sherpa-admin',
					plugins_url('assets/js/sherpa-admin.js', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_script('sherpa-admin');
			}
			
			
			/*
				include js and css with zebra_datepicker
			*/
			global $current_screen;
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
				// Enqueue popper css
				wp_register_style(
						'sherpa-adminui',
						plugins_url('assets/css/sherpa-adminui.css', SHERPA_PLUGIN_FILE)
					);
				wp_enqueue_style('sherpa-adminui');
				
				wp_register_style(
					'sherpa-datepicker',
					plugins_url('assets/css/zebra_datepicker/zebra_datepicker.min.css', SHERPA_PLUGIN_FILE)
				);
				wp_enqueue_style('sherpa-datepicker');
				
				wp_enqueue_style('bootstrap-style', '//cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css', array(), '1.0.0', 'all');
				wp_enqueue_script('bootstrap-script', '//cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js', array('jquery'), '1.0.0', true);
				
        // Enqueue popup.js and naming it 'popup-script'
				wp_register_script(
					'popup-script',
					plugins_url('assets/js/popup.js?rand='.rand(), SHERPA_PLUGIN_FILE),
					array('bootstrap-script'),
					'1.0.0',
					true
				);
				$nextavaildatecustom = $this->nextavaildatecustom();

        //Localise script for ajax STS post deletion
        // wp_localize_script('popup-script', 'ajax_delete_sts_orders_obj', array(
        //   'ajax_url' => admin_url('admin-ajax.php'),
        // ));
				
				wp_localize_script('popup-script', 'sherpa', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'sherpa_delivery_settings_operating_day' => $nextavaildatecustom['comma_separated_disabled_dates'],
					'next_available' => $nextavaildatecustom['next_available'],  
					'nonce' => wp_create_nonce('sherpa')
				));
				wp_enqueue_script('popup-script');
				wp_enqueue_script('datepicker-scriptone');
				wp_register_script(
				'datepicker-scripttw',
					plugins_url('assets/js/zebra_datepicker.min.js', SHERPA_PLUGIN_FILE),
				array('jquery')
				);
				wp_enqueue_script('datepicker-scripttw');
			}
		}
		
		
		public function nextavaildatecustom(){
		    
		    
				$sherpa_delivery_operating_day = get_option('sherpa_delivery_settings_operating_day', '1, 2, 3, 4, 5');
				$all_days = array(0, 1, 2, 3, 4, 5, 6);
				$operating_days_array = array_map('trim', explode(',', $sherpa_delivery_operating_day));
				$operating_days = array();

				foreach ($operating_days_array as $operating_day) {
					if ($operating_day == 7) {
						$operating_day = 0;
					}
					$operating_days[] = $operating_day;
				}

				$disabled_dates = array_diff($all_days, $operating_days);
				$comma_separated_disabled_dates = implode(",", $disabled_dates);
				
				
    		
				
				$current_time = new DateTime('now', new DateTimeZone(wp_timezone_string()));
				$next_available = '';
				$today_date = $current_time->format('Y-m-d');
				$next_date = $today_date;
                if($comma_separated_disabled_dates== '0,6' || $comma_separated_disabled_dates== '6'){
                    if (new DateTime() > new DateTime($next_date." 21:00:00")) {
                        # current time is greater than 2010-05-15 16:00:00
                        # in other words, 2010-05-15 16:00:00 has passed
                         $dys = 1;
                    } else {
                         $dys = 0;
                    }
                   
                } else {
                    $dys = 0;
                }
				// Check availability for next 100 days
				$index = 0;
				while ($index < 100 && !$next_available) {
					$next_date = date('Y-m-d', strtotime($next_date . ' +'.$dys.' day'));
					$dotw = (int) date('w', strtotime($next_date));
				
					if (!in_array($dotw, $disabled_dates)) {
						$next_available = $next_date;
						break;
					}
					$index++;
				}
				
				return array(
				    'comma_separated_disabled_dates' => $comma_separated_disabled_dates,
				    'next_available' => $next_available,
			    );
		    
		    
		}

		public function install() {

			global $wpdb;
			global $my_plugin_db_version;

			$sherpa_entity_options = $wpdb->prefix . 'sherpa_entity_options';
			$charset_collate = $wpdb->get_charset_collate();

			// add ship_via_sherpa product attribute
			// add to quote table 'sherpa_ready_at_date' type 'datetime'
			// add to quote table, 'sherpa_selected_method' type 'text'
			// add to quote table, 'sherpa_selected_method_option' type 'text'
			// add to quote table, 'sherpa_prefer_ready_at_date' type 'datetime'
			// add to order table, 'sherpa_delivery_id' type 'integer'

			if ($wpdb->get_var("SHOW TABLES LIKE '$sherpa_entity_options'") !== $sherpa_entity_options) {
				$sql2 = "CREATE TABLE IF NOT EXISTS $sherpa_entity_options (
`option_id` int(10) NOT NULL AUTO_INCREMENT,
`entity_id` int(10) NOT NULL,
`option_key` varchar(100) NOT NULL,
`option_value` varchar(100) NOT NULL,
PRIMARY KEY (`option_id`)
) $charset_collate;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql2);

				$wpdb->insert($sherpa_entity_options, array(
					'entity_id' => 1,
					'option_key' => 'Delivery Immediately',
					'option_value' => 'Sherpa Now'
				));

				$wpdb->insert($sherpa_entity_options, array(
					'entity_id' => 1,
					'option_key' => 'Schedule for Later',
					'option_value' => 'Sherpa Later'
				));
			}

			add_option('my_plugin_db_version', $my_plugin_db_version);
		}

		public function uninstall() {
			$this->define('WP_UNINSTALL_PLUGIN', true);
			include __DIR__ . '/uninstall.php';
		}

		/**
		 * Define WC Constants.
		 */
		private function define_constants() {
			$this->define('SHERPA_PLUGIN_ID', 'sherpa');
			$this->define('SHERPA_PLUGIN_PAGE_ID', 'wc-sherpa');
			$this->define('SHERPA_PLUGIN_FILE', __FILE__);
			$this->define('SHERPA_PLUGIN_DIR', plugin_dir_path(__FILE__));
			$this->define('SHERPA_PLUGIN_BASENAME', plugin_basename(__FILE__));
			$this->define('SHERPA_VERSION', $this->version);
			$this->define('SHERPA_ROUNDING_PRECISION', 4);
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string $name
		 * @param string|bool $value
		 */
		private function define($name, $value) {
			if (!defined($name)) {
				define($name, $value);
			}
		}

		/**
		 * What type of request is this?
		 * string $type ajax, frontend or admin.
		 *
		 * @return bool
		 */
		private function is_request($type) {

			switch ($type) {
				case 'admin':
					return is_admin();
					break;

				case 'ajax':
					return defined('DOING_AJAX');
					break;

				case 'cron':
					return defined('DOING_CRON');
					break;

				case 'frontend':
					return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
					break;
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			if ($this->is_request('admin') || $this->is_request('frontend')) {
				include_once('includes/Autoloader.php');
			}
		}

		// AJAX Implementation
		public function sherpa_deliver_options_ajax_callback() {

			// @todo - Remove unused variable
			global $wpdb;
			$result = array();

			$service_1h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_sameday_delivery_options_service_1hr', '')
			);

			$service_2h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_sameday_delivery_options_service_2hr', '')
			);

			$service_4h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_sameday_delivery_options_service_4hr', '')
			);

			$service_at = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_sameday_delivery_options_service_at', '')
			);

			$service_bulk_rate = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_sameday_delivery_options_service_bulk_rate', '')
			);

			$result[] = update_option('sherpa_settings_sameday_delivery_options_service_1hr', $service_1h, 1);
			$result[] = update_option('sherpa_settings_sameday_delivery_options_service_2hr', $service_2h, 1);
			$result[] = update_option('sherpa_settings_sameday_delivery_options_service_4hr', $service_4h, 1);
			$result[] = update_option('sherpa_settings_sameday_delivery_options_service_at', $service_at, 1);
			$result[] = update_option('sherpa_settings_sameday_delivery_options_service_bulk_rate', $service_bulk_rate, 1);

			foreach ($result as $key => $value) {
				if (!empty($value)) {
					$response = $value;
					break;
				} else {
					$response = 0;
				}
			}

			// Check response
			if ($response) {
				echo json_encode(array(
					"alert" => "success",
					"message" => "Updated successfully"
				));
			} else {
				echo json_encode(array(
					"alert" => "failure",
					"message" => "Already up to date"
				));
			}

			wp_die();
		}

		public function sherpa_deliver_options_later_ajax_callback() {

			// @todo - Remove unused variable
			global $wpdb;
			$result = array();

			$service_1h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_later_delivery_options_service_1hr', '')
			);

			$service_2h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_later_delivery_options_service_2hr', '')
			);

			$service_4h = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_later_delivery_options_service_4hr', '')
			);

			$service_at = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_later_delivery_options_service_at', '')
			);

			$service_bulk_rate = sanitize_text_field(
				Sherpa_Request::post('sherpa_settings_later_delivery_options_service_bulk_rate', '')
			);

			$result[] = update_option('sherpa_settings_later_delivery_options_service_1hr', $service_1h, 1);
			$result[] = update_option('sherpa_settings_later_delivery_options_service_2hr', $service_2h, 1);
			$result[] = update_option('sherpa_settings_later_delivery_options_service_4hr', $service_4h, 1);
			$result[] = update_option('sherpa_settings_later_delivery_options_service_at', $service_at, 1);
			$result[] = update_option('sherpa_settings_later_delivery_options_service_bulk_rate', $service_bulk_rate, 1);

			foreach ($result as $key => $value) {
				if (!empty($value)) {
					$response = $value;
					break;
				} else {
					$response = 0;
				}
			}

			if ($response) {
				echo json_encode(array(
					"alert" => "success",
					"message" => "Updated successfully"
				));
			} else {
				echo json_encode(array(
					"alert" => "failure",
					"message" => "Already up to date"
				));
			}

			wp_die();
		}

		public function register_my_sherpa_submenu_page() {

			add_submenu_page('woocommerce', 'Sherpa Delivery', 'Sherpa Delivery', 'manage_options', SHERPA_PLUGIN_PAGE_ID, 'sherpa_submenu_page_callback');
		}
		
		/*
			Sherpa page real time updating content.
		*/
		public function my_ajax_view_update_sherpa_action_callback(){
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			
			$order_data = get_post_meta($_POST['post_ids'], 'set_params', true);
			$order_data['leave_unattended'] = $_POST['authority_to_leave'];
			$order_data['specified_recipient'] = $_POST['specified_recipient'];
			$order_data['fragile'] = $_POST['contain_fragile'];
			$order_data['prescription_meds'] = $_POST['prescription_meds'];
			$order_data['send_sms'] = $_POST['send_sms'];
			$order_data['alcohol'] = $_POST['contain_alcohol'];
			$order_data['tobacco'] = $_POST['contain_tobacco'];
			$order_data['high_vis'] = $_POST['high_vis'];
			update_post_meta($_POST['post_ids'], 'set_params', $order_data);
			// update_option('sherpa_settings_authority_to_leave',$_POST['authority_to_leave']);
			// update_option('sherpa_settings_contains_scheduled_medication',$_POST['prescription_meds']);
			// update_option('sherpa_settings_specified_recipient',$_POST['specified_recipient']);
			// update_option('sherpa_settings_send_sms',$_POST['send_sms']);
			// update_option('sherpa_settings_contains_alcohol',$_POST['contain_alcohol']);
			// update_option('sherpa_settings_contains_fragile_items',$_POST['contain_fragile']);
			// update_option('sherpa_settings_contains_tobacco',$_POST['contain_tobacco']);
			// update_option('sherpa_settings_requires_hi_vis_vest',$_POST['high_vis']);
			wp_die();
		}
		/*
			Sherpa page's edit pop-up.
		*/
		public function popupmaker() {
			global $current_screen; 
			$conf = new Sherpa_Configurations();
			$del_date  =  '<input type="text" id="del_date"  class="del_date_viewdd extt" name="del_date" value="'.date("d F").'">';
			//$del_date  =  '<input type="text" id="del_date"  class="del_date_viewdd extt" name="del_date" value="'.date('Y-m-d\TH:i').'">';
			//$del_date	= '<input type="datetime-local" id="del_date_viewxx" class="del_date_viewddxx" data-ordid="'.$order_id.'"  name="del_date"  value="'.$dates.'">';
			//$del_date	= '<input type="datetime-local" id="del_date" class="del_date_viewdd123 extt" name="del_date"  value="'.date('Y-m-d\TH:i').'">';
			// $del_win   = '<select name="sherpa_method_option" id="sherpa_method_option" class="sherpa_method_option_change">
			// 				<option value="Please select Time">Please select Time</option>
			// 			</select><div class="loaderdisdelwinsec"></div>';
			$del_option   = '<select id="del_option" class="del_options_s" name="del_option">';
				if(get_option('service_1hr_enabled')){
                        $del_option .= '<option value="1hr">1 Hour</option>';			       
				}	
                        $del_option .= '<option value="2hr">2 Hour</option>';			       
					
                        $del_option .= '<option value="4hr">4 Hour</option>';			       
					
									
				if(get_option('service_at_enabled')){
                        $del_option .= '<option value="same_day">Same Day</option>';			       
				}					
				if(get_option('service_bulk_rate_enabled')){
                        $del_option .= '<option value="bulk_rate">Bulk Rate</option>';			       
				}					
					$del_option .= '</select>';
			$auth_to_leave = '<label><input type="checkbox" id="authority_to_leave" name="leave_unattended" value="'.get_option('sherpa_settings_authority_to_leave').'">
			 Authority to leave</label>';


			$specified_recipient = '<label><input type="checkbox" id="specified_recipient" name="specified_recipient" value="'.get_option('sherpa_settings_specified_recipient').'"> Specified recipient</label>';


			$contain_fragile = '<label><input type="checkbox" id="contain_fragile" name="fragile" value="'.get_option('sherpa_settings_contains_fragile_items').'"> Contains fragile items</label>';
			
			$contain_medication = '<label><input type="checkbox" id="contain_medication" name="prescription_meds" value="'.get_option('sherpa_settings_contains_scheduled_medication').'"> Contains scheduled medication</label>';

			$send_sms = '<label><input type="checkbox" id="send_sms" name="send_sms" value="'.get_option('sherpa_settings_send_sms').'"> Send SMS</label>';
			
			$contain_alcohol = '<label><input type="checkbox" id="contain_alcohol" name="alcohol" value="'.get_option('sherpa_settings_contains_alcohol').'"> Contains alcohol</label>';
			
			$contain_tobacco = '<label><input type="checkbox" id="contain_tobacco" name="tobacco" value="'.get_option('sherpa_settings_contains_tobacco').'"> Contains tobacco</label>';
			
			$high_vis = '<label><input type="checkbox" id="high_vis" name="high_vis" value="'.get_option('sherpa_settings_requires_hi_vis_vest').'"> Requires hi-vis vest</label>';
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
				?>
				<style>
				.modal-content {
					width: 800px;
				}
				.modal-content {
					right: 119px;
				}
				/* new css */
				tr.top--tr {
					border-bottom: 1px solid #00000073;
				}
				.top--tr td {
					padding: 10px;
					font-size: 15px;
					font-weight: 600;
				}
				.second--tr td {
					padding: 10px;
					font-size: 15px;
					font-weight: 600;
					padding-top: 25px;
					width: 100% !important;
				}
				.third--tr td {
					padding: 10px;
					font-size: 14px;
					font-weight: 600;
					padding-top: 25px;
					width: 100% !important;
				}
				.fourth--tr td {
					padding: 10px;
					font-size: 15px;
					font-weight: 600;
					padding-top: 25px;
					width: 100% !important;
				}
				.wrap--input {
						display: flex;
					justify-content: start;
					align-items: center;
				}
				.wrap--input label {
						margin-left: 10px;
							font-size: 14px;
				}
				</style>
				<div class="modal fade extttfddd" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content modal_edit_bulk">
							  <div class="modal-body">
								<form method="post" id="test" action=''>
									<input type="hidden" id="post_ids" name="post_ids" value="" />
									<table class="modal-body bulk_edit">
										<tr class="top--tr">
											<th>Bulk Edit</th>
											<th>Ready for Pickup <label>Date and time the item/s will be ready for pickup</label></th>
											<th>Delivery Option <label>Sherpa delivery option</label></th>
											<th></th>
											<th><button type="button" class="btn btn-secondary testing" data-dismiss="modal">Cancel</button>
											<button type="button" name="sherpa_update" class="btn btn-primary sherpa_update">Apply</button></th>
										</tr>
										<tr class="second--tr ">
											<td>Options</td>
											<td><?php echo $del_date?></td>
											<td><?php echo $del_option ?></td>
											<td class="tdloaderdisdelwinsec"><?php echo ''; //$del_win; ?></td>
											<td></td>
										</tr>
										<tr class="third--tr">
											<td>Delivery Prefrence</td>
											<td><div class="wrap--input"><?php echo $auth_to_leave?></div></td>
											<td><div class="wrap--input"><?php echo $specified_recipient?></div></td>
											<td><div class="wrap--input"><?php echo $contain_fragile?> </div></td>
											<td><div class="wrap--input"><?php echo $contain_medication?></div></td>
										</tr>
										<tr class="fourth--tr" >
											<td></td>
											<td><div class="wrap--input"><?php echo $send_sms?></div></td>
											<td><div class="wrap--input"><?php echo $contain_alcohol?></div></td>
											<td><div class="wrap--input"><?php echo $contain_tobacco?></div></td>
											<td><div class="wrap--input"><?php echo $high_vis?></div></td>
										</tr>
									</table>
								</form>
						  </div>
						</div>
					</div>
				</div>
        
				<?php
			}
		}
		/*
			Sherpa page's button for sending to sherpa.
		*/
		public function funct_generate_after_content() {
			global $current_screen;
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
				?>
				<form id="sherpa_post" method = "post">
					<div class="input--wrap">
					 <div class="child">
						<button type="button" id="subBtn" class="btn btn-primary send-sherpa--btn">Send to Sherpa</button>
					</div>
					</div>
				</form>
				<?php
			}
		}
		
		/*
			Sherpa page's delivery preferences real time update.
		*/
		public function my_ajax_sherpa_post_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			$ky = '';
			ob_start();
			$this->slot_interval($ky);
			$json_slot = ob_get_clean();
			$setp = get_post_meta(get_post($_POST['post_ids'])->post_parent, 'set_params', true);
			$setp['get_sherpa_date'] = get_post_meta(get_post($_POST['post_ids'])->post_parent, 'get_sherpa_date', true);
			
			$setp['get_sherpa_Prefrence']['leave_unattended'] = $setp['leave_unattended'];
			$setp['get_sherpa_Prefrence']['specified_recipient'] = $setp['specified_recipient'];
			$setp['get_sherpa_Prefrence']['prescription_meds'] = $setp['prescription_meds'];
			$setp['get_sherpa_Prefrence']['send_sms'] = $setp['send_sms'];
			$setp['get_sherpa_Prefrence']['fragile'] = $setp['fragile'];
			$setp['get_sherpa_Prefrence']['alcohol'] = $setp['alcohol'];
			$setp['get_sherpa_Prefrence']['tobacco'] = $setp['tobacco'];
			$setp['get_sherpa_Prefrence']['high_vis'] = $setp['high_vis'];
			
			
			
			echo json_encode(array(
				'slot_interval' => json_decode($json_slot),
				'order_param' => $setp,
			));
			// $mainjson = 
			wp_die();
		}
		
		/*
			Sherpa page's delivery preferences pop-up.
		*/
		public function my_ajax_view_pop_up_shepa_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}

			$order_data = get_post_meta($_POST['post_ids'], 'set_params', true);
			$array = array(
				'authority_to_leave' => $order_data['leave_unattended'], 
				'specified_recipient' => $order_data['specified_recipient'], 
				'contain_fragile' => $order_data['fragile'] ,
				'contain_medication' => $order_data['prescription_meds'], 
				'send_sms' => $order_data['send_sms'],
				'contain_alcohol' => $order_data['alcohol'] ,
				'contain_tobacco' => $order_data['tobacco'] ,
				'high_vis' => $order_data['high_vis'] ,
			);
			

			echo json_encode($array);
			wp_die();
		}

    /*
      Delete selected send to sherpa orders
    */
    // public function my_ajax_delete_send_to_sherpa_orders_action_callback(){

    // }
		/*
			Sherpa page's content update.
		*/
		public function my_ajax_set_sherpa_post_action_callback(){
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			$post_ids = explode( ', ', $_POST['post_ids'] );
			if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
				foreach( $post_ids as $post_id ) {
					$get_id = $post_id;
					preg_match_all('!\d+!', $get_id, $matches);
					foreach($matches as $get_post_detail_id){
						foreach($get_post_detail_id as $post_id){
						$order_id = get_post($post_id);
						$order_id = $order_id->post_parent;
						$order_data = get_post_meta($order_id, 'set_params', true);
						$order_data['ready_at'] = $_POST['del_date'];
						$order_data['delivery_options'] = $_POST['del_option'];
						$order_data['delivery_window'] = $_POST['sherpa_method_option'];
						$order_data['leave_unattended'] = $_POST['authority_to_leave'];
						$order_data['specified_recipient'] = $_POST['specified_recipient'];
						$order_data['prescription_meds'] = $_POST['prescription_meds'];
						$order_data['send_sms'] = $_POST['send_sms'];
						$order_data['alcohol'] = $_POST['contain_alcohol'];
						$order_data['fragile'] = $_POST['contain_fragile'];
						$order_data['tobacco'] = $_POST['contain_tobacco'];
						$order_data['high_vis'] = $_POST['high_vis'];
						
						update_post_meta($order_id, 'set_params', $order_data);
						update_option('sherpa_settings_authority_to_leave',$_POST['authority_to_leave']);
						update_option('sherpa_settings_contains_scheduled_medication',$_POST['prescription_meds']);
						update_option('sherpa_settings_specified_recipient',$_POST['specified_recipient']);
						update_option('sherpa_settings_send_sms',$_POST['send_sms']);
						update_option('sherpa_settings_contains_alcohol',$_POST['contain_alcohol']);
						update_option('sherpa_settings_contains_fragile_items',$_POST['contain_fragile']);
						update_option('sherpa_settings_contains_tobacco',$_POST['contain_tobacco']);
						update_option('sherpa_settings_requires_hi_vis_vest',$_POST['high_vis']);
						}
					}
				}
			}
			wp_die();
		}
		/*
			Get time's slot for customer.
		*/
		public function getTimeSlot($interval, $start_time, $end_time)
		{
			
			$start = new DateTime($start_time);
			$end = new DateTime($end_time);
			$startTime = $start->format('g:i a');
			$endTime = $end->format('g:i a');
			
			$i=0;
			$time = [];
			
			while(strtotime($startTime) <= strtotime($endTime)){
				$start = $startTime;
				$end = date('g:i a',strtotime('+'.$interval.' minutes',strtotime($startTime)));
				$startTime = date('g:i a',strtotime('+60 minutes',strtotime($startTime)));
				$i++;
				if(strtotime($startTime) <= strtotime($endTime)){
					$time[$i]['slot_start_time'] = $start;
					$time[$i]['slot_end_time'] = $end;
					
				}
			}
			
			return $time;
		}
		/*
			Sherpa page's time interval via api.
		*/
		public function slot_interval($ky) {
			
			$conf = new Sherpa_Configurations();
			$operating_time_value = $conf->getOperatingTimeWrapper();
			$prep_time = $conf->getData('prep_time');
			$cutoff_time = $conf->getCutoffTime();
			$operating_time = explode(', ', $operating_time_value);	
			$operating_time_to = '21:00';
			if (isset($operating_time[0])) {
				$operating_time_from = $operating_time[0];
				if (false === strpos($operating_time_from, ':')) {
					$operating_time_from .= ':00';
				}
				if (isset($operating_time[1])) {
					$operating_time_to = $operating_time[1];
					if (false === strpos($operating_time_to, ':')) {
						$operating_time_to .= ':00';
					}
				}
			} else {
				$operating_time_from = '07:00';
			}
			list($hour_from, $minute_from) = explode(':', $operating_time_from);
			list($hour_to, $minute_to) = explode(':', $operating_time_to);
			// Convert 24hours format to 12hours format
			if($prep_time == 'NP'){
            $time1 = date("g:i a", strtotime($operating_time_from));
            $time2 = date("g:i a", strtotime($operating_time_to));
			}
			elseif($prep_time == '30M'){
            $time1 = date("g:i a", strtotime('+30 minutes',strtotime($operating_time_from)));
			$time2 =date("g:i a", strtotime('-30 minutes',strtotime($operating_time_to)));
			}
			elseif($prep_time == '1H'){
            $time1 = date("g:i a", strtotime('+1 hour',strtotime($operating_time_from)));
			$time2 =date("g:i a", strtotime('-1 hour',strtotime($operating_time_to)));
			}
			elseif($prep_time == '2H'){
            $time1 = date("g:i a", strtotime('+2 hour',strtotime($operating_time_from)));
			$time2 =date("g:i a", strtotime('-2 hour',strtotime($operating_time_to)));
			}
			else{
		    $time1 = date("g:i a", strtotime('+4 hour',strtotime($operating_time_from)));
			$time2 =date("g:i a", strtotime('-4 hour',strtotime($operating_time_to)));
			}
			if(!empty($_POST['set_interval'][$ky])){
				$interval = $_POST['set_interval'][$ky];
			} else {
				$interval = $_POST['set_interval'];
			}
			
			if($interval == '1hr' || $interval == 'bulk_rate'){
				$slots = $this->getTimeSlot(60, $time1, $time2);
			}else if($interval == '2hr'){
				$slots = $this->getTimeSlot(120, $time1, $time2);
			}else if($interval == '4hr'){
				$slots = $this->getTimeSlot(240, $time1, $time2);
			}else if($interval == 'same_day'){
				$slots = $this->getSameDaySlots($time1, $time2);
			} 
	        
			if(!empty($_POST['set_interval'][$ky])){
				return $slots;
			} else {
				echo json_encode($slots);
			}
		
		}
		/*
			Sherpa page's SameDay interval via api.
		*/
		public function getSameDaySlots($start_time, $end_time)
		{
			$start = new DateTime($start_time);
			$end = new DateTime($end_time);
			$startTime = $start->format('g:i a');
			$endTime = $end->format('g:i a');

			$time = array();
			$time['slot_start_time'] = $startTime;
			$time['slot_end_time'] = $endTime;

			return array($time);
		}
		/*
			Sherpa page's delivery window via api.
		*/
		public function my_ajax_edit_sherpa_post_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
		$method = 'GET';
		$conf = new Sherpa_Configurations();
		
		$operating_time_value = $conf->getOperatingTimeWrapper();
		$prep_time = $conf->getData('prep_time');
		$cutoff_time = $conf->getCutoffTime();
		$operating_time = explode(', ', $operating_time_value);	
		$operating_time_to = '21:00';
		if (isset($operating_time[0])) {
			$operating_time_from = $operating_time[0];
			if (false === strpos($operating_time_from, ':')) {
				$operating_time_from .= ':00';
			}
			if (isset($operating_time[1])) {
				$operating_time_to = $operating_time[1];
				if (false === strpos($operating_time_to, ':')) {
					$operating_time_to .= ':00';
				}
			}
		} else {
			$operating_time_from = '07:00';
		}
		$sherpa_access = get_option('sherpa_access_data', TRUE);
		$access_token  = isset($sherpa_access['access_token'])  ? $sherpa_access['access_token']  : '';
		$headers = array(
			'Accept' => 'application/json',
			'Authorization' => 'Bearer '.$access_token,
			'Content-Type' => 'application/json',
			'Cache-Control' => 'no-cache'
		);
    // Change this to qa for testing for invalid_token error
		$url = 'https://deliveries.sherpa.net.au/api/1/price_calculators/delivery_windows.json';
		$operating_time_from = explode(':',$operating_time_from)[0];
		$operating_time_to = explode(':',$operating_time_to)[0];
		
		foreach($_POST['post_ids'] as $ky => $vl){
			$order_id =  $vl;
			$order_data = get_post_meta($order_id,'set_params',true);
			
			$delivery_options = $_POST['set_interval'][$ky];
			if($delivery_options == '1hr'){
				$delivery_options = '5';
			} else if($delivery_options == '2hr'){
				$delivery_options = '0';
			} else if($delivery_options == '4hr'){
				$delivery_options = '1';
			} else if($delivery_options == 'same_day'){
				$delivery_options = '2';
			}else if($delivery_options == 'bulk_rate' ){
				$delivery_options = '6';
			}
			
		
			
			$order_data['delivery_options'] = $_POST['set_interval'][$ky];
			update_post_meta($order_id, 'set_params', $order_data);
			
			// $date = DateTime::createFromFormat('d-M-y', $order_data['ready_at']);
			// $order_data['ready_at'] = $date->format('d F Y');
			
			$date_str = $order_data['ready_at'];
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
			  $order_data['ready_at'] = date('j F Y', strtotime($date_str));
			}
		  
			
			$originalDateStr = $order_data['ready_at'];
            $originalDateTime = DateTime::createFromFormat('j F Y', $originalDateStr);
            $currentDateTime = new DateTime('now');
            if ($originalDateTime < $currentDateTime) {
                
                
            $nextavaildatecustom = $this->nextavaildatecustom();
            $currentDateTime = new DateTime($nextavaildatecustom['next_available']);
            
            
                
                
              $order_data['ready_at'] = $currentDateTime->format('j F Y');
            } else {
              $order_data['ready_at'] = $originalDateTime->format('j F Y');
            }
			
			
			
			
			
			$date = date_create_from_format('d F Y', $order_data['ready_at']); // Parse the date string into a DateTime object
		   
			$date->setTimezone(new DateTimeZone(wp_timezone_string()));
			$prep_time = $conf->getData('prep_time');
		
			$date->setTime($operating_time_from,00); // Set the time to 9:30 AM
			
			if($prep_time != 'NP'){
				$date->add(new DateInterval('PT'.$prep_time));
			}
            //$formatted_date_string = $date->format('d F Y'); // Format the DateTime object as a string in the desired format
			$formatted_date_string = $date->format(datetime::ISO8601); // Format the DateTime object as a string in the desired format
		    
		    $dateStr = $formatted_date_string;
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:sO', $dateStr);
            $dateTime->setTimezone(new DateTimeZone('Australia/Sydney'));
            $formatted_date_string = $dateTime->format('Y-m-d\TH:i:s.uP');
            $formatted_date_string = str_replace('000000','000',$formatted_date_string);
		   
			$params = array(
			'vehicle_id' => $conf->getVehicleId(),
			'pickup_address' => $order_data['pickup_address'],
			'delivery_address' => $order_data['delivery_address'],
			'delivery_address_city' => $order_data['delivery_address_city'],
			'delivery_address_postal_code' =>$order_data['delivery_address_post_code'],
			'ready_at' => $formatted_date_string,
			'is_update' => false,
			'purchase_item' => false,
			'business_hours' => array(
				'monday' => array(
					'start' => $operating_time_from,
					'end' => $operating_time_to
				),
				'tuesday' => array(
					'start' => $operating_time_from,
					'end' => $operating_time_to
				),
				'wednesday' => array(
					'start' => $operating_time_from,
					'end' => $operating_time_to
				),
				'thursday' => array(
					'start' => $operating_time_from,
					'end' => $operating_time_to
				),
				'friday' => array(
					'start' => $operating_time_from,
					'end' => $operating_time_to
				)
			),
			'delivery_options' => $delivery_options,
			);
			
			$sherpa_access = get_option('sherpa_access_data', TRUE);
			$url = $url . '?' . http_build_query($params);
			$response = wp_remote_get(
			$url,
			array(
					'method' => 'GET',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $headers,
					'body' => $params,
				)
			);
			// $dateString = $formatted_date_string;
			// $dateTime = new DateTime($dateString);
			// $datew = $dateTime->format('Y-m-d');
			
			// $date_to_check = $datew;
			// $current_date = date('Y-m-d');

			// if ($current_date > $date_to_check) {
				// $today_date = date('Y-m-d', strtotime('today'));
				// $datew = $today_date; // Output: 2023-03-03 (assuming the current date is March 3, 2023)
			// } 
			sleep(2);
           
		    if(!empty(json_decode(wp_remote_retrieve_body( $response ))->delivery_options[0]->windows)){
		        
		        
		    
			$datew = key(json_decode(wp_remote_retrieve_body( $response ))->delivery_options[0]->windows);
			$times = json_decode(wp_remote_retrieve_body( $response ))->delivery_options[0]->windows->$datew;
			$formatted_times = array();
			foreach ($times as $index => $time) {
				$start_time = date('g:i a', strtotime(explode(' - ', $time)[0]));
				$end_time = date('g:i a', strtotime(explode(' - ', $time)[1]));
				
				$formatted_times[$index + 1] = array(
					'slot_start_time' => $start_time,
					'slot_end_time' => $end_time,
				);
			}
			$sltss[$ky] = array(
					$formatted_times,
					);
			$sltss[$ky]['delivery_window'] = str_replace('–','-',$order_data['delivery_window']);
			
		    } else {
		        $formatted_timesNo[0] = array(
					'slot_start_time' => 'No response',
					'slot_end_time' => '',
				);
				$sltss[$ky] = array(
					$formatted_timesNo,
				);
		    }
			
		}
			echo json_encode($sltss);
			wp_die();
		}
		/*
			Sherpa page's date update.
		*/
		public function my_ajax_select_shepa_date_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}

			$order_id =  $_POST['post_ids'] ;
			$order_data = get_post_meta($order_id, 'set_params', true);
			$order_data['ready_at'] = $_POST['set_time'];
			update_post_meta($order_id, 'set_params', $order_data);
			wp_die();
		
		}
		/*
			Sherpa page's delivery time update.
		*/
		public function my_ajax_time_sherpa_post_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}

			$order_id =  $_POST['post_ids'] ;
			$order_data = get_post_meta($order_id, 'set_params', true);

			$order_data['delivery_window'] = $_POST['set_time'];
			update_post_meta($order_id, 'set_params', $order_data);
			wp_die(1);
		}
		/*
			Delete orders from Send to Sherpa page.
		*/
		public function my_ajax_send_to_sherpa_delete_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			$order_id =  $_POST['post_ids'] ;
			// $order_data = get_post_meta($order_id, 'set_params', true);
			// $order_data['delivery_packages'] = $_POST['del_packages'];
			// update_post_meta($order_id, 'set_params', $order_data);
      foreach ($order_id as $id){
        //echo 'Testing Callback: '.$id;
        wp_delete_post($id, true); // delete sts order but keep order meta
      }
      // echo 'Post Type :'.get_post_type($order_id[0]);
			wp_die(); // Always include this to end AJAX requests properly in wordpress
		}
    /*
      Update Sherpa Date and Time
    */
    public function my_ajax_edit_sherpa_date_time_callback() {
      if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
      $order_id =  $_POST['post_ids'] ;
      $date_time_values = $_POST['date_time_values'];
      $order_data = get_post_meta($order_id, 'set_params', true);
      //$order_data['delivery_packages'] = $_POST['del_packages'];
      //$order_data['delivery_packages'] = $_POST['del_packages']; // Replace with date and time
      $order_data['last_selected_time'] = $date_time_values;
			update_post_meta($order_id, 'set_params', $order_data);
			wp_die(1);
    }
		/*
			Sherpa page's packages update.
		*/
		public function my_ajax_edit_sherpa_packages_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			$order_id =  $_POST['post_ids'] ;
			$order_data = get_post_meta($order_id, 'set_params', true);
			$order_data['delivery_packages'] = $_POST['del_packages'];
			update_post_meta($order_id, 'set_params', $order_data);
			wp_die(1);
		}
		/*
			Sherpa page's delivery option update.
		*/
		public function my_ajax_edit_sherpa_options_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			$order_id =  $_POST['post_ids'] ;
			$order_data = get_post_meta($order_id, 'set_params', true);
			$order_data['delivery_options'] = $_POST['del_options'];
			update_post_meta($order_id, 'set_params', $order_data);
			wp_die(1);
		}
		/*
			Sherpa page's delivery time's format like figma.
		*/
		public function my_ajax_select_shepa_update_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			
			
			/* _POST_POSTArray
				(
				[action] => my_ajax_edit_sherpa_post
				[nonce] => 7943a7768d
				[type] => edit
				[set_interval] => Array
				(
				[0] => 4hr
				)

				[post_ids] => Array
				(
				[0] => 22
				)

				)
 */			
			
			
			$_POST['post_ids'][0] = explode(',',$_POST['post_ids'])[0];
			$_POST['set_interval'] = array($_POST['set_interval']);
			
			
			$psid = get_post(explode(',',$_POST['post_ids'])[0])->post_parent;
		
			$_POST['post_ids'] = array(
									0 => $psid
									);
		
			
			$this->my_ajax_edit_sherpa_post_callback();
			// $ky = '';
			// $this->slot_interval($ky);
			wp_die();
		}
		/*
			WooCommerce order send to sherpa from sherpa page via api.
      This is fired when we hit 'Send to Sherpa' button on 'Send to Sherpa' page
		*/
		public function my_ajax_send_sherpa_action_callback() {
			if ( !isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'sherpa' ) ) {
				exit('Unauthorized Request');
			}
			error_reporting(E_ALL ^ E_NOTICE);
      $chosen_deliveries_json = $_POST['chosen_delivery_array']; 
      $chosen_delivery_options = stripslashes($chosen_deliveries_json); 
      $chosen_delivery_options = json_decode($chosen_delivery_options, true); 
			$post_ids = explode( ',', $_POST['post_ids'] );
      $date_time_values_json = $_POST['date_time_array']; // JSON Object {\"3265\":\"2023-08-16T11:00\",\"3267\":\"2023-08-21T09:00\"}
      foreach ($_POST as $key => $value) {
        //echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
        //error_log("Field ".htmlspecialchars($key)." is ".htmlspecialchars($value));
        error_log("Field ".$key." is ".$value);
      }
      //$date_time_values = explode(',', $_POST['dateTimeValues']);
      $date_time_values = stripslashes($date_time_values_json); // {"3265":"2023-08-16T11:00","3267":"2023-08-21T09:00"} Removed slashes in date_time_values
      error_log("post['date_time_array'] value is ".$date_time_values); // {"3265":"2023-08-16T11:00","3267":"2023-08-21T09:00"}

      //$date_time_values = '{"3265":"2023-08-16T11:00"}';
      //error_log('DatetimeValues 1 : '.print_r($date_time_values['3315']));
      $date_time_values = json_decode($date_time_values, true);
      //error_log('DatetimeValues : '.print_r($date_time_values['3315']));
      if(isset($date_time_values[0])){
        error_log('DatetimeValues : '.print_r($date_time_values[0]));
      }
			$shop_name =  get_option('blogname');
      //return false;

      $sent_message = (count($post_ids) > 1)? 'Orders have ' : 'Order has ';
   
			if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
				foreach($post_ids as $post_id){
				error_log('post_id : '.$post_id);
				$get_post_id = get_post($post_id);
				
				$order_id = $get_post_id->post_parent; // Gets the order id from STS order id
        error_log('order_id : '.$order_id);
        error_log('Test value: '.$date_time_values[strval($order_id)].":00.000+11:00");
        error_log('Test value: '.$date_time_values[$order_id].":00.000+11:00");
        //return false;

					if($order_id == 0){
					    $order_id = $post_id;
					}			
							$order_detail = get_post_meta($order_id, 'set_params'); // item description is empty here
							$sherpa_origin = get_option('woocommerce_sherpa_settings');

              //Test printing default delivery note
              $testData = get_option('sherpa_settings_notes');

              $conf = new Sherpa_Configurations();
              //Fetch sherpa default product description
              $item_description = $conf->getItemDescription();

							
							foreach($order_detail as $order_data){
								$vehicle_id = $order_data['vehicle_id'];
                $item_description = !empty($item_description)? $item_description : 'Order #: ' . $order_id . ' - Sender: ' . $order_data['delivery_address_contact_name'] . ' ' . $order_data['delivery_address_contact_name_last'] . ' - ' . $order_data['delivery_address_phone_number'] . ' - ';
								$pickup_address = $order_data['pickup_address'];
								$pickup_address_unit = isset($order_data['pickup_address_unit'])? $order_data['pickup_address_unit']: '';
								$pickup_address_country_code = $order_data['pickup_address_country_code'];
								$pickup_address_contact_name = $order_data['pickup_address_contact_name'];
								$pickup_address_instructions = $order_data['pickup_address_instructions'];
								$delivery_address_unit = $order_data['delivery_address_unit'];
								$delivery_address = $order_data['delivery_address'];
								$delivery_address_contact_name = $order_data['delivery_address_contact_name'];
								$delivery_address_contact_name_last = $order_data['delivery_address_contact_name_last'];
								$delivery_address_phone_number = $order_data['delivery_address_phone_number'];
								$delivery_address_country_code = $order_data['delivery_address_country_code'];
								$delivery_address_post_code = $order_data['delivery_address_post_code'];
								$delivery_address_city = $order_data['delivery_address_city'];
								//$delivery_address_state = $order_data['delivery_address_state'];// returns state code instead of state name
								$delivery_address_state = !empty($order_data['shipping']['state']) ? ($order_data['shipping']['state']) : (isset($order_data['billing']['state']) ? $order_data['billing']['state'] : $order_data['delivery_address_state'] ); // Getting delivery address error from api when using state code e.g. AUK instead of Auckland
								$delivery_options = $order_data['delivery_options'];
								$delivery_options = $chosen_delivery_options[$order_id];
								$delivery_packages = $order_data['delivery_packages'];
								//$ready_at = $order_data['ready_at'];
								//$ready_at = $date_time_values[$order_id].":00.000+12:00";
								$ready_at = $date_time_values[$order_id].":00.000+".get_option('gmt_offset').":00"; // Set timezone offset from WP settings
                error_log('$ready_at: '.$ready_at);
                // return false;
								//$ready_at = "2023-10-09T05:30:00.000+11:00"; // Need to use this format for datetimepicker values
								$delivery_window = $order_data['delivery_window'];
								$contains_fragile = $order_data['fragile'];
								$authority_to_leave = $order_data['leave_unattended'];
								$contain_medication = $order_data['prescription_meds'];
								$requires_hi_vis_vest = $order_data['high_vis'];
								$contains_tobacco = $order_data['tobacco'];
								$send_sms = $order_data['send_sms'];
								$contains_alcohol = $order_data['alcohol'];
								$specified_recipient = $order_data['specified_recipient'];
							}

              // get order items from Order w.r.t Order Id.
				      $order = new WC_Order($order_id);
				      $items = $order->get_items();

				      $itemsCount = count($items);
				      $i = 1;
				      $priceTotal = 0;

              $allowSendingProductDescription = false;
				      // adding product name to items description.
              if($allowSendingProductDescription){
                foreach ($items as $key => $val) {
                  $item_description .= $items[$key]['name'];
                  if ($i == $itemsCount) {
                    $item_description .= ".";
                    $priceTotal += $items[$key]['line_total'];
                    break;
                  } else {
                    $item_description .= " ,";
                    $priceTotal += $items[$key]['line_total'];
                    $i++;
                  }
                }
              }
				      
            
				      // truncate at 250 character length
				      if (strlen($item_description) > 250) {
				      	$item_description = substr($item_description, 0, 248);
				      	$item_description .= '...';
				      }
							
							$updated_value = '';
							if($delivery_options == '1hr'){
								$delivery_options = '5';
								$updated_value = '1 hour delivery';
							} else if($delivery_options == '2hr'){
								$delivery_options = '0';
								$updated_value = '2 hour delivery';
							} else if($delivery_options == '4hr'){
								$delivery_options = '1';
								$updated_value = '4 hour delivery';
							} else if($delivery_options == 'same_day' || $delivery_options == 'at'){
								$delivery_options = '2';
								$updated_value = 'Same day delivery';
							}else if($delivery_options == 'bulk_rate' ){
								$delivery_options = '6';
								$updated_value = 'Bulk rate delivery';
							}
							
							// get Order's delivery instructions.
							$order = wc_get_order($order_id);
							$delivery_instructions = (strlen($order->get_customer_note()) > 250) ? substr($order->get_customer_note(), 0, 250) . '...' : $order->get_customer_note();
							$delivery_instructions = preg_replace('/\s+/', ' ', trim($delivery_instructions));
							
							/* $pickup_address = $pickup_street . ' ' . $pickup_city . ' ' . $pickup_postal . ' ' . $pickup_state;

							return preg_replace('/\s+/', ' ', trim($pickup_address)); */
							$del_address = $delivery_address . ' ' . $delivery_address_city . ' ' . $delivery_address_post_code . ' ' . $delivery_address_state;

							
							/* // @todo check selected option , selected date
							$ship_ready_at = WC()->session->get('sherpa_ready_at_date');
							$method_option = WC()->session->get('sherpa_selected_method_option');
							if ($method_option) {
								list($method_option_hour, $method_option_minute) = explode(':', $method_option);
								if ($method_option_hour > 0) {
									// hour should not be 0
									$readyAt = new DateTime($ship_ready_at, new DateTimeZone(wp_timezone_string()));
									$readyAt->setTime($method_option_hour, $method_option_minute, 0);
									$ship_ready_at = $readyAt->format(DateTime::ISO8601);
								}
							}
							return $ship_ready_at; */
							
							$converted_time = date('H:i', strtotime(explode('-',$delivery_window)[0]));
						
							$ship_ready_at = $ready_at.'T'.explode(':',$converted_time)[0].':'.explode(':',explode(' ',$delivery_window)[0])[1].':00+1000';		
							
							$pickup_address = $sherpa_origin['origin_address'] . ' ' . $sherpa_origin['origin_city'] . ' ' . $sherpa_origin['origin_postcode'] . ' ' . $sherpa_origin['origin_state'];

              //error_log('$ready_at: '.$ready_at);
              //return false;
							$param = array(
								'vehicle_id' => $vehicle_id,
								'pickup_address'=> $pickup_address,
								'pickup_address_instructions'=> $pickup_address_instructions,
								'delivery_address_unit'=> $delivery_address_unit,
								'pickup_address_unit'=> $pickup_address_unit,
								'delivery_address'=> $del_address, 
								'delivery_address_postal_code'=> $delivery_address_post_code,
								'delivery_address_contact_name'=> 'Recipient: ' . $delivery_address_contact_name . ' ' . $delivery_address_contact_name_last,'delivery_address_phone_number'=> $delivery_address_phone_number,
                //'delivery_address_instructions'=> $delivery_address_instructions,
								'delivery_address_city'=> $delivery_address_city,
								'delivery_address_instructions' => $delivery_instructions,
								'item_description'=> $item_description,
								'notes'=> $notes,
								'delivery_option' => $delivery_options,
								//'ready_at'=> $ship_ready_at, //2023-05-18T13:00:00.000+10:00
								'ready_at'=> $ready_at, //2023-05-18T13:00:00.000+10:00
								'order_id'=> $order_id,
								'shop_name'=> $shop_name,
								'internal_reference_id'=> $order_id,
								'source' => 'wordpress',
								'leave_unattended'=> $authority_to_leave,
								'check_id'=> $specified_recipient,
								'fragile'=> $contains_fragile,
								'alcohol'=> $contains_alcohol,
								'high_vis'=> $requires_hi_vis_vest,
								'tobacco'=> $contains_tobacco,
								'prescription_meds'=> $contain_medication,
								'send_sms_tracking'=> $send_sms,
								'pickup_address_contact_name'=> $pickup_address_contact_name,
								'delivery_address_contact_name_last'=> $delivery_address_contact_name_last,
								'delivery_address_state'=> $delivery_address_state,
								'item_number'=> $delivery_packages,
								// 'delivery_window'=> $delivery_window,
								
							);

              //error_log(json_encode($param));
              //return false;
						
							$headers = array(
											'X-App-Token' => 'user_sherpa_api'
										);
							$sherpa_access = get_option('sherpa_access_data', TRUE);
						
							$access_token  = isset($sherpa_access['access_token'])  ? $sherpa_access['access_token']  : '';
							$headers = array(
								'Accept' => 'application/json',
								'Authorization' => 'Bearer '.$access_token,
								'Content-Type' => 'application/json',
								'Cache-Control' => 'no-cache'
							);
              // Change this to qa for testing for invalid_token error
							$url = 'https://deliveries.sherpa.net.au/api/1/deliveries';
							$url = $url . '?' . http_build_query($param);
							
							$response = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
								'method' => 'POST',
								'headers' => $headers,
								'httpversion' => '1.0',
								'sslverify' => false,
								'body' => json_encode($param)
							))));
													
							if (isset($response->errors) || isset($response->error) ) {
								$error_message = $response->errors;
								if(!empty($error_message)){
									foreach($error_message as $key=>$messages){
										foreach($messages as $display_error){
											set_transient( 'sherpa_admin_notice_error', $key.': '. $display_error, 600);
										}
									}
								}else{
									set_transient( 'sherpa_admin_notice_error',$response->error, 600);
								}
								
							}else{
								global $wpdb;


                
                // fetch order id
                //check the value of '_sherpa_delivery_ready_at' if exists based on id
                //$test_ready_at = get_post_meta($item->get_order_id(), '_sherpa_delivery_ready_at', true); 
                $test_ready_at = get_post_meta($order_id, '_sherpa_delivery_ready_at', true); //2023-10-20
                //if it hold sold value update it
                // same with -> $deliver_for = get_post_meta($item->get_order_id(), '_sherpa_delivery_deliver_for', true); // gets start time
								$test_deliver_for = get_post_meta($order_id, '_sherpa_delivery_deliver_for', true); //15:15
									
								if(!empty($order)){
								$dateresponse = new DateTime($response->ready_at); //2023-10-27 16:15:00.000000
								$deliver_for = new DateTime($response->deliver_for); //2023-10-27 18:15:00.000000
								$new_ready_attime = $dateresponse->format("h:i a"); //04:15 pm
								$deliver_fortime = $deliver_for->format("h:i a"); //06:15 pm
								$delivery_windowtime = $new_ready_attime .' - '.$deliver_fortime; //"04:45 pm - 06:45 pm"
                                $new_ready_at = $dateresponse->format('d/m/Y'); //"27/10/2023"

                // Update values in database
                update_post_meta($order_id, '_sherpa_delivery_ready_at', $new_ready_at);
                $get_new_ready_at = get_post_meta($order_id, '_sherpa_delivery_ready_at', true); //"27/10/2023"
                update_post_meta($order_id, '_sherpa_delivery_time_plain_text_new', $delivery_windowtime);
                $get_updated_delivery_window_text = get_post_meta($order_id, '_sherpa_delivery_time_plain_text', true);
                $test = get_post_meta($order_id, '_sherpa_delivery_time_plain_text_new', true);
                        		$note  = 'Order has been sent to Sherpa for delivery on '.$new_ready_at.' between '.$delivery_windowtime.' <a href="'.$response->delivery_tracking->url.'" target="_blank">Track delivery here.</a>';
                        		
                        			
                        		
                        		
                        		
                        		$order->add_order_note($note);
								foreach ( $order->get_items() as $item_id => $item ) {
								    
								    
									// check if this is the item you are looking for
									if ( $item->get_order_id() === $order_id ) {
									update_post_meta($item->get_order_id(), '_sherpa_delivery_ready_at',$ready_at);
									update_post_meta($item->get_order_id(), '_sherpa_delivery_time_plain_text',$delivery_window);
									$order_items = $wpdb->get_results(
										$wpdb->prepare(
											"SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d",
											$order_id
										)
									);
									$order_item_id = $order_items[1]->order_item_id;
									$new_order_item_name = $updated_value; 
									$table_name = $wpdb->prefix . 'woocommerce_order_items';
									$wpdb->update(
										$table_name,
										array('order_item_name' => $new_order_item_name),
										array('order_item_id' => $order_item_id)
									);
								
										break;
									}
								}
								
								update_post_meta($order_id, '_sherpa_delivery_response',$response);   
                 
                //Fetch send to sherpa order id using parent id of post
                $args_to_fetch_sts_orders = array(
                  'post_type'      => 'send_to_sherpa', // sts orders have a post type of 'send to sherpa'
                  'post_parent'    => $order_id, // order id is the parent id for sts orders
                  'fields'         => 'ids', // Only retrieve post IDs
                );

                $send_to_sherpa_orders = get_posts($args_to_fetch_sts_orders);
                $sts_order_id = $send_to_sherpa_orders[0];
								
                $after_send_data_update = array(
                  'ID' => $order_id,
                  'post_type' => 'shop_order', // Change back to shop_order otherwise it wont show under completed orders in wp admin dashboard
                  //'post_status' => 'wc-completed' // Shows in completed orders in wp admin dashboard
                );
                wp_update_post($after_send_data_update); // Update post status of original order
                update_post_meta($order_id, 'shipped_by', 'Sherpa'); // Sherpa flag when post is sent
								wp_delete_post(intval($sts_order_id)); // Delete Send to Sherpa order post
								set_transient( 'sherpa_admin_notice', $sent_message." been successfully sent to Sherpa", 600);
								}else{
									set_transient( 'sherpa_admin_notice_error', "Couldn't find Woo Order", 600);
								}
								
							}
									
				}
				die('1');
				
			}else{
				set_transient( 'sherpa_admin_notice', "Order has been successfully sent", 600);
			}
			wp_die();	
		}
		
		/*
			Sherpa page's view delivery preferences.
		*/
		public function viewpopupmaker() {
			global $current_screen;
			$auth_to_leave = '<label><input type="checkbox" id="authority_to_leave" name="leave_unattended" value="'.get_option('sherpa_settings_authority_to_leave').'">
			 Authority to leave</label>';


			$specified_recipient = '<label><input type="checkbox" id="specified_recipient" name="specified_recipient" value="'.get_option('sherpa_settings_specified_recipient').'"> Specified recipient</label>';


			$contain_fragile = '<label><input type="checkbox" id="contain_fragile" name="fragile" value="'.get_option('sherpa_settings_contains_fragile_items').'"> Contains fragile items</label>';
			
			$contain_medication = '<label><input type="checkbox" id="contain_medication" name="prescription_meds" value="'.get_option('sherpa_settings_contains_scheduled_medication').'"> Contains scheduled medication</label>';

			$send_sms = '<label><input type="checkbox" id="send_sms" name="send_sms" value="'.get_option('sherpa_settings_send_sms').'"> Send SMS</label>';
			
			$contain_alcohol = '<label><input type="checkbox" id="contain_alcohol" name="alcohol" value="'.get_option('sherpa_settings_contains_alcohol').'"> Contains alcohol</label>';
			
			$contain_tobacco = '<label><input type="checkbox" id="contain_tobacco" name="tobacco" value="'.get_option('sherpa_settings_contains_tobacco').'"> Contains tobacco</label>';
			
			$high_vis = '<label><input type="checkbox" id="high_vis" name="high_vis" value="'.get_option('sherpa_settings_requires_hi_vis_vest').'"> Requires hi-vis vest</label>';
			if ( isset( $current_screen->post_type ) && 'send_to_sherpa' === $current_screen->post_type ) {
			?>
			<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			  <div class="modal-dialog" role="document">
				<div class="modal-content">
				  <div class="modal-header">
				  <div class="container">
				  <div class="row">
					  <div class="col-md-10 left_text">
						<h6>Delivery Preferences</h6>
						</div>
						<div class="col-md-2 text_right">
							<button type="button" name="view_update" class="btn btn-primary view_update">Apply</button></div>
						</div>
					</div>
				  </div>
				 
				  <div class="modal-body view-body">
						<form method="post" id="view" action=''>
							<input type="hidden" id="post_ids" name="post_ids" value="" />
								<div class="row">
								<div class="col-md-12">
								<table class="table delivery_prefrences">
									 <tr>
									<td><?php echo 	$auth_to_leave; ?></td>
									<td><?php echo  $specified_recipient; ?></td>
									<td><?php echo  $contain_fragile; ?></td>
									<td><?php echo  $contain_medication; ?></td>
								</tr>
								<tr>
									<td><?php echo  $send_sms; ?></td>
									<td><?php echo  $contain_alcohol; ?></td> 
									<td><?php echo  $contain_tobacco; ?></td>
									<td><?php echo  $high_vis; ?></td>
								</tr>
								
							</table>
								</div>
								</div>
								
						</form>
				  </div>
				</div>
			  </div>
			</div>
			<?php
			}
		}
	}
endif;

function sherpa_submenu_page_callback() {

	require_once 'templates/adminhtml/html.php';
}

function add_product_ship_via_sherpa_option() {

	global $woocommerce, $post;

	echo '<div class="options_group">';

	woocommerce_wp_select(array(
		'id' => '_ship_via_sherpa',
		'label' => __('Ship via Sherpa', 'woocommerce'),
		'options' => array(
			'1' => __('Yes', 'woocommerce'),
			'0' => __('No', 'woocommerce')
		)
	));
	echo '</div>';
}

function save_product_ship_via_sherpa_option($post_id) {

	// Get option value.
	if (isset($_POST['_ship_via_sherpa'])) {
		update_post_meta($post_id, '_ship_via_sherpa', sanitize_text_field($_POST['_ship_via_sherpa']));
	}
}

function sherpa_plugin_instance() {
	return (new SherpaLogistics());
}

// Run!
add_action('plugins_loaded', function () {
	$GLOBALS['sherpa'] = sherpa_plugin_instance();
});

// activation_hook
register_activation_hook(__FILE__, function () {
	sherpa_plugin_instance()->install();
});

// deactivation_hook
register_deactivation_hook(__FILE__, function () {
	sherpa_plugin_instance()->uninstall();
});

