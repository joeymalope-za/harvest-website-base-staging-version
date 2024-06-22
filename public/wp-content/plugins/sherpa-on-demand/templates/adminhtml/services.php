<style>
  .sherpa-delivery-options {
    margin: 20px 0;
    width: 95%;
    max-width: 100%;
    background-color: white;
    border: 1px solid #8c8f94;
    padding: 10px 30px 25px 30px;
    border-radius: 5px;
    display: grid;
  }

  .custom-tooltip{
    border: 2px solid black;
    /* width: fit-content; */
    width: 13px;
    height: 13px;
    text-align: center;
    border-radius: 50%;
    font-family: inherit;
    font-weight: 700;
    font-size: x-small;
  }

  .delivery-option {
    margin: 20px 0;
    border: 1px solid;
    width: 90%;
  }

  .delivery-option-entry {
    margin-bottom: 20px;
  }

  .delivery-option-entry label {
    font-weight: 600;
  }

  .delivery-option-entry p.description {
    padding-left: 24px;
  }

  tr:nth-child(even) td{
    background-color: #f2f2f2;
    outline: 2px solid #f2f2f2;
  }   
</style>

<?php

$conf = new Sherpa_Configurations(); // Sherpa settings

if (!isset($this->services) || empty($this->services)) // Labels
  return;

$this->custom_services = $this->get_option('services', array());
$custom_services = $this->get_option('services', array());

// Sort
$sort = 0;
$this->ordered_services = array();

if (isset($this->services) && $this->services) {
  foreach ($this->services as $code => $values) {
    if (isset($this->custom_services[$code]['order'])) {
      $sort = $this->custom_services[$code]['order'];
    }

    while (isset($this->ordered_services[$sort])) {
      $sort++;
    }

    $this->ordered_services[$sort] = array($code, $values);
    $sort++;
    }
}

