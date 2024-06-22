<?php

function enqueue_sweetalert() {
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', [], false, true);
}
if (function_exists('add_action'))
    add_action('wp_enqueue_scripts', 'enqueue_sweetalert');

function create_alert($type, $title="", $text="", $disappear = false) {
    $alert_id = uniqid('alert_', true);
    set_transient($alert_id, compact('type', 'title', 'text', 'disappear'), 1 * HOUR_IN_SECONDS);
    $alerts = isset($_COOKIE['alerts']) ? json_decode(stripslashes($_COOKIE['alerts']), true) : [];
    $alerts[] = $alert_id;
    setcookie('alerts', json_encode($alerts), time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
}

function display_alerts() {
    // get alert IDs from the cookie
    $alerts = isset($_COOKIE['alerts']) ? json_decode(stripslashes($_COOKIE['alerts']), true) : [];
    if (!empty($alerts)) {
        foreach ($alerts as $key => $alert_id) {
            $alert = get_transient($alert_id);
            if ($alert) {
                // generate the SweetAlert JavaScript for this alert
                echo '<script>document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "' . esc_js($alert['type']) . '",
                        title: "' . esc_js($alert['title']) . '",
                        text: "' . esc_js($alert['text']) . '",
                        timer: "' . ($alert['disappear'] ? '5000' : '999999') . '",
                        showConfirmButton: ' . ($alert['disappear'] ? 'false' : 'true') . ',
                        allowOutsideClick: ' . 'true' . ',
                    }); });
                </script>';
                unset($alerts[$key]);
                delete_transient($alert_id);
            }
        }

        // Update the cookie with the remaining alert IDs
        setcookie('alerts', json_encode($alerts), time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }
}
if (function_exists('add_action'))
    add_action('wp_footer', 'display_alerts');
