<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
*  Load custom order class
*/
require_once get_stylesheet_directory().'/wco/classes/class-orders.php';

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);

    // Enqueue your script (assuming it's stored in your theme's js directory)
    wp_enqueue_script('custom-cart-script', get_stylesheet_directory_uri() . '/custom-cart.js', array('jquery'), '', true);

    // Localize the script with new data
    $translation_array = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('custom_nonce')
    );
    wp_localize_script('custom-cart-script', 'cart_ajax_object', $translation_array);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );


// ALL CUSTOM CODE BELOW HERE !!!


// STOP WP REDIRECT GUESSING //AWB
add_filter('redirect_canonical', 'no_redirect_on_404');
function no_redirect_on_404($redirect_url)
{
    if(is_404())
    {
        return false;
    }
    return $redirect_url;
}

// GRAVITY FORMS ZOHO - PRESERVE LEAD SOURCE
add_filter( 'gform_zohocrm_lead_15', function( $lead, $feed, $entry, $form ) {
unset( $lead['Lead_Source'] );
 
return $lead;
}, 10, 4 );

add_filter( 'gform_zohocrm_lead_10', function( $lead, $feed, $entry, $form ) {
unset( $lead['Lead_Source'] );
 
return $lead;
}, 10, 4 );



// GRAVITY FORMS - LETTERS ONLY IN NAME FIELDS ON REG FORM //AWB
add_filter('gform_field_validation', function ($result, $value, $form, $field) {
    if (isset($form['id']) && $form['id'] == 5 && in_array($field->id, [29, 30], true)) {
        
        // Check if value is not empty and contains only letters and spaces.
        if (!empty($value) && !preg_match('/^[a-zA-Z\s]+$/', $value)) {
            $result['is_valid'] = false;
            $result['message']  = empty($field->errorMessage) ? __('This field must only contain letters and spaces.', 'gravityforms') : $field->errorMessage;
        }
    }
    return $result;
}, 10, 4);



// CHANGE REVIEW WORDING ON PRODUCT PAGES AND ARCHIVES //AWB
add_filter( 'ngettext', 'bbloomer_modify_n_customer_reviews', 9999, 5 );

function bbloomer_modify_n_customer_reviews( $translation, $single, $plural, $number, $domain ) {     
    if ( '%s customer review' === $translation ) {         
        $translation = '%s';    
    } elseif ( '%s customer reviews' === $translation ) {
        $translation = '%s';
    }
    return $translation; 
}



// Change coupon form wording on checkout //AWB
add_filter( 'gettext', 'change_coupon_form_text', 20, 3 );

function change_coupon_form_text( $translated_text, $text, $domain ) {
    if ( is_checkout() && 'woocommerce' === $domain ) {
        switch ( $translated_text ) {
            case 'If you have a coupon code, please apply it below.':
                $translated_text = 'If you have a free consultation coupon, please enter it below.';
                break;
        }
    }
    return $translated_text;
}

// REDACT USERNAMES ON REVIEWS //AWB
function my_comment_author( $author, $comment_id, $comment ) {  
    // NOT empty
    if ( $comment ) {
        // Get user id
        $user_id = $comment->user_id;

        // User id exists
        if( $user_id > 0 ) {
            // Get user data
            $user = get_userdata( $user_id );

            // Check if user exists
            if( $user ) {
                // User first name
                $user_first_name = $user->first_name;

                // Call function
                $author = replace_with_stars( $user_first_name );       
            } else {
                $author = __('Anonymous User', 'woocommerce');
            }
        } else {
            $author = __('Anonymous', 'woocommerce');
        }
    }

    return $author;
}
add_filter('get_comment_author', 'my_comment_author', 10, 3 );

function replace_with_stars( $str ) {
    // Returns the length of the given string.
    $len = strlen( $str );

    // Check for string length
    if ($len <= 2) {
        return str_repeat('*', $len);
    }

    return substr( $str, 0, 1 ).str_repeat('*', $len - 2).substr( $str, $len - 1, 1 );
}


// REDIRECT USER TO SHOP ON LOGIN //AWB
function ts_redirect_login( $redirect ) {

    $redirect_page_id = url_to_postid( $redirect );
    $checkout_page_id = wc_get_page_id( 'checkout' );

    if( $redirect_page_id == $checkout_page_id ) {
        return $redirect;
    }

    return '/shop/';
}


add_filter( 'woocommerce_login_redirect', 'ts_redirect_login' );

