<?php

abstract class Sherpa_Api_Abstract {

    private $_confObj;
    private $_timeObj;
    private $_requestObj;
    private $_quoteObj;

    protected $data = array();

    protected $_endpoint = null;

    public function __construct(Sherpa_Configurations $confObj, Sherpa_Time $timeObj, Sherpa_Api_Request $requestObj, Sherpa_Quote $quoteObj) {

        $this->_confObj    = $confObj;
        $this->_timeObj    = $timeObj;
        $this->_requestObj = $requestObj;
        $this->_quoteObj   = $quoteObj;
    }

    public function __call($method, $args) {

        switch (substr($method, 0, 3)) {
            case 'get':
                $key = strtolower(substr($method, 3));
                $data = isset($this->data[$key]) ? $this->data[$key] : FALSE;
                return $data;

            case 'set':
                $key = strtolower(substr($method, 3));
                $this->data[$key] = isset($args[0]) ? $args[0] : null;
                return $this;
        }
    }

    public function getConfObj() {

        return $this->_confObj;
    }

    public function getTimeObj() {

        return $this->_timeObj;
    }

    public function getQuoteObj() {

        return $this->_quoteObj;
    }

    /**
     *
     * @param unknown $request
     * @return string
     */
    protected function _getDeliveryAddress($request) {

        $dest_country  = $this->_requestObj->getDestCountry();
        $dest_state    = $this->_requestObj->getDestRegion();
        $dest_postal   = $this->_requestObj->getDestPostcode();
        $dest_city     = $this->_requestObj->getDestCity();
        $dest_street   = $this->_requestObj->getDestStreet();
        $dest_street_2 = $this->_requestObj->getDestStreet2();
        $dest_address  = $dest_street . ' ' . $dest_street_2 . ' ' . $dest_city;

        if ($dest_state) {
            $dest_region_name = $this->_confObj->getRegionName();
            if ($dest_region_name) {
                $dest_address .= ' ' . $dest_region_name;
            } else {
                $dest_address .= ' ' . $dest_state;
            }
        }

        $dest_address .= ' ' . $dest_postal;

        if ($dest_country) {
            $dest_country_name = $this->_confObj->getCountryName();
            $dest_address     .= ' ' . $dest_country_name;
        }

        return $dest_address;
    }


    /**
     *
     * @return string
     */
    protected function _getPickupAddress() {

        $pickup_country  = $this->_confObj->getCountryId();
        $pickup_state    = $this->_confObj->_getRegionId();
        $pickup_postal   = $this->_confObj->getPostcode();
        $pickup_city     = $this->_confObj->getCity();
        $pickup_street   = $this->_confObj->getStreetLine1();
        $pickup_street_2 = $this->_confObj->getStreetLine2();
        $pickup_address  = $pickup_street . ' ' . $pickup_street_2 . ' ' . $pickup_city;

        if ($pickup_state) {
            $pickup_region_name = $this->_confObj->getRegionName();
            if ($pickup_region_name) {
                $pickup_address .= ' ' . $pickup_region_name;
            } else {
                $pickup_address .= ' ' . $pickup_state;
            }
        }

        $pickup_address .= ' ' . $pickup_postal;

        if ($pickup_country && class_exists('Mage')) {
            $pickup_country_name = Mage::app()->getLocale()->getCountryTranslation($pickup_country);
            $pickup_address     .= ' ' . $pickup_country_name;
        }

        return $pickup_address;
    }
}
