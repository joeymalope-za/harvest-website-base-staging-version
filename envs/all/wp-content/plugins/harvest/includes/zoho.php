<?php
/**
 * Zoho integration
 */

define("ZOHO_CLIENT_ID", "${ZOHO_CLIENT_ID}");
define("ZOHO_CLIENT_SECRET", "${ZOHO_CLIENT_SECRET}");
define("ZOHO_REFRESH_TOKEN", "${ZOHO_REFRESH_TOKEN}");
define("ZOHO_ACCOUNTS_DOMAIN", "https://accounts.zoho.com/");
define("ZOHO_API_DOMAIN", "https://www.zohoapis.com/");
define("ZOHO_CRM_ACCOUNT_NAME", "Zoho");
// yes, organization id is DIFFERENT across products
define("ZOHO_INVENTORY_ORGANIZATION_ID", "7002692282");
define("ZOHO_INVENTORY_SITE", "https://inventory.zoho.com/");
define("ZOHO_INVENTORY_NON_PRODUCTS", ["HRC-420-24-7"]);
define("PLACEHOLDER_PRODUCT_ID", "51445000000802387");
define("PLACEHOLDER_PRODUCT_NAME", "Item Placeholder");
define("VERIFICATION_PAGE_NAME", "id-verify");
define("BOT_PAGE_NAME", "patient-reception");
define("MAGIC_PHONE", "0408342974");

