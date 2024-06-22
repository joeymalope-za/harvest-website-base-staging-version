<?php

namespace MABEL_WOF
{

	use MABEL_WOF\Code\Controllers\Public_Controller;
	use MABEL_WOF\Code\Controllers\Shortcode_Controller;
	use MABEL_WOF\Code\Services\Log_Service;
	use MABEL_WOF\Code\Services\Theming_Service;
	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Config_Manager;
	use MABEL_WOF\Core\Common\Managers\Language_Manager;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
	use MABEL_WOF\Core\Common\Registry;
	use MABEL_WOF\Code\Controllers\Admin_Controller;

	if(!defined('ABSPATH')){die;}

	class Wheel_Of_Fortune
	{
		/**
		 * @var Language_Manager language manager.
		 */
		protected $language_manager;

		/**
		 * Business_Hours_Indicator constructor.
		 *
		 * @param $dir string
		 * @param $url string
		 * @param $slug string
		 * @param $version string
		 */
		public function __construct($dir, $url, $plugin_base, $name, $version, $settings_key)
		{
			// Init meta info.
			Config_Manager::init($dir, $url, $plugin_base, $version, $settings_key, $name);
		}

		public function run()
		{
			// Init translations.
			$this->language_manager = new Language_Manager();

			// Init settings with defaults.
			Settings_Manager::init( [
				'woo_coupons' => false,
				'woo_coupon_duration' => 5,
				'woo_coupon_timeperiod' => 'hours',
				'log' => false,
				'addons' => []
			] );

			// Kick off admin page.
			if(is_admin())
				new Admin_Controller();

			// Kick off public side of things.
			new Public_Controller();

			// Register shortcodes
			new Shortcode_Controller();

			// Register post type
			Registry::get_loader()->add_action('init',$this,'register_post_type');
			// Kick off!
			Registry::get_loader()->run();

			add_action('plugins_loaded', [ $this,'upgrade_routine' ]);

			// GDPR
			add_filter( 'wp_privacy_personal_data_exporters', [ $this,'register_data_exporters' ]);
			add_filter( 'wp_privacy_personal_data_erasers', [ $this,'register_data_erasers' ]);
		}

		public function register_data_erasers($erasers = []) {

			$erasers[] = [
				'eraser_friendly_name' => Config_Manager::$name,
				'callback' => [ $this,'data_eraser' ],
			];
			return $erasers;

		}

		public function register_data_exporters($exporters) {

			$exporters[] = [
				'exporter_friendly_name' => Config_Manager::$name,
				'callback' => [ $this,'data_exporter' ],
			];
			return $exporters;

		}

		public function data_eraser($email, $page = 1) {

			if ( empty( $email ) ) {
				return [
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => [],
					'done'           => true,
				];
			}

			$messages = [];
			$items_removed  = false;
			$items_retained = false;

			$logs = Log_Service::get_logs_of_email($email);

			if(!empty($logs)) {
				Log_Service::delete_logs_by_email( $email );
				$items_removed = true;
				$messages[] = 'WP Optin Wheel: the data was deleted.';
			}

			return [
				'items_removed'  => $items_removed,
				'items_retained' => $items_retained,
				'messages'       => $messages,
				'done'           => true,
			];

		}

		public function data_exporter( $email, $page = 1) {
			$export_items = [];

			$logs = Log_Service::get_logs_of_email($email);

			if(!empty($logs)) {

				$item_id = "wp-optin-wheel-log-".$email;
				$group_id = 'wp-optin-wheel';
				$group_label = __( 'Optin Wheel Plugin Data' );

				$total_plays = Enumerable::from($logs)->count(function($x){
					return $x->type == 1;
				});
				$total_optins = Enumerable::from($logs)->count(function($x){
					return $x->type == 0;
				});
				$optin_form_data = Enumerable::from($logs)->where(function($x){
					return $x->type == 0 && isset($x->fields) && !empty($x->fields) && $x->fields != '[]';
				})->select(function($x){
					return $x->fields;
				})->toArray();

				$play_results = Enumerable::from($logs)->where(function($x){
					return $x->type == 1;
				})->select(function($x){
					return 'Played wheel ID '.$x->wheel_id .'. Landed on segment '.$x->segment.' (text: "'.$x->segment_text.'") and '. ($x->winning == 0 ? 'lost.' : 'won.');
				})->toArray();

				$data = [ [
						'name'  => __( 'User Email', Config_Manager::$slug ),
						'value' => $email,
					], [
						'name'  => __( 'Total plays', Config_Manager::$slug ),
						'value' => $total_plays,
					], [
						'name'  => __( 'Total opt-ins', Config_Manager::$slug ),
						'value' => $total_optins,
					]
				];
				$data[] = [
					'name'  => __( 'Opt-in form data (JSON)', Config_Manager::$slug ),
					'value' => empty($optin_form_data) ? __('No opt-in data saved', Config_Manager::$slug) : join(', ', $optin_form_data),
				];

				$data[] = [
					'name' => __('Play results', Config_Manager::$slug),
					'value' => join('<br/>',$play_results)
				];

				$export_items[] = [
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data
				];

			}

			return [
				'data' => $export_items,
				'done' => true,
			];
		}

