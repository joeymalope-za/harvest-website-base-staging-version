<?php

class Sherpa_Api_Endpoints_Pricecalculator {

	// protected $_endpoint = 'price_calculators/delivery_options.json';
	protected $_endpoint = 'price_calculators/delivery_windows.json';

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

		$dest_country = isset($request['destCountry'])  ? $request['destCountry']  : '';
		$dest_state   = isset($request['destRegion'])   ? $request['destRegion']   : '';
		$dest_postal  = isset($request['destPostcode']) ? $request['destPostcode'] : '';
		$dest_city    = isset($request['destCity'])     ? $request['destCity']     : '';
		$dest_street  = isset($request['destStreet'])   ? $request['destStreet']   : '';

		if (trim($dest_postal) == '') {
			return '';
		}

		$dest_address = $dest_street . ' ' . $dest_city . ' ' . $dest_state . ' ' . $dest_postal . ' ' . $dest_country;

		return preg_replace('/\s+/', ' ', trim($dest_address));
	}

	/**
	 *
	 * @param unknown $request
	 * @return multitype:string NULL
	 */
	public function makeParams($request) {
		// @todo set values of these params
		$pickup_address = $this->_getPickupAddress();
		$delivery_address = $this->_getDeliveryAddress($request);
		$current_ready_at = new DateTime('now', new DateTimeZone(wp_timezone_string()));
		$params = array(
			'vehicle_id' => $this->conf->getVehicleId(),
			'pickup_address' => $pickup_address,
			'delivery_address' => $delivery_address,
			'delivery_address_city' => $request['destCity'],
			'delivery_address_postal_code' => $request['destPostcode'],
			'ready_at' => isset($request['readyAt']) ? $request['readyAt']->format(datetime::ISO8601) : $current_ready_at->format(DateTime::ISO8601),
			'is_update' => 'false',
			'purchase_item' => 'false'
		);

		return $params;
	}

	public function getQuotes($request) {

		$params 		  = $this->makeParams($request);
		$ready_at 		  = $request['readyAt'];
		$is_today 		  = false;
		$delivery_options = array();

		$now = new DateTime('now', new DateTimeZone(wp_timezone_string()));

		if ($now->format('Y-m-d') == $ready_at->format('Y-m-d')) {
			$is_today = true;
		}

		if ($is_today) {

			$later_0 = get_option('sherpa_settings_later_delivery_options_service_2hr', TRUE);
			$later_1 = get_option('sherpa_settings_later_delivery_options_service_4hr', TRUE);
			$later_2 = get_option('sherpa_settings_later_delivery_options_service_at', TRUE);
			$later_5 = get_option('sherpa_settings_later_delivery_options_service_1hr', TRUE);
			$later_6 = get_option('sherpa_settings_later_delivery_options_service_bulk_rate', TRUE);

			if ($later_0) {
				$delivery_options[] = 0;
			}

			if ($later_1) {
				$delivery_options[] = 1;
			}

			if ($later_2) {
				$delivery_options[] = 2;
			}

			if ($later_5) {
				$delivery_options[] = 5;
			}

			if ($later_6) {
				$delivery_options[] = 6;
			}
		} else {

			$same_0 = get_option('sherpa_settings_sameday_delivery_options_service_2hr', TRUE);
			$same_1 = get_option('sherpa_settings_sameday_delivery_options_service_4hr', TRUE);
			$same_2 = get_option('sherpa_settings_sameday_delivery_options_service_at', TRUE);
			$same_5 = get_option('sherpa_settings_sameday_delivery_options_service_1hr', TRUE);
			$same_6 = get_option('sherpa_settings_sameday_delivery_options_service_bulk_rate', TRUE);

			if ($same_0) {
				$delivery_options[] = 0;
			}

			if ($same_1) {
				$delivery_options[] = 1;
			}

			if ($same_2) {
				$delivery_options[] = 2;
			}

			if ($same_5) {
				$delivery_options[] = 5;
			}

			if ($same_6) {
				$delivery_options[] = 6;
			}
		}

		$operating_days = get_option('sherpa_delivery_settings_operating_day');
		$operating_time = get_option('sherpa_delivery_settings_operating_time_wrapper');

		$days_array  = $operating_days ? explode(',', $operating_days) : array();
		$hours_array = $operating_time ? explode(',', $operating_time) : array();

		$start_time = isset($hours_array[0]) ? $hours_array[0] : 9;
		$end_time   = isset($hours_array[1]) ? $hours_array[1] : 21;

		if (!is_numeric($start_time)) {
			$start_time = date('G', strtotime($hours_array[0]));
		}

		if (!is_numeric($end_time)) {
			$end_time = date('G', strtotime($hours_array[1]));
		}

		foreach ($days_array as $day) {

			switch (trim($day)) {
				case 1:
					$params['business_hours']['monday']['start'] = $start_time;
					$params['business_hours']['monday']['end'] = $end_time;
					break;

				case 2:
					$params['business_hours']['tuesday']['start'] = $start_time;
					$params['business_hours']['tuesday']['end'] = $end_time;
					break;

				case 3:
					$params['business_hours']['wednesday']['start'] = $start_time;
					$params['business_hours']['wednesday']['end'] = $end_time;
					break;

				case 4:
					$params['business_hours']['thursday']['start'] = $start_time;
					$params['business_hours']['thursday']['end'] = $end_time;
					break;

				case 5:
					$params['business_hours']['friday']['start'] = $start_time;
					$params['business_hours']['friday']['end'] = $end_time;
					break;

				case 6:
					$params['business_hours']['saturday']['start'] = $start_time;
					$params['business_hours']['saturday']['end'] = $end_time;
					break;

				case 7:
					$params['business_hours']['sunday']['start'] = $start_time;
					$params['business_hours']['sunday']['end'] = $end_time;
					break;
			}
		}

		$params['delivery_options'] = json_encode($delivery_options);

		$this->conf->getLogger()->add(
			Sherpa_Sherpa::LOG_FILE_NAME,
			'PriceCalculator Params -> ' . print_r($params, true)
		);
		$headers = array(
			'X-App-Token' => 'user_sherpa_api'
		);
		$response = $this->client->makeRequest($this->_endpoint, 'GET', $params, $headers);
		$response = (object) $response;

    if(isset($response->response['code'])) {
		  if ($response->response['code'] != '200') {
		  	if (isset($response->body)) {
		  		$response = json_decode($response->body);
		  	}
		  	throw new Exception($response->error);
		  }
    }
		$response = json_decode($response->body);

		$this->conf->getLogger()->add(
			Sherpa_Sherpa::LOG_FILE_NAME,
			'PriceCalculator response -> ' . print_r($response, true)
		);

		// make sure we have an array
		if (!isset($response->delivery_options)) {
			$response->delivery_options = array();
		} else if (isset($response->delivery_options) && !is_array($response->delivery_options)) {
			$response->delivery_options = array(
				0 => $response->delivery_options
			);
		}

		$quotes = array();
		foreach ($response->delivery_options as $key => $quoteData) {

			$quoteData 				 = (object) $quoteData;
			$quoteData->currency     = $response->currency;
			$quoteData->distance     = ($response->distance) / 1000;
			$quoteData->coupon_value = isset($response->coupon_value) ? $response->coupon_value : '';

			$quoteObj      = new Sherpa_Quote(new Sherpa_Rate($this->conf), $this->conf);
			try {
				$responseObj   = $quoteObj->loadResponse($quoteData);
				$quotes[$key]  = $responseObj;
			} catch (Exception $e) {
				continue;
			}
		}

		return $quotes;
	}
}
