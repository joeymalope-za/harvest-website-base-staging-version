<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Sherpa
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


global $wpdb;

unset(WC()->session->sherpa_chosen_shipping_methods);
unset(WC()->session->shipping_for_package);
unset(WC()->session->shipping_for_package_0);

// Delete all the wc_ship transient scum, you aren’t wanted around here, move along.
// Same as being in shipping debug mode
$transients = $wpdb->get_col("SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE '_transient_wc_ship%'");

if (count($transients)) {
	foreach ($transients as $tr) {
		$hash = substr($tr, 11);
		delete_transient($hash);
	}
}

$transient_value = get_transient('shipping-transient-version');
WC_Cache_Helper::delete_version_transients($transient_value);

/**
 * Sherpa Library
 *
 * @author AAlogics Team <team@aalogics.com>
 */
if (!class_exists('Sherpa_Sherpa')) {
	class Sherpa_Sherpa {

		/**
		 * Error Constants
		 */
		const ERR_INVALID_COUNTRY = 'Sorry, shipping to selected country is not available.';
		const ERR_INVALID_DEST = 'Please enter a delivery address to view available shipping methods.';
		const ERR_NO_METHODS = 'No shipping methods available';
		const ERR_INTERNATIONAL = 'International delivery is not available at this time.';

		/* Sherpa Delivery System Time Zone */
		const TIME_ZONE = 'Australia/Sydney';

		/* Sherpa Delivery System Buffer Treshold */
		const BUFFER_THRESHOLD = 5;

		/* Sherpa Delivery System Default Buffer */
		const DEFAULT_BUFFER = 15;

		/* Sherpa Delivery System same day delivery */
		const METHOD_SAMEDAY = 'service_sameday';

		/* Sherpa Delivery System Next day delivery */
		const METHOD_NEXTDAY = 'service_later';

		/* Sherpa Delivery System 1hr Delivery */
		const OPTION_SERVICE_1_HOUR = 'service_1hr';

		/* Sherpa Delivery System 2hrs Delivery */
		const OPTION_SERVICE_2_HOUR = 'service_2hr';

		/* Sherpa Delivery System 4hrs Delivery */
		const OPTION_SERVICE_4_HOUR = 'service_4hr';

		/* Sherpa Delivery System Customer Delivery Option */
		const OPTION_SERVICE_ANYTIME = 'service_at';

		/* Sherpa Delivery System bulk rate Delivery */
		const OPTION_SERVICE_BULK_RATE = 'service_bulk_rate';

		/* Sherpa Delivery System Preparation No Time */
		const PREP_TIME_NO = 'NP';

		/* Sherpa Delivery System 1hr Preparation Time */
		const PREP_TIME_1HOUR = '1H';

		/* Sherpa Delivery System 2hr Preparation Time */
		const PREP_TIME_2HOUR = '2H';

		/* Sherpa Delivery System 4hr Preparation Time */
		const PREP_TIME_4HOUR = '4H';

		/* Sherpa Delivery System 24hr Preparation Time */
		const PREP_TIME_24HOUR = '24H';

		/* Sherpa Delivery System Next Business Day Preparation Time */
		const PREP_TIME_NEXT_BUSINESS = 'NB';

		/* Sherpa Delivery System 30mins Preparation Time */
		const PREP_TIME_30MIN = '30M';

		/* Sherpa Delivery System 1hr Delivery */
		const ONE_HOUR_DELIVERY = 'oneHour';

		/* Sherpa Delivery System 2hrs Delivery */
		const TWO_HOUR_DELIVERY = 'twoHours';

		/* Sherpa Delivery System 4hr Preparation Time */
		const FOUR_HOUR_DELIVERY = 'fourHours';

		/* Sherpa Delivery System Bulk Rate Delivery */
		const BULK_RATE_DELIVERY = 'bulkRate';

		/* Sherpa Delivery System 1hr Preparation Time */
		const LOG_FILE_NAME = 'sherpa.log';

		/* Log File name */
		const TOKEN_LOG_FILE_NAME = 'token.log';

		/* Sherpa Library Error Message For More than 50 km */
		const ERROR_MESSAGE_50_KM = 'Delivery distance is too long, must be less than 50 KM.';

		/* Sherpa Delivery Standard Rates */
		const SHERPA_STANDARD_RATES = 'ST';

		/* Sherpa Delivery Not Allowed */
		const DELIVERY_NOT_ALLOWED = 'ND';

		/* Sherpa Dellivery Flat Rate */
		const DELIVERY_RATE_FLAT = 'FL';

		/* Sherpa Delilvery Rate Margin */
		const DELIVERY_RATE_MARGIN = 'MR';

		/* Sherpa Delivery Rate */
		const DELIVERY_RATE_SHERPA = 'SR';

		/* Other delivery than Sherpa */
		const DELIVERY_OTHER_THAN_SHERPA = '-1';

		const SHERPA_SANDBOX_BASE_URL = 'https://qa.deliveries.sherpa.net.au/';
		const SHERPA_LIVE_BASE_URL = 'https://deliveries.sherpa.net.au/';

		/* Sherpa Delivery Rate */
		const SHERPA_SETTING_SLUG = 'admin.php?page=wc-sherpa';


		/**
		 * Code of the carrier
		 *
		 * @var string
		 */
		const CODE = 'sherpa';

		/**
		 * Code of the carrier
		 *
		 * @var string
		 */
		protected $_code = self::CODE;

		/**
		 * Retrieve a timezone string from the WP settings
		 */
		public static function getTz() {
			return wp_timezone_string();
		}

		/**
		 * Get configuration data of carrier
		 *
		 * @param string $type
		 * @param string $code
		 * @return array|bool
		 */
		public static function getCode($type, $code = '') {

			static $codes;

			$sameday     = get_option('sherpa_settings_sameday_delivery_options_sameday');
			$sameday_1hr = get_option('sherpa_settings_sameday_delivery_options_service_1hr');
			$sameday_2hr = get_option('sherpa_settings_sameday_delivery_options_service_2hr');
			$sameday_4hr = get_option('sherpa_settings_sameday_delivery_options_service_4hr');
			$sameday_AT  = get_option('sherpa_settings_sameday_delivery_options_service_AT');
			$sameday_bulk_rate = get_option('sherpa_settings_sameday_delivery_options_service_bulk_rate');

			$sameday     = $sameday ?: 'Today';
			$sameday_1hr = $sameday_1hr ?: '1 hour delivery';
			$sameday_2hr = $sameday_2hr ?: '2 hour delivery';
			$sameday_4hr = $sameday_4hr ?: '4 hour delivery';
			$sameday_AT  = $sameday_AT ?: 'Same day delivery';
			$sameday_bulk_rate = $sameday_bulk_rate ?: 'Bulk rate delivery';

			$later     = get_option('sherpa_settings_later_delivery_options_later');
			$later_1hr = get_option('sherpa_settings_later_delivery_options_service_1hr');
			$later_2hr = get_option('sherpa_settings_later_delivery_options_service_2hr');
			$later_4hr = get_option('sherpa_settings_later_delivery_options_service_4hr');
			$later_AT  = get_option('sherpa_settings_later_delivery_options_service_AT');
			$later_bulk_rate = get_option('sherpa_settings_later_delivery_options_service_bulk_rate');

			$later     = $later ?: 'Schedule for Later';
			$later_1hr = $later_1hr ?: '1 hour delivery';
			$later_2hr = $later_2hr ?: '2 hour delivery';
			$later_4hr = $later_4hr ?: '4 hour delivery';
			$later_AT  = $later_AT ?: 'Same day delivery';
			$later_bulk_rate = $later_bulk_rate ?: 'Bulk rate delivery';

			$codes = array(
				'services' => array(
					'service_sameday' => __('Today', 'sherpa'),
					'service_later' => __('Schedule for Later', 'sherpa'),
					// 'service_both' => Mage::helper('sherpa')->__('Sherpa Both'),
				),
				'service_sameday' => array(
					'service_1hr' => __($sameday_1hr, 'sherpa'),
					'service_2hr' => __($sameday_2hr, 'sherpa'),
					'service_4hr' => __($sameday_4hr, 'sherpa'),
					'service_at' => __($sameday_AT, 'sherpa'),
					'service_bulk_rate' => __($sameday_bulk_rate, 'sherpa'),
				),
				'service_later' => array(
					'service_1hr' => __($later_1hr, 'sherpa'),
					'service_2hr' => __($later_2hr, 'sherpa'),
					'service_4hr' => __($later_4hr, 'sherpa'),
					'service_at' => __($later_AT, 'sherpa'),
					'service_bulk_rate' => __($later_bulk_rate, 'sherpa'),
				),
				'delivery_rates' => array(
					'SR' => __('Sherpa Rate', 'sherpa'),
					'FL' => __('Flat rate', 'sherpa'),
					'MR' => __('Margin', 'sherpa'),
				),
				'outside_radius' => array(
					'ST' => __('Use Standard Sherpa rates', 'sherpa'),
					'DO' => __('Do not offer delivery', 'sherpa'),
				),
				'perperation_time' => array(
					'NP' => __('No prep needed', 'sherpa'),
					'30M' => __('30 minutes', 'sherpa'),
					'1H' => __('1 hour', 'sherpa'),
					'2H' => __('2 hours', 'sherpa'),
					'4H' => __('4 hours', 'sherpa'),
				),
			);

			if (!isset($codes[$type])) {
				return false;
			} elseif ('' === $code) {
				return $codes[$type];
			}

			if (!isset($codes[$type][$code])) {
				return false;
			} else {
				return $codes[$type][$code];
			}
		}


		public function getTrackingInfo($tracking) {

			$track = Mage::getModel('shipping/tracking_result_status');
			if (Mage::helper('sherpa')->getConfigData('sherpa_credentials/sandbox')) {
				$externalUrl = Sherpa_Logistics_Helper_Data::SHERPA_SANDBOX_BASE_URL . 'track/';
			} else {
				$externalUrl = Sherpa_Logistics_Helper_Data::SHERPA_LIVE_BASE_URL . 'track/';
			}

			$track->setUrl($externalUrl . $tracking)
				->setTracking($tracking)
				->setCarrierTitle($this->getConfigData('name'));
			return $track;
		}

		public function isTrackingAvailable() {

			return true;
		}
	}
}
