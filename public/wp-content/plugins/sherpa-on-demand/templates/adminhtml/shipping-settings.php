<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for flat rate shipping.
 */
$settings = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'sherpa'),
        'type' => 'checkbox',
        'label' => __('Enable this shipping method', 'sherpa'),
        'default' => 'no',
    ),
    'title' => array(
        'title' => __('Method Title', 'sherpa'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'sherpa'),
        'default' => __('Sherpa on demand', 'sherpa'),
        'desc_tip' => true
    ),
    'availability' => array(
        'title' => __('Availability', 'sherpa'),
        'type' => 'select',
        'default' => 'all',
        'class' => 'availability wc-enhanced-select',
        'options' => array(
            'all' => __('All allowed countries', 'sherpa'),
            'specific' => __('Specific Countries', 'sherpa'),
        ),
    ),
    'countries' => array(
        'title' => __('Specific Countries', 'sherpa'),
        'type' => 'multiselect',
        'class' => 'wc-enhanced-select',
        'css' => 'width: 450px;',
        'default' => '',
        'options' => WC()->countries->get_shipping_countries(),
        'custom_attributes' => array(
            'data-placeholder' => __('Select some countries', 'sherpa')
        )
    ),
    'origin_state' => array(
        'title'       => __('Origin State', 'wf-shipping-dhl'),
        'type'        => 'text',
        'description' => __('Enter state for the <strong>Shipper</strong>.', 'sherpa'),
        'default'     => ''
    ),
    'origin_postcode' => array(
        'title'       => __('Origin Postcode', 'sherpa'),
        'type'        => 'text',
        'description' => __('Enter postcode for the <strong>Shipper</strong>.', 'sherpa'),
        'default'     => ''
    ),
    'origin_city'     => array(
        'title'       => __('Origin City', 'sherpa'),
        'type'        => 'text',
        'description' => __('Enter city for the <strong>Shipper</strong>.', 'sherpa'),
        'default'     => ''
    ),
    'origin_address' => array(
        'title'       => __('Origin Address', 'sherpa'),
        'type'           => 'text',
        'description' => __('Enter address for the <strong>Shipper</strong>.', 'sherpa'),
        'default'     => ''
    ),
    'debug' => array(
        'title'       => __('Debug Mode', 'sherpa'),
        'label'       => __('Enable debug mode', 'sherpa'),
        'type'           => 'checkbox',
        'default'       => 'no',
        'desc_tip'    => true,
        'description' => __('Enable debug mode to show debugging information on the cart/checkout.', 'sherpa')
    ),
    'services' => array(
        'type' => 'services',
    ),
);

return $settings;
