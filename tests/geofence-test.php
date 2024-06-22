<?php
include '../public/wp-load.php';
echo "started\n";
$delivery_address_obj = maybe_unserialize(get_user_meta(1, 'delivery_address_obj', true));;
$point = $delivery_address_obj ? array($delivery_address_obj["latitude"],
               $delivery_address_obj["longitude"]): null;
if (inside_geofence($point, "Sydney Same Day")) {
    $user = 1;
    if ($user) {
        $timezone = get_user_meta(1, 'timezone', true);
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
    echo $params['fail'];
}