		// We have to fire this here (on every page load) because what if someone uses a remote tool to
		// auto upgrade plugins? We can't be sure they will visit the admin page before using the plugin.
		public function upgrade_routine() {

			$version = get_option('wof-pro-dev-version');

			if($version !== Config_Manager::$version) {

				// Update database
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				global $wpdb;
				$charset_collate = $wpdb->get_charset_collate();

				$table_name = $wpdb->prefix . 'wof_optins';

				$sql = 'CREATE TABLE ' . $table_name . ' (
					id int(11) NOT NULL AUTO_INCREMENT,
					wheel_id int(11) NOT NULL,
					email varchar(100) NULL,
					created_date datetime NOT NULL,
					fields varchar(8000) NULL,
					ip varchar(40) NULL,
					segment int(3) NULL,
					segment_text varchar (200) NULL,
					type tinyint(1) NULL,
					winning tinyint(1) NULL,
					prize varchar(1000) NULL,
					unique_hash varchar(100) NULL,
					PRIMARY KEY  (id)
				) ' . $charset_collate . ' AUTO_INCREMENT=1;';

				dbDelta( $sql );


				// All upgrades were necessary in pre 3.3.4 versions.
				if($version && version_compare($version,'3.3.4','<')) {

					// Update to IP's to a non identifiable hash (GDPR)
					$rows = $wpdb->get_results( "SELECT id, ip FROM " . $table_name );
					foreach ( $rows as $row ) {
						if ( isset( $row->ip ) && ! empty( $row->ip ) ) {
							$wpdb->update( $table_name, [ 'ip' => '', 'unique_hash' => hash( "md5", $row->ip ) ], [ 'id' => $row->id ] );
						}
					}

					// NULL vs NOT NULL is not done by wpdelta, so do it ourselves
					$wpdb->query( "ALTER TABLE " . $table_name . " MODIFY fields varchar(8000) NULL" );
					$wpdb->query( "ALTER TABLE " . $table_name . " MODIFY email varchar(100) NULL" );

					// Add theme colors & change consent checkbox ids
					$post_ids = get_posts( [
						'post_type'      => 'mb_woc_wheel',
						'fields'         => 'ids',
						'posts_per_page' => - 1,
					] );

					foreach ( $post_ids as $id ) {
						$obj = json_decode( get_post_meta( $id, 'options', true ) );

						if ( $obj === null ) {
							continue;
						}

						if ( empty( $obj->theme ) ) {
							continue;
						}
						$theme = Theming_Service::get_theme( $obj->theme );
						if ( $theme === null ) {
							continue;
						}

						if ( isset( $obj->slices ) ) {
							for ( $i = 0; $i < count( $obj->slices ); $i ++ ) {
								if ( empty( $obj->slices[ $i ]->fg ) ) {
									$obj->slices[ $i ]->fg = $theme['slices']['fg'][ $i ];
								}
								if ( empty( $obj->slices[ $i ]->bg ) ) {
									$obj->slices[ $i ]->bg = $theme['slices']['bg'][ $i ];
								}
							}
						}

						if ( empty( $obj->amount_of_slices ) ) {
							$obj->amount_of_slices = 12;
						}
						if ( empty( $obj->wheel_color ) ) {
							$obj->wheel_color = $theme['wheel'];
						}
						if ( empty( $obj->dots_color ) ) {
							$obj->dots_color = $theme['dots'];
						}
						if ( empty( $obj->bgcolor ) ) {
							$obj->bgcolor = $theme['bgcolor'];
						}
						if ( empty( $obj->fgcolor ) ) {
							$obj->fgcolor = $theme['fgcolor'];
						}
						if ( empty( $obj->pointer_color ) ) {
							$obj->pointer_color = $theme['pointerColor'];
						}
						if ( empty( $obj->button_bgcolor ) ) {
							$obj->button_bgcolor = $theme['buttonBg'];
						}
						if ( empty( $obj->button_fgcolor ) ) {
							$obj->button_fgcolor = $theme['buttonFg'];
						}
						if ( empty( $obj->secondary_color ) ) {
							$obj->secondary_color = $theme['emColor'];
						}
						if ( empty( $obj->secondary_color ) ) {
							$obj->secondary_color = $theme['emColor'];
						}

						foreach ( $obj as $key => $value ) {
							if ( is_string( $value ) && ! empty( $value ) ) {
								if ( $key === 'email_coupon_message' || $key === 'email_link_message' ) {
									// 3.0.3: Fix a bug we had in 3.0.1
									$value = preg_replace( '/n(?=\{{1})/', '<br/>',
										preg_replace( '/nn(?=[A-Z]{1})/', '<br/><br/>', $value )
									);
									$value = str_replace( '\n', '<br/>', $value ); // convert to HTML as since 3.0.2 we use textareas instead of editors
								}
								$obj->{$key} = addcslashes( $value, '"' );
							}
						}
						for ( $i = 0; $i < count( $obj->slices ); $i ++ ) {
							if ( ! empty( $obj->slices[ $i ]->value ) ) {
								$obj->slices[ $i ]->value = addcslashes( $obj->slices[ $i ]->value, '"' );
							}
						}
						for ( $i = 0; $i < count( $obj->fields ); $i ++ ) {
							if ( ! empty( $obj->fields[ $i ]->placeholder ) ) {
								$obj->fields[ $i ]->placeholder = addcslashes( $obj->fields[ $i ]->placeholder, '"' );
							}
						}

						//upgrade everything before v320
						if ( $obj->widget !== 'none' && strpos( $obj->appeartype, 'none' ) === false ) {
							$obj->appeartype = $obj->appeartype . ';none';
						}

						$encoded = json_encode( $obj, JSON_UNESCAPED_UNICODE );

						if ( $encoded === false ) {
							continue;
						}

						update_post_meta( $id, 'options', $encoded );
					}
				}

				update_option('wof-pro-dev-version', Config_Manager::$version,true);
			}
		}

		public function register_post_type() {
			register_post_type('mb_woc_wheel', [
				'public' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
			] );
		}

	}
}