// SHOP ONLY FOR LOGGED IN USERS - REDIRECT TO LOGIN //AWB
function my_redirect_non_logged_in_users() {
    if ( !is_user_logged_in() && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
        wp_redirect( get_permalink( get_option('woocommerce_myaccount_page_id') ) );
        exit;
    }
}
add_action( 'template_redirect', 'my_redirect_non_logged_in_users' );

// REDIRECT AFTER LOGOUT //AWB
/**
* WooCommerce My Account Page Logout Redirect
*/
add_action( 'wp_logout', 'owp_redirect_after_logout' );
function owp_redirect_after_logout() {
         wp_redirect( '/' );
         exit();
}

// AUTOFILL CHECKOUT FORM WITH USER DETAILS //AWB
add_filter( 'woocommerce_checkout_get_value', 'autofill_some_checkout_fields', 10, 2 );
function autofill_some_checkout_fields( $value, $input ) {
    $user = wp_get_current_user();
    
    if( $input === 'billing_first_name' && empty($value) && isset($user->first_name) ) {
        $value = $user->first_name;
    }
    
    if( $input === 'billing_last_name' && empty($value) && isset($user->last_name) ) {
        $value = $user->last_name;
    }
    
    if( $input === 'shipping_first_name' && empty($value) && isset($user->first_name)  ) {
        $value = $user->first_name;
    }
	
	if( $input === 'shipping_last_name' && empty($value) && isset($user->last_name)  ) {
        $value = $user->last_name;
    }
    return $value;
}

// DISPLAY CURRENT USER ROLE using shortcode: [display_user_role] ///AB
function display_user_role_shortcode() {
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $role = ( array ) $user->roles;

        switch( $role[0] ) {
            case 'non_member':
                return 'New Prescription Needed!<br><a href="/test/">Get a Consultation Now</a>';
            case 'standard_member':
                return 'Standard Member';
            case 'high_rollers_club':
                return 'High Rollers Club Member<br><a href="/cancel-membership/">Cancel Membership</a>';
			case 'administrator':
                return 'God';
            default:
                return 'Role not recognized'; // Default message if role doesn't match any of the above
        }

    } else {
        return 'Not logged in';
    }
}
add_shortcode( 'display_user_role', 'display_user_role_shortcode' );


// ORDER PAGE BROWSE PRODUCTS BUTTON REDIRECT //AWB
// Function to add a redirect after users with no orders reported issues when clicking button.
add_filter( 'woocommerce_return_to_shop_redirect', 'zworthkey_rediect_browse_product' );

function zworthkey_rediect_browse_product( $url ) {
    return home_url( '/shop/' );
}

// SHORTCODE TO REPLACE DEFAULT "IN STOCK" OR "OUT OF STOCK" NOTIFICATION ON PRODUCT PAGES - USE [stock_status_label] //AWB
function stock_status_shortcode_callback() {
    global $product; // Get the global product object

    // Check if $product exists and is an instance of WC_Product
    if( !$product || !is_a( $product, 'WC_Product' ) ) {
        return '';
    }

    // Check if the product is out of stock
    if ( !$product->is_in_stock() ) {
        return 'OUT OF STOCK';
    } elseif ( $product->get_stock_quantity() <= 15 && $product->get_stock_quantity() > 0 ) {
        return 'Only a few left, buy now!';
    }

    // Return an empty string otherwise
    return 'In Stock';
}
add_shortcode( 'stock_status_label', 'stock_status_shortcode_callback' );


// CONSENT FORM VALIADATIONS FOR SIGNATURE - GRAVITY FORMS //AWB 
add_filter('gform_validation', 'validate_name_field');

function validate_name_field($validation_result) {
    // Form ID and field ID
    $form_id = 28; 
    $field_id = 8; 

    // Get the form object from the validation result
    $form = $validation_result['form'];

    // Skip validation for other forms
    if ($form['id'] != $form_id) {
        return $validation_result;
    }

    // Loop through the form fields
    foreach ($form['fields'] as &$field) {
        // Skip validation for other fields
        if ($field->id != $field_id) {
            continue;
        }

        // Get submitted value for the field, trim and sanitize it
        $field_value = sanitize_text_field(trim(rgpost("input_{$field->id}")));

        // Get current user first and last name, trim and sanitize it
        $current_user = wp_get_current_user();
        $user_name = sanitize_text_field(trim($current_user->first_name . ' ' . $current_user->last_name));

        // Check if the field value matches the current user's first and last name, ignoring case
        if (strcasecmp($field_value, $user_name) != 0) {
            $field->failed_validation = true;
            $field->validation_message = 'The signature must match your first and last name.';
            $validation_result['is_valid'] = false;
        }
    }

    // Assign modified $form object back to the validation result
    $validation_result['form'] = $form;

    return $validation_result;
}



