<?php
class WC_Custom_Stock
{

    public function __construct()
    {
        // Hook into the WooCommerce product data panels.
        add_action('woocommerce_product_options_stock_fields', array(
            $this,
            'add_custom_stock_fields'
        ));

        // Save custom stock data.
        add_action('woocommerce_process_product_meta', array(
            $this,
            'save_custom_stock_data'
        ));

        // Display custom stock based on user's shipping address.
        // Display custom stock based on user's shipping address.
        add_filter('woocommerce_product_get_stock_quantity', array(
            $this,
            'display_custom_stock'
        ) , 10, 2);

        // Determine Stock Status Based on Custom Stock Levels
        add_filter('woocommerce_product_get_stock_status', array(
            $this,
            'custom_stock_status'
        ) , 10, 2);

        // Update the "Add to Cart" Logic
        add_filter('woocommerce_add_to_cart_validation', array(
            $this,
            'validate_add_to_cart'
        ) , 10, 3);

        // Override Product Visibility on Shop and Archive Pages
        //add_action('woocommerce_product_query', array(
        //    $this,
        //    'modify_product_query'
        //));

        // Stock Validation During Checkout
        add_action('woocommerce_check_cart_items', array(
            $this,
            'validate_cart_stock'
        ));

        // Update Stock After Order Completion
        add_action('woocommerce_order_status_completed', array(
            $this,
            'reduce_custom_stock'
        ));

        // Determine if the product is in stock based on user's location for product archives.
        add_filter('woocommerce_product_is_in_stock', array(
            $this,
            'is_product_in_stock_based_on_location'
        ) , 10, 2);

        // Add this line in the __construct() function of the WC_Custom_Stock class
        add_action('woocommerce_order_status_completed', array($this, 'adjust_custom_stock_after_order_complete'), 20, 1);


    }

    public function is_product_in_stock_based_on_location($is_in_stock, $product)
    {
        // If the product ID is 1012852, return true
        if ($product->get_id() == 1012852) {
            return true;
        }
        $stock_status = $this->custom_stock_status('', $product);
        return $stock_status === 'instock';
    }

