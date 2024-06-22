<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Common
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * User data helper
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Common {

    /*
     * Sherpa Delivery Allowed Countries
     */
    protected $_allowedCountries = array(
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
    );

    /*
     * Sherpa_Configurations class injection Object.
     */
    private $_confObj = null;

    public function __construct(Sherpa_Configurations $confObj) {

        $this->_confObj = $confObj;
    }

    /**
     * Returns array of allowed countries based on Magento system configuration
     * and Temando plugin allowed countries.
     *
     * @param boolean $asJson
     * @return array
     */
    public function getAllowedCountries($asJson = false) {

        $specific = $this->_confObj->__get('sallowspecific');
        // check if all allowed and return selected
        if ($specific == 1) {
            $availableCountries = explode(',', $this->_confObj->__get('specificcountry'));
            $countries = array_intersect_key($this->_allowedCountries, array_flip($availableCountries));
            if ($asJson) {
                return json_encode($countries);
            } else {
                return $countries;
            }
        }

        // return all allowed
        if ($asJson) {
            return json_encode($this->_allowedCountries);
        } else {
            return $this->_allowedCountries;
        }
    }

    /**
     * Gets the date when a package will be ready to ship.
     * Adjusts dates so
     * that they always fall on a weekday.
     *
     * @param <type> $ready_time
     *            timestamp for when the package will be ready
     *            to ship, defaults to 10 days from current date
     */
    public function getReadyDate($ready_time = NULL) {

        if (is_null($ready_time)) {
            $ready_time = strtotime('+10 days');
        }
        if (is_numeric($ready_time) && $ready_time >= strtotime(date('Y-m-d'))) {
            $weekend_days = array(
                '6',
                '7'
            );
            while (in_array(date('N', $ready_time), $weekend_days)) {
                $ready_time = strtotime('+1 day', $ready_time);
            }
            return $ready_time;
        }
    }

    /**
     *
     * @param string $date
     * @return NULL|string
     */
    public function getFormatedDeliveryDateToSave($date = null) {

        if (empty($date) || $date == null || $date == '0000-00-00 00:00:00') {
            return null;
        }

        $timestamp = null;
        try {
            // TODO: add Better Date Validation
            $timestamp = strtotime($date);
            $formatedDate = date('Y-m-d H:i:s', strtotime($date));
        } catch (Exception $e) {
            return null;
        }

        return $formatedDate;
    }

    /**
     *
     * @param string $methods
     * @return NULL|string
     */
    public function getAllowedMethods() {

        $methods     = $this->_confObj->getServices();
        $methods_prc = array();
        foreach ($methods as $method_key => $services) {
            if (is_array($services)) {
                foreach ($services as $service) {
                    if (is_array($service) && isset($service['enabled']) && $service['enabled']) {
                        if (!in_array($method_key, $methods_prc)) {
                            $methods_prc[] = $method_key;
                        }
                    }
                }
            }
        }

        return $methods_prc;
    }

    public function getEnabledServices() {

        $methods      = $this->_confObj->getServices();
        $services_prc = array();
        foreach ($methods as $method_key => $services) {
            if (is_array($services)) {
                foreach ($services as $service_key => $service) {
                    if (is_array($service) && isset($service['enabled']) && $service['enabled']) {
                        $services_prc["{$method_key}_{$service_key}"] = 1;
                    }
                }
            }
        }
        return $services_prc;
    }

    /**
     * One hour options.
     */
    public function oneHours($start_time, $end_time) {

        $hours = array();
        $interval = DateInterval::createFromDateString('1 hour');
        $period = new DatePeriod($start_time, $interval, $end_time);
        foreach ($period as $dt) {
            $temp = clone $dt;
            $temp->add(new DateInterval('PT1H'));
            if ($temp <= $end_time) {
                $hours[$dt->format('H') . ':' . $dt->format('i')] = date("g:i a", strtotime($dt->format('H') . ":" . $dt->format('i'))) . ' - ' . date("g:i a", strtotime($temp->format('H') . ":" . $dt->format('i')));
            }
        }
        return $hours;
    }

    /**
     *
     * @start_time, @end_time, @prep_time = store start time , and end time , store prepartion time respectively
     *
     * @return NULL|string
     */
    public function twoHours($start_time, $end_time) {

        $hours = array();
        $interval = DateInterval::createFromDateString('1 hour');
        $period = new DatePeriod($start_time, $interval, $end_time);
        foreach ($period as $dt) {
            $temp = clone $dt;
            $temp->add(new DateInterval('PT2H'));
            if ($temp <= $end_time) {
                $hours[$dt->format('H') . ':' . $dt->format('i')] = date("g:i a", strtotime($dt->format('H') . ":" . $dt->format('i'))) . ' - ' . date("g:i a", strtotime($temp->format('H') . ":" . $dt->format('i')));
            }
        }
        return $hours;
    }

    /**
     *
     * @start_time, @end_time
     *
     * @return NULL|string
     */
    public function fourHours($start_time, $end_time) {

        $hours = array();
        $interval = DateInterval::createFromDateString('1 hour');
        $period = new DatePeriod($start_time, $interval, $end_time);

        $i = true;
        foreach ($period as $dt) {
            $temp = clone $dt;
            $temp->add(new DateInterval('PT4H'));
            if ($temp <= $end_time) {
                $hours[$dt->format('H') . ':' . $dt->format('i')] = date("g:i a", strtotime($dt->format('H') . ":" . $dt->format('i'))) . ' - ' . date("g:i a", strtotime($temp->format('H') . ":" . $dt->format('i')));
            }
        }
        return $hours;
    }

    /**
     *
     * unknown $method, unknown carrier
     *
     * @return NULL|string
     */
    public function getMethodTitle($method, $carrier = null) {

        switch ($method) {
            case Sherpa_Sherpa::METHOD_SAMEDAY:
                if ($carrier) {
                    $title = $this->_confObj->getData('sameday_delivery_options_' . $carrier);
                } else {
                    $title = $this->_confObj->getData('sameday_delivery_options_sameday');
                }
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY:
                if ($carrier) {
                    $title = $this->_confObj->getData('later_delivery_options_' . $carrier);
                } else {
                    $title = $this->_confObj->getData('later_delivery_options_later');
                }
                break;
        }

        return $title;
    }

    public function getMethodTitleAdmin($method, $readyAt) {

        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'readyAt -> ' . print_r($readyAt, TRUE));
        $title = $this->_confObj->getData('title') . ' - ';
        $readyFromAt = new DateTime($readyAt, new DateTimeZone(wp_timezone_string()));
        $readyToAt = clone $readyFromAt;

        switch ($method) {
            case Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR:
                $title .= $this->_confObj->getData('later_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR);
                $readyToAt->add(new DateInterval('PT1H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR:
                $title .= $this->_confObj->getData('later_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR);
                $readyToAt->add(new DateInterval('PT2H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR:
                $title .= $this->_confObj->getData('later_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR);
                $readyToAt->add(new DateInterval('PT4H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME:
                $title .= $this->_confObj->getData('later_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME);
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE:
                $title .= $this->_confObj->getData('later_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE);
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR:
                $title .= $this->_confObj->getData('sameday_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR);
                $readyToAt->add(new DateInterval('PT2H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR:
                $title .= $this->_confObj->getData('sameday_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR);
                $readyToAt->add(new DateInterval('PT2H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR:
                $title .= $this->_confObj->getData('sameday_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR);
                $readyToAt->add(new DateInterval('PT4H'));
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . '-' . $readyToAt->format('g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME:
                $title .= $this->_confObj->getData('sameday_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME);
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . ')';
                break;

            case Sherpa_Sherpa::METHOD_SAMEDAY . '_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE:
                $title .= $this->_confObj->getData('sameday_delivery_options_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE);
                $title .= '(' . $readyFromAt->format('d/m, g:i A') . ')';
                break;
        }

        return $title;
    }

    public function getCurrentTime($date = '', $format = 'date') {

        $timezone = wp_timezone_string() ? wp_timezone_string() : 'Australia/Sydney';

        if ($date) {
            $now = new DateTime($date, new DateTimeZone($timezone));
        } else {
            $now = new DateTime('', new DateTimeZone($timezone));
        }

        $hours = $now->format('H');
        $minutes = $now->format('i');

        $readyDate = $now->setTime($hours, $minutes, 0);

        if ($format == 'time') {
            return $readyDate->format('H:i');
        } else {
            return $readyDate->format('Y-m-d H:i:s');
        }
    }

    /**
     *
     * @param unknown $option_id
     * @param unknown $readyAt
     * @return Ambigous <multitype:, NULL, string, multitype:string >
     */
    public function getServiceOptions($option_id, $readyAt) {

        $readyAt = $this->getCurrentTime($readyAt);
        $readyAt = new DateTime($readyAt, new DateTimeZone(wp_timezone_string()));
        $options = array();

        switch ($option_id) {
            case 'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR:
            case 'service_later_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR:
                $options = $this->hoursOptions(Sherpa_Sherpa::ONE_HOUR_DELIVERY, $readyAt);
                break;

            case 'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR:
            case 'service_later_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR:
                $options = $this->hoursOptions(Sherpa_Sherpa::TWO_HOUR_DELIVERY, $readyAt);
                break;

            case 'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR:
            case 'service_later_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR:
                $options = $this->hoursOptions(Sherpa_Sherpa::FOUR_HOUR_DELIVERY, $readyAt);
                break;

            case 'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME:
            case 'service_later_' . Sherpa_Sherpa::OPTION_SERVICE_ANYTIME:
                $options[] = Sherpa_Sherpa::OPTION_SERVICE_ANYTIME;
                break;

            case 'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE:
            case 'service_later_' . Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE:
                $options[] = Sherpa_Sherpa::OPTION_SERVICE_ANYTIME;
                break;

            default:
                break;
        }

        return (array) $options;
    }

    /**
     *
     * @param unknown $items
     * @return number
     */
    public function getItemsTotal($items) {

        $canShip = false;
        $c = count($items);
        $price = 0;
        for ($i = 0; $i < $c; $i++) {
            if ($items[$i]->getProduct() instanceof Mage_Catalog_Model_Product) {
                $price += $items[$i]->getPrice() * $items[$i]->getQty();
            }
        }
        return $price;
    }

    public function checkDeliveryOptionIsAllowed($option_id, $method, $readyAt) {

        $flag = false;
        switch ($method) {
            case Sherpa_Sherpa::METHOD_SAMEDAY:
                $options = explode(',', $this->_confObj->getServiceSameday());
                break;
            case Sherpa_Sherpa::METHOD_NEXTDAY:
                $options = explode(',', $this->_confObj->getServiceLater());
                break;
        }

        // check if option is allowd in config
        if (in_array($option_id, $options)) {
            $flag = true;
        }

        return $flag;
    }

    public function getViewReadyAtDate($ship_ready_at, $method) {
        $ship_date = null;
        if ($ship_ready_at) {
            $ship_ready_at = new DateTime(
                $ship_ready_at,
                new DateTimeZone(wp_timezone_string())
            );
            $ship_date = $ship_ready_at->format('Y-m-d');
        }

        return $ship_date;
    }

    /**
     *
     * @param
     * @$h_status = either 2hours , 4 hours or any time that day
     * @return NULL|string
     */
    public function hoursOptions($h_status, $start_time) {

        $office_time1 = $this->_confObj->getOperatingTimeWrapper();
        $prep_time    = $this->_confObj->getPrepTime();

        if ($office_time1) {

            // Explode str
            $office_time2 = array_map('trim', explode(',', $office_time1));

            if ($h_status == Sherpa_Sherpa::ONE_HOUR_DELIVERY) {
                $end_time_hour = trim($office_time2[1]);
                if (!is_numeric($end_time_hour)) {
                    $end_time_hour = date('G', strtotime($end_time_hour));
                }
                $end_time = clone $start_time;
                $end_time->setTime($end_time_hour, 0, 0);
                $oneHours = $this->oneHours($start_time, $end_time);
                return $oneHours;
            }

            if ($h_status == Sherpa_Sherpa::TWO_HOUR_DELIVERY) {
                $end_time_hour = trim($office_time2[1]);
                if (!is_numeric($end_time_hour)) {
                    $end_time_hour = date('G', strtotime($end_time_hour));
                }
                $end_time = clone $start_time;
                $end_time->setTime($end_time_hour, 0, 0);
                $twoHours = $this->twoHours($start_time, $end_time);
                return $twoHours;
            }

            if ($h_status == Sherpa_Sherpa::FOUR_HOUR_DELIVERY) {
                $end_time_hour = trim($office_time2[1]);
                if (!is_numeric($end_time_hour)) {
                    $end_time_hour = date('G', strtotime($end_time_hour));
                }
                $end_time = clone $start_time;
                $end_time->setTime($end_time_hour, 0, 0);
                $fourHours = $this->fourHours($start_time, $end_time);
                return $fourHours;
            }
        }
    }

    public function getCreateReadyAtDate() {

        // @todo check selected option , selected date
        $ship_ready_at = WC()->session->get('sherpa_ready_at_date');
        $method_option = WC()->session->get('sherpa_selected_method_option');
        if ($method_option) {
            list($method_option_hour, $method_option_minute) = explode(':', $method_option);
            if ($method_option_hour > 0) {
                // hour should not be 0
                $readyAt = new DateTime($ship_ready_at, new DateTimeZone(wp_timezone_string()));
                $readyAt->setTime($method_option_hour, $method_option_minute, 0);
                $ship_ready_at = $readyAt->format(DateTime::ISO8601);
            }
        }
        return $ship_ready_at;
    }

    public function getViewSelectedMethod($quote_selected_method) {

        return $quote_selected_method;
    }

    public function isViewMethodAvailable($method) {

        $flag = true;
        switch ($method) {
            case Sherpa_Sherpa::METHOD_SAMEDAY:
                $current_time = new DateTime('now', new DateTimeZone(wp_timezone_string()));
                $today = $current_time->format('d');

                $ready_time = $this->_timeObj->getReadyAtTime($current_time);
                $ready_day = $ready_time->format('d');

                if ($today == $ready_day) {
                    $flag = true;
                } else {
                    $flag = false;
                }
                break;

            case Sherpa_Sherpa::METHOD_NEXTDAY:
                // do nothing
                break;
        }
        return $flag;
    }

    public function getCartEstimateFormUrl(Mage_Checkout_Block_Cart_Abstract $block) {

        $magentoVersion = Mage::getVersion();
        if (version_compare($magentoVersion, '1.9.1.1', '>')) {
            $url = $block->getFormActionUrl();
        } else {
            $url = $block->getUrl('checkout/cart/estimatePost');
        }
        return $url;
    }
}
