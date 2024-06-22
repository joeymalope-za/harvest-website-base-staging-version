<?php

namespace MABEL_WOF\Core\Models
{

	class Custom_Option extends Option
	{

		/**
		 * @var array hold the data for the custom option template.
		 */
		public $data;

		/**
		 * @var string
		 */
		public $template;

		public function __construct($title, $template, array $data = [])
		{
			parent::__construct( uniqid(), null, $title );

			$this->template = $template;
			$this->data = $data;

		}

	}

}