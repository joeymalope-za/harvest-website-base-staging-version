<?php

namespace MABEL_WOF\Core\Common
{
	use MABEL_WOF\Core\Common\Managers\Config_Manager;
	use MABEL_WOF\Core\Common\Managers\License_Manager;
	use MABEL_WOF\Core\Common\Managers\Options_Manager;
	use MABEL_WOF\Core\Common\Managers\Script_Style_Manager;
	use MABEL_WOF\Core\Models\Start_VM;
	use DateTime;
	use DateTimeZone;

	abstract class Admin extends Presentation_Base
	{
		public $options_manager;
		public $add_mediamanager_scripts;
		private static $notices = [];
		private $license_manager;

		public function __construct(Options_Manager $options_manager)
		{
			parent::__construct();

			$this->add_mediamanager_scripts = false;
			$this->options_manager = $options_manager;

			// License & update manager
			$this->license_manager = new License_Manager();

			Script_Style_Manager::add_script(Config_Manager::$slug,'admin/js/admin.min.js','jquery');
			Script_Style_Manager::add_style(Config_Manager::$slug,'admin/css/admin.min.css');

			$this->loader->add_action('admin_menu', $this, 'add_menu');
			$this->loader->add_filter('plugin_action_links_' . Config_Manager::$plugin_base, $this, 'add_settings_link');

			$this->loader->add_action( 'admin_init', $this, 'init_settings');
			if(isset($_GET['page']) && $_GET['page'] === Config_Manager::$slug)
			{
				$this->loader->add_action( 'admin_enqueue_scripts', $this, 'register_styles' );
				$this->loader->add_action( 'admin_enqueue_scripts', $this, 'register_scripts' );
				$this->loader->add_action('admin_init',$this,'init_admin_page');
				$this->loader->add_action('admin_notices', $this,'show_admin_notices');
			}
		}

		public function show_admin_notices() {
			$notices = self::$notices;

			foreach( $notices as $notice ) {
				echo '<div class="notice is-dismissible notice-'.$notice['class'].'"><p>'.$notice['message'].'</p></div>';
			}

		}

		public abstract function init_admin_page();

		public function add_settings_link( $links )
		{
			$my_links = [
				'<a href="' . admin_url( 'options-general.php?page=' .Config_Manager::$slug ) . '">' .__('Settings' , Config_Manager::$slug). '</a>',
			];
			return array_merge( $links, $my_links );
		}

		public function add_menu()
		{
			$capability = apply_filters( 'wof_capability', 'manage_options' );
			$page = add_options_page('', Config_Manager::$name, $capability, Config_Manager::$slug, [$this,'display_settings']);
			add_action('load-'.$page, [$this, 'load']);
		}

		public function load(){
			$nonce = isset($_POST['_mabelnonce']) ? $_POST['_mabelnonce'] : false;

			if($nonce){
				if(wp_verify_nonce($nonce,'activate-pro')) {
					$activated = $this->license_manager->activate_license();
					$notice = $activated === true ? 'License activated' : $activated;
					array_push(self::$notices, [
						'class' => $activated === true ? 'success' : 'error',
						'message' => __($notice,Config_Manager::$slug)
					]);
				}

				if(wp_verify_nonce($nonce,'deactivate-pro')){
					$deactivated = $this->license_manager->deactivate_license();
					array_push(self::$notices, [
						'class' => 'success',
						'message' => __('License deactivated',Config_Manager::$slug)
					]);
				}
			}

		}

		public function init_settings()
		{
			register_setting( Config_Manager::$slug , Config_Manager::$settings_key );
		}

		public function display_settings()
		{
			$model = new Start_VM();
			$model->settings_key = Config_Manager::$settings_key;
			$model->sections = $this->options_manager->get_sections();
			$model->hidden_settings = $this->options_manager->get_hidden_settings();
			$model->slug = Config_Manager::$slug;
            $model->woo_active = is_plugin_active( 'woocommerce/woocommerce.php');

			$info = License_Manager::get_license_info();

			if($info !== null)
			{
				$model->has_license = !empty($info->key);
				$license_date_as_date =  DateTime::createFromFormat(
					'Y-m-d H:i:s',
					$info->expiration,
					new DateTimeZone('UTC'));
				$difference =  $license_date_as_date->diff(new DateTime('now',new DateTimeZone('UTC')));
				$model->time_left_in_days = $difference->days;
				$model->license_overdue = $difference->invert === 0;
			}

			ob_start();
			include Config_Manager::$dir . 'core/views/start.php';
			echo ob_get_clean();
		}

		public function register_styles() {
			Script_Style_Manager::register_styles();
			Script_Style_Manager::publish_styles();
		}

		public function register_scripts()
		{
			Script_Style_Manager::register_scripts();
			Script_Style_Manager::publish_scripts();

			if($this->add_mediamanager_scripts) {
				wp_enqueue_media();
			}
		}
	}
}