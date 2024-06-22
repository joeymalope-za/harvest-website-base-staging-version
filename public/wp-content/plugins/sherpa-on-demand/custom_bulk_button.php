<?php
function handle_addCustomWooSherpaButton( $redirect, $doaction, $object_ids){
  if ( 'sherpa_edit' === $doaction ) {
    $orders_in_queue = [];
    foreach($object_ids as $order_id){

      //Get the order object
      $order = wc_get_order($order_id);

      //Get the order data
      $order_data = $order->get_data();

      $conf = new Sherpa_Configurations();

      $delivery_time_plain_text = get_post_meta($order_id, '_sherpa_delivery_time_plain_text', true);
      if(empty($delivery_time_plain_text)){
        $delivery_time_plain_text = '12:15 pm - 2:15 pm';
      }

      $sherpa_origin = get_option('woocommerce_sherpa_settings');
      $pickup_address = $sherpa_origin['origin_address'];
      

      $param = array(
        'item_description'=> $conf->getItemDescription(),
        'pickup_address'=> $pickup_address,
        );
        
        update_post_meta ($order_id, 'set_params' ,$param);	
        
        //Create new sherpa post if doesn't exist
        if(!(check_sherpa_post_exists_for_wc_order($order_id))){

          update_post_meta ($order_id, 'check_post' ,true);
          $original_post = get_post(get_the_ID());
          $new_post = array(
            'post_type' => 'send_to_sherpa',
            'post_status' => 'publish',
            'post_password' => '',
            // 'post_title' => $original_post->ID,
            'post_parent' => $order_id,
          );
            wp_insert_post($new_post);
            wp_redirect(admin_url('/edit.php?post_type=send_to_sherpa'));
            exit;
        }else{
          $message = '';
          array_push($orders_in_queue, $order_id);

          $message = (count($orders_in_queue) > 1) ? 'The orders ' : 'The order ';
          $message .= (count($orders_in_queue) > 1) ? ' are ' : ' is ';
          $message .= "already in the sherpa delivery queue.";
          
          set_transient( 'sherpa_order_notice', $message, 3600);
          wp_redirect( admin_url( '/edit.php?post_type=send_to_sherpa' ) );

        }
        
    }die();
  } 
  return $redirect;
}
?>