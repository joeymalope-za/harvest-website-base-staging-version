=== WP Optin Wheel Pro ===
Contributors: studiowombat,maartenbelmans
Tags: optin, popup, wheel, fortune, gamification
Requires at least: 3.7
Tested up to: 6.4.3
Requires PHP: 5.6.0
Stable tag: 4.1.1

== Description ==

The pro version of WP Optin Wheel.

* [&raquo; More info](https://www.studiowombat.com/plugin/wp-optin-wheel/)
* [&raquo; Demos](http://demo.studiowombat.com/wheel-of-fortune-demo/)
* [&raquo; Documentation](https://www.studiowombat.com/kb-category/wp-optin-wheel/)

== Changelog ==

= version 4.1.1 =

 * Improvement: wheel statistics are now also reset when you delete its logs.

= version 4.1 =

 * Improvement: "I'm done playing" button doesn't show on the shortcode wheel anymore when it makes sense to keep it hidden.
 * Improvement: show the wheel's shortcode after creating it, for easy access.
 * Improvement: center logo now has a border radius.

= version 4.0.9 =
 * Improvement: improved checking for when a wheel no longer exists so page builders do not throw errors.


= version 4.0.8 =
 * Fix: fixed update mechanism because some users were not able to activate the plugin.

= version 4.0.7 =
 * Fix: fixed a PHP warning when using SendInBlue (Brevo) as integration.

= version 4.0.6 =
 * Fix: fixed the "pull out" bubble text not disappearing when the wheel shows.

= version 4.0.5 =
 * Fix: updated our Klaviyo integration to their latest API version. If you want to switch, add your site ID to the Klaviyo settings.

= version 4.0.4 =
 * Fix: fixed a bug where the primary email field placeholder would not show in the backend settings.

= version 4.0.3 =
 * Improvement: declared compatibility with WooCommerce HPOS.
 * Other: verify Woo 7.9 compatibility.

= version 4.0.2 =
 * Improvement: added some more admin hooks for developers.
 * Other: verified latest Woo and WP.
 * Other: bumped minimum Woo version to 3.6.0 (guaranteed backward compat for 2+ releases).

= version 4.0.1 =
 * Improvement: improved statistics storage.
 * Improvement: added extra security measures.

= version 4.0 =
 * New: you can now set the spinning speed.
 * New: you can now set the spinning time.
 * New: you can turn off the pointer animation.
 * Improvement: improved animation performance.
 * Improvement: export headings are now translatable.
 * Improvement: updated confetti spray to appear more real-life.

= version 3.7.5 =
 * Fix: fixed a bug with fetching MailerLite lists (groups).

= version 3.7.4 =
 * Fix: fixed a PHP error when WooCommerce is not installed.

= version 3.7.3 =
 * Fix: fixed an issue where the GDPR settings would show an empty checkbox in the backend.

= version 3.7.2 =
 * Improvement: added field ID in the form builder list so you can easily reference it.

= version 3.7.1 =
 * Fix: fixed a bug with saving slice values.

= version 3.7 =
 * New: added new slice type "free gift" (only accessible when using WooCommerce).
 * Improvement: you can now leave an email message blank if you do not want it to send emails for a specific slice type.
 * Fix: fixed an issue with export reports not generating.

= version 3.6.7 =
 * Fix: fixed a bug where adding a checkbox field was not possible.
 * Other: for developers: added a filter to change the output from export.

= version 3.6.6 =
 * Improvement: the email field can now also be repositioned in the form.

= version 3.6.5 =
 * Improvement: improved compatibility between block-based themes and the standalone (shortcode) wheel.

= version 3.6.4 =
 * New: new accessibility setting to close the wheel when ESC key is pressed.

= version 3.6.3 =
 * Improvement: improved accessibility for assistive technology across various sections of the wheel.

= version 3.6.2 =
 * Fix: fixed an issue with some logs not exporting all data.

= version 3.6.1 =
 * Fix: fixed a conflict when your site has plugins that use the library "Form-Field-Dependency".

= version 3.6 =
 * New: shortcode wheels now have a setting to allow people to play again after a delay of X days.
 * Improvement: no cookies are used now when using the shortcode as wheel.
 * Improvement: you couldn't deactivate shortcode wheels so now only popup wheels have the "active" checkbox in the backend.
 * Improvement: display the shortcode in the backend for easier reference.
 * Fix: fixed a bug where users could play again even if it wasn't allowed when "connected data tool" was set to "none".
 * Other: created a new guide on data collection, GDPR, and privacy and linked to it from various places in the plugin's backend.

= version 3.5.12 =
 * Improvement: improved security on the admin side.
 * Other: verify WP 6 and Woo 6.5 compatibility.

= version 3.5.11 =
 * New: allow MailerLite date fields in the form from.

= version 3.5.10 =
 * Fix: fixed SendInBlue API to fetch more than 10 list names.

= version 3.5.9 =
 * Improvement: allow changing capability for exporting CSV files.

= version 3.5.8 =
 * Fix: fixed functions relying on a different WP capability than "administrator" in admin (more).

= version 3.5.7 =
 * Fix: fixed functions relying on a different WP capability than "administrator" in admin.

= version 3.5.6 =
 * Improvement: improved security on the admin side.
 * Improvement: modernized code.
 * Other: removed Chatfuel from the list as they no longer offer the FB checkbox plugin.

= version 3.5.5 =
 * Improvement: removed the separate exports ("export plays" and "export optins") and replaced with one export.
 * Other: verified Woo 6.0 compatibility.

= version 3.5.4 =
 * Improvement: removed some outdated javascript making the resulting file smaller on your site.
 * Fix: fixed an issue with phone number masking when Gravity Forms is also active on the site.

= version 3.5.3 =
 * Improvement: added number fields to SendInBlue integration.
 * Fix: fixed a bug where the total chances percentage would be 200% (instead of 100) when editing the wheel.

= version 3.5.2 =
 * Update: better phone validation for the SendInBlue "SMS" field.
 * Fix: fixed database insertion error when prize text is too long.

= version 3.5.1 =
 * Update: added filter "wof_hide_html_in_logs" for developers.
 * Other: verify & updated WP & Woo version tags.

= version 3.5.0 =
 * Update: added a "delete wheel" confirmation dialog in the backend.
 * Fix: fixed an issue with mailchimp only returning 10 merge fields in the backend.
 * Fix: fixed mobile horizontal scroll styling issue with standalone wheels.
 * Other: updated minimum WooCommerce version to 3.4.0. We will ensure compatibility with older versions (>3.0) for 3 more releases.
 * Other: updated WP & Woo version tags.

= version 3.4.9 =
 * New: if your wheel limiting prizes, you can see how many prizes are claimed per slice in the "view statistics" dialog.

= version 3.4.8 =
 * Fix: fixed an issue with the "widget background color" option not always showing.
 * Fix: fixed an issue with coupon bar color settings not updating correctly.

= version 3.4.7 =
 * Fix: fixed a backend issue with WP editors not saving text.

= version 3.4.6 =
 * Fix: fixed an edge case issue in "winning" calculation when "global chance of winning" is zero.
 * Improvement: "global chance of winning" setting can now only take values from 0 to 100%.
 * Improvement: minor style change to the label next to a checkbox.

= version 3.4.5 = 
 * New: added an option so the popup can appear full screen.
 * New: new field types in WordPress form builder: textareas, select lists and number fields.
 * Improvement: a checkbox label now appears next to the checkbox instead of the other way around.
 * Fix: fixed the "winter wonderland" them that wasn't showing the bottom background.
 * Improvement: saves less data in the database when saving the wheel from the backend.
 * Improvement: removed outdated CSS (for browsers older than 5 years), resulting in a smaller CSS file for the frontend.
 * Improvement: removed reduntant javascript resulting in a smaller JS file for the frontend.
 * Improvement: allow HTML in form fields added to the WordPress, or Zapier form builder.
 * Update: for developers: added a CSS class "wof-played" when the wheel was played.
 * Update: for developers: added a CSS class "wof-won" when the wheel was played and the user won.

= version 3.4.2 =
 * Fix: fixed issue in the new SendInBlue API where users would be added without a list.

= version 3.4.1 =
 * Fix: fixed an issue with searching for product categories in the backend.
 * Update: verify new WP version compatibility.

= version 3.4.0 =
 * Update: SendInBlue Api v2 will be deprecated soon. This release contains options to use their active v3 of the API.
 * Fix: some minor fixes.

= version 3.3.9 =
 * Fix: fixed Mailchimp Birthday field type issues.

= version 3.3.8 =
 * New: ability to add more dynamic codes in the emails sent by our plugin.
 * Update: removed ChatChamp integration as their service has discontinued for a while now.
 * Fix: removed "has_filter" function where it wasn't necessary, effectively reducing code.

= version 3.3.7 =
 * New: new frontend hook so developers can edit form fields being created.
 * New: class and ID attributes to the form elements so they can be targeted with CSS or JS.
 * Fix: added "SameSite" attribute to cookies to comply with new browser features.
 * Other: updated minimum PHP version to 5.6. Backward compatibility guaranteed with 3 minor releases.

= version 3.3.6 =
 * Fix: fix WordPress database table not being created on first run.

= version 3.3.5 =
 * Fix: fixed a bug when editing your wheel and changing slices from a low number to a high number in the backend.
 * Fix: the wheel had tiny gap between slices, which was notable when all slices were light in color. This is now fixed.

= version 3.3.4 =
 * New: new options to show on WooCommerce pages: "order received" and "view order" page.
 * Fix: fixed an admin issue when uploading background images don't have a thumbnail.
 * Dev: added developer hook for the coupon bar.

= version 3.3.3 =
 * Fix: fixed an issue with Mailchimp groups not loading on 1st try when editing a wheel.
 * Fix: fixed some minor HTML changes.
 * Fix: fixed an issue with your wheel's email settings sometimes disappearing in the backend.

= version 3.3.2 =
 * Update: added segment ID (the number of the slice won/lost) to data sent to Zapier.
 * Update: verified Woo 4+ compatibility.
 * Fix: added "usage_count" to coupons. This should fix some coupons returning "expired" when they shouldn't.

= version 3.3.1=
 * New: some more hooks to extend WooCommerce generated coupons.
 * Update: better styling for using images inside slices.
 * Update: primary email field is now type "email" for better mobile keyboard support.
 * Fix: removed a PHP warning in admin screen.
 * Fix: removed a PHP warning in the admin when connecting to ConvertKit.
 * Fix: fixed an issue with the GetResponse API.

= version 3.3.0 =
 * Update: you can now add variations to "include products" or "exclude products" in the coupon settings.
 * Update: allow more HTML tags in certain settings.

= version 3.2.9 =
 * Update: better admin UI to create wheels with the shortcode.
 * Update: added a few more filters so developers can extend even more parts of the plugin.
 * Fix: fixed an issue where custom HTML prizes weren't saved to the database.
 * Fix: fixed an issue with the notification email not being sent if you don't fill out a message & subject.
 * Fix: fixed a filter to change the "from" email address.

= version 3.2.8 =
 * Fix: fixed an issue with some coupon bars.

= version 3.2.7 =
 * Fix: fixed a javascript error in backend.
 * Update: Add filters to IP checking so developers can extend it when needed.

= version 3.2.6 =
 * Fix: fixed issue where the coupon bar would show if you won a prize that is not a coupon.
 * Fix: fixed issue with fetching custom fields in ConvertKit.
 * Update: added min/max values to some admin settings.

= version 3.2.5 =
 * New: 2 new options: "hide on desktop" and "hide on tablet".
 * New: filters to edit data sent to Zapier.
 * Update: refactor frontend javascript and save 3.5kb in size.
 * Update: added Woo compatibility tags.


= version 3.2.4 =
 * New: new integration: Drip.
 * Fix: fixed a bug with list fields containing non-UTF8 characters.
 * Fix: Mailchimp's international phone number field now also supports proper masking.
 * Fix: Fix to display the wheel on product pages from a certain product category.

= version 3.2.3 =
 * Fix: fixed a bug where you couldn't select a widget type in the settings page.
 * Fix: fixed a bug in the settings page where you couldn't select suboptions of the "when to show" setting.
 * Fix: fixed a bug with ActiveCampaign email lists not containing custom fields.
 * Fix: WP_Error handling with all email marketing providers.

= version 3.2.2 =
 * New: new layout option to disable the wheel shadows.
 * New: new option to disable the wheel handles.
 * New: new widget: "mini wheel".
 * New: added filter to skip sending analytics from the wheel to your backend.
 This can be handy if you want to minimize admin-ajax traffic.
 * Fix: Fixed CSS in footer issue.
 * Fix: Fixed an issue where coupon bar wasn't available on all pages.
 * Fix: Fixed an issue with adding coupons automatically to the cart when it's empty.

= version 3.2.1 =
 * Fix: When editing your wheel, the custom colors disappeared (if any). This is now fixed.
 * Fix: fixed a bug with the background setting and custom uploaded backgrounds.
 * Fix: fixed a typo in the custom background CSS.
 * Update: improved performance of the admin. This is to be continued.
 * Update: replace unnecessary translation function _e() with echo.

= version 3.2.0 =
 * Fix: fixed a bug with the backend setting for a fixed cart coupon amount.
 * Fix: fixed a bug where email settings are shown in the backend when they shouldn't.
 * Fix: fixed a rare "flash of unstyled content" bug where the wheel would be visible a fraction of a second during page load.
 * Fix: fixed a bug with the "tick" sound.
 * Update: added some more useful error messages in the backend.
 * Update: better script & style loading on the frontend. It's more performant and keeps page sizes smaller.
 * Update: automatically disable the free plugin when it's still installed. This prevents a few possible errors.
 * New: the setting "when to show the wheel" is now multi-select so you can define more than 1 condition when to show the wheel.
 * New: you can now add up to 24 slices instead of 12.
 * New: more email settings. Each specific slice type now has an email setting.
 * New: possibility to send an email to yourself when someone turned the wheel.
 * New: possibility to show confetti pop when a player won.
 * New: you can now easily add your own backgrounds to the wheel.

= version 3.1.0 =
 * Update: added these Woo coupon settings: coupon type (fixed value or percentage), minimum spend, maximum spend, include/exclude for sales items.
 * Update: adding images to slices is now possible by including <img src="..."/> in the slice value.

= version 3.0.9 =
 * Fix: fixed a bug for finding Woo products with Arabic names.
 * Fix: converted all text in the admin dashboard to translateable strings.
 * Update: allow ID's to be added for 'on click' elements.

= version 3.0.8 =
 * Fix: fixed a bug with "is_product" function for WooCommerce.

= version 3.0.7 =
 * New: zip code support for Mailchimp.
 * Fix: fixed a bug for input masking of fields of type: phone, date and birthday.
 * Fix: fixed a bug with the "All product pages" setting.
 * Update: updated email feature.

= version 3.0.6 =
 * New: option for double opt-in in MailChimp.
 * New: SendInBlue support.
 * New: option to automatically add Woo coupon to cart.
 * New: Developer-friendly WooCommerce coupons.
 * Fixed: Compatibility for PHP 7.2.
 * Update: enhanced HTML emailing.

= version 3.0.5 =
 * Fix: fixed a bug with the 'click' sound.
 * Fix: fixed a bug with replays where a prize would be won but the segment shows 'no prize'.

= version 3.0.4 =
 * New: tick sound when playing (as an option).
 * New: new integration: Newsletter2Go.

= version 3.0.3 =
 * New: New tool: ChatFuel (for Facebook marketing).
 * Improvement: You can now edit the chances to 1/4th of a percentage.

= version 3.0.2 =
 * Fix: fixed a bug with special characters being replaced after updating.
 * Fix: fixed a bug with WooCommerce coupon shown on screen.
 * Fix: fixed a bug where some settings wouldn't update when clicking save.

= version 3.0.1 (major update - check your wheel & read upgrade notice) =

 * New: new design features. You can now design any theme you want!
 * New: ability to upload logo in the center of the wheel.
 * New: Remarkety integration.
 * New: Convertkit integration.
 * New: tools for GDPR compliance.
 * Fixed: several small backend UI fixes.
 * Fix: small fix with handling HTML in slices in the upgrade routine.

= version 2.1.2 =
 * Fix: fix for coupons handed out with a life span of less than 1 hour.
 * Fix: fixed an issue with incorrectly identifying the woo shop page.

= version 2.1.1 =
 * Fix: fixed an issue in rare cases where winning segments would return null.

= version 2.1.0 (major release, verify wheels after updating) =
 * Fix: various small bugfixes and enhancements in the admin dashboard.
 * New: Facebook Messenger support through ChatChamp.
 * New: security option to log IP addresses for better security.
 * New: better logging options.
 * New: support for Mailster.
 * Fix: bugfix for Klaviyo.

= version 2.0.8 =
 * Bugfix: fixed a bug for standalone wheels.

= version 2.0.7 =
 * New: added a 'consent checkbox' field in the form builder for GDPR.
 * New: added a new occurance option 'none', so you can only show widgets, or program your own occurance.
 * Fix: fixed a bug with 'show on pages' setting in the backend.

= version 2.0.6 =
 * New: support for Klaviyo.
 * New: limit prizes: allow prizes to be won only X amount of times.
 * Fix: fixed an issue with converting string to int.

= version 2.0.5 =
 * New: Free shipping option.
 * New: Show the wheel only for logged in or logged out users.
 * Update: Woo coupon percentage is now a number field instead of a dropdown (so more options for you).
 * Fix: fixed coupon bar issue
 * Fix: fixed issue with the 'show on pages' setting in the backend.
 * Update: added some more developer hooks.

= version 2.0.4 =
 * Fixed: fix in Christmas theme.

= version 2.0.3 =
 * New: new slice type: Text/HTML.
 * Updated: more developer-friendliness.

= version 2.0.2 =
 * New: WooCommerce coupon bar for urgency.
 * Fixed: Minor MailChimp bugfix.

= version 2.0.1 =
 * Fix: backward compatibility bug.

= version 2.0.0 (WARNING: big update - test & verify) =
 * New: 6 new themes.
 * New: 8 new backgrounds.
 * Update: updated UI so it's more user-friendly to add a wheel.
 * Update: your design changes are visible in a realtime live-preview.
 * Update: Formbuilder supports dropdown fields for all autoresponders.
 * Update: Mailchimp formbuilder supports text, dropdown, date, birthday, number and phone number types.
 * New: GetResponse integration.
 * New: MailerLite integration.
 * New: Zero BS CRM integration (via separate connector).
 * New: WordPress database integration. Collect opt-ins straight to your own database.
 * New: AWeber integration (via separate connector).
 * Update: ability to let users play without filling out an opt-in form (handy if you want to give prizes only).
 * Update: allow duplicate plays (users can play again, even when they already won).
 * Update: Updated form builder.
 * New: added 'bubble' and 'pull-out' widgets ( = clickable buttons ) to open a wheel.
 * New: new type of slice: redirect. Redirect users to a page, rather than show a prize.
 * Update: made the plugin expandable and developer-friendly.
 * Fix: fixed a bug in email templates.
 * Fix: some small theme CSS fixes.
 * And more ...

= version 1.2.6 =
 * Fix: fixed a bug with multiple excluded categories in Woo coupons.
 * Update: extension abilities for developers.
 * Update: UI changes.

= version 1.2.5 =
 * New: winter/christmas theme.
 * Fixed: various small bugfixes.

= version 1.2.4 =
 * [Version release notes](https://studiowombat.com/wp-optin-wheel-1-2-4-release-notes/)
 * New: anti-cheat engine.
 * Update: added exit-intent alike behavior on mobile.

= version 1.2.3 =
 * New: support for MailChimp's birthday field.
 * Update: added an upper-right close icon, in favor of the lower right closing sentence.

= version 1.2.2 =
 * Update: include RTL language support.
 * Update: improved email sending behind the scenes.
 * Fix: fixed a bug with Javascript events.

= version 1.2.1 =
 * Added javascript events to hook into.
 * Allow some HTML in the segment label.
 * Added product categories to 'show on pages' option.

= version 1.2.0 =
 * Added ability to send coupon codes via email as opposed to showing on screen.
 * Added webhooks support for Zapier etc.

= version 1.1.9 =
 * Added Mailchimp groups.

= version 1.1.8 =
 * Added more content/design settings.
 * Added ability to show the wheel on every page refresh.

= version 1.1.7 =
 * Mobile UI fixes.
 * Bugfix in CSS of the standalone wheel.

= version 1.1.6 =
 * Added extra coupon options for WooCommerce.
 * Bugfix in iOS Safari 11.

= version 1.1.5 =
 * Added extra coupon options.
 * Enhanced iOS experience.
 * Small UI improvements on mobile.

= version 1.1.4 =
 * New: added a new theme: Halloween.
 * New: added a new theme: Black & White.
 * Fix: better 'ticking' animation when the wheel is turning.
 * Update: minor changes to the view on mobile.

= version 1.1.3 =
 * New: added form builder. You can now add more than just an email field to the game's form.
 * New: added demo content to get you started faster.
 * New: UI improvements on the admin page.
 * Fix: Fixed an error for Mac users.
 * Fix: Fixed a bug with the email list dropdown field on the admin page.

= version 1.1.2 =
 * New: added WPML compatiblity.

= version 1.1.1 =
 * Fix: small bugfix for WP versions before 4.7.

= version 1.1.0 =
 * New: added a shortcode [wof_wheel] so you can display the game on any page.

= version 1.0.9 =
 * Fix: Added a noop for window.waitForFinalEvent as some plugins may interfere with tinymce in the admin.

= version 1.0.8 =
 * Fix: CSS changes in the admin screen to support older websites.

= version 1.0.7 =
 * New: added 2 new pattern backgrounds.
 * New: you can now change the background- and textcolor of the theme.

= version 1.0.6 =
 * Fix: fixed a bug with saving the Woo coupon setting.

= version 1.0.5 =
 * New: added action hooks so developers can extend the plugin.

= version 1.0.4 =
 * Fix: the 'email placeholder' content field wasn't saving.

= version 1.0.3 =
 * Update: verified WP 4.8.2 compatibility.
 * New: added WooCommerce integration.
 * New: added log file (via a setting).

= version 1.0.2 =
 * Update: allow visitors to try again if they lost.
 * New: added support for ActiveCampaign.

= version 1.0.1 =
 * New: added support for Campaign Monitor

= version 1.0.0 =
 * Initial version