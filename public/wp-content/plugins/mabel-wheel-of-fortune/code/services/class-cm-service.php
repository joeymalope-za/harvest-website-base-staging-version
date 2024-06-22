<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class CM_service {

		public static function add_to_list($list_id, $email, $fields = []) {

			$values = [
				'EmailAddress' => $email,
				'Resubscribe' => false,
			];

			$name = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'name';});

			if($name != null)
				$values['Name'] = $name->value;

			if(!empty($fields)) {
				$custom_fields = [];
				foreach($fields as $field) {
					array_push($custom_fields, [
						'Key' => $field->id,
						'Value' => $field->value
					] );
				}
				$values['CustomFields']= $custom_fields;
			}

			$values = apply_filters('wof_campaignmonitor_values',$values);

			$response = self::request('subscribers/'.$list_id, $values);

			if($response->status === 400)
				return $response->body->Message;
			return true;
		}

		public static function get_fields_from_list($listId){
			$response = self::request('lists/'.$listId.'/customfields',null,'get');
			if($response === null)
				return new WP_Error();
			$fields = Enumerable::from($response->body)->where(function($x){
				return $x->DataType === 'Text' || $x->DataType === 'MultiSelectOne';
			})->select(function($x) {
				$arr =  [ 'id' => $x->Key, 'title' => $x->FieldName, 'type' => strtolower($x->DataType) ];
				if($x->DataType === 'MultiSelectOne') {
					$arr['type'] = 'dropdown';
					$arr['options'] = [ 'choices' => $x->FieldOptions ];
				}
				return $arr;
			})->toArray();

			array_push($fields, ['id' => 'name', 'title' => 'Name', 'type' => 'text'] );
			return $fields;
		}

		public static function get_email_lists() {
			$list_objects = self::request('clients/'.Settings_Manager::get_setting('cm_client').'/lists',null,'get');

			if($list_objects === null || isset($list_objects->status) && $list_objects->status !== 200)
				return new WP_Error();

			$lists = [];

			foreach($list_objects->body as $list) {
				array_push($lists, [ 'id' => $list->ListID, 'title' => $list->Name] );
			}

			return $lists;
		}

		public static function is_in_list($list_id, $email) {
			$url = 'clients/'.Settings_Manager::get_setting('cm_client').'/listsforemail';

			$response = self::request($url, [
				'email' => $email
			], 'get');

			return Enumerable::from($response->body)->any(function($x) use($list_id){
				return $x->ListID === $list_id;
			});
		}

		private static function request($type, array $body = null, $method = 'post') {

			$url = 'https://api.createsend.com/api/v3.1/'.$type.'.json';
			if($method === 'get' && sizeof($body) > 0)
				$url .= '?'.Enumerable::from($body)->join(function($v, $k){
						return $k.'='.$v;
					}, '&');

			$headers = [
				'Authorization' => 'Basic ' . base64_encode( Settings_Manager::get_setting('cm_api') ),
				'Content-Type' => 'application/json'
			];
			$options = [
				'timeout' => 15,
				'headers' => $headers
			];
			if($body != null && $method === 'post')
				$options['body'] = json_encode($body);

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response)) return null;

			return (object) [
				'status' => $response['response']['code'],
				'body' => json_decode(wp_remote_retrieve_body($response))
			];
		}
	}
}