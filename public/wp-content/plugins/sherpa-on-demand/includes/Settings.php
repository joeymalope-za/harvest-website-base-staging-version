<?php
if (!defined('ABSPATH')) {
    exit();
}

class Sherpa_Settings extends WC_Shipping_Method {

    private $found_rates;

    private $allowed_methods, $origin, $origin_state, $origin_postcode, $origin_city, $origin_address, $custom_services, $origin_country, $debug, $timezone_offset, $ordered_services;

    private $services;

    public function __construct() {

        $this->id                 = SHERPA_PLUGIN_ID;
        $this->method_title       = __('Sherpa', 'sherpa');
        $this->method_description =
            __('Displays real-time Sherpa delivery rates at checkout and logs those deliveries with Sherpa. See ', 'sherpa')
            . sprintf(
                '<a href="%s">WooCommerce > Sherpa Delivery</a>',
                admin_url('/admin.php?page=wc-sherpa')
            )
            . __(' to configure your business settings.', 'sherpa');
        $this->services           = include(SHERPA_PLUGIN_DIR . '/templates/adminhtml/shipping-services.php');
        $this->init();
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        $methods = $this->get_option('services');
        return is_array($methods) ? array_keys($methods) : [];
    }

    /**
     * sort_rates function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function sort_rates($a, $b) {

        if ($a['sort'] == $b['sort']) return 0;
        return ($a['sort'] < $b['sort']) ? -1 : 1;
    }

    /**
     * Checks if the to address is within allowed countries
     *
     * @return boolean
     */
    protected function _canShip($country, $allowed_countries = 'all') {

        if ($allowed_countries == 'all') {
            return true;
        }

        return array_key_exists($country, (array) $allowed_countries);
    }

    /**
     * Check if all products in cart are shippable through sherpa
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool|void
     */
    protected function isShippableBySherpa($items = array()) {

        $shippable = true;

        if (empty((int) get_option('sherpa_settings_product'))) {
            foreach ($items as $key => $values) {
                $data = isset($values['data']) ? $values['data'] : array();
                if ('0' === get_post_meta($data->get_id(), '_ship_via_sherpa', true)) {
                    $shippable = false;
                    break;
                }
            }
        }

        return $shippable;
    }

    /**
     * Returns shipping rate result error method
     *
     * @param string $errorText
     * @return mixed|Mage_Shipping_Model_Rate_Result_Error
     */
    protected function _getErrorMethod($errorText) {

        //wc_add_notice('SHERPA RESPONSE : ' . $errorText);
        return ('SHERPA RESPONSE : ' . $errorText);
    }


    /*
* Main method to display shipping method on frontend
*/
    public function calculate_shipping($package = array()) {

        // Dont run on cart page
        if (is_cart()) {
            return;
        }

        if ($this->get_option('enabled') !== 'yes') {
            return array();
        }

        if (empty($package) || !is_array($package)) {
            return array();
        }

        // TODO: Remove unsed variable
        $sherpa = new Sherpa_Sherpa();
        $confObj = new Sherpa_Configurations();

        $package_contents = isset($package['contents']) ? $package['contents'] : array();
        $destination = isset($package['destination']) ? $package['destination'] : array();

        // check if all items are shippable by sherpa
        if ($this->isShippableBySherpa($package_contents)) {
            try {

                // check if user has selected any method
                if ((WC()->session->get('sherpa_selected_method', FALSE) == Sherpa_Sherpa::METHOD_NEXTDAY) && WC()->session->get('sherpa_prefer_ready_at_date', FALSE)) {
                    $readyAt = new DateTime(WC()->session->get('sherpa_prefer_ready_at_date', FALSE), new DateTimeZone(wp_timezone_string()));
                    $timeObj = new Sherpa_Time($confObj);
                    $readyAt = $timeObj->getReadyAtTime($readyAt);
                    $method_name = $this->_applyMethod(WC()->session->get('sherpa_selected_method'), $readyAt);
                } else {
                    $current_time = new DateTime('now', new DateTimeZone(wp_timezone_string()));

                    $confObj->setOperatingDay(get_option('sherpa_delivery_settings_operating_day'))
                        ->setTimeWrapper(get_option('sherpa_delivery_settings_operating_time_wrapper'))
                        ->setPreparationTime(get_option('sherpa_delivery_settings_prep_time'))
                        ->setCuttoffTime(get_option('sherpa_sherpa_delivery_settings_cutoff_time'));
                    $timeObj = new Sherpa_Time($confObj);
                    $readyAt = $timeObj->getReadyAtTime($current_time);
                    $methods = $this->getAllowedMethods();
                    $sameDayFlag = false;
                    foreach ($methods as $method) {
                        if ($method_name = $this->_applyMethod($method, $readyAt)) {
                            break;
                        }
                    }
                }

                // send API request
                if (isset($method_name) && $method_name) {
                    $this->_getPricesFromApi($method_name, $readyAt, $package, $confObj);
                }
            } catch (Exception $e) {
              // check if checkout page before displaying error
              if(is_checkout()){
                wc_add_notice(__($e->getMessage())); // Shows address error on top of cart page
              }
              //header('Location: ' . $_SERVER['REQUEST_URI']);
              //exit();

                $confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Exception from Settings.php-> ' . $e->getMessage());
                $confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Exception -> ' . $e->getTraceAsString());
                $get_debug = $confObj->getDebug();
                if ($confObj->getDebug() && $confObj->getDebug() == 'yes') {
                    if ($destination['city'] || $destination['address'] || $destination['postcode']) {
                        $error_method = $this->_getErrorMethod($e->getMessage());
                        //return $error_method;
                        //wc_add_notice(__($error_method), 'error');
                        //wc_add_notice(__("Testing wc notice"), 'error');
                    }
                }
            }
        }
    }

