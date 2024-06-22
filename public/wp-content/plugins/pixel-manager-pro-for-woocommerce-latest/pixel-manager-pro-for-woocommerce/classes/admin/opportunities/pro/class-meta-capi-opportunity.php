<?php

namespace WCPM\Classes\Admin\Opportunities\Free;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Opportunities\Opportunity;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Opportunity: Meta CAPI
 *
 * @since 1.29.2
 */
class Meta_CAPI extends Opportunity {

	public static function available() {

		// Facebook Pixel must be enabled
		if (!Options::is_facebook_active()) {
			return false;
		}

		// Facebook CAPI must be disabled
		if (Options::is_facebook_capi_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'meta-capi',
			'title'       => esc_html__(
				'Meta (Facebook) CAPI',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that the Meta (Facebook) Pixel is enabled, but Meta (Facebook) CAPI has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Meta (Facebook) CAPI will improve conversion tracking accuracy overall.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('facebook_capi_token'),
			//				'learn_more_link' => '#',
			'since'       => 1673553471, // timestamp
		];
	}
}
