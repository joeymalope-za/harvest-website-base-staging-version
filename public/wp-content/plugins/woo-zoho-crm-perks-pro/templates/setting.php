<?php
if ( ! defined( 'ABSPATH' ) ) {
     exit;
 }
                                        
 ?><h3><?php echo sprintf(__("Account ID# %d",'woo-zoho'),esc_attr($id));
if($new_account_id != $id){
 ?> <a href="<?php echo esc_url($new_account); ?>" title="<?php esc_html_e('Add New Account','woo-zoho'); ?>" class="add-new-h2"><?php esc_html_e("Add New Account",'woo-zoho'); ?></a> 
 <?php
}
$name=$this->post('name',$info);    
 ?>
 <a href="<?php echo esc_url($link) ?>" class="add-new-h2" title="<?php esc_html_e('Back to Accounts','woo-zoho'); ?>"><?php esc_html_e('Back to Accounts','woo-zoho'); ?></a></h3>
  <div class="crm_fields_table">
    <div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_name"><?php esc_html_e("Account Name",'woo-zoho'); ?></label>
  </div>
  <div class="crm_field_cell2">
  <input type="text" name="crm[name]" value="<?php echo !empty($name) ? esc_html($name) : esc_html('Account #'.$id); ?>" id="vx_name" class="crm_text">

  </div>
  <div class="clear"></div>
  </div>

    <div class="crm_field">
  <div class="crm_field_cell1">
  <label for="vx_dc"><?php esc_html_e('Data Center','woocommece-zoho-crm'); ?></label>
  </div>
  <div class="crm_field_cell2">
<select name="crm[dc]" class="crm_text" id="vx_dc" data-save="no" <?php if( !empty($info['access_token'])){ echo 'disabled="disabled"'; } ?> >
  <?php $envs=array(
  'com'=>__('zoho.com (Global - USA)','woocommece-zoho-crm'),
  'eu'=>__('zoho.eu (Europe)','woocommece-zoho-crm'),
  'in'=>__('zoho.in (India)','woocommece-zoho-crm'),
  'com.cn'=>__('zoho.com.cn (China)','woocommece-zoho-crm'),
  'com.au'=>__('zoho.com.au (Australia)','woocommece-zoho-crm'),
  'jp'=>__('zoho.jp (Japan)','woocommece-zoho-crm'),
  'uk'=>__('zoho.uk (United Kingdom)','woocommece-zoho-crm'),
  'ca'=>__('zohocloud.ca (Canada)','woocommece-zoho-crm'),
  );
foreach($envs as $k=>$v){
    $sel='';
if(!empty($info['dc']) && $info['dc'] == $k){ $sel='selected="selected"'; }
echo '<option value="'.$k.'" '.$sel.'>'.$v.'</option>';
}
 ?>
 </select>
  </div>
  <div class="clear"></div>
  </div>
  <script type="text/javascript">
  jQuery(document).ready(function($){
  /*  $('#vx_dc').change(function(){
        var val=$(this).val();
        var btn=$('.sf_login');
      var url='https://accounts.zoho.'+val+'/';
      var dc=btn.attr('data-url');  
  btn.attr('href',url+dc); 
    })*/
   // var elem=$('#mainform');
    var elem=$('.crm_fields_table :input');
    var form=elem.serialize();
      $('.sf_login').click(function(e){
      var form2=elem.serialize(); 
      if(form != form2){
         e.preventDefault();
        alert('Please "Save Changes" first');  
      }
      });  
  })
  </script>
  <div class="crm_field">
  <div class="crm_field_cell1">
  <label for="vx_type"><?php esc_html_e('Zoho Service','woocommece-zoho-crm'); ?></label>
  </div>
  <div class="crm_field_cell2">
<select name="crm[type]" class="crm_text" id="vx_type" <?php if( !empty($info['access_token'])){ echo 'disabled="disabled"'; } ?> >
  <?php $envs=array(
  ''=>__('Zoho CRM','woocommece-zoho-crm'),
  'invoices'=>__('Zoho Invoice','woocommece-zoho-crm'),
  'books'=>__('Zoho Books','woocommece-zoho-crm'),
  'inventory'=>__('Zoho Inventory','woocommece-zoho-crm'),
  'crmplus'=>__('Zoho CRM Plus','woocommece-zoho-crm'),
   'bigin'=>__('Zoho Bigin','woocommece-zoho-crm'),
  );
