<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Code\Models\Wheel_Model;

	class Log_Service {

		public static function type_of_logging(Wheel_Model $wheel){
			if($wheel->log)
				return apply_filters('wof_logging_type','full', $wheel);
			if($wheel->limit_prizes)
                return apply_filters('wof_logging_type','limit', $wheel);
			return apply_filters('wof_logging_type','minimal', $wheel);;
		}

		public static function drop_logs(){
			global $wpdb;
			$wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->prefix.'wof_optins' );
		}

		public static function delete_all_logs_from_db($wheel_id) {
			global $wpdb;

			$wpdb->delete( $wpdb->prefix.'wof_optins', [ 'wheel_id' => intval($wheel_id) ] );
		}

		#region GDPR functions

		public static function delete_logs_by_email($email) {
			global $wpdb;

			$wpdb->delete( $wpdb->prefix.'wof_optins', [ 'email' => $email ] );
		}

		public static function get_logs_of_email($email){
			global $wpdb;

			$results = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM ". $wpdb->prefix. "wof_optins WHERE email = %s",
					[
						$email,
					]
				)
			);

			return $results;
		}
		#endregion

		public static function update_optin_in_db($id,$wheel_id,$email,$fields) {
			global $wpdb;

			$result = $wpdb->update($wpdb->prefix .'wof_optins', [
				'wheel_id' => $wheel_id,
				'email' => $email,
				'created_date' => current_time('Y-m-d'),
				'fields' => json_encode($fields)
			], [
				'id' => $id
			]);

			return $result;
		}

		public static function log_play_to_db($wheel_id, $email, $winning, $segment,$segment_text,$prize,$segment_type) {

			global $wpdb;

			$hide_html_in_log = apply_filters('wof_hide_html_in_logs', true);
			$log_sensitive_data = apply_filters('wof_log_sensitive_data', true);

			$result = $wpdb->insert($wpdb->prefix .'wof_optins', [
				'wheel_id' => $wheel_id,
				'email' => $log_sensitive_data ? $email : '',
				'created_date' => current_time('Y-m-d H:i:s',true),
				'unique_hash' => $log_sensitive_data ? hash('md5',Helper_Service::get_visitor_ip()) : '',
				'segment' => $segment,
				'segment_text' => substr($segment_text,0,160),
				'prize' => $segment_type == 4 && $hide_html_in_log ? '[custom text/html]' : substr($prize,0,800),
				'winning' => $winning,
				'type' => 1 
			]);

		}

		public static function log_optin_to_db($wheel_id, $email, $fields = null ) {
			global $wpdb;

			$log_sensitive_data = apply_filters('wof_log_sensitive_data', true);

			$insert_array =  [
				'wheel_id' => $wheel_id,
				'email' => $log_sensitive_data ? $email : '',
				'created_date' => current_time('Y-m-d H:i:s',true),
				'unique_hash' => $log_sensitive_data ? hash('md5',Helper_Service::get_visitor_ip()) : '',
				'type' => 0 
			];
			if($fields != null && $log_sensitive_data)
				$insert_array['fields'] = json_encode($fields);

			$result = $wpdb->insert($wpdb->prefix .'wof_optins', $insert_array);

			return $result;
		}

		public static function get_all_logs($wheel_id) {

			global $wpdb;

			$results = $wpdb->get_results(
				$wpdb->prepare("
					SELECT *  
					FROM ". $wpdb->prefix. "wof_optins 
					WHERE wheel_id = %d
					ORDER BY id ASC",
					[ $wheel_id ]
				)
			);

			return $results;

		}

		public static function get_last_logs($wheel_id) {
			global $wpdb;

			$results = $wpdb->get_results(
				$wpdb->prepare("
					SELECT wheel_id,created_date,fields,email,segment,winning,segment_text,prize,
					CASE WHEN type != 1 THEN 'opt-in' ELSE 'play' END AS type_description 
					FROM ". $wpdb->prefix. "wof_optins 
					WHERE wheel_id = %d
					ORDER BY id
					DESC LIMIT 30",
					[ $wheel_id ]
				)
			);
			return $results;
		}

		public static function has_played_yet(Wheel_Model $wheel,$provider_obj,$mail = '', $days = -1, &$out_checked_with = null) {
			global $wpdb;

			$where = 'wheel_id = %d AND type = 0';

			$check_with = 'mail';

			if( !$provider_obj->needsEmail  || $provider_obj->isFbOptin) {
				$check_with = 'ip';
			}

			if ($wheel->log_ips && $provider_obj->needsEmail) {
				$check_with = 'mail+ip';
			}

			$where_vars = [ $wheel->id ];
			switch($check_with){
				case 'mail':
					$where .= ' AND email = %s';
					$where_vars[] = $mail;
					break;
				case 'ip':
					$where .= ' AND unique_hash = %s';
					$where_vars[] = hash('md5',Helper_Service::get_visitor_ip());
					break;
				case 'mail+ip':
					$where .= ' AND (email = %s OR unique_hash = %s)';
					$where_vars[] = $mail;
					$where_vars[] = hash('md5',Helper_Service::get_visitor_ip());
					break;
			}

			$days = intval( $days );
			if($days > 0) {
				$where.= ' AND created_date > (utc_timestamp() - INTERVAL %d DAY)';
				$where_vars[] = $days;
			}

			$out_checked_with = $check_with;

			$results = $wpdb->get_results(
				$wpdb->prepare('SELECT unique_hash, email FROM '.$wpdb->prefix.'wof_optins WHERE '.$where, $where_vars)
			);

			return count($results) > 0;

		}

	}

}