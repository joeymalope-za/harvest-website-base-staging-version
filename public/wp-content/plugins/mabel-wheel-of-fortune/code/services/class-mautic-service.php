<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
	class Mautic_Service {

		private static $also_add = ['name'];

		public static function add_to_list($list_id, $email, $fields = [], $allow_duplicates = false) {

			$is_in_list = self::is_in_list( $list_id, $email  );

			if($is_in_list !== false && !$allow_duplicates)
				return false;

			if($is_in_list !== false && $allow_duplicates){
				self::update_contact($is_in_list,$list_id,$fields);
				return true;
			}

			$response = self::request('contacts', self::prepare_post_data($list_id,$fields,$email),'post');

			if($response === null)
				return "Could not add email to list.";
			if($response->status !== 202){
				return empty($response->body->message)? "Could not add email to list." : $response->body->message;
			}

			return true;
		}

		public static function update_contact($contact_id,$fields = []) {
			$response = self::request('contacts/'.$contact_id.'/edit',$fields,'post');
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
				$f = Enumerable::from($fields)->firstOrDefault(function($x)use($obl){return $x->id === $obl;});
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

			$response = self::request('contacts/list/fields',null,'get');

			$fields = [];

			if($response !== null && $response->status === 200){

				foreach($response->body as $k=>$v) {
					$x = filter_var($k, FILTER_VALIDATE_INT);
					if(is_int($x) && $v->type ==='text')
						array_push( $fields, [ 'id' => $v->id, 'title' => $v->label, 'typ' => $v->type ] );
				}

			}

			return $fields;
		}

		public static function get_email_lists() {
			$response = self::request('segments',null,'get');

			$lists = [];

			if($response !== null && $response->status === 200){

				foreach($response->body->lists as $k=>$v) {
					$x = filter_var($k, FILTER_VALIDATE_INT);
					if(is_int($x) && $v->isPublished === true)
						array_push($lists, [ 'id' => $v->id, 'title' => $v->name] );
				}
				return $lists;

			}

			return $lists;
		}

		public static function is_in_list($list_id, $email) {

			$response = self::request('contacts?search=email:'.urlencode($email).' segment:'.urlencode($list_id), null, 'get');

			if($response !== null && $response->status === 200 ){
				if($response->body->total !== '1')
					return false;

				foreach($response->body->contacts as $k=>$v) {
					$x = filter_var($k, FILTER_VALIDATE_INT);
					if(is_int($x))
						return $x;
				}
			}

			return false;
		}

		private static function request($action, array $body = null, $method = 'post') {

			$url =  trailingslashit(Settings_Manager::get_setting('mautic_url')) .$action;

			$headers = [
				'Authorization' => 'Basic '.base64_encode(Settings_Manager::get_setting('mautic_user').':'.Settings_Manager::get_setting('mautic_pw')),
				'Content-Type' => 'application/json'
			];

			$options = [
				'timeout' => 10,
				'headers' => $headers
			];

			if($body != null && $method === 'post') {
				$options['body'] = json_encode( $body );
				$options['method'] = 'PUT';
			}

			$response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

			if(is_wp_error($response))
				return null;

			return (object) array (
				'status' => $response['response']['code'],
				'body' => json_decode(wp_remote_retrieve_body($response))
			);
		}
	}
}