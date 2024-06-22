jQuery(function ($) {
    
    const wwbp_product_pricing_rules_common = {

        /**
         * Block Weight Range Table When Request Processing
         */
        block: function(selector) {
            $(selector).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        /**
         * Unblock Weight Range Table When Request Processing
         */
        unblock: function(selector) {
            $(selector).unblock();
        },
    }

    const wwbp_simple_product_pricing_rules = {

        /**
         * Initialization
         */
        init: function() {

            // Toggle Options Trigger
            $('.wwbp_simple_product_options').on('change', '#wwbp_is_enable', this.toggleOptions);

            // Add Pricing Rule Row
            $('#general_product_data').on('click', '#add_weight_ranges', this.addPricingRuleRow);

            // Remove Pricing Rule Row
            $('#general_product_data').on('click', '.delete_weight_ranges', this.removePricingRuleRow);
        },

        /**
         * Toggle Options
         */
        toggleOptions: function() {
            $(this).closest(".wwbp_simple_product_options").find(".wwbp_simple_options").slideToggle("slow");
        },

        /**
         * Add Weight Range Pricing
         */
        addPricingRuleRow: function(event) {

            wwbp_product_pricing_rules_common.block(".wwbp_simple_pricing_rules");

            let $this = $(event.currentTarget),
                post_id = $($this).attr('data-post_id'),
                unique_id = Math.floor(Math.random() * 1000) + Date.now();

            let data = {
                action: "wwbp_ajax_simple_range_add",
                general_data: unique_id,
                post_id: post_id,
                _ajax_nonce: wwbp_admin.ajax_nonce,
            }

            $.post(wwbp_admin.ajax_url, data, function(response) {
                $("#wwbp_pricing_rules_table").append(response);
                let rows_count = $('.simple_range_row').length;
                if (rows_count > 0) {
                    $(".empty_row").remove();
                }

                wwbp_product_pricing_rules_common.unblock(".wwbp_simple_pricing_rules");
            });
        },

        /**
         * Remove Pricing Rule Row
         */
        removePricingRuleRow: function(event) {

            wwbp_product_pricing_rules_common.block(".wwbp_simple_pricing_rules");

            let $this = $(event.currentTarget),
                post_id = $($this).attr('data-post_id'),
                unique_id = $($this).attr('data-unique_id');

            let data = {
                action: "wwbp_ajax_simple_range_remove",
                unique_id: unique_id,
                post_data: post_id,
                _ajax_nonce: wwbp_admin.ajax_nonce,
            }

            $.post(wwbp_admin.ajax_url, data, function(response) {
                if (response.success) {
                    let trimmed_response = response.data.replace(/\s/g, '');
                    $("#row_" + trimmed_response).remove();
                    let rows_count = $('.simple_range_row').length;
                    if (rows_count <= 0) {
                        $("#wwbp_pricing_rules_table").append("<tr class='empty_row'><td colspan='4'>No Rules Found</td></tr>");
                    }
                } else {
                    alert("Something went wrong, Try again!");
                }

                wwbp_product_pricing_rules_common.unblock(".wwbp_simple_pricing_rules");
            });
        },
    };

    wwbp_simple_product_pricing_rules.init();

    const wwbp_variable_product_pricing_rules = {

        /**
         * Initialization
         */
        init: function() {

            // Toggle Options Trigger
            $(document).on('change', '.wwbp_is_enable', this.toggleOptions);

            // Add Pricing Rule Row
            $('#variable_product_options').on('click', '#add_weight_ranges', this.addPricingRuleRow);

            // Remove Pricing Rule Row
            $('#variable_product_options').on('click', '.delete_weight_ranges', this.removePricingRuleRow);
        },

        /**
         * Toggle Options
         */
        toggleOptions: function() {
            $(this).closest(".wwbp_variable_product_options").find(".wwbp-variable-options").slideToggle("slow");
        },

        /**
         * Add Weight Range Pricing
         */
        addPricingRuleRow: function(event) {

            let $wrapper = $(this).closest('.woocommerce_variation').find('.wwbp_variable_pricing_rules');
            wwbp_product_pricing_rules_common.block($wrapper);

            let $this = $(event.currentTarget),
                unique_id = Math.floor(Math.random() * 1000) + Date.now(),
                variation_id = $(this).data('var_id');

            let data = {
                action: "wwbp_ajax_variable_range_add",
                general_data: unique_id,
                var_data: variation_id,
                _ajax_nonce: wwbp_admin.ajax_nonce,
            }

            $.post(wwbp_admin.ajax_url, data, function(response) {
                $this.closest('.woocommerce_variation').find('#wwbp_pricing_rules_table').append(response);
                let row_count = $('.variable_range_row').length;
                if (row_count > 0) {
                    $(".empty_row").remove();
                }

                wwbp_product_pricing_rules_common.unblock($wrapper);
            });
        },

        /**
         * Remove Weight Range Pricing
         */
        removePricingRuleRow: function(event) {

            let $wrapper = $(this).closest('.woocommerce_variation').find('.wwbp_variable_pricing_rules');
            wwbp_product_pricing_rules_common.block($wrapper);

            let $this = $(event.currentTarget),
                post_id = $($this).attr('data-post_id'),
                unique_id = $($this).attr('data-unique_id');

            let data = {
                action: "wwbp_ajax_variable_range_remove",
                unique_id: unique_id,
                post_data: post_id,
                _ajax_nonce: wwbp_admin.ajax_nonce,
            }

            $.post(wwbp_admin.ajax_url, data, function(response) {
                if (response.success) {
                    let trimmed_response = response.data.replace(/\s/g, '');
                    $this.closest('.woocommerce_variation').find("#row_" + trimmed_response).remove();
                    let rows_count = $('.variable_range_row').length;
                    if (rows_count <= 0) {
                        $(".wwbp_pricing_rules_table").append("<tr class='empty_row'><td colspan='4'>No Rules Found</td></tr>");
                    }
                } else {
                    alert("Something went wrong, Try again!");
                }

                wwbp_product_pricing_rules_common.unblock($wrapper);
            });
        },
    };

    wwbp_variable_product_pricing_rules.init();

});