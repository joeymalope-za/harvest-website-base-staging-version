<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;

	class Mailster_Service {

		public static function add_to_list($list_id, $email, $fields) {

			$subscriber_data = [
				'status' => 1, 
				'email' => $email
			];

			foreach($fields as $field) {
				$subscriber_data[$field->id] = $field->value;
			}

			$subscriber_id = mailster( 'subscribers' )->add($subscriber_data, true );

			if(is_int($subscriber_id)){
				$success = mailster( 'subscribers' )->assign_lists( $subscriber_id, $list_id, true);
				if($success)
					return true;
			}

			return "Could not add email to list.";

		}

		public static function is_in_list($email, $list_id) {

			$subscriber = mailster('subscribers')->get_by_mail($email);
			if($subscriber === false)
				return false;

			$lists = mailster('subscribers')->get_lists($subscriber->ID);
			if(empty($lists))
				return false;

			return Enumerable::from($lists)->any(function($x) use($list_id){
				return $x->ID === $list_id;
			});

		}

		public static function get_fields_from_list() {
			$fields = [
				[ 'id' => 'firstname','title' => 'First Name', 'type' => 'text' ],
				[ 'id' => 'lastname', 'title' => 'Last Name', 'type' => 'text' ],
			];

			if(!function_exists('mailster'))
				return [];

			$mailster_fields = mailster()->get_custom_fields();

			foreach($mailster_fields as $k => $f){

				if($f['type'] !== 'textfield' && $f['type'] !== 'dropdown')
					continue;

				$field = [
					'id' => $k,
					'title' => $f['name'],
					'type' => $f['type'] === 'textfield' ? 'text' : 'dropdown'
				];

				if($field['type'] === 'dropdown')

					$field['options'] = [
						'choices' =>$f['values']
					];

				array_push($fields, $field);
			}

			return $fields;
		}

		public static function get_email_lists() {

			if(function_exists('mailster')){
				$lists = mailster('lists')->get();

				return Enumerable::from($lists)->select(function($x){
					return [
						'id' => $x->ID,
						'title' => $x->name
					];
				})->toArray();
			} else{
				return [];
			}

		}

	}
}