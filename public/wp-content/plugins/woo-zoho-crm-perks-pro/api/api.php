<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if(!class_exists('vxc_zoho_api')){
    
class vxc_zoho_api extends vxc_zoho{
  
         public $token='' ; 
    public $info=array() ; // info
    public $url='';
    public $ac_url='https://accounts.zoho.com/';
    public $error= "";
    public $timeout= "30";
    public static $address=array();

function __construct($info) {
     
    if(isset($info['data'])){ 
       $this->info= $info['data'];
     
       $domain='com';
       if(isset($this->info['dc'])){
       $domain=$this->info['dc'];    
       }
       $this->ac_url='https://accounts.zoho.'.$domain.'/';
    }
    if(!isset($this->info['type'])){
           $this->info['type']='';
    }
}
public function get_token(){
    $users=$this->get_users();
 
    $info=$this->info;
    if(is_array($users) && count($users)>0){
    $info['valid_token']='true';    
    }else{
        $info['zoho_error']=$users;
      unset($info['valid_token']);  
    }
return $info;
}
/**
  * Get New Access Token from infusionsoft
  * @param  array $form_id Form Id
  * @param  array $info (optional) Infusionsoft Credentials of a form
  * @param  array $posted_form (optional) Form submitted by the user,In case of API error this form will be sent to email
  * @return array  Infusionsoft API Access Informations
  */
public function refresh_token($info=""){
  if(!is_array($info)){
  $info=$this->info;
  }

  if(!isset($info['refresh_token']) || empty($info['refresh_token'])){
   return $info;   
  }
    $ac_url=$this->ac_url(); 
  $client=$this->client_info(); 
  ////////it is oauth    
  $body=array("client_id"=>$client['client_id'],"client_secret"=>$client['client_secret'],"redirect_uri"=>$client['call_back'],"grant_type"=>"refresh_token","refresh_token"=>$info['refresh_token']);
  $re=$this->post_crm($ac_url.'oauth/v2/token','token',$body);

  if(isset($re['access_token']) && $re['access_token'] !=""){ 
  $info["access_token"]=$re['access_token'];
 // $info["refresh_token"]=$re['refresh_token'];
 // $info["org_id"]=$re['id'];
  $info["class"]='updated';
  $info["token_time"]=time(); 
  $info['valid_token']='true'; 
  }else{
      $info['valid_token']=''; 
  $info['error']=$re['error'];
  $info['access_token']="";
   $info["class"]='error';
  } 
  //api validity check
  $this->info=$info;
  //update infusionsoft info 
  //got new token , so update it in db
  $this->update_info( array("data"=> $info),$info['id']); 
  return $info; 
  }
public function handle_code(){
      $info=$this->info;
      $id=$info['id'];
 
        $client=$this->client_info();
  $log_str=array(); $token=array();
  $ac_url=$this->ac_url(); 
  if(isset($_REQUEST['code'])){
  $code=$this->post('code'); 
  
  if(!empty($code)){

     
  $body=array("client_id"=>$client['client_id'],"client_secret"=>$client['client_secret'],"redirect_uri"=>$client['call_back'],"grant_type"=>"authorization_code","code"=>$code);
  $token=$this->post_crm($ac_url.'oauth/v2/token','token',$body);
  }
  if(isset($_REQUEST['error'])){
   $token['error']=$this->post('error');   
  }
  if(empty($token['refresh_token'])){
      $token['access_token']='';
      $dc=!empty($info['dc']) ? $info['dc'] : 'com';
      if(empty($token['error'])){
      $token['error']='You can connect one Zoho account to one location only. if you want to connect one zoho account to multiple locations then please use <b>own zoho App</b> for each location. If you want to dissconnect from other locations then Go to <a href="'.$ac_url.'u/h#sessions/userconnectedapps" target="_blank">accounts.zoho.'.$dc.' -> Sessions -> Connected Apps</a> and remove "CRM Perks" app'; 
  } }
  
  }else if(!empty($info['refresh_token'])){
        $token=$this->post_crm($ac_url.'oauth/v2/token/revoke','token',array('token'=>$info['refresh_token']));
  }

  $url='';
  if(!empty($token['api_domain'])){
  $url=$token['api_domain'];  
  }

  $info['instance_url']=$url;
  $info['access_token']=$this->post('access_token',$token);
  $info['token_exp']=$this->post('expires_in_sec',$token);
  $info['client_id']=$client['client_id'];
  $info['_id']=$this->post('id',$token);
  $info['refresh_token']=$this->post('refresh_token',$token);
  $info['token_time']=time();
  $info['_time']=time();
  $info['error']=$this->post('error',$token);
  $info['api']="api";
  $info["class"]='error';
  $info['valid_token']=''; 
  $info['api_check']=''; 
  if(!empty($info['access_token'])){
  $info["class"]='updated';
  $info['valid_token']='true'; 
  }

  $this->info=$info;

  $this->update_info( array('data'=> $info) , $id); //var_dump($info); die();
  return $info;
  }

public function ac_url(){
    $dc='com';
    if(!empty($this->info['dc'])){
    $dc=$this->info['dc'];    
    }
    $zoho='zoho.'; if($dc == 'ca'){$zoho='zohocloud.';}
    $this->ac_url='https://accounts.'.$zoho.$dc.'/';
  return $this->ac_url;  
}
public function get_crm_objects(){
    $type=$this->info['type']; 
if( in_array($type,array('invoices','books','inventory'))){
    if($type == 'inventory'){
  $objs=array('contacts'=>'Contacts','invoices'=>'Invoices','customerpayments'=>'Customer Payments','creditnotes'=>'Credit Notes');      
    }else{
$objs=array('contacts'=>'Contacts','invoices'=>'Invoices','estimates'=>'Estimates','customerpayments'=>'Customer Payments','creditnotes'=>'Credit Notes','recurringinvoices'=>'Recurring Invoices'); //,'contactpersons'=>'Contact Persons'
    }
if( in_array($type, array('books','inventory'))){
    $objs['purchaseorders']='Purchase Orders';
    $objs['salesorders']='Sales Orders';
}
$objs['items']='Items';  
return $objs;
}
$arr= $this->post_crm('settings/modules');
//var_dump($arr);
$skip=array('Associated_Products');
if(!empty($arr['modules'])){
$objects=$arr['modules'];  
  $objects_f="";
  if(is_array($objects)){
        $objects_f=array();
     foreach($objects as $object){
         if(isset($object['editable']) && $object['editable'] == true && !in_array($object['api_name'],$skip) && $object['visibility'] == 1){ // && $object['status'] == 'visible' will hide Notes
             if($object['generated_type'] == 'custom'){
            $object['plural_label'].=' (Custom)';     
             }
    $objects_f[$object['api_name']]=$object['plural_label'];   
         }
     }    
  }
 return $objects_f;   
}else{
    if(is_array($arr)){
      if(isset($arr['message'])){
       $arr=$arr['message'];   
      }else if(isset($arr['error'])){
       $arr=$arr['error'];   
      }else{
        $arr=json_encode($arr);  
      }  
    }
 return $arr;   
}

}
public function get_layouts($module){
$arr= $this->post_crm('settings/layouts?module='.$module);

if(!empty($arr['layouts'])){
        $objects_f=array();
foreach($arr['layouts'] as $object){
    if(!empty($object['visible'])){
    $objects_f[$object['id']]=$object['name'];
    }   
}    
 return $objects_f;   
}else if(isset($arr['error'])){
 return $arr['error'];   
}

}
public function get_fields_crm($module){
  $fields=array();
  $arr=$this->post_crm('settings/fields?module='.$module);
 //$arr= $this->post_crm('settings/modules');
//var_dump($arr);
if(isset($arr['fields']) && is_array($arr['fields'])){
foreach($arr['fields'] as $field){
 
if( isset($field['field_read_only']) && $field['field_read_only'] === false && !in_array($field['data_type'],array('fileupload')) ){ //visible = true
            $name=$field['api_name'];
            if(in_array($name,array('Product_Details','Tag'))){
                continue;
            }
        $v=array('label'=>$field['field_label'],'name'=>$field['api_name'],'type'=>$field['data_type']);
       if(isset($field['custom_field']) && $field['custom_field'] === true){
       $v['custom']='yes';    
       }
       if( $v['type'] == 'lookup' ){
           if(!empty($field['lookup']['module'])){
          $v['module']=$field['lookup']['module'];     
           }   
       }else  if($v['type'] == 'multiselectlookup'){ ///var_dump($field);
           if(!empty($field['multiselectlookup']['connected_module'])){
          $v['module']=$field['multiselectlookup']['connected_module']['api_name'];   
          $v['linking_module']=$field['multiselectlookup']['linking_module']['api_name'];    
          $v['module_field']=$field['multiselectlookup']['connectedfield_apiname']; //or lookup_apiname  both are same

         
} 
if(empty($v['module_field'])){ continue; }  
}
       
//$v['req']=$required;
if(isset($field['length'])){
$v["maxlength"]=$field['length'];
}
       if(!empty($field['pick_list_values'])){
         $ops=$eg=array();
         foreach($field['pick_list_values'] as $op){
         $ops[]=array('value'=>$op['display_value'],'label'=>$op['display_value']);
         $eg[]=$op['display_value'].'='.$op['display_value'];
         }  
      if( strtolower($field['api_name']) !='stage'){      
       $v['options']=$ops;
     }
       $v['eg']=implode(', ',array_slice($eg,0,10));
       }  
$fields[$name]=$v;   
        }         
}
if(in_array($module,array('Sales_Orders','Invoices'))){
    $item_module='Ordered_Items';
    if($module == 'Invoices'){    $item_module='Invoiced_Items'; }
   $item_fields=$this->get_fields_crm($item_module); 
   if(is_array($item_fields)){
   foreach($item_fields as $kk=>$vv){
   if(!in_array($kk,array('Created_Time','Parent_Id','Total_After_Discount','Net_Total','Total'))){
       $vv['label'].=' - Line item';
       $vv['name']=$kk='vxline_'.$vv['name'];
       $vv['is_item']='1';
       $fields[$kk]=$vv;
   }    
   }
 }
}
if(in_array($module,array('Ordered_Items','Invoiced_Items'))){ return $fields; }
$fields['tags']=array('label'=>'Tags','name'=>'tags','type'=>'tags','maxlength'=>'0'); 
if($this->info['type'] == ''){
$fields['GCLID']=array('label'=>'GCLID','name'=>'GCLID','type'=>'text','maxlength'=>'100'); 
}
if($this->info['type'] == 'bigin' && strtolower($module) == 'deals' && !isset($fields['pipeline']) ){
$fields['Pipeline']=array('label'=>'Pipeline','name'=>'Pipeline','type'=>'text','maxlength'=>'100'); 
}
if(isset($fields['Grand_Total'])){
  //  $currency_symbol
 $fields['currency_symbol']=array('label'=>'Currency Symbol','name'=>'currency_symbol','type'=>'text','maxlength'=>'100');    
}
//if(in_array($module,array('Leads','Contacts'))){
$fields['vx_attachments']=array('label'=>'Attachments - Related List','name'=>'vx_attachments','type'=>'files','maxlength'=>'0','custom'=>'yes');  
$fields['vx_attachments2']=array('label'=>'Attachments - Related List 2','name'=>'vx_attachments2','type'=>'files','maxlength'=>'0','custom'=>'yes');  
$fields['vx_attachments3']=array('label'=>'Attachments - Related List 3','name'=>'vx_attachments3','type'=>'files','maxlength'=>'0','custom'=>'yes');  
$fields['vx_attachments4']=array('label'=>'Attachments - Related List 4','name'=>'vx_attachments4','type'=>'files','maxlength'=>'0','custom'=>'yes');  
$fields['vx_attachments5']=array('label'=>'Attachments - Related List 5','name'=>'vx_attachments5','type'=>'files','maxlength'=>'0','custom'=>'yes'); 
$fields['zoho_triggers']=array('label'=>'Zoho Triggers','name'=>'zoho_triggers','type'=>'text','maxlength'=>'100','custom'=>'yes','eg'=>'workflow,approval,blueprint');  
}
else if(!empty($arr['message'])){
 $fields=$arr['message'];   
}

return $fields;  
}

public function get_fields_invoice($module){
    
$json['invoices']='["reference_number","place_of_supply","gst_treatment","gst_no","template_id","date","payment_terms","payment_terms_label","due_date","discount","tax_total","shipping_charge","is_discount_before_tax","discount_type","is_inclusive_tax","exchange_rate","recurring_invoice_id","invoiced_estimate_id","salesperson_name","project_id","allow_partial_payments","notes","terms","adjustment","adjustment_description","reason","tax_authority_id","tax_exemption_id","invoice_number","tax_id","tax_treatment","vat_treatment","branch_id","reference_invoice_type","payment_options","allow_partial_payments","pricebook_id"]'; //recurringinvoices
 
 $json['salesorders']='["salesorder_number","reference_number","shipment_date","date","notes","terms","discount","shipping_charge","shipping_charge","is_discount_before_tax","discount_type","delivery_method","adjustment","adjustment_description","pricebook_id","salesperson_id","salesperson_name","is_inclusive_tax","exchange_rate","template_id","place_of_supply","gst_treatment","gst_no","tax_id","tax_treatment","branch_id","pricebook_id"]';
 
  $json['recurringinvoices']='["recurrence_name","start_date","end_date","recurrence_frequency","repeat_every","tax_id","email","gst_no","gst_treatment","place_of_supply","source_of_supply","destination_of_supply","abn","vendor_id","payment_terms","payment_terms_label","is_discount_before_tax","shipping_charge","adjustment","adjustment_description","quantity","unit","rate","description","name","discount","branch_id"]';
 
 $json['purchaseorders']='["vendor_id","purchaseorder_number","reference_number","place_of_supply","source_of_supply","destination_of_supply","gst_treatment","tax_treatment","gst_no","template_id","date","delivery_date","discount","tax_total","is_discount_before_tax","is_inclusive_tax","exchange_rate","billing_address_id","discount_account_id","salesorder_id","notes","terms","adjustment","adjustment_description"]';
 
 $json['contacts']='["contact_name","company_name","contact_type","customer_sub_type","salutation","first_name","last_name","email","phone","mobile","skype","designation","department","website","billing_attention","billing_address","billing_street2","billing_state_code","billing_city","billing_state","billing_zip","billing_country","billing_fax","billing_phone","shipping_attention","shipping_address","shipping_street2","shipping_state_code","shipping_city","shipping_state","shipping_zip","shipping_country","shipping_fax","shipping_phone","contact_persons","language_code","notes","place_of_contact","gst_no","gst_treatment","tax_treatment","vat_treatment","tax_exemption_id","tax_authority_id","tax_id","payment_terms","payment_terms_label","is_portal_enabled","facebook","twitter","currency_code","currency_id","pricebook_id"]';

 
 $json['contactpersons']='["salutation","first_name","last_name","email","phone","mobile","skype","designation","department","enable_portal"]';
   
 $json['estimates']='["contact_persons","template_id","place_of_supply","gst_treatment","tax_treatment","gst_no","estimate_number","reference_number","date","expiry_date","exchange_rate","discount","is_discount_before_tax","discount_type","is_inclusive_tax","salesperson_name","notes","terms","shipping_charge","adjustment","adjustment_description","tax_id","tax_exemption_id","tax_authority_id","branch_id"]';
    
 $json['customerpayments']='["payment_mode","amount","date","reference_number","description","exchange_rate","bank_charges","account_id","tax_account_id","branch_id","send_paid_invoice_to"]';
 
 $json['items']='["name","sku","rate","description","description","unit","product_type","item_type","initial_stock","initial_stock_rate","is_taxable","tax_id","avatax_tax_code","avatax_use_code","hsn_or_sac","tax_specification","upc","ean","isbn","part_number","pricebook_rate","purchase_rate","reorder_level","purchase_description","inventory_account_id","purchase_account_id"]';
  
  $module_a=$module;
   if( in_array($module, array('recurringinvoices1','creditnotes'))){
       $module_a='invoices';
   }
$fields=array(); $req=array('contact_name'); $dates=array('date','start_date','end_date','shipment_date'); 
$bool=array('is_discount_before_tax','is_taxable','allow_partial_payments');
$ops=array('is_portal_enabled'=>array('1'=>'true','0'=>'false'),'contact_type'=>array('customer','business','vendor'),'customer_sub_type'=>array('business','individual'),'product_type'=>array('goods','service'),'item_type'=>array('sales','purchases','sales_and_purchases','inventory'));
$egs=array('reference_invoice_type'=>'registered','payment_options','payment_options'=>'paypal, authorize_net, payflow_pro, stripe, 2checkout, braintree');

if(isset($json[$module_a])){
$arr=json_decode($json[$module_a],true);
 if(!empty($arr)){
foreach($arr as $v){
    $label=ucwords(str_replace('_',' ',$v));
   $field=array('label'=>$label,'type'=>'Text','name'=>$v);
   if(isset($ops[$v])){
       $op=array();
       foreach($ops[$v] as $c){
       $op[]=array('value'=>$c);    
       }
   $field['options']=$op;    
   $field['type']='list';    
   }
   if($v == 'tax_id'){
   $res=$this->post_crm('settings/taxes'); 
   if(!empty($res['taxes'])){
    $ops=array();
      foreach($res['taxes'] as $vv){
   $ops[]=array('label'=>$vv['tax_name'],'value'=>$vv['tax_id']);       
      }
     $field['options']=$ops;    
   $field['type']='list';   
   }    
   }
   if($v == 'currency_id'){
   $res=$this->post_crm('settings/currencies'); 
   if(!empty($res['currencies'])){
    $ops=array();
      foreach($res['currencies'] as $vv){
   $ops[]=array('label'=>$vv['currency_code'].' - '.$vv['currency_name'],'value'=>$vv['currency_id']);      // 
      }
     $field['options']=$ops;    
   $field['type']='list';   
   }    
   }
    if(in_array($v,$req)){
        $field['req']='1';
    }
    if(in_array($v,$dates)){
        $field['type']='date';
    }
    if(in_array($v,$bool)){
        $field['type']='bool';
    }
   if(isset($egs[$v])){
       $field['eg']=$egs[$v];
   } 
    $fields[$v]=$field;
}

if(!empty( $fields['tax_id']) && $module == 'contacts'){
$field=$fields['tax_id'];
$field['name']='tax_id_new';
$field['label']='Tax ID (Apply if already not set)';
    $fields['tax_id_new']=$field;    
}
if($module == 'creditnotes'){
$field=array('type'=>'number','name'=>'amount','label'=>'Refund Amount');
    $fields['amount']=$field;   
$field=array('type'=>'text','name'=>'description','label'=>'Refund Description');
    $fields['description']=$field;  
$field=array('type'=>'text','name'=>'from_account_id','label'=>'Refund From Account');
    $fields['from_account_id']=$field; 
$field=array('type'=>'text','name'=>'refund_mode','label'=>'Refund Mode');
    $fields['refund_mode']=$field;     
}     
}    } 
$custom=array('invoices','contacts','estimates','purchaseorders','salesorders','items','customerpayments'); 
if(in_array($module,$custom)){
$module=rtrim($module,'s'); 
if($module == 'customerpayment'){ 
    $arr=$this->post_crm('settings/customfields');

if(isset($arr['customfields']['customer_payment'])){
  $arr=array('customfields'=>$arr['customfields']['customer_payment']);  
} 

}else{
    ///$module='customer_payment';
    $arr=$this->post_crm('settings/customfields/'.$module);
}  

if(!empty($arr['customfields'])){
  foreach($arr['customfields'] as $v){ 
     $id=$v['index'];
     if(empty($v['data_type'])){ $v['data_type']='text'; }
     // $id=$this->info['type'] == 'books' ? $v['index'] : $v['customfield_id'];
      $field=array('label'=>$v['label'],'type'=>$v['data_type'],'name'=>$id,'is_custom'=>'1');
      if(!empty($v['values'])){
          $ops=$eg=array();
          foreach($v['values'] as $op){
         $ops[]=array('value'=>$op['name'],'label'=>$op['name']);
         $eg[]=$op['name'];
         }  
       $field['options']=$ops;
       $field['eg']=implode(', ',array_slice($eg,0,10));
      }
    $fields[$id]=$field;  
  }  
}
}  

return $fields;
}

public function get_crm_fields($module,$fields_type=""){
 if( in_array($this->info['type'],array('invoices','books','inventory'))){
 $fields=$this->get_fields_invoice($module);   //get_fields_invoice 
 }else{ 
$fields=$this->get_fields_crm($module);
 } 
if(is_array($fields)){
     if(!empty($fields['Adjustment']) || !empty($fields['adjustment'])){
  $fields['vx_ship_entry']=array('name'=>'vx_ship_entry',"type"=>'text','label'=>'Zoho Item ID - for Shipping as line item');
  if(isset($fields['tax_id'])){ //|| isset($fields['Tax'])
     // $ship_tax=isset($fields['tax_id']) ? $fields['tax_id'] : $fields['Tax'];
      $ship_tax=$fields['tax_id'];
      $ship_tax['name']='vx_ship_entry_tax';
      $ship_tax['label']='Shipping Tax';
      $fields['vx_ship_entry_tax']=$ship_tax;
  }
  if(!isset($fields['shipping_charge'])){
  $fields['shipping_charge']=array('name'=>'shipping_charge',"type"=>'text','label'=>'Shipping Charge for line item');
  } }
 
    /*    if(in_array($module,array('SalesOrders','PurchaseOrders'))){
      $fields['sub_total']=array('label'=>'Sub Total','name'=>'sub_total','type'=>'text','maxlength'=>'100');  
      $fields['grand_total']=array('label'=>'Grand Total','name'=>'grand_total','type'=>'text','maxlength'=>'100');  
      $fields['tax']=array('label'=>'Tax','name'=>'tax','type'=>'text','maxlength'=>'100');  
      $fields['adjustment']=array('label'=>'Adjustment','name'=>'adjustment','type'=>'text','maxlength'=>'100');
    }*/
/*
$arr=$this->post_crm('settings/related_lists?module='.$module);
 if(!empty($arr['related_lists'])){
     foreach($arr['related_lists'] as $field){ 
      $v=array('label'=>$field['display_label'].' - Related List','name'=>$field['api_name'],'type'=>'related_list'); 
  $fields[$field['api_name']]=$v;       
     }
 }*/   
if($fields_type =="options"){
$field_options=array();
if(is_array($fields)){
foreach($fields as $k=>$f){
if(isset($f['options']) && is_array($f['options']) && count($f['options'])>0){
$field_options[$k]=$f;         
}
}    
}
return $field_options;
}    
}

return $fields; 
}
/**
  * Get campaigns from salesforce
  * @return array Salesforce campaigns
  */
public function get_campaigns(){ 

   $arr= $this->post_crm('Campaigns','get',array('fields'=>'Campaign_Name,id'));
  ///seprating fields
  $msg='No Campaign Found';
$fields=array();
if(!empty($arr['data'])){
foreach($arr['data'] as $val){
$fields[$val['id']]=$val['Campaign_Name'];
}
   
}else if(isset($arr['message'])){
 $msg=$arr['message'];   
}

  return empty($fields) ? $msg : $fields;
}
/**
  * Get users from zoho
  * @return array users
  */
public function get_users(){ 
if(in_array($this->info['type'], array('invoices','books','inventory') )){
    return $this->get_users_invoices();    
}
$arr=$this->post_crm('users?type=AllUsers');
$users=array();    
  ///seprating fields
  $msg='No User Found';
if(!empty($arr['users'])){
if(is_array($arr['users']) && isset($arr['users'][0])){
  foreach($arr['users'] as $k=>$v){
   $users[$v['id']]=$v['full_name'];   
  }  
}
}else if(isset($arr['message'])){
 $msg=$arr['message'];   
}

return empty($users) ? $msg : $users;
}
public function get_users_invoices(){ 

$arr=$this->post_crm('users');

$users=array();    
  ///seprating fields
  $msg='No User Found';
if(!empty($arr['users'])){
if(is_array($arr['users']) && isset($arr['users'][0])){
  foreach($arr['users'] as $k=>$v){
   $users[$v['user_id']]=$v['name'];   
  }  
}
}else if(isset($arr['message'])){
 $msg=$arr['message'];   
}

return empty($users) ? $msg : $users;
}
/**
  * Get users from zoho
  * @return array users
  */
public function get_price_books(){ 

$arr=$this->post_crm('Price_Books','get',array('fields'=>'Price_Book_Name,id'));

  ///seprating fields
  $msg=__('No Price Book Found','woocommerce-salesforce-crm');
$fields=array();
if(!empty($arr['data'])){
foreach($arr['data'] as $val){
$fields[$val['id']]=$val['Price_Book_Name'];
}
   
}else if(isset($arr['message'])){
 $msg=$arr['message'];   
}
  return empty($fields) ? $msg : $fields;
}

public function push_object($module,$fields,$meta){

if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set( 'precision', 17 );
    ini_set( 'serialize_precision', -1 ); //prevents json_encode 1.33665654545588444487 , zoho does not accept it
}    
     
/*
  $path='Invoices/135465000000197020/Products/135465000000197001';
    $path='Sales_Orders/283812000000682013';
    $path='Sales_Orders/283812000000935001';
   // $path='Leads/55427000036833113';
   $data=array('id'=>'402178000000311057','List_Price'=>25,'Quantity'=>3);  //,'Parent_Id'=>'402178000000303048'
   $data=array('Product'=>array('id'=> '402178000000311057'),'List_Price'=>25,'Quantity'=>4);  //
   $data=array('Deal_Name'=>'test deal3','Stage'=>'Needs Analysis','Pipeline'=>'Sales Pipeline','Associated_Products'=>array($data));
   
   //$post=json_encode(array('data'=>array(array('Associated_Products'=>array($data)))) );
   $post=json_encode(array('data'=>array($data )) );
   $path='Deals/402178000000303048/Products';    
   $path='Associated_Products';    
   //$path='Deals/402178000000303040';    
   $path='Deals/402178000000311101';    
   $res=$this->post_crm($path,'put',$post);  
      $path='Products/283812000001092001';    
   $res=$this->post_crm($path); 
//$res=$this->post_crm($path,'get');  
var_dump($res,$data); die();
 
$res=$this->get_entry('Sales_Orders','3595657000000400001');
var_dump($res); die(); 
//$this->get_crm_objects();
//die();  Drivers_X_Contacts= LinkingModule1 ,d_accounts=LinkingModule2
$p='Drivers/3779612000000197323';
//$p='Contacts/3703799000000313001/CustomModule1';
//$p='Contacts/3703799000000209001';
$post=json_decode($json,true);
//$post=array('file'=>'@'.realpath(__DIR__.'/banner9.png'));
$post=array('attachmentUrl'=>'https://www.express.com.pk/images/NP_ISB/20181225/Sub_Images/1105997112-1.jpg','File_Name'=>'exp.jpg','Size'=>'10');
$post=array('multi_contact'=>array(array('id'=>'3703799000000313001')),'Name'=>'Updated 2');
$post=array('Name'=>'Updated 4','Secondary_Email'=>'admin@local.com','multi_contact'=>''); //,
$post=array('D_Contacts'=>array('id'=>'3779612000000215002'),'mutl_contact'=>array('id'=>'3779612000000197323'));
$post=array('data'=>array($post));
$p='Drivers_X_Contacts';
//$p='d_accounts';
$post=json_encode($post);
$post='{"data":[{"D_Contacts":{"id":"3779612000000215008"},"mutl_contact":{"id":"3779612000000220016"}}]}';

$r=$this->post_crm($p,'post',$post);
//$r=$this->post_crm($p,'get');
var_dump($r); die();

$post=array('Last_Name'=>'lewiss','URL_1'=>'http://google.com','File_Upload_1'=>array(array('entity_Id' => 3.7037990000002E+18)));
//$post=http_build_query($post);
$p='Sales_Orders/149964000000152015/Products/149964000000152001';
$p='Contacts/149964000000140007/Products/149964000000152001';
$p='Sales_Orders/149964000000152015';
$p='Sales_Orders';
$json='{"Subject":"touseefcccdd","Description":"ahmadhcccsdd","Billing_City":"houston","Billing_State":"TA","Billing_Country":"PK","Billing_Code":"","Owner":"149964000000132011","Product_Details":[{"product":{"id":"149964000000151009"},"quantity":2},{"product":{"id":"149964000000152001"},"quantity":5}]}';
$p='Products/149964000000152180/Price_Books/149964000000148008';
$post=json_encode(array('data'=>array(array('qty'=>'1'))));
$post=json_encode(array('data'=>array(array('list_price'=>558))));
//$post=json_encode(array('data'=>array(json_decode($json,true))));
*/

$crm_type=$this->info['type'];
if( in_array($crm_type,array('invoices','books','inventory'))){
    return $this->push_object_invoice($module,$fields,$meta);
}
if( in_array($crm_type,array('books'))){
  //  return $this->push_object_books($module,$fields,$meta);
}
//check primary key
 $extra=array();
$custom_fields= isset($meta['fields']) ? $meta['fields'] : array();
     $files=array();
  for($i=1; $i<6; $i++){
$field_n='vx_attachments';
if($i>1){ $field_n.=$i; }
  if(isset($fields[$field_n]['value'])){
    $files=$this->verify_files($fields[$field_n]['value'],$files);
    unset($fields[$field_n]);  
  }
}
if( is_array($custom_fields) && !empty($custom_fields)){
    foreach($custom_fields as $k=>$v){
        if(!empty($v['is_item']) && isset($meta['map'][$k])){
        $meta['item_fields'][$k]=$meta['map'][$k];
        if(isset($fields[$k])){ unset($fields[$k]); }    
        }
    } 
}

  $debug = isset($_GET['vx_debug']) && current_user_can('manage_options');
  $event= isset($meta['event']) ? $meta['event'] : '';
  
  $id= isset($meta['crm_id']) ? $meta['crm_id'] : '';

  if($debug){ ob_start();}
if(isset($meta['primary_key']) && $meta['primary_key']!="" && isset($fields[$meta['primary_key']]['value']) && $fields[$meta['primary_key']]['value']!=""){    
$search=$fields[$meta['primary_key']]['value'];
$field=$meta['primary_key'];
$field_type= isset($custom_fields[$field]['type']) ? $custom_fields[$field]['type'] : '';
if(!in_array($field_type,array('email','phone1'))){
$field_type='criteria'; 
$search_text=str_replace(array('(',')'),array('\(','\)'),$search);
$search='('.$field.':equals:'.$search_text.')'; 
if(strpos($search,' ')=== false ){
   $search.='and('.$field.':starts_with:'.$search_text.')'; // start_with is required for phones , without this zoho macthes short/invalid phones to long correct ones 
}
}
//$search='((Deal_Name:equals:touseefcccdd ahmad)and(Deal_Name:starts_with:touseefcccdd ahmad))';
    //search object
$path=$module.'/search?'.$field_type.'='.urlencode($search);
$search_response=$this->post_crm($path);
//var_dump($search_response,$path,$search); die();
$extra["body"]=$path;
$extra["search"]=$search;
$extra["response"]=$search_response;
      
  if($debug){
  ?>
  <h3>Search field</h3>
  <p><?php print_r($field) ?></p>
  <h3>Search term</h3>
  <p><?php print_r($search) ?></p>
    <h3>POST Body</h3>
  <p><?php print_r($body) ?></p>
  <h3>Search response</h3>
  <p><?php print_r($search_response) ?></p>  
  <?php
  }
      if(is_array($search_response) && !empty($search_response['data']) ){
          $search_response=$search_response['data'];
      if( count($search_response)>5){
       $search_response=array_slice($search_response,count($search_response)-5,5);   
      }
      $extra["response"]=$search_response;
      $id=$search_response[0]['id'];
  }

}



$post=array(); $status=$action=$method=''; $send_body=true;
 $entry_exists=false;
 $link=""; $error=""; 
 $path='';
 $arr=array();
if($id == ""){
if(empty($meta['new_entry'])){
$method='post';
}else{
    $error='Entry does not exist';
}
$action="Added";  $status="1";
}
else{
 $entry_exists=true;
if($event == 'add_note'){ 
$module='Notes';
$action="Added";
$status="1"; 
$send_body=false;
$post=array('Title'=>$fields['Title']['value'],'Body'=>$fields['Body']['value'],'Parent_Id'=>$fields['ParentId']['value']);   
$arr=$this->post_note($post,$meta['related_object']);
if(isset($arr['data'][0]['details']['id'])){
$id=$arr['data'][0]['details']['id']; 
}
}
else if(in_array($event,array('delete','delete_note'))){
 $send_body=false;
     if($event == 'delete_note'){ 
   $module='Notes';
     }
     $method="delete";
     $action="Deleted";
  $status="5";  
  $path=$module.'?ids='.$id;
}
else{
    //update object
$status="2"; $action="Updated";
if(empty($meta['update'])){
$method='put';
$path=$module.'/'.$id;
}
} }
if(!empty($meta['convert'])){
    if(!empty($id)){
     $path='Leads/'.$id.'/actions/convert';
    $post=array(array('overwrite'=>true,'notify_lead_owner'=>true,'notify_new_entity_owner'=>true));
    $post=json_encode(array('data'=>$post));
    $extra['convert lead']=$res=$this->post_crm($path,'post',$post);
    if(!empty($res['data'][0]['Contacts'])){
       $id=$res['data'][0]['Contacts']; $module='Contacts'; 
    } 
    }else{
$status='';  $error='Lead Does not Exist'; 
    }
    
}else if(!empty($method)){
$zoho_products=$related=array();
$module_products=false;
$multi_lookup=$tags=array(); $product_img='';
if($send_body){
foreach($fields as $k=>$v){
   $type=isset($custom_fields[$k]['type']) ? $custom_fields[$k]['type'] : ''; 
 
    if( in_array($type, array('files','tags') )){
     $related[$type]=$v['value'];   
    }else if($type == 'multiselectlookup'){
     $multi_lookup[$k]=$v['value'];   
    }else if( in_array($type, array('fileupload') )){
//this field is not supported in zoho API  
    }else if($k == 'Tag'){
     $tags=explode(',',$v['value']);   
    }else if($k == 'zoho_triggers'){
     $post['trigger']=explode(',',$v['value']); 
    }else if($k == 'Record_Image'){
 $product_img=$v['value'];
    }else if($k == 'currency_symbol'){
        $k='$currency_symbol';
    }else if($k == 'Tax'){
     $post[$k]=array_map(function($val){return array('value'=>$val);}, explode(',',$v['value']));  
    }else{
        if($k == 'GCLID'){ $k='$gclid'; }
         if($k == 'Pipeline'){ $v['value']=array('id'=>$v['value']); }
    $post[$k]=$this->clean_field($type,$v['value']); 
    }
    if(in_array($type, array('datetime','date'))){
    $fields[$k]['value']=$post[$k];
    }
 if(!empty($v['is_item']) && isset($meta['map'][$k])){
        $meta['item_fields'][$k]=$meta['map'][$k];
        if(isset($fields[$k])){ unset($fields[$k]); }    
}
}
if(!empty($tags)){
    $tag=array();
    foreach($tags as $v){
    $tag[]=array('name'=>trim($v));    
    }
 $post['Tag']=$tag;   
}
if($module != 'Contacts'){
//var_dump($multi_lookup,$post); die('-------');
}
//multi lookup filds do not work with update , in case of duplicate assignment , it stops whole update with error
//$post['drivers_multi_lookup'][]=array('drivers_multi_lookup'=>array('id'=>'619960000000406002')); //driver id
//$post['Multi_Select_Lookup_leads_custom'][]=array('Multi_Select_Lookup_leads_custom'=>array('id'=>'619960000000370019')); //lead id
//$post['Tax']=array(array('value'=>'gst - 7.0 %')); //'id'=>'283812000000680025',
//var_dump($post,$fields); die();
 //change owner id
  if(isset($meta['owner']) && $meta['owner'] == "1"){
   $post['Owner']=$meta['user'];   
   $fields['Owner']=array('label'=>'Owner','value'=>$meta['user']);
  }  
  if(!empty($meta['add_layout'])){
      $layout_field='Layout';
      if(!empty($this->info['type']) && $this->info['type'] == 'bigin' && in_array($module,array('Pipelines'))){
       $layout_field='Pipeline';   
      }
   $post[$layout_field]=array('id'=>$meta['layout']);   
   $fields[$layout_field]=array('label'=>$layout_field,'value'=>$meta['layout']);
  }

  if(!empty($meta['order_items'])){
   $order_res=$this->get_zoho_products($meta);  
  $zoho_products=$order_res['res'];
  //var_dump($zoho_products); die();
  if(is_array($order_res['extra'])){
  $extra=array_merge($extra, $order_res['extra']);
  } 

 if(is_array($zoho_products)){   //&& count($zoho_products)>0
if(in_array($module,array('Sales_Orders','Purchase_Orders','Invoices','Quotes'))){
    $field_name='Ordered_Items';   
    if($module == 'Invoices'){ $field_name='Invoiced_Items';  }else if($module == 'Quotes'){ $field_name='Quoted_Items';  }else if($module == 'Purchase_Orders'){ $field_name='Purchase_Items';  }
    $product_name='Product_Name';  if($crm_type == 'bigin'){ $product_name='Product'; $field_name='Associated_Products'; } 
    
     if(isset($order_res['count']) && !empty($zoho_products) && $order_res['count'] > count($zoho_products)){ //if some item failed , do not process order
    $method=''; $arr=array('code'=>'lines_missmatch','message'=>'Some Zoho line items failed');   
   }
 foreach($zoho_products as $v){  
     $item_arr=array($product_name=>array('id'=>$v['id']),'Quantity'=>$v['qty'],'List_Price'=>floatval($v['cost'])); //,'Tax'=>$v['tax'] //list_price ,unit_price
     //$v['cost'] = total after discounts , no need to add seprate discount
     if(!empty($meta['item_price']) ){
         if($meta['item_price'] == 'dis'){
             
               $item_arr['List_Price']=$v['cost_woo'];  
   if( !empty($v['cost_woo']) && $v['cost_woo'] > $v['cost']){ 
         $item_arr['Discount']=floatval($v['cost_woo']-$v['cost'])*$v['qty'];
         $item_arr['Discount']=round($item_arr['Discount'],2);
     }

         }else if($meta['item_price'] == 'cost'){
       $item_arr['List_Price']=floatval($v['cost_woo']); 
      }else if($meta['item_price'] == 'cost_tax'){
       $item_arr['List_Price']=floatval($v['cost'])+floatval($v['tax']); 
      }
     }
$item_arr['List_Price']=round($item_arr['List_Price'],2);
if(!empty($v['tax_id'])){
    $t_arr=explode(' - ',$v['tax_id']);
    if(is_array($t_arr) && count($t_arr)>1){
    preg_match("|\d+|", $t_arr[1], $int);
    if(isset($int[0]) && !empty($int[0])){
    $item_arr['Line_Tax'][]=array('name'=>trim($t_arr[0]),'percentage'=>floatval($int[0]));     
    } }
}
    if(!empty($v['fields'])){
        foreach($v['fields'] as $kk=>$vv){
        $type=isset($custom_fields[$kk]['type']) ? $custom_fields[$kk]['type'] : ''; 
    
            $item_arr[substr($kk,7)]=$this->clean_field($type,$vv); //
        }
    }

if(!isset($post[$field_name])){ $post[$field_name]=array(); }
$post[$field_name][]=$item_arr; //Discount , Tax  

} 
//var_dump($post['Ordered_Items']); die();
if(!empty($post['vx_ship_entry'])){
    
      $ship_line=array($product_name=>array('id'=>$post['vx_ship_entry']),'Quantity'=>1,'List_Price'=>floatval($post['shipping_charge']));
    if(!empty($post['vx_ship_entry_tax'])){
     $ship_line['Tax']=$post['vx_ship_entry_tax'];   
    }
  if(!isset($post[$field_name])){ $post[$field_name]=array(); }  
 $post[$field_name][]= $ship_line;
}

//var_dump($post[$field_name],$post); die();
//$extra['line items']=$post['Product_Details']; Ordered_Items
if(!empty($post[$field_name])){
$fields['line_items']=array('value'=>$post[$field_name],'label'=>'Line Items'); 
}
 if(!empty($post['vx_ship_entry'])){
    unset($post['vx_ship_entry']);  
 unset($post['shipping_charge']);  
 unset($post['vx_ship_entry_tax']);  
 }  
  }else{
  $module_products=true;    
  }

 }
}
//if($module == 'purchaseorders'){
 //var_dump($post,$meta['order_items']); die('----------');   
//}
//var_dump($post,$extra,$crm_type); die();
$post=array('data'=>array($post));
if(!empty($meta['assign_rule'])){
    $post['lar_id']=$meta['assign_rule'];
}
}

if(!empty($method)){
if(empty($path)){  $path=$module; }
$arr=$this->post_crm( $path, $method,json_encode($post));
//var_dump($arr,$post); die();
}
if(!empty($arr['data'])){
    if(isset($arr['data'][0]['status']) && $arr['data'][0]['status'] == 'success' && isset($arr['data'][0]['details']['id'])){
$id=$arr['data'][0]['details']['id']; 

    }else if(isset($arr['data'][0]['message'])){
$error=$arr['data'][0]['code'].' : '.$arr['data'][0]['message'];   
$status='';       
}

}
else if(isset($arr['message'])){
$error=$arr['code'].' : '.$arr['message'];   
$status='';       
}

if(!empty($id)){
//add to campaign
if(isset($meta['add_to_camp']) && $meta['add_to_camp'] == "1"){
   $extra['Campaign Path']=$camp_path=$module.'/'.$id.'/Campaigns/'.$meta['campaign'];
   $camp_post=array('data'=>array(array('Member_Status'=>'active')));
   $extra['Add Campaign']=$this->post_crm($camp_path,'put',json_encode($camp_post));   
  }
if(!empty($product_img)){
$url='Products/'.$id.'/photo';
$arr=array('attachments_v2'=>array('image.png'=>$product_img));
$extra['Product Img']=$this->post_crm($url,'post',$arr); 
  }  
//add tags  
if(!empty($related['tags'])){ 
    $camp_path=$module.'/'.$id.'/actions/add_tags';
$extra['Add Tags']=$this->post_crm($camp_path,'post',json_encode(array('tags'=>array_map(function($val){return array('name'=>trim($val));},explode(',',$related['tags']) ) ))); 
}
if(!empty($files)){ 
    $extra['Add Files']=$files;
 $camp_path=$module.'/'.$id.'/Attachments';    
foreach($files as $k=>$file){
//  $file=str_replace($upload['baseurl'],$upload['basedir'],$file);
$extra['Add Files '.$k]=$this->post_crm($camp_path,'post',array('attachmentUrl'=>$file)); 

} 
 

}


if($module_products){
foreach($zoho_products as $k=>$v){
$extra['Add Product Path '.$k]=$path=$module.'/'.$id.'/Products/'.$v['id'];
$post=json_encode(array('data'=>array(array('Quantity'=>$v['qty']))) );
$extra['Add Products '.$k]=$this->post_crm($path,'put',$post);   
}
}
if($multi_lookup){
foreach($multi_lookup as $k=>$v){
$field=isset($custom_fields[$k]) ? $custom_fields[$k] : array(); 
if(!empty($field['module_field'])){
$extra['Multilookup Path '.$k]=$path=$field['linking_module'];
$extra['Multilookup post '.$k]=$post=array('data'=>array(array($field['module_field']=>array('id'=>$id),$k=>array('id'=>$v))));
$extra['Multilookup res '.$k]=$this->post_crm($path,'post',json_encode($post) );   
} }
}
 
}

}
if(!empty($id)){
   $domain=!empty($this->info['dc']) ? $this->info['dc'] : 'com'; 
   $type= empty($this->info['type']) ? 'crm' : $this->info['type'];
   // $link='https://crm.zoho.'.$domain.'/crm/EntityInfo.do?module='.$module."&id=".$id;
   $zoho='.zoho.'; if($domain == 'ca'){$zoho='.zohocloud.';} 
    $link='https://'.$type.$zoho.$domain.'/crm/tab/'.str_replace('_','',$module).'/'.$id; 
}
  if($debug){
  ?>
  <h3>Account Information</h3>
  <p><?php //print_r($this->info) ?></p>
  <h3>Data Sent</h3>
  <p><?php print_r($post) ?></p>
  <h3>Fields</h3>
  <p><?php echo json_encode($fields) ?></p>
  <h3>Response</h3>
  <p><?php print_r($response) ?></p>
  <h3>Object</h3>
  <p><?php print_r($module."--------".$action) ?></p>
  <?php
 echo  $contents=trim(ob_get_clean());
  if($contents!=""){
  update_option($this->id."_debug",$contents);   
  }
  }
       //add entry note
 if(!empty($status) && !empty($meta['__vx_entry_note']) && !empty($id)){
 $disable_note=$this->post('disable_entry_note',$meta);
   if(!($entry_exists && !empty($disable_note))){
       $entry_note=$meta['__vx_entry_note'];
       $entry_note['Parent_Id']=$id;
   

$note_response=$this->post_note($entry_note,$module);
  $extra['Note Body']=$entry_note;
  $extra['Note Response']=$note_response;
 
   }  
 }


return array("error"=>$error,"id"=>$id,"link"=>$link,"action"=>$action,"status"=>$status,"data"=>$fields,"response"=>$arr,"extra"=>$extra);
}
public function is_address($field){
 $is_address=false;
 if(!in_array($field,array('shipping_charge'))){
 $is_address= strpos($field,'billing_') !== false || strpos($field,'shipping_') !== false ;
 }
return $is_address;
}
public function clean_field($type,$val){
     if( in_array($type, array('textarea','text','picklist') ) && is_array($val)){
      $val=trim(implode(' ',$val));  
    }else if(in_array($type, array('datetime','date'))){
        // to do , change time offset from+00:00 to real
        $offset=get_option('gmt_offset');
     $offset=$offset*3600; 
     $date_val=strtotime(str_replace(array("/"),"-",$val));
     if( $type == 'datetime' && strpos($val,'+') === false){ // convert to utc if no timezone(+) does not exist with time string
     $date_val-= $offset;   
     }
        // Y-m-d\TH:i:s-08:00  
     if($type == 'date'){
     $val=date('Y-m-d',$date_val);  
    }else{
     $val=date('c',$date_val);   
    }

    }else if( in_array($type,array('multiselectpicklist')) ){
          if(is_string($val)){ $val=array($val); }
      $val=$val;  
    }else if($type == 'boolean'){
      $val=!empty($val) ? true : false;  
    }else if($type == 'currency'){
      $val=floatval($val);  
    }else if($type == 'integer'){
      $val=intval($val);  
    }else if($type == 'text'){
      $val=strval($val);  
    }
    return $val;
}
public function verify_files($files,$old=array()){
        if(!is_array($files)){
        $files_temp=json_decode($files,true);
     if(is_array($files_temp)){
    $files=$files_temp;     
     }else if (!empty($files)){ //&& filter_var($files,FILTER_VALIDATE_URL)
      $files=array_map('trim',explode(',',$files));   
     }else{
      $files=array();    
     }   
    }
    if(is_array($files) && is_array($old) && !empty($old)){
   $files=array_merge($old,$files);     
    }
  return $files;  
}
public function push_object_invoice($module,$fields,$meta){ 
   
  /*  $json='{"notes":"touseefcccddxx ahmadhcccs localhost.com","reference_number":"wc-10795","place_of_supply":"WB","gst_treatment":"business_gst","gst_no":"19AAGFG0836Q1ZW","reason":"Sales Return","customer_id":"552210000000014002","line_items":[{"item_id":"552210000000016011","quantity":2,"rate":200,"tax_id":"552210000000018131"}],"reference_invoice_type":"registered"}';
$post=json_decode($json,1);
$post=array('JSONString'=>json_encode($post));
$res=$this->post_crm('creditnotes','post',$post);
var_dump($res); die();    */
 //$res=$this->post_crm('salesorders/195137000000051007'); var_dump($res); die();
  //  $res=$this->post_crm('customerpayments/118114000000182001');
//  $path='items?sku='.urlencode('WOO-BLA');
 // $path='items/183210000000116013';
//  $path='itemgroups?group_name='.urlencode('Woo Salesforce');
//$res=$this->post_crm($path);
//var_dump($res); die();
/*
$json='{"name":"Contact Form Salesforce Addon122x1122","sku":"vxcf-sales112","rate":"100","description":"xxxxxxxxx","group_name":"Woo Salesforce","group_id":"95902000000117125","attribute_option_name1":"blue","attribute_option_name2":"xl","attribute_name1":"size","attribute_name2":"Color"}';
$arr=$this->post_crm('items','post',array('JSONString'=>$json));
var_dump($arr); die();*/
// $res=$this->post_crm('invoices/417555000000041271');
 //  $res=$this->post_crm('items/460224000000042205');
// var_dump($res); die();
 
// $post=array('JSONString'=>'{"contact_persons":[{"first_name":"johnxx","last_name":"lewisxx","email":"bioinfo38@gmail.com","phone":"8104763057"}]}');
//$post=array('JSONString'=>'{"invoices":[{"invoice_id":"109158000000032286"}],"order_status":"closed","status":"closed","invoiced_status":"invoiced","date":"2020-01-01"}');
//$post=array('JSONString'=>'{"salesorder_id":"109158000000032204","salesorder_number":"SO-00001","date":"2020-01-01"}');
//$res=$this->post_crm('contacts/1638246000000106001','put',$post);
//$res=$this->post_crm('invoices/1639733000000076134/email','post');
//$res=$this->post_crm('salesorders/109158000000032204/status/open','post');
//$res=$this->post_crm('salesorders/109158000000032204','put',$post);
//$res=$this->post_crm('invoices/fromsalesorder?salesorder_id=95902000000065001','post',$post);
//var_dump($res); die();
//check primary key
 $extra=array(); 
  $event= isset($meta['event']) ? $meta['event'] : '';
  $custom_fields= isset($meta['fields']) ? $meta['fields'] : array();
  $id= isset($meta['crm_id']) ? $meta['crm_id'] : '';
  $total=0;
  if($module == 'customerpayments'){
   $module_single='payment';   
  }else if($module == 'recurringinvoices'){
   $module_single='recurring_invoice';   
  }else{
$module_single=rtrim($module,'s');
  }
 $group_title='';
 $type=$this->info['type'];
 if( $module == 'items' && !empty(self::$order['_product_id']) && in_array($type,array('inventory'))){ 
    $product=wc_get_product(self::$order['_product_id']); 
    if(is_object($product) && method_exists($product,'get_parent_id')){
     $product_id=$product->get_id();   
     $parent_id=$product->get_parent_id();   
    $sku=$product->get_sku(); 
       if(!empty($parent_id)){ //variable product
         $product_simple=wc_get_product($parent_id);
         $parent_sku=$product_simple->get_sku();
         //find group id
         $group_id='';
    if(empty(self::$wp_product_update[$parent_id])){ //if already NOT found     
         $group_title=$product_simple->get_title();
$path='itemgroups?group_name='.urlencode($group_title);
$extra['search group']=$res=$this->post_crm($path); 
if(!empty($res['itemgroups'][0])){
$group_id=self::$wp_product_update[$parent_id]=$res['itemgroups'][0]['group_id'];     
} 
}else{ //already fround
$group_id=self::$wp_product_update[$parent_id];        
}
if(!empty($group_id)){ 
$fields['group_id']=array('value'=>$group_id ,'label'=>'Group ID');
$group_title='';  //group id found , so no need to create group
}
          
          //fix sku for variation
         if($parent_sku == $sku){
             $sku.='-'.$product_id;
             if(!empty($fields['sku']['value'])){
       $fields['sku']['value']=$sku;    
       }
         }
         //fix title , append attrs
       $title=$this->get_product_attrs($product);  
       if(!empty($fields['name']['value'])){
       $fields['name']['value']=strval($fields['name']['value']).' - '.$title;    
       }  
       
   }else{
    $type=$product->get_type();   
    if(in_array($type,array('variable'))){ //ignore variable and allow othrs as simple zoho items
     return false;   
    }
   }    
    }
 } 
 //var_dump($module,$fields,$meta,self::$order); die();
 // !isset($wp_product_update[$post_id]) &&

if(isset($meta['primary_key']) && $meta['primary_key']!="" && isset($fields[$meta['primary_key']]['value']) && $fields[$meta['primary_key']]['value']!=""){    
$search=$fields[$meta['primary_key']]['value'];
$field=$meta['primary_key'];
$field_type= isset($custom_fields[$field]['type']) ? $custom_fields[$field]['type'] : '';
if(!in_array($field,array('email','phone','contact_name','company_name','first_name','last_name'))){
if($this->is_address($field)){
  $field='address';   
}else{
    $field='search_text'; 
} 
}

$path=$module.'?'.$field.'='.urlencode($search);
//$path=$module.'?status=all';
if($module == 'contacts'){
    $path.='&contact_type=customer&status=active';
}
$search_response=$this->post_crm($path);
//var_dump($search_response,$path); die();
$extra["body"]=$path;
$extra["search"]=$search;
$extra["response"]=$search_response;
$new_res=array();
      if(is_array($search_response) && !empty($search_response[$module]) ){
          $search_response=$search_response[$module];
      if( count($search_response)>1){
          $search_response=array_reverse($search_response); //get last item from result (last order of a woo subscription)
          foreach($search_response as $k=>$v){
              if(isset($v[$meta['primary_key']]) && $v[$meta['primary_key']] == $search){
            $new_res=$v;    if(isset($v['total'])){ $total=$v['total']; }  
              }
          }
          if( count($search_response)>5){
       $search_response=array_slice($search_response,0,5);
          }   
      }

      $extra["response"]=$search_response;
      if(isset($new_res[$module_single.'_id'])){
      $id=$new_res[$module_single.'_id'];  //count($search_response)-1
      }else if(isset($search_response[0][$module_single.'_id'])){
      $id=$search_response[0][$module_single.'_id'];  
      if(isset($search_response[0]['total'])){ $total=$search_response[0]['total']; } 
      }
  }
}
//var_dump($search_response,$module_single); die();

$acc_id=!empty($this->info['id']) ? $this->info['id'] : '0'; 
$post=array(); $status=$action=$method=$contact_person_id=$invoice_id=''; $send_body=true;
 $entry_exists=false;
 $link=""; $error=""; 
 $path=''; $q=array(); $disable_items=false;
 $arr=array();
if($id == ""){
if(empty($meta['new_entry'])){
$method='post';
}else{
    $error='Entry does not exist';
}
$action="Added";  $status="1";
$path=$module;
if(!empty($meta['order_check']) && !empty($meta['object_order']) &&  !empty(self::$feeds_res[$meta['object_order']]['id']) && $module == 'invoices'){
  $path.='/fromsalesorder';  
  $q['salesorder_id']= self::$feeds_res[$meta['object_order']]['id'];
  $disable_items=true;
}
}
else{
 $entry_exists=true;
if(in_array($event,array('delete','delete_note'))){
 $send_body=false;
     if($event == 'delete_note'){ 
   $module='Notes';
     }
     $method="delete";
     $action="Deleted";
  $status="5";  
  $path=$module.'/'.$id;
}
else{
    //update object
$status="2"; $action="Updated";
if(empty($meta['update'])){
$method='put';
$path=$module.'/'.$id;
 if($module == 'creditnotes' && (isset($fields['amount']) || isset($fields['from_account_id']) || isset($fields['refund_mode']) || isset($fields['description']) ) ){
$path.='/refunds';   $method='post';
if(!isset($fields['amount'])){ $fields['amount']=array('value'=>$total,'label'=>'Refund Amount'); }
}

 if($module == 'contacts'){
 $person=$this->post_crm('contacts/'.$id.'/contactpersons');
  if(!empty($person['contact_persons'][0]['contact_person_id'])){
  $contact_person_id=$person['contact_persons'][0]['contact_person_id'];    
  }
 }
}
}
}

if(!empty($method)){
$zoho_products=$related=$custom=$email=array();
$contact_fields=array("salutation","first_name","last_name","email","phone","mobile","skype","designation","department","enable_portal");
$skip_fields=array('refund_amount');
if($send_body){
foreach($fields as $k=>$v){
    $field=isset($custom_fields[$k]) ? $custom_fields[$k] : array(); 

    if(empty($field['type']) || in_array($k,$skip_fields)){ continue; }

    $type=$field['type']; 
       if($type == 'check_box' || in_array($k,array('is_portal_enabled'))){
        // to do , change time offset from+00:00 to real
     $v['value']=!empty($v['value']) ? true : false; 
    }else if($type == 'date'){
     $v['value']=date('Y-m-d',strtotime(str_replace('/', '-',$v['value'])));   
    }else if($type == 'bool'){
     $v['value']=(bool)$v['value'];   
    }
   if(!empty($field['is_custom'])){ 
      
  $cust_field=array('value'=>$v['value']);
  if($this->info['type'] == 'books'){
    $cust_field['index']=$field['name'];  
  }else{
   $cust_field['label']=$field['label'];     
  }
    $post['custom_fields'][]=$cust_field;
   }else if($this->is_address($k)){ 
       if(strpos($k,'shipping_') !== false){
       $id_key=substr($k,0,8); 
       $k=substr($k,9);
       }else{
        $id_key=substr($k,0,7); 
       $k=substr($k,8);     
       } 
       $post[$id_key.'_address'][$k]=$v['value']; 
    }else if(in_array($k,$contact_fields)){
      $related['contacts'][$k]=$v['value'];  

    }else if($k == 'invoice_id'){ 
        $inv=array('invoice_id'=>$v['value']);
        if(!empty($fields['amount']['value'])){
        $inv['amount_applied']=$fields['amount']['value'];  
        }
      $post['invoices']=array($inv);  
    }else if($k == 'tax_id_new'){
        if(!empty($id)){
       $con=$this->post_crm($path); 
       if(empty($con['contact']['tax_id'])){
       $post['tax_id']=$v['value'];    
       }    
        }   
}else if($k == 'payment_options'){
       $post['payment_options']=array('payment_gateways'=>array(array('gateway_name'=>$v['value'],'configured'=>true)));    
}else if($k == 'send_paid_invoice_to'){
    $email['to_mail_ids']=array($v['value']);
}else if($k == 'discount'){
    $post['discount']=abs(floatval($v['value']));
}else if($k == 'currency_code'){
if(!empty($meta['fields']['currency_id']['options'])){
    foreach($meta['fields']['currency_id']['options'] as $op){ 
    $v['value']=strtolower($v['value']);
    $op['label']=strtolower($op['label']);
        if( strpos($op['label'],$v['value']) === 0 ){
      $post['currency_id']=$op['value'];        
        }
    }
}
}else{
     $post[$k]=$v['value'];    
    }
}

if(!empty($related['contacts']) ){
    $person=$related['contacts'];
    if(!empty($contact_person_id)){
      $person['contact_person_id']=$contact_person_id;
    }
    if(!empty($post['is_portal_enabled'])){
        $person['enable_portal']=true;
    }
    $post['contact_persons']=array($person);
}
$customer_id='';
    if(!empty($meta['contact_check']) && !empty($meta['object_contact']) &&  !empty(self::$feeds_res[$meta['object_contact']]['id']) ){
     $customer_key='customer_id'; 
    if($module == 'purchaseorders'){
      $customer_key='delivery_customer_id';   
    }    
   $post[$customer_key]=$customer_id=self::$feeds_res[$meta['object_contact']]['id'];   
   $fields[$customer_key]=array('label'=>'Customer ID','value'=>$post[$customer_key]);
 }
$addresses=array('billing','shipping');
if($module == 'contacts' && $status == '2' && !empty($path)){
   // $address_res=$this->post_crm('contacts/'.$id.'/address'); 
   $contact_res=$this->post_crm('contacts/'.$id); 
   $contact= !empty($contact_res['contact']) ? $contact_res['contact'] : array();
  $addrs=array(); 
  
      if(!empty($contact['billing_address'])){
        $addr=$contact['billing_address'];
        $addr['type']='shipping';
        $addrs[$addr['address_id']]=$addr;
    }
     if(!empty($contact['shipping_address'])){
        $addr=$contact['shipping_address'];
        $addr['type']='billing';
        $addrs[$addr['address_id']]=$addr;
    }
    if(!empty($contact['addresses'])){
        foreach($contact['addresses'] as $v){
            $v['type']='common';
     $addrs[$v['address_id']]=$v;   
        }
    }
    

        self::$address[$id]=array(); 
        foreach($addresses as $addr_id){
        if(!empty($post[$addr_id.'_address'])){ 

            foreach($addrs as $v){
                if(!empty($post[$addr_id.'_address']['address']) && $post[$addr_id.'_address']['address'] == $v['address'] && !empty($post[$addr_id.'_address']['city']) && $post[$addr_id.'_address']['city'] == $v['city'] && in_array($v['type'],array($addr_id,'common'))){
                self::$address[$id][$addr_id.'_address']=$v['address_id'];  
                unset($post[$addr_id.'_address']); //address matched , so remove it from post  
                }
            }
      

if(empty(self::$address[$id][$addr_id.'_address'])){ // no address matched , add as new address
    $addr=$post[$addr_id.'_address']; //$addr['update_existing_transactions_address']=true;
      $extra[$addr_id.' address']=$addr_res=$this->post_crm('contacts/'.$id.'/address','post',array('JSONString'=>json_encode($addr)));  
      unset($post[$addr_id.'_address']);
      if(!empty($addr_res['address_info'])){
      self::$address[$id][$addr_id.'_address']=$addr_res['address_info']['address_id'];    
      }else{
    $method='';  $arr=array('code'=>'invalid_address','message'=>'Customer address is not Valid');
      }
}
            
        } 
        }
} 
if( in_array($module,array('salesorders','invoices')) && !$disable_items && !empty($customer_id)){
  foreach($addresses as $addr_id){
   if(!empty(self::$address[$customer_id][$addr_id.'_address'])){
       $post[$addr_id.'_address_id']=self::$address[$customer_id][$addr_id.'_address'];
       $fields[$addr_id.'_address']=array('value'=>self::$address[$customer_id][$addr_id.'_address'],$addr_id.' address');
   }   
  }  
} 
if($module == 'items' && !empty($fields['group_id'])){
    $post['group_id']=$fields['group_id']['value'];
}
 //change owner id
  if(isset($meta['owner']) && $meta['owner'] == "1"){
   $post['owner_id']=$meta['user'];   
   $fields['owner_id']=array('label'=>'Owner','value'=>$meta['user']);
  } 

   
if( (!empty($meta['email_check']) || ($module == 'invoices' && empty($meta['confirm_check'])) ) && !empty($post['customer_id']) ){ 
    //always update contacts for invoices , it is required for "send paid invoice to" featrue in payment feed
  $persons=array();
  $person=$this->post_crm('contacts/'.$post['customer_id'].'/contactpersons');
  if(!empty($person['contact_persons'])){
  foreach($person['contact_persons'] as $p){
      $persons[]=$p['contact_person_id'];
  }  
 $post['contact_persons']=$persons;
 if(!empty($meta['email_subject'])){
     $post['custom_subject']=$meta['email_subject'];
 }
  if(!empty($meta['email_body'])){
     $post['custom_body']=$meta['email_body'];
 }
   $fields['contact_id']=array('label'=>'Contact Person','value'=>$persons);
  }
}

if(!empty($meta['invoice_check']) && !empty($meta['object_invoice']) &&  !empty(self::$feeds_res[$meta['object_invoice']]['id']) ){
    $inv=array('invoice_id'=>self::$feeds_res[$meta['object_invoice']]['id']);
    $invoice_id=self::$feeds_res[$meta['object_invoice']]['id'];
        if(!empty($fields['amount']['value'])){
        $inv['amount_applied']=$fields['amount']['value'];  
        }else if(isset(self::$feeds_res[$meta['object_invoice']]['response']['invoice']['total'])){
        $inv['amount_applied']=self::$feeds_res[$meta['object_invoice']]['response']['invoice']['total']; 
        $fields['amount']=array('label'=>'Amount','value'=>$inv['amount_applied']); 
        $post['amount']=$inv['amount_applied'];    
        }
      $post['invoices']=array($inv);   
   $fields['invoice_id']=array('label'=>'Invoice ID','value'=>self::$feeds_res[$meta['object_invoice']]['id']);
} 

if(!empty($meta['order_items']) && !$disable_items){
   $order_res=$this->get_zoho_products_invoice($meta); 
   $zoho_products=$order_res['res']; 
   if(isset($order_res['count']) && !empty($zoho_products) && $order_res['count'] > count($zoho_products)){ //if some item failed , do not process order
    $method=''; $arr=array('code'=>'lines_missmatch','message'=>'Some Zoho line items failed');   
   } 
  
  if(is_array($order_res['extra'])){
  $extra=array_merge($extra, $order_res['extra']);
  }
//var_dump($zoho_products); die();

 if(is_array($zoho_products) && count($zoho_products)>0){

 foreach($zoho_products as $v){
 $line_item=array('item_id'=>$v['id'],'quantity'=>$v['qty'],'rate'=>$v['cost']);
// $line_item['rate']=1.98;
  if(!empty($meta['warehouse'])){
    $line_item['warehouse_id']=$meta['warehouse'];   
  }
  if(!empty($v['description'])){
    $line_item['description']=$v['description'];   
  }
  if( empty($meta['items_name'])){
    $line_item['name']=wp_strip_all_tags($v['name']);   
  }
  if(!empty($meta['item_price'])){
      if($meta['item_price'] == 'dis'){
  $line_item['rate']=$v['cost_woo'];  
   if( !empty($v['cost_woo']) && $v['cost_woo'] > $v['cost']){ 
         $line_item['discount']=floatval($v['cost_woo']-$v['cost'])*$v['qty'];
     }
      }else if($meta['item_price'] == 'cost'){
       $line_item['rate']=$v['cost_woo'];   
      }else if($meta['item_price'] == 'cost_tax'){
       $line_item['rate']=floatval($v['cost'])+floatval($v['tax']); 

      }
  }
  $line_item['rate']=round(floatval($line_item['rate']),2);
 if(!empty($v['tax_id'])){
     $line_item['tax_id']=$v['tax_id'];
 } 
 if(!empty($post['pricebook_id'])){
     $line_item['pricebook_id']=$post['pricebook_id'];
 }
if($module == 'purchaseorders' && !empty($v['purchase_rate'])){
  $line_item['rate']=$v['purchase_rate'];  
}     
$post['line_items'][]= $line_item;  
}
//var_dump($post['line_items'],$zoho_products); die();   
//$extra['line items']=$post['line_items'];
if(!empty($post['vx_ship_entry'])){
    $ship_line=array('item_id'=>$post['vx_ship_entry'],'quantity'=>1,'rate'=>floatval($post['shipping_charge'])); 
    if(!empty($post['vx_ship_entry_tax'])){
     $ship_line['tax_id']=$post['vx_ship_entry_tax'];   
    }
 $post['line_items'][]=$ship_line;
  
}
$fields['line_items']=$post['line_items'];  
 }
 if(!empty($post['vx_ship_entry'])){
    unset($post['vx_ship_entry']);  
 unset($post['shipping_charge']);  
 unset($post['vx_ship_entry_tax']);  
 }else if(!empty($post['shipping_charge']) && !empty($post['vx_ship_entry_tax'])){
   $post['shipping_charge_tax_id']=$post['vx_ship_entry_tax'];   
    unset($post['vx_ship_entry_tax']);
 }
}

if($module != 'contacts' && isset($post['pricebook_id']) ){
    unset($post['pricebook_id']);
} 
if($module == 'salesorders' ){ 
  //  var_dump($post); die();
}
if($module == 'invoices' ){ //customerpayments
// var_dump($post,self::$address); die();
if(!empty($post['discount']) && empty($post['discount_type'])){
$post['discount_type']='entity_level';    
}
if(isset($post['adjustment'])){
$post['adjustment']=(float)$post['adjustment'];
}
if(isset($post['tax_total'])){
$adj= isset($post['adjustment']) ? $post['adjustment'] : 0; 
$tax=(float)$post['tax_total'];
$post['adjustment']=$tax+$adj;
}

//$post['discount_amount']=12;
//$post['discount_applied_on_amount']=48;
//$post['is_discount_before_tax']=true;
}
if(!empty($post['discount']) && !isset($post['discount_type'])){
       $post['discount_type']='entity_level'; //discount on zoho books needs this 
}
if($module == 'creditnotes'){
  //  $post['reference_invoice_type']='registered';
 // unset($post['date']); 
 // $post['refund_mode']='cash';
 // $post['from_account_id']='165004000000037131';
}
if($module == 'purchaseorders'){
  // var_dump($post); die();
// var_dump($post,$meta['order_items'],$extra); die('----------');   
}
if(isset($post['is_inclusive_tax'])){
  $post['is_inclusive_tax']= !empty($post['is_inclusive_tax']) ? true : false;  
}
//$post['currency_id']='113194000000000059';
//$post['currency_code']='USD';
//$post['currency_symbol']='$';
//$post['is_taxable']=true;
//unset($post['tax_id']);
if( $module == 'customerpayments'){
  //  $post['account_type']='cash';
 //   $post['payment_mode']='cash';
 //   $post['account_name']='Petty Cash';
 //   $post['account_id']='118114000000000346';
//var_dump($post); die();
}
//$post['shipping_address']=array(array('address'=>'abc road lahore','city'=>'lahore','state'=>'New York','country'=>'Pakistan'));
if(!empty($group_title) && $status == '1'){
    $post=array('group_name'=>$group_title,'unit'=>'qty','items'=>array($post));
    $path='itemgroups';
}

//$post['is_portal_enabled']=true;
//var_dump($post,$fields); die();

$post=array('JSONString'=>json_encode($post));
}


if(!empty($method)){

if(!empty($meta['email_check'])){ $q['send']='true'; }
if(in_array($module,array('salesorders','invoices')) && isset($fields['salesorder_number']) ){  $q['ignore_auto_number_generation']='true';  }
if(!empty($q)){ $path.='?'.http_build_query($q); }

$arr=$this->post_crm( $path, $method,$post);
if(in_array($module,array('salesorders','invoices'))){
 //var_dump($arr,$path,$q,in_array($module,array('salesorders','invoices')),$fields['salesorder_number']); die();   
//var_dump($arr,$post,$path,$extra); die();
}
 
if(!empty($group_title) && !empty($arr['item_group']['group_id'])){
    $id=$arr['item_group']['group_id'];
}
}
//var_dump($arr,$id); die();

if(!empty($arr[$module_single][$module_single.'_id'])){
    if(isset($arr[$module_single][$module_single.'_id'])){
$id=$arr[$module_single][$module_single.'_id']; 
}

if(!empty($email) && !empty($id) && !empty($invoice_id)){
    $path='invoices/'.$invoice_id.'/email';
    $extra['Sending Email']=$this->post_crm( $path,'post',json_encode($email));
}
if(!empty($meta['confirm_check']) && !isset($q['send']) && !empty($id)){ 
    $status_s=$module == 'invoices' ? 'sent' : 'confirmed';
    $path=$module.'/'.$id.'/status/'.$status_s;
    $extra['Confirm']=$this->post_crm( $path, 'post');
}
if($disable_items){
    $post=array();
     foreach($addresses as $addr_id){
   if(!empty(self::$address[$acc_id][$addr_id.'_address'])){
       $post[$addr_id.'_address_id']=self::$address[$acc_id][$addr_id.'_address'];
   }   
  }
  if(!empty($post)){
$post=array('JSONString'=>json_encode($post));
 $extra['Updating address']=$this->post_crm( 'invoices/'.$id, 'put',$post);
  }   
}
}
 if(empty($id) && isset($arr['message'])){
$error=$arr['message'].' - '.$arr['code'];   
$status='';       
}


}
if(!empty($id)){
   $domain=!empty($this->info['dc']) ? $this->info['dc'] : 'com'; 
   // $link='https://crm.zoho.'.$domain.'/crm/EntityInfo.do?module='.$module."&id=".$id; 
   $type=$this->info['type'] == 'invoices' ? 'invoice' : $this->info['type'];
   $module_url=str_replace('_','',$module);
   if($module == 'customerpayments'){
     $module_url='paymentsreceived';  
   }
    if($module == 'estimates'){
     $module_url='quotes';  
   }
    if( $type == 'inventory' && $module == 'items'){
     $module_url='inventory/items';  
   }
    $link='https://'.$type.'.zoho.'.$domain.'/app#/'.$module_url.'/'.$id; 
}

return array("error"=>$error,"id"=>$id,"link"=>$link,"action"=>$action,"status"=>$status,"data"=>$fields,"response"=>$arr,"extra"=>$extra);
}

   public function get_product_attrs($product){
     // append variation names ,  $item->get_name() does not support more than 3 variation names
          $attrs=$product->get_attributes(); //$item->get_formatted_meta_data( '' )
            $var_info=array(); $title='';
             if(is_array($attrs) && count($attrs)>0){
                 foreach($attrs as $attr_key=>$attr_val){
                    // $att_name=wc_attribute_label($attr_key,$product);
                     $term = get_term_by( 'slug', $attr_val, $attr_key );
                 if ( taxonomy_exists( $attr_key ) ) {
                $term = get_term_by( 'slug', $attr_val, $attr_key );
                if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
                    $attr_val = $term->name;
                }    
            }
            if(!empty($attr_val)){
            $var_info[]=$attr_val;
            }    
                 }
             }
          if(!empty($var_info)){
          $title=implode(', ',$var_info);    
          }
  return $title;        
}
public function post_note($post,$module){
  $re=array('Title'=>'Note_Title','Body'=>'Note_Content');
    foreach($post as $k=>$v){
  if(isset($re[$k])){
   $post[$re[$k]]=$v;
   unset($post[$k]);   
  }
  }
  $post['Parent_Id']=array('module'=>array('api_name'=>$module),'id'=>$post['Parent_Id']);
     //$post['se_module']=$module; 
return $this->post_crm('Notes','POST', json_encode(array('data'=>array($post))) );  
}
public function get_wc_items($meta){
      $_order=self::$_order;
    //  $fees=$_order->get_shipping_total();
    //  $fees=$_order-> get_total_discount();
    //  $fees=$_order-> get_total_tax();

      
     $products=array();  $order_items=$items=array(); 
     
      if(is_object($_order) && method_exists($_order,'get_items')){
   $items=$_order->get_items(); 
 }
 
if(is_array($items) && count($items)>0 ){
foreach($items as $item_id=>$item){

$sku=$img_id=$cat=''; $qty=$unit_price=$tax=$total=$cost=$cost_woo=$stock=0;
if(method_exists($item,'get_product')){
  // $p_id=$v->get_product_id();  
   $product=$item->get_product();
   if(!$product){ continue; } //product deleted but exists in line items of old order
   $total=floatval($item->get_total());
   $total=round($total,2);
   $qty = $item->get_quantity();  
   $tax = $item->get_total_tax();
   if(!empty($tax) && !empty($qty)){
       $tax=floatval($tax)/$qty;
   }
   $title=$product->get_title();
  // $title=$item->get_name();
   $sku=$product->get_sku();     
   $unit_price=floatval($product->get_price());  
   $unit_price=round($unit_price,2);  
    $parent_id=$product->get_parent_id();
    $product_id=$product->get_id(); 
    if(method_exists($_order,'get_item_total')){
       $cost=(float)$_order->get_item_total($item,false,true); //including woo coupon discuont
       $cost_woo=(float)$_order->get_item_subtotal($item, false, true); // does not include coupon discounts
   
     if(!empty($meta['item_price_custom'])){
      $cost=(float)wc_get_order_item_meta($item->get_id(),$meta['item_price_custom'],true); 
     }   
       $cost=round($cost,2);
       $cost_woo=round($cost_woo,2);
    }
    if(method_exists($product,'get_stock_quantity')){
   $stock=$product->get_stock_quantity();
  $img_id=$product->get_image_id(); //
  $terms = get_the_terms( $product->get_id() , 'product_cat' );
  if(!empty($terms[0]->name)){
   $cat=$terms[0]->name;   
  }
}
    
    if(empty($sku)){
        $sku='wc-'.$product_id;
    }
   if(!empty($parent_id)){
         $product_simple=new WC_Product($parent_id);
         $parent_sku=$product_simple->get_sku(); 
         if($parent_sku == $sku){
             $sku.='-'.$product_id;
         }
     // append variation names ,  $item->get_name() does not support more than 3 variation names
          $attrs=$product->get_attributes(); //$item->get_formatted_meta_data( '' )
            $var_info=array(); //var_dump($attrs,$product_id); die();
             if(is_array($attrs) && count($attrs)>0){
                 foreach($attrs as $attr_key=>$attr_val){   //var_dump($attr_val);
                 if(!is_object($attr_val)){
                    // $att_name=wc_attribute_label($attr_key,$product);
                     $term = get_term_by( 'slug', $attr_val, $attr_key );
                 if ( taxonomy_exists( $attr_key ) ) {
                $term = get_term_by( 'slug', $attr_val, $attr_key );
                if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
                    $attr_val = $term->name;
                }    
            } 
            if(!empty($attr_val)){
            $var_info[]=$attr_val;
            }    
                 } }
             }
          if(!empty($var_info)){
          $title.=' '.implode(', ',$var_info);    
          }    
   }
   if(empty($total)){ $unit_price=0; } 
 }
 else{ //version_compare( WC_VERSION, '3.0.0', '<' )  , is_array($item) both work
          $line_item=$this->wc_get_data_from_item($item); 
   $p_id= !empty($line_item['variation_id']) ? $line_item['variation_id'] : $line_item['product_id'];
        $line_desc=array();
        if(!isset($products[$p_id])){
        $product=new WC_Product($p_id);
        }else{
         $product=$products[$p_id];   
        }
        $qty=$line_item['qty'];
        $products[$p_id]=$product;
        $sku=$product->get_sku(); 
        if(empty($sku) && !empty($line_item['product_id'])){ 
            //if variable product is empty , get simple product sku
            $product_simple=new WC_Product($line_item['product_id']);
            $sku=$product_simple->get_sku(); 
        }
        $unit_price=$product->get_price();  
       // $title=$product->get_title();
       $title=$item['name'];
          }
  $temp=array('sku'=>$sku,'unit_price'=>$unit_price,'title'=>wp_strip_all_tags($title),'qty'=>$qty,'tax'=>$tax,'total'=>$total,'cost'=>$cost,'cost_woo'=>$cost_woo,'qty_stock'=>$stock,'img_id'=>$img_id,'cat'=>$cat,'tax_id'=>'','fields'=>array());
          if(method_exists($product,'get_stock_quantity')){
   $temp['stock']=$product->get_stock_quantity();
   if(!empty($meta['tax_id'])){
if($meta['tax_id'] == 'map'){    
$item_tax=$item->get_taxes(); //var_dump($item_tax); die();
if(!empty($item_tax['total']) && !empty($meta['tax_map'])){
$tax_ids=$item_tax['total'];
$tax_class=$item->get_tax_class();
if(empty($tax_class)){ $tax_class='standard'; }
    $tax_ids+=array($tax_class=>'tax Class'); 
    foreach($tax_ids as $tax_id=>$tax_val){
        $tax_rate=array_search($tax_id,$meta['tax_map']);
        if($tax_rate){ 
         $temp['tax_id']=$tax_rate;   
            break;
        }
    }   
}
//var_dump($item_tax['total'],$meta['tax_map'],$temp); die();
}else{
 $temp['tax_id']=$meta['tax_id'];   
}
}

} 
     if(!empty($meta['item_fields'])){
        foreach($meta['item_fields'] as $k=>$v){
        if(isset($v['type'])){
            if($v['type'] == 'value'){
                $temp['fields'][$k]=$this->process_tags($v['value'],$item);
        }else{
         $temp['fields'][$k]=$this->get_field_val($v,$item);   
        }    }
        }   
       }
if(!empty($meta['item_desc'])){
    $temp['item_desc']=$this->process_tags($meta['item_desc'],$item);
}
     $order_items[]=$temp;     
      }
     } 
 // var_dump($order_items); die();   
   return $order_items;       
}
public function get_zoho_products($meta){ 

     $sales_response=array();  $extra=array();
     $items=$this->get_wc_items($meta); $items_count=0; 
     if(is_array($items) && count($items)>0 ){
         $n=0;  $items_count=count($items);
      foreach($items as $item){
          $n++; //var_dump($item); continue;
          extract($item);
    
 $product_detail=array('price'=>$unit_price,'qty'=>$qty,'tax_id'=>$tax_id,'total'=>$total,'cost'=>$cost,'cost_woo'=>$cost_woo,'fields'=>$item['fields']);
 
 $url='Products/search?criteria='.urlencode('((Product_Code:equals:'.$sku.'))');
 $search_response=$this->post_crm($url); 
 
 $product_id='';
 $extra['Search SKU - '.$n]=$sku; 
if(!empty($search_response['data'][0]['id'])){
  $product_id=$search_response['data'][0]['id'];  
  $extra['Search Product - '.$n]=$search_response['data'][0];
}else{
  $extra['Search Product - '.$n]=$search_response;  
}

if(empty($product_id)){ //create new product  
$path='Products';
$fields=array('Product_Name'=>$title,'Product_Code'=>$sku,'Unit_Price'=>$unit_price);  
if(!empty($qty_stock)){
   $fields['Qty_in_Stock']=$qty_stock;
} 
if(!empty($cat)){
 $fields['Product_Category']=$cat;   
}
$post=json_encode(array('data'=>array($fields)));
$arr=$this->post_crm('Products','post',$post); 


$extra['Product Post - '.$n]=$fields;
$extra['Create Product - '.$n]=$arr;

if(isset($arr['data'][0]['details']['id'])){
$product_id=$arr['data'][0]['details']['id'];


if(!empty($img_id)){
$p_url=wp_get_attachment_url( $img_id );
$url='Products/'.$product_id.'/photo';
$arr=array('attachments_v2'=>array('image.png'=>$p_url));
$extra['Product Img - '.$n]=$this->post_crm($url,'post',$arr);
}
}
if(!empty($meta['price_book']) && !empty($product_id)){ // add to price book
$price_book=$meta['price_book'];
$path='Products/'.$product_id.'/Price_Books/'.$meta['price_book']; 
$post=array('list_price'=>(float)$unit_price); 
$post=json_encode(array('data'=>array($post)));
$arr=$this->post_crm($path,'put',$post); 

$extra['Add PriceBook - '.$n]=$post.'----'.$path;
$extra['PriceBook Redult - '.$n]=$arr;  
}

//var_dump($post,$product_id,$book_post); die('--------------');
}
if(!empty($product_id)){ //create order here
$product_detail['id']=$product_id;
$sales_response[]=$product_detail;
}
 
      }
     }
   //  die('----');
     return array('res'=>$sales_response,'extra'=>$extra,'count'=>$items_count);
}  
      
