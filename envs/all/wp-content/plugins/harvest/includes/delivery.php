<?php
if (function_exists('add_shortcode')) {
    add_shortcode("inside_geozone",
        function($params) {
            $params = shortcode_atts(
                array(
                    'zone_name' => 'Sydney Same Day',
                    'address_field' => 'delivery_address_obj',
                    'success' => 'Same Day Delivery',
                    'fail' => 'Next Day Delivery',
                    'default' => 'Standard Delivery',
                ),
                $params,
                'inside_oneday_zone'
            );
            $delivery_address_obj = maybe_unserialize(get_user_meta(get_current_user_id(), $params["address_field"], true));
            $point = $delivery_address_obj ? array($delivery_address_obj["latitude"],
                $delivery_address_obj["longitude"]) : null;
            if (inside_geofence($point, $params["zone_name"])) {
                $user = wp_get_current_user();
                if ($user) {
                    $timezone = get_user_meta($user->ID, 'timezone', true);
                    if ($timezone) {
                        date_default_timezone_set($timezone);
                    }
                }
                if (date('G') < 17) {
                    echo $params['success'];
                } else
                    echo $params['fail'];
            }
            else {
                echo $params['default'];
            }
        });
}

function inside_geofence($point, $szbd_name) {
    if (!$point)
        return false;
    $fence_points = get_transient("geofence_$szbd_name");

    if ($fence_points === false) {
        $args = array(
            'post_type' => 'szbdzones',
            'title' => $szbd_name,
            'posts_per_page' => 1,
        );
        $posts = get_posts($args);
        if ($posts) {
            $post = $posts[0];
        } else {
            throw new Exception("Delivery zones post not found");
        }
        $post_id = $post->ID;
        // get geofence point array
        $fence_points = get_post_meta($post_id, 'szbdzones_metakey', true)["geo_coordinates"];

        // determine if the point is inside the fence
        if (!$fence_points || !is_array($fence_points) || count($fence_points) < 3) {
            throw new Exception("Invalid geofence points");
        }
        set_transient("geofence_$szbd_name", $fence_points, 0.5 * HOUR_IN_SECONDS);
    }

    $num_points = count($fence_points);
    $x = $point[0];
    $y = $point[1];
    $inside = false;

    for ($i = 0, $j = $num_points - 1; $i < $num_points; $j = $i++) {
        $xi = $fence_points[$i][0];
        $yi = $fence_points[$i][1];
        $xj = $fence_points[$j][0];
        $yj = $fence_points[$j][1];

        $intersect = (($yi > $y) != ($yj > $y))
            && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

        if ($intersect) $inside = !$inside;
    }

    return $inside;
}