<?php
/**
 * The class responsible for defining all actions that occur in the admin area.
 */
class Admin {

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->plugin_name = 'woocommerce-checkout-toggle';
        $this->version = '1.0';
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu() {
        add_options_page('Woocommerce Checkout Toggle', 'Checkout Toggle', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'));
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        include_once('partials/admin-display.php');
    }

    /**
     * Register the settings for the plugin.
     */
    public function options_update() {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
    }

    /**
     * Validate the settings.
     */
    public function validate($input) {
        // All checkboxes inputs
        $valid = array();

        // Cleanup
        $valid['high_roller'] = (isset($input['high_roller']) && !empty($input['high_roller'])) ? 1 : 0;
        $valid['standard_member'] = (isset($input['standard_member']) && !empty($input['standard_member'])) ? 1 : 0;

        return $valid;
    }
}
?>
