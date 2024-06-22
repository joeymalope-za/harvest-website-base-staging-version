<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Configurations
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sherpa library Configurations
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Configurations extends Sherpa_Abstract {

    public function __construct($onlyLogger = false, $withSetting = true) {

        if ($onlyLogger) {
            $logger = new WC_Logger();
            $this->data['logger'] = $logger;
        } else {

            $logger = new WC_Logger();
            $this->data['logger'] = $logger;

            // Load configurations from database
            $this->data['username'] = get_option('sherpa_credentials_account');
            $this->data['password'] = get_option('sherpa_credentials_password');
            $this->data['sandbox'] = get_option('sherpa_credentials_sandbox', 1);
            $this->data['title'] = get_option('sherpa_settings_title', __('Sherpa On Demand', 'sherpa'));
            $this->data['store_title'] = get_option('sherpa_settings_title', __('Sherpa On Demand', 'sherpa'));
            $this->data['store_name'] = get_option('sherpa_settings_store_name', __('Store Name', 'sherpa'));
            $this->data['notes'] = get_option('sherpa_settings_notes');

            $this->data['shipment'] = get_option('sherpa_settings_shipment', 0);
            $this->data['tracking_link'] = get_option('sherpa_settings_add_tracking_link', 0);
            $this->data['product'] = get_option('sherpa_settings_product', 1);
            $this->data['vehicle_id'] = get_option('sherpa_settings_vehicle', 1);
            $this->data['delivery_rates'] = get_option('sherpa_settings_delivery_rates');
            $this->data['add_margin'] = get_option('sherpa_settings_add_margin');
            $this->data['item_description'] = get_option('sherpa_settings_item_description');

            $this->data['flat_rate_1_hour'] = get_option('sherpa_settings_flat_rate_1_hour');
            $this->data['flat_rate_2_hour'] = get_option('sherpa_settings_flat_rate_2_hour');
            $this->data['flat_rate_4_hour'] = get_option('sherpa_settings_flat_rate_4_hour');
            $this->data['flat_rate_same_day'] = get_option('sherpa_settings_flat_rate_same_day');
            $this->data['flat_rate_bulk_rate'] = get_option('sherpa_settings_flat_rate_bulk_rate');

            $this->data['outside_radius_1_hour'] = get_option('sherpa_settings_outside_radius_1_hour');
            $this->data['outside_radius_2_hour'] = get_option('sherpa_settings_outside_radius_2_hour');
            $this->data['outside_radius_4_hour'] = get_option('sherpa_settings_outside_radius_4_hour');
            $this->data['outside_radius_same_day'] = get_option('sherpa_settings_outside_radius_same_day');
            $this->data['outside_radius_bulk_rate'] = get_option('sherpa_settings_outside_radius_bulk_rate');

            $this->data['operating_day'] = get_option('sherpa_delivery_settings_operating_day', '1,2,3,4,5');
            $this->data['operating_time_wrapper'] = get_option('sherpa_delivery_settings_operating_time_wrapper', '9:00, 17:00');
            $this->data['prep_time'] = get_option('sherpa_delivery_settings_prep_time');
            $this->data['cutoff_time'] = get_option('sherpa_sherpa_delivery_settings_cutoff_time', '3:00 PM');

            // Same day
            $this->data['service_sameday'] = get_option('sherpa_settings_service_sameday', 'service_1hr,service_2hr,service_4hr,service_at,service_bulk_rate');
            $this->data['sameday_delivery_options_sameday'] = get_option('sherpa_settings_sameday_delivery_options_sameday');
            $this->data['sameday_delivery_options_service_1hr'] = get_option('sherpa_settings_sameday_delivery_options_service_1hr', '1 hour delivery');
            $this->data['sameday_delivery_options_service_2hr'] = get_option('sherpa_settings_sameday_delivery_options_service_2hr', '2 hour delivery');
            $this->data['sameday_delivery_options_service_4hr'] = get_option('sherpa_settings_sameday_delivery_options_service_4hr', '4 hour delivery');
            $this->data['sameday_delivery_options_service_at'] = get_option('sherpa_settings_sameday_delivery_options_service_at', 'Same day delivery');
            $this->data['sameday_delivery_options_service_bulk_rate'] = get_option('sherpa_settings_sameday_delivery_options_service_bulk_rate', 'Bulk rate delivery');

            // Schedule for later
            $this->data['service_later'] = get_option('sherpa_settings_service_later', 'service_1hr,service_2hr,service_4hr,service_at,service_bulk_rate');
            $this->data['later_delivery_options_later'] = get_option('sherpa_settings_later_delivery_options_later');
            $this->data['later_delivery_options_service_1hr'] = get_option('sherpa_settings_later_delivery_options_service_1hr', '1 hour delivery');
            $this->data['later_delivery_options_service_2hr'] = get_option('sherpa_settings_later_delivery_options_service_2hr', '2 hour delivery');
            $this->data['later_delivery_options_service_4hr'] = get_option('sherpa_settings_later_delivery_options_service_4hr', '4 hour delivery');
            $this->data['later_delivery_options_service_at'] = get_option('sherpa_settings_later_delivery_options_service_at', 'Same day delivery');
            $this->data['later_delivery_options_service_bulk_rate'] = get_option('sherpa_settings_later_delivery_options_service_bulk_rate', 'Bulk rate delivery');

            // Delivery prefs
            $this->data['authority_to_leave'] = get_option('sherpa_settings_authority_to_leave', '0');
            $this->data['send_sms'] = get_option('sherpa_settings_send_sms', '0');
            $this->data['specified_recipient'] = get_option('sherpa_settings_specified_recipient', '0');
            $this->data['contains_alcohol'] = get_option('sherpa_settings_contains_alcohol', '0');
            $this->data['contains_fragile_items'] = get_option('sherpa_settings_contains_fragile_items', '0');
            $this->data['contains_scheduled_medication'] = get_option('sherpa_settings_contains_scheduled_medication', '0');
            $this->data['contains_tobacco'] = get_option('sherpa_settings_contains_tobacco', '0');
            $this->data['requires_hi_vis_vest'] = get_option('sherpa_settings_requires_hi_vis_vest', '0');
        }

        // Add settings
        if ($withSetting) {
            $this->getSettings();
        }
    }

    public function getSettings() {
        $settings = get_option('woocommerce_sherpa_settings');
        if (!empty($settings) && is_array($settings)) {
            $settings = array_map(array(
                $this,
                'format_settings'
            ), $settings);
            foreach ($settings as $key => $value) {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Decode values for settings.
     *
     * @param mixed $value
     * @return array
     */
    public function format_settings($value) {
        return is_array($value) ? $value : $value;
    }
}
