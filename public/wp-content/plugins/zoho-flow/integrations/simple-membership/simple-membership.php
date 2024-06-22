<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if (!class_exists('SwpmMembershipLevel')) {
    require_once ABSPATH . 'wp-content/plugins/simple-membership/classes/class.swpm-membership-level.php';
}

class Zoho_Flow_Simple_Membership extends Zoho_Flow_Service
{
    private static $tables = array(
        'members' => 'swpm_members_tbl',
        'membership' => 'swpm_membership_tbl',
        'getmembership' => 'swpm_membership_tbl',
    );
    
    public static function gettable($key){
        return self::$tables[$key];
    }
    
    /**
     * get_members - List all members of SWPM
     * @param unknown $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_members($request){
        return rest_ensure_response($this->Fetch_Query_Details('members', null));
    }
    
    /**
     * get_membership_levels - List all the membershiplevels
     * @param unknown $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_membership_levels($request) {
        $data = array();
        $result = $this->Fetch_Query_Details('membership', null)->data;
        foreach ($result as $member) {
            $valid_for = $this->column_default($member, 'valid_for');
            $member['valid_for'] = $valid_for;
            array_push($data, $member);
        }
        return rest_ensure_response($data);
    }
    
    public function create_membership($request){
        global $wpdb;
        $data = array();
        
        if(empty($request['alias']) || empty($request['role'])){
            $msg = "";
            if(empty($request['alias'])) {
                $msg= "Alias is required.";
            }
            if(empty($request['role'])) {
                $msg = "Role is required.";
            }
            return new WP_Error( 'rest_bad_request', esc_html__( $msg, 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        $level_info = $request->get_params();
        $wpdb->insert($wpdb->prefix . "swpm_membership_tbl", $level_info);
        $membership_id = $wpdb->insert_id;
        
        if(is_wp_error($membership_id)){
            $errors = $membership_id->get_error_messages();
            $error_code = $membership_id->get_error_code();
            foreach ($errors as $error) {
                return new WP_Error( $error_code, esc_html__( $error, 'zoho-flow' ), array('status' => 400) );
            }
        }
        $response = $this->Fetch_Query_Details('getmembership', array('id'=>$membership_id));
        $valid_for = $this->column_default($request, 'valid_for');
        $data = (array) $response->data[0];
        $data['valid_for'] = $valid_for;
        
        $this->trigger_webhook($data, 'membership');
        return rest_ensure_response($data);
    }
    
    public function update_membership($request) {
        global $wpdb;
        $data = array();
        
        $membership_id = $request['id'];
        
        if(!ctype_digit($membership_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Membership id is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        if(empty($request['alias']) || empty($request['role'])){
            $msg = "";
            if(empty($request['alias'])) {
                $msg= "Alias is required.";
            }
            if(empty($request['role'])) {
                $msg = "Role is required.";
            }
            return new WP_Error( 'rest_bad_request', esc_html__( $msg, 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE " . ' id=%d', $membership_id);
        $level = $wpdb->get_row($query, ARRAY_A);
        $level = (array) $level;
        
        if(empty($level)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Membership row does not exists.', 'zoho-flow' ), array( 'status' => 400 ) );
        } else {
            $level_info = $request->get_params();
            $result = $wpdb->update($wpdb->prefix . "swpm_membership_tbl", $level_info, array('id'=> $membership_id));
            
            $response = $this->Fetch_Query_Details('getmembership', $request);
            $valid_for = $this->column_default($request, 'valid_for');
            $data = (array) $response->data[0];
            $data['valid_for'] = $valid_for;
            if ( ! $result ) {
                if(is_wp_error($membership_id)){
                    //DB error occurred
                    error_log('db error occurred');
                    $errormsg = 'Update membership level - DB error occurred: ' . json_encode( $wpdb->last_result );
                    return new WP_Error('rest_bad_request', esc_html__($errormsg, 'zoho-flow'), array('status' => 400));
                }
            }
        }
        $this->trigger_webhook($data, 'membership');
        return rest_ensure_response($data);
    }
    
    public function create_member($request){
        global $wpdb;
        //First, check if email or username belongs to an existing admin user.
        SwpmMemberUtils::check_and_die_if_email_belongs_to_admin_user($request['email']);
        SwpmMemberUtils::check_and_die_if_username_belongs_to_admin_user($request['user_name']);
        
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE " . ' user_name=%s', $request['user_name']);
        $profile = $wpdb->get_row($query, ARRAY_A);
        $profile = (array) $profile;
        
        if (!empty($profile)) {
            return new WP_Error( 'rest_bad_request', esc_html__( 'The member already exists.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        $profile = $request->get_params();
        $profile['member_since'] = SwpmUtils::get_current_date_in_wp_zone();
        
        $member_id = SwpmMemberUtils::create_swpm_member_entry_from_array_data($profile);
        
        if(is_wp_error($member_id)){
            $errors = $member_id->get_error_messages();
            $error_code = $member_id->get_error_code();
            foreach ($errors as $error) {
                return new WP_Error( $error_code, esc_html__( $error, 'zoho-flow' ), array('status' => 400) );
            }
        }
        
        $data = $this->get_member(array('member_id'=>$member_id, 'login'=>null));
        $this->trigger_webhook($data, 'members');
        return rest_ensure_response($data);
    }
    
    public function update_member($request) {
        global $wpdb;
        $member_id = $request['member_id'];
        
        if(!ctype_digit($member_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Member ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE " . ' member_id=%d', $member_id);
        $profile = $wpdb->get_row($query, ARRAY_A);
        $profile = (array) $profile;
        
        if(empty($profile)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Member does not exists.', 'zoho-flow' ), array( 'status' => 400 ) );
        } else {
            $profile = $request->get_params();
            $result = $wpdb->update($wpdb->prefix . "swpm_members_tbl", $profile, array('member_id'=> $member_id));
            if ( ! $result ) {
                if(is_wp_error($member_id)){
                    //DB error occurred
                    error_log('db error occurred');
                    $errormsg = 'Update member - DB error occurred: ' . json_encode( $wpdb->last_result );
                    return new WP_Error('rest_bad_request', esc_html__($errormsg, 'zoho-flow'), array('status' => 400));
                }
            }
        }
        $data = $this->get_member(array('member_id'=>$member_id, 'login'=>null));
        $this->trigger_webhook($data, 'members');
        return rest_ensure_response($data);
    }
    
    /**
     * get_member - Get member using username/email/memberid
     * @param unknown $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_member($request) {
        return rest_ensure_response($this->Fetch_Query_Details('getmember' , $request));
    }
    
    /**
     * Fetch_Query_Details is the function to execute the query for given modules.
     * @param string  $action Module that related to query. Choose the table based on the param.
     * @return WP_REST_Response|WP_Error
     */
    private function Fetch_Query_Details( $action, $request){
        global $wpdb;
        
        if($action==='getmember'){
            $table = $this->gettable('members');
            $login = esc_attr($request['login']);
            if(isset($login) && filter_var($request['login'], FILTER_VALIDATE_EMAIL)){
                $query = "SELECT * FROM " . $wpdb->prefix . $table . " WHERE  email = '". $login ."'";
            } else if(isset($request['member_id'])){
                $query = "SELECT * FROM " . $wpdb->prefix . $table . " WHERE  member_id = ". $request['member_id'];
            }else if(isset($request['login'])){
                $query = "SELECT * FROM " . $wpdb->prefix . $table . " WHERE  user_name = '". $login ."'";
            }
        }else {
            $table = $this->gettable($action);
            if($action === 'getmembership'){
                $query = "SELECT * FROM " . $wpdb->prefix . $table . " WHERE id=". $request['id'];
            } else {
                $query = "SELECT * FROM " . $wpdb->prefix . $table;
            }
        }
        $totalitems = $wpdb->query($query);
        if($totalitems > 0){
            $members = $wpdb->get_results($query, ARRAY_A);
            return rest_ensure_response($members);
        }else {
            return rest_ensure_response(array());
        }
    }
    
