<?php

class Sherpa_Api_Client extends Sherpa_Api_Abstract {

    const NO_SHIP_DATE = 'Unable to send on the specified date and/or location.';

    protected $sherpaApiSandbox = 'https://qa.deliveries.sherpa.net.au/api/1/';
    protected $sherpaApi        = 'https://deliveries.sherpa.net.au/api/1/';

    protected $_client = null;
    protected $_is_sand = null;
    protected $_configurations;

    public function __construct(Sherpa_Configurations $configurations) {

        $this->_configurations = $configurations;
    }

    public function connect($username = null, $password = null, $sandbox = false, $forceCheck = false) {

        $oauth = Sherpa_Api_Factory::build('oauth');
        $endPoint = $oauth->getEndPoint();

        $this->_is_sand = $sandbox;

        if ($sandbox) {
            $url = $this->sherpaApiSandbox;
        } else {
            $url = $this->sherpaApi;
        }

        $url = $url . $endPoint;
        if (!$username) {
            $username = $this->_configurations->get('sherpa_credentials/account');
        }
        if (!$password) {
            $password = $this->_configurations->get('sherpa_credentials/password');
        }

        $token_file = new Sherpa_Varien_Io_File();
        if (!$token_file->fileExists(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, TRUE) || $forceCheck) {
            $parameters = $oauth->makeRequestParams($username, $password, TRUE);
            $token_file->rm(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, $this->getRefreshToken());
        } else {
            $parameters = $oauth->makeRequestParams($username, $password);
        }

        try {

            $sherpa_access = get_option('sherpa_access_data', TRUE);
            $refresh_token = isset($sherpa_access['refresh_token']) ? $sherpa_access['refresh_token'] : '';
            $access_token  = isset($sherpa_access['access_token'])  ? $sherpa_access['access_token']  : '';
            $token_type    = isset($sherpa_access['token_type'])    ? $sherpa_access['token_type']    : '';
            $expires_in    = isset($sherpa_access['expire_date'])   ? $sherpa_access['expire_date']   : '';
            $now            = date('Y-m-d H:i:s');

            $needs_request = true;
            if ($refresh_token && $access_token && $token_type && $expires_in && $expires_in > $now && !$forceCheck) {

                $this->setRefreshToken($refresh_token);
                $this->setAccessToken($access_token);
                $this->setTokenType($token_type);
                $needs_request = false;
            }

            if ($needs_request) {
                $response = wp_remote_post(
                    $url,
                    array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => $oauth->makeRequestHeaders(),
                        'body' => $parameters,
                    )
                );

                if ($response) {
                    if (isset($response->errors)) {
                        $error_messages = $response->errors;
                        foreach ($error_messages as $key => $error_message) {
                            switch ($error_message) {
                                case 'invalid_token':
                                    $token_file->rm(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME);
                                    break;
                                case 'invalid_client':
                                    // @todo write handling logic
                                    break;
                                default:
                                    if (is_array($error_message)) {
                                        throw new Exception($error_message[0]);
                                    }
                                    break;
                            }
                        }
                    } else {
                        $response = json_decode($response['body']);
                        $refresh_token = $response->refresh_token;
                        $access_token  = $response->access_token;
                        $token_type    = $response->token_type;
                        $expires_in    = $response->expires_in;

                        $this->setRefreshToken($refresh_token);
                        $this->setAccessToken($access_token);
                        $this->setTokenType($token_type);

                        $expire_date = date('Y-m-d H:i:s', strtotime("+ $expires_in seconds"));

                        $token_data = array('refresh_token' => $refresh_token, 'access_token' => $access_token, 'token_type' => $token_type, 'expire_date' => $expire_date);

                        update_option('sherpa_access_data', $token_data);

                        // save refresh token to file if not exists
                        $token_file = new Sherpa_Varien_Io_File();
                        if ($this->getRefreshToken() && $token_file->fileExists(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, TRUE)) {
                            $token_file->write(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, $this->getRefreshToken());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $token_file = new Sherpa_Varien_Io_File();
            $token_file->rm(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME);
            throw new Exception($e->getMessage());
        }


        return $this;
    }

    public function makeRequest($endPoint, $method, $params, $headers = array()) {

        if ($this->_is_sand) {
            $url = $this->sherpaApiSandbox;
        } else {
            $url = $this->sherpaApi;
        }

        $url = $url . $endPoint;

        //add authorization header
        $headers['Authorization'] = $this->getTokenType() . ' ' . $this->getAccessToken();

        if ($method == 'GET') {
            $url = $url . '?' . http_build_query($params);
            $response = wp_remote_get(
                $url,
                array(
                    'method' => 'GET',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => $headers,
                    'body' => $params,
                )
            );
        } else {
            // $url = $url.'?'.http_build_query($params);
            $response = wp_remote_post(
                $url,
                array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => $headers,
                    'body' => $params,
                )
            );
        }

        return $response;
    }
}