    function custom_validate_cart_stock($passed, $product_id, $quantity)
    {
        // Get the product.
        $product = wc_get_product($product_id);

        // If WC customer doesn't exist, return default validation.
        if (!is_a(WC()->customer, 'WC_Customer'))
        {
            return $passed;
        }

        // Determine stock based on user location.
        $state = WC()
            ->customer
            ->get_shipping_state();
        switch ($state)
        {
            case 'NT':
            case 'QLD':
                $stock = $product->get_meta('_stock_brisbane');
            break;
            case 'VIC':
                $stock = $product->get_meta('_stock_melbourne');
            break;
            case 'NSW':
            case 'ACT':
            case 'SA':
            case 'TAS':
            case 'WA':
                $stock = $product->get_meta('_stock_sydney');
            break;
            default:
                $stock = $product->get_stock_quantity(); // Default to WooCommerce stock if state doesn't match.
                
            break;
        }

        // Compare requested quantity against stock.
        if ($quantity > $stock)
        {
            wc_add_notice(sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order. We only have %s left. Please adjust your order and try again.', 'wc-custom-stock') , $product->get_name() , $stock) , 'error');
            $passed = false;
        }

        return $passed;
    }

    public function validate_cart_stock()
    {
        $all_products_in_stock = true; // Assume all products are in stock by default
        $debug_messages = []; // To store our debug messages
        foreach (WC()
            ->cart
            ->get_cart() as $cart_item_key => $cart_item)
        {
            $product = $cart_item['data'];

            // If the product ID is 1012852, skip this product
            if ($product->get_id() == 1012852) {
                continue;
            }
            $product_id = $product->get_id();
            $stock_status = $this->custom_stock_status('', $product);

            // Determine stock based on user location.
            $user_location_stock = $this->display_custom_stock($product->get_stock_quantity() , $product);

            // Add debug messages
            $debug_messages[] = "Product ID: $product_id";
            $debug_messages[] = "User Location Stock: $user_location_stock";
            $debug_messages[] = "Cart Item Quantity: " . $cart_item['quantity'];

            // Check if there's enough stock for the cart item quantity.
            if ($cart_item['quantity'] > $user_location_stock)
            {
                wc_add_notice(sprintf(__('Sorry, we do not have enough "%s" in stock in your location to fulfill your order. We only have %s left. Please adjust your order and try again.', 'wc-custom-stock') , $product->get_name() , $user_location_stock) , 'error');
                $all_products_in_stock = false; // Set to false if any product is out of stock
                
            }
        }

        // Print debug messages to the browser console
        if (!empty($debug_messages))
        {
            echo '<script>';
            foreach ($debug_messages as $message)
            {
                echo "console.log('{$message}');";
            }
            echo '</script>';
        }

        return $all_products_in_stock; // Return the validation status
        
    }

    public function reduce_custom_stock($order_id)
    {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item)
        {
            $product = $item->get_product();
            // If the product ID is 1012852, skip this product
            if ($product->get_id() == 1012852) {
                continue;
            }
            $quantity = $item->get_quantity();

            // Get the shipping state from the order object.
            $state = $order->get_shipping_state();
            $meta_key = '';

            switch ($state)
            {
                case 'NT':
                case 'QLD':
                    $meta_key = '_stock_brisbane';
                break;
                case 'VIC':
                    $meta_key = '_stock_melbourne';
                break;
                case 'NSW':
                case 'ACT':
                case 'SA':
                case 'TAS':
                case 'WA':
                    $meta_key = '_stock_sydney';
                break;
            }

            $current_stock = $product->get_meta($meta_key);
            $new_stock = $current_stock - $quantity;
            $product->update_meta_data($meta_key, $new_stock);
            $product->save();
        }
    }
/*
    // Product Visibility on Shop and Archive Pages - enable to hide out of stock products on category pages
    public function modify_product_query($q)
    {
        if (!is_a(WC()->customer, 'WC_Customer'))
        {
            return;
        }

        $meta_query = $q->get('meta_query');

        $state = WC()
            ->customer
            ->get_shipping_state();
        $meta_key = '';

        switch ($state)
        {
            case 'NT':
            case 'QLD':
                $meta_key = '_stock_brisbane';
            break;
            case 'VIC':
                $meta_key = '_stock_melbourne';
            break;
            case 'NSW':
            case 'ACT':
            case 'SA':
            case 'TAS':
            case 'WA':
                $meta_key = '_stock_sydney';
            break;
        }

        $meta_query[] = array(
            'key' => $meta_key,
            'value' => 0,
            'compare' => '>',
        );

        $q->set('meta_query', $meta_query);
    }
*/
    public function validate_add_to_cart($passed, $product_id, $quantity)
    {
        $product = wc_get_product($product_id);
        // If the product ID is 1012852, return true
        if ($product->get_id() == 1012852) {
            return true;
        }
        $stock_status = $this->custom_stock_status('', $product);

        if ($stock_status == 'outofstock')
        {
            wc_add_notice(__('Sorry, this product is out of stock in your location.', 'wc-custom-stock') , 'error');
            return false;
        }

        return $passed;
    }

    public function custom_stock_status($stock_status, $product)
    {
        // If the product ID is 1012852, skip this product
        if ($product->get_id() == 1012852) {
            return $stock_status;
        }
        if (!is_a(WC()->customer, 'WC_Customer'))
        {
            return $stock_status;
        }

        $state = WC()
            ->customer
            ->get_shipping_state();
        $stock_quantity = 0;

        switch ($state)
        {
            case 'NT':
            case 'QLD':
                $stock_quantity = $product->get_meta('_stock_brisbane');
            break;
            case 'VIC':
                $stock_quantity = $product->get_meta('_stock_melbourne');
            break;
            case 'NSW':
            case 'ACT':
            case 'SA':
            case 'TAS':
            case 'WA':
                $stock_quantity = $product->get_meta('_stock_sydney');
            break;
        }

        return $stock_quantity <= 0 ? 'outofstock' : 'instock';
    }