// REMOVE WC "ADD TO CART" BUTTON FOR 'NON_MEMBER' USER ROLE //AWB
// Function to help restrict users with no prescription from purchasing products
// 
// Check if the current user has the role 'non_member'
function check_non_member_role() {
    // Get the current user
    $current_user = wp_get_current_user();

    // Check if user has the 'non_member' role
    if ( in_array( 'non_member', (array) $current_user->roles ) ) {
        return true;
    }
    return false;
}

// Remove WooCommerce "Add to Cart" button for 'non_member' role
function remove_add_to_cart_button() {
    if ( check_non_member_role() ) {
        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    }
}

add_action( 'init', 'remove_add_to_cart_button' );

// Remove elements with the class 'non-member-hide' for 'non_member' role
function hide_elements_for_non_member() {
    if ( check_non_member_role() ) {
        ?>
        <style>
            .non-member-hide {
                display: none !important;
            }
        </style>
        <?php
    }
}

add_action( 'wp_head', 'hide_elements_for_non_member' );

// ADD LOGIN/LOGOUT BUTTON FOR DESKTOP [login_logout_link] //AWB
function login_logout_link_shortcode() {
    if (is_user_logged_in()) {
        // User is logged in
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname; // Get the user's first name
        $display_name = !empty($first_name) ? $first_name : $current_user->user_login; // Use login name if first name is not available

        // Create a logout URL
        $logout_url = wp_logout_url(get_permalink());
        return "Hi, " . esc_html($display_name) . " | <a href='" . esc_url($logout_url) . "'>Logout</a>";
    } else {
        // User is logged out
        // Create a login URL
        $login_url = wp_login_url(get_permalink());
        return "<a href='" . esc_url($login_url) . "'>Log In</a>";
    }
}

add_shortcode('login_logout_link', 'login_logout_link_shortcode');


// // ALL ORDERS STATUS UPDATED TO COMPLETE AFTER PAYMENT //AWB
add_action( 'woocommerce_payment_complete', 'custom_update_order_status', 10, 1 );

function custom_update_order_status( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );

    if ( $order && ! $order->has_status( 'completed' ) ) {
        $order->update_status( 'completed' );
    }
}

// CLEAN USER ROLE FOR DISPLAY ON MY ACCOUNT PAGE //AWB
function role_to_title($role) {
    return ucwords(str_replace('_', ' ', $role));
}

// COPY ALL FEES TO ORDER META //AWB
// Function to easily pass order fee details to order custom fields. To be used with Zoho Feeds plugin.
add_action('woocommerce_checkout_update_order_meta', 'add_fees_as_meta');
 
function add_fees_as_meta( $order_id ) {
    $order = wc_get_order( $order_id );
    foreach( $order->get_fees() as $fee ){
        $fee_name = $fee->get_name();
        $fee_total = $fee->get_total();
        update_post_meta( $order_id, sanitize_title($fee_name), $fee_total );
    }
}

// ADD MOBILE PHONE FIELD ON MY ACCOUNT PAGE //AWB
// Display the mobile phone field on my account page
add_action( 'woocommerce_edit_account_form', 'add_billing_mobile_phone_to_edit_account_form' ); // After existing fields
function add_billing_mobile_phone_to_edit_account_form() {
    $user = wp_get_current_user();
    ?>
     <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="billing_phone"><?php _e( 'Mobile phone', 'woocommerce' ); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--phone input-text" name="billing_phone" id="billing_phone" value="<?php echo esc_attr( $user->billing_phone ); ?>" />
    </p>
    <?php
}

// ADD CURRENT USER ROLE TO BODY TAG - USED FOR CSS SECRET MENU //AWB
 add_filter( 'body_class', function( $classes ) { $user = wp_get_current_user(); $roles = $user->roles; return array_merge( $classes, $roles ); } );


// REPLACE USER PHONE NUMBER WITH SUPPORT NUMBER WHEN ORDER PLACED - FOR SHERPA //AWB
function replace_phone_number($order_id) {
    update_post_meta($order_id, '_billing_phone', '1800420247');
    update_post_meta($order_id, '_shipping_phone', '1800420247');
}
add_action('woocommerce_checkout_update_order_meta', 'replace_phone_number');


