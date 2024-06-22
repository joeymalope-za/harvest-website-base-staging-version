<?php
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$inject_doctor_logic = false;
$auth_token = NULL;

if (function_exists('add_action')) {
    add_action('template_redirect', function () {
        global $inject_doctor_logic;
        // not shop - do nothing
        if (!is_shop())
            return true;
        $link_token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
        if ($link_token) {
            login_link_token($link_token);
        }
        $user_id = get_current_user_id();
        // test doctor call bypass based on magic phone number
        if (get_user_meta($user_id, 'test_bypass_approval', true) && !get_user_meta($user_id, 'prescription_approved')) {
            update_user_meta($user_id, 'doctor_approval_required', true);
            update_user_meta($user_id, 'prescription_approved', true);
            update_user_meta($user_id, 'active_prescription', json_decode('{dosage: "28", dosage_unit: "grams", frequency: "monthly", thc_content: "22", "created_at": "1707142387", "expiration_date": "1722694387", "duration": "6"}'));
            make_member($user_id);
        }
        // both flow and shop access check
        if (redirect_to_right_step())
            return ob_get_clean();
        if (!can_see_the_shop() && should_do_redirects())
            wp_redirect("/my-account");
        return true;
    });
    add_action('wp_body_open', function () {
        // inject JS and HTML
        ob_start();
        if (!is_shop())
            return 0;
        $user_id = get_current_user_id();
        if ($user_id) {
            inject_html($user_id);
        } else {
            // not logged in or not shop
        }
        echo ob_get_clean();
    });
};

function login_link_token($link_token) {
    // attempt to read JWT and autologin the user
    global $auth_token;
    try {
        $headers = new stdClass();
        $decoded = JWT::decode($link_token, new Key(JWT_SECRET, 'HS256'), $headers);
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'uuid',
                    'value' => $decoded->id,
                    'compare' => '='
                )
            )
        );
        $userQuery = new WP_User_Query($args);
        $user = NULL;
        $userId = NULL;
        $users = $userQuery->get_results();

        if (!empty($users)) {
            $user = $users[0];
            $userId = $user->ID;
            wp_set_current_user($userId);
            wp_set_auth_cookie($userId);
            do_action('wp_login', $user->user_login, get_user_by('id', $userId));
            $auth_token = $link_token;
        } else {
            debug_log("invalid link token id - user not found");
        }
    } catch (\Firebase\JWT\ExpiredException $e) {
        debug_log("expired link token");
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        debug_log("link token invalid signature");
    } catch (\Exception $e) {
        debug_log("error decoding link token: $e");
    }
}

function can_see_the_shop() {
    $doctor_approval_required = get_user_meta(get_current_user_id(), 'doctor_approval_required', true);
    $no_prescription_status = !metadata_exists('user', get_current_user_id(), "prescription_approved");
    $doctor_call_pending = $doctor_approval_required && $no_prescription_status;
    return is_member(get_current_user_id()) || $doctor_call_pending;
}

