<?php

namespace WCPM\Classes\Admin\Opportunities\Free;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Opportunities\Opportunity;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Opportunity: Pinterest Enhanced Match
 *
 * @since 1.28.1
 */
class Pinterest_Enhanced_Match extends Opportunity {

	public static function available() {

		// Google Ads purchase conversion must be enabled
		if (!Options::is_pinterest_active()) {
			return false;
		}

		// Conversion Adjustments conversions must be disabled
		if (Options::is_pinterest_enhanced_match_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'pinterest-enhanced-match',
			'title'       => esc_html__(
				'Pinterest Enhanced Match',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that Pinterest is enabled, but Pinterest Enhanced Match has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Pinterest Enhanced Match will improve conversion tracking accuracy when no Pinterest cookie is present and with cross-device checkouts.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('pinterest_enhanced_match'),
			//				'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}