// Check and validate the mobile phone
add_action( 'woocommerce_save_account_details_errors','billing_mobile_phone_field_validation', 20, 1 );
function billing_mobile_phone_field_validation( $args ){
    if ( isset($_POST['billing_phone']) && empty($_POST['billing_phone']) )
        $args->add( 'error', __( 'Please fill in your Mobile phone', 'woocommerce' ),'');
}

// Save the mobile phone value to user data
add_action( 'woocommerce_save_account_details', 'my_account_saving_billing_mobile_phone', 20, 1 );
function my_account_saving_billing_mobile_phone( $user_id ) {
    if( isset($_POST['billing_phone']) && ! empty($_POST['billing_phone']) ) {
        $phone_number = sanitize_text_field($_POST['billing_phone']);
        update_user_meta( $user_id, 'billing_phone', $phone_number );
        update_user_meta( $user_id, 'shipping_phone', $phone_number );
    }
}

// Reinstate custom fields after ACF //AWB
add_filter('acf/settings/remove_wp_meta_box', '__return_false');

// LOG USER OUT AFTER NOT APPROVED //AWB
function custom_logout_button() {
    ob_start(); // start buffer output
    ?>
    <form action="<?php echo wp_logout_url(home_url()); ?>" method="post">
      <input type="submit" value="Home">
    </form>
    <?php
    return ob_get_clean(); // output stored into a string
}
add_shortcode('logout_button', 'custom_logout_button');


// Hook to update user meta key after checkout
// Make the hook priority high (5), than update_new_remaining_dosage hook
add_action('woocommerce_order_status_completed', 'update_prescription_dosage_ordered', 1);
function update_prescription_dosage_ordered(){

	$user_id = get_current_user_id();
    $prescription_usage = get_user_meta($user_id, 'prescription_usage', true) ?: array();

    // Check if the user is logged in
    if ($user_id > 0) {

        // Get order data
        $orders = new Orders();
        $get_checkout_items = $orders->get_checkout_items();
        
        $prescription_usage_updated = array_merge($prescription_usage, $get_checkout_items);

        // Update user meta
        update_user_meta($user_id, 'prescription_usage', $prescription_usage_updated);

        // Re-calculate the prescription history
        $orders->set_prescription_history();
    }
}

// Make the hook priority low (10), than update_prescription_dosage_ordered hook
// By adding this function priority low it will fix a race condition above and 
// To make sure users prescription_summary is up to date before this function execution
// By separating this hook, it's making sure each function can be handled by workers separately and avoid N+1 bugs
add_action('woocommerce_order_status_completed', 'update_new_remaining_dosage', 10);
function update_new_remaining_dosage(){
    $orders = new Orders();
    
    $orders->set_new_remaining_dosage();
}

