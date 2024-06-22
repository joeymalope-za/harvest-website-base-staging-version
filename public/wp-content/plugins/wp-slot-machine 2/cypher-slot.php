<?php
/**
 * Plugin Name: Harvest Slot Machine for cypher
 * Description: A simple slot machine game with a prize system. Use shortcode [cypher_slot_machine id="random number"] - ID is being logged and can only be spun once. See <a href="/wp-content/plugins/wp-slot-machine%202/spin_log.txt">spin_log.txt</a> for a running log of spins
 * Version: 1.0
 * Author: //Aaron B 🔥
 */


// Make sure this file is called by wp, not directly
if (!defined('ABSPATH')) {
    exit;
}

// This function enqueues the JS and CSS files
function cypher_slot_scripts() {
    error_log('cypher_slot_scripts function called');
    global $spin_id; // Declare $spin_id as global
    wp_enqueue_script('cypher-slot-js', plugin_dir_url(__FILE__) . 'cypher-slot.js', array('jquery'), '1.0', true);
    wp_enqueue_style('cypher-slot-css', plugin_dir_url(__FILE__) . 'cypher-slot.css');

    $prizes_and_weights = get_option('cypher_slot_prizes', array(
        "Harvest Pro Vape" => 50,
        // other prizes
    )); 
    error_log('transient spin_id: ' . get_transient('spin_id'));
    // Get the current user ID
    error_log('spin_id: ' . $spin_id);
    $user_id = get_current_user_id();
    wp_localize_script('cypher-slot-js', 'php_vars', array(
        'prizes' => json_encode($prizes_and_weights),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'spin_id' => $spin_id,
    ));
}
add_action('wp_enqueue_scripts', 'cypher_slot_scripts', 20);

// Front end prize names defined here & count is the weight out of 100 spins the prize appears
$prizes_and_weights = array(
    "Cloud 9 NEO Vape" => array('id' => 1015342, 'count' => 1),
    "Mint Pack n Puff" => array('id' => 1015357,  'count' => 10),
    "Black Cotton Mouth Killer" => array('id' => 1015348,  'count' => 10),
    "Fister Lube" => array('id' => 1015352,  'count' => 10),
    "Drawstring Bag" => array('id' => 1009369,  'count' => 10),
    "Black Rise n Grinder" => array('id' => 1015349,  'count' => 20),
    "Repn Harvest Chain" => array('id' => 1015344,  'count' => 10),
    "Hog Buddy Pouch" => array('id' => 1015359,  'count' => 10),
    "Banana Pack n Puff" => array('id' => 1015347,  'count' => 10)
); 
update_option('cypher_slot_prizes', $prizes_and_weights);

function cypher_random_spin($spin_id) {
    global $spin_id; // Declare $spin_id as global
    // Log the spinner ID
    error_log('Spinner ID in cypher_random_spin: ' . $spin_id);
    // Get the current user ID
    $user_id = get_current_user_id();

    // Get the current spin count
    $spin_count = get_user_meta($user_id, 'spin_count_' . $spin_id, true);

    // If the user has already spun 3 times, return early
    if ($spin_count >= 3) {
        return array('name' => "No More Spins", 'id' => 0);
    }

    // Increment the spin count
    increment_user_spin_count($user_id, $spin_id);

    // Get the spin id from the transient
    $spin_id = get_transient('spin_id');

    // Get the current user ID
    $user_id = get_current_user_id();

    // Log the spin id
    error_log('Spin id: ' . $spin_id);

    // Get the current user's shipping state
    $state = WC()->customer->get_shipping_state();
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

    $prizes_and_weights = get_option('cypher_slot_prizes');
    
    // Create a new array where each prize is represented proportionally to its weight
    $weighted_prizes = array();
    foreach ($prizes_and_weights as $prize_name => $prize_data) {
        $weighted_prizes = array_merge($weighted_prizes, array_fill(0, $prize_data['count'], $prize_name));
    }

    // Randomly select a prize from the weighted prizes array
    $prize_name = $weighted_prizes[array_rand($weighted_prizes)];

    // Get the product associated with the prize
    $product = wc_get_product($prizes_and_weights[$prize_name]['id']);

    // Check if the product is in stock in the user's location
    if ($product && $product->get_meta($meta_key) > 0 && $prizes_and_weights[$prize_name]['count'] > 0) {
        $prizes_and_weights[$prize_name]['count']--;
        update_option('cypher_slot_prizes', $prizes_and_weights);
        $prize = array('name' => $prize_name, 'id' => $prizes_and_weights[$prize_name]['id']);

        // Write to the log file
        write_spin_log($spin_id, $prize_name, $user_id);
        
        return $prize;
    }
    $no_win = array('name' => "No Win", 'id' => 0);
    return $no_win;
}

