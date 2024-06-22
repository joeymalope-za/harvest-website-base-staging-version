<?php

function register_api_routes()
{
    register_rest_route('harvest-api', '/id-manual-review', [
        'methods' => 'POST',
        'callback' => 'handle_ID_manual_review',
        'permission_callback' => 'harvest_api_auth_callback'
    ]);
    register_rest_route('harvest-api', '/virtual-doctor-results', [
        'methods' => 'POST',
        'callback' => 'handle_virtual_doctor_results',
        'permission_callback' => 'harvest_api_auth_callback'
    ]);
    register_rest_route('harvest-api', '/queue_wait', [
        'methods' => 'POST',
        'callback' => 'handle_queue_wait',
        'permission_callback' => 'harvest_api_auth_callback'
    ]);
    register_rest_route('harvest-api', '/callback_requested', [
        'methods' => 'POST',
        'callback' => 'handle_callback_requested',
        'permission_callback' => 'harvest_api_auth_callback'
    ]);
    register_rest_route('harvest-api', '/call_finished', [
        'methods' => 'POST',
        'callback' => 'call_finished',
        'permission_callback' => function () {
            return true;
        }
    ]);
    register_rest_route('harvest-api', '/link_df_session', [
        'methods' => 'POST',
        'callback' => 'link_df_session',
        'permission_callback' => function () {
            return true;
        }
    ]);
    register_rest_route('harvest-api', '/set_user_timezone', [
        'methods' => 'POST',
        'callback' => 'set_user_timezone',
        'permission_callback' => function () {
            return true;
        }
    ]);
    register_rest_route('harvest-api', '/get_approval_status', [
        'methods' => 'POST',
        'callback' => 'get_approval_status',
        'permission_callback' => function () {
            return true;
        }
    ]);
    register_rest_route('harvest-api', '/update_membership', [
        'methods' => 'POST',
        'callback' => 'update_membership',
        'permission_callback' => 'harvest_api_auth_callback'
    ]);
}

// install REST API
if (function_exists('add_action')) {
    add_action('rest_api_init', 'register_api_routes');
}

function harvest_api_auth_callback(WP_REST_Request $request)
{
    $headers = $request->get_headers();
    if (isset($headers['authorization']) && $headers['authorization'][0]) {
        $authorization = $headers['authorization'][0];
        $auth_parts = explode(' ', $authorization);
        if (count($auth_parts) == 2 && $auth_parts[0] == 'Basic') {
            $auth_data = base64_decode($auth_parts[1]);
        } else {
            return new WP_Error('rest_forbidden', 'Invalid authorization header', ['status' => 401]);
        }
    } else {
        // try extracting auth token from auth_token URL parameter
        $auth_token = $request->get_param('auth_token');
        if (!$auth_token) {
            return new WP_Error('rest_forbidden', 'No authorization token', ['status' => 401]);
        } else {
            $auth_data = base64_decode(urldecode($auth_token));
        }
    }
    list($username, $password) = explode(':', $auth_data, 2);
    if ($username != HARVEST_REST_USER || $password != HARVEST_REST_PASSWORD) {
        return new WP_Error('rest_forbidden', 'Invalid credentials', ['status' => 401]);
    }
    return true;
}
function handle_ID_manual_review(WP_REST_Request $request)
{
    $parameters = json_decode($request->get_body(), true);
    $uuid = $parameters["uuid"];

    $args = array(
        'meta_key' => 'uuid',
        'meta_value' => $uuid,
        'meta_compare' => '='
    );
    $users = get_users($args);
    if ($users) {
        $user = $users[0];
        debug_log("request manual review for user " . $user->ID. gmdate("Y-MM-ddTHH:mm:ss±HH:mm"). ' date from server:' . $parameters['updatedDate']);
        update_zoho_patient_fields($user->ID, [
            'status'=>'ID Manual Review',
            'id_url'=> $parameters['idImage'],
            'selfie_url' => $parameters['selfieImage'],
            'id_review_date'=> toISO8601("now")
        ]);


    }
}
function update_membership(WP_REST_Request $request)
{
    $parameters = json_decode($request->get_body(), true);
    $uuid = $parameters["harvest_uuid"];
    $is_approved = $parameters["prescription_approved"];
    $rejection_reason = $parameters["rejection_reason"];
    $prescription = $parameters["prescription"];

    // Add new values to prescriptions
    date_default_timezone_set('Australia/Sydney');

    foreach ($prescription as &$prescription_item) {
        if (is_array($prescription_item)) {
            // Add 'created_at' to each sub-array
            $prescription_item['created_at'] = time(); // Use Unix timestamp
            $prescription_item['expiration_date'] = time() + (6 * 30 * 24 * 60 * 60); // Add 6 months from created_at
            $prescription_item['duration'] = '6'; // Default 6 months
        }
    }

    unset($prescription_item); // Unset the reference to avoid potential issues

    $args = array(
        'meta_key' => 'uuid',
        'meta_value' => $uuid,
        'meta_compare' => '='
    );
    $users = get_users($args);
    if ($users) {
        $user = $users[0];
        update_user_meta($user->ID, 'prescription_approved', $is_approved);
        update_user_meta($user->ID, 'consultation_payment_pending', $is_approved);
        update_user_meta($user->ID, 'rejection_reason', $rejection_reason);
        update_user_meta($user->ID, 'active_prescription', $prescription);

        if ($is_approved) {
            make_member($user->ID);
            update_zoho_patient_fields($user->ID, [
                'status'=> 'Doctor Approved',
                'dr_pas_date' => toISO8601("now"),
                'doctor'=> $parameters['doctor'] ]);
        }else{
            if($rejection_reason == 'Doctor ID Failed'){
                update_zoho_patient_fields($user->ID, ['status'=> 'Doctor ID Fail',
                    'reason' => $rejection_reason,
                    'dr_id_fail_date' => toISO8601("now"),
                    'doctor'=> $parameters['doctor']
                ]);
            }else{
                update_zoho_patient_fields($user->ID, ['status'=> 'Doctor Fail',
                    'reason' => $rejection_reason,
                    'dr_fail_date' => toISO8601("now"),
                    'Consulting_Doctor'=> $parameters['doctor']
                ]);
            }
        }
        debug_log("update_membership endpoint set prescription approved to $is_approved for user " . $user->ID);
    }
}