    protected function _applyMethod($method, $readyAt) {

        switch ($method) {
            case Sherpa_Sherpa::METHOD_SAMEDAY:
                $today = new DateTime('now', new DateTimeZone(wp_timezone_string()));
                if ($readyAt->format('Y-m-d') == $today->format('Y-m-d')) {
                    return Sherpa_Sherpa::METHOD_SAMEDAY;
                } else {
                    return Sherpa_Sherpa::METHOD_NEXTDAY;
                }
                //break; // unreachable code
            case Sherpa_Sherpa::METHOD_NEXTDAY:
                return Sherpa_Sherpa::METHOD_NEXTDAY;
                //break; // unreachable code
        }
    }

    protected function _getPricesFromApi($method, DateTime $readyAt, $request, $confObj) {

        $methodName = $this->id;
        $confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, '_getPricesFromApi Session set readyAt -> ' . print_r($readyAt->format('Y-m-d H:i:s'), TRUE));
        $confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, '_getPricesFromApi Session set sherpa_selected_method -> ' . print_r($method, TRUE));

        // @todo save quote
        WC()->session->set('sherpa_ready_at_date', $readyAt->format('Y-m-d H:i:s'));
        WC()->session->set('sherpa_selected_method', $method);

        // @todo get cart total price
        // $totalPrice = Mage::helper('sherpa')->getItemsTotal($request->getAllItems());

        $totalPrice = 100;
        $apiRequest = new Sherpa_Api_Request($confObj);

        // @todo Remove unsed variable
        $result = array();

        $apiRequest
            ->setDeliveryOption($methodName)
            ->setExpectedItemPrice($totalPrice)
            ->setisUpdate(false)
            ->setisPurchaseItem(false)
            ->setReadyAt($readyAt)
            ->setItems($request['contents'])
            ->setDestination(
                $request['destination']['country'],
                $request['destination']['postcode'],
                $request['destination']['state'],
                $request['destination']['city'],
                $request['destination']['address'],
                $request['destination']['address_2']
            )
            ->setAllowedCarriers($this->getAllowedMethods());
        if (strlen($request['destination']['postcode']) > 2) {
            $quotes = $apiRequest->getQuotes();
        } else {
            $quotes = array();
        }

        $count = 0;
        $commonObj = new Sherpa_Common($confObj);

        $method_1 = 'service_sameday';
        $method_2 = 'service_later';

        if (($method == $method_1 || $method == $method_2) && is_array($quotes)) {
            foreach ($quotes as $quote) {

                if ($commonObj->checkDeliveryOptionIsAllowed($quote->getCarrierId(), $method, $readyAt)) {
                    $count++;
                    $methodTitle = $commonObj->getMethodTitle($method, $quote->getCarrierId());

                    // Enabled check
                    if (
                        (isset($this->custom_services[$method_1][$quote->getCarrierId()])
                            && empty($this->custom_services[$method_1][$quote->getCarrierId()]['enabled']))
                        && (isset($this->custom_services[$method_2][$quote->getCarrierId()])
                            && empty($this->custom_services[$method_2][$quote->getCarrierId()]['enabled']))
                    ) {
                        // continue;
                    }

                    $this->prepare_rate(
                        $method,
                        $quote->getCarrierId(),
                        $methodTitle,
                        $quote->getTotalPrice(),
                        $quote->getWindows()
                    );
                }
            }
        }

        if ($this->found_rates && !WC()->session->get('sherpa_address_error', FALSE)) {
            uasort($this->found_rates, array($this, 'sort_rates'));

            foreach ($this->found_rates as $key => $rate) {
                $this->add_rate($rate);
            }
        }

        if ($count == 0) {
            if ($this->get_option('debug')) {
                throw new Exception(__('No options available', 'sherpa'));
            }
        }
    }

    private function init() {

        // Autoloader
        include_once('Autoloader.php');

        // Define user set variables
        $this->title = $this->get_option('title', $this->method_title);
        $this->allowed_methods = $this->get_option('allowed_methods');
        $this->origin = $this->get_option('origin_postcode');
        $this->origin_state = $this->get_option('origin_state');
        $this->origin_postcode = $this->get_option('origin_postcode');
        $this->origin_city = $this->get_option('origin_city');
        $this->origin_address = $this->get_option('origin_address');
        $this->custom_services = $this->get_option('services', array());

        // @todo fix this
        $this->origin_country = apply_filters('woocommerce_dhl_origin_country_code', WC()->countries->get_base_country());
        $this->debug = ($bool = $this->get_option('debug')) && $bool == 'yes' ? true : false;

        // Time zone adjustment, which was configured in minutes to avoid time diff with server.
        // Convert that in seconds to apply in date() functions.
        $this->timezone_offset = !empty($this->settings['timezone_offset']) ? intval($this->settings['timezone_offset']) * 60 : 0;

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        //print_r($this->get_option('services', array()));
        
        add_action('woocommerce_update_options_shipping_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
    }

    public function debug($message, $type = 'notice') {

        if ($this->debug) {
            wc_add_notice($message, $type);
        }
    }

    private function environment_check() {

        if (!$this->origin && $this->enabled == 'yes') {
            echo '<div class="error">
<p>' . __('Sherpa is enabled, but the origin postcode has not been set.', 'sherpa') . '</p>
</div>';
        }
    }

    /**
     * prepare_rate function.
     *
     * @access private
     * @param mixed $rate_code
     * @param mixed $rate_id
     * @param mixed $rate_name
     * @param mixed $rate_cost
     * @return void
     */
    private function prepare_rate($rate_code, $rate_id, $rate_name, $rate_cost, $rate_windows) {

        // Merging
        if (isset($this->found_rates[$rate_id])) {
            $rate_cost = $rate_cost + $this->found_rates[$rate_id]['cost'];
            $packages = 1 + $this->found_rates[$rate_id]['packages'];
        } else {
            $packages = 1;
        }

        // Sort
        if (isset($this->custom_services[$rate_code]['order'])) {
            $sort = $this->custom_services[$rate_code]['order'];
        } else {
            $sort = 999;
        }

        $this->found_rates[$rate_id] = array(
            'id' => $rate_code . '_' . $rate_id,
            'label' => $rate_name,
            'cost' => $rate_cost,
            'sort' => $sort,
            'packages' => $packages,
            'windows'  => $rate_windows
        );
    }

    public function admin_options() {

        // Check users environment supports this method
        // $this->environment_check();

        // Show settings
        parent::admin_options();
    }

    public function init_form_fields() {

        $this->form_fields = include(SHERPA_PLUGIN_DIR . '/templates/adminhtml/shipping-settings.php');
    }

    public function generate_services_html() {

        ob_start();
        include(SHERPA_PLUGIN_DIR . '/templates/adminhtml/services.php');
        return ob_get_clean();
    }

    /**
     * validate_services_field function.
     *
     * @access public
     * @param mixed $key
     * @return void
     */
    public function validate_services_field($key) {

        $services = array();
        $posted_data = $this->get_post_data();
        $posted_services = (isset($posted_data['sherpa_service']) && is_array($posted_data['sherpa_service'])) ? $_POST['sherpa_service'] : array();

        if (empty($posted_services))
            return;

        // Loop.
        foreach ($posted_services as $code => $settings) {
            $name = isset($settings['name']) ? sanitize_text_field($settings['name']) : '';
            $order = (isset($settings['order']) && is_numeric($settings['order'])) ? sanitize_text_field($settings['order']) : '';

            $services[$code] = array(
                'name' => wc_clean($name),
                'order' => wc_clean($order)
            );

            $all_services = isset($this->services[$code]['services'])
                ? $this->services[$code]['services'] : array();

            // Have services?
            if (!empty($all_services)) {
                foreach ($all_services as $key => $name) {
                    $services[$code][$key]['enabled'] = isset($settings[$key]['enabled']) ? true : false;
                }
            }
        }

        // Returns the active services to store
        return $services;
    }
}