function getAccessToken($client_id, $client_secret, $refresh_token) {
    $token = get_transient('zoho_access_token');
    if ($token)
        return $token;

    $url = ZOHO_ACCOUNTS_DOMAIN . "oauth/v2/token";
    $params = array(
        "refresh_token" => $refresh_token,
        "client_id" => $client_id,
        "client_secret" => $client_secret,
        "grant_type" => "refresh_token"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $response = json_decode($result, true);

    curl_close($ch);

    if (isset($response["access_token"])) {
        $token = $response["access_token"];
        // access token expires in an hour
        set_transient('zoho_access_token', $token, 60*60 - 600);
        return $token;
    } else {
        debug_log('Error obtaining Zoho access token');
        return null;
    }
}

function create_zoho_user($user_id) {
    $access_token = getAccessToken(ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, ZOHO_REFRESH_TOKEN);
    $user = get_user_by( 'id', $user_id );
    $user_meta = get_user_meta( $user_id );
    $zoho_fields = array(
        "First_Name" => $user_meta["first_name"][0],
        "Last_Name" => $user_meta["last_name"][0],
        "Email" => $user->user_email,
        "Mobile" => $user_meta["phone"][0],
        //"Lead_Date" =>  date_format(date_create(),"yyyy-MM-dd"), //auto generated in zoho
        "Lead_Status" => 'Reg Form Completed',
        "Lead_Source" => 'Registration Form',
        "Source_URL" => 'Registration Form',
        "harvest_uuid" => $user_meta["uuid"][0]
    );
    if($user_meta["referring_url"][0]) $zoho_fields["Source_URL"] = $user_meta["referring_url"][0];

    if ($access_token) {
        $url = ZOHO_API_DOMAIN . "crm/v6/Leads";
        $headers = array(
            "Authorization: Zoho-oauthtoken " . $access_token
        );

        $data = array(
            "data" => array($zoho_fields)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $result_obj = json_decode($result);
        if (curl_errno($ch) || $result_obj->data[0]->code != "SUCCESS" ) {
            debug_log("Error: unable to create Zoho user: $result");
            throw new Exception("Unable to create Zoho user: $result");
        }
        $zoho_id = $result_obj->data[0]->details->id;
        curl_close($ch);

        update_user_meta($user_id, "zoho_id", $zoho_id);
        debug_log("Zoho lead $zoho_id created for user $user_id");
    } else {
        debug_log("Error: unable to refresh Zoho access token.");
        throw new Exception("Unable to create Zoho user: unable to refresh Zoho access token");
    }
}

function create_wc_order_from_zoho($zoho_order, $wp_user_id) {
    $order = null;
    $zoho_product_details = $zoho_order -> Product_Details;
    foreach ($zoho_product_details as $zoho_product) {
        // Ignore the placeholder product
        if ($zoho_product->product->name != PLACEHOLDER_PRODUCT_NAME && $zoho_product->product->id != PLACEHOLDER_PRODUCT_ID) {
            // first non-placeholder product - create wc order
            if ($order == null) {
                $order = wc_create_order(array('customer_id' => $wp_user_id));
                $order -> set_created_via('flow');
                $order->set_currency(get_woocommerce_currency());

            }
            $product_id = wc_get_product_id_by_sku($zoho_product->product->Product_Code);
            if ($product_id) {
                $order->add_product(wc_get_product($product_id), $zoho_product->quantity);
            } else {
                // TODO: maybe try explicitly pulling this new product from Zoho
                error_log("Error syncing SalesOrder " . $zoho_order->details->id . " No WooCommerce product found matching product code: " . $zoho_product->product->Product_Code);
            }
        }
    }
    if ($order != null) {
        $order->set_status('wc-pending');
        $order->calculate_totals();
        $order->save();
        return $order -> get_id();
    }
    return null;
}

function populate_wc_cart_from_zoho($zoho_order, $wp_user_id)
{
    wp_set_current_user($wp_user_id);
    include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
    include_once WC_ABSPATH . 'includes/class-wc-cart.php';
    if ( is_null( WC()->cart ) ) {
        wc_load_cart();
    }
    //WC()->cart->empty_cart();
    $zoho_product_details = $zoho_order->Product_Details;
    foreach ($zoho_product_details as $zoho_product) {
        // Ignore the placeholder product
        if ($zoho_product->product->name != PLACEHOLDER_PRODUCT_NAME && $zoho_product->product->id != PLACEHOLDER_PRODUCT_ID) {
            $product_id = wc_get_product_id_by_sku($zoho_product->product->Product_Code);
            if ($product_id) {
                WC()->cart->add_to_cart($product_id, $zoho_product->quantity);
                debug_log("item $product_id added to $wp_user_id cart");
            } else {
                // TODO: maybe try explicitly pulling this new product from Zoho
                error_log("Error syncing SalesOrder " . $zoho_order->details->id . " No WooCommerce product found matching product code: " . $zoho_product->product->Product_Code);
            }
        }
    }
}

function get_crm_sales_order($access_token, $sales_order_id) {
    $url = ZOHO_API_DOMAIN . "crm/v2/Sales_Orders/" . $sales_order_id;
    $headers = array(
        "Authorization: Zoho-oauthtoken " . $access_token
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    $result_obj = json_decode($result);
    if (isset($result_obj->data[0]->Product_Details)) {
        return $result_obj->data[0];
    }

    return null;
}

function read_patient_data_from_zoho($user_id) {
    $user_meta = get_user_meta( $user_id );
    $record_id = $user_meta["zoho_id"][0];
    $order_id = $user_meta["zoho_initial_order_id"][0];
    $access_token = getAccessToken(ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, ZOHO_REFRESH_TOKEN);
    if ($access_token) {
        $url = ZOHO_API_DOMAIN . "crm/v2/Contacts/" . $record_id;
        $headers = array(
            "Authorization: Zoho-oauthtoken " . $access_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $result_obj = json_decode($result);

        update_user_meta($user_id, "doctor_approval_required", $result_obj->data[0]->Doctor_Approval_Required);
        // store prescription details
        update_user_meta($user_id, "prescription", $result_obj->data[0]->Subform_1);

        if ($result_obj->data[0]->Prescription_Approved != null) {
            update_user_meta($user_id, "prescription_approved", $result_obj->data[0]->Prescription_Approved);
        }
        curl_close($ch);
        debug_log("user $user_id fields updated from zoho contact");

        if (!get_user_meta($user_id, "cart_populated", true)) {
            $zoho_order = get_crm_sales_order($access_token, $order_id);
            if ($zoho_order) {
                populate_wc_cart_from_zoho($zoho_order, $user_id);
                update_user_meta($user_id, "cart_populated", true);
                debug_log("initial cart for $user_id populated from CRM sales order $order_id");
            } else
                debug_log("CRM sales order $order_id for $user_id doesnt have any products");
        }
    } else {
        debug_log("Error: unable to refresh Zoho access token.");
        http_response_code(500);
        exit;
    }
}

function update_zoho_patient_address_fields($user_id, $address_fields) {
    $user_meta = get_user_meta( $user_id );
    $record_id = $user_meta["zoho_id"][0];

    $user = array(
        "Street" => $address_fields["street"],
        "City" => $address_fields["city"],
        "State" => $address_fields["state"],
        "Zip_Code" => $address_fields["post_code"],
        "Country" => $address_fields["country"],
// below fields are not required in Zoho anymore, because we have custom doctor UI, plus it's not safe to store sensitive data in WP
//        "Condition" => $user_meta["condition"][0],
//        "Dosage" => intval($user_meta["dosage"][0]),
// categories are determined AFTER the doctor adds items to the cart
// "Product_Category" => get_user_meta( $user_id, "product_category", true),
//        "Doctor_Approval_Required" => $user_meta["doctor_approval_required"][0] == 1,
//        "Patient_Card" => $user_meta["patient_card"][0],
//        "ID_Image_Download" => $user_meta["id_url"][0],
//        "Selfie_Image_Download" => $user_meta["selfie_url"][0],
//        "Images_View_URL" => ID_WEB_DOMAIN . 'images/'.$user_meta["verification_id"][0],
//        "Meeting_URL" => $user_meta["meeting_url"][0],
    );

    $access_token = getAccessToken(ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, ZOHO_REFRESH_TOKEN);

    if ($access_token) {
        $url = ZOHO_API_DOMAIN . "crm/v6/Leads/" . $record_id;
        $headers = array(
            "Authorization: Zoho-oauthtoken " . $access_token
        );

        $data = array(
            "data" => array($user)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $result_obj = json_decode($result);

        if (curl_errno($ch) || $result_obj->data[0]->code != "SUCCESS" ) {
            debug_log("Error: unable to update Zoho user $record_id: $result");
            http_response_code(500);
            exit;
        }

        curl_close($ch);
        debug_log("Zoho lead  $record_id address updated for user $user_id");
    } else {
        debug_log("Error: unable to refresh Zoho access token.");
        http_response_code(500);
        exit;
    }
}
function update_zoho_patient_fields($user_id, $fields) {
    $user_meta = get_user_meta( $user_id );
    $record_id = $user_meta["zoho_id"][0];

    $user = array(
        "Lead_Status" => $fields["status"],
    );
    if(isset($fields['reason'])) $user['ID_Failure_Reason'] = $fields['reason'];
    if(isset($fields['id_url'])) $user['ID_Image'] = $fields['id_url'];
    if(isset($fields['selfie_url'])) $user['Selfie_Image'] = $fields['selfie_url'];
    if(isset($fields['user_condition'])) $user['User_Condition'] = $fields['user_condition'];

    if(isset($fields['id_pass_date'])) $user['ID_Verify_Pass_Date_Time'] = $fields['id_pass_date'];
    if(isset($fields['id_review_date'])) $user['ID_Manual_Review_Date_Time'] = $fields['id_review_date'];

    if(isset($fields['bot_pas_date'])) $user['Dr_Bot_Passed_Date_Time'] = $fields['bot_pas_date'];
    if(isset($fields['bot_fail_date'])) $user['Dr_Bot_Failed_Date_Time'] = $fields['pass_datefail_date'];

    if(isset($fields['doctor'])) $user['Consulting_Doctor'] = $fields['doctor'];
    if(isset($fields['dr_pas_date'])) $user['Dr_Approve_Date_Time'] = $fields['dr_pas_date'];
    if(isset($fields['dr_fail_date'])) $user['Dr_Rejection_Date_Time'] = $fields['dr_fail_date'];
    if(isset($fields['dr_id_fail_date'])) $user['Dr_Reject_ID_Date_Time'] = $fields['dr_id_fail_date'];

    $access_token = getAccessToken(ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, ZOHO_REFRESH_TOKEN);
    if ($access_token) {
        $url = ZOHO_API_DOMAIN . "crm/v6/Leads/" . $record_id;
        $headers = array(
            "Authorization: Zoho-oauthtoken " . $access_token
        );

        $data = array("data" => array($user));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $result_obj = json_decode($result);

        if (curl_errno($ch) || $result_obj->data[0]->code != "SUCCESS" ) {
            debug_log("Error: unable to update Zoho user $record_id: $result");
            http_response_code(500);
            exit;
        }

        curl_close($ch);
        debug_log("Zoho lead $record_id updated for user $user_id");
    } else {
        debug_log("Error: unable to refresh Zoho access token.");
        http_response_code(500);
        exit;
    }
}