<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Api_Endpoints_Tracking
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tracking endpoint implementation.
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Api_Endpoints_Tracking {

    protected $_endpoint = '/deliveries/{id}/track.json';

    public function trackDelivery($request, $client, $deliveryId) {

        $headers = array(
            'X-App-Token' => 'user_sherpa_api'
        );

        //api endpoint
        $this->_endpoint = str_replace("{id}", $deliveryId, $this->_endpoint);

        $response = $client->makeRequest($this->_endpoint, Zend_Http_Client::GET, null, $headers);
        $response = (object)$response;

        return $response;
    }
}
