<?php
class Sherpa_Api_Endpoints_Oauth {

    protected $_endpoint = 'oauth/token';

    public function __construct() {

        $this->setEndPoint($this->_endpoint);
    }

    public function setEndPoint($endPoint) {

        $this->_endpoint = $endPoint;
    }

    public function getEndPoint() {

        return $this->_endpoint;
    }


    public function makeRequestParams($username, $password, $credentials = FALSE) {

        //use refresh token if exists
        $token_file = new Sherpa_Varien_Io_File();
        if (
            $token_file->fileExists(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, TRUE)
            && !$credentials
        ) {
            $refresh_token = $token_file->read(SHERPA_PLUGIN_DIR . Sherpa_Sherpa::TOKEN_LOG_FILE_NAME, NULL);
            $params = array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id' => 'user',
            );
        } else {
            $params = array(
                'grant_type' => 'password',
                'client_id' => 'user',
                'username' => $username,
                'password' => $password,
            );
        }

        return $params;
    }

    public function makeRequestHeaders() {

        return array(
            'X-App-Token' => 'user_sherpa_api',
        );
    }
}
