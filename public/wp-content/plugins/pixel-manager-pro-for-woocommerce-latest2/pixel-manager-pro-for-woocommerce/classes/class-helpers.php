<?php

namespace WCPM\Classes;

use ActionScheduler_Store;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use stdClass;
use WC_Log_Handler_File;
use WCPM\Classes\Admin\Environment;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Helpers {

	private static $user_data;


	/**
	 * This function takes any of the input types and sanitizes them automatically.
	 *
	 * @param string $input_type
	 *        The type of input to sanitize. Can be INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
	 *
	 * @param bool   $raw
	 *        Whether to apply raw sanitization or not. Default is false.
	 *
	 * @return mixed
	 */
	public static function get_input_vars( $input_type, $raw = false ) {

		$data = filter_input_array($input_type, FILTER_UNSAFE_RAW);

		return self::generic_sanitization($data, $raw);
	}

	private static function sanitize_string( $string, $raw = false ) {

		if ($raw) {
			return filter_var($string, FILTER_UNSAFE_RAW);
		}

		// Don't use FILTER_SANITIZE_STRING as it is deprecated from PHP 8.x
		// Don't use FILTER_SANITIZE_FULL_SPECIAL_CHARS as it will reformat Umlauts like ä, ö, ü
		// Don't use strip_tags() as it will remove information.
		// https://stackoverflow.com/a/69207369/4688612
		// https://gist.github.com/alewolf/e9235adeaf26dc024bab4f53825ec6da

		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

	public static function generic_sanitization( $input, $raw = false ) {

		if (is_array($input)) {
			$sanitized_array = [];

			foreach ($input as $key => $value) {
				$sanitized_array[$key] = self::generic_sanitization($value, $raw);
			}

			return $sanitized_array;
		}

		if (is_object($input)) {
			$sanitized_object = new stdClass();

			foreach ($input as $key => $value) {
				$sanitized_object->$key = self::generic_sanitization($value, $raw);
			}

			return $sanitized_object;
		}

		if (is_string($input)) {
			return self::sanitize_string($input, $raw);
		}

		if (is_bool($input)) {
			// No sanitization needed for boolean values
			return $input;
		}

		// If the input is of any other type (e.g., int, float), no sanitization is needed
		return $input;
	}

	public static function is_allowed_notification_page( $page ) {

		$allowed_pages = [ 'page_wpm', 'index.php', 'dashboard' ];

		foreach ($allowed_pages as $allowed_page) {
			if (strpos($page, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	public static function is_wc_hpos_enabled() {
		return class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function is_orders_page() {
		global $pagenow;

		$_get = self::get_input_vars(INPUT_GET);

		return ( 'edit.php' === $pagenow && isset($_get['post_type']) && 'shop_order' === $_get['post_type'] ) || ( isset($_get['page']) && 'wc-orders' === $_get['page'] );
	}

	// If is single order page return true
	// TODO Check if it works with HPOS enabled
	public static function is_edit_order_page() {
//		global $pagenow;
//
//		$_get = self::get_input_vars(INPUT_GET);
//
//		error_log('current screen id: ' . get_current_screen()->id);

		return 'shop_order' === get_current_screen()->id;

//		return
//			'post.php' === $pagenow
//			&& isset($_get['post'])
//			&& 'shop_order' === get_post_type($_get['post'])
//			&& isset($_get['action'])
//			&& 'edit' === $_get['action'];
	}

	public static function is_email( $email ) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function is_url( $url ) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	public static function clean_product_name_for_output( $name ) {
		return html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	public static function get_e164_formatted_phone_number( $number, $country ) {

		try {
			$phone_util    = PhoneNumberUtil::getInstance();
			$number_parsed = $phone_util->parse($number, $country);
			return $phone_util->format($number_parsed, PhoneNumberFormat::E164);
		} catch (NumberParseException $e) {
			/**
			 * Don't error log the exception. It leads to more confusion than it helps:
			 * https://wordpress.org/support/topic/php-errors-in-version-1-27-0/
			 */
			return $number;
		}
	}

	// https://stackoverflow.com/a/60199374/4688612
	public static function is_iframe() {
		if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && 'iframe' === $_SERVER['HTTP_SEC_FETCH_DEST']) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_percentage( $counter, $denominator ) {
		return $denominator > 0 ? round($counter / $denominator * 100) : 0;
	}

	public static function get_user_country_code( $user ) {

		// If the country code is set on the user, return it
		if (isset($user->billing_country)) {
			return $user->billing_country;
		}

		// Geolocate the user IP and return the country code
		return Geolocation::get_visitor_country();
	}

	public static function is_dashboard() {

		global $pagenow;

		// Don't check for the plugin settings page. Notifications have to be handled there.
		$allowed_pages = [
			'index.php',
			'dashboard',
		];

		foreach ($allowed_pages as $allowed_page) {
			if (strpos($pagenow, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Trim a settings page input string by removing all whitespace, newlines, tabs, double quotes, single quotes and backticks.
	 *
	 * @return string
	 * @since 1.27.10
	 */
	public static function trim_string( $string ) {

		$string = trim($string);

		// Return the string with all newlines, tabs, double quotes, single quotes and backticks removed. Keep spaces within the string.
		$string = preg_replace('/[\n\r\t"\'`]/', '', $string);

		// Remove all quotes
		$string = str_replace('"', '', $string);

		// Remove html quote entities
		$string = str_replace('&quot;', '', $string);

		// Remove anything from the front and back of the string that looks like &#039; or similar
		$string = preg_replace('/&[^;]+;/', '', $string);

		// Remove a ; at the end of the string
		return rtrim($string, ';');
	}

	/**
	 * Filter to enable PMW lazy loading.
	 *
	 * @return bool
	 * @since 1.28.0
	 */
	public static function lazy_load_pmw__premium_only() {

		if (
			Options::lazy_load_requirements()
			&& Options::get_options_obj()->general->lazy_load_pmw
		) {
			return true;
		}

		if (apply_filters('experimental_lazy_load_pmw', false)) {
			return true;
		}

		return false;
	}

	/**
	 * Filter to enable PMW web worker with Partytown.
	 *
	 * @return bool
	 * @since 1.28.0
	 */
	public static function partytown__premium_only() {
		return (bool) apply_filters('experimental_pmw_partytown', false);
	}

	/**
	 * Filter to return the Facebook fbevents.js script URL.
	 *
	 * It allows to either return the latest version or a specific version.
	 *
	 * @return string
	 * @since 1.30.0
	 */
	public static function get_facebook_fbevents_js_url() {

		$fbevents_standard_url = 'https://connect.facebook.net/en_US/fbevents.js';

		if (apply_filters('pmw_facebook_fbevents_script_version', '')) {
			return $fbevents_standard_url . '?v=' . apply_filters('pmw_facebook_fbevents_script_version', '');
		}

		if (apply_filters('pmw_facebook_fbevents_script_url', '')) {
			return apply_filters('pmw_facebook_fbevents_script_url', '');
		}

		return $fbevents_standard_url;
	}

	/**
	 * Given a datetime string, return the unix timestamp for the local timezone.
	 *
	 * @param $datetime_string
	 * @return false|string
	 *
	 * @since 1.30.8
	 */
	public static function datetime_string_to_unix_timestamp_in_local_timezone( $datetime_string ) {
		return wp_date('U', strtotime($datetime_string . ' ' . wp_timezone_string()));
	}

	public static function is_admin_page( $page_ids = [] ) {

		// If no page IDs are given, check if the current page is an admin page
		if (empty($page_ids)) {
			return false;
		}

		// Check if the current page is an admin page and if it is one of the given page IDs
//		return is_admin() && in_array(get_current_screen()->id, $page_ids);

		if (
			!isset($_GET['page'])
			|| !in_array($_GET['page'], $page_ids)
		) {
			return false;
		}

		return true;
	}

	public static function hash_string( $string, $algo = 'sha256' ) {

		// If the given algorithm is supported, hash the string
		if (in_array($algo, hash_algos(), true)) {
			return hash($algo, $string);
		}

		return $string;
	}

	public static function get_random_ip() {

		$ip = '';

		// Generate a random IP address
		for ($i = 0; $i < 4; $i++) {
			$ip .= rand(0, 255);
			if ($i < 3) {
				$ip .= '.';
			}
		}

		return $ip;
	}

	/**
	 * We are controlling the entire output in all formats from here. Why?
	 * Because each pixel has different requirements for each data field.
	 * Hashed, not hashed, lower case, not lower case, phone with + or without +,
	 * etc.
	 *
	 * @return array
	 */
	public static function get_user_data( $order = null ) {

		// if the user_data array is already set and not empty, return it
		if (!empty(self::$user_data)) {
			return self::$user_data;
		}

		$data = [];

		$order
			= (
			Environment::is_woocommerce_active()
			&& !$order
			&& Shop::pmw_is_order_received_page()
			&& Shop::pmw_get_current_order()
		)
			? Shop::pmw_get_current_order()
			: $order;


		// If the order is not null get the $current_user from the order
		if (
			$order
			&& $order->get_user()
		) {
			$current_user = $order->get_user();
		} else {
			$current_user = is_user_logged_in() ? wp_get_current_user() : null;
		}

		// If the user is logged in, get the user data
		if ($current_user) {
			$data['id']['raw']    = get_current_user_id();
			$data['id']['sha256'] = self::hash_string($data['id']['raw']);
		}

		/**
		 * Determine the details.
		 *
		 * If logged in use the logged-in user data.
		 * On the order page, override the logged-in user data with the order data.
		 */

		// Email
		$email = is_user_logged_in() && $current_user->user_email ? $current_user->user_email : '';
		$email = $order && $order->get_billing_email() ? $order->get_billing_email() : $email;

		// First name
		$first_name = is_user_logged_in() && $current_user->first_name ? $current_user->first_name : '';
		$first_name = $order && $order->get_billing_first_name() ? $order->get_billing_first_name() : $first_name;

		// Last name
		$last_name = is_user_logged_in() && $current_user->last_name ? $current_user->last_name : '';
		$last_name = $order && $order->get_billing_last_name() ? $order->get_billing_last_name() : $last_name;

		// Phone
		$phone = is_user_logged_in() && $current_user->billing_phone ? $current_user->billing_phone : '';
		$phone = $order && $order->get_billing_phone() ? $order->get_billing_phone() : $phone;

		// City
		$city = is_user_logged_in() && $current_user->billing_city ? $current_user->billing_city : '';
		$city = $order && $order->get_billing_city() ? $order->get_billing_city() : $city;

		// State
		$state = is_user_logged_in() && $current_user->billing_state ? $current_user->billing_state : '';
		$state = $order && $order->get_billing_state() ? $order->get_billing_state() : $state;

		// Postcode
		$postcode = is_user_logged_in() && $current_user->billing_postcode ? $current_user->billing_postcode : '';
		$postcode = $order && $order->get_billing_postcode() ? $order->get_billing_postcode() : $postcode;

		// Country
		$country = is_user_logged_in() && $current_user->billing_country ? $current_user->billing_country : '';
		$country = $order && $order->get_billing_country() ? $order->get_billing_country() : $country;

		// Add the details to the data array and return it
		// Only add the data if it exists
		$data = $email ? self::get_user_object_email($data, $email) : $data;
		$data = $first_name ? self::get_user_object_first_name($data, $first_name) : $data;
		$data = $last_name ? self::get_user_object_last_name($data, $last_name) : $data;
		$data = $phone ? self::get_user_object_phone($data, $phone, $current_user) : $data;
		$data = $city ? self::get_user_object_city($data, $city) : $data;
		$data = $state ? self::get_user_object_state($data, $state) : $data;
		$data = $postcode ? self::get_user_object_postcode($data, $postcode) : $data;
		$data = $country ? self::get_user_object_country($data, $country) : $data;

		self::$user_data = $data;

		return $data;
	}

	public static function get_user_data_object( $order = null ) {
		$data = self::get_user_data($order);
		return json_decode(wp_json_encode($data));
	}

	private static function get_user_object_email( $data, $email ) {

		$email = self::trim_string($email);
		$email = strtolower($email);

		$data['email']['raw']      = $email;
		$data['email']['sha256']   = self::hash_string($email);
		$data['email']['facebook'] = self::hash_string($email);

		return $data;
	}

	private static function get_user_object_first_name( $data, $first_name ) {

		$first_name = self::trim_string($first_name);

		$data['first_name']['raw']       = $first_name;
		$data['first_name']['sha256']    = self::hash_string($first_name);
		$data['first_name']['facebook']  = strtolower($first_name);
		$data['first_name']['pinterest'] = self::hash_string(strtolower($first_name));


		return $data;
	}

	private static function get_user_object_last_name( $data, $last_name ) {

		$last_name = self::trim_string($last_name);

		$data['last_name']['raw']       = $last_name;
		$data['last_name']['sha256']    = self::hash_string($last_name);
		$data['last_name']['facebook']  = strtolower($last_name);
		$data['last_name']['pinterest'] = self::hash_string(strtolower($last_name));

		return $data;
	}

	private static function get_user_object_phone( $data, $phone, $current_user ) {

		$phone = self::trim_string($phone);

		$data['phone']['raw']            = $phone;
		$data['phone']['e164']           = self::get_e164_formatted_phone_number(strtolower($phone), self::get_user_country_code($current_user));
		$data['phone']['sha256']['raw']  = self::hash_string($phone);
		$data['phone']['sha256']['e164'] = self::hash_string($data['phone']['e164']);
		$data['phone']['facebook']       = str_replace('+', '', strtolower($phone));
		$data['phone']['pinterest']      = self::hash_string(preg_replace('/[^0-9]/', '', $data['phone']['e164']));

		return $data;
	}

	private static function get_user_object_city( $data, $city ) {

		$city = self::trim_string($city);

		$data['city']['raw']       = $city;
		$data['city']['sha256']    = self::hash_string($city);
		$data['city']['facebook']  = strtolower($city);
		$data['city']['pinterest'] = self::hash_string(strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $city)));

		return $data;
	}

	private static function get_user_object_state( $data, $state ) {

		$state = self::trim_string($state);

		$data['state']['raw']       = $state;
		$data['state']['sha256']    = self::hash_string($state);
		$data['state']['facebook']  = preg_replace('/[a-zA-Z]{2}-/', '', strtolower($state));
		$data['state']['pinterest'] = self::hash_string(strtolower($state));

		return $data;
	}

	private static function get_user_object_postcode( $data, $postcode ) {

		$postcode = self::trim_string($postcode);

		$data['postcode']['raw']       = $postcode;
		$data['postcode']['facebook']  = strtolower($postcode);
		$data['postcode']['pinterest'] = self::hash_string(preg_replace('/[^0-9]/', '', $postcode));

		return $data;
	}

	/**
	 * This function takes a user data array and a country string and adds the country values to the user object.
	 *
	 * @param array  $data
	 *        The user data array.
	 *
	 * @param string $country
	 *        The country string to be added to the user object.
	 *
	 * @return array
	 *         The updated user data array with country values added to the user object.
	 */
	private static function get_user_object_country( $data, $country ) {

		$country = self::trim_string($country);

		$data['country']['raw']       = $country;
		$data['country']['facebook']  = strtolower($country);
		$data['country']['pinterest'] = self::hash_string(strtolower($country));

		return $data;
	}

	/**
	 * Filter to define if all s2s requests should be sent blocking
	 *
	 * @return bool
	 */
	public static function should_all_s2s_requests_be_sent_blocking() {
		return (bool) apply_filters('pmw_send_all_s2s_requests_blocking', false);
	}

	/**
	 * This function takes a number and formats it as a decimal.
	 *
	 * @param mixed $number
	 *         The number to format. Can be a string or a float.
	 *
	 * @param mixed $dp
	 *         The number of decimal places to round to. Default is false, which means no rounding is performed.
	 *
	 * @param bool  $trim_zeros
	 *         Whether to trim trailing zeros and the decimal point. Default is false.
	 *
	 * @return float | null | string
	 *         Returns the formatted decimal number as a string, or false if the input is not a valid number.
	 */
	public static function format_decimal( $number, $dp = false, $trim_zeros = false ) {

		if (function_exists('wc_format_decimal')) {
			return wc_format_decimal($number, $dp, $trim_zeros);
		} else {
			if (is_string($number)) {

				// Remove all spaces
				$number = str_replace(' ', '', $number);

				// Convert multiple dots to one
				// $number = preg_replace('/\.{2,}/', '.', $number);
				$number = preg_replace('/\.(?![^.]+$)|[^0-9.-]/', '', $number);

				// echo $number . PHP_EOL;

				$number = (float) preg_replace('/[^0-9\.\-]/', '', $number);
			}

			if (!is_float($number)) {
				return null;
			}

			if (false !== $dp) {
				$number = round($number, $dp);
			}

			$number = (string) $number;

			if ($trim_zeros) {
				$number = rtrim($number, '0');
				$number = rtrim($number, '.');
			}

			return $number;
		}
	}

	/**
	 * Checks if experimental feature EXPERIMENTAL_PMW is enabled.
	 *
	 * @return bool
	 *        Returns true if EXPERIMENTAL_PMW or PMW_EXPERIMENTS are defined and have a truthy value, otherwise returns false.
	 */
	public static function is_experiment() {
		return
			( defined('EXPERIMENTAL_PMW') && EXPERIMENTAL_PMW )
			|| ( defined('PMW_EXPERIMENTS') && PMW_EXPERIMENTS );
	}

	/**
	 * This function checks if the WooCommerce compatibility declaration function exists.
	 *
	 * @return bool
	 */
	public static function does_the_woocommerce_declare_compatibility_function_exist() {
		return
			class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')
			&& method_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil', 'declare_compatibility');
	}

	/**
	 * Declare compatibility with a feature for WooCommerce.
	 *
	 * @param string $feature_id
	 *        The ID of the feature to declare compatibility with.
	 *
	 * @param string $plugin_file
	 *        Optional. The plugin file to specify the compatibility for. Default is PMW_PLUGIN_BASENAME.
	 *
	 * @param bool   $positive_compatibility
	 *        Optional. Whether to declare positive compatibility or not. Default is true.
	 */
	public static function declare_woocommerce_compatibility( $feature_id, $plugin_file = PMW_PLUGIN_BASENAME, $positive_compatibility = true ) {

		if (!self::does_the_woocommerce_declare_compatibility_function_exist()) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility($feature_id, $plugin_file, $positive_compatibility);
	}

	/**
	 * Get the version information of the software.
	 *
	 * @return array The version information.
	 *  - 'number' : string : The current version number.
	 *  - 'pro' : bool : Whether the software is a premium version or not.
	 *  - 'eligibleForUpdates' : bool : Whether the software can receive updates or not.
	 *  - 'distro' : string : The distribution identifier of the software.
	 *  - 'beta' : bool : Whether the software is a beta version or not.
	 */
	public static function get_version_info() {
		return [
			'number'             => PMW_CURRENT_VERSION,
			'pro'                => wpm_fs()->is__premium_only(),
			'eligibleForUpdates' => wpm_fs()->can_use_premium_code__premium_only(),
			'distro'             => PMW_DISTRO,
			//			'beta'               => PMW_DISTRO === 'fms' ? wpm_fs()->is_beta() : false,
			'beta'               => self::is_beta(),
		];
	}


	/**
	 * This function checks if the application is running in beta mode.
	 *
	 * @return bool
	 *
	 * @see   self::is_experiment()
	 * @see   wpm_fs()->is_beta()
	 *
	 * @since 1.35.1
	 */
	private static function is_beta() {

		if (self::is_experiment()) {
			return true;
		}

		if (PMW_DISTRO === 'fms') {
			return wpm_fs()->is_beta();
		}

		return false;
	}

	public static function is_valid_ipv6_address( $ip ) {
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	public static function is_woocommerce_session_active() {

		// If WC() not available, return false
		if (!function_exists('WC')) {
			return false;
		}

		// If WC()->session not available, return false
		if (!isset(WC()->session)) {
			return false;
		}

		// If WC()->session->has_session() not available, return false
		if (!method_exists(WC()->session, 'has_session')) {
			return false;
		}

		return WC()->session->has_session();
	}

	/**
	 * Check if the WP_DEBUG constant is defined and set to true or false.
	 *
	 * This function essentially checks whether WordPress debugging mode
	 * is active or not. The WP_DEBUG constant is an in-built WordPress
	 * constant that can be used to trigger the 'debug' mode throughout
	 * WordPress.
	 *
	 * @return bool Returns true if WP_DEBUG is defined and set to true,
	 * otherwise it returns false.
	 *
	 * @since 1.35.1
	 */
	public static function is_wp_debug_mode_active() {
		return defined('WP_DEBUG') && WP_DEBUG;
	}

	/**
	 * Checks if the PMW_DEBUG_CONSTANT is defined and true.
	 *
	 * This function checks if the PHP constant 'PMW_DEBUG' is defined in the system. If it is defined,
	 * this function further checks that the value of the constant is truthy. It essentially determines
	 * if the PMW Debug Mode is active in the environment.
	 *
	 * @return bool Returns true if 'PMW_DEBUG' constant is defined and its value is true, else returns false.
	 *
	 * @since 1.36.0
	 */
	public static function is_pmw_debug_mode_active() {
		return defined('PMW_DEBUG') && PMW_DEBUG;
	}

	/**
	 * Retrieves the file name of the most recent WooCommerce log that starts with a specific source.
	 *
	 * This function fetches all log files, then filters them to retain only the ones
	 * starting with the specified source string. It then returns the file name of the
	 * most recent log matching this criteria.
	 *
	 * If there are no logs, or no logs match the source, an empty string is returned.
	 *
	 * @param string $source The source string that the log file name should start with.
	 * @return string The file name of the most recent log that starts with $source. If no such log exists, returns an empty string.
	 */
	private static function get_file_name_of_most_recent_wc_log( $source ) {

		// return if the class WC_Log_Handler_File does not exist
		if (!class_exists('WC_Log_Handler_File')) {
			return '';
		}

		$logs = WC_Log_Handler_File::get_log_files();

		if (empty($logs)) {
			return '';
		}

		// If $logs array contains a key that starts with $source . '-' then return the latest in the array
		$pmw_logs = array_filter(
			$logs,
			function ( $key ) use ( $source ) {
				return strpos($key, $source . '-') === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		if (empty($pmw_logs)) {
			return '';
		}

		$last_key = array_key_last($pmw_logs);

		return $pmw_logs[$last_key];
	}

	/**
	 * Get the link to the most recent log file,
	 * for the given slug. The slug must be exactly
	 * the same as the source parameter in the log call.
	 *
	 * @param $source
	 *
	 * @return string|null
	 *
	 * @since 1.36.0
	 */
	public static function get_admin_url_link_to_recent_wc_log( $source ) {

		$file_name = self::get_file_name_of_most_recent_wc_log($source);

		if (empty($file_name)) {
			return null;
		}

		return admin_url('admin.php?page=wc-status&tab=logs&log_file=' . $file_name);
	}

	public static function get_external_url_to_most_recent_log( $source ) {

		$file_name = self::get_file_name_of_most_recent_wc_log($source);

		if (empty($file_name)) {
			return null;
		}

		return self::get_url_to_log_dir() . $file_name;
	}

	private static function get_url_to_log_dir() {
		// return if WC_LOG_DIR is not defined
		if (!defined('WC_LOG_DIR')) {
			return '';
		}

		$wc_log_dir = substr(trailingslashit(WC_LOG_DIR), strpos(trailingslashit(WC_LOG_DIR), '/wp-content/'));

		$wc_log_dir = get_bloginfo('url') . $wc_log_dir;

		return trailingslashit($wc_log_dir);
	}

	/**
	 * Gets all external log file URLs based on a specific source.
	 *
	 * This function searches all log files in the WooCommerce logs directory,
	 * selects those that start with the specified source or 'fatal-errors',
	 * and returns their URL.
	 *
	 * @param string $source The source log files to search for.
	 *
	 * @return string A JSON-encoded array of URLs to the log files.
	 *
	 * @since 1.36.0
	 */
	public static function get_all_external_log_file_urls( $source ) {

		$needles = [
			'fatal-errors',
			$source,
		];

		$logs = WC_Log_Handler_File::get_log_files();

		$logs = array_filter(
			$logs,
			function ( $key ) use ( $needles ) {
				foreach ($needles as $needle) {
					if (strpos($key, $needle . '-') === 0) {
						return true;
					}
				}
				return false;
			},
			ARRAY_FILTER_USE_KEY
		);

		$logs = array_values(array_map(
			function ( $log ) {
				return self::get_url_to_log_dir() . $log;
			},
			$logs
		));

		if (empty($logs)) {
			return null;
		}

		return wp_json_encode($logs);
	}

	/**
	 * Checks if a scheduled action exists in Action Scheduler.
	 *
	 * This function checks if a scheduled action with a specific hook, arguments, and group exists in Action Scheduler.
	 * If the function 'as_has_scheduled_action' exists, it uses it to check for the scheduled action.
	 * If 'as_has_scheduled_action' does not exist, it uses 'as_next_scheduled_action' to check for the scheduled action.
	 * This is necessary as installs that use plugins with versions of Action Scheduler older than 3.2.1 don't reliably load
	 * the latest version of Action Scheduler which contains the 'as_has_scheduled_action' function.
	 *
	 * Read: https://developer.woo.com/2021/10/12/best-practices-for-deconflicting-different-versions-of-action-scheduler/
	 *
	 * @param string $hook             The hook of the scheduled action to check for.
	 * @param array  $args             Optional. The arguments of the scheduled action to check for. Default is an empty array.
	 * @param string $group            Optional. The group of the scheduled action to check for. Default is an empty string.
	 * @param string $partial_matching Optional. Whether to perform partial matching on the arguments or not. Default is 'off'. Can be 'like' or 'json'.
	 *
	 * @return bool Returns true if a scheduled action with the specified hook, arguments, and group exists, otherwise returns false.
	 */
	public static function pmw_as_has_scheduled_action( $hook, $args = [], $group = '', $partial_matching = 'off' ) {

		// If $partial_matching is true, set it to 'like'
		if ($partial_matching) {
			$partial_matching = 'like';
		}

		return (bool) as_get_scheduled_actions([
			'hook'                  => $hook,
			'args'                  => $args,
			'group'                 => $group,
			'partial_args_matching' => $partial_matching,
			'status'                => self::get_action_scheduler_active_states(),
			'per_page'              => 1,        // to make it as fast as possible
			'orderby'               => 'none',   // to make it as fast as possible
		],
			'ids'                    // to make it as fast as possible
		);
	}

	/**
	 * This function returns the active states for the Action Scheduler.
	 *
	 * It checks if the class 'ActionScheduler_Store' exists. If it does, it returns an array with the constants
	 * 'STATUS_PENDING' and 'STATUS_RUNNING' from the 'ActionScheduler_Store' class.
	 *
	 * If the 'ActionScheduler_Store' class does not exist, it returns an array with the strings 'Pending' and 'In-progress'.
	 *
	 * @return array An array containing the active states for the Action Scheduler.
	 *
	 * @since 1.37.1
	 */
	private static function get_action_scheduler_active_states() {

		if (class_exists('ActionScheduler_Store')) {
			return [
				ActionScheduler_Store::STATUS_PENDING,
				ActionScheduler_Store::STATUS_RUNNING,
			];
		}

		return [
			'Pending',
			'In-progress',
		];
	}

	public static function can_order_modal_be_shown() {
		return Options::is_ga4_data_api_active()
			|| Options::is_order_level_ltv_calculation_active();
	}

	public static function get_script_string_allowed_html() {
		return [
			'class' => [],
		];
	}

	public static function get_script_string() {
		$script_string = '';

		$attributes = [];

		// if the Iubenda plugin is active, add the Iubenda attributes
		if (Environment::is_iubenda_active()) {
			// add an attribute class and add a class name _iub_cs_skip to it
			$attributes['class'][] = '_iub_cs_skip';
		}

		// Build the attribute string
		foreach ($attributes as $attribute => $values) {
			$script_string .= ' ' . $attribute . '="' . implode(' ', $values) . '"';
		}

		return $script_string;
	}

	public static function iubenda_script_exception_start() {

		if (!Environment::is_iubenda_active()) {
			return;
		}

		?>

		<!--IUB-COOKIE-BLOCK-SKIP-START-->
		<?php
	}

	public static function iubenda_script_exception_end() {
		if (!Environment::is_iubenda_active()) {
			return;
		}

		?>

		<!--IUB-COOKIE-BLOCK-SKIP-END-->
		<?php
	}
}