foreach($envs as $k=>$v){
    $sel='';
if(!empty($info['type']) && $info['type'] == $k){ $sel='selected="selected"'; }
echo '<option value="'.$k.'" '.$sel.'>'.$v.'</option>';
}
 ?>
 </select>
  </div>
  <div class="clear"></div>
  </div>
<?php if(count($orgs) > 1){ ?> 
  <div class="crm_field">
  <div class="crm_field_cell1">
  <label for="vx_org"><?php esc_html_e('Zoho Organization','woocommece-zoho-crm'); ?></label>
  </div>
  <div class="crm_field_cell2">
<select name="crm[zoho_org]" class="crm_text" id="vx_org">
  <?php
foreach($orgs as $k=>$v){
    $sel='';
if(!empty($info['zoho_org']) && $info['zoho_org'] == $k){ $sel='selected="selected"'; }
echo '<option value="'.$k.'" '.$sel.'>'.$v.'</option>';
}
 ?>
 </select>
  </div>
  <div class="clear"></div>
 </div>  
<?php } ?>  
<div class="crm_field">
  <div class="crm_field_cell1"><label><?php esc_html_e('Zoho Access','woocommece-zoho-crm'); ?></label></div>
  <div class="crm_field_cell2">
  <?php if(isset($info['access_token'])  && $info['access_token']!="") {
  ?>
  <div style="padding-bottom: 8px;" class="vx_green"><i class="fa fa-check"></i> <?php
  echo sprintf(__("Authorized Connection to %s on %s",'woocommece-zoho-crm'),'<code>Zoho</code>',date('F d, Y h:i:s A',$info['_time']));
?></div><?php
  }else{
      $ret=$link.'&'.$this->id."_tab_action=get_token&vx_action=redirect&id=".$id."&vx_nonce=".$nonce;
$dc=!empty($info['dc']) ? $info['dc'] : 'com';
$ret_dc=$ret.'&dc='.$dc;
$scope='ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.users.Read,ZohoCRM.coql.READ';
if(!empty($info['type'])){
    if($info['type'] == 'invoices'){
$scope='ZohoInvoice.invoices.ALL,ZohoInvoice.contacts.ALL,ZohoInvoice.settings.ALL,ZohoInvoice.estimates.ALL,ZohoInvoice.expenses.ALL,ZohoInvoice.projects.ALL,ZohoInvoice.creditnotes.ALL,ZohoInvoice.customerpayments.ALL';
}else if($info['type'] == 'books'){
$scope='ZohoBooks.fullaccess.all';
}else if($info['type'] == 'inventory'){
$scope='ZohoInventory.FullAccess.all';
}else if($info['type'] == 'bigin'){
$scope='ZohoBigin.modules.ALL,ZohoBigin.settings.ALL,ZohoBigin.users.Read';
}  }
$auth_url='oauth/v2/auth?scope='.$scope.'&response_type=code&client_id='.$client['client_id'].'&access_type=offline&redirect_uri='.urlencode($client['call_back']);
$ac_url=$api->ac_url();      
?>
  <a class="button button-default button-hero sf_login" data-id="<?php echo esc_html($client['client_id']) ?>" href="<?php echo $ac_url.$auth_url.'&state='.urlencode($ret_dc) ?>"  data-state="<?php echo urlencode($ret); ?>" data-url="<?php echo $auth_url ?>" target="_self" title="<?php esc_html_e("Login with Zoho",'woocommece-zoho-crm'); ?>" > <i class="fa fa-lock"></i> <?php esc_html_e("Login with Zoho",'woocommece-zoho-crm'); ?></a>
<?php 
  }

if( (isset($_POST['vx_test_connection']) || empty($info['access_token'])) && !empty($info['error'])){
 ?><div style="border-left: 4px solid #d00000; background: #fff; padding: 12px; margin-top: 12px;"><?php echo $info['error']; ?></div><?php   
}  
   ?>
  </div>
  <div class="clear"></div>
  </div>                  
