<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;

	class WordPress_service {

		public static function add_optin($wheel_id, $email, $fields = []) {
			global $wpdb;

			$is_in_list = self::is_in_list( $wheel_id,$email );

			if($is_in_list !== false) {
				Log_Service::update_optin_in_db($is_in_list,$wheel_id, $email,$fields);
				return true;
			}

			$result = Log_Service::log_optin_to_db($wheel_id, $email, $fields);

			return is_int($result);
		}

		public static function is_in_list($wheel_id,$email){
			global $wpdb;
			$id = $wpdb->get_var(
				$wpdb->prepare('SELECT id FROM '.$wpdb->prefix.'wof_optins WHERE wheel_id = %d AND email = %s and type != 1 ', [
					$wheel_id,
					$email
				])
			);

			return $id === null ? false : $id;
		}

		public static function get_prize_limits($wheel_id) {
			global $wpdb;

			$results = $wpdb->get_results(
				$wpdb->prepare('SELECT segment, count(*) as total FROM '. $wpdb->prefix. 'wof_optins WHERE winning != 0 and wheel_id = %d and type = 1 group by segment', [
					$wheel_id,
				])
			);

			return $results;
		}

		public static function filter_segments_by_prize_limit($segments,$wheel_id) {

			$results = self::get_prize_limits($wheel_id);

			if(empty($results))
				return $segments;

			$filtered_slices = Enumerable::from($segments)->where(function($slice) use($results) {

				if(empty($slice->limit)) 
					return true;

				$limit = intval($slice->limit);
				if($limit === -1) 
					return true;
				if($limit === 0) 
					return false;

				$db_result = Enumerable::from($results)->firstOrDefault(function($x)use($slice){return intval($x->segment) === $slice->id;});

				if($db_result === null) 
					return true;

				return (intval($db_result->total) >= $slice->limit) ? false : true;

			})->toArray();

			return $filtered_slices;
		}
	}
}