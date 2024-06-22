<?php

// Same day options
$sameday = get_option('sherpa_settings_sameday_delivery_options_sameday');
$sameday_1hr = get_option('sherpa_settings_sameday_delivery_options_service_1hr');
$sameday_2hr = get_option('sherpa_settings_sameday_delivery_options_service_2hr');
$sameday_4hr = get_option('sherpa_settings_sameday_delivery_options_service_4hr');
$sameday_at = get_option('sherpa_settings_sameday_delivery_options_service_at');
$sameday_bulk_rate = get_option('sherpa_settings_sameday_delivery_options_service_bulk_rate');

$sameday     = $sameday ?: __('Today', 'sherpa');
$sameday_1hr = $sameday_1hr ?: __('1 hour delivery', 'sherpa');
$sameday_2hr = $sameday_2hr ?: __('2 hour delivery', 'sherpa');
$sameday_4hr = $sameday_4hr ?: __('4 hour delivery', 'sherpa');
$sameday_at  = $sameday_at ?: __('Same day delivery', 'sherpa');
$sameday_bulk_rate  = $sameday_bulk_rate ?: __('Bulk rate delivery', 'sherpa');

// Schedule for later options
$later = get_option('sherpa_settings_later_delivery_options_later');
$later_1hr = get_option('sherpa_settings_later_delivery_options_service_1hr');
$later_2hr = get_option('sherpa_settings_later_delivery_options_service_2hr');
$later_4hr = get_option('sherpa_settings_later_delivery_options_service_4hr');
$later_at = get_option('sherpa_settings_later_delivery_options_service_at');
$later_bulk_rate = get_option('sherpa_settings_later_delivery_options_service_bulk_rate');

$later = $later ?: __('Schedule for Later', 'sherpa');
$later_1hr = $later_1hr ?: __('1 hour delivery', 'sherpa');
$later_2hr = $later_2hr ?: __('2 hour delivery', 'sherpa');
$later_4hr = $later_4hr ?: __('4 hour delivery', 'sherpa');
$later_at = $later_at ?: __('Same day delivery', 'sherpa');
$later_bulk_rate = $later_bulk_rate ?: __('Bulk rate delivery', 'sherpa');

/**
 * USPS Services and subservices
 */
return array(
    'service_sameday' => array(
        'name' => __($sameday, 'sherpa'),
        'services' => array(
            'service_1hr' => __($sameday_1hr, 'sherpa'),
            'service_2hr' => __($sameday_2hr, 'sherpa'),
            'service_4hr' => __($sameday_4hr, 'sherpa'),
            'service_at'  => __($sameday_at, 'sherpa'),
            'service_bulk_rate'  => __($sameday_bulk_rate, 'sherpa'),
        )
    ),
    'service_later' => array(
        'name' => __($later, 'sherpa'),
        'services' => array(
            'service_1hr' => __($later_1hr, 'sherpa'),
            'service_2hr' => __($later_2hr, 'sherpa'),
            'service_4hr' => __($later_4hr, 'sherpa'),
            'service_at'  => __($later_at, 'sherpa'),
            'service_bulk_rate'  => __($later_bulk_rate, 'sherpa'),
        )
    ),
);
