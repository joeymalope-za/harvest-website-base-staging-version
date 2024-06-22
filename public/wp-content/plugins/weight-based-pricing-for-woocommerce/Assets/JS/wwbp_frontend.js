jQuery(function ($) {

    // Calculate and Display the Product Actual Weight and Price
    // Based on User inputted weight quantity
    $(document.body).on('change', '#wwbp_weight', function () {

        // Get values from data JSON
        let data = JSON.parse($('#wwbp_data').html()),
            price = parseFloat(data['price']),
            price_decimals = parseInt(data['price_decimals']),
            currency_symbol = data['currency_symbol'],
            wastage_percentage = parseFloat(data['wastage_percentage']),
            pricing_rules = data['pricing_rules'],
            actual_weight, sale_price, special_price = price;

        // Get user inputted weight quantity
        let input_weight = parseFloat($('#wwbp_weight').val());

        // Get Special Price based on the Weight and Pricing Rules
        for (i = 0; i < pricing_rules.length; i++) {
            from_weight = parseFloat(pricing_rules[i]['wwbp_from_weight']);
            to_weight = parseFloat(pricing_rules[i]['wwbp_to_weight']);

            if (from_weight <= input_weight && input_weight <= to_weight) {
                special_price = parseFloat(pricing_rules[i]['wwbp_sale_price']);
            }
        }

        // Check and Display Special Price
        if (0 < special_price && special_price < price) {
            sale_price = parseFloat(input_weight * price).toFixed(price_decimals);
            $('#wwbp_sale_price').html(currency_symbol + sale_price);
            price = special_price;
        } else {
            $('#wwbp_sale_price').html("");
        }

        // Calculate Actual Weight and Price
        actual_weight = input_weight - ((wastage_percentage / 100) * input_weight);
        price = input_weight * price;

        // Format Actual Weight and Price
        actual_weight = parseFloat(parseFloat(actual_weight).toFixed(3));
        price = parseFloat(price).toFixed(price_decimals);

        // Display Actual weight
        if (isNaN(actual_weight)) {
            $('#wwbp_actual_weight').html("");
        } else {
            $('#wwbp_actual_weight').html(actual_weight);
        }

        // Display Price
        if (isNaN(price)) {
            $('#wwbp_product_price').html("");
        } else {
            $('#wwbp_product_price').html(price);
        }

    });

    // Generate Calculation Section only for Variable Product
    $(document.body).on('change', 'input.variation_id', function () {

        // Get Variation ID
        let variation_id = this.value;

        // Validate Variation ID
        if (variation_id != '') {

            // Get Products general data
            let general_data = JSON.parse($(".wwbp_general_data").html());

            // Get Product data for the specific Variation ID from JSON array
            let product_data = JSON.parse($(".wwbp_variations_data").html())[variation_id];

            // Calculation Section Data
            let data = [];
            data['price'] = product_data['wwbp_price'];
            data['price_decimals'] = general_data['wwbp_price_decimals'];
            data['currency_symbol'] = general_data['wwbp_currency_symbol'];
            data['custom_input_is_enable'] = general_data['wwbp_custom_input_is_enable'];
            data['actual_weight_is_enable'] = general_data['wwbp_actual_weight_is_enable'];
            data['wastage_percentage'] = product_data['wwbp_wastage_percentage'];
            data['pricing_rules'] = product_data['wwbp_pricing_rules'];

            // Data array to JSON object
            data_json = JSON.stringify(Object.assign({}, data));

            // Check Weight based Product Pricing is enabled
            if (product_data['wwbp_is_enable'] == "yes") {

                let readonly = data['custom_input_is_enable'] ? "" : "readonly";
                let show_actual_weight = data['actual_weight_is_enable'] ? "" : "style='display: none'";

                // Generate calculation section
                let section = "<div id='wwbp_data' style='display:none'>" + data_json + "</div>";
                
                section += "<table>";

                section += "<tr><td>" + general_data['wwbp_weight_label'] + " (" + product_data['wwbp_weight_unit'] + ")</td>";
                section += "<td><span class='weight'>";
                section += "<input type='button' value='-' class='wwbp_button minus' disabled>";
                section += "<input type='number' name='wwbp_weight_qty'";
                section += "    id='wwbp_weight' class='qty'";
                section += "    max='" + product_data['wwbp_max_qty'] + "'";
                section += "    min='" + product_data['wwbp_min_qty'] + "'";
                section += "    step='" + product_data['wwbp_intervel'] + "'";
                section += "    value='" + product_data['wwbp_min_qty'] + "'";
                section += "    autofocus required " + readonly + ">";
                section += "<input type='button' value='+' class='wwbp_button plus'>";
                section += "</span></td></tr>";

                section += "<tr " + show_actual_weight + "><td>" + general_data['wwbp_actual_weight_label'] + "</td>";
                section += "<td><span id='wwbp_actual_weight'>";
                section += product_data['wwbp_actual_weight'] + "</span>";
                section += product_data['wwbp_weight_unit'] + "</td></tr>";

                section += "<tr><td><b>" + general_data['wwbp_sale_price_label'] + "</b></td>";
                section += "<td><span id='wwbp_sale_price' style='text-decoration: line-through; opacity: 0.8;'></span>"; 
                section += " <b>" + general_data['wwbp_currency_symbol'] + "<span id='wwbp_product_price'>";
                section += product_data['wwbp_sale_price'] + "</span></b></td></tr>";

                section += "</table>";

                // Load section
                $('.wwbp_calculation_section').html(section);
            
            } else {

                // Clear section
                $('.wwbp_calculation_section').html("");
            }
        } else {

            // Clear section
            $('.wwbp_calculation_section').html("");
        }
    });

    // Get Step Decimals
    if (!String.prototype.getDecimals)
    {
        String.prototype.getDecimals = function () 
        {
            var num = this, match = ('' + num).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);
            if (!match) return 0;
            return Math.max(0, (match[1] ? match[1].length : 0) - (match[2] ? +match[2] : 0));
        }
    }

    // Weight "plus" and "minus" buttons
    $(document.body).on('click', '.wwbp_button.plus, .wwbp_button.minus', function () {

        // Get values
        var $wgt = $(this).closest('.weight').find('.qty'),
            currentVal = parseFloat($wgt.val()),
            max = parseFloat($wgt.attr('max')),
            min = parseFloat($wgt.attr('min')),
            step = $wgt.attr('step');

        // Format values
        if (!currentVal || currentVal === '' || currentVal === 'NaN') currentVal = 0;
        if (max === '' || max === 'NaN') max = '';
        if (min === '' || min === 'NaN') min = 0;
        if (step === 'any' || step === '' || step === undefined || parseFloat(step) === 'NaN') step = 1;

        // Change the value
        if ($(this).is('.plus')) {
            if (max && (currentVal >= max)) $wgt.val(max);
            else $wgt.val((currentVal + parseFloat(step)).toFixed(step.getDecimals()));
        } else {
            if (min && (currentVal <= min)) $wgt.val(min);
            else if (currentVal > 0) $wgt.val((currentVal - parseFloat(step)).toFixed(step.getDecimals()));
        }

        // Get Value
        currentVal = parseFloat($wgt.val());

        // Disable minus button if reach the minimum range
        if (min === currentVal) $('.minus').prop('disabled', true);
        else $('.minus').prop('disabled', false);

        // Disable plus button if reach the maximum range
        if (max === currentVal) $('.plus').prop('disabled', true);
        else $('.plus').prop('disabled', false);

        // Trigger change event
        $wgt.trigger('change');
    });
    
});
