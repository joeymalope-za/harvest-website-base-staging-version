<?php 
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;
if( !class_exists( 'vxcf_zoho_cron' ) ) {

class vxcf_zoho_cron extends vxc_zoho{
    
  public static $checking_update=false;
  public $items_batch=100;
  public $save_product=false;
 public static $lics=array();    
 public static $users=array();    
 public static $updates=array();  
   
public function __construct() {
add_action( 'add_section_tab_wc_vxc_zoho', array( $this, 'output_sections' ),60 );
add_filter( 'add_section_html_vxc_zoho', array( $this, 'section_html' ) );
add_filter( 'Zoho_woo_objects_list', array( $this, 'add_objects' ) );
add_action('wp_ajax_vxc_zoho_get_sync_settings', array($this, 'get_settings_ajax'));
//add_action('wp', array($this, 'wp')); 
add_action( 'vxc_zoho_item_cron',array($this,'cron'));
add_filter( 'cron_schedules',array($this,'cron_schedules'));
add_filter('woocommerce_add_to_cart_validation', array($this, 'maybe_validate_stock'),20,5);
}   
public function add_objects($objects){
foreach($objects as $k=>$v){
    if(empty($k)){ unset($objects[$k]); }
}
$arr=array('salesreceipt'=>'Sales Receipt','estimate'=>'Estimate','creditmemo'=>'Credit Memo','refundreceipt'=>'Refund Receipt','payment'=>'Payment');
return array_merge($objects,$arr);    
}

public function set_item_cron($meta,$cron){
 
    $next=0;
    if(isset($cron['next'])){ unset($cron['next']); }
    $next = wp_next_scheduled( 'vxc_zoho_item_cron' );
   // var_dump($next);
    $next += (int)$meta['item_cron'];
    $time=current_time( 'timestamp' );
   // var_dump($next,$time);
    $cron['next']=array('label'=>'Next Check','time'=>$next);   
    return $cron;  
 if($next){
 wp_clear_scheduled_hook( 'vxc_zoho_item_cron' ); 
 }
   if(!empty($meta['sync_items']) || !empty($meta['sync_stock']) || !empty($meta['sync_order']) ){
     $time=current_time( 'timestamp' ,1 );
        $next = $time+(int)$meta['item_cron'];
        $local_next= current_time( 'timestamp') +$meta['item_cron'];
       // $next = $time+1;
 $created=wp_schedule_single_event($next, 'vxc_zoho_item_cron');
//  if($created !== false ){
 $cron['next']=array('label'=>'Next Check','time'=>$local_next);   
//}      
// }
 }

 return $cron;  
}

public function wp(){
if(isset($_GET['vx'])){
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$this->cron();  die();   
}
}

public function get_woo_status($meta,$zoho_status) {
  $woo_status='';
    if( !empty($meta['zoho']) && !empty($meta['woo']) ){
    foreach($meta['zoho'] as $z_key=>$z_status){
           if( strtolower($z_status) == strtolower($zoho_status) && isset($meta['woo'][$z_key])){
          $woo_status=substr($meta['woo'][$z_key],3); 
          break;    
           }   
          }
    } 
    return $woo_status;    
}
public function cron() {

/*    
$pro_id=10824;
//$pro_id=26; //variation , simple 
$p=wc_get_Product($pro_id);
$p->set_stock_quantity(25);

$st=$p->variation_is_visible();
var_dump($st);
//$p->set_status('publish');
//$p->save();
die();
*/
ini_set('max_execution_time', 1000);
$cron=get_option('vxcf_zoho_cron_status',array());
$meta=get_option('vxcf_zoho_cron',array());
if(empty($meta['account'])){ return; }
$is_error=false;
//$cron=$this->set_item_cron($meta,$cron);
//update_option('vxcf_zoho_cron_status',$cron);

 $log_arr=array("object"=>'',"order_id"=>'0',"crm_id"=>'0',"meta"=>'',"time"=>date('Y-m-d H:i:s'),"status"=>'6',"link"=>'',"data"=>array(),"response"=>'',"extra"=>array(),"feed_id"=>'','parent_id'=>'0','event'=>'');
 
$info=$this->get_info( $meta['account'] );
$api=$this->get_api($info); 


 global $wpdb;
$save=$is_cron=false; $is_crm=false;
$orders_done=array();
if(!empty($meta['sync_items']) || !empty($meta['sync_stock'])){
 //   $str='2019-08-16T23:50:42-07:00';
 //   echo date('Y-m-d H:i:s',strtotime($str)); die();

$after=!empty($meta['after']) ? $meta['after'] :  0;   
$zone=get_option('timezone_string');
try{$zone=new DateTimeZone($zone);}catch(Exception $e){ $zone=null;}
   
$d = new DateTime(date('Y-M-d H:i:s',$after)); //,$zone

//$d->setTimezone($la_time);
$items=$created=$mod=array();
 $type='';
if(empty($info['data']['type']) || in_array($info['data']['type'],array('crmplus'))){
$q="select Product_Name,Description,Product_Code,Product_Category,Qty_in_Stock,Qty_in_Demand,Unit_Price,Tag,Product_Active,Created_Time,Modified_Time from Products  where Modified_Time > '".$d->format('c')."'  order by Modified_Time asc";//; // $this->items_batch
if(!empty($meta['page'])){
    $q.=' limit '.($meta['page']*$this->items_batch).', '.$this->items_batch;
}else{
 $q.=' limit '.$this->items_batch;   
}
$post=array('select_query'=>$q);
$post=json_encode($post);
$res=$api->post_crm('coql','post',$post);
//$data='{"data":[{"Product_Category":null,"Qty_in_Demand":0,"Modified_Time":"2023-11-01T12:04:02+05:00","Description":null,"Product_Code":null,"Created_Time":"2020-06-02T12:16:16+05:00","Product_Name":"First Subscriptionxxxa","Qty_in_Stock":0,"id":"283812000000344015","Tag":[],"Product_Active":true,"Unit_Price":0}],"info":{"count":16,"more_records":false}}';
//$res=json_decode($data,1);
//var_dump($res); die();
$is_crm=true;
}else{
$type=$info['data']['type'];
//$this->items_batch='300'; $meta['page']=0;
 $q='items?sort_column=last_modified_time&per_page='.$this->items_batch.'&sort_order=A&last_modified_time='.urlencode($d->format('Y-m-d\TH:i:sO'));
if(!empty($meta['page'])){
    $q.='&page='.(intval($meta['page'])+1);
}

$res=$api->post_crm($q); //2019-11-27T12:05:02-0500
//var_dump($res); die();
///$res=$api->post_crm('items?search_text=ET-PSX16'); //2019-11-27T12:05:02-0500
//var_dump($res); die(); //for incventory actual_available_stock and available_stock
//$res=$api->post_crm('items?last_modified_time=2020-01-21T03:28:23-0000'); //%2B for + in timezone
//?page=1&per_page=50&sort_column=created_time&sort_order=D&usestate=true&organization_id=xxxx&created_time_greater_than=2017-03-06T08:02:51-0500&filter_by=Status.Inactive https://help.zoho.com/portal/community/topic/how-use-filter-and-pagination-during-call-zohobooks-api
if(!empty($res['items'])){
 $res['data']= array();
 foreach($res['items'] as $v){ 
   if(isset($v['item_id'])){
       $v['id']=$v['item_id'];
   }
   if(isset($v['name'])){
       $v['Product_Name']=$v['name'];
   }
   if(isset($v['Product_Active'])){
       $v['status']=$v['Product_Active'] === true ? 'active' : 'Inactive';
   }
   if(isset($v['description'])){
       $v['Description']=$v['description'];
   }
   if(isset($v['sku'])){
       $v['Product_Code']=$v['sku'];
   }
   if(isset($v['rate'])){
       $v['Unit_Price']=$v['rate'];
   }
   /*
         'stock_on_hand' => float 21
      'asset_value' => string '' (length=0)
      'available_stock' => float 21  //accounting stock
      'actual_available_stock' => float 21  //physical stock
      'committed_stock' => float 2
      'actual_committed_stock' => float 2
      'available_for_sale_stock' => float 19
      'actual_available_for_sale_stock' => float 19
      */
      /* myithub au inventory has this only
          "stock_on_hand": 60,
    "has_attachment": false,
    "is_returnable": false,
    "available_stock": 58,
    "actual_available_stock": 60,
    lultofathena.com
        "available_stock": -2, //needs this , and it is their physical stock available for sale
    "actual_available_stock": 0,
    */
   //'available_stock actual_available_stock ,committed_stock actual_committed_stock available_for_sale_stock actual_available_for_sale_stock
   if(isset($v['available_stock']) ){  //accounting stock on hand
       $v['stock_on_hand']=$v['available_stock'];
   }  
    if(isset($v['actual_available_stock']) ){  //physical stock on hand
       $v['stock_on_hand']=$v['actual_available_stock'];
   }
     $qty_field=!empty($meta['item_qty_field']) ? $meta['item_qty_field'] : 'actual_available_for_sale_stock';
   //physical stock available for sale
   if(isset($v[$qty_field]) ){  //actual_available_stock or available_stock && !isset($v['stock_on_hand'])
       $v['stock_on_hand']=$v[$qty_field];
   }

   if(isset($v['stock_on_hand'])){
       if( !empty($meta['warehouse']) && in_array($type,array('inventory'))){
     $item=$api->post_crm('items/'.$v['id']); 
     if(!empty($item['item']['warehouses'])){
     foreach($item['item']['warehouses'] as $w){
        if($w['warehouse_id'] == $meta['warehouse']){ 
        $v['stock_on_hand']=floatval($w['warehouse_'.$qty_field]);  //warehouse_stock_on_hand , warehouse_available_stock  ,  warehouse_available_for_sale_stock   , warehouse_actual_available_for_sale_stock  ,warehouse_actual_committed_stock
        /*{
            "warehouse_id": "2799904000000376323",
            "warehouse_name": "Happy_LA_OTHERS",
            "status": "active",
            "is_primary": false,
            "warehouse_stock_on_hand": 0.000000,
            "initial_stock": 0.0,
            "initial_stock_rate": 0.00,
            "warehouse_available_stock": 0.000000,
            "warehouse_actual_available_stock": 0.000000,
            "warehouse_committed_stock": 20.000000,
            "warehouse_actual_committed_stock": 20.000000,
            "warehouse_available_for_sale_stock": -20.000000,
            "warehouse_actual_available_for_sale_stock": -20.000000,
            "is_fba_warehouse": false,
            "sales_channels": []
        }*/
        } 
     }    
     }     
       }
       $v['Qty_in_Stock']=$v['stock_on_hand'];
   }
   if(isset($v['last_modified_time'])){
       $v['Modified_Time']=$v['last_modified_time'];
   }
     $res['data'][]=$v;  
 }
 unset($res['items']); 
} 
}
if(isset($_GET['vx'])){
echo json_encode($res['data']).'-------'.date('Y-M-d H:i:s',$after).'----------'.$q; //die('---------');
}
//var_dump($res,$q); die();


$logs=array(); $last_id=''; $after=$after_m=0; $n=0; $items_found=$items_done=$items_updated=$orders_found=$orders_done=array();

if(!empty($res['data'])){
foreach($res['data'] as $item){
      

if( $n >= $this->items_batch ){ break; }

$item_id=$item['id'];
$after=max($after,strtotime($item['Modified_Time'])); //createdDate 
$last_id=$item_id;

$cat_name='';
if(!empty($item['category_name'])){
 $cat_name=$item['category_name'];
}else if(!empty($item['Product_Category'])){
   $cat_name=$item['Product_Category'];
} 

if(!empty($meta['item_cats']) && is_array($meta['item_cats']) && !in_array($cat_name,$meta['item_cats'])){
  continue;  
}


$log='Zoho Item #'.$item_id;

$title=$desc=$short=$img=$sku='';
if(!empty($item['Product_Name'])){
$items_found[$item_id]['title']=$title=$item['Product_Name'];   
$items_found[$item_id]['category']=$cat_name;   
}
if(!empty($item['Description'])){
 $items_found[$item_id]['short_description']=$short=$desc=$item['Description'];   
}

 $sku=$item['Product_Code']; 
 if(empty($sku)){ $sku=$item_id; }
 //$sku=preg_replace("/[^a-zA-Z0-9_-]+/", "", $sku); 
 $items_found[$item_id]['sku']=$sku; 
 if(isset($item['Qty_in_Demand'])){
  // $item['Qty_in_Stock']=$item['Qty_in_Demand'];  
 }
  if(isset($item['Qty_in_Stock'])){
 $items_found[$item_id]['qty']=$item['Qty_in_Stock']; 
 }
$pro_id=wc_get_product_id_by_sku($sku);   
if(empty($pro_id) && empty($meta['sync_items']) ){ continue;  } // if product does not exists and add products not enabled , then continue
if(!empty($pro_id) && (empty($meta['sync_stock']) && empty($meta['sync_title']) ) ){ continue;  } // if product exists , and stock or title is not enabled 

$p_status='';
//$p_status='draft';    
//$p_status='publish';   

if(empty($title) || empty($sku) ){
    $log.=' Error: Title , SKU or description is empty';
    $logs[$item_id]=$log; $is_error=true;
   continue;
} 

    
    if(empty($pro_id)){
$p=new WC_Product($pro_id); 
    $log.=' (new product) ';    
try{

    if(!empty($meta['back_order'])){
 $p->set_backorders($meta['back_order']);   
}   
if(!empty($meta['sync_stock']) && isset($item['Qty_in_Stock']) ){
 $p->set_manage_stock(1);
} 
$p->set_sku($sku);
    
}catch(Exception $e){
    $log.=' Error: '.$e->getMessage();
    $logs[$item_id]=$log; $is_error=true;
     continue; 
} }else{ //updating old product , support variations
    $p=wc_get_Product($pro_id);
}

if(empty($pro_id) || !empty($meta['sync_title'])){
    $p->set_name($title); 
  //  $p->set_description($desc);
  //  $p->set_status('publish');
    $p->set_short_description($short);
    $this->save_product=true; $cat_name=''; $cats_all=array();
    if(!empty($item['category_name'])) { 
        $cat_name=$item['category_name'];
        $cats_all[]=$cat_name;
if(empty($pro_id)){
     $cats_arr=$api->post_crm('categories');
    if(!empty($cats_arr['categories'])){
    $cats=array(); $cat_sel=array();
    
      foreach($cats_arr['categories'] as $cat){
  if($cat['name'] == $cat_name){
      $cat_sel=$cat;
  } 
  if($cat['category_id'] != '-1'){ 
$cats[$cat['category_id']]=$cat;  
  }        
      }
if($cat_sel['parent_category_id'] != '-1'){
 do{
    $cat_sel=$cats[$cat_sel['parent_category_id']];
     $cats_all[]=$cat_sel['name'];  
 }while(!empty($cats[$cat_sel['parent_category_id']]));
 $cats_all=array_reverse($cats_all);     
} }      
}
    
}else if(!empty($item['Product_Category'])) {
       $cats_all[]=$cat_name=$item['Product_Category'];
    }
  
    if(!empty($cats_all)){
     $cat_id=$par_id='';   
foreach($cats_all as $cat_nam){
    $cid = get_term_by('name',$cat_nam, 'product_cat' );
    $term_id='';
    if(!$cid ){
     $cat_args=array(
          //  'description'=> $data['description'],
           // 'slug' => $data['slug'],
         //   'parent' => $data['parent']
        );
        if(!empty($par_id)){
      $cat_args['parent']=$par_id;      
        }   
        $cid = wp_insert_term(
        $cat_nam, // the term 
        'product_cat', // the taxonomy
        $cat_args
         );
      if(!empty($cid['term_id'])){ 
  $cat_id=$par_id=$cid['term_id'];   
 }   
    }else{
    $cat_id=$par_id=$cid->term_id;    
    }
    
}
 if(!empty($cat_id)){   
  $p->set_category_ids(array($cat_id));   
 } }
 
    }
    
//var_dump($p); die();  
/************** save product ********************************/
//sync stock
if(isset($item['status']) && strtolower($item['status']) == 'inactive'){
 $p_status='draft';   
}
$log.=$this->sync_item($p,$item,$meta,$pro_id,$p_status);
if($this->save_product){    
$p->save();
}

if(empty($pro_id)){
    if(empty($type)){
$url='Products/'.$item_id.'/photo';
    }else{ //$type == 'inventory'
  $url='items/'.$item_id.'/image';      
    }
 if(!empty($url)){   
$img_res=$api->post_crm($url,'file');  
if(!empty($item['image_name'])){ //available for zoho inventory etc but not for crm
    $img_res['title']=$item['image_name'];
}

$items_found[$item_id]['image']='No';
if(!empty($img_res['file']) && !empty($img_res['title'])){ 
  $upload=$this->upload_image_wp( $img_res['title'] , $img_res['file'] );
$media_id=$this->set_uploaded_image_as_attachment($upload);  
if(!empty($media_id)){ $p->set_image_id($media_id); $p->save(); }
$items_found[$item_id]['image']=$upload['url'];
}
 }
 
           
$items_done[]=$p->get_id();
}else{ 
$items_updated[]=$p->get_id();
}
$id=$p->get_id();
update_post_meta($id,'cfx_zoho_id',$item_id);
$log.=' to WooCommerce #'.$id;    
$n++;
$logs[$item_id]=$log;

}
}

if(!empty($after)){
    if(!$is_crm){ 
    //    $after+=1; 
    } 
    //increase 1 second for books , inventory etc
    if(!empty($meta['after']) && $meta['after'] == $after){
        if(empty($meta['page'])){ $meta['page']=0; }
     $meta['page']= $meta['page']+1;  
    }else{
     $meta['page']=0;    
    }
    $meta['after']=$after;
    $meta['last_id']=$last_id;
    $save=true;
  do_action('vxc_zoho_items_cron',$res['data']);    
}else{ //after is empty = no item found
 if(!empty($meta['page'])){ //page is not empty = reached end page with 0 result
    $meta['page']=0; $save=true;
    $meta['after']+=1; //we have looped over all pages with same time , so increase time by 1 sec because we can not check last page again and again or start from 0 page
}   
}
$is_cron=true;
}
if(!empty($meta['sync_order'])){

//$d_order = new DateTime('2020-02-12 01:11:11');
$after_order=!empty($meta['after_order']) ? $meta['after_order'] :  0; 
//$after_order=strtotime('2021-05-12T13:10:41-0500');
$d_order = new DateTime(date('Y-M-d H:i:s',$after_order)); 
//var_dump($after_order);
$orders=array();
if(empty($info['data']['type'])){
   $q="select Subject,Status,Created_Time,Modified_Time from Sales_Orders where Modified_Time > '".$d_order->format('c')."'  order by Modified_Time desc "; 
   if(!empty($meta['page_order'])){
    $q.=' limit '.($meta['page_order']*$this->items_batch).', '.$this->items_batch;
}else{
 $q.=' limit '.$this->items_batch;   
}
 // $q="select Subject,Status,Created_Time,Modified_Time from Sales_Orders where Modified_Time > '2020-03-23T13:01:11+05:00'"; 
$post=array('select_query'=>$q);
$post=json_encode($post);
$res=$api->post_crm('coql','post',$post);
if(!empty($res['data'])){
 $orders=$res['data'];   
}
$is_crm=true;
}
else{
$type=$info['data']['type'];
$q='salesorders?sort_column=last_modified_time&sort_order=A&per_page=30&last_modified_time='.urlencode($d_order->format('Y-m-d\TH:i:sO'));

if(!empty($meta['page_order'])){
    $q.='&page='.(intval($meta['page_order'])+1);
}
$res=$api->post_crm($q);
if(!empty($res['salesorders'])){
 $orders=$res['salesorders'];  
} 
}
if(isset($_GET['vx'])){ 
    echo $q.'-------------------------'; echo json_encode($orders); //die();

}
if(!empty($orders)){
$order_status_arr=array();
foreach($orders as $v){
 $mod_time='';
 if(isset($v['last_modified_time'])){ 
 $mod_time=$v['last_modified_time']; 
 }else if(isset($v['Modified_Time'])){
 $mod_time=$v['Modified_Time'];    
 }
 $order_id='';
 if(isset($v['salesorder_id'])){
 $order_id=$v['salesorder_id'];    
 }else if(isset($v['id'])){
 $order_id=$v['id'];    
 }
 $order_status='';
  if(isset($v['order_status'])){
 $order_status=strtolower($v['order_status']);    
 }else if(isset($v['status'])){
 $order_status=strtolower($v['status']);    
 }else if(isset($v['Status'])){
 $order_status=$v['Status'];    
 }
 $order_status_arr[$order_id]=$order_status;
if(!empty($mod_time)){    
$after_order=max($after_order,strtotime($mod_time));
}     
}


if(isset($_GET['vx'])){

    $text=json_encode($orders).'-------'.$d_order->format('Y-m-d\TH:i:sO');
  //  echo $text;
//    $filename = dirname(__FILE__).'/log.txt';
//$fh = fopen($filename, "a");
//fwrite($fh, $text."\r\n");
//fclose($fh);
//die('---------'.$filename);
}
$order_id='';
if(!empty($order_status_arr)){
$ids_temp=array_keys($order_status_arr);
//$sql="SELECT * FROM $wpdb->postmeta WHERE meta_key ='vxc_zoho_order' AND  meta_value in ('".implode("','",$ids_temp)."') LIMIT 100";   
//$posts=$wpdb->get_results($sql, ARRAY_A); 
$args = array(
    'status'        => 'any', // 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled', 
    'meta_key'      => 'vxc_zoho_order',
    'meta_value'    => $ids_temp, 
    'meta_compare'  => 'IN', // Possible values are ‘=’, ‘!=’, ‘>’, ‘>=’, ‘<‘, ‘<=’, ‘LIKE’, ‘NOT LIKE’, ‘IN’, ‘NOT IN’, ‘BETWEEN’, ‘NOT BETWEEN’, ‘EXISTS’ 
    //'return'        => 'ids' ,
    'limit'=>'100' 
);

$orders = wc_get_orders( $args );
//var_dump($orders,$ids_temp,$order_status_arr); die();
if(isset($_GET['vx'])){
echo json_encode($posts).'------------'.$sql; //die(); 
} 
if(!empty($orders)){
     foreach($orders as $order){
       // $order_id=$v['post_id'];
       $zoho_id=$order->get_meta('vxc_zoho_order',true);
        $order_id=$order->get_id();
       // $order = new WC_Order($order_id); 
       $woo_status=$order->get_status();
      $orders_found[$order_id]=$zoho_id;
        if($order && isset($order_status_arr[$zoho_id])){
 $zoho_status=$order_status_arr[$zoho_id]; 
            $new_status=$this->get_woo_status($meta,$zoho_status);
            if(isset($_GET['vx'])){
           var_dump($new_status,$woo_status,$zoho_status,esc_attr($order_id),$zoho_id);  echo '----------'; //die();
            }
          if(!empty($new_status) && $new_status != $woo_status ){
          $orders_done[$zoho_id]='Status of woo #'.$order_id.' and zoho #'.$zoho_id.' is '.$new_status;      
            $order->update_status($new_status); 
          }
        }
    }
}
}
if(isset($_GET['vx'])){
//echo json_encode($orders_done).'------------'.json_encode($orders_found); die(); 
}

if(!empty($after_order)){
    if(!$is_crm){ 
    //    $after+=1; 
    } 
    //increase 1 second for books , inventory etc
    if(!empty($meta['after_order']) && $meta['after_order'] == $after_order){
        if(empty($meta['page_order'])){ $meta['page_order']=1; }
     $meta['page_order']= $meta['page_order']+1;  
    }else{
     $meta['page_order']=0;    
    }
    $meta['after_order']=$after_order;
    $meta['last_id_order']=$order_id;
    $save=true;
}
}else if(!empty($meta['page_order'])){
  $meta['page_order']=0;  $save=true;
}
$is_cron=true;
if(!empty($res['data'])){
do_action('vxc_zoho_orders_cron',$res['data'],$orders_done);
} 
}

if($is_cron){
    if($save){ 
update_option('vxcf_zoho_cron',$meta);     
}
//var_dump($cron); die();
if( !empty($items_updated) || !empty($items_done) ){
 $log_arr['object']='items_cron';   
 $log_arr['meta']= count($items_found).' items found and '.count($items_done).' products created and '.count($items_updated).' products updated';

 foreach($items_found as $it=>$item_lg){
     $log_arr['data']['Item #'.$it]=$item_lg;
 } 
 foreach($logs as $it=>$item_lg){
     $log_arr['extra']['Item #'.$it]=$item_lg;
 }
  
}
if( !empty($orders_done) ){
   $log_arr['object']='items_cron'; 
    if(!empty($log_arr['meta'])){
        $log_arr['meta'].=' and ';
    }
    $log_arr['meta'].=count($orders_done).' Orders Updated';
  //  $log_arr['meta']=trim($log_arr['meta']);
   foreach($orders_done as $it=>$item_lg){
       $logs['order #'.$it]=$item_lg;
     $log_arr['extra']['Order #'.$it]=$item_lg;
 }
  foreach($orders_found as $it=>$item_lg){
     $log_arr['data']['Order #'.$it]=$item_lg;
 }   
}


if(!empty($log_arr['object'])){
 $log_arr['data']=json_encode($log_arr['data']);   
 $log_arr['extra']=json_encode($log_arr['extra']); 
$table=$wpdb->prefix ."vxc_zoho_log";
$wpdb->insert($table,$log_arr);
}

$time=current_time( 'timestamp' );


if(!empty($logs)){  
$cron['item']=array('logs'=>$logs,'label'=>'Sync Items/Orders','time'=>$time);
}
$cron['last']=array('label'=>'Last Checked','time'=>$time);
update_option('vxcf_zoho_cron_status',$cron);


if($is_error && !empty($logs) && !empty($info['data']['error_email']) ){
$email_info=array("msg"=>implode('<br/>',$logs),"title"=>__("Woocommerce Zoho CRON Error - CRM Perks",'woocommerce-zoho-crm'));
    $email_body=vxc_zoho::format_user_info($email_info,true); 
  $error_emails=explode(",",$info['data']['error_email']); 
  $headers = array('Content-Type: text/html; charset=UTF-8');
  foreach($error_emails as $email)   
  wp_mail(trim($email),$email_info['title'], $email_body,$headers);
     
}
}
//die('-----cron end-----');
}


public function maybe_validate_stock($passed, $product_id, $quantity, $variation_id = '', $variations= ''){
        $p=false;
      try{
      $p=new WC_Product($product_id); 
      }catch(Exception $e){}
   if(!$p){ return $passed; } 
    
    $sku=$p->get_sku();
    $stock=$p->get_manage_stock();
if(!empty($sku) && $stock){
   $meta=get_option('vxcf_zoho_cron',array());  
   if(!empty($meta['account']) && !empty($meta['sync_stock']) ){
   $info=$this->get_info($meta['account']); 
$api=$this->get_api($info);
 $url='Products/search?criteria='.urlencode('((Product_Code:equals:'.$sku.'))');
 $res=$api->post_crm($url); 
if(!empty($res['data'][0])){
$this->sync_item($p,$res['data'][0],$meta,$product_id);
} } }

    return $passed; 
}

public function sync_item($p,$item,$meta,$post_id='',$status='' ){
 $log='';
try{    
$save=false;
   $log='';
   if(!empty($post_id)) { $log.=' WooCoommerce #'.$post_id; } 

$p_type=$p->get_type();
$stock=$p->get_manage_stock(); $qty_db=0;

if($stock && isset($item['Qty_in_Stock']) ){
$qty=$item['Qty_in_Stock'];
if($stock === 'parent'){
    $parent_id=$p->get_parent_id();
    $parent=wc_get_Product($parent_id);
    if($parent){
    $qty_db=$parent->get_stock_quantity();    
      if($qty_db != $qty){
      $parent->set_stock_quantity($qty);
      $parent->save();
    }      
    }
}else{ 
      $qty_db=$p->get_stock_quantity();    
      if($qty_db != $qty){
          $save=true;
      $p->set_stock_quantity($qty);
   
      }
      
}   
$log.=' Updating Qty='.$qty.' old Qty='.$qty_db;
if(empty($qty) && !empty($meta['stock'])){
 $status=$meta['stock'];  
}

}else{
    $log.=' Manage Stock disabled';
}
if(in_array($p_type,array('simple','variation')) && !empty($meta['sync_price']) ){      //&& !empty($meta['sync_items'])  update price without adding new items 
 //set price for simple or variation , Not variable
$price_db=$p->get_regular_price();
$price=$item['Unit_Price'];
if($price_db != $price){
$p->set_regular_price($price);  
$log.=' Updating price='.$price;
$save=true;   
} 
   
}
//change status to draft or trach if out of stock
if(!empty($status) && !in_array($p_type,array('variation')) ){ //do change satus of variations
$save=true;
$p->set_status($status); 
$log.=' Status='.$status;   
}
//var_dump($save); die();

if($save || empty($post_id) ){
$p->save(); 
$this->save_product=false;
   if(empty($post_id)) { 
       $post_id=$p->get_id();
       $log.=' WooCoommerce #'.$post_id; 
   }  

}

}catch(Exception $e){}
return $log; 
}

public function output_sections($sections) {
$sections['cron']=__('Zoho to WooCommerce','woocommerce-zoho-crm');
return $sections;
}

public function section_html($page_added){

/*    $pro_id=30; $qty=20; $price=25;
    $p=wc_get_product($pro_id);  //does not convert variable to simple when updating price ,instead new WC_Product convert variable to simple 
$p->set_stock_quantity($qty);
$p->set_regular_price($price); 
$p->save();
die();*/
    global $current_section;
if(!$page_added && $current_section == 'cron' && current_user_can('vxc_zoho_read_settings') ){
$page_added=true; 
$meta=get_option('vxcf_zoho_cron',array());
$cron=get_option('vxcf_zoho_cron_status',array());

if(!is_array($meta)){ $meta=array(); }
$time=current_time( 'timestamp' );

if(!empty($_POST['crm'])){
 $meta=$this->post('crm');
 
$offset= get_option('gmt_offset');
 $after=strtotime($meta['after']);
 if(!empty($after)){
 $meta['after']=$after-($offset*3600);
}

$after_order=strtotime($meta['after_order']);
if(!empty($after_order)){
 $meta['after_order']=$after_order-($offset*3600); 
}
   $crons=array('60'=>'every_minute','300'=>'5min','900'=>'15min','1800'=>'30min','3600'=>'hourly','43200'=>'twicedaily','86400'=>'daily','604800'=>'weekly','18144000'=>'Monthly');
   wp_clear_scheduled_hook( 'vxc_zoho_item_cron' ); 
   if(!empty($meta['sync_items']) || !empty($meta['sync_stock']) || !empty($meta['sync_order']) ){
     $cronn=$meta['item_cron'];
      if (! wp_next_scheduled ( 'vxc_zoho_item_cron') && !empty($crons[$cronn])) {
        wp_schedule_event( time(), $crons[$cronn], 'vxc_zoho_item_cron' );
    }  
   }
//$cron=array();
//$cron=$this->set_item_cron($meta,$cron);
// $meta=array_merge($meta,$post);
 update_option('vxcf_zoho_cron',$meta);
//  update_option('vxcf_zoho_cron_status',$cron);
// $meta=get_option('vxcf_zoho_cron',array());
// $cron=get_option('vxcf_zoho_cron_status',array());
}


      global $wpdb;
 $table=$wpdb->prefix . 'vxc_zoho_accounts';

$sql='SELECT * FROM '.$table.' where  status !=9 limit 100';
$accounts = $wpdb->get_results( $sql ,ARRAY_A );

wp_enqueue_script('jquery-ui-datepicker' );
wp_enqueue_script('jquery-ui-slider' );
wp_enqueue_style('vxc-ui', self::$base_url.'css/jquery-ui.min.css');
wp_enqueue_style('vxc-ui-time', self::$base_url.'css/jquery-ui-timepicker-addon.css');
wp_enqueue_script('vxc-ui-time', self::$base_url.'js/jquery-ui-timepicker-addon.js');

?>
<table class="form-table">
<tr>
<th><label for="vx_ac"><?php esc_html_e('Zoho Account','woocommerce-zoho-crm'); ?></label></th>
<td>  <select id="vxc_account" name="crm[account]" style="width: 100%">
<option value=""><?php esc_html_e('Select Account','woocommerce-zoho-crm'); ?></option>
<?php
  foreach($accounts as $v){
      $info_arr=array();
      if(!empty($v['data'])){ 
 // $info_arr=json_decode($this->de_crypt($v['data']),true); 
      }
   //   if(!empty($info_arr['type'])){ continue; }
   $sel="";
   if(!empty($meta['account']) && $meta['account'] == $v['id']){
       $sel='selected="selected"';
   }
  echo '<option value="'.$v['id'].'" '.$sel.'>'.$v['name'].'</option>';     
  }   
?>
</select></td>
</tr>
</table>
<div id="vx_sync_load" style="display: none; text-align: center; padding-top: 20px;"><i class="fa fa-circle-o-notch fa-spin"></i> Loading ...</div>
<div id="vx_sync_settings"><?php $this->cron_settings($meta); ?></div>
<script type="text/javascript">
jQuery(document).ready(function($){
var vx_crm_ajax='<?php echo wp_create_nonce("vx_crm_ajax") ?>';
 
  
$('#vxc_account').change(function(){
       var load=$('#vx_sync_load');
       var div=$('#vx_sync_settings');
       div.hide();
       load.show();
$.post(ajaxurl,{action:'vxc_zoho_get_sync_settings',vx_crm_ajax:vx_crm_ajax,account:$(this).val()},function(res){ div.html(res); div.show(); load.hide(); add_sel2();    });
   });

$(document).on("change",".vx_toggles",function(e){
var id=$(this).attr('id');
var div=$('#'+id+'_div');
if($(this).is(':checked')){
div.show();    
}else{ div.hide(); }
});
$(document).on("click",".vx_refresh_data",function(e){
  e.preventDefault();  
  var btn=$(this);
  var action=$(this).data('id');
  var account=$("#vxc_account").val();
  button_state_vx("ajax",btn);
  $.post(ajaxurl,{action:'refresh_data_<?php echo esc_attr($this->id) ?>',vx_crm_ajax:vx_crm_ajax,vx_action:action,account:account},function(res){
  var re=$.parseJSON(res);
  button_state_vx("ok",btn);  
  if(re.status){
 if(re.status == "ok"){
  $.each(re.data,function(k,v){
   if($("#"+k).length){
   $("#"+k).html(v);    
   }else if($("."+k).length){
   $("."+k).html(v);    
   }   
  })   
 }else{
  if(re.error && re.error!=""){
      alert(re.error);
  }   
 }
  }   

  });   
});
   add_sel2();  
function add_sel2(){
//jQuery('.vx_item_fields').select2({ placeholder: '<?php esc_html_e('Select Item Field','woocommerce-zoho-crm') ?>'});
//jQuery('#crm_sel_attr').select2({ placeholder: '<?php esc_html_e('Select Attributes','woocommerce-zoho-crm') ?>'});
//jQuery('#crm_sel_book').select2({ placeholder: '<?php esc_html_e('Select Price Book','woocommerce-zoho-crm') ?>'});
jQuery('#crm_sel_cats').select2({ placeholder: '<?php esc_html_e('Select categories','woocommerce-zoho-crm') ?>'});

$('.vxc_date').datetimepicker({
timeFormat: 'HH:mm:ss',
dateFormat: 'dd-M-yy'
});

}
function button_state_vx(state,button){
var ok=button.find('.reg_ok');
var proc=button.find('.reg_proc');
     if(state == "ajax"){
          button.attr({'disabled':'disabled'});
ok.hide();
proc.show();
     }else{
         button.removeAttr('disabled');
   ok.show();
proc.hide();      
     }
}
 var sel=$('#crm_sel_attr');
  if(!sel.find('option').length){
  sel.parents('.vx_tr').find('.vx_refresh_data').trigger('click');
  }   
  var sel=$('#crm_sel_var');
  if(!sel.find('option').length){
  sel.parents('.vx_tr').find('.vx_refresh_data').trigger('click');
  } 
})
</script>
<?php
if(empty($meta['account'])){ return true; }
$next_item = wp_next_scheduled( 'vxc_zoho_item_cron' );

 $offset = get_option('gmt_offset');
  $offset=$offset*3600;
  $item_status='Disabled';
  $stock_status='Disabled';
// if(!empty($cron['next'])){
  ?>
  <div class="updated below-h2"><h4><?php esc_html_e('Products Sync CRON','woocommerce-zoho-crm'); ?></h4>
  <?php
      if(!empty($cron['last'])){
  ?>
  <p><?php echo $cron['last']['label'].' @ '.date('d-M-Y H:i:s',$cron['last']['time']); //$cron['last']['time']+$offset ?></p>
  <?php
      }
  if(!empty($next_item) && !empty($meta['item_cron'])){
   //   $next_item+=(int)$meta['item_cron'];
  ?>
  <p>Next Check @ <?php echo date('d-M-Y H:i:s',$offset+$next_item).' and current time is '.date('d-M-Y H:i:s',$time); ?></p>
<?php
$item_status='Active';
  }
  /*else if(!empty($cron['next'])){
   $item_status='Stopped , try re-saving cron settings';   
  }*/
  ?>
  <p><b>Cron Status: <?php echo $item_status ?></b></p>
  <?php
      if(!empty($cron['item']) && !empty($cron['item']['logs']) ){ ?>  
      <p><?php echo $cron['item']['label'].' @ '.date('d-M-Y H:i:s',$cron['item']['time']); ?></p>
      <p><?php echo implode('<hr>',$cron['item']['logs']); ?></p>
      <?php }
  ?>
  </div>
  <?php   
 //} 
   
    }
    
return $page_added;
}
public function get_settings_ajax(){
check_ajax_referer("vx_crm_ajax","vx_crm_ajax"); 
  if(!current_user_can('vxc_zoho_edit_settings')){ 
   die('-1');  
 }
 $account=!empty($_REQUEST['account']) ? (int)$_REQUEST['account'] : '';
 
 $meta=get_option('vxcf_zoho_cron',array());
 $meta['account']=$account;
 $this->cron_settings($meta);
 die();
}

public function cron_settings($meta){

if(empty($meta['account'])){ return ''; }

$after='';
if(!empty($meta['after'])){
$offset=get_option('gmt_offset');
$after=date('d-M-Y H:i:s',$meta['after']+($offset*3600)); 
}

$after_order='';
if(!empty($meta['after_order'])){
$offset=get_option('gmt_offset');
$after_order=date('d-M-Y H:i:s',$meta['after_order']+($offset*3600)); 
}
$info=$this->get_info($meta['account']);
$type=isset($info['data']['type']) ? $info['data']['type'] : '';
$zoho_status=array('inventory'=>array('fulfilled','confirmed','void','overdue','closed'),'books'=>array('draft','open', 'invoiced','partially_invoiced','void','overdue','closed'),'invoicesxxx'=>array('sent', 'draft','overdue','paid','void','unpaid','partially_paid','viewed','closed'),''=>array('Created','Approved','Delivered','Cancelled','closed'));
//unset($meta['zoho']);
$zoho_statuses=array(); 
if(isset($zoho_status[$type])){
 $zoho_statuses=$zoho_status[$type];  
}
if(empty($meta['zoho']) ){
  $meta['zoho']=$zoho_statuses;  
}

?>
<table class="form-table">
<tr>
<th><label for="vx_product"><?php esc_html_e('Sync Products','woocommerce-zoho-crm');  ?></label></th>
<td><label for="vx_product"><input type="checkbox" id="vx_product" name="crm[sync_items]" value="yes" <?php if(!empty($meta['sync_items'])){echo 'checked="checked"';} ?> > <?php esc_html_e('Add Zoho Products to WooCommerce','woocommerce-zoho-crm');  ?></label></td>
</tr>

<tr>
<th><label for="vx_inventory"><?php esc_html_e('Sync Inventory','woocommerce-zoho-crm');  ?></label></th>
<td><label for="vx_inventory"><input type="checkbox" id="vx_inventory" name="crm[sync_stock]" value="yes" <?php if(!empty($meta['sync_stock'])){echo 'checked="checked"';} ?> > <?php esc_html_e('Update Qty on Hand from Zoho to WooCommerce','woocommerce-zoho-crm');  ?></label></td>
</tr>
<tr>
<th><label for="vx_price"><?php esc_html_e('Sync Price','woocommerce-zoho-crm');  ?></label></th>
<td><label for="vx_price"><input type="checkbox" id="vx_price" name="crm[sync_price]" value="yes" <?php if(!empty($meta['sync_price'])){echo 'checked="checked"';} ?> > <?php esc_html_e('Update Price from Zoho to WooCommerce','woocommerce-zoho-crm');  ?></label></td>
</tr>

<tr>
<th><label for="vx_title"><?php esc_html_e('Sync Product Title','woocommerce-zoho-crm');  ?></label></th>
<td><label for="vx_title"><input type="checkbox" id="vx_title" name="crm[sync_title]" value="yes" <?php if(!empty($meta['sync_title'])){echo 'checked="checked"';} ?> > <?php esc_html_e('Sync Product title and Short Description from Zoho to WooCommerce if product already exists in WooCommerce.','woocommerce-zoho-crm');  ?></label></td>
</tr>

<tr>
<th><label for="vx_item_cron"><?php esc_html_e('Sync Time','woocommerce-zoho-crm'); ?></label></th>
<td>  <select id="vx_item_cron" name="crm[item_cron]" style="width: 100%">
  <?php
  $cron=!empty($meta['item_cron']) ? $meta['item_cron'] : '3600';
  $cache=array("60"=>"One Minute (for testing only)","300"=>"5 Minutes","900"=>"15 Minutes","1800"=>"30 Minutes","3600"=>"One Hour","43200"=>"12 Hours","86400"=>"One Day","604800"=>"7 Days","18144000"=>"1 Month");

  foreach($cache as $secs=>$label){
   $sel="";
   if($cron == $secs){
       $sel='selected="selected"';
   }
  echo "<option value='$secs' $sel>$label</option>";     
  }   
  ?>
  </select></td>
</tr>

<tr>
<th><label><?php esc_html_e('Sync Products Updated after','woocommerce-zoho-crm'); ?></label></th>
<td>
<input type="text" name="crm[after]" class="vxc_date" value="<?php echo $after; ?>" autocomplete="off" style="width: 99%">
 <div class="howto"><?php esc_html_e('Leave empty to start from 0 ','woocommerce-zoho-crm') ?></div> 

  </td>
 </tr>

 <tr>
<th><label for="vx_item_field"><?php esc_html_e('Zoho Stock field','woocommerce-zoho-crm'); ?></label></th>
<td>  <select id="vx_item_field" name="crm[item_qty_field]" style="width: 100%">
  <?php
  $cron=!empty($meta['item_qty_field']) ? $meta['item_qty_field'] : '';
  $cache=array(""=>"Use default Zoho field for Stock Qty","available_stock"=>"available_stock (Accounting Stock)","actual_available_stock"=>"actual_available_stock (Physical Stock)","actual_available_for_sale_stock"=>"actual_available_for_sale_stock (Physical Stock)");

  foreach($cache as $secs=>$label){
   $sel="";
   if($cron == $secs){
       $sel='selected="selected"';
   }
  echo "<option value='$secs' $sel>$label</option>";     
  }   
  ?>
  </select></td>
</tr>

<tr>
<th><label><?php esc_html_e('Allow BackOrders','woocommerce-zoho-crm'); ?></label></th>
<td>

<select  name="crm[back_order]" style="width: 99%">
  <?php
  $opst=array('no'=>'Do not allow','notify'=>'Allow , but notify customer','yes'=>'Yes, Allow');

  foreach($opst as $k=>$v){
   $sel="";
   if(isset($meta['back_order']) && $meta['back_order'] == $k){
       $sel='selected="selected"';
   }
  echo "<option value='$k' $sel>$v</option>";     
  }   
  ?>
  </select>
  </td>
  </tr>
 <tr>
<th><label><?php esc_html_e('Sync if category is','woocommerce-zoho-crm'); ?></label></th>
<td>
  <div class="vx_tr" >
  <div class="vx_td">
<select class="vx_sel2" id="crm_sel_cats" name="crm[item_cats][]" multiple="multiple" style="width: 99%">
  <?php
  if(!empty($info['meta']['item_cats'])){
  foreach($info['meta']['item_cats'] as $k=>$v){
   $sel=""; 
   if(isset($meta['item_cats']) && is_array($meta['item_cats']) &&  in_array($k,$meta['item_cats'])){
       $sel='selected="selected"';
   }
  echo "<option value='$k' $sel>$v</option>";     
  }   }
  ?>
  </select>
 <div class="howto"><?php esc_html_e('Leave empty to sync items from all categories','woocommerce-zoho-crm') ?></div> 
  </div>
  <div class="vx_td2">
  <button class="button vx_refresh_data" data-id="refresh_cats">
  <span class="reg_ok"><i class="fa fa-refresh"></i> <?php esc_html_e('Refresh','woocommerce-zoho-crm') ?></span>
  <span class="reg_proc" style="display: none;"><i class="fa fa-circle-o-notch fa-spin"></i> <?php esc_html_e('Loading ...','woocommerce-zoho-crm') ?></span>
  </button>
  
  </div>
  </div>

  </td>
 </tr>
  <tr>
<th><label for="vx_stock"><?php esc_html_e('If out of stock in Zoho','woocommerce-zoho-crm');  ?></label></th>
<td><select id="vx_stock" name="crm[stock]" style="width: 100%">
  <?php
  $stock=!empty($meta['stock']) ? $meta['stock'] : '';
  $cache=array(""=>'Update Quantity to zero','draft'=>'Hide that Item in WooCommerce','trash'=>'Delete that item from WooCommrece');
  foreach($cache as $secs=>$label){
   $sel="";
   if($stock == $secs){
       $sel='selected="selected"';
   }
  echo "<option value='$secs' $sel>$label</option>";     
  }   
  ?>
  </select></td>
</tr>
<?php
if(!empty($info['data']['type']) && in_array($info['data']['type'],array('inventory'))){
$meta['warehouses']=$this->get_warehouses($info);        
?>
  <tr>
<th><label for="vx_warehouse"><?php esc_html_e('Select Warehouse for Stock Qty','woocommerce-zoho-crm');  ?></label></th>
<td><select id="vx_warehouse" name="crm[warehouse]" style="width: 100%">
<?php
if(!empty($meta['warehouses'])){
 $wares=array(''=>__('All Warehouses','woocommerce-zoho-crm'));
  if(is_array($meta['warehouses'])){
    $wares+=$meta['warehouses'];  
  }  
  foreach($wares as $secs=>$label){
   $sel="";
   if(!empty($meta['warehouse']) && $meta['warehouse'] == $secs){
       $sel='selected="selected"';
   }
  echo "<option value='$secs' $sel>$label</option>";     
  }   
}
?>
  </select></td>
</tr>
<?php
}
?>
</table>
<?php
if(!empty($zoho_statuses)){
?>
<h3><?php esc_html_e('Sync Zoho Orders Status (Optional)','woocommerce-zoho-crm');  ?></h3>
<table class="form-table">
<tr>
<th><label for="vx_orders"><?php esc_html_e('Sync Order Status','woocommerce-zoho-crm');  ?></label></th>
<td><label for="vx_orders"><input type="checkbox" id="vx_orders" class="vx_toggles" name="crm[sync_order]" value="yes" <?php if(!empty($meta['sync_order'])){echo 'checked="checked"';} ?> > <?php esc_html_e('Synchronize Order Status from Zoho to WooCommerce.','woocommerce-zoho-crm');  ?></label></td>
</tr>
</table>
<div id="vx_orders_div" style="<?php if(empty($meta['sync_order'])){echo 'display:none';} ?>">
<table class="form-table">
<tr>
<th><label><?php esc_html_e('Sync Orders Updated after','woocommerce-zoho-crm'); ?></label></th>
<td>
<input type="text" name="crm[after_order]" class="vxc_date" value="<?php echo $after_order; ?>" autocomplete="off" style="width: 99%">
 <div class="howto"><?php esc_html_e('Leave empty to start from 0 ','woocommerce-zoho-crm') ?></div> 

  </td>
 </tr>

</table>

<h3><?php esc_html_e('Map Zoho Order Status to WooCommerce Order Status','woocommerce-zoho-crm');  ?></h3>
<table class="form-table">
<tr>
<th>
<label><?php esc_html_e('Zoho Order Status','woocommerce-zoho-crm'); ?></label>
<div class="howto">e.g: <?php echo implode(', ',$zoho_statuses); ?></div>
</th>
<th><label><?php esc_html_e('WooCommerce Order Status','woocommerce-zoho-crm'); ?></label></th>
</tr>
<?php
$status_list=wc_get_order_statuses();

for($i=0; $i<5; $i++){
?>
<tr>
<th><input type="text" name="crm[zoho][<?php echo $i ?>]" value="<?php if(isset($meta['zoho'][$i])){ echo $meta['zoho'][$i]; }  ?>" /></th>
<td>
<select  name="crm[woo][<?php echo $i ?>]" style="width: 99%">
<?php
foreach($status_list as $k=>$v){
   $sel="";
   if(isset($meta['woo'][$i]) && $meta['woo'][$i] == $k){
       $sel='selected="selected"';
   }
  echo "<option value='$k' $sel>$v</option>";     
}   
  ?>
  </select>
  </td>
  </tr>
<?php
}
?>  
</table>


</div>
<?php
}
?>
<p class="submit_vx">
  <button type="submit" value="save" class="button-primary" title="<?php esc_html_e('Save Changes','woocommerce-zoho-crm'); ?>" name="save"><?php esc_html_e('Save Changes','woocommerce-zoho-crm'); ?></button>
</p>    
    <?php

}


protected function upload_image_wp( $name , $content ) {
    $upload=array();
    if(!empty($name) && !empty($content) ){
       $upload = wp_upload_bits( $name, '', $content ); 
    }
 return $upload;   
}
protected function upload_image_from_url( $image_url ) {
        $file_name = basename( current( explode( '?', $image_url ) ) );
        $parsed_url = @parse_url( $image_url );

        // Check parsed URL.
if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
return 'invalid url';
}

        // Ensure url is valid.
        $image_url = str_replace( ' ', '%20', $image_url );
        // Get the file.
        $response = wp_safe_remote_get( $image_url, array(
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
return 'error while dowloading image';
        } 

            $headers = wp_remote_retrieve_headers( $response );
if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
                $disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
                $disposition = sanitize_file_name( $disposition );
                $file_name   = $disposition;
            } elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
                $file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
            }
            unset( $headers );

        // Upload the file.
        $upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

        if ( $upload['error'] ) {
return 'error while saving image';
}

        // Get filesize.
        $filesize = filesize( $upload['file'] );

        if ( 0 == $filesize ) {
            @unlink( $upload['file'] );
            unset( $upload );
return;
        }

        unset( $response );

        return $upload;
    }
