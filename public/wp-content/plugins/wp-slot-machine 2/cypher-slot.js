

jQuery(document).ready(function($) {

    // Set all slots to show the "bell" icon on page load
    $('.icon').addClass('bell');

    // Function to set a specific icon for a slot
    const setSlotIcon = (slotId, iconClass) => {
        $(`#${slotId} .icon`).attr('class', 'icon ' + iconClass);
    };


    $('#cypher-spin-button').click(function() {
        // Initialize timers
        let timer1, timer2, timer3;

        // Disable the button
        // $(this).prop('disabled', true).addClass('disabled');

        // Hide the "I dont want free stuff" button when spin is clicked
        $('#no_spin_button').addClass('hide-button');

        // Show the "Proceed to delivery" button when spin is clicked
        $('#proceed_cart').removeClass('hide-button').addClass('show-button');


        // Reset the prize notification
        $('#cypher-prize-message').text('');

        // Add this AJAX request
        $.ajax({
            url: php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_order_2', // This will be the POST parameter
                create_order: true // Add this line
            },
            success: function(response) {
                // Handle the response here if needed
            }
        });
        console.log(php_vars);
        $.ajax({
            url: php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'cypher_random_spin', // Call the random_spin function
                spin_id: php_vars.spin_id, // Get the spin_id from php_vars
            },
            success: function(response) {
                console.log('Response from random_spin:', response); // Log the response from random_spin
                // Parse the response into a JavaScript object
                var prize = JSON.parse(response);
                // Log the prize object
                console.log('Prize:', prize);
        
                // Call the create_order function with the response from random_spin
                $.ajax({
                    url: php_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_order_2', // This will be the POST parameter
                        prize_name: prize.name, // Pass the name from the prize object
                        prize_id: prize.id // Pass the id from the prize object
                    },
                    success: function(response) {
                        // Handle the response here if needed
                    }
                });
                setTimeout(() => {
                    clearInterval(timer3);
                    const randomIcon = $('#slot1 .icon').attr('class').split(' ')[1];  // Get the icon class set for slot1
                    setSlotIcon('slot3', randomIcon);
        
                    // Parse the response into a JavaScript object
                    var prize = JSON.parse(response);

                    // Check if the prize id is 0 (spin has reached its maximum count)
                    if (prize.id === 0) {
                        // Display the error message on the page
                        $('#cypher-prize-message').text(prize.name);
                    } else {
                        // Display the prize message on the page
                        $('#cypher-prize-message').text(prize.name + ' Has been added to your cart');
                    }
                }, 3000); // Slot 3 stops 0.5 seconds after Slot 2
                // Redirect after 3 seconds
            }
        });

        // Function to update a slot with a random number
        const updateSlot = (slotId) => {
            const icons = ['bar', 'bell', 'clover', 'diamond', 'cherry', 'lemon', 'plum', 'seven', 'watermelon'];
            const randomIcon = icons[Math.floor(Math.random() * icons.length)];
            $(`#${slotId} .icon`).attr('class', 'icon ' + randomIcon);
        };

        // Start cycling numbers on each slot
        timer1 = setInterval(() => updateSlot('slot1'), 100);
        timer2 = setInterval(() => updateSlot('slot2'), 100);
        timer3 = setInterval(() => updateSlot('slot3'), 100);

        // Stop cycling numbers after specific time intervals
        setTimeout(() => {
            clearInterval(timer1);
            // Update with a single random icon for all slots
            const icons = ['bar', 'bell', 'clover', 'diamond', 'cherry', 'lemon', 'plum', 'seven', 'watermelon'];
            const randomIcon = icons[Math.floor(Math.random() * icons.length)];
            
            setSlotIcon('slot1', randomIcon);
        }, 2000);

        setTimeout(() => {
            clearInterval(timer2);
            const randomIcon = $('#slot1 .icon').attr('class').split(' ')[1];  // Get the icon class set for slot1
            setSlotIcon('slot2', randomIcon);
        }, 2500);

    });
});



