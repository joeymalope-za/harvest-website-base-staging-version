<?php

namespace MABEL_WOF\Core\Models
{
	class Inline_Style
	{
		public $handle;
		public $rule;

		/**
		 * @var array
		 */
		public $styles;

		public function __construct($handle,$rule,$styles = [])
		{
			$this->handle = $handle;
			$this->rule = $rule;
			$this->styles = $styles;
		}
	}
}