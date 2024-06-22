jQuery(document).ready(function () {
    if (jQuery("#sherpaApiForm").length) {
        jQuery('[data-toggle="tooltip"]').tooltip();

        delOptionMethodAdmin(jQuery("#del_options"));
        jQuery("#del_options").on("change", function () {
            delOptionMethodAdmin(jQuery(this));
        });

        ratesMethodAdmin(jQuery("#sel_rates_method"));
        jQuery("#sel_rates_method").on("change", function () {
            ratesMethodAdmin(jQuery(this));
        });

        //Time Selector Dropdown

        jQuery('#operating_time_selector_from, #operating_time_selector_to').on('change',function() {
            var hiddenOperatingTimeWrapper = jQuery('#sherpa_sherpa_delivery_settings_operating_time_wrapper');
            var timeWindow = jQuery('#operating_time_selector_from').val()+', '+jQuery('#operating_time_selector_to').val(); //
            hiddenOperatingTimeWrapper.val(timeWindow);
        });

        jQuery('#operating_time_selector_to').on('change', function(){
            var prepTimeValues = {'NP' : 0, '30M' : 0.5, '1H' : 1, '2H' : 2, '3H' : 3, '4H' : 4 };
            var prepTimeInHour = prepTimeValues[jQuery('#prep_time').val()];
            var closingTime = jQuery('#operating_time_selector_to').val();

            let closingHours, closingMinutes;
            [closingHours,closingMinutes] = closingTime.split(':');

            let closingNumber = parseInt(closingHours) + (parseInt(closingMinutes) / 60.0); //converts Time(HH:MM) to int-float(HH.MM) to perform calculations
            
            let cutoffNumber = closingNumber - prepTimeInHour; //cutoff calculation with float values
            var cutoffHour = Math.floor(cutoffNumber);
            var cutoffMinutes = Math.ceil((cutoffNumber % 1) * 60);

            var hiddenCutoffTimeField = jQuery('#sherpa_sherpa_delivery_settings_cutoff_time');
            hiddenCutoffTimeField.val(String(cutoffHour+':'+String(cutoffMinutes).padStart(2, '0'))); // Renders Cutoff time in acceptable format
        });

        //--/Time Selector Dropdown

        // setting default values of delivery options
        if (jQuery("#sherpa_settings_sameday_delivery_options_sameday").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_sameday").val("Today");
        }

        if (jQuery("#sherpa_settings_sameday_delivery_options_service_1hr").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_service_1hr").val("1 hour delivery");
        }

        if (jQuery("#sherpa_settings_sameday_delivery_options_service_2hr").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_service_2hr").val("2 hour delivery");
        }

        if (jQuery("#sherpa_settings_sameday_delivery_options_service_4hr").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_service_4hr").val("4 hour delivery");
        }

        if (jQuery("#sherpa_settings_sameday_delivery_options_service_at").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_service_at").val("Same day delivery");
        }

        if (jQuery("#sherpa_settings_sameday_delivery_options_service_bulk_rate").val()?.length == 0) {
            jQuery("#sherpa_settings_sameday_delivery_options_service_bulk_rate").val("Bulk rate delivery");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_later").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_later").val("Schedule for later");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_service_1hr").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_service_1hr").val("1 hour delivery");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_service_2hr").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_service_2hr").val("2 hour delivery");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_service_4hr").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_service_4hr").val("4 hour delivery");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_service_at").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_service_at").val("Same day delivery");
        }

        if (jQuery("#sherpa_settings_later_delivery_options_service_bulk_rate").val()?.length == 0) {
            jQuery("#sherpa_settings_later_delivery_options_service_bulk_rate").val("Bulk rate delivery");
        }


        jQuery(".slider-time").html(slider_time1);
        jQuery(".slider-time2").html(slider_time2);


        // initial values
        var event = {};
        var initValues = {
            values: [operating_time1, operating_time2],
        };

        jQuery("#sherpa_sherpa_delivery_settings_operating_time_wrapper").val(
            operating_time_value
        );
        jQuery(
            "#sherpa_sherpa_delivery_settings_cutoff_time option:eq(" +
                cutoff_time +
                ")"
        ).prop("selected", true);

        // Save settings
        jQuery(document).on("click", ".sherpaSettings", function (e) {
            e.preventDefault();

            jQuery(".errorMessage").html("");
            jQuery(".apiErrorMessage").html("");
            jQuery(".successMessage").html("");
            var data = jQuery(sherpaApiForm).serialize();

            var error = 0;
            if (
                jQuery.trim(jQuery("#sherpa_credentials_account").val()) == ""
            ) {
                error = 1;
                jQuery("#username_validate").html(
                    "Please enter our Sherpa Username."
                );
                jQuery("#username").focus();
                return false;
            }

            if (
                jQuery.trim(jQuery("#sherpa_credentials_password").val()) == ""
            ) {
                error = 1;
                jQuery("#password_validate").html(
                    "Please enter your Sherpa Password."
                );
                jQuery("#password").focus();
                return false;
            }

            if (error == 2) {
                return false;
            }

            if (error == 1) {
                alert("Please fill all the fields.");
                return false;
            } else {
                jQuery(".loaderImage").show();
                jQuery.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: data,
                    dataType: "json",
                    success: function (data) {
                        jQuery(".loaderImage").hide();
                        if (typeof data.message != "undefined") {
                            jQuery("#responseMgs").html(data.message);
                            if (data.class == "errorMessage") {
                                jQuery("#responseMgs").removeClass(
                                    "successMessage"
                                );
                            } else {
                                jQuery("#responseMgs").removeClass(
                                    "errorMessage"
                                );
                            }

                            jQuery("#responseMgs").addClass(data.class);
                            jQuery("#responseMgs").focus();
                        }
                        // location.reload();
                    },
                    error: function (textStatus, errorThrown) {},
                });
            }
        });
        // Save API settings
        jQuery(document).on("click", ".sherpaApiTestings", function (e) {
            e.preventDefault();
            jQuery(".errorMessage").html("");
            jQuery(".apiErrorMessage").html("");
            jQuery(".successMessage").html("");

            var error = 0;
            var username = jQuery("#sherpa_credentials_account").val();
            var password = jQuery("#sherpa_credentials_password").val();

            if (jQuery.trim(username) == "") {
                error = 1;
                jQuery("#username_validate").html(
                    "Please enter your Sherpa Username."
                );
                jQuery("#username").focus();
                return false;
            }

            if (jQuery.trim(password) == "") {
                error = 1;
                jQuery("#password_validate").html(
                    "Please enter your Sherpa Password."
                );
                jQuery("#password").focus();
                return false;
            }

            if (error == 1) {
                alert("Please fill all the fields.");
                return false;
            } else {
                jQuery(".apiLoaderImage").show();
                // @todo call api for authentication

                let data = jQuery(sherpaAccountForm).serializeArray();
                data.push({
                    name: "button",
                    value: jQuery(this).attr("value"),
                });

                jQuery.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: data,
                    dataType: "json",
                    success: function (data) {
                        jQuery(".apiLoaderImage").hide();
                        jQuery("#sherpa-messages").html(
                            '<ul class="messages"><li class="' +
                                data.alert_class +
                                '"><ul><li><span>' +
                                data.message +
                                "</span></li></ul></li></ul>"
                        );
                        // location.reload();
                    },
                    error: function (textStatus, errorThrown, a) {
                        //
                    },
                });
            }
        });

        // Schedule of later options
        jQuery(document).on(
            "click",
            ".saveDelOpts.saveDelOptsLater",
            function () {
                var error = 0;
                jQuery(".errorMessage").html("");
                jQuery(".apiErrorMessage").html("");
                jQuery(".successMessage").html("");

                jQuery(".delOptInput_later").each(function () {
                    if (jQuery.trim(jQuery(this).val()) == "") {
                        error = 1;
                        jQuery(
                            "#" + jQuery(this).attr("id") + "_validate"
                        ).html(
                            "Please fill " +
                                jQuery(this).attr("name") +
                                " Field."
                        );
                        jQuery("#" + jQuery(this).attr("id")).focus();
                        return false;
                    }
                });

                if (error == 1) {
                    return false;
                } else {
                    jQuery(".deliveryLoaderImage").show();
                    var data = jQuery(delivery_options_later_form).serialize();
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: data,
                        dataType: "json",
                        success: function (data) {
                            jQuery(".deliveryLoaderImage").hide();
                            jQuery("#deliveryResMgsLater").html(data.message);
                            jQuery("#deliveryResMgsLater").focus();
                            location.reload();
                        },
                        error: function (textStatus, errorThrown) {
                            //
                        },
                    });
                }
            }
        );

        // Same day options
        jQuery(document).on(
            "click",
            ".saveDelOpts.saveDelOptsSameday",
            function () {
                var error = 0;
                jQuery(".errorMessage").html("");
                jQuery(".apiErrorMessage").html("");
                jQuery(".successMessage").html("");

                jQuery(".delOptInput_later").each(function () {
                    if (jQuery.trim(jQuery(this).val()) == "") {
                        error = 1;
                        jQuery(
                            "#" + jQuery(this).attr("id") + "_validate"
                        ).html(
                            "Please fill " +
                                jQuery(this).attr("name") +
                                " Field."
                        );
                        jQuery("#" + jQuery(this).attr("id")).focus();
                        return false;
                    }
                });

                if (error == 1) {
                    return false;
                } else {
                    jQuery(".deliveryLoaderImage").show();
                    var data = jQuery(
                        delivery_options_sameday_form
                    ).serialize();
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: data,
                        dataType: "json",
                        success: function (data) {
                            jQuery(".deliveryLoaderImage").hide();
                            jQuery("#deliveryResMgsSameDay").html(data.message);
                            jQuery("#deliveryResMgsSameDay").focus();
                            location.reload();
                        },
                        error: function (textStatus, errorThrown) {
                            //
                        },
                    });
                }
            }
        );
    }

    // Numeric check
    function sherpaCheckForNumeric() {
        let element = jQuery("#del_price");
        onlyPositiveNumeric(element);
    }

    function onlyPositiveNumeric(element) {
        element.on("input", function () {
            this.value = this.value
                .replace(/[^0-9.]/g, "")
                .replace(/(\..*)\./g, "$1");
        });
    }

    sherpaCheckForNumeric();

    // Sherpa delivery prefs check
    function sherpaDeliveryPrefsCheck() {
        var authorityToLeave = jQuery(
            'input[name="sherpa_settings_authority_to_leave"]'
        );
        var scheduledMedication = jQuery(
            'input[name="sherpa_settings_contains_scheduled_medication"]'
        );
        var tobacco = jQuery('input[name="sherpa_settings_contains_tobacco"]');
        var specifiedRecipient = jQuery(
            'input[name="sherpa_settings_specified_recipient"]'
        );
        var fragile = jQuery(
            'input[name="sherpa_settings_contains_fragile_items"]'
        );
        var alcohol = jQuery('input[name="sherpa_settings_contains_alcohol"]');

        var showAlertMessage = function () {
            alert(
                "In some states, same-day delivery of alcohol requires the recipient to be in attendance. Please check appropriate laws in your state for more information."
            );
        };

        var uncheckATL = function () {
            authorityToLeave.prop("checked", false).removeAttr("checked");
        };

        var disableATL = function () {
            authorityToLeave.attr("disabled", true);
        };

        var enableATL = function () {
            authorityToLeave.removeAttr("disabled");
        };

        var checkSpecifiedRecipient = function () {
            specifiedRecipient.prop("checked", true);
        };

        var checkIfAnyChecked = function () {
            var anyChecked = false;
            jQuery.each(
                [scheduledMedication, tobacco, specifiedRecipient],
                function (index, value) {
                    if (value.prop("checked")) {
                        anyChecked = true;
                    }
                }
            );

            if (false === anyChecked) {
                authorityToLeave.removeAttr("disabled");
            }

            return anyChecked;
        };

        var fragileChecked = function (checked) {
            fragile.prop("checked", checked);
        };

        var fragileDisabled = function (disabled) {
            fragile.attr("disabled", disabled);
        };

        var handleFragileCheck = function (that) {
            if (that.checked) {
                fragileChecked(true);
            } else {
                fragileDisabled(false);
            }
        };

        var handleChange = function (that) {
            if (that.checked) {
                uncheckATL();
                disableATL();
            }

            checkIfAnyChecked();
        };

        if (checkIfAnyChecked()) {
            uncheckATL();
            disableATL();
        }

        scheduledMedication.change(function () {
            handleFragileCheck(this);
            handleChange(this);
        });

        tobacco.change(function () {
            handleChange(this);
        });

        specifiedRecipient.change(function () {
            handleChange(this);
        });

        alcohol.change(function () {
            if (this.checked) {
                if (authorityToLeave.prop("checked")) {
                    showAlertMessage();
                }
                uncheckATL();
                disableATL();
                checkSpecifiedRecipient();
            } else {
                enableATL();
            }
        });

        authorityToLeave.change(function () {
            if (this.checked && alcohol.prop("checked")) {
                showAlertMessage();
            }
        });
    }
	// Change Delivery Vehicle
	function change_delivery_vehicle(){
		let selected_vehicle_help;
		switch (jQuery('#vehicle_options').val()) {
			case '1':
				selected_vehicle_help = 'Max 60x60x60cm & 20Kg per item - Up to 6 items, please select Van for larger deliveries.';
				break;
			case '2':
				selected_vehicle_help = 'Max 30x30x30cm & 10Kg - Please ensure the item/s suit this option. No fragile items.';
				break;
			case '4':
				selected_vehicle_help = 'Max 150x150x150cm & 25Kg per item - Please allow up to 30 minutes for a Sherpa to be allocated and specify dimensions in item description.';
		}
		jQuery('.selected_vehicle_help').html(selected_vehicle_help);
	}
    sherpaDeliveryPrefsCheck();
    jQuery('#vehicle_options').on('change', function() {
        change_delivery_vehicle();
    });
	change_delivery_vehicle();
});

