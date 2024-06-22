<?php
/**
 * Plugin Name: Harvest Support Chat
 * Description: Adds the user's email, name, cart contents & shipping state to the Livechat support chat iframe to aid support agents. Use shortcode [ab_support_chat]. <a href="/wp-content/plugins/ab-support-chat/Readme.txt">More details</a>
 * Version: 1.1
 * Author: //Aaron B 🔥
*/

function ab_support_chat_func() {
    // Get the current user's data
    $current_user = wp_get_current_user();

    // Get the current page URL
    $current_page_url = home_url( $_SERVER['REQUEST_URI'] );

    // Check if the user is logged in
    if ( $current_user->exists() ) {
        // Get the user's email
        $email = $current_user->user_email;

        // Get the user's first and last name
        $first_name = $current_user->user_firstname;
        $last_name = $current_user->user_lastname;

        // Get the user's role
        $user_roles = $current_user->roles;
        $user_role = array_shift($user_roles);

        // Get the user's shipping state
        $shipping_state = get_user_meta($current_user->ID, 'shipping_state', true);

        // Get the user's cart items
        $cart_items = WC()->cart->get_cart();
        $cart_contents = array();
        foreach($cart_items as $item) {
            $product = wc_get_product($item['product_id']);
            $cart_contents[] = rawurlencode($product->get_name()); // Apply rawurlencode() to each product name
        }
        $cart_contents_string = implode(", ", $cart_contents);

        // Create the iframe with the user's email, name, current page URL, cart items, role, and shipping state
        $url = 'https://secure.livechatinc.com/licence/16695186/v2/open_chat.cgi?name=' . rawurlencode($first_name . ' ' . $last_name) . '&params=Page%3D' . rawurlencode($current_page_url) . '%26Email%3D' . rawurlencode($email) . '%26Cart%3D' . rawurlencode($cart_contents_string) . '%26Membership-Level%3D' . rawurlencode($user_role) . '%26Shipping-State%3D' . rawurlencode($shipping_state);
        //error_log($url); // Log the URL to the error log
        $iframe = '<div id="support"><iframe class="support-chat-window" src="' . $url . '"></iframe></div>';

        return $iframe;
    } else {
        // If the user is not logged in, return the iframe without the email, name, current page URL, cart items, role, and shipping state
        return '<div id="support"><iframe class="support-chat-window" src="https://secure.livechatinc.com/licence/16695186/v2/open_chat.cgi"></iframe></div>';
    }
}
add_shortcode('ab_support_chat', 'ab_support_chat_func');