<?php

namespace MABEL_WOF\Core\Models
{

	/**
	 * Class Option_Dependency
	 * @package MABEL_WOF\Core\Models
	 * Contains dependency info: a field is only visible when the depending field's value is set.
	 */

	class Option_Dependency
	{
		public $element_id;

		public $values;

		public $not_empty;

		public function __construct($element_id, $values, $not_empty = false)
		{
			// Legacy
			if(is_string($values))
				$values = [$values];

			$this->element_id = $element_id;
			$this->values = $values;
			$this->not_empty = $not_empty;
		}
	}
}