// Add custom validation for product dosage per month
add_filter( 'woocommerce_add_to_cart_validation', 'woocommerce_add_cart_validate_dosage', 5, 2);
function woocommerce_add_cart_validate_dosage($passed, $product_id) {
    
    $user_id = get_current_user_id();
    $prescription_summary = get_user_meta($user_id, 'prescription_summary', true) ?: [];

    $thc = get_field('thc_content', $product_id) ?: 0;
    $created_at = Orders::get_checkout_time();
    $weight_qty = isset($_POST['wwbp_weight_qty']) ? $_POST['wwbp_weight_qty'] : 0.0;
    $cart_total_weight = 0.0; // Initialized as float

    $orders = new Orders();
    $prescription_data_item = $orders->get_prescription_item($thc);

    if($prescription_data_item && is_array($prescription_data_item)){
        foreach($prescription_data_item as $data_item){
            $start_timestamp = $data_item['created_at'];
            $end_timestamp = $data_item['expiration_date'];
            $duration = $data_item['duration'];
        }
        
        $interval_size = ($end_timestamp - $start_timestamp) / $duration; // Interval range between month's duration
        
        // Returns the month order (ex. order belongs to the first month represent in array[0], if second month array[1])
        $interval_number = min($duration, floor(($created_at - $start_timestamp) / $interval_size) ); 
        
        // Get all cart items and add the total weight
        $cart_items = $orders->get_checkout_items();

        if(is_array($cart_items)){
            foreach($cart_items as $cart_item){
                if($cart_item['thc_content']===$thc){
                    $cart_total_weight += $cart_item['weight_qty'];
                }
            }
        }

        if(is_array($prescription_summary)){
            foreach($prescription_summary as $summary){
                if($summary[$thc][$interval_number]['remaining_qty']){
                    $remaining_dosage = $summary[$thc][$interval_number]['remaining_qty'];
                }
            }
        }

        if($prescription_summary){
            // Check if the product has weight if exceeds the monthly limit
            if($remaining_dosage < $weight_qty){
                // Add an error notice
                wc_add_notice( __( 'The product weight exceeds your monthly limit. ('.($remaining_dosage).' grams remaining). Visit your <a href="/my-account/">my account</a> and check your usage.', 'harvest' ), 'error' );

                // Set $passed to false to prevent the product from being added to the cart
                $passed = false;
            }

            // Include weight_qty in calculation to fix bug that the last item added needs in the cart and check the total cart weight
            if($remaining_dosage < ($cart_total_weight+$weight_qty)){ 
                // Add an error notice
                wc_add_notice( __( 'Hold up! Your total cart weight now exceeds your monthly dosage limit. You have '.($remaining_dosage).' grams remaining. Visit <a href="/my-account/">my account</a> to check your total amount. Error? please contact support.', 'harvest' ), 'error' );

                // Set $passed to false to prevent the product from being added to the cart
                $passed = false;
            }
        }

        else{
            // First time order
            $first_order_dosage = 0;

            foreach($prescription_data_item as $data_item){
                $first_order_dosage = $data_item['dosage'];
            }

            if($first_order_dosage < ($cart_total_weight + $weight_qty)){
                wc_add_notice( __( 'Hold up! Your total cart weight now exceeds your monthly dosage limit. You have '.($first_order_dosage).' grams remaining. Visit <a href="/my-account/">my account</a> to check your total amount. Error? please contact support.', 'harvest' ), 'error' );

                $passed = false;
            }
        }
        
    }

    return $passed;
}

function update_cart_ajax() {
    check_ajax_referer('custom_nonce', 'nonce');

    $cart_item_key = isset($_POST['cart_item_key']) ? wc_clean($_POST['cart_item_key']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    // User role check
    $user = wp_get_current_user();
    $is_high_rollers_club_member = in_array('high_rollers_club', (array) $user->roles);

    if ($cart_item_key && $quantity >= 0) {
        WC()->cart->set_quantity($cart_item_key, $quantity, true);
        WC()->cart->calculate_totals();

        $subtotal = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $product_weight = $product->get_weight();
            $multiplier = (!empty($product_weight) && $product_weight > 0) ? ($product_weight * $quantity) : $quantity;


            if ($product->is_on_sale() && $is_high_rollers_club_member) {
                // For high rollers club members, apply the sale price if available
                $price = $product->get_sale_price() * $multiplier;
            } else {
                // Otherwise, use the regular price
                $price = $product->get_regular_price() * $multiplier;
            }

            // Calculate the subtotal considering the multiplier
            $subtotal += $price;
        }

        // Format the subtotal as a currency
        $subtotal_html = wc_price($subtotal);

        wp_send_json_success(array('subtotal' => $subtotal_html));
    } else {
        wp_send_json_error('Error updating cart.');
    }
}
add_action('wp_ajax_update_cart', 'update_cart_ajax');
add_action('wp_ajax_nopriv_update_cart', 'update_cart_ajax');




// disable coupon notifications

add_filter('woocommerce_coupons_enabled', 'custom_disable_coupon_notice_on_cart_checkout');

function custom_disable_coupon_notice_on_cart_checkout($enabled) {
    if (is_cart() || is_checkout()) {
        return false;
    }
    return $enabled;
}

add_action('init', 'start_custom_session', 1);
function start_custom_session() {
    if (!session_id()) {
        session_start();
    }
}

add_action( 'wp_ajax_update_consent', 'update_user_consent_callback' );
function update_user_consent_callback() {
    // Check for the correct user
    if ( !current_user_can( 'edit_user', get_current_user_id() ) ) {
        wp_send_json_error( 'Insufficient permissions.' );
        wp_die();
    }

    check_ajax_referer( 'update-consent-nonce', 'nonce' );

    $consent_value = isset( $_POST['consent'] ) ? (int) $_POST['consent'] : 0;

    // Update the user meta
    $updated = update_user_meta( get_current_user_id(), 'consent', $consent_value );

    if ( $updated ) {
        wp_send_json_success( 'User consent updated successfully.' );
    } else {
        wp_send_json_error( 'Failed to update user consent.' );
    }

    wp_die();
}
