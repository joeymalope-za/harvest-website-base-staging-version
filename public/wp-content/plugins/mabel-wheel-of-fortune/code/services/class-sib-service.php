<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class SIB_Service {

		public static function action($action,$data = []) {
			$is_v3 = Settings_Manager::get_setting('sib_apiv3');

			if($is_v3) {
				switch ( $action ) {
					case 'add to list':
						return self::add_to_list_v3($data['list'],$data['email'], isset($data['fields']) ? $data['fields'] : []);
					case 'get fields':
						return self::get_fields_from_list_v3();
					default:
						return self::get_email_lists_v3();
				}
			}

			switch ( $action ) {
				case 'add to list':
					return self::add_to_list($data['list_id'],$data['email'], isset($data['fields']) ? $data['fields'] : []);
				case 'get fields':
					return self::get_fields_from_list();
				default:
					return self::get_email_lists();
			}
		}

		public static function add_to_list_v3($list_id,$email, $fields = []) {
			$values = [
				'email' => $email,
				'updateEnabled' => true,
				'listIds' => [ intval($list_id) ],
			];

			if(!empty($fields)) {
				$values['attributes'] = [];
				foreach($fields as $f) {
					$values['attributes'][$f->id] = $f->value;
				}
			}

			$response = self::request('contacts',$values,'post','3');

			if($response === null )
				return "Could not add email to list.";

			if(isset($response->message) && $response->message === 'Invalid phone number')
				return $response->message;

			return true;
		}

		public static function add_to_list($list_id, $email, $fields = []) {

			$values = [
				'email' => $email,
				'listid' => [ $list_id ],
			];

			if(!empty($fields)) {
				$values['attributes'] = [];
				foreach($fields as $f) {
					$values['attributes'][$f->id] = $f->value;
				}
			}

			$response = self::request('user/createdituser',$values,'post');

			if($response === null || ($response->code !== 'success'))
				return "Could not add email to list.";

			return true;

		}

		public static function get_fields_from_list_v3() {

			$response = self::request('contacts/attributes',null,'get','3');
			if($response === null) return new WP_Error();

			if(isset($response->code))
				return new WP_Error();

			$fields = [];

			if(empty($response->attributes))
				return $fields;

			foreach($response->attributes as $attribute) {

				if(isset($attribute->category) && $attribute->category === 'global')
					continue;

                if( empty( $attribute->type ) ) continue;

				$type = strtolower(trim($attribute->type));

				if($type !== 'text' && $type !== 'float')
					continue;

				$detail = [
					'type' => $type === 'float' ? 'number' : 'text',
					'id' => $attribute->name
				];

				switch($attribute->name){
					case 'LASTNAME': $detail['title'] = 'Last name'; break;
					case 'FIRSTNAME': $detail['title'] = 'First name'; break;
					case 'SMS': $detail['title'] = 'Phone'; $detail['type'] = 'sms'; break;
					default: $detail['title']  = $attribute->name;break;
				}

				$fields[] = $detail;
			}

			return $fields;
		}

		public static function get_fields_from_list() {

			$response = self::request('attribute',null,'get');

			if($response === null) return new WP_Error();

			if(!isset($response->code) || $response->code !== 'success' || !isset($response->data->normal_attributes))
				return new WP_Error();

			$fields = [];

			foreach($response->data->normal_attributes as $attribute) {
				if(strtolower($attribute->type) !== 'text')
					continue;
				$detail = [
					'type' => 'text',
					'id' => $attribute->name
				];
				switch($attribute->name){
					case 'LASTNAME': $detail['title'] = 'Last name'; break;
					case 'FIRSTNAME': $detail['title'] = 'First name'; break;
					case 'SMS': $detail['title'] = 'Phone'; break;
					default: $detail['title']  = $attribute->name;break;
				}

				$fields[] = $detail;
			}

			return $fields;
		}

		public static function get_email_lists_v3() {
			try{
				$payload = self::request('contacts/lists', [
					'offset' => 0,
					'limit' => 50
				], 'get','3');

				if($payload === null) return new WP_Error();

				if(isset($payload->code))
					return new WP_Error();

				if(empty($payload->lists))
					return [];

				return Enumerable::from($payload->lists)->select(function($x) {
					return [ 'id' => $x->id, 'title' => $x->name ];
				})->toArray();
			}
			catch (\Exception $e) {
				return new WP_Error();
			}
		}

		public static function get_email_lists() {
			try{
				$payload = self::request('list', [
					'page' => 1,
					'page_limit' => 50
				], 'get');

				if($payload === null) return new WP_Error();

				if(!isset($payload->code) || $payload->code !== 'success')
					return new WP_Error();

				return Enumerable::from($payload->data->lists)->select(function($x) {
					return [ 'id' => $x->id, 'title' => $x->name ];
				})->toArray();
			}
			catch (\Exception $e){
				return new WP_Error();
			}

		}

		private static function request($type, array $body = null, $method = 'post', $version = '2.0') {

			$api_key = $version === '3' ?  Settings_Manager::get_setting('sib_apiv3') : Settings_Manager::get_setting('sib_api');

			if($api_key === null) return null;

			$url = 'https://api.sendinblue.com/v'.$version.'/'.$type;

			$headers = [
				'api-key' => $api_key,
				'Content-Type' => 'application/json'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers
			];

			if($body != null) {
				$options['body'] = $method === 'get' ? $body : json_encode( $body );
			}

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response)) return null;

			$body = wp_remote_retrieve_body($response);

			if(!empty($body))
				return json_decode($body);

			if(!empty($response['response']) && ($response['response']['code'] == 201 || $response['response']['code'] == 204))
				return (object) $response['response'];

			return null;
		}

	}
}