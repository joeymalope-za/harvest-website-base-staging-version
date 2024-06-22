<?php

namespace WWBP\App\Controllers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WWBP\App\Helpers\CheckCompatible;

class Activator
{
    /**
     * Activator construct.
     */
    public function __construct() 
    {
        register_activation_hook(WWBP_PLUGIN_FILE, function () {
            new CheckCompatible; // Check Compatibility
        });
    }
}
