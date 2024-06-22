<?php

namespace MABEL_WOF\Core\Common\Managers
{
	use MABEL_WOF\Core\Models\Checkbox_Option;
	use MABEL_WOF\Core\Models\ColorPicker_Option;
	use MABEL_WOF\Core\Models\Container_Option;
	use MABEL_WOF\Core\Models\Custom_Option;
	use MABEL_WOF\Core\Models\Dropdown_Option;
	use MABEL_WOF\Core\Models\Number_Option;
	use MABEL_WOF\Core\Models\Range_Option;
	use MABEL_WOF\Core\Models\Text_Option;

	abstract class Abstract_Options_Manager
	{
		/**
		 * Function to display the field on a settings page.
		 */
		public function display_field(array $args)
		{
			if(!isset($args) || !isset($args['option']))
				return false;

			$option = $args['option'];

			$field_dir = Config_Manager::$dir . 'core/views/fields/';

			if($option instanceof Checkbox_Option) {
				return require $field_dir . 'checkbox.php';
			}

			if($option instanceof Dropdown_Option) {
				return require $field_dir . 'dropdown.php';
			}

			// Needs to be checked before Text_Option as it derives from it.
			if($option instanceof Number_Option) {
				return require $field_dir . 'number.php';
			}

			if($option instanceof Text_Option) {
				return require $field_dir . 'textbox.php';
			}

			if($option instanceof ColorPicker_Option) {
				return require $field_dir . 'colorpicker.php';
			}

			if($option instanceof Range_Option) {
				return require $field_dir . 'rangeslider.php';
			}

			if($option instanceof Custom_Option) {
				$data = $option->data;
                if( strpos(  $option->template, '.php') === false )
                    return require Config_Manager::$dir . 'admin/views/' . $option->template . '.php';
                return require $option->template;
			}

			if($option instanceof Container_Option) {
				return require $field_dir . 'container-option.php';
			}

			return false;
		}
	}
}