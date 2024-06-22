<?php


/**
 * Install and upgrade client abstract class
 *
 * @category   Varien
 * @package    Varien_Io
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Sherpa_Varien_Io_Abstract {

    /**
     * If this variable is set to true, our library will be able to automaticaly
     * create non-existant directories
     *
     * @var bool
     */
    protected $_allowCreateFolders = false;

    /**
     * Allow automaticaly create non-existant directories
     *
     * @param bool $flag
     * @return Varien_Io_Abstract
     */
    public function setAllowCreateFolders($flag) {

        $this->_allowCreateFolders = (bool)$flag;
        return $this;
    }

    /**
     * Open a connection
     *
     * @param array $config
     * @return bool
     */
    public function open(array $args = array()) {

        return false;
    }

    public function dirsep() {

        return '/';
    }

    public function getCleanPath($path) {

        if (empty($path)) {
            return './';
        }

        $path = trim(preg_replace("/\\\\/", "/", (string)$path));

        if (!preg_match("/(\.\w{1,4})$/", $path) && !preg_match("/\?[^\\/]+$/", $path) && !preg_match("/\\/$/", $path)) {
            $path .= '/';
        }

        $matches = array();
        $pattern = "/^(\\/|\w:\\/|https?:\\/\\/[^\\/]+\\/)?(.*)$/i";
        preg_match_all($pattern, $path, $matches, PREG_SET_ORDER);

        $pathTokR = $matches[0][1];
        $pathTokP = $matches[0][2];

        $pathTokP = preg_replace(array("/^\\/+/", "/\\/+/"), array("", "/"), $pathTokP);

        $pathParts = explode("/", $pathTokP);
        $realPathParts = array();

        for ($i = 0, $realPathParts = array(); $i < count($pathParts); $i++) {
            if ($pathParts[$i] == '.') {
                continue;
            } elseif ($pathParts[$i] == '..') {
                if ((isset($realPathParts[0]) && $realPathParts[0] != '..') || ($pathTokR != "")) {
                    array_pop($realPathParts);
                    continue;
                }
            }

            array_push($realPathParts, $pathParts[$i]);
        }

        return $pathTokR . implode('/', $realPathParts);
    }

    public function allowedPath($haystackPath, $needlePath) {

        return strpos($this->getCleanPath($haystackPath), $this->getCleanPath($needlePath)) === 0;
    }
}
