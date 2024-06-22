Harvest Toggle Membership Plugin

This is a WordPress plugin designed to work with WooCommerce. It adds member pricing, a checkout toggle for membership selection, and consult fees to orders at checkout. It also automatically adds a High Rollers Club Membership product to the cart on the checkout page.

Features

- Member Pricing: Different pricing for standard and high roller members. High Rollers Club members get the default WooCommerce sale price, while Standard Members get the regular price.
- Checkout Toggle: Allows users to select their membership type at checkout. This also dictates which price is used (Sale or Regular).
- Consult Fees: Adds consult fees to orders at checkout if usermeta field 'consultation_payment_pending' has a value of "1" - indicating the user has had a consultation.
- Automatic Product Addition: Automatically adds a High Rollers Club Membership product to the cart on the checkout page, users must opt-in to HRC membership each time they checkout as per specification (previously was opt-out which is how the plugin is configured).


The plugin works automatically once activated. It adds a toggle switch to the checkout page that allows users to select their membership type. Depending on the membership type selected, different pricing and fees are applied.

Code Overview

The plugin is structured in an object-oriented manner and follows WordPress plugin development best practices.

- plugin.php: This is the main plugin file. It includes the activation and deactivation hooks and loads the core plugin class.
- includes/class-toggle.php: This class handles the toggle functionality on the checkout page.
- includes/class-product-handler.php: This class handles the product addition and removal based on the toggle state.
- public/class-public.php: This class handles the public-facing functionality of the plugin.
- admin/class-admin.php: This class handles the admin-specific aspects of the plugin.
- public/js/public.js: This JavaScript file handles the AJAX request when the toggle state changes.
- public/css/public.css and admin/css/admin.css: These CSS files style the public and admin sides of the plugin respectively.


Hooks

The plugin uses several WordPress and WooCommerce hooks:

- woocommerce_after_checkout_form: Used to add the toggle to the checkout page.
- wp_ajax_toggle_membership and wp_ajax_nopriv_toggle_membership: Used to handle the AJAX request when the toggle state changes.
- woocommerce_before_calculate_totals: Used to update the product prices based on the membership type.
- woocommerce_cart_calculate_fees: Used to add the consultation fee for standard members.
- woocommerce_checkout_update_order_meta: Used to add the consultation fee to the order meta.
- woocommerce_thankyou: Used to update the consultation payment status after checkout.