<?php if(isset($info['access_token'])  && $info['access_token']!="") { ?>
<div class="crm_field">
  <div class="crm_field_cell1"><label><?php esc_html_e("Revoke Access",'woocommece-zoho-crm'); ?></label></div>
  <div class="crm_field_cell2">  <a class="button button-secondary" id="vx_revoke" href="<?php echo esc_url($link."&".$this->id."_tab_action=get_token&vx_nonce=".$nonce.'&id='.$id);?>"><i class="fa fa-unlock"></i> <?php esc_html_e("Revoke Access",'woocommece-zoho-crm'); ?></a>
  </div>
  <div class="clear"></div>
  </div> 
<?php } ?>
 
  <div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_custom_app_check"><?php esc_html_e("Zoho Client",'woo-zoho'); ?></label></div>
  <div class="crm_field_cell2"><div><label for="vx_custom_app_check"><input type="checkbox" name="crm[custom_app]" id="vx_custom_app_check" value="yes" <?php if($this->post('custom_app',$info) == "yes"){echo 'checked="checked"';} ?>><?php echo sprintf(__('Use Own Zoho App - If you want to connect one Zoho accounts to multiple sites then use a separate Zoho App for each site. %sView ScreenShots%s ','woo-zoho'),'<a href="https://www.crmperks.com/create-zoho-app-for-connecting-wordpress/" target="_blank">','</a>'); ?></label></div>
  </div>
  <div class="clear"></div>
  </div>

<div id="vx_custom_app_div" style="<?php if($this->post('custom_app',$info) != "yes"){echo 'display:none';} ?>">
     <div class="crm_field">
  <div class="crm_field_cell1"><label for="app_id"><?php esc_html_e("Client ID",'woo-zoho'); ?></label></div>
  <div class="crm_field_cell2">
     <div class="vx_tr">
  <div class="vx_td">
  <input type="password" id="app_id" name="crm[app_id]" class="crm_text" placeholder="<?php esc_html_e("Zoho Client ID",'woo-zoho'); ?>" value="<?php echo esc_html($this->post('app_id',$info)); ?>">
  </div><div class="vx_td2">
  <a href="#" class="button vx_toggle_btn vx_toggle_key" title="<?php esc_html_e('Toggle Consumer Key','woo-zoho'); ?>"><?php esc_html_e('Show Key','woo-zoho') ?></a>
  
  </div></div>
  
    <div class="howto">
  <ol>
  <li><?php echo sprintf(__('Create New Client %shere%s','woo-zoho'),'<a href="https://accounts.zoho.com/developerconsole" target="_blank">','</a>'); ?></li>
  <li><?php esc_html_e('Enter Client Name(eg. My App)','woo-zoho'); ?></li>
  <li><?php echo sprintf(__('Enter %s or %s in Redirect URI','woo-zoho'),'<code>https://www.crmperks.com/google_auth/</code>','<code>'.$link."&".$this->id.'_tab_action=get_code</code>'); ?>
  </li>
<li><?php esc_html_e('Select Client Type as "Web Based"','woo-zoho'); ?></li>
<li><?php esc_html_e('Save Application','woo-zoho'); ?></li>
<li><?php echo __('Copy Client Id and Secret','woo-zoho'); ?></li>
   </ol>
  </div>
  
