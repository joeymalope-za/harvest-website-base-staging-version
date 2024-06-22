<?php

namespace WCPM\Classes\Admin\Opportunities\Free;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Opportunities\Opportunity;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Opportunity: Google Ads Enhanced Conversions
 *
 * @since 1.28.0
 */
class Google_Ads_Enhanced_Conversions extends Opportunity {

	public static function available() {

		// Google Ads purchase conversion must be enabled
		if (!Options::is_google_ads_purchase_conversion_enabled()) {
			return false;
		}

		// Enhanced conversions must be disabled
		if (Options::is_google_ads_enhanced_conversions_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'google-ads-enhanced-conversions',
			'title'           => esc_html__(
				'Google Ads Enhanced Conversions',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'The Pixel Manager detected that Google Ads purchase conversion is enabled, but Google Ads Enhanced Conversions has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Google Ads Enhanced Conversions will help you track more conversions that otherwise would get lost, such as cross-device conversions.',
					'woocommerce-google-adwords-conversion-tracking-tag	'
				),
			],
			'impact'          => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('google_ads_enhanced_conversions'),
			'learn_more_link' => Documentation::get_link('opportunity_google_ads_enhanced_conversions'),
			'since'           => 1672895375, // timestamp
		];
	}
}
