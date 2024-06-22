<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Helper_Rate
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shipping rate helper
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Rate extends Sherpa_Abstract {

    private $_confObj = null;

    public function __construct(Sherpa_Configurations $confObj) {

        $this->_confObj = $confObj;
    }

    /**
     * The function return the shipping delivery rate as per the distance and Delivery rate selected.
     *
     * @param String distance
     * @param String price.
     * @return String
     */
    public function calculateRate($distance, $price, $option = NULL) {

        $newRate = $price;

        // check current delivery rate selection.
        // How to get Config value in wordpress.
        $deliveryRate = $this->_confObj->getDeliveryRates();

        switch ($deliveryRate) {
            case Sherpa_Sherpa::DELIVERY_RATE_SHERPA:
                $newRate = $price;
                break;
            case Sherpa_Sherpa::DELIVERY_RATE_FLAT:
                switch ($option) {
                    case Sherpa_Sherpa::OPTION_SERVICE_1_HOUR:
                        $flat_rate = $this->_confObj->getData('flat_rate_1_hour');
                        $outsideRadius = $this->_confObj->getData('outside_radius_1_hour');
                        break;
                    case Sherpa_Sherpa::OPTION_SERVICE_2_HOUR:
                        $flat_rate = $this->_confObj->getData('flat_rate_2_hour');
                        $outsideRadius = $this->_confObj->getData('outside_radius_2_hour');
                        break;
                    case Sherpa_Sherpa::OPTION_SERVICE_4_HOUR:
                        $flat_rate = $this->_confObj->getData('flat_rate_4_hour');
                        $outsideRadius = $this->_confObj->getData('outside_radius_4_hour');
                        break;
                    case Sherpa_Sherpa::OPTION_SERVICE_ANYTIME:
                        $flat_rate = $this->_confObj->getData('flat_rate_same_day');
                        $outsideRadius = $this->_confObj->getData('outside_radius_same_day');
                        break;
                    case Sherpa_Sherpa::OPTION_SERVICE_BULK_RATE:
                        $flat_rate = $this->_confObj->getData('flat_rate_bulk_rate');
                        $outsideRadius = $this->_confObj->getData('outside_radius_bulk_rate');
                        break;
                }

                // check whether the delivery is inside radius.
                $flat_rate = $flat_rate;
                $radiusRate = $this->getRadiusPrice($distance, $flat_rate);
                if ($radiusRate) {
                    $flatRateAmount = $this->_confObj->getData('flat_rate');
                    $newRate = $radiusRate;
                } else {
                    $newRate = ($outsideRadius == Sherpa_Sherpa::SHERPA_STANDARD_RATES) ? $price : Sherpa_Sherpa::DELIVERY_NOT_ALLOWED;
                }
                break;

            case Sherpa_Sherpa::DELIVERY_RATE_MARGIN:
                $margin = $this->_confObj->getAddMargin();
                $addedMargin = $this->calculatePercentage($margin, $price);

                // adding margin percentage amount to the total price.
                $newRate = $price + $addedMargin;
                break;
        }

        return $newRate;
    }

    /**
     * This function returns the percentage of a number.
     *
     * @param
     * String number, String value.
     * @return String
     */
    public function calculatePercentage($number, $value) {

        $percentAmount = ($number / 100) * $value;
        return $percentAmount;
    }

    /**
     * This function checks the distance between delivery addresses.
     *
     * @param
     * String distance.
     * @return Boolean
     */
    public function getRadiusPrice($distance, $flat_rate) {

        // converting meters into km.
        $calculatedDistance = (int) $distance;
        $price = false;

        // fetching current configured radius values from config.
        if ($flat_rate) {

            // @todo sort by distance_group
            usort($flat_rate, function ($item1, $item2) {

                $item1_delete = isset($item1['delete']) ? $item1['delete'] : false;
                $item2_delete = isset($item2['delete']) ? $item2['delete'] : false;
                $item1_group  = isset($item1['distance_group']) ? $item1['distance_group'] : 0;
                $item2_group  = isset($item2['distance_group']) ? $item2['distance_group'] : 0;

                if (!$item1_delete && !$item2_delete) {
                    if ($item1_group == $item2_group) {
                        return 0;
                    } else {
                        return ($item1_group < $item2_group) ? -1 : 1;
                    }
                } else {
                    return 0;
                }
            });

            foreach ($flat_rate as $distanceOpt) {

                $optGroup  = isset($distanceOpt['distance_group']) ? $distanceOpt['distance_group'] : 0;
                $optPrice  = isset($distanceOpt['price']) ? $distanceOpt['price'] : 0;

                switch ($optGroup) {
                    case 5:
                        if ($distance >= 0 && $distance <= 5) {
                            $price = $optPrice;
                        }
                        break;
                    case 10:
                        if ($distance > 5 && $distance <= 10) {
                            $price = $optPrice;
                        }
                        break;
                    case 20:
                        if ($distance > 10 && $distance <= 20) {
                            $price = $optPrice;
                        }
                        break;
                    case 30:
                        if ($distance > 20 && $distance <= 30) {
                            $price = $optPrice;
                        }
                        break;
                    case 40:
                        if ($distance > 30 && $distance <= 40) {
                            $price = $optPrice;
                        }
                        break;
                    case 50:
                        if ($distance > 40 && $distance <= 50) {
                            $price = $optPrice;
                        }
                        break;
                }
            }
        }

        $this->_confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'getRadiusPrice $price -> ' . print_r($price, TRUE));
        return $price;
    }
}