public function get_zoho_products_invoice($meta){ 

     $sales_response=array();  $extra=array();
     $items=$this->get_wc_items($meta);  $items_count=0; 
if(is_array($items) && count($items)>0 ){
    $items_count=count($items);
foreach($items as $item){
extract($item);
$item['title']=substr($item['title'],0,100);
///var_dump($sku,$p_id); die('------die-------');
$product_detail=array('price'=>$unit_price,'qty'=>$qty,'cost'=>$cost,'cost_woo'=>$cost_woo,'purchase_rate'=>'','tax'=>$tax,'name'=>$item['title']);
if(!empty($tax_id)){
    $product_detail['tax_id']=$tax_id;
}if(!empty($item['item_desc'])){
    $product_detail['description']=substr($item['item_desc'],0,1900);
}
//$this->info['type'] == 'books' &&
if( empty($meta['search_items_sku'])){ //books support sku search but sku is not enabled by default in books
 $url='items?name='.urlencode($item['title']);
}else{
 $url='items?sku='.urlencode($sku);
}
 $search_response=$this->post_crm($url); 

//var_dump($search_response,$url); die();
 $product_id='';
if(!empty($search_response['items'][0]['item_id'])){
  $product_id=$search_response['items'][0]['item_id'];  
  if(!empty($search_response['items'][0]['purchase_rate'])){
   $product_detail['purchase_rate']=$search_response['items'][0]['purchase_rate']; 
  }
  $extra['Search Product - '.$sku]=$search_response['items'][0];
}else{
  $extra['Search Product - '.$sku]=$search_response;  
}

if(empty($product_id)){ //create new product
$path='Products';
$fields=array('name'=>$item['title'],'sku'=>$sku,'rate'=>$unit_price,'product_type'=>'goods');  
if(!empty($meta['product_type'])){
   $fields['product_type']=$meta['product_type']; 
}
$post=array('JSONString'=>json_encode($fields));
$arr=$this->post_crm('items','post',$post); 

//var_dump($arr,$fields); die();
$extra['Product Post - '.$sku]=$fields;
$extra['Create Product - '.$sku]=$arr;

if(isset($arr['item']['item_id'])){
$product_id=$arr['item']['item_id']; 
}

//var_dump($post,$product_id,$book_post); die('--------------');
}
if(!empty($product_id)){ //create order here
$product_detail['id']=$product_id;
$sales_response[]=$product_detail;
}

     }
 }
  return array('res'=>$sales_response,'extra'=>$extra,'count'=>$items_count);
  }

