<?php

// do sign up flow redirect on a registration page
if (function_exists('add_action')) {
    add_action('template_redirect', function () {
        if (is_page('registration')) {
            redirect_to_right_step();
        }
    });
    add_action('wp_body_open', function () {
        if (is_page('registration')) {
            $model_url1 = ID_API_DOMAIN . "models/face_recognition_model-shard1";
            $model_url2 = ID_API_DOMAIN . "models/face_recognition_model-shard2";
            $model_url3 = ID_API_DOMAIN . "models/tiny_face_detector_model-shard1";
            // preload id verification component, while the user fills the form
            // clear bot data, in case it's a second registration
            echo "
                <link rel='preload' href='$model_url1' as='fetch' crossorigin>
                <link rel='preload' href='$model_url2' as='fetch' crossorigin>
                <link rel='preload' href='$model_url3' as='fetch' crossorigin>
                <script>
                    sessionStorage.clear();
                </script>
            ";
        }
    });
}

