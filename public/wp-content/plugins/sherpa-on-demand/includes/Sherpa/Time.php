<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Helper_Time
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sherpa library Time Helper Class
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Time {

    private $_confObj = null;

    protected $_businessDays;
    protected $_operatingHours;
    protected $_prepTime;
    protected $_cuttOfHour;

    public function __construct(Sherpa_Configurations $confObj) {
        $this->_confObj = $confObj;
    }

    /**
     * This function returns the Time Zone for the Delivery.
     *
     * @param
     *            None
     * @return String
     */
    public function getTimeZone() {

        return wp_timezone_string() ? wp_timezone_string() : Sherpa_Sherpa::TIME_ZONE;
    }

    /**
     * This function check configuration either input day is business day or not
     *
     * @param unknown $day
     * @return boolean
     */
    public function checkBusinessDay(DateTime &$day, $businessDays, $i = 0) {

        // fix for infinite loop
        $i++;
        if (in_array($day->format('N'), $businessDays)) {
            return $day;
        } else {
            if ($i < 9) {
                $interval = $day->add(new DateInterval('P1D'));
                $day = $this->checkBusinessDay($interval, $businessDays, $i);
            } else {
                return false;
            }
        }
        return $day;
    }

    /**
     * This function return nearest available readyTime after checking prepTime
     *
     * @param DateTime $start_time
     * @return DateTime
     */
    public function applyPreperationTime(DateTime &$start_time) {

        list($hour_from, $minute_from) = explode(':', $this->_operatingHours[0]);

        switch ($this->_prepTime) {
            case Sherpa_Sherpa::PREP_TIME_NO:
                $prep_time = clone $start_time;
                $this->checkOperatingHour($start_time);
                if ($prep_time->format('d') != $start_time->format('d')) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                }
                break;
            case Sherpa_Sherpa::PREP_TIME_30MIN:
                $prep_time = clone $start_time;
                $this->checkOperatingHour($start_time);
                $start_time->add(new DateInterval('PT30M'));
                if ($prep_time->format('d') != $start_time->format('d')) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('PT30M'));
                }
                break;
            case Sherpa_Sherpa::PREP_TIME_1HOUR:
                $prep_time = clone $start_time;
                $this->checkOperatingHour($start_time);
                $start_time->add(new DateInterval('PT1H'));
                if ($prep_time->format('d') != $start_time->format('d')) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('PT1H'));
                }
                break;
            case Sherpa_Sherpa::PREP_TIME_2HOUR:
                $prep_time = clone $start_time;
                $this->checkOperatingHour($start_time);
                $start_time->add(new DateInterval('PT2H'));
                if ($prep_time->format('d') != $start_time->format('d')) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('PT2H'));
                }
                break;
            case Sherpa_Sherpa::PREP_TIME_4HOUR:
                $prep_time = clone $start_time;
                $this->checkOperatingHour($start_time);
                $start_time->add(new DateInterval('PT4H'));
                if ($prep_time->format('d') != $start_time->format('d')) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('PT4H'));
                }
                break;
            case Sherpa_Sherpa::PREP_TIME_24HOUR:
                $start_time->add(new DateInterval('P1D'));
                break;
            case Sherpa_Sherpa::PREP_TIME_NEXT_BUSINESS:
                $cuttOfTime = clone $start_time;
                $cuttOfTime->setTime($this->_cuttOfHour, 0, 0);
                if ($start_time > $cuttOfTime) {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('P2D'));
                } else {
                    $start_time->setTime($hour_from, $minute_from, 0);
                    $start_time->add(new DateInterval('P1D'));
                }
                break;
        }
        return $start_time;
    }

    /**
     * This function check if input hour is within config limit or not
     *
     * @param unknown $hour
     * @param unknown $operatingHourMin
     * @param unknown $operatingHourMax
     * @param unknown $i
     * @return boolean
     */
    public function checkOperatingHour(DateTime &$start_time, $i = 0) {

        list($hour_from, $minute_from) = explode(':', $this->_operatingHours[0]);
        list($hour_to, $minute_to) = explode(':', $this->_operatingHours[1]);

        $operating_hour_from = clone $start_time;
        $operating_hour_to = clone $start_time;

        $operating_hour_from->setTime($hour_from, $minute_from);
        $operating_hour_to->setTime($hour_to, $minute_to);

        // fix for infinite loop
        $i++;
        if ($start_time >= $operating_hour_from && $start_time <= $operating_hour_to) {
            return $start_time;
        } else {
            if ($i < 25) {

                $start_time->add(new DateInterval('PT1H'));
                $start_time->setTime($start_time->format('H'), $operating_hour_from->format('i'));
                $start_time = $this->checkOperatingHour($start_time, $i);
            } else {
                return false;
            }
        }
        return $start_time;
    }

    /**
     * This function check if input time is before cutt-off time or not
     *
     * @param unknown $time
     * @return boolean
     */
    public function checkCuttOffTime($time) {

        return false;
    }

    /**
     *
     * @param DateTime $current_time
     * @param DateTime $prep_time
     * @param unknown $operating_hours
     * @param DateTime $cutt_off_time
     * @return DateTime
     */
    public function getDeliveryTime(DateTime $current_time, DateTime $prep_time, $operating_hours, DateTime $cutt_off_time) {

        return new DateTime('', new DateTimeZone($this->getTimeZone()));
    }


    /**
     *
     * This function check the current service options for Sherpa Delivery method.
     *
     * @param DateTime $readyAt
     * @return DateTime
     */
    public function checkServiceOptions(DateTime &$readyAt) {

        // Check if service options are available
        $options = array(
            'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_1_HOUR,
            'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_2_HOUR,
            'service_sameday_' . Sherpa_Sherpa::OPTION_SERVICE_4_HOUR,
        );

        $available = false;
        foreach ($options as $option) {
            $commonObj = new Sherpa_Common($this->_confObj);
            $services = (array) $commonObj->getServiceOptions($option, $readyAt->format('Y-m-d H:i:s'));

            if (count($services) > 0) {
                $available = true;
                break;
            }
        }

        // If service options are not available for same day, then change ready at date
        if (!$available) {
            list($hour_from, $minute_from) = explode(':', $this->_operatingHours[0]);
            $readyAt->add(new DateInterval('P1D'));
            $readyAt->setTime($hour_from, $minute_from, 0);
            $this->checkBusinessDay($readyAt, $this->_businessDays);
            $this->applyPreperationTime($readyAt);
        }

        return $readyAt;
    }

    /**
     *
     * @param DateTime $readyAt
     * @param unknown $buffer
     */
    public function checkDeliveryWindow(DateTime &$readyAt, $buffer = Sherpa_Sherpa::DEFAULT_BUFFER) {

        $current_min = (int)$readyAt->format('i');
        $threshold = Sherpa_Sherpa::BUFFER_THRESHOLD; // threshold of 5 mins
        $window = $current_min % $buffer;
        $add = 'PT0M';

        switch ($window) {
            case 0:
                $add = 'PT' . $buffer . 'M';
                break;
            case $window <= ($buffer - $threshold):
                $add = 'PT' . ($buffer - $window) . 'M';
                break;
            case $window > ($buffer - $threshold):
                $add = 'PT' . (($buffer - $window) + $buffer) . 'M';
                break;
        }

        // Mage::log ('checkDeliveryWindow $add' . print_r ( $add, true ), null, Sherpa_Logistics_Helper_Data::LOG_FILE_NAME );
        $readyAt->add(new DateInterval($add));
    }

    /**
     * This function returns readyAt time
     *
     * @param DateTime $readyAt
     * @return DateTime
     */
    public function getReadyAtTime(DateTime $readyAt = NULL) {

        $current_time = new DateTime('now', new DateTimeZone($this->getTimeZone()));
        if ($current_time > $readyAt) {
            $readyAt = clone $current_time;
        }

        // How to get config values in the library
        // Get options for config
        $this->_businessDays = explode(',', $this->_confObj->getOperatingDay());
        $this->_prepTime = $this->_confObj->getPrepTime();
        $this->_cuttOfHour = $this->_confObj->getCuttoffTime();

        $this->_operatingHours = '';
        if (false !== strpos($this->_confObj->getOperatingTimeWrapper(), ',')) {
            $this->_operatingHours = explode(',', $this->_confObj->getOperatingTimeWrapper());

            // Appends '00'
            if (false == strpos($this->_operatingHours[0], ':')) {
                $this->_operatingHours[0] .= ':00';
            }

            if (false == strpos($this->_operatingHours[1], ':')) {
                $this->_operatingHours[1] .= ':00';
            }
        }

        // Apply conditions
        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'getReadyAtTime readyAt -> ' . print_r($readyAt, true));
        $this->applyPreperationTime($readyAt);
        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'applyPreperationTime -> ' . print_r($readyAt, true));
        $this->checkBusinessDay($readyAt, $this->_businessDays);
        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'applyBusinessDay -> ' . print_r($readyAt, true));

        // Apply buffer only on same day
        if ($current_time->format('d') == $readyAt->format('d')) {
            list($hour_from, $minute_from) = explode(':', $this->_operatingHours[0]);
            list($hour_to, $minute_to) = explode(':', $this->_operatingHours[1]);
            $operating_hour_from = clone $current_time;
            $operating_hour_to = clone $current_time;

            $operating_hour_from->setTime($hour_from, $minute_from);
            $operating_hour_to->setTime($hour_to, $minute_to);

            if ($current_time >= $operating_hour_from && $current_time <= $operating_hour_to) {
                $readyAt->setTime($readyAt->format('H'), $readyAt->format('i'), '0');
                $this->checkDeliveryWindow($readyAt);
                $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'checkDeliveryWindow -> ' . print_r($readyAt, true));
            }
        }

        // Apply serviceWindow
        $this->checkServiceOptions($readyAt);
        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Final ReadyAt -> ' . print_r($readyAt, true));

        // Mage::log ('Final ReadyAt' . print_r ( $readyAt, true ) , null, Sherpa_Logistics_Helper_Data::LOG_FILE_NAME );
        return $readyAt;
    }

    /**
     * This function returns whether or not a value is between a range
     *
     * @param unknown $val
     * @param unknown $min
     * @param unknown $max
     * @return Boolean
     */
    public function betweenRange($val, $min, $max) {

        return ($val >= $min && $val <= $max);
    }
}