function link_df_session(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_REST_Response(array('message' => 'Unauthorized'), 401);
    }
    $parameters = json_decode($request->get_body(), true);
    $session = $parameters["df_session"];
    update_user_meta($user->ID, 'chat_session', $session);
    update_api_user($user->ID);
    debug_log("link DF session $session to user $user->ID");
    return new WP_REST_Response(array('status' => 'success'), 200);
}

function set_user_timezone(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_REST_Response(array('message' => 'Unauthorized'), 401);
    }
    $parameters = json_decode($request->get_body(), true);
    $timezone = $parameters["timezone"];
    update_user_meta($user->ID, 'timezone', $timezone);
    debug_log("set timezone of user $user->ID to $timezone");
    return new WP_REST_Response(array('status' => 'success'), 200);
}

function call_finished(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_REST_Response(array('message' => 'Unauthorized'), 401);
    }
    update_user_meta($user->ID, 'meeting_attended', true);
    debug_log("Set meeting attended for user $user->ID");
    return new WP_REST_Response(array('status' => 'success'), 200);
}

function get_approval_status(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_REST_Response(array('message' => 'Unauthorized'), 401);
    }
    $has_result = metadata_exists('user', $user->ID, "prescription_approved");
    $is_approved = get_user_meta($user->ID, "prescription_approved", true);
    return new WP_REST_Response(array('has_result' =>$has_result, 'result' => $is_approved), 200);
}

function handle_callback_requested(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    $email = $data['session']['email']['value'];
    $user = get_user_by('email', $email);
    if ($user) {
        update_user_meta($user->ID, "callback_requested", true);
    }
    debug_log("handled call scheduled for user $email");
    return new WP_REST_Response(['result' => 'success'], 200);
}

function handle_queue_wait(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    $email = $data['session']['email']['value'];
    $user = get_user_by('email', $email);
    debug_log("handled queue wait for user $email");
    if ($user) {
        update_user_meta($user->ID, "queue_wait", true);
    }
    return new WP_REST_Response(['result' => 'success'], 200);
}

