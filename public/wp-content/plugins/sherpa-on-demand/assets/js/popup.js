jQuery(document).ready(function() {

  // Remove bulk edit from STS Page
  jQuery("#bulk-action-selector-top option[value='sherpa_edit']").remove();
  jQuery("#bulk-action-selector-bottom option[value='sherpa_edit']").remove();

  // Add 'delete_order' option for STS page
  jQuery('#bulk-action-selector-top').append(jQuery('<option>', {
    value: 'delete_order',
    text: 'Bulk Delete'
  }));
  jQuery('#bulk-action-selector-bottom').append(jQuery('<option>', {
    value: 'delete_order',
    text: 'Bulk Delete'
  }));
  //var test = jQuery(del_date_viewdd).val();

  //Footer Note for Send to Sherpa page
    var divElement = jQuery('<div style="margin-top:50px; padding: 20px; background: #f6f7f7">');
    var noteHeading = jQuery('<p>').append(jQuery('<strong>').text('Please Note:'));
    var note = jQuery('<p><strong>Please Note</strong></p>');
    var loginLink = jQuery('<a>').attr('href', 'https://deliveries.sherpa.net.au/users/sign_in').attr('target', '_blank').text('Sherpa Delivery account');
    var supportLink = jQuery('<a>').attr('href', 'https://www.sherpa.net.au/contact').attr('target', '_blank').text('Sherpa Support');
    var moreInfoLink = jQuery('<a>').attr('href', 'https://help.auuser.sherpadelivery.com/hc/en-us/articles/4409689836429-When-and-where-is-Sherpa-available-').attr('target', '_blank').text('Click here');
    var text = jQuery('<p>').text('If you select a delivery option different to that chosen by customer at checkout, pricing may vary. Actual cost will be shown within your ').append(loginLink).append('.');
    var text2 = jQuery('<p>').text('Please contact your Account Manager or ').append(supportLink).append(' for any queries in relation to the cost or progress of your deliveries.');
    var text3 = jQuery('<p>').append(moreInfoLink).append(' for more information about Sherpa’s delivery areas.');
    divElement.append(noteHeading).append(text).append(text2).append(text3);
    jQuery('.alignleft.actions.bulkactions:last').after(divElement);

  jQuery(document).on('change', '#posts-filter', function(e) {
    let val = jQuery('#bulk-action-selector-top').val();
    if('delete_order' === val) {
      e.preventDefault();
      console.log('Delete order option selected');

       jQuery('#doaction').on('click', function(){
        //Fetch all checked order
        let post_ids = jQuery('input[name="post[]"]:checked').map(function(){
          return this.value;
        }).get();

        if (post_ids.length < 1) {
          alert('Please select orders to be removed.');
          return false;
        }

        var formData = {
          action: 'my_ajax_send_to_sherpa_delete_action', // WP Action
          nonce: sherpa.nonce,
          type: 'delete',
          post_ids: post_ids,
          set_interval: set_interval
        }
        if (confirm("The selected order/s will be removed from the Send to Sherpa queue. You can add them again by selecting ‘Send to Sherpa’ from within the Order Actions on the Orders page.") == true) {

          jQuery.post(sherpa.ajax_url, formData, function(response) {
            console.log('Response from php '+response);
            //location.reload(); // Reload the page
          });
      
        } else {
          console.log('declined by user');
        }
      });//click event
        console.log('formData : '+formData);
    }
    if ('sherpa_edit' === val) {
      e.preventDefault();
      let post_ids = jQuery('input[name="post[]"]:checked').map(function() {
          return this.value;
      }).get().join();
      if (post_ids) {
        jQuery('#exampleModal #post_ids').val(post_ids);
        jQuery('#exampleModal').modal('show');
      }
			var set_interval = '1hr';
			jQuery('#sherpa_method_option').empty();
			jQuery('.loaderdisdelwinsec').show();
			var formData = {
        action: 'my_ajax_select_shepa_update_action',
        nonce: sherpa.nonce,
        type: 'changes',
        post_ids: post_ids,
        set_interval: set_interval
      }
      jQuery.post(sherpa.ajax_url, formData, function(response) {
        data_sherpa = JSON.parse(response);
        jQuery.each(data_sherpa[0][0], function(key, item) {
          Object.prototype.toString = function() {
            return JSON.stringify(item)
          }
          slots = slotv = item.slot_start_time + ' - ' + item.slot_end_time;
				  //slotv.split(":")[0]+slotv.split(" ")[1]+' - '+slotv.split(" ")[3].split(":")[0]+ slotv.split(" ")[4]
          jQuery('#sherpa_method_option').append(jQuery("<option value=" + slots + ">" + slots + "</option>").val(slots));
				  jQuery('.loaderdisdelwinsec').hide();
		    });
      })		
    }
  });
	
	// Sending order to Send to Sherpa page
  jQuery(document).on('click', '#subBtn', function(e) {
    e.preventDefault();
    //debugger;
    
    var dateTimeValues = {};
    var sddClosingTime = '16:00';

//debugger;
    //Using each and get and join
    var stsAndOriginalIds = {};
    jQuery('input[type="datetime-local"]').each(function() {
      let dateTimeSTSId = jQuery(this).attr('sts-ordid');
      let dateTimeOrderId = jQuery(this).attr('data-ordid');

      // Create an array of STS orders ids and their respective order ids
      stsAndOriginalIds[dateTimeOrderId.toString()] = dateTimeSTSId.toString();

      let value = jQuery(this).val();
      //dateTimeValues.push({orderId: id, orderValue: value });
      dateTimeValues[dateTimeOrderId.toString()] = value;
    });

    // Fetch value of chosen delivery service
    var chosenOptionValues = {};
    jQuery('select[id="del_options"]').each(function() {
      var del_option_order_id = jQuery(this).attr('data-ordid');
      var del_option_value = jQuery(this).val();
      chosenOptionValues[del_option_order_id.toString()] = del_option_value;
      console.log('Select delivery service for order '+ del_option_order_id + ' is '+del_option_value);
      // if(del_option_value === 'at'){
      //   var sddChosenTime = dateTimeValues[del_option_order_id]; // string
      //   console.log('sddChosenTime '+ sddChosenTime);
      //   console.log('sddChosenTime has type'+ typeof sddChosenTime);
      //   // Parse the time from the sddChosenTime variable
      //   var parsedTime = new Date(sddChosenTime);
      //   console.log('parsedTime '+ parsedTime);
      //   // Create a target time to compare with
      //   var parsedTargetTime = new Date(parsedTime);
      //   console.log('parsedTargetTime '+ parsedTargetTime);
      //   parsedTargetTime.setHours(16, 0, 0, 0);
      //   console.log('parsedTargetTime '+ parsedTargetTime);

      //   // Compare the times
      //   if (parsedTime > parsedTargetTime) {
      //       alert("The time exceeds 16:00. SDD must be chosen before 4 PM");
      //   } else {
      //       alert("The time is before 16:00.");
      //   }
      //   alert('SDD chosen for order '+ del_option_order_id+ ' for time '+ dateTimeValues[del_option_order_id]);
      //   alert('Type of datetime is '+typeof dateTimeValues[del_option_order_id]);
      // }
    });
    //debugger;

    //Checked Post Ids as an array
    let post_ids_array = jQuery('input[name="post[]"]:checked').map(function() {
      return this.value;
      }).get();
    console.log('Type of post_ids_array is '+typeof post_ids_array) // Object

    //Checked Post Ids as a comma separated string
    let post_ids = jQuery('input[name="post[]"]:checked').map(function() {
    return this.value;
    }).get().join();

    console.log('Type of post_ids is '+typeof post_ids);
    console.log(post_ids);

    //Convert strings of ids to array
    var id_array = post_ids.split(',');
    console.log('Type of id_array is '+typeof id_array);
    id_array.forEach(function(id){
      console.log('ID # '+id+' has time '+dateTimeValues[id]);
    });
 

    //Test: Checking and logging length of array
    if(post_ids_array.length > 1){
      //console.log('>1');
      console.log('Length is '+post_ids_array.length);
    }


    // If checkbox is checked
    if (post_ids) {

      // CHECK FOR ERRORS
      var errorsExist = false;

      //check for error messages in checked boxes
      jQuery('input[name="post[]"]:checked').each(function() {
        var checkbox = jQuery(this);
        var errorDiv = checkbox.closest("tr").find("div#error-message");
        var errorText = errorDiv.text().trim();
        
        if (errorText !== ""){      
            alert('Error: Please check below fields for the following error: '+errorText);
            errorsExist = true;
            return false;
        }
      });

      if(errorsExist){
        return false;
      }

      //CONVERT CHECKED IDS TO ARRAY
      postIdArray = post_ids.split(',');
      console.log('Post Id array[1]: '+postIdArray[1]);
//debugger;
      /* 
      CHECK IF SDD IS CHOSEN AFTER 4 PM
      */
      // LOOP THROUGH EACH CHOSEN STS ID
      for (var i = 0; i < postIdArray.length; i++) {
        var post_id = postIdArray[i];
        console.log('STS ID: '+postIdArray[i]+ ' is checked');
        // LOOP THROUGH EACH ORIGINAL ID AND ITS CORRESPONDING STS ID
        for (var originalOrderId in stsAndOriginalIds) {
          if(stsAndOriginalIds.hasOwnProperty(originalOrderId)) {
            console.log('Original order ID: '+originalOrderId+ ' has an STS ID: '+stsAndOriginalIds[originalOrderId]);
            // CHECK IF CHOSEN STS ID EXISTS IN THIS LIST
            if (postIdArray[i] === stsAndOriginalIds[originalOrderId] ){
              // CHECK CHOSEN DELIVERY FOR THIS ORDER
              console.log('chosen service'+chosenOptionValues[originalOrderId]);
              // CHECK TIME IF CHOSEN SERVICE IS SDD 
              if (chosenOptionValues[originalOrderId] === 'at' || true) {
                // CHECK IF TIME CHOSEN IS PAST 4PM
                let sddChosenTime = dateTimeValues[originalOrderId];
                console.log('Chosen time is '+sddChosenTime);
                console.log('sddChosenTime has type of '+typeof sddChosenTime);
                let currentTime = new Date();
                console.log("Current time is "+currentTime);
                let parsedSddTime = new Date(sddChosenTime);
                console.log('Closing time is '+parsedSddTime);
                var sddClosingTime = new Date(parsedSddTime);

                // CHANGE CLOSING TIME IF SDD SELECTED
                if (chosenOptionValues[originalOrderId] === 'at') {
                sddClosingTime.setHours(16, 0, 0, 0); // SDD Closing time set to 4PM
                console.log('Closing time is '+sddClosingTime);
                

                  // CHECK IF SDD CLOSING TIME HAS PASSED
                  if (parsedSddTime > sddClosingTime){
                    alert("Error from order#"+originalOrderId+" : Same Day Deliveries cannot be placed after 4 PM");
                    return false;
                  } 
                }

                // CHECK IF PAST TIME IS FORCE FED IN TIME FIELD
                if (parsedSddTime < currentTime){
                  alert("Error from order#"+originalOrderId+" : Please choose a time that is not in the past.");
                  return false;
                }
              }
            }
          }
        }
      }
 
      //debugger;
      var formData = {
        action: 'my_ajax_send_sherpa_action',
        nonce: sherpa.nonce,
        type: 'send',
        post_ids: post_ids,
        //date_time_array: dateTimeValues
        date_time_array: JSON.stringify(dateTimeValues),
        //include chosen sherpa service here
        chosen_delivery_array: JSON.stringify(chosenOptionValues)
      }
      //debugger;
			
			if (confirm("The selected order/s will be sent to Sherpa.Please log in to your Sherpa user account to see applicable cost, or if you need to make additional changes.") == true) {
				jQuery.post(sherpa.ajax_url, formData, function(response) {
					//responsej = JSON.parse(response);
					// setInterval('location.reload()', 2000);
					jQuery('form#sherpa_post').submit();
				});
			} else {
				return false;
			}            
    } else {
        alert('Please select Order-ID');
    }
  });

  // WIP: Ajax push date and time to sherpa
  // jQuery(document).on('change', '.del_date_viewddxx', function(e) {
  //   e.preventDefault();
  //   var formData = {
  //     action: 'my_ajax_edit_sherpa_date_time',
  //     nonce: sherpa.nonce,
  //     type: 'edit',
  //     date_time: jQuery(this).val(),
  //     post_ids: jQuery(this).attr('data-ordid')
  //   }
  //   jQuery.post(sherpa.ajax_url, formData, function(response) {
  //   });
  // });

	
  jQuery(document).on('change', '.del_packages', function(e) {
    e.preventDefault();
    var formData = {
      action: 'my_ajax_edit_sherpa_packages',
      nonce: sherpa.nonce,
      type: 'edit',
      del_packages: jQuery(this).val(),
      post_ids: jQuery(this).attr('data-ordid')
    }
    jQuery.post(sherpa.ajax_url, formData, function(response) {
    });
  });

// Update Sherpa option on selection in STS page
  jQuery(document).on('change', '.del_options', function(e) {
    e.preventDefault();
    var formData = {
      action: 'my_ajax_edit_sherpa_options',
      nonce: sherpa.nonce,
      type: 'edit',
      del_options: jQuery(this).val(),
      post_ids: jQuery(this).attr('data-ordid')
    }
    jQuery.post(sherpa.ajax_url, formData, function(response) {
    });
  });

  // Update Sherpa date and time on selection in STS page
  jQuery(document).on('change', '.del_date_viewddxx', function(e) {
    e.preventDefault();
    var formData = {
      action: 'my_ajax_edit_sherpa_date_time',
      nonce: sherpa.nonce,
      type: 'edit',
      date_time_values: jQuery(this).val(),
      post_ids: jQuery(this).attr('data-ordid')
    }
    jQuery.post(sherpa.ajax_url, formData, function(response) {
    });
  });
	
	
  jQuery(document).on('change', '.del_win_changes', function(e) {
    e.preventDefault();
    var set_time = this.value;
    var formData = {
        action: 'my_ajax_time_sherpa_post',
        nonce: sherpa.nonce,
        type: 'edit',
        post_ids: jQuery(this).attr('data-ordid'),
        set_time: set_time
    }
    jQuery.post(sherpa.ajax_url, formData, function(response) {
    })
  })
    // update on runtime 	 
    jQuery(".sherpa_update").click(function(event) {
      event.preventDefault();
      var del_date = jQuery('input#del_date').val(); // date time field
      var del_option = jQuery('select#del_options').val();
      var del_win = jQuery('select#sherpa_method_option').val();
      var packages = jQuery('select#packages').val();
      var authority_to_leave = jQuery(".extttfddd").find("input[name='leave_unattended']").is(':checked');
      var specified_recipient = jQuery(".extttfddd").find("input[name='specified_recipient']").is(':checked');
      var contain_fragile = jQuery(".extttfddd").find("input[name='fragile']").is(':checked');
      var prescription_meds = jQuery(".extttfddd").find("input[name='prescription_meds']").is(':checked');
      var send_sms = jQuery(".extttfddd").find("input[name='send_sms']").is(':checked');
      var contain_alcohol = jQuery(".extttfddd").find("input[name='alcohol']").is(':checked');
      var contain_tobacco = jQuery(".extttfddd").find("input[name='tobacco']").is(':checked');
      var high_vis = jQuery(".extttfddd").find("input[name='high_vis']").is(':checked');
      var formData = {
        action: 'my_ajax_set_sherpa_post_action',
        nonce: sherpa.nonce,
        type: 'save',
        del_date: del_date,
        del_option: del_option,
        sherpa_method_option: del_win,
        packages: packages,
        authority_to_leave: authority_to_leave,
        specified_recipient: specified_recipient,
        contain_fragile: contain_fragile,
        prescription_meds: prescription_meds,
        send_sms: send_sms,
        contain_alcohol: contain_alcohol,
        contain_tobacco: contain_tobacco,
        high_vis: high_vis,
        post_ids: jQuery('#post_ids').val(),
      }
      jQuery.ajax({
        'url': sherpa.ajax_url,
        'type': 'POST',
        'data': formData,
        'success': function(data) {
          jQuery('form#test').submit();
          // jQuery(".extttfddd").modal("hide");
        },
      })
    })
	
    jQuery(document).on('click', '#daily_pref', function(e) {
      event.preventDefault();
      jQuery('.delivery_prefrences').attr('pref_id', '');
      jQuery('.delivery_prefrences').attr('pref_id', jQuery(this).attr('class').split(" ")[1]);
      let post_ids = jQuery('input[name="post[]"]').map(function() {
        return this.value;
      }).get().join();
      jQuery('#viewModal #post_ids').val(post_ids);
      jQuery('#viewModal').modal('show');
      var formData = {
        action: 'my_ajax_view_pop_up_shepa_action',
        nonce: sherpa.nonce,
        type: 'view',
        post_ids: jQuery(this).attr('class').split(" ")[1],
      }
      jQuery.post(sherpa.ajax_url, formData, function(response) {
        var response_json = JSON.parse(response);
        jQuery.each(response_json, function(key, item) {
          if (item == 'true' || item == '1') {
              var check = true;
          } else {
              var check = false;
          }
          jQuery('#viewModal').find('#' + key).prop("checked", check);
          jQuery('#exampleModal').find('#' + key).prop("checked", check);
        });
      });
    });
	
    jQuery(".view_update").click(function(event) {
      event.preventDefault();
      var authority_to_leave = jQuery(".delivery_prefrences").find("input[name='leave_unattended']").is(':checked');
      var specified_recipient = jQuery(".delivery_prefrences").find("input[name='specified_recipient']").is(':checked');
      var contain_fragile = jQuery(".delivery_prefrences").find("input[name='fragile']").is(':checked');
      var prescription_meds = jQuery(".delivery_prefrences").find("input[name='prescription_meds']").is(':checked');
      var send_sms = jQuery(".delivery_prefrences").find("input[name='send_sms']").is(':checked');
      var contain_alcohol = jQuery(".delivery_prefrences").find("input[name='alcohol']").is(':checked');
      var contain_tobacco = jQuery(".delivery_prefrences").find("input[name='tobacco']").is(':checked');
      var high_vis = jQuery(".delivery_prefrences").find("input[name='high_vis']").is(':checked');
      var formData = {
        action: 'my_ajax_view_update_sherpa_action',
        nonce: sherpa.nonce,
        type: 'update',
        authority_to_leave: authority_to_leave,
        specified_recipient: specified_recipient,
        contain_fragile: contain_fragile,
        prescription_meds: prescription_meds,
        send_sms: send_sms,
        contain_alcohol: contain_alcohol,
        contain_tobacco: contain_tobacco,
        high_vis: high_vis,
        post_ids: jQuery('.delivery_prefrences').attr('pref_id'),
      }
      jQuery.ajax({
        'url': sherpa.ajax_url,
        'type': 'POST',
        'data': formData,
        'success': function(data) {
          jQuery("#viewModal").modal("hide");
        },
      })
      jQuery('form#view').submit();	
    });
	
    jQuery('.del_options_s').change(function() {
      var set_interval = this.value;
		  let post_ids = jQuery('input[name="post[]"]:checked').map(function() {
		  	return this.value;
		  }).get().join();
      jQuery('#sherpa_method_option').empty();
		
		  jQuery('.loaderdisdelwinsec').show();
      var formData = {
        action: 'my_ajax_select_shepa_update_action',
        nonce: sherpa.nonce,
        type: 'changes',
        post_ids: post_ids,
        set_interval: set_interval
      }

        jQuery.post(sherpa.ajax_url, formData, function(response) {
          data_sherpa = JSON.parse(response);
			
          jQuery.each(data_sherpa[0][0], function(key, item) {
            Object.prototype.toString = function() {
              return JSON.stringify(item)
            }
            slots = slotv = item.slot_start_time + ' - ' + item.slot_end_time;
            //slotv.split(":")[0]+slotv.split(" ")[1]+' - '+slotv.split(" ")[3].split(":")[0]+ slotv.split(" ")[4]
                
            jQuery('#sherpa_method_option').append(jQuery("<option value=" + slots + ">" + slots + "</option>").val(slots));
				    jQuery('.loaderdisdelwinsec').hide();
			    });
        })
    });
	


    // Get the current date and time
    var currentDate = new Date(); //Thu Jun 15 2023 16:41:14 GMT+1000 (Australian Eastern Standard Time)
    var currentDateOnly = currentDate.toISOString().split('T')[0]; //2023-06-15
    var currentDateTimeX = currentDate.toISOString().slice(0, 16); //2023-06-15T06:45
    var currentDateTime = currentDate.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' }); //16:41
    var formattedCurrentDate;
    console.log('formattedCurrentDate: '+formattedCurrentDate);
    console.log('Current Date Only: '+currentDateOnly);
    console.log('Current time: ' +currentDateTime);
    console.log('CurrentDateTimeX: '+currentDateTimeX);

    // Set the minimum value of the datetime picker to the current date and time
      jQuery('.del_date_viewddxx').attr('min', currentDateTimeX);
      // var delDateTimeValue = jQuery('.del_date_viewddxx').val();
      // jQuery.each(delDateTimeValue, function(key, item) {
      //   console.log('TESTING: '+key+item);
      // });

      //jQuery(".del_date_viewddxx").each(function(){
      jQuery('input[type="datetime-local"].del_date_viewddxx').each(function(){
        var del_date_onload = jQuery(this).val();
        var currentDate = new Date();
        var hours = currentDate.getHours();
        var minutes = currentDate.getMinutes();
        //var formattedCurrentDate = currentDate.toISOString().substring(0,16);
        var formattedCurrentDate = currentDate.toISOString().substring(0,10)+ ' ' + ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2);
        //var formattedCurrentDate = currentDate.toISOString().substring(0,10)+ ' ' + ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2);
        //formattedCurrentDate = jQuery.format.date(formattedCurrentDate, 'yyyy-MM-dd HH:mm');

        //Compare the date with the current date
        if(new Date(del_date_onload) < currentDate) {
          // jQuery('#error-message').text('Please select a date that is not in the past.');
          // console.log('Past Date found: '+del_date_onload);
          //var message = '<div style="color: #c4105a">This date has passed.</div>';
          //jQuery(this).after(message);
          jQuery(this).val(formattedCurrentDate);
          console.log(currentDate);
          console.log('FormattedCurrentDate: '+formattedCurrentDate);
        }

      });

      

    // Disable specific days
    // jQuery('.del_date_viewddxx').on('change', function(){
    //   var currentDateOnly = currentDate.toISOString().split('T')[0]; //2023-06-15
    //   var selectedDateTime = new Date(jQuery(this).val());
    //   var selectedTime = selectedDateTime.toTimeString().slice(0, 16);

    //   let year = selectedDateTime.getFullYear();
    //   //let month = selectedDateTime.getMonth();
    //   let month = ('0' + (selectedDateTime.getMonth() + 1)).slice(-2);
    //   //let day = selectedDateTime.getDate();
    //   let day = ('0' + selectedDateTime.getDate()).slice(-2);
    //   let hours = selectedDateTime.getHours();
    //   //let hours = ('0' + selectedDateTime.getHours()).slice(-2);
    //   //let minutes = selectedDateTime.getMinutes();
    //   let minutes = ('0' + selectedDateTime.getMinutes()).slice(-2);

    //   // Create the formatted date and time string
    //   var formattedDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    //   console.log('formattedDateTime : '+formattedDateTime);

    //   console.log('Selected time: ' +currentDateTime);

    //   var selectedDateString = selectedDateTime.toISOString().split('T')[0];
    //   console.log(selectedDateString);

    //   console.log('Selected date string : '+selectedDateString);
    //   console.log('current date only : '+currentDateOnly);
    //   console.log('selectedTime: '+selectedTime);
    //   console.log('currentDateTime: '+currentDateTime);
    //   console.log('selectedDateTime: '+selectedDateTime);

    //   // Check if past time selected
    //   if( (selectedDateString === currentDateOnly) && (selectedTime < currentDateTime) ){
    //     jQuery(this).next('#error-message').text('Please select a time that is not in the past.');
    //     jQuery(this).val(currentDateTime);
    //   }
    //   // Check if past date selected
    //   else if(selectedDateTime < new Date()){
    //     jQuery(this).next('#error-message').text('Please select a date that is not in the past.');
    //     jQuery(this).val('');
    //   }
    //   else{
    //     jQuery('#error-message').text('');
    //   }
    // })

    //Test 
    jQuery('.del_date_viewddxx').on('change', function () {
      console.log('//////On Date Time Change//////');
      var currentDate = new Date(); // Get the current date and time
      var selectedDateTimeText = jQuery(this).val();
      var selectedDateTime = new Date(jQuery(this).val()); // Get the chosen date and time

      //this.val() = selectedDateTime;

      var selectedDatepickerValue = jQuery(this).val();
      var chosenTime = selectedDatepickerValue.substring(11);
      console.log(typeof selectedDatepickerValue);
      console.log("Chosen value"+selectedDatepickerValue);
      console.log('Chosen time'+chosenTime);
      console.log('Current Date'+currentDate);
      console.log('Selected Date'+selectedDateTime);

      //Dummy current Time
      var currentTime = '08:52';

      if (chosenTime < currentTime) { // HH:MM
        console.log('Past time selected, Date not checked');
      }


    
      // Check if past time selected
      if (selectedDateTime < currentDate) {
        jQuery(this).next('#error-message').text('Please select a date and time that is not in the past.');
        //jQuery(this).val(currentDate.toISOString().slice(0, 16));
        jQuery(this).attr('value',(currentDate.toISOString().slice(0, 16)));
        return;
      }
    
      // Check if past date selected
      // else if (selectedDateTime < currentDate) {
      //   jQuery(this).next('#error-message').text('Please select a date that is not in the past.');
      //   jQuery(this).val('');
      //   return;
      // }



      else {
        jQuery(this).next('#error-message').text('');
        jQuery(this).attr('value', selectedDateTimeText);
      }
    
      // Reset the error message if everything is valid
      //jQuery('#error-message').text('');
    
      // Format the selected date and time
      var formattedDateTime = selectedDateTime.toISOString().slice(0, 16); // Change this to NZ timezone
      console.log('formattedDateTime: ' + formattedDateTime);
    });
  // Test
  //Custom date time selection
    // jQuery(".del_date_viewdd").Zebra_DatePicker({
    //   changeMonth: true,
    //   changeYear: true,
		//   // 1,2,3,4,5
		//   // * * * 0,6
    //   showButtonPanel: false,
		//   direction: 1,
		//   direction: [sherpa.next_available, false],
    //   format: 'd F Y',
    //   //format:'d F Y H:i',
    //   //startDate: new Date(),
    //   onSelect: function(date) {
    //     jQuery(this).change();
		// 	  var dd = jQuery(this).val(); //23 March 2023
		// 	  var originalDateStr = dd;
		// 	  var originalDate = new Date(originalDateStr);
		// 	  var monthsArr = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		// 	  var month = originalDate.getMonth();
		// 	  var day = originalDate.getDate();
		// 	  var year = originalDate.getFullYear();
		// 	  var monthName = monthsArr[month];
		// 	  var dd = day + ' ' + monthName + ' ' + year;
		// 	  jQuery(this).val(dd);
		// 	  jQuery(this).val(jQuery(this).val().split(' ')[0]+' '+jQuery(this).val().split(' ')[1]); //23 March
    //       var formData = {
    //         action: 'my_ajax_select_shepa_date_action',
    //         nonce: sherpa.nonce,
    //         type: 'changes',
    //         set_time: dd,
    //         // thisdate: jQuery(this).context.value,
    //         post_ids: jQuery(this).attr('data-ordid')
    //       }
    //       jQuery.post(sherpa.ajax_url, formData, function(response) {
    //       })
    //   }
    // });
	
	// var rest = sherpa.sherpa_delivery_settings_operating_day;
  // console.log('Rest:'+rest);
	// if(rest){
	// 	jQuery(".del_date_viewdd").Zebra_DatePicker({
	// 		disabled_dates: ['* * * '+sherpa.sherpa_delivery_settings_operating_day+''],
  //     format:'d F Y H:i',
  //     //startDate: new Date(),
	// 		onSelect: function(date) {   
  //       jQuery(this).change();
  //       var dd = jQuery(this).val(); //23 March 2023
  //       var originalDateStr = dd;
  //       var originalDate = new Date(originalDateStr);
  //       var monthsArr = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  //       var month = originalDate.getMonth();
  //       var day = originalDate.getDate();
  //       var year = originalDate.getFullYear();
  //       var monthName = monthsArr[month];
  //       var dd = day + ' ' + monthName + ' ' + year;
  //       jQuery(this).val(dd);
                    			
  //       jQuery(this).val(jQuery(this).val().split(' ')[0]+' '+jQuery(this).val().split(' ')[1]); //23 March
  //         var formData = {
  //             action: 'my_ajax_select_shepa_date_action',
  //             nonce: sherpa.nonce,
  //             type: 'changes',
  //             set_time: dd,
  //             // thisdate: jQuery(this).context.value,
  //             post_ids: jQuery(this).attr('data-ordid')
  //         }
  //         jQuery.post(sherpa.ajax_url, formData, function(response) {
  //         })
  //     }
	// 	});
	// }

	//var restnext_available = sherpa.next_available;
  //var direction = sherpa.next_available ? [sherpa.next_available +' 00:00', false] : true;
	// if(restnext_available || rest){
  //   var disabledDates = (sherpa.sherpa_delivery_settings_operating_day) ? ['* * * '+sherpa.sherpa_delivery_settings_operating_day+''] : '0,1';
  //   console.log(disabledDates);
  //   console.log(sherpa.next_available);
	// 	jQuery(".del_date_viewdd").Zebra_DatePicker({
  //     //format:'d F Y H:i:s',
  //     // format:'d F Y',
  //     format:"Y-m-d H:i", //Enables time field selection but delivery windows remain unchanged
  //     //Notes to self
  //     //BUG: Enabling time also enables past date selection need to investigate further
  //     //When sent to sherpa only the selected date is updated in order note along with a default delivery window
  //     //occasional crashes - Clear send to sherpa pile and create new orders
  //     //Code cleanup
  //     startDate: new Date(),
  //     show_select_time: true,
	// 		direction: direction, 
  //     //disabled_dates: ['* * * '+sherpa.sherpa_delivery_settings_operating_day+''],
	// 		disabled_dates: disabledDates,
	// 		onSelect: function(date) {
  //             jQuery(this).change();
  //       			// var dd = jQuery(this).val(); //23 March 2023
  //             // debugger;
  //       			// var originalDateStr = dd;
  //       			// var originalDate = new Date(originalDateStr);
  //       			// var monthsArr = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  //       			// var month = originalDate.getMonth();
  //       			// var day = originalDate.getDate();
  //       			// var year = originalDate.getFullYear();
  //             // //var time = originalDate.getTime();
  //       			// var monthName = monthsArr[month];
  //       			// var dd = day + ' ' + monthName + ' ' + year;
  //       			//jQuery(this).val(dd);
  //       			//jQuery(this).val(jQuery(this).val().split(' ')[0]+' '+jQuery(this).val().split(' ')[1]); //23 March
  //               var formData = {
  //                   action: 'my_ajax_select_shepa_date_action',
  //                   nonce: sherpa.nonce,
  //                   type: 'changes',
  //                   set_time: jQuery(this).val(),
  //                   // thisdate: jQuery(this).context.value,
  //                   post_ids: jQuery(this).attr('data-ordid')
  //               }
  //               console.log(formData);
  //               jQuery.post(sherpa.ajax_url, formData, function(response) {
  //               })
  //     }
	// 	});
	// }
});