</div>
  <div class="clear"></div>
  </div>
     <div class="crm_field">
  <div class="crm_field_cell1"><label for="app_secret"><?php esc_html_e("Client Secret",'woo-zoho'); ?></label></div>
  <div class="crm_field_cell2">
       <div class="vx_tr" >
  <div class="vx_td">
 <input type="password" id="app_secret" name="crm[app_secret]" class="crm_text"  placeholder="<?php esc_html_e("Zoho Client Secret",'woo-zoho'); ?>" value="<?php echo esc_html($this->post('app_secret',$info)); ?>">
  </div><div class="vx_td2">
  <a href="#" class="button vx_toggle_btn vx_toggle_key" title="<?php esc_html_e('Toggle Consumer Secret','woo-zoho'); ?>"><?php esc_html_e('Show Key','woo-zoho') ?></a>
  
  </div></div>
  </div>
  <div class="clear"></div>
  </div>
       <div class="crm_field">
  <div class="crm_field_cell1"><label for="app_url"><?php esc_html_e("Zoho Client Redirect URL",'woo-zoho'); ?></label></div>
  <div class="crm_field_cell2"><input type="text" id="app_url" name="crm[app_url]" class="crm_text" placeholder="<?php esc_html_e("Zoho Client Redirect URL",'woo-zoho'); ?>" value="<?php echo esc_html($this->post('app_url',$info)); ?>"> 

  </div>
  <div class="clear"></div>
  </div>
  </div>

 <div class="crm_field">
  <div class="crm_field_cell1"><label><?php esc_html_e("Test Connection",'woocommece-zoho-crm'); ?></label></div>
  <div class="crm_field_cell2">      <button type="submit" class="button button-secondary" name="vx_test_connection"><i class="fa fa-refresh"></i> <?php esc_html_e("Test Connection",'woocommece-zoho-crm'); ?></button>
  </div>
  <div class="clear"></div>
  </div> 
  <div class="crm_field">
  <div class="crm_field_cell1"><label for="vx_error_email"><?php esc_html_e("Notify by Email on Errors",'woo-zoho'); ?></label></div>
  <div class="crm_field_cell2"><textarea name="crm[error_email]" id="vx_error_email" placeholder="<?php esc_html_e("Enter comma separated email addresses",'woo-zoho'); ?>" class="crm_text" style="height: 70px"><?php echo isset($info['error_email']) ? esc_html($info['error_email']) : ""; ?></textarea>
  <span class="howto"><?php esc_html_e("Enter comma separated email addresses. An email will be sent to these email addresses if an order is not properly added to Zoho. Leave blank to disable.",'woo-zoho'); ?></span>
  </div>
  <div class="clear"></div>
  </div>   


  <button type="submit" value="save" class="button-primary" title="<?php esc_html_e('Save Changes','woo-zoho'); ?>" name="save"><?php esc_html_e('Save Changes','woo-zoho'); ?></button>  
 
 
 <?php
