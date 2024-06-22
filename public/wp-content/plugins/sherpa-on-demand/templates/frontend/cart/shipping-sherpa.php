<?php

$confObj = new Sherpa_Configurations();

$showSherpa = $showSherpaError = false;
$sherpa_delivery_operating_day = get_option('sherpa_delivery_settings_operating_day', '1, 2, 3, 4, 5');
$all_days = array(0, 1, 2, 3, 4, 5, 6);
$operating_days_array = array_map('trim', explode(',', $sherpa_delivery_operating_day));
$operating_days = array();

foreach ($operating_days_array as $operating_day) {
    if ($operating_day == 7) {
        $operating_day = 0;
    }
    $operating_days[] = $operating_day;
}

$disabled_dates = array_diff($all_days, $operating_days);
$comma_separated_disabled_dates = implode(",", $disabled_dates);
$current_time = new DateTime('now', new DateTimeZone(wp_timezone_string()));

$sherpa_methods = array();
foreach ($available_methods as $key => $method) {
    if ($method->method_id == 'sherpa') {
        $sherpa_methods[] = $method;

        if ($method->id == 'sherpa_error') {
            $confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'available key -> ' . print_r($key, true));
            $showSherpaError = true;
            $error = $method->label;
            unset($available_methods[$key]);
        } else {
            $showSherpa = true;
        }
    }
}

$next_available = '';
$today_date = $current_time->format('Y-m-d');
$next_date = $today_date;

// Check availability for next 100 days
$index = 0;
while ($index < 100 && !$next_available) {
    $next_date = date('Y-m-d', strtotime($next_date . ' +1 day'));
    $dotw = (int) date('w', strtotime($next_date));
    if (!in_array($dotw, $disabled_dates)) {
        $next_available = $next_date;
        break;
    }

    $index++;
}

$confObj->getLogger()->add(Sherpa_Sherpa::LOG_FILE_NAME, 'Available methods -> ' . print_r($available_methods, true));

// AU state?
function check_if_au_state($state) {
    return (
        (isset($_REQUEST['country']) && 'AU' === $_REQUEST['country'])
        && (isset($_REQUEST['state']) && $state === $_REQUEST['state']));
}
?>
<?php if ($showSherpaError) : ?>
    <li>
        <ul>
            <li><?php echo $confObj->getTitle(); ?></li>
            <li class="woocommerce-error"><?php echo $error; ?></li>
        </ul>
    </li>
<?php endif; ?>

<?php

if ($showSherpa) :

    // Post data
    $output = array();
    if (isset($_REQUEST['post_data'])) {
        $str = $_REQUEST['post_data'];
        parse_str($str, $output);
    }

    $has_sherpa_methods = false;
    $commonObj = new Sherpa_Common($confObj);

    $sherpaReadyAtDate = WC()->session->get('sherpa_ready_at_date', NULL)
        ? WC()->session->get('sherpa_ready_at_date', NULL) : $next_available;
    $dateObj = new DateTime($sherpaReadyAtDate, new DateTimeZone(wp_timezone_string()));
    $sherpaReadyAtDateFormatted = $dateObj->format('Y-m-d');

    $selected_method = $commonObj->getViewSelectedMethod(WC()->session->get('sherpa_selected_method', 'service_sameday'));
    $shipping_date = $commonObj->getViewReadyAtDate($sherpaReadyAtDate, $selected_method);
    $selected_method_option = WC()->session->get('sherpa_selected_method_option');
    $chosen_method = WC()->session->get('sherpa_chosen_shipping_methods', false);
    $chosen_method = empty($chosen_method) ? (isset($sherpa_methods[0]->id) ? $sherpa_methods[0]->id : false) : $chosen_method[0];

    if (isset($output['sherpa_estimate_method_select']) && false === strpos($chosen_method, $output['sherpa_estimate_method_select'])) {
        $chosen_method = $sherpa_methods[0]->id;
    }

    $enabled_services = $commonObj->getEnabledServices();
    if (!isset($enabled_services[$chosen_method]) || !$enabled_services[$chosen_method]) {
        foreach ($enabled_services as $enabled_service_key => $snabled_service_value) {
            if (strpos($enabled_service_key, $selected_method) !== false) {
                $chosen_method = $enabled_service_key;
                break;
            }
        }
    }

    //print_r($enabled_services);
    if ($selected_method == 'service_sameday') {
        // $sherpaReadyAtDate = '';
    }

    if (empty($selected_method)) {
        $selected_method = 'service_sameday';
    }
