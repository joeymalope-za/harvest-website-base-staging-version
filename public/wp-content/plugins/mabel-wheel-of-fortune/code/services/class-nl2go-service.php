<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class Nl2Go_Service {

		public static function add_to_list($list_id, $email, $fields = []) {

			$values = [
				'email' => $email,
				'list_id' => $list_id,
				'is_unsubscribed' => false,
                'is_blacklisted' => false,
			];

			foreach($fields as $f){
				$values[$f->id] = $f->value;
			}

			$response = self::request('recipients',$values,'post');

			if($response === null || ($response->status !== 200 && $response->status !== 201))
				return "Could not add email to list.";

			return true;

		}

		public static function get_fields_from_list($list_id) {

			$response = self::request('lists/'.$list_id.'/attributes',null,'get');

			$fields = [
				[ 'id' => 'first_name','title' => 'First Name', 'type' => 'text' ],
				[ 'id' => 'last_name', 'title' => 'Last Name', 'type' => 'text' ],
				[ 'id' => 'phone', 'title' => 'Phone', 'type' => 'text' ]
			];

			if($response !== null && $response->status === 200) {

				foreach($response->body['value'] as $v){
					$details = self::request('attributes/'.$v['id'],null,'get');
					$detail = $details->body['value'][0];
					$fields[] = (object) [
						'id' => $detail['name'],
						'title' => $detail['name'],
						'type' => strtolower($detail['type'])
					];
				}

				return $fields;
			}

			return new WP_Error();
		}

		 public static function get_email_lists() {
			$response = self::request('lists',null,'get');

			if($response !== null && $response->status === 200) {
				return Enumerable::from($response->body['value'])->select(function($x){
					$details = self::request('lists/'.$x['id'],null,'get');
					return (object) ['id' => $x['id'], 'title' => $details->body['value'][0]['name'] ];
				})->toArray();
			}

			return new WP_Error();
		}

		private static function request($action,$body = null, $method = 'post') {

			$url = 'https://api.newsletter2go.com/' . $action;
			$access_token = self::get_access_token();

			$headers = [
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers
			];

			if(!empty($body))
				$options['body'] = json_encode($body);

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response))
				return null;

			return (object) [
				'status' => $response['response']['code'],
				'body' => json_decode(wp_remote_retrieve_body($response),true)
			];

		}

		private static function get_access_token() {

			$should_refresh = true;

			$setting = Settings_Manager::get_setting('nl2go_accesstoken');

			if($setting) {
				$setting = json_decode($setting);
				if(time() - $setting->time <= 7190)
					$should_refresh = false;
			}

			if(!$should_refresh)
				return $setting->token;

			$url =  'https://api.newsletter2go.com/oauth/v2/token';

			$headers = [
				'Authorization' => 'Basic ' . base64_encode(Settings_Manager::get_setting('nl2go_authkey')),
				'Content-Type' => 'application/json'
			];

			$body = [
				'username' => Settings_Manager::get_setting('nl2go_u'),
				'password' => Settings_Manager::get_setting('nl2go_pw'),
				'grant_type' => 'https://nl2go.com/jwt'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers,
				'body' => json_encode($body)
			];

			$response = wp_remote_post( $url, $options);

			if(is_wp_error($response))
				return null;

			$result = json_decode(wp_remote_retrieve_body($response), true);
			$token = $result['access_token'];

			Settings_Manager::set_setting('nl2go_accesstoken',json_encode([
				'time' => time(),
				'token' => $token
			]));
			Settings_Manager::save();

			return $token;

		}

	}

}