<?php
/*
Plugin Name: Harvest Compare Price Plugin
Description: This plugin will add custom field values "compare_percent" and "compare_amount" to WooCommerce single products.
Version: 1.3
Author: //Aaron B 🔥
License: GPL2
*/

// Calculate the difference in sale price and compare price
function update_compare_fields() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );

    $loop = new WP_Query( $args );
    if ( $loop->have_posts() ) {
        while ( $loop->have_posts() ) : $loop->the_post();
            global $product;
            // Fetch the sale price using WooCommerce's built-in method
            $sale_price = $product->get_sale_price();

            // Check for the custom field "flower_display_sale_price"
            $custom_field_value = get_post_meta($product->get_id(), 'flower_display_sale_price', true);

            // Use the custom field value if present, otherwise use the sale price
            $sale_price = !empty($custom_field_value) ? $custom_field_value : $sale_price;

            if ($product->is_type('variable')) {
                $default_attributes = $product->get_default_attributes();
                foreach ($product->get_available_variations() as $variation_values) {
                    $is_default_variation = true;
                    foreach ($default_attributes as $def_attr_key => $def_attr_value) {
                        if ($variation_values['attributes']['attribute_' . $def_attr_key] != $def_attr_value) {
                            $is_default_variation = false;
                            break;
                        }
                    }
                    if ($is_default_variation) {
                        $sale_price = $variation_values['display_price']; // use the sale price of the default variation
                        break;
                    }
                }
            }

            $compare_price = get_post_meta( $product->get_id(), 'Compare price', true );
            $compare_price = number_format(floatval(get_post_meta( $product->get_id(), 'Compare price', true )), 2);
            update_post_meta($product->get_id(), 'Compare price', $compare_price);
            
            if($sale_price && $compare_price && $compare_price != 0) {
                $compare_percent = (($compare_price - $sale_price) / $compare_price) * 100;
                $compare_percent = ceil($compare_percent); // Round up to the nearest whole number
                $compare_amount = $compare_price - $sale_price;
                $compare_amount = number_format($compare_amount, 2); // Format to 2 decimal places
                update_post_meta($product->get_id(), 'compare_percent', $compare_percent);
                update_post_meta($product->get_id(), 'compare_amount', $compare_amount);
            }
           
        endwhile;
    }
    wp_reset_query();
}

// WP Admin options
function compare_price_menu() {
    add_menu_page('Compare Price', 'Compare Price', 'manage_options', 'compare-price', 'compare_price_page', 'dashicons-chart-area', 60);
}
add_action('admin_menu', 'compare_price_menu');

function compare_price_page() {
    ?>
    <div class="wrap">
        <h1>Update Compare Price Fields</h1>
        <form method="post" action="">
            <input type="hidden" name="update_compare_fields_form" value="Y">
            <?php
            if(isset($_POST['update_compare_fields_form']) && $_POST['update_compare_fields_form'] == 'Y') {
                update_compare_fields();
                echo '<p>The compare fields have been updated successfully.</p>';
            }
            ?>
            <p>Click the button below to update the compare price fields for all products:</p>
            <input type="submit" value="Update Fields" class="button-primary">
        </form>
    </div>
    <?php
}
?>