function inject_html($user_id)
{
    global $auth_token;
    $nonce = wp_create_nonce('wp_rest');
    // xxx vulnerability - any user can initiate doctor chat on behalf of other user with known email
    // but how else to link Zoho CRM user to SalesIQ operator chat?
    $email = sanitize_email(wp_get_current_user()->user_email);
    $first_name = sanitize_text_field(get_user_meta($user_id, 'first_name', true));
    $isScheduled = filter_input(INPUT_GET, 'scheduled', FILTER_SANITIZE_NUMBER_INT) == 1 ? 'true' : 'false';
    $meeting_attended = get_user_meta($user_id, "meeting_attended", true);
    $requires_approval = get_user_meta($user_id, 'doctor_approval_required', true);
    // doctor approval is required and no approval yet - widget should activate and display state based on its logic
    $activate_widget = !metadata_exists('user', $user_id, "prescription_approved") && $requires_approval && !$meeting_attended;
    //$activate_widget = false;
    $widgetUrl = QUEUE_WEB_DOMAIN;
    $consentUrl = HARVEST_CONSENT_DOMAIN;
    $originUrl = DOMAIN_URL;
    if ($activate_widget) {
        $object = (object) [
            'user' => (object) [
                'id' => get_user_meta($user_id, "uuid", true)
            ]
        ];
        if (!$auth_token) {
            $res = call_harvest_api($object, "api/getAuthToken", "POST");
            if ($res) {
                $res_obj = json_decode($res);
                if (property_exists($res_obj, "token"))
                    $auth_token = $res_obj->token;
            }
        }
    }
    ?>


<script>
    var user_token = '<?php echo $auth_token; ?>';
    var activateWidget = <?php echo $activate_widget ? 'true' : 'false'; ?>;
    var widgetUrl = '<?php echo $widgetUrl; ?>';
    var originUrl = '<?php echo $originUrl; ?>';
    var consentUrl = '<?php echo $consentUrl; ?>';
    var restNonce = '<?php echo $nonce; ?>';
</script>


<style>
    .hidden{display:none !important;}
    .consult-iframe-cont{
    padding: 8px;
    position: fixed;
    right: 0px;
    bottom: 0;
    width: 100%;
    max-width: 720px;
    height: 172px;
    z-index: 9999;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    transform: translateX(0);
    -webkit-transform: translateX(0);
    -moz-transform: translateX(0);
    -o-transform: translateX(0);
    -webkit-transition: transform 0.3s ease, height 0.3s ease, width 0.3s ease, top 0.3s ease;
    -moz-transition: transform 0.3s ease, height 0.3s ease, width 0.3s ease, top 0.3s ease;
    -o-transition: transform 0.3s ease, height 0.3s ease, width 0.3s ease, top 0.3s ease;
    transition: transform 0.3s ease, height 0.3s ease, width 0.3s ease, top 0.3s ease;
}
.consult-iframe-cont.expanded{
    top: 0;
    transform: translate(-50%, -50%);
    -webkit-transform: translate(-50%, -50%);
    -moz-transform: translate(-50%, -50%);
    -o-transform: translate(-50%, -50%);
    top: 50%;
    left: 50%;
    width: 100%;
    height: 90vh;
    max-width: 1000px;
}

.consult-iframe{
    display: flex;
    flex-grow: 1;
    height: 100%;
    border: none;
}

.consult-iframe-header{
    background: #fd6440;
    cursor: pointer;
    padding: 8px 16px;
    color: #fff;
    display: flex;
    align-items: center;
}

.swal2-container {
    z-index: 9999;
}

.swal2-html-container .consent-container iframe {
    width: 100%;
    height: 70vh;
    border: none;
}


    @media (max-width: 1024px){
        .consult-iframe-cont{
            bottom: 78px;
        }
        .consult-iframe-cont.expanded{
        height: calc(100vh - 78px);
        }
    }

    @media (max-width: 767px){
    .consult-iframe-cont{
        bottom: 48px;
    }
    .consult-iframe-cont.expanded{
    height: calc(100vh - 48px);
    }
    }
    @media (max-width: 656px){
        .consult-iframe-cont{
            height: 146px;
        }
    }
</style>

<script>
    
    document.addEventListener("DOMContentLoaded", function () {
        // Define variables for the iframe and the container
        const contDiv = document.createElement("div");
        const iframe = document.createElement("iframe");
        const header = document.createElement("div");

        // Set up the header with the initial hidden state and SVG
        header.className = "consult-iframe-header hidden";
        header.innerHTML = '<svg style="margin-right:4px; width:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg> Expand Video Consultation'; // SVG content goes here

        // Toggle the display of the iframe
        function toggleIframe() {
            if (contDiv.classList.contains("expanded")) {
                contDiv.classList.remove("expanded");
                header.innerHTML = '<svg style="margin-right:4px; width:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg> Expand Video Consultation'; // SVG content for expand
            } else {
                contDiv.classList.add("expanded");
                header.innerHTML = ' <svg style="margin-right:4px; width:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" /></svg> Collapse Video Consultation'; // SVG content for collapse
            }
        }

        header.addEventListener("click", toggleIframe);

        // Event listener for the iframe messages
        function eventListener(event) {
            if (event?.data?.source === "react-devtools-bridge" 
                || event?.data?.source === "react-devtools-content-script"
            ) {
                return;
            }

            console.log('event:', event);
            const { type, error, source } = event.data;
            console.log('eventListener:: type, error, source ', type, error, source);

            switch (type) {
                case "doctor-consultation-completed":
                    console.log('Shop.php Video consultation ended');
                    // Handle completion
                    contDiv.remove();
                    //setTimeout(() => window.location.reload(), 5000);
                    const consentLink = consentUrl + 'consent?token=' + encodeURIComponent(user_token);
                    Swal.fire({
                        icon: "success",
                        title: "Welcome to the club!",
                        html: `<div class="consent-container"><iframe src="${consentLink}"></iframe></div>`,
                        timer: "99999999",
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });
                    break;
                case "consent-approved":
                    // Handle approved
                    jQuery.post(
                        '<?php echo admin_url( 'admin-ajax.php' ); ?>', 
                        {
                            action: 'update_consent',
                            consent: 1,
                            nonce: '<?php echo wp_create_nonce("update-consent-nonce"); ?>'
                        },
                        function(response) {
                            if (response.success) {
                                window.location.reload();
                            } else {
                                console.error('Failed to update consent:', response.data);
                            }
                        }
                    );
                    break;
                case "doctor-consultation-rejected":
                    // Handle rejection
                    setTimeout(() => window.location.reload(), 5000);
                    Swal.fire({
                        icon: "error",
                        title: "Not approved",
                        text: "Sorry, we cannot approve your CBD treatment at this time.",
                        timer: "99999999",
                        showConfirmButton: true,
                        allowOutsideClick: false,
                    }).then(function() {
                        window.location.reload();
                    });
                    break;
                case "queueFail":
                    toggleIframe();
                    if (error === "doctor_fail") {
                        // Handle doctor fail error
                        console.log("No doctors available, attempting to show popup");
                        Swal.fire({
                            icon: "error",
                            title: "Hang on",
                            text: "Sorry, the doctor had a technical issue. We'll get you the next available doctor!",
                            timer: "99999999",
                            showConfirmButton: true,
                            allowOutsideClick: false,
                        });
                    } else if (error === "no_doctors") {
                        // Handle no doctors error
                        const url = widgetUrl + 'select_callback_time?token=' + encodeURIComponent(user_token);
                        Swal.fire({
                            icon: "error",
                            title: "Doctors are offline",
                            text: "Sorry, no doctors are currently available. Please select a callback time after this window.",
                            timer: "99999999",
                            showConfirmButton: true,
                            allowOutsideClick: false,
                        }).then(function() {
                            window.location.href = url; // redirect to select a callback time
                        });
                    } else {
                        // Handle generic error
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Sorry, there was an error. Please try again later.",
                            timer: "99999999",
                            showConfirmButton: true,
                            allowOutsideClick: false,
                        });
                    }
                    break;
                case "doctor-consultation-started":
                    // Handle consultation started
                    console.log('Shop.php:: doctor-consultation-started');
                    if(!contDiv.classList.contains("expanded")) {
                        toggleIframe();
                    }

                    header.classList.remove("hidden");
                    // Optionally show a message to the user
                    Swal.fire({
                        icon: "info",
                        title: "Your consultation is starting",
                        text: "Please be ready to discuss with your doctor.",
                        showConfirmButton: true
                    });
                    break;
                case "callback-confirmed":
                    console.log("Shop.php:: callback confirmed");
                    document.body.removeChild(iframe);
                    window.removeEventListener("message", eventListener);
                    break;
                case "select-callback-time": 
                    console.log('Shop.php:: select-callback-time');

                    if (!contDiv.classList.contains("expanded")) {
                        contDiv.classList.add("expanded");
                    }

                    header.classList.add("hidden");
                    break;
                default:
                    // Handle any other types
                    console.log("Unhandled message type:", type);
                    break;
            }

        }

        // Initialize the iframe after permissions are granted
        function initIframe() {
            const url = widgetUrl + 'patient?token=' + encodeURIComponent(user_token); // Ensure the token is URL-encoded
            console.log('InitIFrame::url: ', url);
            iframe.src = url;
            iframe.allow = "camera; microphone";
            iframe.className = "consult-iframe";
            contDiv.className = "consult-iframe-cont hidden";

            iframe.onload = function () {
                // Show the iframe when it's loaded
                contDiv.classList.remove("hidden");
            };

            // Append the iframe and header to the container div
            contDiv.appendChild(header);
            contDiv.appendChild(iframe);

            // Append the container div to the body of the document
            document.body.appendChild(contDiv);

            // Listen for messages sent from the iframe
            window.addEventListener("message", eventListener);
        }

        // Check if the widget should be activated
        if (activateWidget) {
            // Request for video and audio permissions before loading the iframe
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(initIframe)
                .catch((error) => {
                    console.error('Error getting media devices: ', error)
                    // Prompt the user to enable permissions
                    Swal.fire({
                        icon: "error",
                        title: "Permissions needed",
                        text: "Please enable camera and microphone permissions to start the consultation.",
                        showConfirmButton: true
                    });
                });
        }
    });
</script>
<?php
}
