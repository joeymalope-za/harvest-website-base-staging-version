<?php

namespace WWBP\App\Views\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Settings
{
    /**
     * Settings construct.
     */
    public function __construct()
    {
        ?>
            <div class="wrap woocommerce">
                <h1 style="margin-bottom: 10px"><?php _e('Weight Based Pricing Settings', 'weight-based-pricing-woocommerce') ?></h1>
                <form method="post" action="" enctype="multipart/form-data">
                    <style>p.description{display:inline!important}</style>
                    <?php
                        woocommerce_admin_fields($this->options());
                        wp_nonce_field('wwbp_save_settings', 'wwbp_save_settings_nonce', true, true);
                    ?>
                    <input name="save" class="button-primary" type="submit" value="<?php _e('Save Changes', 'weight-based-pricing-woocommerce');?>" style="margin-top: 20px" />
                </form>
                <form method="post" action="" enctype="multipart/form-data" style="float: left; margin-top: -30px; margin-left: 150px">
                    <?php wp_nonce_field('wwbp_reset_settings', 'wwbp_reset_settings_nonce', true, true); ?>
                    <input class="button-secondary" type="submit" name="reset" value="<?php _e("Reset", 'weight-based-pricing-woocommerce')?>">
                </form>
            </div>
        <?php
    }

    /**
     * Settings fields options args.
     */
    public static function options()
    {
        return array(
            array(
                'type' => 'title',
                'id' => 'wwbp_settings',
            ),

            array(
                'name' => __('Enable Weight Based Pricing', 'weight-based-pricing-woocommerce'),
                'type' => 'checkbox',
                'id' => 'wwbp_is_enable',
                'class' => 'wwbp_is_enable',
                'default' => 'yes',
                'std' => 'yes',
                'desc' => __('Enable the Weight Based Pricing globally', 'weight-based-pricing-woocommerce'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Default Weight Unit', 'weight-based-pricing-woocommerce'),
                'type' => 'select',
                'id' => 'wwbp_default_weight_unit',
                'class' => 'wwbp_default_weight_unit',
                'options' => array(
                    'kg' => __('Kilogram (kg)', 'weight-based-pricing-woocommerce'),
                    'g' => __('Gram (g)', 'weight-based-pricing-woocommerce'),
                    'lb' => __('Pound (lb)', 'weight-based-pricing-woocommerce'),
                    'oz' => __('Ounce (oz)', 'weight-based-pricing-woocommerce'),
                ),
                'default' => 'kg',
                'std' => 'kg',
                'label' => __('Default Weight Unit', 'weight-based-pricing-woocommerce'),
            ),
            array(
                'name' => __('Custom Weight Input', 'weight-based-pricing-woocommerce'),
                'type' => 'checkbox',
                'id' => 'wwbp_custom_input_is_enable',
                'class' => 'wwbp_custom_input_is_enable',
                'default' => 'yes',
                'std' => 'yes',
                'desc' => __('Allow customers to enter custom weight input', 'weight-based-pricing-woocommerce'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Add to Cart Button Label (Shop Page)', 'weight-based-pricing-woocommerce'),
                'type' => 'text',
                'id' => 'wwbp_add_to_cart_button_label',
                'class' => 'wwbp_add_to_cart_button_label',
                'default' => __('Go To Product', 'weight-based-pricing-woocommerce'),
                'std' => __('Go To Product', 'weight-based-pricing-woocommerce'),
            ),
            array(
                'name' => __('Sale Price Label (Product Page)', 'weight-based-pricing-woocommerce'),
                'type' => 'text',
                'id' => 'wwbp_sale_price_label',
                'class' => 'wwbp_sale_price_label',
                'default' => __('Sale Price', 'weight-based-pricing-woocommerce'),
                'std' => __('Sale Price', 'weight-based-pricing-woocommerce'),
            ),
            array(
                'name' => __('Enable Actual Weight', 'weight-based-pricing-woocommerce'),
                'type' => 'checkbox',
                'id' => 'wwbp_actual_weight_is_enable',
                'class' => 'wwbp_actual_weight_is_enable',
                'default' => 'yes',
                'std' => 'yes',
                'desc' => __('Show and Calculate the Actual Weight based on the Wastage percentage', 'weight-based-pricing-woocommerce'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Actual Weight Label', 'weight-based-pricing-woocommerce'),
                'type' => 'text',
                'id' => 'wwbp_actual_weight_label',
                'class' => 'wwbp_actual_weight_label',
                'default' => __('Actual Weight', 'weight-based-pricing-woocommerce'),
                'std' => __('Actual Weight', 'weight-based-pricing-woocommerce'),
            ),
            array(
                'name' => __('Show Total Savings', 'weight-based-pricing-woocommerce'),
                'type' => 'checkbox',
                'id' => 'wwbp_show_total_savings',
                'class' => 'wwbp_show_total_savings',
                'default' => 'yes',
                'std' => 'yes',
                'desc' => __('Show Your Savings row in Cart Totals', 'weight-based-pricing-woocommerce'),
                'desc_tip' => true,
            ),

            array(
                'type' => 'sectionend',
                'id' => 'wwbp_settings',
            ),
        );
    }
}
