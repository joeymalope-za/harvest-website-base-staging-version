<?php
/**
 * The class responsible for handling the product.
 */
class Product_Handler {
    public function enqueue_scripts() {
        wp_enqueue_script('toggle-membership-public', plugin_dir_url(dirname(__FILE__)) . 'public/js/public.js', array('jquery'), '1.0.0', true);
        wp_localize_script('toggle-membership-public', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('handle_product_nonce') 
        ));
    }

    public function display_toggle() {
        // Get the current user
        $user = wp_get_current_user();

        // Check if the user has the 'administrator' or 'high_rollers_club' role
        if (in_array('administrator', $user->roles) || in_array('high_rollers_club', $user->roles)) {
            // If the user has one of these roles, return early and do not display the toggle
            return;
        }
        $checked = $this->is_product_in_cart() ? 'checked' : '';
        echo '<div class="membership-toggle">
        <h3 class="checkout-title">Membership Options</h3>
                <div class="toggle-switch">
                    <span>Standard Member</span>
                    <input type="checkbox" id="high-roller-toggle" name="membership" data-product-id="' . $this->product_id . '" ' . $checked . '>
                    <label for="high-roller-toggle"></label>
                    <span>High Rollers Club</span>
                </div>
              </div>';
    }

    private function is_product_in_cart() {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == $this->product_id) {
                return true;
            }
        }
        return false;
    }


    /**
     * The ID of the product to be added or removed from the cart.
     */
    private $product_id = 1012852;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        //add_action('woocommerce_before_checkout_form', array($this, 'display_toggle'));
        add_action('wp_ajax_handle_product', array($this, 'handle_product'));
        add_action('wp_ajax_nopriv_handle_product', array($this, 'handle_product'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_handle_product', array($this, 'handle_product'));
        add_action('wp_ajax_nopriv_handle_product', array($this, 'handle_product'));
        add_action('template_redirect', array($this, 'add_product_for_standard_member'));
        add_action('woocommerce_before_calculate_totals', array($this, 'update_prices_for_standard_member'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_consultation_fee'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'add_consultation_fee_to_order_meta'));
    }

    /**
     * Handle the product based on the toggle state.
     */
    public function handle_product() {
        // Check the nonce
        check_ajax_referer('handle_product_nonce', 'nonce');
    
        // Get the product ID and membership from the AJAX request
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $membership = isset($_POST['membership']) ? $_POST['membership'] : '';
    
        // Handle the product
        if ($membership == 'high_roller') {
            $this->add_product_to_cart($product_id);
            // Store the 'high_roller' membership status in a session variable
            WC()->session->set('membership', 'high_roller');
        } else {
            $this->remove_product_from_cart($product_id);
            // Store the 'standard' membership status in a session variable
            WC()->session->set('membership', 'standard');
        }
    
        // Send a response back to the AJAX request
        wp_send_json_success();
    }

    // Change cart items to regular price if they opt out of membership
    public function update_prices_for_standard_member($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
    
        $user = wp_get_current_user();
    
        if (!in_array('standard_member', $user->roles)) {
            return;
        }
    
        $membership = WC()->session->get('membership');
    
        if ($membership == 'standard') {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['data']->is_on_sale()) {
                    $regular_price = $cart_item['data']->get_regular_price();
                    $cart_item['data']->set_price($regular_price);
                }
            }
        } else if ($membership == 'high_roller') {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['data']->is_on_sale()) {
                    $sale_price = $cart_item['data']->get_sale_price();
                    $cart_item['data']->set_price($sale_price);
                }
            }
        }
    }

    // Add membership to cart on pageload of checkout (standard members)
    public function add_product_for_standard_member() {
        // Check if the user is on the checkout page
        if (!is_checkout()) {
            return;
        }
    
        // Check if the user is a 'standard_member'
        $user = wp_get_current_user();
        if (!in_array('standard_member', $user->roles)) {
            return;
        }
    
        // Check if the product is already in the cart
        if ($this->is_product_in_cart()) {
            return;
        }
    
        // Add the product to the cart
        $this->add_product_to_cart($this->product_id);
    
        // Set the 'high_roller' membership status in a session variable
        WC()->session->set('membership', 'high_roller');
    }


    // Add consultation fee
    public function add_consultation_fee() {
        error_log('add_consultation_fee method called');
        // Check if the user is a 'standard_member'
        $user = wp_get_current_user();
        if (!in_array('standard_member', $user->roles)) {
            error_log('User is not a standard member');
            return;
        }

        // Check if the user meta field 'consultation_payment_pending' has a value of 1
        $consultation_payment_pending = get_user_meta($user->ID, 'consultation_payment_pending', true);
        error_log('consultation_payment_pending: ' . $consultation_payment_pending);
        if ($consultation_payment_pending != 1) {
            return;
        }

        // Check if the product is not in the cart
        if (!$this->is_product_in_cart()) {
            error_log('Product is not in the cart');
            // Add the consultation fee
            WC()->cart->add_fee(__('Consultation Fee', 'your-text-domain'), 49);
            error_log('Consultation fee added');
        }
    }

    // Add consultation fee to custom field in order item meta "added_consult_fee"
    public function add_consultation_fee_to_order_meta($order_id) {
        // Check if there are any fees added
        if (isset(WC()->cart->fees) && !empty(WC()->cart->fees)) {
            $total_fees = 0;
            foreach (WC()->cart->fees as $fee) {
                if ($fee->name == __('Consultation Fee', 'your-text-domain')) {
                    // Add the consultation fee to the order meta
                    update_post_meta($order_id, 'added_consult_fee', $fee->amount);
                }
                // Add the fee amount to the total fees
                $total_fees += $fee->amount;
            }
            // Add the total fees to the order meta
            update_post_meta($order_id, 'added_total_fees', $total_fees);
        }
    }
    
    
    /**
     * Add the product to the cart.
     */
    private function add_product_to_cart($product_id) {
        WC()->cart->add_to_cart($product_id);
    }
    
    /**
     * Remove the product from the cart.
     */
    private function remove_product_from_cart($product_id) {
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $product_id) {
                    WC()->cart->remove_cart_item($cart_item_key);
                    break;
                }
            }
        }
    }

    /**
     * The code that runs during plugin activation.
     */
    public function activate() {
        // Nothing to do here for now.
    }

    /**
     * The code that runs during plugin deactivation.
     */
    public function deactivate() {
        // Nothing to do here for now.
    }
}

// Clear user meta field 'consultation_payment_pending' after checkout
add_action('woocommerce_thankyou', 'update_consultation_payment_status', 10, 1);

function update_consultation_payment_status($order_id) {
    // Get the order
    $order = wc_get_order($order_id);

    // Get the user ID from the order
    $user_id = $order->get_user_id();

    // Update the 'consultation_payment_pending' user meta to 'paid'
    update_user_meta($user_id, 'consultation_payment_pending', 'paid');
}
?>