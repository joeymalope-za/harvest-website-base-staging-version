<?php
$conf = new Sherpa_Configurations();
$operating_time_value = $conf->getOperatingTimeWrapper();
$cutoff_time = $conf->getCutoffTime();
$operating_time = explode(', ', $operating_time_value);

$operating_time_to = '21:00';
if (isset($operating_time[0])) {
    $operating_time_from = $operating_time[0];
    if (false === strpos($operating_time_from, ':')) {
        $operating_time_from .= ':00';
    }

    if (isset($operating_time[1])) {
        $operating_time_to = $operating_time[1];
        if (false === strpos($operating_time_to, ':')) {
            $operating_time_to .= ':00';
        }
    }
} else {
    $operating_time_from = '07:00';
}

list($hour_from, $minute_from) = explode(':', $operating_time_from);
list($hour_to, $minute_to) = explode(':', $operating_time_to);

// Convert 24hours format to 12hours format
$time1 = date("g:i A", strtotime($operating_time_from));
$time2 = date("g:i A", strtotime($operating_time_to));

// Get slider ranges values
$operating_slider1 = $hour_from * 60 + $minute_from;
$operating_slider2 = $hour_to * 60 + $minute_to;

// Delivery rates
$delivery_rates = [
    [
      'id' => 'SR',
      'label' => __('Sherpa rate', 'sherpa'),
    ],
    [
      'id' => 'FL',
      'label' => __('Flat rate', 'sherpa'),
    ],
    [
      'id' => 'MR',
      'label' => __('Margin', 'sherpa'),
    ],
];

?>

<script type="text/javascript">
  var operating_time1 = '<?= $operating_slider1 ?>';
  var operating_time2 = '<?= $operating_slider2 ?>';
  var slider_time1 = '<?= $time1 ?>';
  var slider_time2 = '<?= $time2 ?>';
  var cutoff_time = '<?= $cutoff_time ?>';
  var operating_time_value = '<?= $operating_time_value ?>';
</script>

