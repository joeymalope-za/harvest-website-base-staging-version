<?php

// add address form logic
if (function_exists('add_action')) {
    add_action('wp_body_open', function () {
        if (is_page('address')) {
            $model_url1 = ID_WEB_DOMAIN . "models/face_recognition_model-shard1";
            $model_url2 = ID_WEB_DOMAIN . "models/face_recognition_model-shard2";
            $nonce = wp_create_nonce('wp_rest');
            // preload id verification component, while the user fills the form
            echo "
                <link rel='preload' href='$model_url1' as='fetch' crossorigin>
                <link rel='preload' href='$model_url2' as='fetch' crossorigin>
            ";
            $nonce = wp_create_nonce('wp_rest');
            echo "<script>var restNonce='$nonce';</script>";
            echo '
<script>
                         const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                          fetch("/wp-json/harvest-api/set_user_timezone", {
                                method: "POST",
                                headers: {
                                "Content-Type": "application/json",
                                "X-WP-Nonce": restNonce
                                },
                                body: JSON.stringify({timezone: timezone})
                            })
                                .then(response => {
                                if (response.ok) {
                                    console.log("user timezone set");
                                } else {
                                    console.error("error setting user timezone");
                                }
                            });
</script>
            ';
        }
    });
}

