<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

	class CK_Service {

		public static function add_to_list($list_id, $email, $fields = []) {

			$request_url =  '';
			if(strpos($list_id, 'sequence:') !== false)
				$request_url = 'courses/'.str_replace('sequence:','',$list_id).'/subscribe';
			else
				$request_url = 'tags/'.str_replace('tag:','',$list_id).'/subscribe';

			$data = [
				'email' => $email,
				'fields' => []
			];

			$first_name = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'first_name';});
			if($first_name != null)
				$data['first_name'] = $first_name->value;

			foreach ($fields as $field) {
				if($field->id !== 'first_name')
					$data['fields'][$field->id] = $field->value;
			}

			$data['fields'] = (object) $data['fields'];

			$data = apply_filters('wof_convertkit_values',$data);

			$response = self::request($request_url, $data);

			if($response === null || $response->status !== 200)
				return "Could not add email to list.";

			return true;
		}

		public static function get_fields_from_list() {
			$custom_fields = [
			    [
                    'id' => 'first_name',
                    'title' => 'First name',
                    'type' => 'text',
                ]
			];

			$response = self::request('custom_fields',null,'get');

			if($response === null || $response->status !== 200)
				return new WP_Error();

			return array_merge($custom_fields, Enumerable::from($response->body->custom_fields)->select(function($x){
				return [
					'id' => $x->key,
					'title' => $x->label,
					'type' => 'text'
				];
			})->toArray());

		}

		public static function get_email_lists() {

			$sequence_responses = self::request('sequences',null,'get');
			$tag_responses = self::request('tags',null,'get');
			if($sequence_responses === null || $tag_responses === null)
				return new WP_Error();

			$lists = [];

			if($sequence_responses->status === 200) {
				$lists = array_merge( $lists, Enumerable::from( $sequence_responses->body->courses )->select( function ( $x ) {
					return [
						'id'    => 'sequence:' . $x->id,
						'title' => 'Sequence: ' . $x->name
					];
				} )->toArray() );
			}
			if($tag_responses->status === 200) {
				$lists = array_merge($lists, Enumerable::from($tag_responses->body->tags)->select(function($x){
					return [
						'id' => 'tag:'.$x->id,
						'title' => 'Tag: '.$x->name
					];
				})->toArray());
			}

			return $lists;
		}

		private static function request($action, array $body = null, $method = 'post') {
			$base = 'https://api.convertkit.com/v3/';
			$url =  $base . $action .'?api_key='. Settings_Manager::get_setting('ck_api').'&api_secret='.Settings_Manager::get_setting('ck_secret');

			if($method === 'get' && is_array($body))
				$url .= '&'.Enumerable::from($body)->join(function($v, $k){
						return urlencode($k).'='.urlencode($v);
					}, '&');

			$headers = [
				'Content-Type' => $method === 'get' ? 'application/json' : 'application/x-www-form-urlencoded'
			];

			$options = [
				'timeout' => 15,
				'headers' => $headers,
				'method' => strtoupper($method)
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