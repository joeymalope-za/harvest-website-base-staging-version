<?php

class Sherpa_Api_Request extends Sherpa_Api_Abstract {

	protected $_client;
	protected $_conf;

	public function __construct(Sherpa_Configurations $conf = NULL) {

		$this->_client = new Sherpa_Api_Client($conf);
		$this->_conf = $conf;
	}

	public function getVerification() {
		try {
			$this->_client->connect($this->getUsername(), $this->getPassword(), $this->getSandbox(), TRUE);
			if ($this->_client->getAccessToken()) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Gets all available sherpa quotes for this request.
	 *
	 * @return sherpa_sherpa_Model_Mysql4_Quote_Collection
	 */
	public function getQuotes() {
		if (!$quotes = $this->_fetchQuote()) {
			// validation failed
			return false;
		}

		return $quotes;
	}

	/**
	 * Fetches the quotes and saves them into the database.
	 *
	 * @throws Exception
	 */
	protected function _fetchQuote() {
		$request = $this->toRequestArray();
		if (!$request) {
			return false;
		}

		try {

			$this->_client->connect(
				$this->_conf->getUsername(),
				$this->_conf->getPassword(),
				$this->_conf->getSandbox()
			);

			if ($this->_client->getAccessToken()) {

				$priceCalculator = Sherpa_Api_Factory::build('pricecalculator', array($this->_client, $this->_conf));
				return $priceCalculator->getQuotes($request, $this->_client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Create a new Delivery in Sherpa console.
	 *
	 * @return array
	 */
	public function createNewDelivery() {
		if (!$delivery = $this->_createDelivery()) {
			// validation failed
			return false;
		}

		return $delivery;
	}

	/**
	 * Get user details in Sherpa console.
	 *
	 * @return array
	 */
	public function getUserDetails() {
		if (!$user = $this->_getUserData()) {
			// validation failed
			return false;
		}

		return $user;
	}


	/**
	 * validate Delivery address in Sherpa console.
	 *
	 * @return array
	 */
	public function validateDelivery() {
		if (!$delivery = $this->_validateDelivery()) {
			// validation failed
			return false;
		}

		return $delivery;
	}

	/**
	 * Connect to Sherpa and create a new Delivery in the console.
	 *
	 * @throws Exception
	 */
	protected function _createDelivery() {
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		$deliveryObj = null;

		try {
			$this->_client->connect($this->_conf->getUsername(), $this->_conf->getPassword(), $this->_conf->getSandbox());

			if ($this->_client->getAccessToken()) {

				$sherpa = Sherpa_Api_Factory::build('deliveries', array($this->_client, $this->_conf));
				$deliveryObj = $sherpa->createDelivery($request, $this->_client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $deliveryObj;
	}

  protected function _getUserData() {
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		$userObj = null;

		try {
			$this->_client->connect($this->_conf->getUsername(), $this->_conf->getPassword(), $this->_conf->getSandbox());

			if ($this->_client->getAccessToken()) {

				$sherpa = Sherpa_Api_Factory::build('users', array($this->_client, $this->_conf));
				$userObj = $sherpa->getUserDetails($request, $this->_client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $userObj;
	}

	/**
	 * Connect to Sherpa and validate Delivery address in the console.
	 *
	 * @throws Exception
	 */
	protected function _validateDelivery() {
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		$deliveryObj = null;

		try {
			$this->_client->connect($this->_conf->getUsername(), $this->_conf->getPassword(), $this->_conf->getSandbox());

			if ($this->_client->getAccessToken()) {

				$sherpa = Sherpa_Api_Factory::build('deliveries', array($this->_client, $this->_conf));
				$deliveryObj = $sherpa->validateDelivery($request, $this->_client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $deliveryObj;
	}

	/**
	 * Get Tracking token by Delivery Id.
	 *
	 * @return array
	 */
	public function trackDelivery($deliveryId) {
		if (!$tracking = $this->_trackDelivery($deliveryId)) {
			// validation failed
			return false;
		}

		return $tracking;
	}

	/**
	 * Connect to Sherpa and get the tracking token of the delivery.
	 *
	 * @throws Exception
	 */
	protected function _trackDelivery($deliveryId) {
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		//append Item description field to the request array.
		if ($this->getItemDescription()) {
			$request['item_description'] = $this->getItemDescription();
		}

		try {
			$client = Mage::getModel('sherpa/api_client')->connect($this->getUsername(), $this->getPassword(), $this->getSandbox());

			if ($client->getAccessToken()) {
				$tracking = Mage::getModel('sherpa/api_request_tracking');
				$tracking_response = $tracking->trackDelivery($request, $client, $deliveryId);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $tracking_response;
	}


	public function setDestination($country, $postcode, $region, $city, $street = null, $street2 = null, $company = null) {
		$country  = ($country == 'AU') ? 'Australia' : $country;
		$this->setDestinationCountry($country)
			->setDestinationPostcode($postcode)
			->setDestinationCity($city)
			->setDestinationRegion($region)
			->setDestinationCompany($company)
			->setDestinationStreet2($street2)
			->setDestinationStreet($street);
		return $this;
	}

	public function toRequestArray($order_id = null, $deliveryOption = null, $readyAt = null) {

		if (!$this->validate()) {
			return false;
		}

		$return = array(
			'destCountry' => $this->getDestinationCountry(),
			'destPostcode' => $this->getDestinationPostcode(),
			'destCity' => $this->getDestinationCity(),
			'destRegion' => $this->getDestinationRegion(),
			'destStreet' => $this->getDestinationStreet(),
			'destStreet2' => $this->getDestinationStreet2(),
			'destCompany' => $this->getDestinationCompany(),
			'order_id' => $this->getOrderId(),
			'deliveryOption' => $this->getDeliveryOption(),
			'vehicle_id' => $this->getVehicleId(),
			'item_description' => $this->getItemDescription(),
			'recipient_contact_name' => $this->getRecipientName(),
			'recipient_phone_number' => $this->getRecipientPhone(),
			'delivery_instructions' => $this->getDeliveryInstructions(),
			'notes' => $this->getNotes()
		);

		if ($this->getReadyAt()) {
			$return['readyAt'] = $this->getReadyAt();
		}

		// @todo add code here
		return $return;
	}

	public function validate() {
		// @todo add validations
		return true;
	}

	/**
	 * Gets Delivery Area Availability from this request.
	 *
	 * @return Boolean
	 */
	public function checkDeliveryAreaAvailability() {
		if (!$isSherpaAvailable = $this->_checkDeliveryArea()) {
			// validation failed
			return false;
		}

		return $isSherpaAvailable;
	}

	/**
	 * Create Campaign Delivery from this request.
	 *
	 * @return Boolean
	 */
	public function createCampaignDelivery() {
		if (!$status = $this->_createCampaignDelivery()) {
			// validation failed
			return false;
		}

		return $status;
	}

	/**
	 * Check Sherpa Delivery Area Availability.
	 *
	 * @throws Exception
	 */
	protected function _checkDeliveryArea() {
		$isSherpaAvailable = false;
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		try {
			$client = Mage::getModel('sherpa/api_client')->connect($this->getUsername(), $this->getPassword(), $this->getSandbox());

			if ($client->getAccessToken()) {
				$sherpaAvailability = Mage::getModel('sherpa/api_request_deliveryarea');
				$isSherpaAvailable = $sherpaAvailability->getSherpaAvailability($request, $client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $isSherpaAvailable;
	}

	/**
	 * Check Sherpa Delivery Area Availability.
	 *
	 * @throws Exception
	 */
	protected function _createCampaignDelivery() {
		$response = false;
		$request = $this->toRequestArray();

		if (!$request) {
			return false;
		}

		try {
			$client = Mage::getModel('sherpa/api_client')->connect($this->getUsername(), $this->getPassword(), $this->getSandbox());

			if ($client->getAccessToken()) {
				$campaign = Mage::getModel('sherpa/api_request_campaigns');
				$response = $campaign->createCampaignDelivery($request, $client);
			} else {
				throw new Exception('Unable to connect to sherpa');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $response;
	}
}
