<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Autoloader
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shepa Users Endpoint.
 *
 * @author Sherpa Team <shreejai@sherpa.net.au>
 */
class Sherpa_Api_Endpoints_Users {

	protected $_endpoint         = '/users.json';
	protected $_validateEndpoint = '/deliveries/validate.json';
	private $client;
	private $conf;

	public function __construct(Sherpa_Api_Client $client, Sherpa_Configurations $conf) {
		$this->client = $client;
		$this->conf = $conf;
	}

	/**
	 *
	 * @param unknown $request
	 * @param unknown $client
	 * @throws Exception
	 * @return unknown
	 */
	public function getUserDetails($request, $client) {

		$headers = array(
			'X-App-Token' => 'user_sherpa_api'
		);
		$response = $client->makeRequest($this->_endpoint, 'GET', [], $headers);

		$response = (object)$response;

		if ($response->response['code'] != '200') {
			throw new Exception($response->response['message']);
		}
		$response = json_decode($response->body);
		$this->conf->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'User response -> ' . print_r($response, true));
		return $response;
	}
}
