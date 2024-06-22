<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
	use WP_Error;

	class AC_Service {

		public static function add_to_list($list_id, $email, $fields = []) {

			$values = self::prepare_post_data($email,$list_id,$fields);
			$values['status['.$list_id.']'] = 1;

			$values = apply_filters('wof_activecampaign_values',$values);

			$response = self::request('contact_sync', $values,'post');

			if($response === null || $response->status !== 200)
				return "Could not add email to list.";
			return true;
		}

		private static function prepare_post_data($email,$list_id, $fields = []) {

			$values = [
				'email' => $email,
				'p['.$list_id.']' => $list_id,
			];

			$phone = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'phone';});
			$first_name = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'first_name';});
			$last_name = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'last_name';});

			if($phone != null)
				$values['phone'] = $phone->value;
			if($first_name != null)
				$values['first_name'] = $first_name->value;
			if($last_name != null)
				$values['last_name'] = $last_name->value;

			foreach ($fields as $field) {
				if($field->id !== 'phone' && $field->id !== 'last_name' && $field->id !== 'first_name')
					$values['field['.$field->id.',0]'] = $field->value;
			}

			return $values;

		}

		public static function update_contact($list_id,$contact_id,$contact_email,$fields = []) {

			$values = self::prepare_post_data($contact_email,$list_id,$fields);
			$values['id'] = $contact_id;

			$response = self::request('contact_edit',$values,'post');
			return $response != null && $response->status === 200;
		}

		public static function get_fields_from_list($listId) {
			$response = self::request('list_list', [
				'global_fields' => 1,
				'full' => 1,
				'ids' => $listId
			],'get');
			$fields = [
				[ 'id' => 'first_name','title' => 'First Name', 'type' => 'text' ],
				[ 'id' => 'last_name', 'title' => 'Last Name', 'type' => 'text' ],
				[ 'id' => 'phone', 'title' => 'Phone', 'type' => 'text' ]
			];

			if($response->body->result_code === 1  && $response->status === 200) {

				foreach($response->body as $k=>$v) {
					if(!empty($v->fields)){
						return array_merge($fields,Enumerable::from($v->fields)->where(function($x){return $x->type === 'text' || $x->type === 'dropdown';})->select(function($x){
							$arr = [ 'id' => $x->id,'title' => $x->title, 'type' => $x->type ];
							if($x->type === 'dropdown'){
								$arr['options'] = [
									'choices' => Enumerable::from($x->options)->select(function($x){return $x->value;})->toArray()
								];
							}
							return $arr;
						})->toArray());
					}
				}
				return $fields;
			}

			return new WP_Error();
		}

		public static function get_email_lists() {

			$response = self::request('list_list', [
				'full' => 0,
				'ids' => 'all'
			], 'get');

			$lists = [];

			if($response->body->result_code === 1  && $response->status === 200){
				foreach($response->body as $k=>$v) {
					$x = filter_var($k, FILTER_VALIDATE_INT);
					if(is_int($x))
						array_push($lists, [ 'id' => $v->id,'title' => $v->name ]);
				}
				return $lists;
			}

			return new WP_Error();

		}

		public static function is_in_list($list_id, $email) {

			$response = self::request('contact_view_email', [
				'email' => $email
			], 'get');

			if($response->body->result_code === 1  && $response->status === 200) {
				foreach($response->body->lists as $list){
					if($list->listid === $list_id)
						return $response->body->id;
				}
			}

			return false;

		}

		private static function request($action, array $body = null, $method = 'post') {

			$url =  trailingslashit(Settings_Manager::get_setting('ac_url')) .'admin/api.php';

			$body['api_key'] = Settings_Manager::get_setting('ac_api');
			$body['api_action'] = $action;
			$body['api_output'] = 'json';

			if($method === 'get' && sizeof($body) > 0)
				$url .= '?'.Enumerable::from($body)->join(function($v, $k){
						return urlencode($k).'='.urlencode($v);
					}, '&');

			$headers = [
				'Api-Token' => Settings_Manager::get_setting('ac_api'),
				'Content-Type' => $method === 'get' ? 'application/json' : 'application/x-www-form-urlencoded'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers
			];

			if($body != null && $method === 'post')
				$options['body'] = $body;

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);
			if(is_wp_error($response)) return null;

			return (object) [
				'status' => $response['response']['code'],
				'body' => json_decode(wp_remote_retrieve_body($response))
			];
		}
	}
}