<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Managers\Config_Manager;

	class Helper_Service {

		public static $iv = 'S3puVVRNZ2tKZHlsbHdmRzlTVmFJUT09';

		public static function encrypt($string) {
			$key = hash( 'sha256', Config_Manager::$settings_key );
			$iv = substr( hash( 'sha256', self::$iv ), 0, 16 );
			return base64_encode( openssl_encrypt( $string, "AES-256-CBC", $key, 0, $iv ) );
		}

		public static function decrypt($string) {
			$key = hash( 'sha256', Config_Manager::$settings_key );
			$iv = substr( hash( 'sha256', self::$iv ), 0, 16 );
			return  openssl_decrypt( base64_decode( $string ), "AES-256-CBC", $key, 0, $iv );
		}

		public static function truncate_text($in,$length = 25){
			return strlen($in) > $length ? substr($in,0,$length).'...': $in;
		}

		public static function get_visitor_ip() {

			foreach ( ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
				if (array_key_exists($key, $_SERVER) === true) {
					foreach (explode(',', $_SERVER[$key]) as $ip) {
						if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
							return apply_filters('wof_ip',$ip);
						}
					}
				}
			}

            return apply_filters('wof_ip',null);
		}

		public static function hex_to_rgba( $hex, $alpha = 1 ) {
			$hex = str_replace( '#', '', $hex );
			$length = strlen( $hex );
			$rgb['r'] = hexdec( $length == 6 ? substr( $hex, 0, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 0, 1 ), 2 ) : 0 ) );
			$rgb['g'] = hexdec( $length == 6 ? substr( $hex, 2, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 1, 1 ), 2 ) : 0 ) );
			$rgb['b'] = hexdec( $length == 6 ? substr( $hex, 4, 2 ) : ( $length == 3 ? str_repeat( substr( $hex, 2, 1 ), 2 ) : 0 ) );
			if($alpha){
				$rgb['a'] = $alpha;
			}
			return printf('rgba(%s,%s,%s,%s)',$rgb['r'],$rgb['g'],$rgb['b'],$alpha);
		}
	}

}