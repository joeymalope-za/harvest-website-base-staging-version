jQuery(document).ready(function($) {
  // Check if we are on the WooCommerce checkout page
  if (jQuery('body').is('.woocommerce-checkout')) {      // Show an alert
      alert('Welcome to the checkout page!');
  }
});