public function cron_schedules($schedules){
    
    
    if(!isset($schedules['every_minute']))
    {
        $schedules['every_minute'] = array(
        'display' => __( 'Every Minute', 'woocommerce-zoho-crm' ),
        'interval' => 60,
        );
    }
      if(!isset($schedules['5min']))
    {
        $schedules['5min'] = array(
            'display' => __( 'Every 5 Minutes', 'woocommerce-zoho-crm' ),
            'interval' => 300,
        );
    }
     
    if(!isset($schedules['15min']))
    {
        $schedules['15min'] = array(
        'display' => __( 'Every 15 Minutes', 'woocommerce-zoho-crm' ),
        'interval' => 900,
        );
    }
    if(!isset($schedules['30min']))
    {
        $schedules['30min'] = array(
        'display' => __( 'Every 30 Minutes', 'woocommerce-zoho-crm' ),
        'interval' => 1800,
        );
    }
    /*
    if(!isset($schedules['hourly']))
    {
        $schedules['hourly'] = array(
        'display' => __( 'Hourly', 'woocommerce-zoho-crm' ),
        'interval' => 3600,
        );
    }
    if(!isset($schedules['twicedaily']))
    {
        $schedules['twicedaily'] = array(
        'display' => __( 'Twice Daily', 'woocommerce-zoho-crm' ),
        'interval' => 43200,
        );
    }
    if(!isset($schedules['daily']))
    {
        $schedules['daily'] = array(
        'display' => __( 'Daily', 'woocommerce-zoho-crm' ),
        'interval' => 86400,
        );
    }
    if(!isset($schedules['weekly']))
    {
        $schedules['weekly'] = array(
        'display' => __( 'Weekly', 'woocommerce-zoho-crm' ),
        'interval' => 604800,
        );
    }*/
    if(!isset($schedules['monthly']))
    {
        $schedules['monthly'] = array(
        'display' => __( 'Monthly', 'woocommerce-zoho-crm' ),
        'interval' => 2635200,
        );
    }
  return $schedules;  
}    
protected function set_uploaded_image_as_attachment( $upload, $id = 0 ) {
        $info    = wp_check_filetype( $upload['file'] );
        $title   = '';
        $content = '';
include_once( ABSPATH . 'wp-admin/includes/image.php' );

        if ( $image_meta = @wp_read_image_metadata( $upload['file'] ) ) {
            if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
                $title = wc_clean( $image_meta['title'] );
            }
            if ( trim( $image_meta['caption'] ) ) {
                $content = wc_clean( $image_meta['caption'] );
            }
        }

        $attachment = array(
            'post_mime_type' => $info['type'],
            'guid'           => $upload['url'],
            'post_parent'    => $id,
            'post_title'     => $title,
            'post_content'   => $content,
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
        if ( ! is_wp_error( $attachment_id ) ) {
            wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
        }

        return $attachment_id;
}
}
new vxcf_zoho_cron();
}
