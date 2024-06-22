<?php

class Sherpa_Request {

    public static function get($key, $default = null) {
        $key = trim($key);
        if (isset($_GET[$key]) && $_GET[$key]) {
            return $_GET[$key];
        }
        return $default;
    }

    public static function post($key, $default = null) {
        $key = trim($key);
        if (isset($_POST[$key]) && $_POST[$key]) {
            return $_POST[$key];
        }
        return $default;
    }

    public static function request($key, $default = null) {
        $key = trim($key);
        if (isset($_REQUEST[$key]) && $_REQUEST[$key]) {
            return $_REQUEST[$key];
        }
        return $default;
    }
}