?>
<tr valign="top">
  <td class="titledesc" colspan="2" style="padding-left: 0px">
    <div class="sherpa-delivery-options">
      <h2><?php _e('Delivery Services', 'sherpa'); ?></h2>
      <p style="font-weight: 500">Select and label the Sherpa delivery services you wish your customer to see at checkout.</p>
      <p class="description">When selected, these options will be shown to your customers at checkout. To learn more about our delivery services, <a target="_blank" href="https://help.auuser.sherpadelivery.com/hc/en-us/articles/4409706678029-What-different-delivery-options-do-I-have-to-choose-from-">click here</a>.</p>
            
      <div class="delivery-option" onload="checkServices()">
        <?php 
        //Looping through options
        foreach ($this->ordered_services as $key => $value) {
          $code = isset($value[0]) ? $value[0] : '';
          $values = isset($value[1]) ? $value[1] : array();
          $service_name = isset($values['name']) ? $values['name'] : '';
          $code_refined = str_replace('service_','', $code);
                  

          if (!isset($this->custom_services[$code])) {
            $this->custom_services[$code] = array();
          }
          ?>
          <?php
          $code_heading = ($code == 'service_sameday')? 'Allow deliveries on same day' : 'Allow delivery dates in the future'; 
          ?>
          <table style="float: left; width: 50%">

            <tr>
              <?php if($code=='service_sameday'){?>
                <td><strong>Sherpa service</strong></td>
              <?php } ?>
              <td>
                <input id="<?php echo $code.'_checkbox'; ?>" type="checkbox" name="<?php echo $code.'_checkbox'; ?>" <?php echo "checked"; ?> style="float: right; margin-top:-27px;" />   
              </td>
              <td colspan="2">
                <div style="margin-bottom: 10px;"> <strong> <?php echo $code_heading; ?> </strong> </div>
                <!-- <input class="form-control" style="display: block;" id="sherpa_settings_sameday_delivery_options_sameday" name="sherpa_settings_sameday_delivery_options_sameday" required type="text" value="<?php //echo $conf->getSamedayDeliveryOptionsSameday(); ?>" /> -->
                <input class="form-control" style="display: block;" id="<?php echo 'sherpa_settings_'.$code_refined.'_delivery_options_'.$code_refined; ?>" name="<?php echo 'sherpa_settings_'.$code_refined.'_delivery_options_'.$code_refined; ?>" required type="text" value="<?php echo trim($service_name) ?>" />
              </td>
            </tr>

            <!-- <h2><?php //echo trim($service_name) ?></h2> -->
            <?php if (isset($values['services'])) : ?>

              <?php
                foreach ($values['services'] as $key => $name) :

                  $help_text = '';

                  if ('service_1hr' == $key) {
                    $service_label = '1 hour delivery';
                  }

                  elseif ('service_2hr' == $key) {
                    $help_text = __('Guaranteed if pickup before 7pm');
                    $service_label = '2 hour delivery';
                  }

                  elseif ('service_4hr' == $key) {
                    $help_text = __('Guaranteed if pickup before 3pm');
                    $service_label = '4 hour delivery';
                  }

                  elseif ('service_at' == $key) {
                    $help_text = __('Guaranteed when logged before 12pm (deliver by 5pm) or before 2pm (deliver by 7pm)');
                    $service_label = 'Same day delivery';
                  }

                  elseif ('service_bulk_rate' == $key) {
                    $service_label = 'Bulk rate delivery';
                  }

                  // Checked or not
                  $checked = checked(
                    (isset($this->custom_services[$code][$key]['enabled']) && !empty($this->custom_services[$code][$key]['enabled'])),
                    true,
                    false
                  );

                  // Prepare checkbox key
                  $checkbox_key = "sherpa_service_{$code}_{$key}";
                  $codeForLable = str_replace("service_","", $code).'_delivery_options_'.$key;
                  $nameForCheckbox = "sherpa_service[".$code."][".$key."][enabled]";
              ?>
              <div class="delivery-option-entry">
                 
              <?php 
                if((get_option($key.'_enabled')) || (in_array($key, ['service_2hr', 'service_4hr'])) ){
              ?> 
              <tr>
                  

                  <?php if($code == 'service_sameday'){  ?>
                    <td>
                      <label for="<?php echo $checkbox_key; ?>"> <strong> <?php echo $service_label; ?> </strong> </label>
                    </td>
                  <?php } ?>

                  <td>
                    <input class="<?php echo $code.'_option_checkbox'; ?>" id="<?php echo $checkbox_key; ?>" type="checkbox" name="sherpa_service[<?php echo $code; ?>][<?php echo $key; ?>][enabled]" <?php echo $checked; ?> style="float: right; margin-top:2px;" />   
                  </td>
                                
                  <td>      
                    <div class="col-sm-3">
                      <input id="sherpa_settings_<?php echo $codeForLable; ?>" 
                      name="sherpa_settings_<?php echo $codeForLable; ?>" required type="text" 
                      value="<?php echo $conf->getData($codeForLable) ?>" 
                      <?php //echo (!isset($_POST[$nameForCheckbox]))? "disabled" : "not"; ?>
                      <?php echo $checked ? " " : "disabled" ?>
                      />
                      <?php 
                      if(isset($_POST['mainform'])){
                        if(!isset($_POST[$nameForCheckbox])){
                          echo $nameForCheckbox.' is disabled';
                        }else{
                          echo 'Checkbox: '.$_POST[$nameForCheckbox];
                        }
                      }?>
                    </div> 
                  </td>

                  
                </tr>
                <?php 
                  } 
                ?>
                              
                <?php 
                  //Update db if lable is changed
                  if(isset($_POST['sherpa_settings_'.$codeForLable]) && $_POST['sherpa_settings_'.$codeForLable] != " "){
                    update_option('sherpa_settings_'.$codeForLable, $_POST['sherpa_settings_'.$codeForLable]); 
                    header("Refresh:0");
                  }
                      
                  if(isset($_POST['sherpa_settings_sameday_delivery_options_sameday']) && $_POST['sherpa_settings_sameday_delivery_options_sameday'] != " "){
                    update_option('sherpa_settings_sameday_delivery_options_sameday', $_POST['sherpa_settings_sameday_delivery_options_sameday']); 
                    header("Refresh:0");
                    //sherpa_settings_sameday_delivery_options_sameday
                  }

                  if(isset($_POST['sherpa_settings_later_delivery_options_later']) && $_POST['sherpa_settings_later_delivery_options_later'] != " "){
                    update_option('sherpa_settings_later_delivery_options_later', $_POST['sherpa_settings_later_delivery_options_later']); 
                    header("Refresh:0");
                    //sherpa_settings_later_delivery_options_later
                  }
                ?>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </table> 
        <?php } //loop ends ?>
      </div>
      
    
      <?php 
      $sherpa_settings = get_option('woocommerce_sherpa_settings');

      if (is_array($sherpa_settings) && array_key_exists('show_timeslot_checkbox', $sherpa_settings)){
        $timeslotCheckboxDb = $sherpa_settings['show_timeslot_checkbox']; // gets checkbox value from database 
      } else {
        $timeslotCheckboxDb = "checked"; // checked is checked by default for new installs/updates
      }
      ?>

      <div style="width: 80%;">
        <input id="show_timeslot_checkbox" type="checkbox" name="show_timeslot_checkbox" <?php echo ($timeslotCheckboxDb)? 'checked': ''; ?> style="float: left; margin-top: 6px;" />             
        <div style="margin-left: 35px;">
          <p><strong>Show delivery timeslots at checkout</strong></p>
          <p>When selected, customer will be prompted to choose the time they wish to receive their delivery. This option only applies when delivery is requested on day of order. If customer selects a delivery date in the future, timeslots will always be shown.</p>          
        </div>
      </div>

      <?php
      $isChecked = isset($_POST['show_timeslot_checkbox']) ? true : false; 
      if($_POST){ // Check if form is submitted
        $this->update_option('show_timeslot_checkbox', $isChecked, 1); //updates in db
        error_log('Form submitted, checkbox value is '.get_option('show_timeslot_checkbox'));
      } else {
        error_log('Page refreshed');
        error_log('Checkbox value from db '.$this->get_option('show_timeslot_checkbox'));
      }
      
      ?>
      
      
      <?php if(!get_option('service_at_enabled') && !get_option('service_bulk_rate_enabled')){ ?>
        <div style="width: 80%; background: #f7f9fb; padding: 10px 7px; padding-top: 3px; margin-top: 20px">
          <a data-html="true" data-toggle="tooltip" href="javascript:void(0);" title="" style="color: #000; float: left; margin-top: 6px; text-decoration: none">
            <i class="fa fa-question tooltipHelp"></i>
            <div class="custom-tooltip">i</div>
          </a>
          <div style="margin-left: 28px">
            <p><strong>Logging more than 20 deliveries a day?</strong></p>
            <p>You may be eligible for volume delivery discounts. Please reach out to your Sherpa Account Manager or email <a href="mailto:sales@sherpa.net.au">sales@sherpa.net.au</a> for more information.</p>
          </div>
        </div>
      <?php } ?>

      <!-- <p class="description">* Important: If a customer chooses a date in the future you are unable to fulfill, be sure to cancel that job in your <a target="_blank" href="https://deliveries.sherpa.net.au/users/reports/current_deliveries">Sherpa Delivery Account Dashboard</a> and contact your customer to arrange a different delivery time.</p> -->
    </div>
  </td>
