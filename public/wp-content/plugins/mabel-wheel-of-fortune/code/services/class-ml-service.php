<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class ML_Service {

		public static function add_to_list($list_id, $email, $fields = []) {

			$post_fields = [];

			foreach($fields as $f) {
				if($f->id !== 'name')
					$post_fields[$f->id] = $f->value;
			}

			$post_data = [
				'email' => $email,
				'fields' => $post_fields
			];

			$name = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'name';});
			if($name !== null)
				$post_data['name'] = $name->value;

			$post_data = apply_filters('wof_mailerlite_values',$post_data);

			$response = self::request('groups/'.$list_id.'/subscribers', $post_data,'post');

			if($response === null || $response->status !== 200)
				return "Could not add email to list.";

			return true;
		}

		public static function get_fields_from_list() {

			$response = self::request('fields',null,'get');

			if($response !== null && $response->status === 200) {

				return Enumerable::from($response->body)->where(function($x){
					return ( $x['type'] === 'TEXT' || $x['type']  === 'DATE' ) && $x['key'] !== 'email';
				})->select(function($x){
					return (object) [
						'id' => $x['key'],
						'title' => $x['title'],
						'type' => $x['type'] === 'DATE' ? 'date_ml' : 'text'
					];
				})->toArray();

			}

			return new WP_Error();
		}

		public static function get_email_lists() {
			$response = self::request('groups',null,'get');

			if($response !== null && $response->status === 200) {
				return Enumerable::from($response->body)->select(function($x){
					return (object) [ 'id' => '' . $x['id'], 'title' => $x['name'] ];
				})->toArray();
			}

			return new WP_Error();
		}

		public static function is_in_list($list_id, $email) {

			$response = self::request('subscribers/'.urlencode($email).'/groups', null, 'get');

			if($response !== null && $response->status === 200 && is_array($response->body)){
				$list = Enumerable::from($response->body)->firstOrDefault(function($x) use($list_id){
					return $x['id'] == $list_id;
				});
				return $list !== null;
			}

			return false;
		}

		private static function request($action, array $body = null, $method = 'post') {

			$url =  'https://api.mailerlite.com/api/v2/' .$action;

			$headers = [
				'X-MailerLite-ApiKey' => Settings_Manager::get_setting('ml_api'),
				'Content-Type' => 'application/json'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers
			];

			if($body != null && $method === 'post')
				$options['body'] = json_encode($body);

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response))
				return null;

			return (object) [
				'status' => $response['response']['code'],
				'body' => json_decode(wp_remote_retrieve_body($response),true)
			];
		}
	}
}