?>
    <?php if ($methods = (array) $commonObj->getAllowedMethods()) : ?>
        <li class="sherpa-shipping-method sherpa-delivery-options" style="text-align:left">
            <ul>
                <li class="title"><?php echo $confObj->getTitle(); ?></li>
                <li>
					<?php 
						$con = in_array('service_sameday', $methods) && in_array('service_later', $methods);
						if($con){ echo '<label class="datepicker-label" for="datepicker"><?php echo __(\'When?\', \'sherpa\') ?></label>'; }
					?>
					<select name="sherpa_estimate_method_select" id="sherpa-methods" <?php if(!$con){ echo 'style="display:none"'; } ?> >
						<?php foreach ($methods as $method_name) : ?>
							<option value="<?php echo $method_name; ?>" <?php selected($selected_method, $method_name, true); ?>>
							<?php echo $commonObj->getMethodTitle($method_name) ?>
							</option>
						<?php endforeach; ?>
					</select>
                </li>
                <li id="sherpa-datepicker" <?php if(!(in_array('service_later', $methods))){ echo 'style="display:none"'; } ?>>
                    <input type="text" class="datepicker" name="sherpa_ready_at" value="<?php echo $shipping_date ?>" placeholder="Pick a date" />
                </li>
                <?php foreach ($available_methods as $index => $method) :  ?>
                    <?php

                    // Not a Sherpa method?
                    if ($method->method_id !== 'sherpa') {
                        continue;
                    }

                    // Check if enabled?
                    if (!$enabled_services || !isset($enabled_services[$method->id]) || !$enabled_services[$method->id]) {
                        continue;
                    }

                    $method_id = $method->id;
                    $service_options = $commonObj->getServiceOptions($method->id, $sherpaReadyAtDate);

                    // Skip later methods on sameday
                    if ($selected_method == 'service_sameday' && strpos($method_id, 'service_later_') !== false) {
                        continue;
                    }

                    /**
                     * Delivery Rates = Sherpa Rate or Margin,
                     * the bulk rate should not display on the checkout page.
                     */
                    if (in_array($method->id, [
                        'service_later_service_bulk_rate',
                        'service_sameday_service_bulk_rate',
                    ]) && 'FL' !== $confObj->getData('delivery_rates')) {
                        continue;
                    }

                    $service_windows = array();
                    $meta_data = $method->get_meta_data();
                    $meta_windows = (array) $meta_data['windows']; // convert object to array
                    $meta_data_windows = isset($meta_data['windows']) ? reset($meta_windows) : []; // gets first element of object array                    $meta_data_windows = isset($meta_data['windows']) ? reset($meta_data['windows']) : []; // gets first element of object array
                    $meta_opening_time = $meta_data_windows[0]; // gets first delivery window
                    $meta_data_windows = explode('-', $meta_data_windows[0] );
                    $meta_windows_from = $meta_data_windows[0]; // gets window opening time

                    
                    $windows_timeslots = isset($meta_data['windows']) ? (array) $meta_data['windows'] : [];

                    // Fetch shop opening time
                    $conf = new Sherpa_Configurations();
                    $operating_time_value = $conf->getOperatingTimeWrapper();
                    $operating_time = explode(', ', $operating_time_value);
                    $operating_time_from = $operating_time[0];

                    // Flag to check if window opening time is same as shop opening time
                    $deduct_15_minutes = (str_replace(' ', '', $meta_windows_from) == str_replace(' ', '', $operating_time_from)) ? false : true;
                    //print_r($windows_timeslots);
                    // Windows timeslots?
                    foreach ($windows_timeslots as $windows) {
                        if (is_array($windows)) {
                            foreach ($windows as $window) {
                                $window_array = $window ? explode(' - ', $window) : array();
                                $start_window = isset($window_array[0]) ? $window_array[0] : '';
                                $end_window = isset($window_array[1]) ? $window_array[1] : '';

                                // Check?
                                if ($start_window && $end_window) {
                                      if (check_if_au_state('NSW') && 1 === (int) $confObj->getData('contains_alcohol') && 'service_sameday' === $selected_method) {
                                        if(get_option('sherpa_delivery_settings_prep_time') == 'NP'  
                                        && $selected_method == "service_sameday"
                                        && $method_id != "service_sameday_service_bulk_rate"
                                        && $method_id != "service_sameday_service_at"){
                                          $no_prep_start_window = date('H:i', strtotime($start_window));
                                          $no_prep_end_window = date('H:i', strtotime($end_window));
                                          $no_prep_start_window = ($deduct_15_minutes) ? strtotime("-15 minutes", strtotime($start_window)) : strtotime($start_window);
                                          $no_prep_end_window = ($deduct_15_minutes) ? strtotime("-15 minutes", strtotime($end_window)) : strtotime($end_window);

                                          $start_window = date("H:i", $no_prep_start_window);
                                          $end_window = date("H:i", $no_prep_end_window);
                                        }
                                        $start_window_parts = explode(':', $start_window);
                                        $end_window_parts = explode(':', $end_window);
                                        if ((int) $start_window_parts[0] >= 9 && (int) $end_window_parts[0] <= 23) {
                                            if (isset($service_options[$start_window]) || 
                                                'service_sameday_service_1hr' == $method_id ||
                                                'service_sameday_service_2hr' == $method_id ||
                                                'service_sameday_service_4hr' == $method_id ||
                                                'service_sameday_service_at' == $method_id || 
                                                'service_later_service_at' == $method_id || 
                                                'service_sameday_service_bulk_rate' == $method_id || 
                                                'service_later_service_bulk_rate' == $method_id) {
                                                $service_windows[$start_window] = implode(' - ', array(
                                                    date('g:i a', strtotime($start_window)),
                                                    date('g:i a', strtotime($end_window)),
                                                ));
                                            }
                                        }
                                    } else {
                                        if(get_option('sherpa_delivery_settings_prep_time') == 'NP' 
                                        && $selected_method == "service_sameday" 
                                        && $method_id != "service_sameday_service_bulk_rate"
                                        && $method_id != "service_sameday_service_at"){
                                          $no_prep_start_window = date('H:i', strtotime($start_window));
                                          $no_prep_end_window = date('H:i', strtotime($end_window));
                                          $no_prep_start_window = ($deduct_15_minutes) ? strtotime("-15 minutes", strtotime($start_window)) : strtotime($start_window);
                                          $no_prep_end_window =($deduct_15_minutes) ? strtotime("-15 minutes", strtotime($end_window)) : strtotime($end_window);

                                          $start_window = date("H:i", $no_prep_start_window);
                                          $end_window = date("H:i", $no_prep_end_window);
                                        }
                                        if (isset($service_options[$start_window]) ||
                                           'service_sameday_service_1hr' == $method_id ||
                                           'service_sameday_service_2hr' == $method_id ||
                                           'service_sameday_service_4hr' == $method_id ||
                                           'service_sameday_service_at' == $method_id ||
                                           'service_later_service_at' == $method_id ||
                                           'service_sameday_service_bulk_rate' == $method_id ||
                                           'service_later_service_bulk_rate' == $method_id) {
                                            $service_windows[$start_window] = implode(' - ', array(
                                                date('g:i a', strtotime($start_window)),
                                                date('g:i a', strtotime($end_window)),
                                            ));
                                          
                                        }
                                    }
                                }
                            }
                        }
                    }

                    ?>

                    <?php if (count($service_windows) > 0) : ?>
                        <li class="sherpa-window-slot">
                            <?php

                            $has_sherpa_methods = true;

                            printf(
                                '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method sherpa" %4$s /><label for="shipping_method_%1$d_%2$s">%5$s</label>',
                                $index,
                                sanitize_title($method->id),
                                esc_attr($method->id),
                                checked($method->id, $chosen_method, false),
                                wc_cart_totals_shipping_method_label($method)
                            );

                            do_action('woocommerce_after_shipping_rate', $method, $index);

                            ?>

                            <?php if (!in_array(Sherpa_Sherpa::OPTION_SERVICE_ANYTIME, $service_windows)) : ?>
                              <?php
                                $sherpa_settings = get_option('woocommerce_sherpa_settings');
                                $show_timselots = ($selected_method=='service_sameday' && !$sherpa_settings['show_timeslot_checkbox']) ? false : true;
                              ?>
                                <div id="list-shipping_method_0_<?php echo $method->id ?>" style="<?php echo ($method->id === $chosen_method) ? 'display: block;' : 'display: none;' ?>" class="list-method-options">
                                  <div style="display: <?php echo ($show_timselots)? 'block' : 'none'; ?> ;">
                                    <p style="margin-bottom: 0;">
                                        <small><em>Please select a time</em></small>
                                    </p>
                                    <select name="sherpa_method_option_<?php echo $method->id ?>" <?php echo empty($selected_method_option) ? ' class="sherpa-select-error"' : ''; ?>>
                                      <?php if($show_timselots){  ?>
                                        <option value="">Select a time</option>
                                      <?php } ?>
                                        <?php foreach ($service_windows as $value => $label) : ?>
                                            <option value="<?php echo $value ?>" <?php echo ($selected_method_option == $value) ? 'selected' : '' ?>><?php echo $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                  </div>
                                </div>
                                <script type="text/javascript">
                                    jQuery('[name=sherpa_method_option_<?php echo $method->id ?>]').on('change', function(e) {
                                        e.preventDefault();
                                        var selected_option = jQuery('[name=sherpa_method_option_<?php echo $method->id ?>] option:selected').val();
                                        jQuery('[name="sherpa_delivery_time_plain_text"]').val(
                                            jQuery('[name=sherpa_method_option_<?php echo $method->id ?>] option:selected').text()
                                        );
                                        <?php if (current_action() == 'wc_ajax_update_order_review') : ?>
                                            jQuery('.shipping_method').change();
                                        <?php else : ?>
                                            trigger_sherpa_selected_method_option(selected_option);
                                        <?php endif; ?>
                                    });
                                </script>
                            <?php endif; ?>

                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <script type="text/javascript">
                function updateDelivetyTimePlainText() {
                    jQuery('input.shipping_method.sherpa').each(function(i, elm) {
                        if (jQuery(elm).prop('checked') && jQuery(elm).val()) {
                            var mOption = jQuery('select[name="sherpa_method_option_' + jQuery(elm).val() + '"]');
                            if (mOption.length) {
                                jQuery('[name="sherpa_delivery_time_plain_text"]').val(mOption.find(":selected").text());
                            }
                        }
                    });
                }

                updateDelivetyTimePlainText();

                jQuery('input.shipping_method.sherpa').change(function(e) {
                    jQuery(document.body).trigger('update_checkout');
                });

                function trigger_update_checkout() {
                    wc_checkout_form.reset_update_checkout_timer();
                    wc_checkout_form.dirtyInput = false;
                    jQuery(document.body).trigger('update_checkout');
                };

                function trigger_sherpa_selected_method_option(selected_option) {
                    var shipping_methods = [];

                    jQuery('select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]').each(function() {
                        shipping_methods[jQuery(this).data('index')] = jQuery(this).val();
                    });

                    jQuery('div.cart_totals').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });

                    if (shipping_methods[0].indexOf('service_later') != '-1') {
                        var sherpa_ready_at = jQuery('[name="sherpa_ready_at"]').val();
                    } else {
                        var sherpa_ready_at = '';
                    }

                    var data = {
                        security: wc_cart_params.update_shipping_method_nonce,
                        shipping_method: shipping_methods,
                        sherpa_selected_method_option: selected_option,
                        sherpa_ready_at: sherpa_ready_at,
                        sherpa_prefer_ready_at: sherpa_ready_at
                    };

                    jQuery.post(
                        wc_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'update_shipping_method'), data,
                        function(response) {
                            jQuery('div.cart_totals').replaceWith(response);
                            jQuery(document.body).trigger('updated_shipping_method');
                        });
                };

                jQuery(document).ready(function() {
                    var coShippingMethodForm = jQuery('.woocommerce-shipping-calculator')[0];

                    jQuery("input.shipping_method").on('change', function(event) {
                        if (jQuery(this).hasClass('sherpa')) {
                            var method_name = jQuery(this).attr('id');
                            var shippingMethodValue = jQuery(this).val();
                            if (jQuery("#list-" + method_name).length) {
                                if (jQuery(this).is(':checked')) {
                                    jQuery(".list-method-options").hide();
                                    jQuery("#list-" + method_name).show();

                                    // @todo if count options == 1 , then don't prevent
                                    event.preventDefault();
                                    event.stopPropagation();
                                    return false;
                                }
                            }
                        }
                    });

                    jQuery('input.datepicker').Zebra_DatePicker({
                        direction: true,
                        <?php if ($comma_separated_disabled_dates) { ?>
                            disabled_dates: ['* * * <?php echo $comma_separated_disabled_dates; ?>'],
                        <?php } ?>
                        direction: ['<?php echo $next_available ?>', false],
                        onSelect: function(date, date_default, dateObj, element) {
                            jQuery('li.sherpa-window-slot').show();
                            jQuery('.woocommerce-shipping-calculator #sherpa_ready_at, #order_review #sherpa_ready_at').val(date);
                            jQuery('.woocommerce-shipping-calculator #sherpa_selected_method, #order_review #sherpa_selected_method').val(jQuery('#sherpa-methods option:selected').val());
                            <?php if (current_action() == 'wc_ajax_update_order_review') : ?>
                                jQuery('.shipping_method').change();
                                jQuery('.shipping_method').first().trigger('click');
                                jQuery(document.body).trigger('update_checkout');
                            <?php else : ?>
                                coShippingMethodForm.submit();
                            <?php endif; ?>
                        },
                        onClear: function() {
                            jQuery('li.sherpa-window-slot').hide();
                            jQuery('p#no-sherpa-methods').hide();
                        },
                        onChange: function(view, elements) {}
                    });

                    // initialize fields
                    var selected_option = jQuery('#sherpa-methods option:selected').val();
                    if (selected_option && selected_option == 'service_sameday') {
                      jQuery('#sherpa-datepicker').hide();
                    }
                    if (selected_option && selected_option == 'service_later') {
                        jQuery('#sherpa-datepicker').show();
                        jQuery('p#no-sherpa-methods').hide();
                    } else {
                        jQuery('#sherpa-datepicker').hide();
						// if(jQuery('#sherpa-methods > option').length == 1){
						// 	if(!<?php echo $has_sherpa_methods ?>){
						// 		jQuery('.sherpa-shipping-method').hide();
						// 		//if only sherpa method enable.
						// 		if(jQuery('.woocommerce-shipping-methods').length == 1){
						// 			jQuery( '<p>There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.</p>' ).insertAfter( ".sherpa-shipping-method" ); 
						// 		}
						// 	}
							 
						// }
                    }

                    // show hide on change
                    jQuery('#sherpa-methods').change(function(e) {
                        var selected_option = this.value;
                        jQuery('.woocommerce-shipping-calculator #sherpa_selected_method,#order_review #sherpa_selected_method').val(selected_option);

                        if (selected_option == 'service_later') {
                            jQuery('#sherpa-datepicker').show();
                            jQuery('[name="sherpa_ready_at"]').val('');
                            jQuery('li.sherpa-window-slot').hide();
                            jQuery('p#no-sherpa-methods').hide();
                            jQuery('.woocommerce-shipping-calculator #sherpa_ready_at, #order_review #sherpa_ready_at')
                                .val('<?php echo $shipping_date ?>');
                            jQuery('.woocommerce-shipping-calculator #sherpa_current_time, #order_review #sherpa_current_time')
                                .val('<?php echo $current_time->format('Y-m-d h:i:s') ?>');
                        } else {
                            jQuery('.woocommerce-shipping-calculator #sherpa_current_time, #order_review #sherpa_current_time')
                                .val('<?php echo $current_time->format('Y-m-d h:i:s') ?>');

                            // hide datepicker
                            jQuery('#sherpa-datepicker').hide();

                            // reset values
                            jQuery('.woocommerce-shipping-calculator #sherpa_ready_at, #order_review #sherpa_ready_at')
                                .val('');

                            // submit form again
                            <?php if (current_action() == 'wc_ajax_update_order_review') : ?>
                                jQuery('.shipping_method').change();
                                jQuery(document.body).trigger('update_checkout');
                            <?php else : ?>
                                coShippingMethodForm.submit();
                            <?php endif; ?>
                        }
                    });

                    // invoke ajax request if there is only one option in shipping time dropdown
                    jQuery("ul#shipping_method ul li input[type=radio]").on('click', function() {
                        var optionSel = jQuery(this).parent().find('.list-method-options select option');
                        var getSelect = jQuery(this).parent().find('.list-method-options select');
                        var optionCount = optionSel.size();
                        if (optionCount == 1) {
                            optionSel.change();
                        }

                        // selecting first value if nothing is selected
                        var selectedValue = getSelect.val();
                        var firstValue = getSelect.find('option:first').val();

                        if (selectedValue == firstValue) {
                            getSelect.find('option:first').change();
                        }
                    });

                    // set initial values
                    jQuery('.woocommerce-shipping-calculator #sherpa_ready_at, #order_review #sherpa_ready_at')
                        .val('<?php echo $sherpaReadyAtDateFormatted; ?>');
                    jQuery('.woocommerce-shipping-calculator #sherpa_selected_method, #order_review #sherpa_selected_method')
                        .val('<?php echo $selected_method; ?>');
                    jQuery('.woocommerce-shipping-calculator #sherpa_current_time, #order_review #sherpa_current_time')
                        .val('<?php echo $current_time->format('Y-m-d h:i:s') ?>');
                    jQuery('.woocommerce-shipping-calculator #sherpa_delivery_time, #order_review #sherpa_delivery_time')
                        .val('<?php echo $selected_method_option; ?>');
                });
            </script>

            <?php
            if ('service_later' == $selected_method) {
                if ($current_time->format('Y-m-d') == $sherpaReadyAtDateFormatted) {
                    echo '<script>jQuery(\'[name="sherpa_ready_at"]\').val(\'\'); jQuery(\'.sherpa-window-slot\').hide();</script>';
                } elseif (empty($has_sherpa_methods)) {
                    //When no methods selected for later and shop is closed for today
                    echo '<p id="no-sherpa-methods" class="text-medium">' . __('Delivery today no longer available', 'sherpa') . '</p>';
                }
            }

            if ('service_sameday' == $selected_method && empty($has_sherpa_methods)) {
                echo '<p id="no-sherpa-methods" class="text-medium">' . __('Delivery today no longer available', 'sherpa') . '</p>';
            }
            ?>
        </li>
    <?php endif; ?>
<?php endif; ?>
