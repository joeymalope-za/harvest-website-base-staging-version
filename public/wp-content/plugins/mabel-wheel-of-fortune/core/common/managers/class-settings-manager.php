<?php

namespace MABEL_WOF\Core\Common\Managers
{

	use Illuminate\Support\Facades\Config;

	/**
	 * This class holds the settings as saved from the admin pages.
	 * Class Settings_Manager
	 * @package MABEL_WOF\Core
	 */
	class Settings_Manager
	{
		/**
		 * @var array contains all plugin settings from the database.
		 */
		private static $settings = null;

		/**
		 * @var array contains all plugin default settings.
		 */
		private static $defaults;

		public static function init(array $defaults = [])
		{
			self::$defaults = $defaults;
		}

		/**
		 * Function to lazy fetch the settings. Only when they're needed.
		 */
		private static function fetch_settings()
		{
			if(self::$settings === null)
			{
				$options = get_option( Config_Manager::$settings_key );
				self::$settings = ( $options != '' && $options !== false ) ? (array) $options : [];
				self::sanitize();
			}
		}

		public static function has_setting($key)
		{
			self::fetch_settings();
			return !empty(self::$settings[$key]);
		}

		public static function set_setting($key, $value)
		{
			self::fetch_settings();
			self::$settings[$key] = $value;
		}

		public static function save()
		{
			self::fetch_settings();
			update_option(Config_Manager::$settings_key, self::$settings);
		}

		public static function delete_setting($key) {
			self::fetch_settings();
			unset(self::$settings[$key]);
		}

		public static function set_and_save($key, $value) {
			self::fetch_settings();
			self::$settings[$key] = $value;
			self::save();
		}

		public static function get_settings(){
			self::fetch_settings();
			return self::$settings;
		}

		public static function get_all_settings(){
			self::fetch_settings();
			return self::$settings;
		}

		public static function get_setting($key, $fallback_to_default = true)
		{
			self::fetch_settings();

			$setting = isset( self::$settings[$key] ) ? self::$settings[$key] : null;

			if($setting !== null || $fallback_to_default === false)
				return $setting;

			if(is_string($fallback_to_default))
				return $fallback_to_default;

			if(!is_array(self::$defaults))
				return null;

			if(isset(self::$defaults[$key]))
				return self::$defaults[$key];

			return null;
		}

		/**
		 * Translates the default setting if no setting was found
		 * @param $key
		 *
		 * @return null|string
		 */
		public static function get_translated_setting($key)
		{
			self::fetch_settings();

			$setting = isset( self::$settings[$key] ) ? self::$settings[$key] : null;

			if($setting != null)
				return $setting;

			if(!is_array(self::$defaults))
				return null;

			if(isset(self::$defaults[$key]))
				return __(self::$defaults[$key], Config_Manager::$slug);

			return null;
		}

		private static function sanitize()
		{
			foreach(self::$settings as $k => $v){
				if($v === 'true'){
					self::$settings[$k] = true;
					continue;
				}
				if($v === 'false'){
					self::$settings[$k] = false;
					continue;
				}
			}
		}
	}
}