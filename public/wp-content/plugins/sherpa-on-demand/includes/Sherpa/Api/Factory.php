<?php

/**
 * @category    Sherpa
 * @package     Sherpa_Api_Factory
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sherpa library Configurations
 *
 * @author AAlogics Team <team@aalogics.com>
 */
class Sherpa_Api_Factory {

    public static function build($endpoint, $dependencies = array()) {

        $className = 'Sherpa_Api_Endpoints_' . ucfirst($endpoint);
        $reflector = new ReflectionClass($className);
        $obj = $reflector->newInstanceArgs($dependencies);
        return $obj;
    }
}
