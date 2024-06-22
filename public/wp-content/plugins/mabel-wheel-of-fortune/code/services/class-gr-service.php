<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class GR_Service {

		private static $also_add = ['name'];

		public static function add_to_list($list_id, $email, $fields = []) {

			$is_in_list = self::is_in_list( $list_id, $email );

			if($is_in_list !== false) {
				self::update_contact($is_in_list,$list_id,$fields);
				return true;
			}

			$values = self::prepare_post_data($list_id,$fields,$email);

			$values = apply_filters('wof_getresponse_values',$values);

			$response = self::request('contacts', $values ,'post');

			if($response === null)
				return "Could not add email to list.";
			if($response->status !== 200 && $response->status !== 202){
				return empty($response->body->message)? "Could not add email to list." : $response->body->message;
			}

			return true;
		}

		public static function update_contact($contact_id,$list_id,$fields = []) {
			$response = self::request('contacts/'.$contact_id,self::prepare_post_data($list_id,$fields));
			return $response !== null && $response->status === 200;
		}

		private static function prepare_post_data($list_id,array $fields,$email = null) {

			$post_data = [
				'customFieldValues' => [],
				'campaign' => [ 'campaignId' => $list_id ]
			];
			if(!empty($email))
				$post_data['email'] = $email;

			foreach (self::$also_add as $obl) {
				$f = Enumerable::from($fields)->firstOrDefault(function($x) use($obl){return $x->id === $obl; });
				if(!empty($f))
					$post_data[$obl] = $f->value;
			}

			foreach($fields as $field){
				if(!in_array($field->id, self::$also_add)) {
					array_push($post_data['customFieldValues'], [
						'customFieldId' => $field->id,
						'value' => [ $field->value ]
					]);
				}
			}

			return $post_data;
		}

		public static function get_fields_from_list() {

			$fields = array (
				['id' => 'name','title' => 'Name', 'type' => 'text']
			);

			$response = self::request('custom-fields',null,'get');

			if($response !== null && $response->status === 200) {

				return array_merge($fields,Enumerable::from($response->body)->where(function($x){
					return $x->type === 'text' || $x->type === 'single_select';
				})->select(function($x) {
					$arr = [
						'id' => $x->customFieldId,
						'title' => ucfirst(str_replace('_',' ',$x->name)),
						'type' => $x->type
					];
					if($x->type === 'single_select') {
						$arr['type']    = 'dropdown';
						$arr['options'] = [
							'choices' => $x->values
						];
					}
					return $arr;
				})->toArray());

			}

			return new WP_Error();
		}

		public static function get_email_lists() {
			$response = self::request('campaigns',null,'get');

			if($response !== null && $response->status === 200){

				return Enumerable::from($response->body)->select(function($x){
					return  ['id' => $x->campaignId, 'title' => ucfirst($x->name) ];
				})->toArray();

			}

			return new WP_Error;
		}

		public static function is_in_list($list_id, $email) {

			$response = self::request('contacts?query[campaignId]='.urlencode($list_id).'&query[email]='.urlencode($email), null, 'get');

			if($response !== null && $response->status === 200 && is_array($response->body) && count($response->body) === 1){
				return $response->body[0]->contactId;
			}

			return false;
		}

		private static function request($action, array $body = null, $method = 'post') {

			$url =  'https://api.getresponse.com/v3/' .$action;


			$headers = [
				'X-Auth-Token' => 'api-key '.Settings_Manager::get_setting('gr_api'),
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
				'body' => json_decode(wp_remote_retrieve_body($response))
			];
		}
	}
}