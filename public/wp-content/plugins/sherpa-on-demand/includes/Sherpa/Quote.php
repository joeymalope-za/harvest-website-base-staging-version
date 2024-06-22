<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Sherpa
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sherpa Library Quote
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Quote extends Sherpa_Abstract {

    protected $_carrier = null;
    private $_rateObj = null;
    private $_confObj = null;

    public function __construct(Sherpa_Rate $rateObj, Sherpa_Configurations $confObj) {

        $this->_rateObj = $rateObj;
        $this->_confObj = $confObj;
    }


    /**
     * Loads values into this object
     *
     * @param stdClass $response the SOAP response directly from the Sherpa
     * API.
     */
    public function loadResponse(stdClass $response) {

        $option_id = false;
        switch ($response->delivery_option) {

            // 2 Hour Delivery: delivery_option => 0
            case 0:
                $option_id = 'service_2hr';
                break;

            // 4 Hour Delivery: delivery_option => 1
            case 1:
                $option_id = 'service_4hr';
                break;

            // Same Day Delivery: delivery_option => 2
            case 2:
                $option_id = 'service_at';
                break;

            // 1 Hour Delivery: delivery_option => 5
            case 5:
                $option_id = 'service_1hr';
                break;

            // Bulk Rate: delivery_option => 6
            case 6:
                $option_id = 'service_bulk_rate';
                break;

            default:
                $option_id = false;
                break;
        }

        if ($response instanceof stdClass && $option_id) {
            $this
                ->setCurrency($response->currency)
                ->setCouponValue($response->coupon_value)
                ->setDistance($response->distance)
                ->setCarrierId($option_id)
                ->setWindows($response->windows);

            // get rate as per delivery rates admin configuration.
            $price = $this->_rateObj->calculateRate($response->distance, $response->price, $option_id);

            if ($price == Sherpa_Sherpa::DELIVERY_NOT_ALLOWED) {
                $currentRadiusDistance = $this->_confObj->getRadius();
                $this->setTotalPrice($price);
                throw new Exception("Sorry, delivery is not available in your area.");
            } else {
                $this->setTotalPrice($price);
            }
        }

        return $this;
    }
}
