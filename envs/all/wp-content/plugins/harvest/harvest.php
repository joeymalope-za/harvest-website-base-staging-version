<?php
/**
 * Plugin Name: Harvest Plugin
 * Description: The plugin contains various integrations and customization of Harvest website.
 * Author: 🔥Ivan🔥
 *
 * @package HarvestPlugin
 */

// composer includes
require_once  ABSPATH . '/vendor/autoload.php';
function toISO8601(string $dateString): string {
    $dateTime = new \DateTime($dateString, new \DateTimeZone('UTC'));
    return $dateTime->format('Y-m-d\\TH:i:s');
}
require_once plugin_dir_path(__FILE__) . 'includes/api.php';
require_once plugin_dir_path(__FILE__) . 'includes/id-verification.php';
require_once plugin_dir_path(__FILE__) . 'includes/patient-reception.php';
require_once plugin_dir_path(__FILE__) . 'includes/gf-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/flow-controller.php';
require_once plugin_dir_path(__FILE__) . 'includes/shop.php';
require_once plugin_dir_path(__FILE__) . 'includes/zoho.php';
require_once plugin_dir_path(__FILE__) . 'includes/inventory_sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/alerts.php';
require_once plugin_dir_path(__FILE__) . 'includes/membership.php';
require_once plugin_dir_path(__FILE__) . 'includes/delivery.php';
require_once plugin_dir_path(__FILE__) . 'includes/address.php';

define('HARVEST_REST_USER', "Gt8UsyG2U");
define('HARVEST_REST_PASSWORD', "xwYqYEYzI8C");
define("SIGNUP_FORM_ID", 5);
define("CONSENT_FORM_ID", 28);
define("ADDRESS_FORM_ID", 24);
define("SIGNUP_FORM_EMAIL_FIELD_ID", 3);
define("ID_API_DOMAIN", "${ID_API_DOMAIN}");
define("HARVEST_API_DOMAIN", "${HARVEST_API_DOMAIN}");
define("HARVEST_API_SECRET", "l2k3jrdfsSF32^0395reksfdl@");
define("ID_API_SECRET", "84a52ff181cda0ea971db284e5c39c9a");
define("ID_WEB_DOMAIN", "${ID_WEB_DOMAIN}");
define("QUEUE_WEB_DOMAIN", "${QUEUE_WEB_DOMAIN}");
define("HARVEST_QUEUE_DOMAIN", "${HARVEST_QUEUE_DOMAIN}");
define("HARVEST_CONSENT_DOMAIN", "${HARVEST_CONSENT_DOMAIN}");
define("DOMAIN_URL", "${DOMAIN_URL}");
define("VIDEO_MEETING_LIFE", 24 * 60 * 60);
define("MAX_MEETING_LENGTH", 15 * 60);
define("DAILYCO_API_KEY", "${DAILYCO_API_KEY}");
define("JWT_SECRET", "k34j6es9fdvqo23k412qqa7u5743");
define("AUTH_TOKEN","${AUTH_TOKEN}");

function get_uuid($value)
{
    return wp_generate_uuid4();
}

function debug_log($text)
{
    $time_str = date('m-d-Y H:i:') . sprintf('%09.6f', date('s') + fmod(microtime(true), 1));
    $wp_user_id = 0;
    if (function_exists( 'get_current_user_id' ) ) {
        $wp_user_id = get_current_user_id();
    }
    $pid = function_exists('getmypid') ? getmypid() : -1;
    $key = array_search(__FUNCTION__, array_column(debug_backtrace(), 'function'));
    $file = basename(debug_backtrace()[$key]['file']);
    error_log("$time_str $pid $file $wp_user_id $text");
}

?>
