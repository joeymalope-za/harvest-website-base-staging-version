<?php
if (function_exists('add_action')) {
    add_action('template_redirect', function () {
        if (is_page(VERIFICATION_PAGE_NAME))
            redirect_to_right_step();
        return true;
    });
    add_action('wp_body_open', function () {
        if (!is_page(VERIFICATION_PAGE_NAME))
            return true;
        ob_start();
        // in contrast with other steps, this one doesn't use callback to receive verification status, and instead gets it
        // from API on every page refresh - that should be very fast
        $user_id = get_current_user_id();
        debug_log("wp_body_open");
        debug_log($user_id);
        $user_uuid = get_user_meta($user_id, 'uuid', true);
        // check if user already verified
        $is_verified = get_user_meta($user_id, 'is_verified', true);
        if (!$is_verified) {
            // check if verification was successful or initiate a new one
            $first_name = get_user_meta($user_id, 'first_name', true);
            $last_name = get_user_meta($user_id, 'last_name', true);
            list($verification_id, $status, $status_reason, $id_url, $selfie_url, $address, $dob) = validate_verification($user_uuid, "$first_name $last_name");
            debug_log("Verification id for user $user_id is $verification_id");
            update_user_meta($user_id, 'verification_id', $verification_id);
            debug_log("update verification $status $status_reason");
            if (!is_null($status)) {
                $is_verified = $status == 'success';
                update_user_meta($user_id, 'is_verified', $is_verified);
                update_user_meta($user_id, 'verification_status', $status);
                update_user_meta($user_id, 'verification_status_reason', $status_reason);
                update_user_meta($user_id, 'address_details', $address);
                debug_log("update verification status $user_id: $status $status_reason");
                // create Harvest API user
                $user_meta = get_user_meta($user_id);
                $flattened_meta = [];
                foreach ($user_meta as $key => $value) {
                    $flattened_meta[$key] = $value[0];
                }
                $flattened_meta["id_url"] = $id_url;
                $flattened_meta["selfie_url"] = $selfie_url;
                $flattened_meta["dob"] = $dob;
                $flattened_meta["email"] = $flattened_meta["nickname"];
                call_harvest_api($flattened_meta, "api/createUser", "POST");

                // update zoho user
                if($is_verified){
                    update_zoho_patient_fields($user_id, [
                        'status'=> 'ID Verification Passed',
                        'id_pass_date' => toISO8601("now")
                    ]);
                }else if( $status == 'review' ){
                    update_zoho_patient_fields($user_id, [
                        'status'=> 'ID Manual Review',
                        'id_review_date' => toISO8601("now"),
                        'id_url' => $id_url,
                        'selfie_url' =>  $selfie_url
                    ]);
                }
                // we just updated verification status - see if we need another page
                if (redirect_to_right_step())
                    return ob_get_clean();
            }
        }
        echo ob_get_clean();
        return true;
    });
}

function validate_verification($user_id, $name)
{
    $verification = get_verification($user_id, $name);
    if (property_exists($verification, "verification")) {
        $address = property_exists($verification->verification, "address") ? $verification->verification->address: "";
        $dob = property_exists($verification->verification, "dateOfBirth") ? $verification->verification->dateOfBirth: "";
        return array($verification->verification->_id, $verification->verification->status,
            $verification->verification->statusReason,
            $verification->verification->idImage, $verification->verification->selfieImage, $address, $dob);
    }
    $verification_token = $verification->token;
    $id_domain = ID_WEB_DOMAIN;
    inject_verification_html($verification_token, $id_domain);
    return array($verification->verificationId, null, null, null, null, null, null);
}

function encrypt($string, $secret_key)
{
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($string, 'AES-256-CBC', $secret_key, OPENSSL_RAW_DATA, $iv);
    $ciphertext = $iv . $ciphertext;
    $ciphertext_base64 = base64_encode($ciphertext);
    return $ciphertext_base64;
}

function sendRequest($url, $method, $body)
{
    $json_payload = json_encode($body);

    $secret_key = ID_API_SECRET;
    $authorization = encrypt($json_payload, $secret_key);

    $headers = array(
        'content-type: application/json',
        "authorization: " . $authorization,
    );

    $curl = curl_init();

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
        debug_log("Error code $status communicating with verification API: $response");
        return null;
    }
    return $response;
}

function get_verification($user_id, $name)
{
    $body = new stdClass();
    if (!empty($user_id)) {
        $body->userId = $user_id;
        $body->name = $name;
        $body->checkFirst = true;
        debug_log("Calling verification API for $body->userId");
        $url = ID_API_DOMAIN . 'api/verification/initiate';
        $response = sendRequest($url, "POST", $body);
        if ($response != null)
            return json_decode($response);
    }
    return null;
}

function query_verification($verification_id)
{
    $url = ID_API_DOMAIN . "api/verification/result";
    $body = new stdClass();
    $body->verificationId = $verification_id;
    $response = sendRequest($url, "POST", $body);
    if ($response != null) {
        $decoded = json_decode($response);
        return $decoded;
    }
    return null;
}

function inject_verification_html($verification_token, $verification_domain)
{
    echo '<script type="text/javascript">';
    echo "var verification_token='$verification_token';
          var verification_url='$verification_domain';
        ";
    echo '
    
    class IdentityVerification {    
    preload = () => {
        const preloadLibrary = (url) => {
            const element = document.createElement("link");
            element.rel = "preload";
            element.href = url;
            element.as = "fetch";
            element.crossOrigin = "anonymous"; 
            document.head.appendChild(element);
        }
        
        const iframe = document.createElement("iframe");
        iframe.style.display = "none";
        iframe.src = this.verificationUrl
        document.body.appendChild(iframe);
        
        iframe.addEventListener("load", () => {
            document.body.removeChild(iframe);
            
            preloadLibrary(`${this.verificationUrl}/models/face_recognition_model-shard1`)
            preloadLibrary(`${this.verificationUrl}/models/face_recognition_model-shard2`)
        });
    }  
    
    eventListener = (event) => {
        if(event.source !== this.iframe.contentWindow) {
            return
        }
        
        const { type } = event.data
        
        console.log("iframe event data =", event)
        console.log("iframe event type =", type)
        
        if(type === "user-exit") {
            this.onExit && this.onExit()
            this.close()
        }
        
        if(type === "verification-completed") {
            this.onComplete && this.onComplete(event.data.verification)
            this.close()
        }
    } 
    
    start = async ({ token }) => { try {
        const url = `${this.verificationUrl}?token=${token}`
        const iframe = document.createElement("iframe");
        iframe.src = url
        iframe.allowTransparency = "true"
        iframe.allow = "camera"
        
        iframe.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background-color: transparent;
            border: 0;
        `;
      
        document.body.appendChild(iframe);
        this.iframe = iframe
        
        window.addEventListener("message", this.eventListener);
    } catch(error) {
        this.onError && this.onError(error)
    }}
    
    close() {
        if(this.iframe) {
            this.iframe.parentNode.removeChild(this.iframe);
            this.iframe = null;
            
            window.removeEventListener("message", this.eventListener);
        }
    }
    
    constructor({
        environment,
        onComplete,
        onExit,
        onError,
    }) {
        this.verificationUrl = verification_url
        this.onComplete = onComplete
        this.onExit = onExit
        this.onError = onError
    }
}
    
    const identityVerification = new IdentityVerification({
        onComplete: (data) => window.location.reload(),
        onClose: () => window.location.reload(),
        onError: (error) => console.log(error),
    });
    identityVerification.start({ token: verification_token });
    ';
    echo '</script>';
}

?>