public function client_info(){
      $info=$this->info;
  $client_id='1000.VFO2QGIQUKMK66057CVLZ8OM1RU9JT';
  $client_secret='feddae1bd7831d4b69e2e4d26ad2057dc8d2d1685a';
  $call_back="https://www.crmperks.com/google_auth/";
  $dc= !empty($info['dc']) ? $info['dc'] : '';
  if($dc == 'com.cn' ){
  $client_id='1000.A84IJNXYRY2U85669SF4LF76AXW9TP';
  $client_secret='817d63c5dfffa01fcc16841f9ad4f6354c017dc1e3';
  }
  if($dc == 'com.au' ){
  $client_id='1000.60USE7OKHPQO9I1QFAUF71YRRB8CIN';
  $client_secret='c009db7e715a587ca585b9beb0ceca90d4d3bc0423';
  }    
  $secret=array('eu'=>'a4e8d2c2284766a748674911a1f5ecbb0a1d7da460','in'=>'d944e3292b8377374725017d934e301f4d2f126f98','jp'=>'2487c9ea4d42924e1de1c9e0a8a1b45b00668386d3','uk'=>'8f40cbce02cb8531d2bc09e9fe357c0adff7b3e991','ca'=>'88d98d11617c3fd56ea6c0dcc362d9db470cd5bc5d');
  

  if($this->id == 'vxc_zoho'){
  
  $client_id='1000.JIR7NH735QWJ15857WRBLPYZQ96LZJ';
  $client_secret='ee5194c9cb5876a2133a03657ef01f7490529bfff4';  
   if($dc == 'com.cn' ){
  $client_id='1000.NLQL8QA4ZBPG48016W4FAJ1DDBZ5PP';
  $client_secret='0e6ad76e4ebd6bae6660bcc3908a421143644ddca0';
  }
   if($dc == 'com.au' ){
  $client_id='1000.7Y0LTS21560E41BQPS1EW24R87FOUN';
  $client_secret='a922b07758b1820c00da07448c7db801f09a5b1272';
  }
  
  $secret=array('eu'=>'f659dba19a084551da0d3d34080ac4b06b23e5b976','in'=>'09e03e8e5ead546bbd8932368cf8b2d0a9fdda2f7e','jp'=>'60e9ea26cbd5650a0fe86f0acee29962be0f1dd938','uk'=>'f12e7ce4603798e289326a2184c90e212026505438','ca'=>'2716dd6f1dbb34d2eb056e01de7976ab09445eeb3e');
  }else if($this->id == 'vxg_zoho'){
      
  $client_id='1000.5X3DYKDO3XDH837304FOWEEUQRIYLM';
  $client_secret='91eaa6878b6d0c77644c26a5c4c9b9da394a353e78';  
   if($dc == 'com.cn' ){
  $client_id='1000.RE0ZEM75FBOG52882KNP8GPJTGEUQP';
  $client_secret='cedf6f4dcf2d4952be21558cfbe83d1db66f12ed98';
  }
   if($dc == 'com.au' ){
  $client_id='1000.6SPXIIHITEA64DKY1YR5EQUUHA2LHN';
  $client_secret='815a7be23d3a04d7e815d17c989f17d7b79286538e';
  }
  $secret=array('eu'=>'cf65bd821349873353d3c75c747e951fb87706991a','in'=>'703fd2dd6384cdaa8fd648ba7dc63f199866fe12f0','jp'=>'73bcba298506f86f55c50477ba73fbaee8aeb34bfc','uk'=>'979ac17c75b5bc241a2732c240ead263d3e5a7388d','ca'=>'e740de46cb15dc3cd34bc6ae3e9af282b6eafb005c');
  }
  //custom app
  if(is_array($info)){
      
      if(!empty($info['dc']) && isset($secret[$info['dc']])){
        $client_secret=$secret[$info['dc']];  
      }
      if($this->post('custom_app',$info) == "yes" && $this->post('app_id',$info) !="" && $this->post('app_secret',$info) !="" && $this->post('app_url',$info) !=""){
     $client_id=$this->post('app_id',$info);     
     $client_secret=$this->post('app_secret',$info);     
     $call_back=$this->post('app_url',$info);     
      }
  }
  return array("client_id"=>$client_id,"client_secret"=>$client_secret,"call_back"=>$call_back);
}
public function post_crm($path,$method='get',$body=""){
$header=array();   //'content-type'=>'application/x-www-form-urlencoded' ;   

$is_file=false;
if($method == 'token'){
$method='post';   

}else{
  if($method == 'file'){
$method='get';   
$is_file=true;
}
$dc=isset($this->info['dc'])  ? $this->info['dc'] : 'com';
if(!empty($this->info['type']) && !empty($this->info['zoho_org'])){
$concat='?';
if(strpos($path,'?') !== false){
$concat='&';    
}
$path.=$concat.'organization_id='.$this->info['zoho_org'];
}
$zoho='zoho.'; if($dc == 'ca'){$zoho='zohocloud.';}
//var_dump($path);
$is_crm=false;
if($this->info['type'] =='invoices'){
 //$path='https://invoice.'.$zoho.$dc.'/api/v3/'.$path;   
 $path='https://www.zohoapis.'.$dc.'/invoice/v3/'.$path;   
}else if($this->info['type'] =='books'){
 //$path='https://books.'.$zoho.$dc.'/api/v3/'.$path;   
 $path='https://www.zohoapis.'.$dc.'/books/v3/'.$path;   
}else if($this->info['type'] == 'inventory'){
 //$path='https://inventory.'.$zoho.$dc.'/api/v1/'.$path;   
 $path='https://www.zohoapis.'.$dc.'/inventory/v1/'.$path;  //https://www.zohoapis.ca/inventory/ works 
}else{ $is_crm=true;
$crm='crm/v6'; //v2.1
if($this->info['type'] == 'bigin'){ $crm=$this->info['type'].'/v2'; }
$path='https://www.zohoapis.'.$dc.'/'.$crm.'/'.$path;     
}

$token_time=!empty($this->info['token_time']) ? $this->info['token_time'] :'';
$time=time();
$expiry=intval($token_time)+3500;   //86400
if($expiry<$time){
    $this->refresh_token(); 
}  
$access_token=!empty($this->info['access_token']) ? $this->info['access_token'] :'';
$header['Authorization']='Zoho-oauthtoken ' .$access_token; 
if(!is_array($body) && $is_crm){
$header['Content-Type']='application/json'; //required for add_tags feature of zoho crm , does not work with books, inventory etc
}
//$header[]='Authorization: Zoho-oauthtoken ' .$access_token; 
}
//var_dump($header,$path); die();

 if(!empty($body) && is_array($body) && isset($body['attachments_v2'])){
     $files = array(); $file_name='attachments[]';
if(!empty($body['attachments'])){
$files=$body['attachments'];
unset($body['attachments']);
}
if(!empty($body['attachments_v2'])){
$files=$body['attachments_v2'];
unset($body['attachments_v2']);
$file_name='file';
}
$boundary = wp_generate_password( 24 );
$delimiter = '-------------' . $boundary;
$header['Content-Type']='multipart/form-data; boundary='.$delimiter;
$body = $this->build_data_files($boundary, $body, $files,$file_name);
}

$args=array(
  'method' => strtoupper($method),
  'timeout' => $this->timeout,
  'headers' => $header,
 'body' => $body
  );
$response = wp_remote_request( $path , $args); 
//if($method != 'get'){
   // var_dump($header); //die();
//}
$body = wp_remote_retrieve_body($response);
//var_dump($body,$args,$path);

  if(is_wp_error($response)) { 
  $error = $response->get_error_message();
  return array('error'=>$error);
  }else{
 if($is_file){
$body=array('file'=>$body);

if(!empty($response['headers']['content-disposition'])){
    //$response['headers']['content-disposition']="attachment;filename*=UTF-8''283812000000344015_Products_photo.png";
$filehead=$response['headers']['content-disposition'];
 if(preg_match('/filename="(.+?)"/', $filehead, $matches)) {
        $body['title']=$matches[1];
}else if(preg_match('/filename=([^; ]+)/', $filehead, $matches)) {
        $body['title']=rawurldecode($matches[1]);
}else if(preg_match('/\w+\.\w+/', $filehead, $matches)) {  
        $body['title']=$matches[0];
}    
}
//var_dump($path,$response['headers'],$body['title'],$matches); die();
 }else{     
 $body=json_decode($body,true);     
  } }
  return $body;
}
public function build_data_files($boundary, $fields, $files, $file_name='attachments[]'){
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
            . $content . $eol;
    }

    foreach ($files as $name => $file) {
    $name=basename($file);
   $content = file_get_contents($file);
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="'.$file_name.'"; filename="'.$name.'"' . $eol
            //. 'Content-Type: image/png'.$eol
            . 'Content-Transfer-Encoding: binary'.$eol;

        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--".$eol;


    return $data;
}
  
public function get_entry($module,$id){
$arr=$this->post_crm($module.'/'.$id);
 $entry=array();
if(!empty($arr['data'][0]) && is_array($arr['data'][0])){
    $entry=$arr['data'][0];
}
return $entry;     
}

}
}
?>