function cypher_check_prizes() {
    $prizes_and_weights = get_option('cypher_slot_prizes');
    if (array_sum($prizes_and_weights) === 0) {
        wp_mail("your-email@example.com", "Prizes Depleted", "All prizes are depleted.");
        exit;
    }
}


register_activation_hook(__FILE__, function() {
    add_option('cypher_slot_prizes', array(
        "Cloud 9 NEO Vape" => array('id' => 1015342, 'count' => 1),
        "Mint Pack n Puff" => array('id' => 1015357,  'count' => 10),
        "Black Cotton Mouth Killer" => array('id' => 1015348,  'count' => 10),
        "Fister Lube" => array('id' => 1015352,  'count' => 10),
        "Drawstring Bag" => array('id' => 1009369,  'count' => 10),
        "Black Rise n Grinder" => array('id' => 1015349,  'count' => 20),
        "Repn Harvest Chain" => array('id' => 1015344,  'count' => 10),
        "Hog Buddy Pouch" => array('id' => 1015359,  'count' => 10),
        "Banana Pack n Puff" => array('id' => 1015347,  'count' => 10)
    ));
});

register_deactivation_hook(__FILE__, function() {
    delete_option('cypher_slot_prizes');
});

// This function will be responsible for displaying the slot machine and handling logic
function display_cypher_slot() {
    global $spin_id;
    $output = "";
    $result = "";

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        // If the user is not logged in, display a login form
        $output .= '<p>You must be logged in to spin the slot machine.</p>';
        $output .= wp_login_form(array('echo' => false));
        return $output;
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = cypher_random_spin($spin_id);
        cypher_check_prizes();
    
        // Pass the won prize as a parameter to the create_prize_order_on_init() function
        create_prize_order_on_init_2($result);
    }

    $output .= '<form method="POST">';
    $output .= '<input type="submit" value="Spin">';
    $output .= '</form>';
    $output .= '<style>
                /* Add your styles here */
                input[type="submit"] {
                    background-color: #4CAF50;
                    color: white;
                }
                </style>';

    if ($result) {
        $output .= '<div>Result: ' . $result['name'] . '</div>';
    }

    return $output;
}


function cypher_slot_machine_ui() {

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        // If the user is not logged in, display a login form
        echo '<img src="/wp-content/uploads/2023/11/og-offer-small-1.gif" alt="Image">';
        echo '<h2 class="login-alert">You must be logged in to solve the cypher.</h2>';
        wp_login_form();
        return;
    }

    // Get the current user ID
    $user_id = get_current_user_id();

    // Slot machine HTML
    ?>
    <div id="cypher-slot-machine">
        <div class="cypher-slot-container">
            <div class="slot" id="slot1"><div class="icon bell"></div></div>
        </div>
        <div class="cypher-slot-container">
            <div class="slot" id="slot2"><div class="icon bell"></div></div>
        </div>
        <div class="cypher-slot-container">
            <div class="slot" id="slot3"><div class="icon bell"></div></div>
        </div>
    </div>
    <?php

    // Spin button
    echo '<button id="cypher-spin-button">Spin</button>';
}

function ajax_cypher_random_spin() {
    global $spin_id; // Declare $spin_id as global
    // Check if the spin ID is set in the AJAX request
    if (!isset($_POST['spin_id'])) {
        // Handle the case when spin_id is not set, e.g., return an error response
        echo json_encode(['error' => 'spin_id is not set']);
        wp_die();
    }

    // Get the spin ID from the AJAX request
    $spin_id = $_POST['spin_id'];

    // Pass the spin ID to the cypher_random_spin function
    $result = cypher_random_spin($spin_id);

    error_log(print_r($_POST, true));

    $result = cypher_random_spin($spin_id);
    // Get the current user ID & date
    $user_id = get_current_user_id();
    $current_date = date("Y-m-d");
    error_log('Result from cypher_random_spin: ' . print_r($result, true)); // Log the result from random_spin
    echo json_encode($result);
    wp_die(); // This is required to terminate immediately and return a proper response
}
    add_action('wp_ajax_cypher_random_spin', 'ajax_cypher_random_spin');
    add_action('wp_ajax_nopriv_cypher_random_spin', 'ajax_cypher_random_spin');