    public function add_custom_stock_fields()
    {
        global $post;
        $product = wc_get_product($post->ID);

        echo '<p class="form-field">';
        echo '<label for="_stock_sydney">Stock (Sydney)</label>';
        echo '<input type="text" class="short" name="_stock_sydney" id="_stock_sydney" value="' . esc_attr($product->get_meta('_stock_sydney')) . '">';
        echo '<span class="description">Stock Qty for Sydney location. Servicing: NSW, ACT, SA, WA, TAS</span>';
        echo '</p>';

        echo '<p class="form-field">';
        echo '<label for="_stock_melbourne">Stock (Melbourne)</label>';
        echo '<input type="text" class="short" name="_stock_melbourne" id="_stock_melbourne" value="' . esc_attr($product->get_meta('_stock_melbourne')) . '">';
        echo '<span class="description">Stock Qty for Melbourne location. Servicing VIC</span>';
        echo '</p>';

        echo '<p class="form-field">';
        echo '<label for="_stock_brisbane">Stock (Brisbane)</label>';
        echo '<input type="text" class="short" name="_stock_brisbane" id="_stock_brisbane" value="' . esc_attr($product->get_meta('_stock_brisbane')) . '">';
        echo '<span class="description">Stock Qty for Brisbane location. Servicing QLD, NT</span>';
        echo '</p>';

    }

    public function save_custom_stock_data($post_id)
    {
        $product = wc_get_product($post_id);
        // If the product ID is 1012852, skip this product
        if ($product->get_id() == 1012852) {
            return;
        }

        $stock_sydney = isset($_POST['_stock_sydney']) ? sanitize_text_field($_POST['_stock_sydney']) : '';
        $product->update_meta_data('_stock_sydney', $stock_sydney);

        $stock_melbourne = isset($_POST['_stock_melbourne']) ? sanitize_text_field($_POST['_stock_melbourne']) : '';
        $product->update_meta_data('_stock_melbourne', $stock_melbourne);

        $stock_brisbane = isset($_POST['_stock_brisbane']) ? sanitize_text_field($_POST['_stock_brisbane']) : '';
        $product->update_meta_data('_stock_brisbane', $stock_brisbane);

        $product->save();
    }

    public function display_custom_stock($stock_quantity, $product)
    {
        // If the product ID is 1012852, skip this product
        if ($product->get_id() == 1012852) {
            return $stock_quantity;
        }
        // Check if the WooCommerce customer object exists.
        if (!is_a(WC()->customer, 'WC_Customer'))
        {
            return $stock_quantity;
        }

        // Get the user's shipping state.
        $state = WC()
            ->customer
            ->get_shipping_state();

        switch ($state)
        {
            case 'NT':
            case 'QLD':
                return $product->get_meta('_stock_brisbane');
            case 'VIC':
                return $product->get_meta('_stock_melbourne');
            case 'NSW':
            case 'ACT':
            case 'SA':
            case 'TAS':
            case 'WA':
                return $product->get_meta('_stock_sydney');
            default:
                return $stock_quantity;
        }
    }

    /**
     * Adjusts the custom stock based on the weight.
     *
     * @param int $quantity The original quantity.
     * @param WC_Order_Item $item The order item object.
     * @param WC_Order $order The order object.
     * @return int Adjusted quantity.
     */
    public function adjust_custom_stock_after_order_complete($order_id) {
        // Get the order object
        $order = wc_get_order($order_id);
        
        // Loop through order items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            // If the product ID is 1012852 (High Rollers Club Membership), skip this product
            if ($product->get_id() == 1012852) {
                continue;
            }
            $reduced_stock = $item->get_meta('_reduced_stock', true) - 1;  // Subtracting 1 to align with actual reduced stock
            $product = $item->get_product();
                        
            // Get user's shipping state
            $state = $order->get_shipping_state();
            $meta_key = '';
    
            // Determine the right stock meta field based on state
            switch ($state) {
                case 'NT':
                case 'QLD':
                    $meta_key = '_stock_brisbane';
                break;
                case 'VIC':
                    $meta_key = '_stock_melbourne';
                break;
                case 'NSW':
                case 'ACT':
                case 'SA':
                case 'TAS':
                case 'WA':
                    $meta_key = '_stock_sydney';
                break;
            }
    
            // Reduce custom stock
            if ($meta_key) {
                $current_stock = $product->get_meta($meta_key);
                $new_stock = $current_stock - $reduced_stock;
                $product->update_meta_data($meta_key, $new_stock);
                $product->save();
            }
        }
    }
    
    


}

function instantiate_wc_custom_stock()
{
    $wc_custom_stock_instance = new WC_Custom_Stock();
    add_action('woocommerce_check_cart_items', array(
        $wc_custom_stock_instance,
        'validate_cart_stock'
    ));
}
add_action('plugins_loaded', 'instantiate_wc_custom_stock');



