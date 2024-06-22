<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sherpa_options");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sherpa_entities");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sherpa_entity_options");