function create_prize_order_on_init_2() {
    // Only run this function if a user is logged in and a spin is made
    if (!is_user_logged_in() || !isset($_POST['prize_name']) || !isset($_POST['prize_id'])) {
        return;
    }

    // Get the prize data from the $_POST variable
    $prize = array(
        'name' => $_POST['prize_name'],
        'id' => intval($_POST['prize_id'])
    );

    // Log the prize data
    error_log('Prize data: ' . print_r($prize, true));

    // Check if $prize is an array and contains the 'id' key
    if (!is_array($prize) || !isset($prize['id'])) {
        error_log('Invalid prize: ' . print_r($prize, true));
        return;
    }

    // If the prize id is 0, it means there's no win, so return early
    if ($prize['id'] === 0) {
        return;
    }

    // Get the product ID associated with the prize
    $product_id = $prize['id']; // Use the prize passed as parameter

    // Add the product to the user's cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), array('prize' => true));
}
add_action('wp_ajax_create_order_2', 'create_prize_order_on_init_2');
add_action('wp_ajax_nopriv_create_order_2', 'create_prize_order_on_init_2');


// Register the shortcode
add_shortcode('cypher_slot_machine', 'display_slot_machine');

// Create a shortcode to display the UI
function cypher_slot_machine_shortcode($atts) {
    global $spin_id; // Declare $spin_id as global
    error_log('cypher_slot_machine_shortcode function called');
    // Extract the attributes
    $atts = shortcode_atts(
        array(
            'id' => '', // Default value
        ), 
        $atts, 
        'cypher_slot_machine'
    );
    error_log('shortcode id: ' . $atts['id']);

    // Store the id in a transient
    set_transient('spin_id', $atts['id'], 300); // The transient will expire after 60 seconds

    ob_start();
    cypher_slot_machine_ui();
    return ob_get_clean();
}
add_shortcode('cypher_slot_machine', 'cypher_slot_machine_shortcode');


function set_prize_price_to_zero($cart) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        if (isset($cart_item['prize']) && $cart_item['prize']) {
            $cart_item['data']->set_price(0);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'set_prize_price_to_zero', 10, 1);

// This function disables the quantity field for cart items that are prizes
function disable_quantity_field_for_prizes($product_quantity, $cart_item_key, $cart_item) {
    // Check if the cart item is a prize
    if (isset($cart_item['prize']) && $cart_item['prize']) {
        // If it's a prize, return the quantity without the input field
        $product_quantity = sprintf('%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity']);
    }

    return $product_quantity;
}
add_filter('woocommerce_cart_item_quantity', 'disable_quantity_field_for_prizes', 10, 3);

// Create a spin log for all cypher spins
function write_spin_log($spin_id, $prize_won, $user_id) {
    global $spin_id; // Declare $spin_id as global
    // Define the log file path
    $log_file_path = plugin_dir_path(__FILE__) . 'spin_log.txt';

    // Get the current date and time
    $date_time = date('Y-m-d H:i:s');

    // Prepare the log entry
    $log_entry = "Date & Time: $date_time, Spinner ID: $spin_id, Prize Won: $prize_won, User ID: $user_id" . PHP_EOL;

    // Append the log entry to the log file
    file_put_contents($log_file_path, $log_entry, FILE_APPEND);
}

// Add the spin counts to user meta - to allow max 3 spins per slot ID
function increment_user_spin_count($user_id, $spin_id) {
    global $spin_id; // Declare $spin_id as global
    // Log the spinner ID
    error_log('Spinner ID in increment_user_spin_count: ' . $spin_id);

    // Get the current spin count
    $spin_count = get_user_meta($user_id, 'spin_count_' . $spin_id, true);

    // If the spin count is not set, initialize it to 0
    if (!$spin_count) {
        $spin_count = 0;
    }

    // Increment the spin count
    $spin_count++;

    // Update the spin count
    update_user_meta($user_id, 'spin_count_' . $spin_id, $spin_count);
}