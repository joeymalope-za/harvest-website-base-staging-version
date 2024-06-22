<?php
/**
 * The class responsible for the toggle functionality.
 */
class Toggle {

    /**
     * The ID of the product to be added or removed from the cart.
     */
    private $product_id = 1012852;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('woocommerce_after_checkout_form', array($this, 'add_toggle_to_checkout'));
        add_action('wp_ajax_toggle_membership', array($this, 'toggle_membership'));
        add_action('wp_ajax_nopriv_toggle_membership', array($this, 'toggle_membership'));
    }

    /**
     * Add the toggle to the checkout page.
     */
    public function add_toggle_to_checkout() {
        echo '<div class="membership-toggle">
                <input type="checkbox" id="high-roller-toggle" name="membership" checked>
                <label for="high-roller-toggle">High Rollers Club</label>
              </div>';
        wp_enqueue_script('toggle-script', plugin_dir_url(__FILE__) . '../public/js/public.js', array('jquery'), '1.0', true);
        wp_localize_script('toggle-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    /**
     * Handle the toggle action.
     */
    public function toggle_membership() {
        if ($_POST['membership'] == 'high_roller') {
            WC()->cart->add_to_cart($this->product_id);
        } else {
            WC()->cart->remove_cart_item($this->product_id);
        }
        wp_die();
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
?>
