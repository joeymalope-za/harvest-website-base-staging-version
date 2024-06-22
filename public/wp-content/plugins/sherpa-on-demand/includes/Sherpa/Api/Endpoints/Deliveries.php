<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Autoloader
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shepa Deliveries Endpoint.
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Api_Endpoints_Deliveries {

	protected $_endpoint         = '/deliveries.json';
	protected $_validateEndpoint = '/deliveries/validate.json';
	private $client;
	private $conf;

	public function __construct(Sherpa_Api_Client $client, Sherpa_Configurations $conf) {

		$this->client = $client;
		$this->conf = $conf;
	}

	/**
	 *
	 * @return string
	 */
	protected function _getPickupAddress() {

		$pickup_state = $this->conf->getOriginState();
		$pickup_postal = $this->conf->getOriginPostcode();
		$pickup_city = $this->conf->getOriginCity();
		$pickup_street = $this->conf->getOriginAddress();
		$pickup_address = $pickup_street . ' ' . $pickup_city . ' ' . $pickup_postal . ' ' . $pickup_state;

		return preg_replace('/\s+/', ' ', trim($pickup_address));
	}

	/**
	 *
	 * @param unknown $request
	 * @return string
	 */
	protected function _getDeliveryAddress($request) {

		$dest_country = $request['destCountry'];
		$dest_state = $request['destRegion'];
		$dest_postal = $request['destPostcode'];
		$dest_city = $request['destCity'];
		$dest_street = $request['destStreet'];
		$dest_address = $dest_street . ' ' . $dest_city . ' ' . $dest_state . ' ' . $dest_postal . ' ' . $dest_country;

		if (trim($dest_postal) == '') {
			return '';
		}

		return preg_replace('/\s+/', ' ', trim($dest_address));
	}

	/**
	 *
	 * @param unknown $request
	 * @return string
	 */
	protected function _getDeliveryAddressUnit($request) {

		$dest_company = isset($request['destCompany']) ? $request['destCompany'] : '';
		$dest_street2 = isset($request['destStreet2']) ? $request['destStreet2'] : '';
		$dest_unit = $dest_company . ' ' . $dest_street2;

		return preg_replace('/\s+/', ' ', trim($dest_unit));
	}

	/**
	 *
	 * @param unknown $request
	 * @return multitype:string NULL
	 */
	protected function makeParams($request) {

		$this->conf->getLogger()->add(
			Sherpa_Sherpa::LOG_FILE_NAME,
			'makeParams() -> $request object -> ' . print_r($request, true)
		);

		// Set values of these params
		$pickup_address = $this->_getPickupAddress();
		$delivery_address = $this->_getDeliveryAddress($request);
		$delivery_address_unit = $this->_getDeliveryAddressUnit($request);
		$current_ready_at = new DateTime('now', new DateTimeZone(wp_timezone_string()));
		$store_name = $this->conf->getStoreName();
		$pickup_instructions = $this->conf->getNotes();
    $current_user = wp_get_current_user();
    $display_name = $current_user->display_name;

		$params = array(
			'vehicle_id' => $this->conf->getVehicleId(),
      'pickup_address_contact_name'=> $display_name, // pickup name added
			'pickup_address' => $pickup_address,
			'pickup_address_instructions' => $pickup_instructions,
			'delivery_address_unit' => $delivery_address_unit,
			'delivery_address' => $delivery_address,
			'delivery_address_city' => $request['destCity'],
			'delivery_address_postal_code' => $request['destPostcode'],
			'delivery_address_contact_name' => isset($request['recipient_contact_name']) ? $request['recipient_contact_name'] : '',
			'delivery_address_phone_number' => isset($request['recipient_phone_number']) ? $request['recipient_phone_number'] : '',
			'delivery_address_instructions' => isset($request['delivery_instructions']) ? $request['delivery_instructions'] : '',
			'item_description' => isset($request['item_description']) ? $request['item_description'] : 'Test description',
			'notes' => isset($request['notes']) ? $request['notes'] : '',
			'delivery_option' => isset($request['deliveryOption']) ? $request['deliveryOption'] : 0,
			'ready_at' => isset($request['readyAt']) ? $request['readyAt'] : $current_ready_at->format(DateTime::ISO8601),
			'order_id' => isset($request['order_id']) ? $request['order_id'] : 0,
			'shop_name' => $store_name,
			'internal_reference_id' => isset($request['order_id']) ? $request['order_id'] : '',
			'source' => 'wordpress',
			'leave_unattended' => empty($this->conf->getData('authority_to_leave')) ? 'false' : 'true',
			'check_id' => empty($this->conf->getData('specified_recipient')) ? 'false' : 'true',
			'fragile' => empty($this->conf->getData('contains_fragile_items')) ? 'false' : 'true',
			'alcohol' => empty($this->conf->getData('contains_alcohol')) ? 'false' : 'true',
			'high_vis' => empty($this->conf->getData('requires_hi_vis_vest')) ? 'false' : 'true',
			'tobacco' => empty($this->conf->getData('contains_tobacco')) ? 'false' : 'true',
			'prescription_meds' => empty($this->conf->getData('contains_scheduled_medication')) ? 'false' : 'true',
			'send_sms_tracking' => empty($this->conf->getData('send_sms')) ? 'false' : 'true',
		);

		$this->conf->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Delivery Request -> ' . print_r($params, true));

		return $params;
	}

	/**
	 *
	 * @param unknown $request
	 * @param unknown $client
	 * @throws Exception
	 * @return unknown
	 */
	public function validateDelivery($request, $client) {

		$params = $this->makeParams($request);

		$headers = array(
			'X-App-Token' => 'user_sherpa_api'
		);
		$response = $client->makeRequest($this->_validateEndpoint, 'POST', $params, $headers);

		$response = (object)$response;

		if ($response->response['code'] != '201') {
			$response = json_decode($response->body);
			if (isset($response->errors)) {
				$errors = (array)$response->errors;
				foreach ($errors as $error) {
					throw new Exception($error[0]);
					break;
				}
			}
		}

		$this->conf->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Delivery validate response -> ' . print_r($response, true));
		return $response;
	}

	/**
	 *
	 * @param unknown $request
	 * @param unknown $client
	 * @throws Exception
	 * @return unknown
	 */
	public function createDelivery($request, $client) {

		$params = $this->makeParams($request);

		$headers = array(
			'X-App-Token' => 'user_sherpa_api'
		);
		$response = $client->makeRequest($this->_endpoint, 'POST', $params, $headers);

		$response = (object)$response;

		if ($response->response['code'] != '201') {
			throw new Exception($response->response['message']);
		}
		$response = json_decode($response->body);
		$this->conf->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Delivery response -> ' . print_r($response, true));
		return $response;
	}
}
