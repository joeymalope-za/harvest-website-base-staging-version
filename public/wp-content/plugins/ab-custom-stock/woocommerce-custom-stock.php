<?php
/**
 * Plugin Name: Harvest Custom Stock
 * Description: Adds custom stock locations and displays stock according to user shipping address.
 * Version: 1.0
 * Author: //Aaron B 🔥
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the main class file.
include_once dirname( __FILE__ ) . '/includes/class-wc-custom-stock.php';
