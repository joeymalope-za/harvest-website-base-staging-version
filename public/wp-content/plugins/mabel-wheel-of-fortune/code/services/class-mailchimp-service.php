<?php

namespace MABEL_WOF\Code\Services
{

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class MailChimp_Service
	{

		public static function add_to_list($list_id, $email, $fields, $group = null) {

			$data = [
				'email_address' => $email,
				'status' =>  Settings_Manager::has_setting('mailchimp_double_optin')
				             && Settings_Manager::get_setting('mailchimp_double_optin') === true ? 'pending' : 'subscribed'
			];

			if(!empty($fields)) {

				$merge_fields = [];

				foreach($fields as $field) {
					$v = $field->value;

					if($field->type === 'birthday' && isset($field->options) && isset($field->options['date_format']) && $field->options['date_format'] === 'DD/MM') {
						$v = explode('/',$v);
						$v = $v[1] .'/'. $v[0];
					}

					$merge_fields[$field->id] = $v;
				}
				$data['merge_fields'] = $merge_fields;
			}

			if(!empty($group))
				$data['interests'] = [ $group => true ];

			$data = apply_filters('wof_mailchimp_values',$data);

			$response = self::request('lists/'.$list_id.'/members/'.md5(strtolower($email)),$data);

			if(isset($response->status) && $response->status == 400)
				return $response->detail;

			return true;
		}

		public static function get_email_lists() {
			$payload = self::request('lists?count=100', null, 'get');

			if($payload === null) return new WP_Error();
			$list_objects = Enumerable::from($payload->lists)->select(function($x){
				return [ 'id' => $x->id, 'name' => $x->name ];
			})->toArray();

			$lists = [];

			foreach($list_objects as $list) {
				array_push( $lists, [ 'id' => $list['id'], 'title' => $list['name'] ] );
			}

			return $lists;
		}

		public static function is_in_list($email,$listId) {
			$hash = md5(strtolower($email));
			$response = self::request('lists/'.$listId.'/members/'.$hash,null,'get');

			if($response->status === 'subscribed' || $response->status === 'pending') return true;
			return false;
		}

		public static function get_fields_from_list($listId){

			$allowed_fields = ['text','birthday','dropdown','date','number','phone','zip'];

			$response = self::request('lists/'.$listId.'/merge-fields?count=100',null,'get');
			if($response === null)
				return new WP_Error();

			return Enumerable::from($response->merge_fields)->where(function($x) use($allowed_fields){
				return in_array($x->type, $allowed_fields);
			})->select(function($x){

				$f = [
					'id' => $x->tag,
					'title' => $x->name,
					'type' => $x->type
				];

				if($x->type === 'birthday' || $x->type === 'dropdown' || $x->type === 'date' || $x->type ==='phone')
					$f['options'] = $x->options;

				return $f;
			})->toArray();
		}

		public static function get_list_groups($listId) {
			$response = self::request('lists/'.$listId.'/interest-categories',null,'get');

			if(!isset($response->categories) || empty($response->categories))
				return [];

			$results = [];

			foreach($response->categories as $category) {
				$detail_response = self::request('/lists/'.$listId.'/interest-categories/'.$category->id.'/interests',null,'get');

				if(isset($detail_response->interests) && !empty($detail_response->interests)) {

					foreach($detail_response->interests as $interest) {
						array_push($results, [
							'id' => $interest->id,
							'title' => $category->title .' - '.$interest->name
						]);
					}

				}
			}
			return $results;
		}

		private static function request($type, array $body = null, $method = 'post') {
			$api_key = Settings_Manager::get_setting('mailchimp_api');
			if($api_key === null) return null;

			$data_center = explode('-',$api_key)[1];
			$url = 'https://' . $data_center . '.api.mailchimp.com/3.0/'.$type.'/';

			$headers = [
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
				'Content-Type' => 'application/json'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers
			];

			if($body != null) {
				$options['body'] = json_encode( $body );
				$options['method'] = 'PUT';
			}

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response)) return null;

			return json_decode(wp_remote_retrieve_body($response ));
		}

	}
}