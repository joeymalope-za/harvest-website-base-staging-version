<?php

namespace MABEL_WOF\Core\Models {

	class Info_Option extends Option
	{
		public $title;
		public $text;

		public function __construct($title,$text) {
			$this->title = $title;
			$this->text = $text;
		}
	}
}