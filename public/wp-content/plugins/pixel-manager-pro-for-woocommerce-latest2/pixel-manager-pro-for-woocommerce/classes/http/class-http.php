<?php

namespace WCPM\Classes\Http;

use WCPM\Classes\Geolocation;
use WCPM\Classes\Logger;
use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Http {

	protected $options;
	protected $options_obj;
	protected $post_request_args;
	protected $server_base_path;
	protected $mp_purchase_hit_key;
	protected $mp_full_refund_hit_key;
	protected $mp_partial_refund_hit_key;
	protected $logger;
	public function __construct( $options ) {

		$this->options     = Options::get_options();
		$this->options_obj = Options::get_options_obj();

		$this->post_request_args = [
			'body'        => '',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => Options::is_http_request_logging_enabled(),
			'headers'     => [],
			'cookies'     => [],
			'sslverify'   => !Geolocation::is_localhost(),
		];

		$this->post_request_args = apply_filters_deprecated('wooptpm_http_post_request_args', [ $this->post_request_args ], '1.13.0', 'pmw_http_post_request_args');
		$this->post_request_args = apply_filters_deprecated('wpm_http_post_request_args', [ $this->post_request_args ], '1.31.2', 'pmw_http_post_request_args');

		$this->post_request_args = apply_filters('pmw_http_post_request_args', $this->post_request_args);
	}

	protected function send_hit( $request_url, $payload = null ) {

		if ($payload) {
			$this->post_request_args['body'] = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// Log our request
		if (Options::is_http_request_logging_enabled()) {

			$this->post_request_args['blocking'] = true;

			$response = wp_safe_remote_post($request_url, $this->post_request_args);

			Logger::debug('request url: ' . $request_url);

			if ($payload) {
				Logger::debug('payload: ' . print_r($payload, true));
			}

			Logger::debug('response body: ' . wp_remote_retrieve_body($response));

			if (
				200 !== wp_remote_retrieve_response_code($response)
				&& 204 !== wp_remote_retrieve_response_code($response)
			) {
				Logger::error('response body: ' . wp_remote_retrieve_body($response));
			}

			if (is_wp_error($response)) {
				Logger::error('response error message: ' . $response->get_error_message());
			}

		} else {
			wp_safe_remote_post($request_url, $this->post_request_args);
		}
	}
}
