<?php
if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Orders' ) ) {
    class Orders{

        /**
        * Returns product flower weight and thc_content
        */
        public function get_checkout_items() {
            if (function_exists('WC')) {
                $cart = WC()->cart;
                if (null !== $cart) {
                    $cart_items = $cart->get_cart(); // Get cart items
        
                    $response_data = array();
        
                    foreach ($cart_items as $cart_item_key => $cart_item) {
                        $product = $cart_item['data'];
                        if (!$product) {
                            continue;
                        }
        
                        
                        // Check if product is flower and has 'wwbp_weight_qty' set.
                        if (isset($cart_item['wwbp_weight_qty'])) {
                            $checkout_data = array(
                                'created_at' => self::get_checkout_time(),
                                'weight_qty' => $cart_item['wwbp_weight_qty'],
                                // Use $product->get_id() to ensure compatibility with variations.
                                'thc_content' => get_post_meta($product->get_id(), 'thc_content', true)
                            );
        
                            array_push($response_data, $checkout_data); // Add checkout item to the array
                        }
                    }
        
                    return $response_data;
                }
            }
            return array(); // Return an empty array if WC()->cart is not initialized.
        }

        /**
        * Returns checkout time
        * Use Unix timestamp
        */
        public static function get_checkout_time(){
            date_default_timezone_set('Australia/Sydney');

            return $current_date = time();
        }

        /**
        * Set user's prescription summary by thc_content
        */
        public function set_prescription_history(){

            $user_id = get_current_user_id();
            $active_prescription = get_user_meta($user_id, 'active_prescription', true) ?: array();
            $prescription_usage = get_user_meta($user_id, 'prescription_usage', true) ?: array();

            $results_data = array();

            foreach($active_prescription as $id => $item){

                $prescription_thc_content = $active_prescription[$id]['thc_content'];
                $dosage = $active_prescription[$id]['dosage'];
                $start_timestamp = $active_prescription[$id]['created_at']; // Prescription start date
                $end_timestamp = $active_prescription[$id]['expiration_date']; // Prescription expiry date
                $duration = $active_prescription[$id]['duration']; // duration of the prescription

                $max_dosage[$prescription_thc_content] = $dosage;

                $interval_size = ($end_timestamp - $start_timestamp) / $duration; // Interval range between month's duration
                $usage_results = array_fill(0, $duration, array()); // Initialize an array to store the sum of weight_qty and remaining dosage based on thc_content

                // Initialize arrays to store remaining dosage for each thc_content
                $remaining_dosage = $max_dosage;

                foreach ($prescription_usage as $order) {
                    $created_at = $order['created_at'];
                    $weight_qty = floatval($order['weight_qty']);
                    $thc_content = $order['thc_content'];

                    // Show only specific thc_content
                    if($prescription_thc_content === $thc_content){
                    
                        // Returns the month order (ex. order belongs to the first month represent in array[0], if second month array[1])
                        $interval_number = min($duration, floor(($created_at - $start_timestamp) / $interval_size) ); 

                        // Check if orders are within the range of the prescription duration
                        // If outside the prescription duration, order is skip
                        // -1 to fix array start key from 0
                        if($interval_number <= ($duration-1)){ 
                            
                            // Update remaining dosage
                            $remaining_dosage[$thc_content] -= $weight_qty;
                            
                            // Increment the sum for thc_content if not already set
                            if (!isset($usage_results[$interval_number])) {
                                $usage_results[$interval_number] = array(
                                    'ordered_qty' => 0,
                                    'remaining_qty' => $max_dosage[$thc_content] // Initialize remaining quantity
                                );
                            }
                            
                            // Increment the sum of weight_qty for the corresponding thc_content
                            $usage_results[$interval_number]['ordered_qty'] += $weight_qty;

                            // Update remaining quantity
                            $usage_results[$interval_number]['remaining_qty'] = $remaining_dosage[$thc_content];
                        }
                    }
                }

                $results_data[$id][$prescription_thc_content] = $usage_results;
            }

            // Update user meta
            update_user_meta($user_id, 'prescription_summary', $results_data);
        }

        /**
        * Return users filtered prescription by thc_content
        */
        public function get_prescription_item($target_thc_content){
            $this->target_thc = $target_thc_content;

            $user_id = get_current_user_id();
            $active_prescription = get_user_meta($user_id, 'active_prescription', true) ?: array();

            // Filter the dosage data by THC content
            $filtered_active_prescription = array_filter($active_prescription, function($item) use ($target_thc_content) {
                return $item['thc_content'] === $target_thc_content;
            });

            return $filtered_active_prescription;
        }

        /**
        * Check user prescription expiration by timestamp
        */
        public function is_prescription_expired($expiration_timestamp){
            $this->expiration_timestamp = $expiration_timestamp;

            // Get current timestamp
            $today = self::get_checkout_time();

            // Compare expiration date with current date
            if ($today > $expiration_timestamp) {
                return true; // expired
            } else {
                return false; // not expired
            }
        }

        /**
        * Update users summary remaining dosage
        * Include previous month dosage to the next month remaining dosage
        */
        public function set_new_remaining_dosage(){
            $user_id = get_current_user_id();
            $active_prescription = get_user_meta($user_id, 'active_prescription', true) ?: "";
            $prescription_summary = get_user_meta($user_id, 'prescription_summary', true) ?: "";
            
            // Get filtered active_prescription data
            $prescription_data = array();

            foreach($active_prescription as $prescription_item){
                $thc = $prescription_item['thc_content'];

                $prescription_data[$thc]['dosage']=$prescription_item['dosage'];
                $prescription_data[$thc]['duration']=$prescription_item['duration'];
            }

            // Get filtered prescription_summary data
            $results_data = array();
            foreach($prescription_summary as $prescription_items){
                array_push($results_data, $prescription_items);
            }
            
            $result_data = array(); // This will holds the new prescription_summary
            
            foreach ($results_data as $index => $summary_items) {

                foreach ($summary_items as $dosage_key => $summary_item) {

                    $duration = $prescription_data[$dosage_key]['duration'];
                    $dosage = $prescription_data[$dosage_key]['dosage'];

                    // For blank array make sure that it's using the dosage limit
                    $temp_data = array();

                    for ($l = 0; $l < $duration; $l++) {

                        if($summary_item[$l]['remaining_qty']=== 0.0){
                            $value = 0;
                        }
                        else{
                            $value = $summary_item[$l]['remaining_qty'] ?: $dosage;
                        }

                        array_push($temp_data, $value);
                    }

                    // Logic to sum up previous month qty to next month qty
                    for ($i = 0; $i < count($temp_data); $i++) {
                        $sum = 0;

                        // Sum elements from index 0 to $i
                        for ($j = 0; $j <= $i; $j++) {
                            $sum += $temp_data[$j];
                        }

                        // Store the sum in the answer array
                        $result_data[$index][$dosage_key][$i]['ordered_qty'] = $summary_item[$i]['ordered_qty'] ?: 0;
                        $result_data[$index][$dosage_key][$i]['remaining_qty'] = $sum;
                    }
                }
            }

            update_user_meta($user_id, 'prescription_summary', $result_data);
        }
    }
}