// Refresh dropdowns
function refreshDisables(htmlId) {
    var dropdowns = jQuery("#" + htmlId + "_container").find("select");

    // Collect used values
    var getUsedValues = function () {
        // dropdownID => value
        var usedValues = [];

        dropdowns.each(function (index) {
            var dropdown = jQuery(this);
            var value = dropdown.val();

            if (value) {
                usedValues.push({
                    index: index,
                    value: String(value),
                });
            }
        });

        return usedValues;
    };

    dropdowns.each(function (index) {
        var dropdown = jQuery(this);
        var currentValue = String(dropdown.val());
        var usedValues = getUsedValues();
        var usedCurrentValueBy = usedValues.filter(function (item) {
            return item.value == currentValue && item.index != index;
        });

        if (usedCurrentValueBy.length === 0) {
            return;
        }

        var firstUser = usedCurrentValueBy[0];

        if (firstUser.index <= index) {
            var usedValuesOnly = usedValues.map(function (item) {
                return item.value;
            });

            dropdown.find("option").each(function () {
                var option = jQuery(this);
                var optionValue = String(option.prop("value"));

                if (usedValuesOnly.indexOf(optionValue) !== -1) {
                    option.prop("disabled", true);
                    option.prop("selected", false);
                }
            });
        }
    });

    // Re run for disable
    var usedValues = getUsedValues();
    dropdowns.each(function (index) {
        var dropdown = jQuery(this);
        var options = dropdown.find("option");

        options.each(function () {
            var option = jQuery(this);
            var optionValue = String(option.prop("value"));
            var valueUser = usedValues.filter(function (item) {
                return item.value == optionValue;
            });

            if (valueUser.length > 0 && valueUser[0].index != index) {
                option.prop("disabled", true);
            }

            if (valueUser.length === 0) {
                option.prop("disabled", false);
            }
        });
    });
}