if(!empty($info['access_token']) && self::$is_pr){ 

$is_crm=false;  
if(empty($info['type']) || $info['type'] == 'crmplus'){
 $is_crm=true;      
}  
if(!isset($meta['tax_codes']) || !empty($_POST['vx_refresh_tax'])){
$tax_codes=array();
if($is_crm){
$res=$api->post_crm('settings/fields?module=Products');    
      if(!empty($res['fields'])){
    $ops=array();
      foreach($res['fields'] as $vv){
          if($vv['api_name'] == 'Tax' && !empty($vv['pick_list_values'])){
           foreach($vv['pick_list_values'] as $kk){
          $ops[$kk['actual_value']]=$kk['display_value'];       
           }   
          }
       
      }
     $tax_codes=$ops;     
   } 
      
}else{
$res=$api->post_crm('settings/taxes'); 
 if(!empty($res['taxes'])){
    $ops=array();
      foreach($res['taxes'] as $vv){
   $ops[$vv['tax_id']]=$vv['tax_name'];      
      }
     $tax_codes=$ops;     
   }
}

 
$meta['tax_codes']=$tax_codes;
  if(isset($info['id'])){
$this->update_info( array("meta"=>$meta) , $info['id'] );
} }

//if(!$is_crm){
?>
<h3 style="margin-top: 60px"><?php esc_html_e('Map WooCommerce Tax Rates to Zoho Tax Codes','woo-zoho'); ?></h3>
<p><?php echo __('In case of multiple taxes, please create a tax group in Zoho and map first priority WooCommerce tax rate to Zoho Tax Group.','woo-zoho'); ?> </p>
  
<div class="crm_fields_table">
<div class="crm_field">
  <div class="crm_field_cell1"><label><?php esc_html_e('Zoho Tax Codes ','woo-zoho'); ?></label></div>
  <div class="crm_field_cell2">
  <button type="submit" class="button button-secondary" name="vx_refresh_tax" value="yes"><i class="fa fa-refresh"></i> <?php esc_html_e('Refresh Tax Codes','woo-zoho'); ?></button>
  </div>
<div class="clear"></div>
</div>
  
<?php
if(!empty($meta['tax_codes'])){
 //$tax_class=get_option('woocommerce_shipping_tax_class');   
// $tax_class=get_option('woocommerce_tax_classes');  
 $tax_class=get_option('woocommerce_tax_display_cart');   
// $tax_class=get_option('woocommerce_tax_classes');   
 $woo_rates=array();
// $tax_classes=WC_Tax::get_tax_classes();
 $tax_classes=wc_get_product_tax_class_options();
 if(is_array($tax_classes) && !empty($tax_classes)){
     $tax_classes=array('standard'=>'')+$tax_classes;
 foreach($tax_classes as $tax_k=>$tax_name){
     
  $woo_arr=WC_Tax::get_rates_for_tax_class($tax_name); 
  if(!empty($woo_arr)){
      if(empty($tax_name)){ $tax_name='Standard'; }
    $woo_rates[$tax_k]=$tax_name.' - Class';
      foreach($woo_arr as $tax){
            if($tax->tax_rate_priority < 2){
       $woo_rates[$tax->tax_rate_id]=$tax->tax_rate_name.' '.trim($tax->tax_rate_country.' '.$tax->tax_rate_state);  
            } 
      }
  }  
 }
 }
 $tax_map=!empty($meta['tax_map']) ? $meta['tax_map'] : array(); 
 foreach($tax_map as $k=>$woo_tax){
     if(!isset($woo_rates[$woo_tax])){
       $woo_rates[$woo_tax]=$woo_tax;  
     }
 }
foreach($meta['tax_codes'] as $k=>$v){    
 ?>
<div class="crm_field">
  <div class="crm_field_cell1"><label><?php echo $v ?></label></div>
  <div class="crm_field_cell2">
<select name="tax[<?php echo esc_attr($k) ?>]" class="crm_text vx_woo_tax">
<option value=""><?php esc_html_e('Select WooCommerce Tax Rate ','woo-zoho'); ?></option>
<?php 
if(!empty($woo_rates)){
  foreach($woo_rates as $tax_k=>$tax){
    $sel='';
if(!empty($tax_map[$k]) && $tax_map[$k]  == $tax_k){ $sel='selected="selected"'; }
echo '<option value="'.$tax_k.'" '.$sel.'>'.$tax.'</option>';
} }
 ?>
 </select>
  </div>
<div class="clear"></div>
</div> 
 <?php   
} }
?>
   <button type="submit" value="save" class="button-primary" title="<?php esc_html_e('Save Changes','woo-zoho'); ?>" name="save_tax"><?php esc_html_e('Save Tax Codes','woo-zoho'); ?></button> 
    </div>
<?php
//} 
}
 ?>
 
  </div>  

  <script type="text/javascript">

  jQuery(document).ready(function($){


  $(".vx_tabs_radio").click(function(){
  $(".vx_tabs").hide();   
  $("#tab_"+this.id).show();   
  }); 
$(".sf_login").click(function(e){
    if($("#vx_custom_app_check").is(":checked")){
    var client_id=$(this).data('id');
    var new_id=$("#app_id").val();
    if(client_id!=new_id){
          e.preventDefault();   
     alert("<?php esc_html_e('Zoho Client ID Changed.Please save new changes first','woo-zoho') ?>");   
    }    
    }
})
  $("#vx_custom_app_check").click(function(){
     if($(this).is(":checked")){
         $("#vx_custom_app_div").show();
     }else{
            $("#vx_custom_app_div").hide();
     } 
  });
    $(document).on('click','#vx_revoke',function(e){
  
  if(!confirm('<?php esc_html_e('Notification - Remove Connection?','woo-zoho'); ?>')){
  e.preventDefault();   
  }
  });
  
  $('.vx_woo_tax').select2({ placeholder: '<?php esc_html_e('Select WooCommerce Tax Rate OR Enter custom Tax','woo-zoho') ?>',tags:true,allowClear: true});
     
  })
  </script>  