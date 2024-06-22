<?php

namespace MABEL_WOF\Core\Common
{

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Config_Manager;
	use MABEL_WOF\Core\Models\Inline_Style;

	class Presentation_Base
	{

		public $loader;
		/**
		 * @var array with key, value pairs to send to the frontend.
		 */

		public function __construct()
		{
			$this->loader = Registry::get_loader();
		}

		public function add_ajax_function($name,$component,$callable,$frontend = true,$backend = true)
		{
			if($frontend)
				$this->loader->add_action('wp_ajax_nopriv_' . $name,$component,$callable);
			if($backend)
				$this->loader->add_action('wp_ajax_' . $name,$component,$callable);
		}
	}
}
