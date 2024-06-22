<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Autoloader
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Classes Autoloader
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Autoloader {

    static public function loader($className) {
        if (substr($className, 0, 6) == 'Sherpa') {
            $classFile = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className)));
            $filename  = plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . str_replace('\\', '/', $classFile) . ".php";
            if (file_exists($filename)) {
                include_once($filename);
                if (class_exists($className)) {
                    return TRUE;
                }
            }
        } else {
            return FALSE;
        }
    }
}

spl_autoload_register('Sherpa_Autoloader::loader');