// Flat rate widget
function flatRate(htmlId, objectName) {
    this.htmlId = htmlId;
    this.objectName = objectName;
    this.groupRowTemplate =
        "<tr>" +
        '<td><select onChange="' +
        this.objectName +
        '.groupControl.handleGroupDropdownChange(); return false;" class="pricegroup required-entry" name="' +
        this.htmlId +
        '[{{index}}][distance_group]" id="group_price_row_' +
        this.htmlId +
        '_{{index}}_distance_group">' +
        '<option value="5">0-5 KM</option>' +
        '<option value="10">5-10 KM</option>' +
        '<option value="20">10-20 KM</option>' +
        '<option value="30">20-30 KM</option>' +
        '<option value="40">30-40 KM</option>' +
        '<option value="50">40-50 KM</option>' +
        "</select></td>" +
        '<td><input class="required-entry" type="number" min="0" max="9999" step="0.01" name="' +
        this.htmlId +
        '[{{index}}][price]" value="{{price}}" id="group_price_row_' +
        this.htmlId +
        '_{{index}}_price" /></td>' +
        '<td class="last"><input type="hidden" name="' +
        this.htmlId +
        '[{{index}}][delete]" class="delete" value="" data-id="group_price_row_{{index}}_delete" />' +
        '<button title="Delete Group Price" type="button" class="scalable delete icon-btn delete-product-option" id="group_price_row_' +
        this.htmlId +
        '_{{index}}_delete_button distance-delete-btn" onclick="' +
        this.objectName +
        '.groupControl.deleteItem(event); return false;">' +
        "<span>Delete</span></button></td>" +
        "</tr>";

    this.groupControl = {
        template: this.groupRowTemplate,
        htmlId: this.htmlId,
        itemsCount: 0,

        addItem: function () {
            var data = {
                group: "5",
                price: '0',
                readOnly: false,
                index: this.itemsCount++,
            };

            if (arguments.length >= 3) {
                data.group = arguments[0];
                data.price = arguments[1];
            }

            if (arguments.length == 4) {
                data.readOnly = arguments[2];
            }

            if (this.itemsCount >= 6) {
                jQuery("button.add-" + this.htmlId).hide();
            }

            var template = this.template
                .replace(new RegExp("{{group}}", "g"), data.group)
                .replace(new RegExp("{{price}}", "g"), data.price)
                .replace(new RegExp("{{readOnly}}", "g"), data.readOnly)
                .replace(new RegExp("{{index}}", "g"), data.index);

            jQuery("#" + this.htmlId + "_container").append(template);
            jQuery(
                "#group_price_row_" +
                    this.htmlId +
                    "_" +
                    data.index +
                    "_distance_group"
            ).val(data.group);

            refreshDisables(this.htmlId);

            if (data.readOnly == "1") {
                ["website", "distance_group", "price", "delete"].each(function (
                    element_suffix
                ) {
                    jQuery(
                        "#group_price_row_" + data.index + "_" + element_suffix
                    ).disabled = true;
                });
                jQuery(
                    "#group_price_row_" +
                        this.htmlId +
                        "_" +
                        data.index +
                        "_delete_button"
                ).hide();
            }
        },

        disableElement: function (element) {
            element.disabled = true;
            element.addClassName("disabled");
        },

        deleteItem: function (event) {
            this.itemsCount--;
            var tr = jQuery(event.currentTarget).parent().parent();
            if (tr) {
                jQuery(".delete", tr).each(function (key, element) {
                    element.value = "1";
                });
                jQuery(["input", "select"], tr).each(function (element) {
                    jQuery(element).hide();
                });
                jQuery(tr).remove();
                jQuery(tr).addClass("no-display template");
            }
            if (this.itemsCount < 6) {
                jQuery("button.add-" + this.htmlId).show();
            }
            refreshDisables(this.htmlId);
            return false;
        },

        handleGroupDropdownChange: function () {
            refreshDisables(this.htmlId);
        },
    };
}

function delOptionMethodAdmin(data) {
    if (data.val() == 1) {
        jQuery(".schedule_delivery_section").hide();
    } else {
        jQuery(".schedule_delivery_section").show();
    }
}

function ratesMethodAdmin(data) {
    let sherpaMarginShow = jQuery(".margin_show");
    let sherpaFlatShow = jQuery(".flat_show");

    if (data.val() == "MR") {
        sherpaMarginShow.show().css("display", "flex");
        sherpaFlatShow.hide();
    } else if (data.val() == "SR") {
        sherpaMarginShow.hide();
        sherpaFlatShow.hide();
    } else {
        sherpaMarginShow.hide();
        sherpaFlatShow.show();
    }
}

// Sandbox conditonal text
function sherpaSandboxConditionalText(e) {
    let sherpaSandboxYes = jQuery("#sherpa_sanbox_yes");
    let sherpaSandboxNo = jQuery("#sherpa_sanbox_no");
    if (0 == e.value) {
        sherpaSandboxYes.attr("hidden", "hidden");
        sherpaSandboxNo.removeAttr("hidden");
    } else {
        sherpaSandboxYes.removeAttr("hidden");
        sherpaSandboxNo.attr("hidden", "hidden");
    }
}
