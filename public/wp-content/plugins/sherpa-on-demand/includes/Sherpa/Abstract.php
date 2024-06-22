<?php

abstract class Sherpa_Abstract {

    protected $data = array();

    public function __call($method, $args) {

        switch (substr($method, 0, 3)) {
            case 'get':
                $key = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", (substr($method, 3))));
                $data = isset($this->data[$key]) ? $this->data[$key] : FALSE;
                return $data;
                break;

            case 'set':
                $key = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", (substr($method, 3))));
                $this->data[$key] = isset($args[0]) ? $args[0] : null;
                return $this;
                break;

            default:
                return;
                break;
        }
    }

    public function setData($data = array()) {

        $this->data = $data;
    }

    public function getData($name) {

        return isset($this->data[$name]) ? $this->data[$name] : FALSE;
    }
}