<div class="row sherpa-delivery-ui" style="max-width:2000px">
  <div class="col-sm-8">
    <div class="container main-container">
      <div class="panel">
          <div class="panel-body">
            <!-- Credentials -->
              <form class="form-horizontal mb-30" id="sherpaAccountForm" name="sherpaAccountForm" role="form">
                <div class="card-header panel-heading" style="display: flow-root;">
                  <img src="<?php echo site_url() ; ?>/wp-content/plugins/sherpa-on-demand/assets/images/sherpa_logo.png" style="width:120px">
                  <div style="float:right; text-align: right">
                    <div style="margin-top:10px">
                      <a target="_blank" href="https://www.sherpa.net.au/" class="white-link">sherpa.net.au</a>
                    </div>
                  </div>
                </div>
                <div class="panel-default mb-5 p-4">
                  <p class="mb-4 sherpa_info">Enter your Sherpa account details here. If you don’t already have a Sherpa Business Account, click <a href="https://deliveries.sherpa.net.au/users/sign_up" target="_blank">here</a> to create one.</p>
                  <div class="form-group row">
                    <label class="control-label col-sm-3"><?php echo __('Your Sherpa username', 'sherpa'); ?></label>
                    <div class="col-sm-6">
                      <input class="form-control" id="sherpa_credentials_account" name="sherpa_credentials_account" required type="text" value="<?php echo $conf->getUsername(); ?>" />
                      <div class="errorMessage" id="username_validate"></div>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="control-label col-sm-3"><?php echo __('Your Sherpa password', 'sherpa'); ?></label>
                    <div class="col-sm-6">
                      <input class="form-control" id="sherpa_credentials_password" name="sherpa_credentials_password" required type="password" value="<?php echo $conf->getPassword(); ?>" />
                      <div class="errorMessage" id="password_validate"></div>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="control-label col-sm-3"><?php echo __('Test account', 'sherpa'); ?></label>
                    <div class="col-sm-6">
                      <select class="form-control form-control-sm mb-2" id="sherpa_credentials_sandbox" name="sherpa_credentials_sandbox" onchange="sherpaSandboxConditionalText(this)">
                        <option value="1" <?php echo $conf->getSandbox() == 1 ? 'selected=selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo $conf->getSandbox() == 0 ? 'selected=selected' : ''; ?>><?php echo __('No', 'sherpa'); ?></option>
                      </select>
                      <p id="sherpa_sanbox_yes" class="help-text">Use your Sherpa test account login details to test the plugin without sending real delivery orders. If you don’t already have a test account, click <a href="https://qa.deliveries.sherpa.net.au/users/sign_up" target="_blank">here</a> to create one.</p>
                      <p id="sherpa_sanbox_no" class="help-text" hidden>Sherpa orders will be automatically be sent to your Sherpa account for delivery.</p>
                    </div>
                  </div>
  
                  <div class="form-group row">
                    <div class="col-sm-6 offset-sm-3 apiTestingWrap">
                      <input type="hidden" name="action" value="sherpa_credentials_action">
                      <button name="test" value="test" class="btn btn-primary sherpaApiTestings" type="button">Test</button>
                      <button style="margin-left:10px" name="test" value="test-save" class="btn btn-primary sherpaApiTestings" type="button">Test & Save</button>
                    </div>
                  </div>
  
                  <div class="row">
                    <div class="col-sm-12 offset-sm-3">
                      <div class="apiLoaderImage"></div>
                      <div id="sherpa-messages"></div>
                    </div>
                  </div>
                </div>
              </form>
  
              <!-- Options -->
              <form class="form-horizontal" id="sherpaApiForm" name="sherpaApiForm" role="form">
                <div class="delivery-settings-section">
                  <div class="panel-default p-4">
                    <h4>Delivery Settings</h4>
                    <div class="form-group row d-none">
                      <label class="control-label col-sm-3"><?php echo __('Your store name', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input class="form-control" id="sherpa_settings_title" name="sherpa_settings_title" type="text" value="<?php echo $conf->getStoreTitle() ?>" required />
                        <div class="errorMessage" id="username_validate"></div>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Create shipment on order placement', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sherpa_settings_shipment" name="sherpa_settings_shipment">
                          <option value="1" <?php echo $conf->getShipment() == 1 ? 'selected=selected' : ''; ?>><?php echo __('Yes', 'sherpa'); ?></option>
                          <option value="0" <?php echo $conf->getShipment() == 0 ? 'selected=selected' : ''; ?>><?php echo __('No', 'sherpa'); ?></option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Add Sherpa tracking link', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sherpa_settings_add_tracking_link" name="sherpa_settings_add_tracking_link">
                          <option value="1" <?php echo $conf->getTrackingLink() == 1 ? 'selected=selected' : ''; ?>><?php echo __('Yes', 'sherpa'); ?></option>
                          <option value="0" <?php echo $conf->getTrackingLink() == 0 ? 'selected=selected' : ''; ?>><?php echo __('No', 'sherpa'); ?></option>
                        </select>
                        <small class="help-text form-text text-muted">Add the Sherpa Tracking link to order email & include delivery information in order notes.</small>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Enable for all products', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sherpa_settings_product" name="sherpa_settings_product">
                          <option value="1" <?php echo $conf->getProduct() == 1 ? 'selected=selected' : ''; ?>><?php echo __('Yes', 'sherpa'); ?></option>
                          <option value="0" <?php echo $conf->getProduct() == 0 ? 'selected=selected' : ''; ?>><?php echo __('No', 'sherpa'); ?></option>
                        </select>
                      </div>
                    </div>
                  </div><!--End of section-->

                  <!--Delivery Rates Labels-->
                  <?php
                  //TO DO: Add this in a future version
                  $display_label_editor = false;
                  if($display_label_editor){
                  ?>
                  <div class="panel-default p-4">
                    <h4>Name Your Delivery Rates</h4>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Deliver today', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input class="form-control" id="sherpa_settings_sameday_delivery_options_sameday" name="sherpa_settings_sameday_delivery_options_sameday" required type="text" value="<?php echo $conf->getSamedayDeliveryOptionsSameday(); ?>" />
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Delivery labels for same day', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sherpa_settings_service_sameday" multiple="multiple" disabled="disabled" name="sherpa_settings_service_sameday[]">
                          <option value="service_1hr" <?php echo strpos($conf->getData('service_sameday'), 'service_1hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('sameday_delivery_options_service_1hr') ?></option>
                          <option value="service_2hr" <?php echo strpos($conf->getData('service_sameday'), 'service_2hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('sameday_delivery_options_service_2hr') ?></option>
                          <option value="service_4hr" <?php echo strpos($conf->getData('service_sameday'), 'service_4hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('sameday_delivery_options_service_4hr') ?></option>
                          <option value="service_at" <?php echo strpos($conf->getData('service_sameday'), 'service_at') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('sameday_delivery_options_service_at'); ?></option>
                          <option value="service_bulk_rate" <?php echo strpos($conf->getData('service_sameday'), 'service_bulk_rate') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('sameday_delivery_options_service_bulk_rate'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3 btn-group-sm">
                        <button class="btn btn-primary delOptionPopup" data-target="#del_opt_popup" data-toggle="modal" type="button">Edit labels
                        </button>
                        <a data-html="true" data-toggle="tooltip" href="javascript:void(0);" title="Options can be enabled or disabled from woocommerce sherpa shipping settings"><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Schedule for another day', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input class="form-control" id="sherpa_settings_later_delivery_options_later" name="sherpa_settings_later_delivery_options_later" required type="text" value="<?php echo $conf->getData('later_delivery_options_later') ?>" />
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Delivery labels for schedule for later', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sherpa_settings_service_later" multiple="multiple" disabled="disabled" name="sherpa_settings_service_later[]">
                          <option value="service_1hr" <?php echo strpos($conf->getData('service_later'), 'service_1hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('later_delivery_options_service_1hr') ?></option>
                          <option value="service_2hr" <?php echo strpos($conf->getData('service_later'), 'service_2hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('later_delivery_options_service_2hr') ?></option>
                          <option value="service_4hr" <?php echo strpos($conf->getData('service_later'), 'service_4hr') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('later_delivery_options_service_4hr') ?></option>
                          <option value="service_at" <?php echo strpos($conf->getData('service_later'), 'service_at') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('later_delivery_options_service_at') ?></option>
                          <option value="service_bulk_rate" <?php echo strpos($conf->getData('service_later'), 'service_bulk_rate') !== false ? 'selected=selected' : ''; ?>><?php echo $conf->getData('later_delivery_options_service_bulk_rate'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3 btn-group-sm">
                        <button class="btn btn-primary delOptionPopup" data-target="#del_opt_popup_later" data-toggle="modal" type="button">Edit labels
                        </button>
                        <a data-html="true" data-toggle="tooltip" href="javascript:void(0);" title="Options can be enabled or disabled from woocommerce sherpa shipping settings"><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                  </div><!--/Delivery Rates Labels-->
                  <?php } ?>
  
                  <!--Delivery Rate & Distance-->
                  <div class="panel-default p-4">
                    <h4><?php echo __('Delivery Rate & Distance', 'sherpa'); ?></h4>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Delivery rates', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="sel_rates_method" name="sherpa_settings_delivery_rates">
                          <?php
                          foreach ($delivery_rates as $delivery_rate) {
                            printf(
                              '<option value="%s" %s>%s</option>',
                                trim($delivery_rate['id']),
                                selected($conf->getData('delivery_rates'), $delivery_rate['id'], false),
                                trim($delivery_rate['label'])
                            );
                          }
                          ?>
                        </select>
                        <div class="errorMessage" id="sel_rates_method_validate"></div>
                      </div>
                      <div class="col-sm-3">
                        <a data-html="true" data-toggle="tooltip" href="javascript:void(0);" title="Flat Rate- specify a flat rate for deliveries within the specified radius of pickup point. &lt;br&gt; Margin- shipping rates will be a sum total of Sherpa's shipping cost and thespecified % margin."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                                
                    <div class="form-group row margin_show">
                      <label class="col-sm-3 col-form-label control-label"><?php echo __('Additional Margin', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <div class="input-group mb-2">
                          <input type="number" class="form-control" id="del_price" name="sherpa_settings_add_margin" value="<?php echo $conf->getData('add_margin') ? $conf->getData('add_margin') : '0'; ?>" />
                          <div class="input-group-append">
                            <span class="input-group-text">%</span>
                          </div>
                        </div>
                        <small class="form-text text-muted">(Apply this % on top of Sherpa's delivery price)</small>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the percentage margin to apply in addition to Sherpa's delivery cost."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                                
                    <!-- 1 hr delivery -->
                    <?php 
                    $services = get_option('woocommerce_sherpa_settings');
                    //Checks if service is enabled in AA as well as in shipping > delivery services
                    if( get_option('service_1hr_enabled') && (($services['services']['service_sameday']['service_1hr']['enabled']) || ($services['services']['service_later']['service_1hr']['enabled'])) ) { 
                    ?>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('1 hour delivery', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input id="sherpa_settings_flat_rate_1_hour" name="sherpa_settings_flat_rate_1_hour" type="hidden" />
                        <table cellspacing="0" class="data border table" id="group_prices_table_1">
                          <thead>
                            <tr class="headings">
                              <th><?php echo __('Distance', 'sherpa'); ?></th>
                              <th><?php echo __('Flat rate', 'sherpa') ?></th>
                              <th class="last"><?php echo __('Action', 'sherpa'); ?></th>
                            </tr>
                          </thead>
                          <tbody id="sherpa_settings_flat_rate_1_hour_container"></tbody>
                          <tfoot>
                            <tr>
                              <td class="a-right" colspan="3">
                                <button class="scalable add-sherpa_settings_flat_rate_1_hour" id="id_sherpa_sherpa_settings_flat_rate_1_hour" onclick="return flat_rate_1_hour.groupControl.addItem()" title="Add Another" type="button">
                                  <span>Add Rate</span>
                                </button>
                              </td>
                            </tr>
                          </tfoot>
                        </table>
                        <script type="text/javascript">
                          // <![CDATA[
                          jQuery(document).ready(function() {
                            flat_rate_1_hour = new flatRate('sherpa_settings_flat_rate_1_hour', 'flat_rate_1_hour');
                            flat_rate_1_hour.disabledOptions = [];
                            <?php if ($conf->getData('flat_rate_1_hour') && is_array($conf->getData('flat_rate_1_hour'))) : ?>
                              <?php foreach ($conf->getData('flat_rate_1_hour') as $_item) : ?>
                                <?php if (!$_item['delete'] && isset($_item['distance_group'])) : ?>
                                  flat_rate_1_hour.groupControl.addItem('<?php echo $_item['distance_group']; ?>', '<?php echo sprintf('%.2f', $_item['price']); ?>', <?php echo (int)!empty($_item['readonly']); ?>);
                                  flat_rate_1_hour.disabledOptions.push('<?php echo $_item['distance_group']; ?>');
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          });
                          // ]]>
                        </script>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the flat rate for deliveries within the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Outside radius', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="outside_rad_1_hour" name="sherpa_settings_outside_radius_1_hour">
                          <option value="ST" <?php echo $conf->getData('outside_radius_1_hour') == 'ST' ? 'selected=selected' : ''; ?>><?php echo __('Use standard Sherpa rates', 'sherpa'); ?></option>
                          <option value="ND" <?php echo $conf->getData('outside_radius_1_hour') == 'ND' ? 'selected=selected' : ''; ?>><?php echo __('Do not offer delivery', 'sherpa'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Use Standard Sherpa rates- Sherpa's standard delivery rates will apply outside the specified radius. Do not offer delivery- do not offer delivery outside the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                            
                      <div class="hr"></div>
                    </div>
                    <?php } ?>

                    <!-- 2 hr delivery -->
                    <?php
                      $sameday_2hr = isset($services['services']['service_sameday']['service_2hr']['enabled']) ? $services['services']['service_sameday']['service_2hr']['enabled'] : null;
                      $later_2hr = isset($services['services']['service_later']['service_2hr']['enabled']) ? $services['services']['service_later']['service_2hr']['enabled'] : null;

                      if((isset($sameday_2hr) && $sameday_2hr) || (isset($later_2hr) && $later_2hr)){
                     //if( ($services['services']['service_sameday']['service_2hr']['enabled']) || ($services['services']['service_later']['service_2hr']['enabled']) ) {  ?>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('2 hour delivery', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input id="sherpa_settings_flat_rate_2_hour" name="sherpa_settings_flat_rate_2_hour" type="hidden" />
                        <table cellspacing="0" class="data border table" id="group_prices_table_2">
                          <thead>
                            <tr class="headings">
                              <th><?php echo __('Distance', 'sherpa'); ?></th>
                              <th><?php echo __('Flat rate', 'sherpa') ?></th>
                              <th class="last"><?php echo __('Action', 'sherpa'); ?></th>
                            </tr>
                          </thead>
                          <tbody id="sherpa_settings_flat_rate_2_hour_container"></tbody>
                            <tfoot>
                              <tr>
                                <td class="a-right" colspan="3">
                                  <button class="scalable add-sherpa_settings_flat_rate_2_hour" id="id_sherpa_sherpa_settings_flat_rate_2_hour" onclick="return flat_rate_2_hour.groupControl.addItem()" title="Add Another" type="button">
                                    <span>Add Rate</span>
                                  </button>
                                </td>
                              </tr>
                            </tfoot>
                        </table>
                        <script type="text/javascript">
                          // <![CDATA[
                          jQuery(document).ready(function() {
                            flat_rate_2_hour = new flatRate('sherpa_settings_flat_rate_2_hour', 'flat_rate_2_hour');
                            flat_rate_2_hour.disabledOptions = [];
                            <?php if ($conf->getData('flat_rate_2_hour') && is_array($conf->getData('flat_rate_2_hour'))) : ?>
                              <?php foreach ($conf->getData('flat_rate_2_hour') as $_item) : ?>
                                <?php if (!$_item['delete'] && isset($_item['distance_group'])) : ?>
                                  flat_rate_2_hour.groupControl.addItem('<?php echo $_item['distance_group']; ?>', '<?php echo sprintf('%.2f', $_item['price']); ?>', <?php echo (int)!empty($_item['readonly']); ?>);
                                  flat_rate_2_hour.disabledOptions.push('<?php echo $_item['distance_group']; ?>');
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          });
                          // ]]>
                        </script>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the flat rate for deliveries within the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Outside radius', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="outside_rad_2_hour" name="sherpa_settings_outside_radius_2_hour">
                          <option value="ST" <?php echo $conf->getData('outside_radius_2_hour') == 'ST' ? 'selected=selected' : ''; ?>><?php echo __('Use standard Sherpa rates', 'sherpa'); ?></option>
                          <option value="ND" <?php echo $conf->getData('outside_radius_2_hour') == 'ND' ? 'selected=selected' : ''; ?>><?php echo __('Do not offer delivery', 'sherpa'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Use Standard Sherpa rates- Sherpa's standard delivery rates will apply outside the specified radius. Do not offer delivery- do not offer delivery outside the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                              
                      <div class="hr"></div>
                    </div>
                    <?php } ?>

                    <!-- 4 hr delivery -->
                    <?php if( isset($services['services']['service_sameday']['service_4hr']['enabled']) || isset($services['services']['service_later']['service_4hr']['enabled']) ) {  ?>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('4 hour delivery', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input id="sherpa_settings_flat_rate_4_hour" name="sherpa_settings_flat_rate_4_hour" type="hidden" />
                        <table cellspacing="0" class="data border table" id="group_prices_table_3">
                          <thead>
                            <tr class="headings">
                              <th><?php echo __('Distance', 'sherpa'); ?></th>
                              <th><?php echo __('Flat rate', 'sherpa') ?></th>
                              <th class="last"><?php echo __('Action', 'sherpa'); ?></th>
                            </tr>
                          </thead>
                          <tbody id="sherpa_settings_flat_rate_4_hour_container"></tbody>
                          <tfoot>
                            <tr>
                              <td class="a-right" colspan="3">
                                <button class="scalable add-sherpa_settings_flat_rate_4_hour" id="id_sherpa_sherpa_settings_flat_rate_4_hour" onclick="return flat_rate_4_hour.groupControl.addItem()" title="Add Another" type="button">
                                  <span>Add Rate</span>
                                </button>
                              </td>
                            </tr>
                          </tfoot>
                        </table>
                        <script type="text/javascript">
                          // <![CDATA[
                          jQuery(document).ready(function() {
                            flat_rate_4_hour = new flatRate('sherpa_settings_flat_rate_4_hour', 'flat_rate_4_hour');
                            <?php if ($conf->getData('flat_rate_4_hour') && is_array($conf->getData('flat_rate_4_hour'))) : ?>
                              <?php foreach ($conf->getData('flat_rate_4_hour') as $_item) : ?>
                                <?php if (!$_item['delete'] && isset($_item['distance_group'])) : ?>
                                  flat_rate_4_hour.groupControl.addItem('<?php echo $_item['distance_group']; ?>', '<?php echo sprintf('%.2f', $_item['price']); ?>', <?php echo (int)!empty($_item['readonly']); ?>);
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          });
                          // ]]>
                        </script>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the flat rate for deliveries within the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Outside radius', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="outside_rad_4_hour" name="sherpa_settings_outside_radius_4_hour">
                          <option value="ST" <?php echo $conf->getData('outside_radius_4_hour') == 'ST' ? 'selected=selected' : ''; ?>><?php echo __('Use standard Sherpa rates', 'sherpa'); ?></option>
                          <option value="ND" <?php echo $conf->getData('outside_radius_4_hour') == 'ND' ? 'selected=selected' : ''; ?>><?php echo __('Do not offer delivery', 'sherpa'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Use Standard Sherpa rates- Sherpa's standard delivery rates will apply outside the specified radius. Do not offer delivery- do not offer delivery outside the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                            
                      <div class="hr"></div>
                    </div>
                    <?php } ?>

                    <!-- Same day -->
                    <?php if( get_option('service_at_enabled') && ( ($services['services']['service_sameday']['service_at']['enabled']) || ($services['services']['service_later']['service_at']['enabled']) ) ) {  ?>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Same day delivery', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input id="sherpa_settings_flat_rate_same_day" name="sherpa_settings_flat_rate_same_day" type="hidden" />
                        <table cellspacing="0" class="data border table" id="group_prices_table_4">
                          <thead>
                            <tr class="headings">
                              <th><?php echo __('Distance', 'sherpa'); ?></th>
                              <th><?php echo __('Flat rate', 'sherpa') ?></th>
                              <th class="last"><?php echo __('Action', 'sherpa'); ?></th>
                            </tr>
                          </thead>
                          <tbody id="sherpa_settings_flat_rate_same_day_container"></tbody>
                          <tfoot>
                            <tr>
                              <td class="a-right" colspan="3">
                                <button class="scalable add-sherpa_settings_flat_rate_same_day" id="id_sherpa_sherpa_settings_flat_rate_same_day" onclick="return flat_rate_same_day.groupControl.addItem()" title="Add Another" type="button">
                                  <span>Add Rate</span>
                                </button>
                              </td>
                            </tr>
                          </tfoot>
                        </table>
                        <script type="text/javascript">
                          // <![CDATA[
                          jQuery(document).ready(function() {
                            flat_rate_same_day = new flatRate('sherpa_settings_flat_rate_same_day', 'flat_rate_same_day');
                            <?php if ($conf->getData('flat_rate_same_day') && is_array($conf->getData('flat_rate_same_day'))) : ?>
                              <?php foreach ($conf->getData('flat_rate_same_day') as $_item) : ?>
                                <?php if (!$_item['delete'] && isset($_item['distance_group'])) : ?>
                                  flat_rate_same_day.groupControl.addItem('<?php echo $_item['distance_group']; ?>', '<?php echo sprintf('%.2f', $_item['price']); ?>', <?php echo (int)!empty($_item['readonly']); ?>);
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          });
                          // ]]>
                        </script>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the flat rate for deliveries within the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Outside radius', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="outside_rad_same_day" name="sherpa_settings_outside_radius_same_day">
                          <option value="ST" <?php echo $conf->getData('outside_radius_same_day') == 'ST' ? 'selected=selected' : ''; ?>><?php echo __('Use standard Sherpa rates', 'sherpa'); ?></option>
                          <option value="ND" <?php echo $conf->getData('outside_radius_same_day') == 'ND' ? 'selected=selected' : ''; ?>><?php echo __('Do not offer delivery', 'sherpa'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Use Standard Sherpa rates- Sherpa's standard delivery rates will apply outside the specified radius. Do not offer delivery- do not offer delivery outside the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                            
                      <div class="hr"></div>
                    </div>
                    <?php } ?>

                    <!-- Bulk rate -->
                    <?php if( get_option('service_bulk_rate_enabled') && ( ($services['services']['service_sameday']['service_bulk_rate']['enabled']) || ($services['services']['service_later']['service_bulk_rate']['enabled']) ) ) {  ?>
                    <div class="form-group row flat_show">
                      <label class="control-label col-sm-3"><?php echo __('Bulk rate delivery', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input id="sherpa_settings_flat_rate_bulk_rate" name="sherpa_settings_flat_rate_bulk_rate" type="hidden" />
                        <table cellspacing="0" class="data border table" id="group_prices_table_5">
                          <thead>
                            <tr class="headings">
                              <th><?php echo __('Distance', 'sherpa'); ?></th>
                              <th><?php echo __('Flat rate', 'sherpa') ?></th>
                              <th class="last"><?php echo __('Action', 'sherpa'); ?></th>
                            </tr>
                          </thead>
                          <tbody id="sherpa_settings_flat_rate_bulk_rate_container"></tbody>
                          <tfoot>
                            <tr>
                              <td class="a-right" colspan="3">
                                <button class="scalable add-sherpa_settings_flat_rate_bulk_rate" id="id_sherpa_sherpa_settings_flat_rate_bulk_rate" onclick="return flat_rate_bulk_rate.groupControl.addItem()" title="Add Another" type="button">
                                  <span>Add Rate</span>
                                </button>
                              </td>
                            </tr>
                          </tfoot>
                        </table>
                        <script type="text/javascript">
                          // <![CDATA[
                          jQuery(document).ready(function() {
                            flat_rate_bulk_rate = new flatRate('sherpa_settings_flat_rate_bulk_rate', 'flat_rate_bulk_rate');
                            flat_rate_bulk_rate.disabledOptions = [];
                            <?php if ($conf->getData('flat_rate_bulk_rate') && is_array($conf->getData('flat_rate_bulk_rate'))) : ?>
                              <?php foreach ($conf->getData('flat_rate_bulk_rate') as $_item) : ?>
                                <?php if (!$_item['delete'] && isset($_item['distance_group'])) : ?>
                                  flat_rate_bulk_rate.groupControl.addItem('<?php echo $_item['distance_group']; ?>', '<?php echo sprintf('%.2f', $_item['price']); ?>', <?php echo (int)!empty($_item['readonly']); ?>);
                                  flat_rate_bulk_rate.disabledOptions.push('<?php echo $_item['distance_group']; ?>');
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          });
                          // ]]>
                        </script>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Define the flat rate for deliveries within the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row flat_show d-none" style="display: none; z-index: -999;">
                      <input type="hidden" name="sherpa_settings_outside_radius_bulk_rate" value="ND">
                      <label class="control-label col-sm-3"><?php echo __('Outside radius', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="outside_rad_bulk_rate" name="sherpa_settings_outside_radius_bulk_rate_dummy">
                          <option value="ST" <?php echo $conf->getData('outside_radius_bulk_rate') == 'ST' ? 'selected=selected' : ''; ?>><?php echo __('Use standard Sherpa rates', 'sherpa'); ?></option>
                          <option value="ND" <?php echo $conf->getData('outside_radius_bulk_rate') == 'ND' ? 'selected=selected' : ''; ?>><?php echo __('Do not offer delivery', 'sherpa'); ?></option>
                        </select>
                      </div>
                      <div class="col-sm-3">
                        <a data-toggle="tooltip" href="javascript:void(0);" title="Use Standard Sherpa rates- Sherpa's standard delivery rates will apply outside the specified radius. Do not offer delivery- do not offer delivery outside the specified radius."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <?php } ?>

                    <div class="schedule_delivery_section">
                      <h4><?php echo __('Business Details', 'sherpa'); ?></h4>
                      <p>This information is not shown to your customers</p>
                      <div class="form-group row">
                        <label class="control-label col-sm-3"><?php echo __('Store name', 'sherpa'); ?></label>
                        <div class="col-sm-6">
                          <input class="form-control" id="sherpa_settings_store_name" name="sherpa_settings_store_name" required type="text" value="<?php echo $conf->getStoreName() ?>" />
                          <div class="errorMessage" id="store_name_validate"></div>
                        </div>
                      </div>
                        
                      <div class="form-group row">
                        <input type="hidden" id="sherpa_sherpa_delivery_settings_cutoff_time" name="sherpa_sherpa_delivery_settings_cutoff_time" value="<?php echo $cutoff_time; ?>"/>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Business days', 'sherpa'); ?></label>
                      <div class="col-sm-9">
                        
                        <div id="business_days" style="margin-top:5px;">
                          <?php
                          $business_days = ['Mon'=>'1', 'Tue'=>'2', 'Wed'=>'3', 'Thu'=>'4', 'Fri'=>'5', 'Sat'=>'6', 'Sun'=>'7'];
                          foreach($business_days as $day=>$day_value){
                          ?>
                          <input type="checkbox" value="<?php echo $day_value; ?>" name="sherpa_delivery_settings_operating_day[]" id="<?php echo 'businessDayCheckfield'.$day;?>" <?php echo strpos($conf->getData('operating_day'), $day_value) !== false ? "checked" : ""; ?>>
                              <label style="margin-right: 6px; margin-top: -2px; margin-left:-3px" class="form-check-label" for="<?php echo 'businessDayCheckfield'.$day;?>">
                                  <?php echo $day; ?>
                          </label>
                          <?php } 
                          unset($day);
                          unset($day_value);
                          ?>
                        </div>                        
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">Pickup hours</label>
                      <div class="col-sm-6 input_fields_wrap">
                        <div id="time-range">
                          <p style="font-size:13px; margin-top:7px;">Sherpa drivers can pickup from your store between these hours:</p>
                          <!-- <p>Time range: <span class="slider-time">10:00 AM</span> - <span class="slider-time2">12:00 PM</span></p> -->
                          <div class="sliders_step1">
                              <div id="slider-range"></div>
                          </div>
                                  
                          <!--Pickup Time DROPOWN-->
                          <?php
                              $pickup_hours = array('7:00'=>'7:00 AM','8:00'=>'8:00 AM','9:00'=>'9:00 AM','10:00'=>'10:00 AM','11:00'=>'11:00 AM','12:00'=>'12:00 PM','13:00'=>'1:00 PM','14:00'=>'2:00 PM','15:00'=>'3:00 PM','16:00'=>'4:00 PM','17:00'=>'5:00 PM','18:00'=>'6:00 PM','19:00'=>'7:00 PM','20:00'=>'8:00 PM','21:00'=>'9:00 PM'); 
                          ?>
                          <div class="slider_step1">
                              <select id="operating_time_selector_from">
                                  <?php
                                  foreach($pickup_hours as $hour_key => $hour){
                                  ?>
                                  <option value="<?php echo $hour_key; ?>" 
                                      <?php
                                      if($time1){ 
                                          echo ($hour == $time1)? 'selected=selected' : ''; 
                                      }else{
                                          echo ($hour_key == '9:00')? 'selected=selected' : '';   //Defaults to 9:00 AM if not set
                                      }
                                      ?>
                                  >
                                      <?php echo "From ".$hour; ?>
                                  </option>
                                  <?php }
                                  unset($hour_key);
                                  unset($hour);
                                  ?>
                              </select>
                              <select id="operating_time_selector_to">
                                  <?php
                                  foreach($pickup_hours as $hour_key => $hour){
                                  ?>
                                  <option value="<?php echo $hour_key; ?>" 
                                  <?php
                                      if($time2){
                                          echo ($hour == $time2)? 'selected=selected' : ''; 
                                      }else{
                                          echo ($hour_key == '17:00')? 'selected=selected' : ''; //Defaults to 5:00 PM if not set
                                      }   
                                  ?>>
                                      <?php echo "Until ".$hour; ?>
                                  </option>
                                  <?php }
                                  unset($hour_key);
                                  unset($hour); 
                                  ?>
                              </select>
                          </div>
                          <!--/Pickup Time DROPOWN-->
                        </div>
                        <!-- <small class="help-text note_hour form-text"><span></span></small> -->
                        <small class="help-text form-text text-muted">Rates at checkout will be displayed in relation to these times. For Same Day Delivery option, hours must be at least 12:00pm-5:00pm for delivery by 5pm. Preparation time also impacts cut off times for rates to show.</small>
                        <input id="sherpa_sherpa_delivery_settings_operating_time_wrapper" name="sherpa_delivery_settings_operating_time_wrapper" type="hidden" /> <span class="errorMessage" id="time_slots_validate"></span>
                      </div>
                      <div class="col-sm-3">
                          <a data-toggle="tooltip" href="javascript:void(0);" title="Define timeslots you want to offer for delivery of your products."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3" ><?php echo __('Preparation time', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="prep_time" name="sherpa_delivery_settings_prep_time">
                          <option value="NP" <?php echo $conf->getData('prep_time') == 'NP' ? 'selected=selected' : ''; ?>>No prep needed</option>
                          <option value="30M" <?php echo $conf->getData('prep_time') == '30M' ? 'selected=selected' : ''; ?>>30 minutes</option>
                          <option value="1H" <?php echo $conf->getData('prep_time') == '1H' ? 'selected=selected' : ''; ?>>1 hour</option>
                          <option value="2H" <?php echo $conf->getData('prep_time') == '2H' ? 'selected=selected' : ''; ?>>2 hours</option>
                          <option value="4H" <?php echo $conf->getData('prep_time') == '4H' ? 'selected=selected' : ''; ?>>4 hours</option>
                        </select>
                        <small class="help-text form-text text-muted">Specify the time you need to prepare the order after it has been placed. This impacts the cutoff time to place an order. For example, if you have 1 hour preparation time, and your store is open until 5pm, the latest customers will have until 2pm to place their order for a 2hr delivery (driver will pickup between 3pm and 5pm).</small>
                      </div>
                      <div class="col-sm-3">
                        <a data-html="true" data-toggle="tooltip" href="javascript:void(0);" title="Our Sherpas will arrive at the pickup location after the defined prep time."><i class="fa fa-question tooltipHelp"></i></a>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Instructions for pickup', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input class="form-control" id="sherpa_settings_notes" name="sherpa_settings_notes" required type="text" value="<?php echo $conf->getNotes() ?>" />
                        <small class="help-text form-text text-muted">Let our drivers know any information around parking, loading dock rules, whom to contact etc</small>
                        <div class="errorMessage" id="notes_validate"></div>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Default item/product description', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <input class="form-control" id="sherpa_settings_item_description" name="sherpa_settings_item_description" required type="text" value="<?php echo $conf->getItemDescription() ?>" />
                        <small class="help-text form-text text-muted">If left empty, your product name will go through as the description of the item. If, for privacy reasons, you do not want to expose the product details to the driver, please set a default description here.</small>
                        <div class="errorMessage" id="notes_validate"></div>
                      </div>
                    </div>
                    <div class="form-group row">
                      <input type="hidden" id="sherpa_sherpa_delivery_settings_cutoff_time" name="sherpa_sherpa_delivery_settings_cutoff_time" value="<?php echo $cutoff_time; ?>"/>
                    </div>
                  </div>
                                                
                  <!-- Delivery Preferences -->
                  <div class="panel-default sherpa-delivery-prefs p-4" style="background: #fff; padding: 1px 15px">
                    <h4><?php echo __('Delivery Preferences', 'sherpa'); ?></h4>
                    <div class="form-group row">
                      <label class="control-label col-sm-3"><?php echo __('Delivery vehicle', 'sherpa'); ?></label>
                      <div class="col-sm-6">
                        <select class="form-control form-control-sm" id="vehicle_options" name="sherpa_settings_vehicle">
                          <option value="1" <?php echo $conf->getData('vehicle_id') == 1 ? 'selected=selected' : ''; ?>><?php echo __('Car', 'sherpa'); ?></option>
                          <option value="2" <?php echo $conf->getData('vehicle_id') == 2 ? 'selected=selected' : ''; ?>>Motorbike/Scooter</option>
  									      <option value="4" <?php echo $conf->getData('vehicle_id') == 4 ? 'selected=selected' : ''; ?>>Van/Wagon</option>
                        </select>
  								      <small class="help-text form-text text-muted selected_vehicle_help"></small>                    
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Authority to leave', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                          <input name="sherpa_settings_authority_to_leave" value="1" type="checkbox" class="form-check-input mt-1" id="authority-to-leave" <?php checked($conf->getData('authority_to_leave'), '1', true); ?>>
                          <label class="form-check-label ml-4" for="authority-to-leave">Select to provide authority to leave package should recipient not be available. Note that tobacco, scheduled medication and specified recipient deliveries cannot be left unattended.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Send SMS', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_send_sms" value="1" type="checkbox" class="form-check-input mt-1" id="send-sms" <?php checked($conf->getData('send_sms'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="send-sms">Select to send recipient SMS notifications (only available if recipient provides a SMS enabled phone number).</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Specified recipient', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_specified_recipient" value="1" type="checkbox" class="form-check-input mt-1" id="specified-recipient" <?php checked($conf->getData('specified_recipient'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="specified-recipient">Select if you need our driver to check the recipient’s ID matches the name of the person who placed the order. Specified recipient deliveries cannot be left unattended.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Contains alcohol', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_contains_alcohol" value="1" type="checkbox" class="form-check-input mt-1" id="contains-alcohol" <?php checked($conf->getData('contains_alcohol'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="contains-alcohol">Select if your order contains alcohol.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Contains fragile items', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_contains_fragile_items" value="1" type="checkbox" class="form-check-input mt-1" id="contains-fragile-items" <?php checked($conf->getData('contains_fragile_items'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="contains-fragile-items">Select if your order contains fragile items.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Contains scheduled (prescription) medication', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_contains_scheduled_medication" value="1" type="checkbox" class="form-check-input mt-1" id="contains-scheduled-medication" <?php checked($conf->getData('contains_scheduled_medication'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="contains-scheduled-medication">Select if your order contains scheduled (prescription) medication. Upon delivery, our driver will check the recipient is over 18. Scheduled medication deliveries cannot be left unattended.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Contains tobacco', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_contains_tobacco" value="1" type="checkbox" class="form-check-input mt-1" id="contains-tobacco" <?php checked($conf->getData('contains_tobacco'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="contains-tobacco">Select if your order contains tobacco. Upon delivery, our driver will check the recipient is over 18. Tobacco deliveries cannot be left unattended.</label>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="control-label col-sm-3">
                        <?php echo __('Requires hi-vis vest', 'sherpa'); ?>
                      </label>
                      <div class="col-sm-6">
                        <input name="sherpa_settings_requires_hi_vis_vest" value="1" type="checkbox" class="form-check-input mt-1" id="requires-hi-vis-vest" <?php checked($conf->getData('requires_hi_vis_vest'), '1', true); ?>>
                        <label class="form-check-label ml-4" for="requires-hi-vis-vest">Select if you require our driver to wear a hi-vis vest.</label>
                      </div>
                    </div>
                                                
                    <!-- Save button -->
                    <div class="form-group row">
                      <div class="col-sm-offset-3 col-sm-9">
                        <div class="error-message"></div>
                      </div>
                    </div>
                    <div class="form-group row">
                      <div class="offset-sm-3 col-sm-9 ">
                        <input name="action" type="hidden" value="sherpa_settings_action" />
                        <button class="btn btn-primary sherpaSettings" type="button">Save</button>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-sm-12 offset-sm-3">
                        <div class="loaderImage"></div>
                        <div id="responseMgs"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>

                                                
              <!-- Modal 1 -->
              <div aria-hidden="true" aria-labelledby="memberModalLabel" class="modal fade" id="del_opt_popup" role="dialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title"><?php echo __('Delivery Options', 'sherpa'); ?></h4>
                      <button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form id="delivery_options_sameday_form" method="post" name="delivery_options_sameday_form">
                      <div class="modal-body">
                        <p><span><?php echo __('Edit text labels for the delivery options displayed at checkout page.', 'sherpa'); ?></span></p>
                        <?php
                          $always_enabled_delivery_options = ['service_2hr_enabled','service_4hr_enabled', 'flat_rate_enabled' ];
                          $delivery_options_from_db = ['service_1hr_enabled'=>['service_1hr', 'Deliver in 1 hour'], 'service_2hr_enabled'=>['service_2hr', 'Deliver in 2 hours'], 'service_4hr_enabled'=>['service_4hr', 'Deliver in 4 hours'], 'service_at_enabled'=>['service_at', 'Deliver any time that day'], 'service_bulk_rate_enabled'=>['service_bulk_rate', 'Deliver at bulk rate'] ];                                  
                                  
                          foreach($delivery_options_from_db as $db_flag => $flag_value) {
                            if(in_array($db_flag, $always_enabled_delivery_options) || get_option($db_flag)) {
                        ?>
                              <div class="row">
                                <div class="col-sm-4"><?php echo $flag_value[1]; ?></div>
                                <input class="delOptInput_later" id="sherpa_settings_sameday_delivery_options_<?php echo $flag_value[0];?>" name="sherpa_settings_sameday_delivery_options_<?php echo $flag_value[0];?>" required type="text" value="<?php echo $conf->getData('sameday_delivery_options_'.$flag_value[0]) ?>" />
                                <div class="errorMessage offset-md-4" id="sherpa_settings_later_delivery_options_service_1hr"></div>
                              </div>
                        <?php 
                            }
                          } 
                        ?>
                      </div>
                      <div class="modal-footer">
                        <input name="action" type="hidden" value="delivery_options_action" />
                        <input class="btn btn-primary saveDelOpts saveDelOptsSameday" type="button" value="Save" />
                        <div class="deliveryLoaderImage"></div>
                        <div class="successMessage" id="deliveryResMgsSameDay"></div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
                                                
              <!-- Modal 2 -->
              <div aria-hidden="true" aria-labelledby="memberModalLabel" class="modal fade" id="del_opt_popup_later" role="dialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title"><?php echo __('Delivery Options', 'sherpa'); ?></h4>
                      <button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form id="delivery_options_later_form" method="post" name="delivery_options_later_form">
                      <div class="modal-body ">
                        <span><?php echo __('Edit text labels for the delivery options displayed at checkout page.', 'sherpa'); ?></span>
                        <?php
                          $always_enabled_delivery_options = ['service_2hr_enabled','service_4hr_enabled', 'flat_rate_enabled' ];
                          $delivery_options_from_db = ['service_1hr_enabled'=>['service_1hr', 'Deliver in 1 hour'], 'service_2hr_enabled'=>['service_2hr', 'Deliver in 2 hours'], 'service_4hr_enabled'=>['service_4hr', 'Deliver in 4 hours'], 'service_at_enabled'=>['service_at', 'Deliver any time that day'], 'service_bulk_rate_enabled'=>['service_bulk_rate', 'Deliver at bulk rate'] ];                                  
                          
                          foreach($delivery_options_from_db as $db_flag => $flag_value) {
                            if(in_array($db_flag, $always_enabled_delivery_options) || get_option($db_flag)) {
                        ?>
                              <div class="row">
                                <div class="col-sm-4"><?php echo __($flag_value[1], 'sherpa'); ?>:</div>
                                <input class="delOptInput_later" id="sherpa_settings_later_delivery_options_<?php echo $flag_value[0];?>" name="sherpa_settings_later_delivery_options_<?php echo $flag_value[0];?>" required type="text" value="<?php echo $conf->getData('later_delivery_options_'.$flag_value[0]); ?>" />
                                <div class="errorMessage offset-md-4" id="sherpa_settings_later_delivery_options_<?php echo $flag_value[0];?>"></div>
                              </div>
                        <?php 
                            }
                          } 
                        ?>
                      </div>
                      <div class="modal-footer">
                        <input name="action" type="hidden" value="delivery_options_action_later" />
                        <input class="btn btn-primary saveDelOpts saveDelOptsLater" type="button" value="Save" />
                        <div class="deliveryLoaderImage"></div>
                        <div class="successMessage" id="deliveryResMgsLater"></div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
          </div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div id="sherpa-support-panel" class="container main-container">
      <div id="sign-up" class="mb-30" style="box-shadow: 0 1px 1px rgb(0 0 0 / 5%)">
        <h3><img src="<?php echo site_url(); ?>/wp-content/plugins/sherpa-on-demand/assets/images/account.png" style="width: 25px; margin-bottom: 4px; margin-right: 8px;"> Sign Up to Sherpa</h3>
        <p>You'll need a Sherpa Delivery account to use this plugin.</p>
        <a target="_blank" href="https://deliveries.sherpa.net.au/users/sign_up">
          <button name="australian_sign_up" value="australian_sign_up" class="btn btn-outline-primary block w-100 mb-20" type="button">Sign up</button>
        </a>
      </div>
      <div id="help-panel" style="box-shadow: 0 1px 1px rgb(0 0 0 / 5%)">
        <h3> <img src="<?php echo site_url(); ?>/wp-content/plugins/sherpa-on-demand/assets/images/help.png" style="width: 26px; margin-bottom: 7px; margin-right: 8px;"> Need Help?</h3>
        <p>We have created a setup guide with tutorials to help you get the Sherpa Delivery plugin running on your store.</p>
        <a target="_blank" href="https://help.auuser.sherpadelivery.com/hc/en-us/articles/4419067143181-WooCommerce-WordPress-Sherpa-Delivery-Plugin-Quickstart-Guide">
          <button class="btn btn-outline-primary block w-100 mb-20" type="button">Quick start guide</button>
        </a>
        <p><strong>Didn't find what you need?</strong></p>
        <p>Please send us an email to <a class="underline" href="mailto:plugins@sherpa.net.au">plugins@sherpa.net.au</a> or log a support ticket and our team will get in touch with you.</p>
        <a target="_blank" href="https://getasherpa.atlassian.net/servicedesk/customer/portal/7">
          <button class="btn btn-outline-primary block w-100 mb-50" type="button">Log a support ticket</button>
        </a>
          
        <h3><img src="<?php echo site_url(); ?>/wp-content/plugins/sherpa-on-demand/assets/images/call.png" style="width: 19px; margin-bottom: 3px; margin-right: 8px;"> Contact Sherpa</h3>
        <p class="mb-5"><strong>Delivery Support: </strong> <a class="black-text" href="tel:+61 2 4058 4005">+61 2 4058 4005</a></p>
        <p class="mb-5"><strong>Sales Enquiries: </strong> <a class="black-text" href="tel:1800 841 660">1800 841 660</a></p>
        <p class="mb-50"><strong>Email: </strong> <a class="underline" href="mailto:info@sherpa.net.au">info@sherpa.net.au</a></p>

        <img src="<?php echo site_url(); ?>/wp-content/plugins/sherpa-on-demand/assets/images/sherpa_van.png" style="width:100%; margin-bottom: -37px">

      </div>
    </div>
  </div>
</div>