</tr>

<script>
  var samedayOptionsCheckboxes = ['sherpa_service_service_sameday_service_1hr', 'sherpa_service_service_sameday_service_2hr', 'sherpa_service_service_sameday_service_4hr', 'sherpa_service_service_sameday_service_at', 'sherpa_service_service_sameday_service_bulk_rate'];
  var laterOptionsCheckboxes = ['sherpa_service_service_later_service_1hr', 'sherpa_service_service_later_service_2hr', 'sherpa_service_service_later_service_4hr', 'sherpa_service_service_later_service_at', 'sherpa_service_service_later_service_bulk_rate'];

  switch(this.id) {
    case 'sherpa_service_service_sameday_service_1hr':
      serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_1hr';
      break;
    case 'sherpa_service_service_sameday_service_2hr':
      serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_2hr';
      break;
    case 'sherpa_service_service_sameday_service_4hr':
      serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_4hr';
      break;
    case 'sherpa_service_service_sameday_service_at':
      serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_at';
      break;
    case 'sherpa_service_service_sameday_service_bulk_rate':
      serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_bulk_rate';
      break;
    case 'sherpa_service_service_later_service_1hr':
      serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_1hr';
      break;
    case 'sherpa_service_service_later_service_2hr':
      serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_2hr';
      break;
    case 'sherpa_service_service_later_service_4hr':
      serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_4hr';
      break;
    case 'sherpa_service_service_later_service_at':
      serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_at';
      break;
    case 'sherpa_service_service_later_service_bulk_rate':
      serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_bulk_rate';
      break;
    default:
      // code block
  }

  var todayAllChecked = document.querySelectorAll('.service_sameday_option_checkbox:checked');
    if(todayAllChecked.length === 0){ //today all unchecked
      document.getElementById('service_sameday_checkbox').checked = false;
    }

  var laterAllChecked = document.querySelectorAll('.service_later_option_checkbox:checked');
    if(laterAllChecked.length === 0){ //later all unchecked
      document.getElementById('service_later_checkbox').checked = false;
    }
  

  function checkAllSamedayOptions() {

    if (document.getElementById('service_sameday_checkbox').checked) {
      samedayOptionsCheckboxes.forEach(function(samedayCheckBox) {
        if (!checkIfElementExists(samedayCheckBox)) {
          return;
        }
        document.getElementById(samedayCheckBox).checked = true;
        //Converting each checkbox id to lable textbox id
        var samedayLabelTextBox = samedayCheckBox.replace('sherpa_service_service_sameday_service_','sherpa_settings_sameday_delivery_options_service_');
        document.getElementById(samedayLabelTextBox).disabled = false;
      });
    } else {
        samedayOptionsCheckboxes.forEach(function(samedayCheckBox) {
          if (!checkIfElementExists(samedayCheckBox)) {
            return;
          }
          document.getElementById(samedayCheckBox).checked = false;
          //Converting each checkbox id to lable textbox id
          var samedayLabelTextBox = samedayCheckBox.replace('sherpa_service_service_sameday_service_','sherpa_settings_sameday_delivery_options_service_');
          document.getElementById(samedayLabelTextBox).disabled = true;
        });
    }
  }

  function checkAllLaterOptions() {

    if (document.getElementById('service_later_checkbox').checked) {
      laterOptionsCheckboxes.forEach(function(laterCheckBox) {
        if(!checkIfElementExists(laterCheckBox)){
          return;
        }
        document.getElementById(laterCheckBox).checked = true;
        //Converting each checkbox id to lable textbox id
        var laterLabelTextBox = laterCheckBox.replace('sherpa_service_service_later_service_','sherpa_settings_later_delivery_options_service_');
        document.getElementById(laterLabelTextBox).disabled = false;
      });
    } else {
        laterOptionsCheckboxes.forEach(function(laterCheckBox) {
          if(!checkIfElementExists(laterCheckBox)){
            return;
          }
          document.getElementById(laterCheckBox).checked = false;
          //Converting each checkbox id to lable textbox id
          var laterLabelTextBox = laterCheckBox.replace('sherpa_service_service_later_service_','sherpa_settings_later_delivery_options_service_');
          document.getElementById(laterLabelTextBox).disabled = true;
        });
    }
  }

  document.getElementById('service_sameday_checkbox').addEventListener('change', checkAllSamedayOptions);
  document.getElementById('service_later_checkbox').addEventListener('change', checkAllLaterOptions);

  function disableUncheckedFields(){
    var serviceTextBoxId = '';

    switch(this.id) {
      case 'sherpa_service_service_sameday_service_1hr':
        serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_1hr';
        break;
      case 'sherpa_service_service_sameday_service_2hr':
        serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_2hr';
        break;
      case 'sherpa_service_service_sameday_service_4hr':
        serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_4hr';
        break;
      case 'sherpa_service_service_sameday_service_at':
        serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_at';
        break;
      case 'sherpa_service_service_sameday_service_bulk_rate':
        serviceTextBoxId = 'sherpa_settings_sameday_delivery_options_service_bulk_rate';
        break;
      case 'sherpa_service_service_later_service_1hr':
        serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_1hr';
        break;
      case 'sherpa_service_service_later_service_2hr':
        serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_2hr';
        break;
      case 'sherpa_service_service_later_service_4hr':
        serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_4hr';
        break;
      case 'sherpa_service_service_later_service_at':
        serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_at';
        break;
      case 'sherpa_service_service_later_service_bulk_rate':
        serviceTextBoxId = 'sherpa_settings_later_delivery_options_service_bulk_rate';
        break;
      default:
        // code block
    }

    if(this.checked) {
      document.getElementById(serviceTextBoxId).disabled = false;
    }else{
      document.getElementById(serviceTextBoxId).disabled = true;
    }

  }
 
  /**
  * Returns true if element exists on webpage
  */
  function checkIfElementExists(elem) {
    return document.getElementById(elem);
  }

  /**
  * Run javascript after page load
  */
  window.addEventListener("load", (event) => {
    checkIfElementExists('sherpa_service_service_sameday_service_1hr') &&     document.getElementById('sherpa_service_service_sameday_service_1hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_sameday_service_2hr') &&     document.getElementById('sherpa_service_service_sameday_service_2hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_sameday_service_4hr') &&     document.getElementById('sherpa_service_service_sameday_service_4hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_sameday_service_at') &&     document.getElementById('sherpa_service_service_sameday_service_at').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_sameday_service_bulk_rate') &&     document.getElementById('sherpa_service_service_sameday_service_bulk_rate').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_later_service_1hr') &&     document.getElementById('sherpa_service_service_later_service_1hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_later_service_2hr') &&     document.getElementById('sherpa_service_service_later_service_2hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_later_service_4hr') &&     document.getElementById('sherpa_service_service_later_service_4hr').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_later_service_at') &&     document.getElementById('sherpa_service_service_later_service_at').addEventListener('change', disableUncheckedFields);
    checkIfElementExists('sherpa_service_service_later_service_bulk_rate') &&     document.getElementById('sherpa_service_service_later_service_bulk_rate').addEventListener('change', disableUncheckedFields);

});

</script>