function handle_virtual_doctor_results(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    $sessionId = $data['chat_session'];

    // Search WordPress users matching the meta field 'chat_session' with the session ID
    $args = array(
        'meta_query' => array(
            array(
                'key' => 'chat_session',
                'value' => $sessionId,
                'compare' => '='
            )
        )
    );
    $userQuery = new WP_User_Query($args);
    $user = NULL;
    $userId = NULL;
    $users = $userQuery->get_results();

    if (!empty($users)) {
        // Assuming only one user is found, retrieve the first user
        $user = $users[0];
        $userId = $user->ID;
    } else {
        debug_log("invalid chat session id - user not found");
        return new WP_REST_Response(['message' => 'Invalid session'], 403);
    }

    foreach ($data as $key => $value) {
        if($key !== 'patient_card'){
            update_user_meta($userId, $key, $value);
        }
    }
    debug_log("bot results accepted for session $sessionId of user $userId");

    // get address from verification - here, because address extraction with OpenAI is async operation
    $verification_id = get_user_meta($userId, 'verification_id', true);
    $verification = query_verification($verification_id);
    if (!is_null($verification)) {
        $address = property_exists($verification->verification, "address") ? $verification->verification->address : "";
        if ($verification->verification->status != 'success') {
            debug_log("incorrect state - unsuccessful verification for $userId but bot callback is called");
        }
        update_user_meta($userId, 'address_details', $address);
    } else {
        debug_log("incorrect state - verification for $userId is not found, but bot callback is called");
    }

    if($data['doctor_approval_required']){
        update_zoho_patient_fields($user->ID, [
            'status'=> 'Dr Bot Passed',
             "bot_pas_date" => toISO8601("now"),
             'user_condition' => $data['patient_card']
         ]);
    }else{
        update_zoho_patient_fields($user->ID, [
        'status'=> 'Dr Bot Failed',
         "bot_fail_date" => toISO8601("now"),
         'user_condition' => $data['patient_card']
         ]);
    }
    return new WP_REST_Response(['message' => 'Results accepted'], 200);
}

/* function handle_ID_results(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    $sessionId = $data['chat_session'];

    // Search WordPress users matching the meta field 'chat_session' with the session ID
    $args = array(
        'meta_query' => array(
            array(
                'key' => 'chat_session',
                'value' => $sessionId,
                'compare' => '='
            )
        )
    );
    $userQuery = new WP_User_Query($args);
    $user = NULL;
    $userId = NULL;
    $users = $userQuery->get_results();

    if (!empty($users)) {
        // Assuming only one user is found, retrieve the first user
        $user = $users[0];
        $userId = $user->ID;
    } else {
        debug_log("invalid chat session id - user not found");
        return new WP_REST_Response(['message' => 'Invalid session'], 403);
    }

    foreach ($data as $key => $value) {
        if($key !== 'patient_card'){
            update_user_meta($userId, $key, $value);
        }
    }
    debug_log("bot results accepted for session $sessionId of user $userId");

    // get address from verification - here, because address extraction with OpenAI is async operation
    $verification_id = get_user_meta($userId, 'verification_id', true);
    $verification = query_verification($verification_id);
    if (!is_null($verification)) {
        $address = property_exists($verification->verification, "address") ? $verification->verification->address : "";
        if ($verification->verification->status != 'success') {
            debug_log("incorrect state - unsuccessful verification for $userId but bot callback is called");
        }
        update_user_meta($userId, 'address_details', $address);
    } else {
        debug_log("incorrect state - verification for $userId is not found, but bot callback is called");
    }

    if($data['doctor_approval_required']){
       $status = 'Dr Bot Passed';
    }else{
        $status = 'Dr Bot Failed';
    }
    update_zoho_patient_fields($user->ID, ['status'=> $status, 'user_condition' => $data['patient_card']]);
    return new WP_REST_Response(['message' => 'Results accepted'], 200);
} */

function update_api_user($user_id) {
    $user_meta = get_user_meta($user_id);
    $flattened_meta = [];
    foreach ($user_meta as $key => $value) {
        $flattened_meta[$key] = $value[0];
    }
    call_harvest_api($flattened_meta, "api/updateUser", "POST");
}

function call_harvest_api($body, $endpoint, $method) {
    $json_payload = json_encode($body);
    $headers = array(
        'content-type: application/json',
        "authorization: " . HARVEST_API_SECRET,
    );
    $curl = curl_init();
    $url = HARVEST_API_DOMAIN . $endpoint;
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_HTTPHEADER => $headers,
    ));
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    $status = $info["http_code"];
    if ($status != 200) {
        debug_log("Error code $status communicating with Harvest API: $response");
        return null;
    }
    return $response;
}