    public function get_webhooks($request){
        $data = array();
        $args = array(
            'type' => $request['type']
        );
        $webhooks = $this->get_webhook_posts($args);
        foreach ( $webhooks as $webhook ) {
            $webhook = array(
                'plugin_service' => $this->get_service_name(),
                'id' => $webhook->ID,
                'type' => $request['type'],
                'url' => $webhook->url
            );
            array_push($data, $webhook);
        }
        
        return rest_ensure_response( $data );
    }
    
    public function create_webhook($request){
        $post_title = $request['type'];
        $url = esc_url_raw($request['url']);
        $post_id = $this->create_webhook_post($post_title, array(
            'type' => $request['type'],
            'url' => $url
        ));
        
        return rest_ensure_response( array(
            'plugin_service' => $this->get_service_name(),
            'id' => $post_id,
            'type' =>$request['type'],
            'url' => $url
        ) );
    }
    
    public function delete_webhook($request) {
        $webhook_id = $request['webhook_id'];
        if(!ctype_digit($webhook_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The post ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $result = $this->delete_webhook_post($webhook_id);
        if(is_wp_error($result)){
            return $result;
        }
        return rest_ensure_response(array(
            'plugin_service' => $this->get_service_name(),
            'id' => $result->ID
        ));
        return rest_ensure_response($result);
    }
    
    public function update_membership_level_of_member($request) {
        $member_id = $request['member_id'];
        $membership_lvl = $request['membership_level'];
        if(!ctype_digit($member_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Member ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        if(!ctype_digit($membership_lvl)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Membership level is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        SwpmMemberUtils::update_membership_level($member_id, $request['membership_level_id']);
        return rest_ensure_response($this->get_member(array('member_id'=>$member_id, 'login'=>null)));
    }
    
    public function trigger_webhook($data, $type){
        $args = array(
            'type'    =>  $type,
        );
        $webhooks = $this->get_webhook_posts($args);
        foreach ( $webhooks as $webhook ) {
            $url = $webhook->url;
            zoho_flow_execute_webhook($url, $data, array());
        }
    }

    //Hooks
    public function process_swpm_registration_user_data($member_info) {
        $args = array(
            'type'=>"members",
        );

        $user = SwpmMemberUtils::get_user_by_email($member_info['email']);
        $member_info['member_id'] = $user->member_id;
        $webhooks = $this->get_webhook_posts($args);
        foreach ( $webhooks as $webhook ) {
            $url = $webhook->url;
            $member_info['type']=$webhook->type;
            zoho_flow_execute_webhook($url, $member_info,array());
        }
    }
    
    /**
     * column_default - Used to update membership level validity.
     * @param array $item
     * @param string $column_name
     * @return string unknown
     */
    function column_default($item, $column_name) {
        if ($column_name == 'valid_for') {
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::NO_EXPIRY) {
                return 'No Expiry';
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::FIXED_DATE) {
                $formatted_date = SwpmUtils::get_formatted_date_according_to_wp_settings($item['subscription_period']);
                return $formatted_date;
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::DAYS) {
                return $item['subscription_period'] . " Day(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::WEEKS) {
                return $item['subscription_period'] . " Week(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::MONTHS) {
                return $item['subscription_period'] . " Month(s)";
            }
            if ($item['subscription_duration_type'] == SwpmMembershipLevel::YEARS) {
                return $item['subscription_period'] . " Year(s)";
            }
        }
        return stripslashes($item[$column_name]);
    }
}
