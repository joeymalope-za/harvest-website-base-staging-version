<?php
if (function_exists('add_filter')) {
    add_filter('gform_validation', 'check_duplicate_email');
    add_action('gform_after_submission', 'handle_user_registration', 10, 2);
    add_action("gform_after_submission", 'handle_consent', 10, 2);
    add_action("gform_after_submission", 'handle_address', 10, 2);
}

function check_duplicate_email($validation_result)
{
    $form_id = SIGNUP_FORM_ID;

    // Check if the current form is the one we want to validate
    if ($validation_result['form']['id'] != $form_id) {
        return $validation_result;
    }

    $email_field_id = SIGNUP_FORM_EMAIL_FIELD_ID;
    $email = rgpost("input_{$email_field_id}");

    if (email_exists($email)) {

        // re register if not member
        $user = get_user_by('email', $email);
        $wpID = $user->ID;
        $is_verified_member = get_user_meta($wpID, 'member_since', true);

        if($is_verified_member) {
            // Set the validation result to false to prevent the form from submitting
            $validation_result['is_valid'] = false;

            // Find the email field and add a validation message
            foreach ($validation_result['form']['fields'] as &$field) {
                if ($field->id == $email_field_id) {
                    $field->failed_validation = true;
                    $field->validation_message = 'This email address is already in use. Please enter a different email address.';
                    break;
                }
            }
        }
    }

    return $validation_result;
}

function handle_consent($entry, $form)
{
    if ($form["id"] != CONSENT_FORM_ID)
        return;
    $user_id = get_current_user_id();
    if (!$user_id)
        return;
    update_user_meta($user_id, 'consent', true);
}

function handle_address($entry, $form)
{
    if ($form["id"] != ADDRESS_FORM_ID)
        return;
    $user_id = get_current_user_id();
    if (!$user_id)
        return;
    $address = NULL;
    $address_data = array();
    foreach ($form['fields'] as $field) {
        $value = rgar($entry, (string)$field->id);
        debug_log($field->label);
        debug_log($value);
        if ($field->type == 'gfgeo_geocoder') {
            $address = $value;
        } else {
            switch ($field->label) {
                case 'House No & Street':
                    $address_data['street'] = $value;
                    break;
                case 'City':
                    $address_data['city'] = $value;
                    break;
                case 'State':
                    $address_data['state'] = $value;
                    break;
                case 'Post Code':
                    $address_data['post_code'] = $value;
                    break;
                case 'Country':
                    $address_data['country'] = $value;
                    break;
                default:
                    break;
            }
        }
    }
    if ($address)
        update_user_meta($user_id, 'delivery_address_obj', $address);
    update_zoho_patient_address_fields($user_id, $address_data);
}

function handle_user_registration($entry, $form)
{
    if ($form["id"] != SIGNUP_FORM_ID)
        return;
    try {
        $userdata = array();
        $userRef = '';
        foreach ($form['fields'] as $field) {

            $value = rgar($entry, (string)$field->id);
            switch ($field->label) {
                case 'First name':
                    $userdata['first_name'] = $value;
                    $userdata['billing_first_name'] = $value;
                    $userdata['shipping_first_name'] = $value;
                    break;
                case 'Last name':
                    $userdata['last_name'] = $value;
                    $userdata['billing_last_name'] = $value;
                    $userdata['shipping_last_name'] = $value;
                    break;
                case 'Email':
                    $userdata['user_email'] = $value;
                    break;
                case 'Phone':
                    $userdata['phone'] = $value;
                    $userdata['billing_phone'] = $value;
                    $userdata['shipping_phone'] = $value;
                    break;
                case 'referring_url':
                    $userRef = $value;
                    break;
                default:
                    break;
            }
            if (str_contains(strtolower($field->label), 'password'))
                $userdata['user_pass'] = $value;
        }
        $userdata['user_login'] = $userdata['user_email'];
        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            // check if user has failed previously and if so delete user and re-registering
            $user = get_user_by('email', $userdata['user_email']);
            $wpID = $user->ID;

            // check user states (should be about the same as flow-controller states)

            $is_verified_member = get_user_meta($wpID, 'member_since', true);
            debug_log('is verified'. $is_verified_member);

            if(!$is_verified_member) {
                // delete user to re register
                require_once(ABSPATH.'wp-admin/includes/user.php');
                wp_delete_user($wpID);
                debug_log('user deleted, re-register: ' . $wpID);
                $user_id = wp_insert_user($userdata);
            }else{
                // user already exists
                debug_log($user_id->get_error_message());
                http_response_code(500);
                exit;
            }
        }

        debug_log('user created successfully: ' . $user_id);
        if (isset($userdata['phone'])) {
            update_user_meta($user_id, 'phone', $userdata['phone']);
        }

        $user_uuid = get_uuid("");
        $chat_session = get_uuid("");
        update_user_meta($user_id, 'uuid', $user_uuid);
        update_user_meta($user_id, 'chat_session', $chat_session);
        // set fields for woocommerce
        $first_name = isset($userdata['first_name']) ? $userdata['first_name'] : "";
        $last_name = isset($userdata['last_name']) ? $userdata['last_name'] : "";
        $phone = isset($userdata['phone']) ? $userdata['phone'] : "";
        if($userRef) update_user_meta($user_id, 'referring_url', $userRef);
        update_user_meta($user_id, 'billing_first_name', $first_name);
        update_user_meta($user_id, 'billing_last_name', $last_name);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'shipping_first_name', $first_name);
        update_user_meta($user_id, 'shipping_last_name', $last_name);
        update_user_meta($user_id, 'shipping_phone', $phone);
        // activate doctor's call bypass for a magic phone number
        if ($phone == MAGIC_PHONE) {
            update_user_meta($user_id, 'test_bypass_approval', true);
            debug_log("doctor approval bypass is set for user $user_id");
        }
        create_zoho_user($user_id);
        debug_log("zoho user created for user $user_id uuid $user_uuid");
        wp_set_current_user($user_id, $userdata['user_login']);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $userdata['user_login'], get_user_by('id', $user_id));
    }
    catch (Exception $exception) {
        debug_log("Error registering the user: $exception");
        http_response_code(500);
    }
}