jQuery(function($) {

    // Toggle for delivery instructions
    $(document.body).on('click', '.delivery-instructions-container', function() {
        $('.delivery-notes').slideToggle();
        $('.toggle-instructions').toggleClass('fa-chevron-right fa-chevron-down');
    });

    $(document.body).on('click', '.delivery-notes', function(event) {
        event.stopPropagation();
    });

    // Function to show/hide the loading overlay
    function toggleLoadingOverlay(show) {
        if (show) {
            $('.loading-overlay').show();
        } else {
            $('.loading-overlay').hide();
        }
    }


    // Function to disable/enable buttons
    function toggleButtons(disabled) {
        $('button.plus, button.minus').prop('disabled', disabled);
    }

    $('body').on('click', 'button.plus, button.minus', function() {
        toggleLoadingOverlay(true); // Show the loading overlay
        toggleButtons(true); // Disable buttons to prevent multiple clicks
    
        var type = $(this).data('quantity'); // plus or minus
        var cart_item_key = $(this).data('cart_item_key');
        var currentQty = $(this).closest('.product-quantity').find('.qty').val();
        var newQty = type === 'plus' ? parseInt(currentQty, 10) + 1 : parseInt(currentQty, 10) - 1;
    
        // Prevent negative quantity
        if (newQty < 1) newQty = 1;
    
        $.ajax({
            type: 'POST',
            url: cart_ajax_object.ajax_url,
            data: {
                action: 'update_cart',
                cart_item_key: cart_item_key,
                quantity: newQty,
                nonce: cart_ajax_object.nonce
            },
            beforeSend: function() {
                toggleLoadingOverlay(true);
                toggleButtons(true);
            },
            success: function(response) {
                if (response.success) {
                    // Update the quantity field
                    $('input[name="cart[' + cart_item_key + '][qty]"]').val(newQty);
                    $('.cart-subtotal-top h3 span').html(response.data.subtotal);
                } else {
                    // If response.success is false, handle it
                    alert('There was an error updating the cart. Please try again.');
                }
                toggleLoadingOverlay(false); // Hide the loading overlay
                toggleButtons(false); // Enable buttons
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX call failed: ', textStatus, errorThrown);
                alert('Failed to update cart. Please try again.');
                toggleLoadingOverlay(false); // Hide the loading overlay
                toggleButtons(false); // Enable buttons
            }
        });
    });    

});
