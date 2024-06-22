<?php

namespace MABEL_WOF\Core\Models
{
	class Start_VM
	{
		/**
		 * @var string the plugin's settings key
		 */
		public $settings_key;

		/**
		 * @var bool
		 */
		public $has_license;

		/**
		 * @var bool
		 */
		public $license_overdue;

		/**
		 * @var int
		 */
		public $time_left_in_days;
		/**
		 * @var Option_Section[]
		 */
		public $sections;

		/**
		 * @var string the plugin slug
		 */
		public $slug;

		/**
		 * @var Hidden_Option[]
		 */
		public $hidden_settings;

        public $woo_active;

		public function __construct()
		{
			$this->hidden_settings = [];
			$this->sections = [];
			$this->has_license = false;
            $this->woo_active = false;
		}
	}
}