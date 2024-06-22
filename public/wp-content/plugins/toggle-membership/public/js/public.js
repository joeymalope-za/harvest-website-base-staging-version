jQuery(document).ready(function($) {
    $('#high-roller-toggle').on('change', function() {
        var productId = $(this).data('product-id');
        var isChecked = $(this).is(':checked');
    
        // Show overlay
        $('#ajax-overlay').show();
    
        // Temporarily unbind the WooCommerce update_checkout event to prevent multiple triggers
        $(document.body).off('updated_checkout');
    
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_product',
                product_id: productId,
                membership: isChecked ? 'high_roller' : '',
                nonce: ajax_object.nonce
            },
            complete: function(response) {
                // Re-bind the updated_checkout event
                $(document.body).on('updated_checkout', function() {
                    // update after WooCommerce has updated the checkout
                    if (isChecked) {
                        $('.price-1').css('text-decoration', 'line-through');
                        $('.price-2').show();
                    } else {
                        $('.price-1').css('text-decoration', 'none');
                        $('.price-1').css('color', '#000000');
                        $('.price-2').hide();
                    }
                    // Hide overlay
                    $('#ajax-overlay').hide();
                });
    
                // Manually trigger WooCommerce checkout update
                $(document.body).trigger('update_checkout');
            },
            error: function(errorThrown) {
                console.log(errorThrown);
                // Hide overlay
                $('#ajax-overlay').hide();
            }
        });
    });
    
    
    
});
