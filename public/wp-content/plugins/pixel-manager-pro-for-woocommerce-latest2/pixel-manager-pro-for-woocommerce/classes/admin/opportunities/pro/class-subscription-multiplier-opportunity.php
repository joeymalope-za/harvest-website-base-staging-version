<?php

namespace WCPM\Classes\Admin\Opportunities\Free;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Environment;
use WCPM\Classes\Admin\Opportunities\Opportunity;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Opportunity: Subscription Multiplier
 *
 * Checks for the WooCommerce Subscriptions plugin.
 * Doesn't check for YITH WooCommerce Subscription plugin, because that plugin doesn't register a proper subscription product type.
 * Doesn't check for WP Swings WooCommerce Subscription plugin, because that plugin doesn't register a proper subscription product type.
 *
 * @since 1.28.1
 */
class Subscription_Multiplier extends Opportunity {

	public static function available() {

		// WooCommerce Subscriptions must be active
		if (!Environment::is_woocommerce_subscriptions_active()) {
			return false;
		}

		// Subscription Multiplier must be 1
		if (Options::get_subscription_multiplier() != 1) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'subscription-multiplier',
			'title'       => esc_html__(
				'Subscription Multiplier',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that a WooCommerce Subscriptions plugin is enabled, but the Subscription Multiplier is still set to 1.00.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Setting a value in the Subscription Multiplier field will multiply the conversion value of subscription products by the specified value to better match the lifetime value of the subscription. This will improve campaign optimization.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('subscription_value_multiplier'),
			//				'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}
