<?php

namespace WCPM\Classes\Admin\Opportunities\Free;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Opportunities\Opportunity;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Opportunity: Dynamic Remarketing
 *
 * @since 1.28.0
 */
class Dynamic_Remarketing extends Opportunity {

	public static function available() {

		// At least one paid ads pixel must be enabled
		if (!Options::is_at_least_one_marketing_pixel_active()) {
			return false;
		}

		// Dynamic Remarketing must be disabled
		if (Options::is_dynamic_remarketing_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'dynamic-remarketing',
			'title'       => esc_html__(
				'Dynamic Remarketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that at least one paid ads pixel is enabled, but Dynamic Remarketing has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Dynamic Remarketing output will allow you to collect dynamic audiences (such as general visitors, product viewers, cart abandoners, and buyers) and create dynamic remarketing campaigns.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('google_ads_dynamic_remarketing'),
			'setup_video' => '7fhtv2s94t',
			//			'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}
