<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Managers\License_Manager;

	class Email_Service {

		public static function is_valid_email($email) {
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}

		public static function is_valid_email_domain($email) {

			$license_info = License_Manager::get_license_info();
			if($license_info === null || !isset($license_info->key) || !isset($license_info->expiration))
				return true;
			$expiry = \DateTime::createFromFormat('Y-m-d H:i:s', $license_info->expiration);
			$today = \DateTime::createFromFormat('Y-m-d H:i:s',current_time('Y-m-d H:i:s'));
			if($expiry < $today)
				return true;

			$response = wp_remote_get('http://mailcheck.studiowombat.com/?mail='.urlencode($email).'&license='.urlencode(Helper_Service::encrypt($license_info->key)));

			if(is_wp_error($response)) return true;

			$response_data = json_decode(wp_remote_retrieve_body($response ));

			if(isset($response_data->error) && $response_data->error === true)
				return false;

			if(isset($response_data->result))
				return $response_data->result;

			return true;
		}

	}
}