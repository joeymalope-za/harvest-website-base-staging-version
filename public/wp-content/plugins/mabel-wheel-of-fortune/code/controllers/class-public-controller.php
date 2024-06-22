<?php

namespace MABEL_WOF\Code\Controllers
{

	use MABEL_WOF\Code\Models\CouponBar;
	use MABEL_WOF\Code\Models\CouponBar_VM;
	use MABEL_WOF\Code\Models\Wheel_Model;
	use MABEL_WOF\Code\Models\Wheels_VM;
	use MABEL_WOF\Code\Services\AC_Service;
	use MABEL_WOF\Code\Services\CK_Service;
	use MABEL_WOF\Code\Services\CM_service;
    use MABEL_WOF\Code\Services\Drip_Service;
    use MABEL_WOF\Code\Services\Email_Service;
	use MABEL_WOF\Code\Services\GR_Service;
	use MABEL_WOF\Code\Services\Integrations_Service;
	use MABEL_WOF\Code\Services\KV_Service;
	use MABEL_WOF\Code\Services\Log_Service;
	use MABEL_WOF\Code\Services\MailChimp_Service;
	use MABEL_WOF\Code\Services\Mailster_Service;
	use MABEL_WOF\Code\Services\ML_Service;
	use MABEL_WOF\Code\Services\Nl2Go_Service;
	use MABEL_WOF\Code\Services\RM_Service;
	use MABEL_WOF\Code\Services\SIB_Service;
	use MABEL_WOF\Code\Services\WC_Service;
	use MABEL_WOF\Code\Services\Wheel_service;
	use MABEL_WOF\Code\Services\WordPress_service;
	use MABEL_WOF\Core\Common\Frontend;
	use MABEL_WOF\Core\Common\Html;
	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Config_Manager;
	use MABEL_WOF\Core\Common\Managers\Script_Style_Manager;
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;

	if(!defined('ABSPATH')){die;}

	class Public_Controller extends Frontend
	{
		public function __construct()
		{
			parent::__construct();

			Script_Style_Manager::$frontend_js_var = 'wofVars';
			Script_Style_Manager::add_style(Config_Manager::$slug,'public/css/public.min.css');
			Script_Style_Manager::add_script(Config_Manager::$slug,'public/js/public.min.js','jquery');
			Script_Style_Manager::add_script_variable('baseUrl', Config_Manager::$url);

			add_action('wp_footer', [$this,'add_wheels'], 1);
			add_action('wp_footer', [$this,'add_couponbars'], 1);
			add_action('wp_footer', [$this,'add_nonce'] );
            add_action('template_redirect', [$this, 'maybe_add_gift_to_cart']);

			$this->add_ajax_function('wof-email-optin', $this, 'optin',true, true);
			$this->add_ajax_function('wof-play', $this, 'play',true, true);
			$this->add_ajax_function('wof-register-view', $this, 'register_view', true, true);

		}

		public function add_nonce() {
			echo '<div data-wof-nonce="'.wp_create_nonce('wof-nonce').'"></div>';
		}

        public function maybe_add_gift_to_cart() {

            if( isset( $_GET['wof-add-to-cart'] ) && isset( $_GET['wof-code'] ) ) {
                $id = intval( $_GET['wof-add-to-cart'] );
                $coupon = sanitize_text_field( trim( $_GET['wof-code'] ) );
                if( ! empty( $id ) && ! empty( $coupon ) && WC_Service::is_coupon_valid( $coupon) ) {
                    $this->add_gift_to_cart( $id, $coupon );
                }
            }
        }

        public function add_gift_to_cart( $product_id, $coupon_code = false ) {

            if( empty( $product_id ) || !is_int( $product_id ) ) {
                return;
            }

            $product = wc_get_product( $product_id );
            if( ! $product ) {
                return;
            }

            $is_already_in_cart = false;
            foreach( WC()->cart->get_cart() as $cart_item ) {
                if ( $cart_item['data']->get_id() === $product->get_id() ) {
                    $is_already_in_cart = true;
                    break;
                }
            }

            if( ! $is_already_in_cart ) WC()->cart->add_to_cart( $product->get_id() );

            if( $coupon_code !== false && ! empty( $product->get_price() ) ) {
                if ( ! WC()->cart->has_discount( $coupon_code ) ) {
                    WC()->cart->apply_coupon( $coupon_code );
                }
            }

        }

		public function play($wheel) {
			if(!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['action']) ||
			   !isset($_POST['seq']) || !isset($_POST['pseq']) )
				wp_send_json_error(__('Not allowed.',Config_Manager::$slug));

			if(empty($wheel))
				$wheel = Wheel_service::get_wheel($_POST['id']);

            if( empty( $wheel ) )
                wp_send_json_error(__('Wheel does not exist anymore.', Config_Manager::$slug));

            $fields = isset($_POST['fields']) ? json_decode(sanitize_text_field(stripslashes($_POST['fields']))) : [];

			$current_play = Wheel_service::validate_sequence($wheel, $_POST['seq'], $_POST['pseq']);

			if(!is_int($current_play))
				wp_send_json_error(__('Not allowed.',Config_Manager::$slug));

			$email = empty($_POST['mail']) ? '' : sanitize_email($_POST['mail']);

			$segment = Wheel_service::calculate_segment_hit($wheel);

			$segment = apply_filters('wof-calculated-slice', $segment, $wheel, $fields, $email);

            $calculated_segment_value = $this->calculate_segment_value($segment,$wheel,$fields,$email);
			$winning = $segment->type != 0;
			$wheel->plays_left = $wheel->plays+1-$current_play;
			$is_last = $current_play >= $wheel->plays+1;

			if($winning && $segment->type == 1 && Settings_Manager::get_setting('woo_coupons') === true) {
				$coupon_settings = Enumerable::from($wheel->coupon_settings)->firstOrDefault(function($x) use($segment){
					return $x->slice == $segment->id;
				});
				$coupon = WC_Service::create_coupon($wheel, $segment, Settings_Manager::get_setting('woo_coupon_duration'), Settings_Manager::get_setting('woo_coupon_timeperiod'),$coupon_settings);

				if($wheel->woo_auto_apply)
                    WC_Service::auto_apply_coupon($coupon);
				$segment->value = $coupon;
				$calculated_segment_value = $coupon;
			}

            $gift_coupon_code = null;
            if($winning && $segment->type == 5 && !empty( $segment->value ) ) {

                $product = wc_get_product( intval( $segment->value) );
                if( $product ) {
                    $gift_coupon_code = WC_Service::create_coupon_for_free_product( $product->get_id(), Settings_Manager::get_setting('woo_coupon_duration'), Settings_Manager::get_setting('woo_coupon_timeperiod') );
                    $calculated_segment_value = '<a href="' . wc_get_cart_url() . '" class="wof-color-2 button">' . __( 'View cart', 'woocommerce' ) .'</a>';
                    if( ! $wheel->send_emails || $wheel->winnings_only_in_email != 'true' ) {
                        $this->add_gift_to_cart( intval( $segment->value ), $gift_coupon_code );
                    }
                }


            }

			$log_type = Log_Service::type_of_logging($wheel);

			if($log_type === 'full' || $log_type === 'limit')
				Log_Service::log_play_to_db($wheel->id,empty($email) ? null : $email, $winning, $segment->id, $segment->label, $calculated_segment_value, $segment->type);

			$skip_sending_mail = false;
			if(has_filter('wof-skip-sending-email'))
				$skip_sending_mail = apply_filters('wof-skip-sending-email',$wheel->id);

			if(!empty($email) && !$skip_sending_mail) {

				add_filter( 'wp_mail_content_type', [ $this, 'wof_set_content_type' ] );
				add_filter( 'wp_mail_from', [ $this, 'wof_set_from_email' ] );
				add_filter( 'wp_mail_from_name', [ $this, 'wof_set_from_name' ] );

				if ( ($wheel->send_lost_email && !$winning) || ($wheel->send_emails && $winning)) {

                    $msg = $this->get_email_message( $segment, $wheel, $email, $winning, $fields, $gift_coupon_code );

                    if( ! empty( $msg ) ) {
                        wp_mail(
                            $email,
                            $this->get_email_subject( $segment, $wheel, $winning, $fields ),
                            $msg,
                            "Content-Type: text/html\r\n"
                        );
                    }

				}

				remove_filter( 'wp_mail_content_type', [ $this, 'wof_set_content_type' ] );
				remove_filter( 'wp_mail_from', [ $this, 'wof_set_from_email' ] );
				remove_filter( 'wp_mail_from_name', [ $this, 'wof_set_from_name' ] );
			}

			if($wheel->notify){
				$msg = str_replace(
					[
						'{wheel_name}',
						'{wheel_id}',
						'{all_fields}',
						'{slice_data}'
					],
					[
						$wheel->name,
						$wheel->id,
						$this->print_all_fields($email,$wheel->fields, $fields),
						$segment->label .($segment->type !== 0 && $segment->type !== 4 ? ' ('.$segment->value.')' : '' )
					],
					nl2br(htmlspecialchars_decode(
						empty($wheel->notify_message) ?
							__("Someone just played the wheel '{wheel_name}'. Here's where they landed on:<br/>{slice_data}<br/>And here's what they filled out on the form:<br/>{all_fields}",'mabel-wheel-of-fortune') :
							$wheel->notify_message
					))
				);
				wp_mail(
					$wheel->notify_email,
					empty($wheel->notify_subject) ?
						__("Someone played the wheel",'mabel-wheel-of-fortune') :
						sanitize_text_field($wheel->notify_subject),
					$msg,
					"Content-Type: text/html\r\n"
				);
			}

			$play_data = [
				'wheel' => $wheel->id,
				'wheel_name' => $wheel->name,
				'winning' => $winning,
				'segment_id' => $segment->id,
				'segment_type' => $segment->type,
				'segment_text' => $segment->label,
				'segment_prize' => $calculated_segment_value,
				'segment_chance' => $segment->chance,
				'play' => $current_play,
				'timestamp' => current_time('mysql'),
				'type' => 'play',
				'list_provider' => $this->provider_to_text($wheel->list_provider),
				'list_provider_id' => $wheel->list_provider,
                'fields' => $fields,
			];
			if(!empty($email))
				$play_data['email'] = $email;

			do_action('wof_play', array_merge($play_data, ['wheel_object' => $wheel] ));

			if($wheel->has_setting('play_webhook')) {
                $zapier_data = $play_data;
                $zapier_data = apply_filters('wof_zapier_play_values', $zapier_data);
                wp_remote_post($wheel->play_webhook, ['method' => 'POST','body' => $zapier_data] );
            }

			$return_arr = [
				'segment' => $segment->id,
				'type' => $segment->type,
				'winning' => $winning,
				'title' => $this->get_segment_title($wheel,$segment),
				'text' => $this->get_segment_text($wheel,$segment),
				'value' => $winning ? ($wheel->send_emails && $wheel->winnings_only_in_email == 'true' && $segment->type != 3 ? null : $calculated_segment_value) : null,
				'seq' => Wheel_service::get_sequence($wheel,$current_play+1),
			];

            if( $segment->type != 3 ) {

                if( $wheel->usage === 'sc' ) {

                    if( $winning ) {
                        $return_arr['html'] = '';
                    } else {
                        $return_arr['html'] = $is_last ? '' : Html::view('response-lost', $wheel);
                    }

                } else {
                    $return_arr['html'] = Html::view($winning ? 'response-done' : ($is_last ? 'response-done' : 'response-lost'), $wheel);
                }
            }

			wp_send_json_success($return_arr);
		}

		private function print_all_fields($email,$defined_fields,$fields) {
			$arr = ($email) ? [$email] : [];
			foreach($fields as $f){
				$defined = Enumerable::from($defined_fields)->firstOrDefault(function($x) use($f){return $x->id === $f->id;});
				$arr[] = $defined->placeholder.' : '. ($f->value ? $f->value : '-');
			}
			return join('<br/>',$arr);
		}

		private function calculate_segment_value($segment,$wheel,$fields,$email) {
		    return preg_replace_callback('/\{(.*)\}/', function ($matches) use ($wheel,$fields,$email) {
		        if(count($matches) === 2) {
                    $field = Enumerable::from($fields)->firstOrDefault(function($x) use ($matches){return $x->id === $matches[1];});
                    if($field != null) {
                        return $field->value;
                    }
                    switch ($matches[1]) {
                        case 'wheel_id' : return $wheel->id;
                        case 'wheel_name': return $wheel->name;
                        case 'email': return $email;
                    }
		        }
                return $matches[0];
            },$segment->value);
        }

        private function get_email_subject($segment, Wheel_Model $wheel, $winning = true, $fields = []) {
        	if(!$winning)
        		return $this->replace_field_names(sanitize_text_field($wheel->email_noprize_subject),$fields);

	        switch($segment->type) {
		        case 2: $subject = $wheel->email_link_subject; break;
		        case 3: $subject = empty($wheel->email_redirect_subject) ? $wheel->email_link_subject : $wheel->email_redirect_subject;break;
		        case 4: $subject = empty($wheel->email_html_subject) ? $wheel->email_link_subject : $wheel->email_html_subject;break;
		        case 4: $subject = $wheel->email_gift_subject;break;
		        case 1:default: $subject = $wheel->email_coupon_subject;
	        }
	        return $this->replace_field_names(sanitize_text_field($subject),$fields);
        }

        private function replace_field_names($msg, $fields) {
			if(empty($fields)) return $msg;

	        return preg_replace_callback('/\{field\..+?\}/', function ($matches) use ($fields) {
		        $field_id = str_replace( ['{field.','}'], '',$matches[0]);
		        $field = Enumerable::from($fields)->firstOrDefault(function($x) use ($field_id){return $x->id === $field_id;});
		        if($field != null) {
			        return $field->value;
		        }
		        return '';
	        },$msg);
        }

		private function get_email_message($segment, Wheel_Model $wheel, $email, $winning = true, $fields = [], $gift_coupon = '' ) {
			if(!$winning) {
				$msg = str_replace(
					[ '{label}','{email}' ],
					[$segment->label, $email],
					nl2br(htmlspecialchars_decode($wheel->email_noprize_message))
				);

				return $this->replace_field_names($msg, $fields);
			}
			switch($segment->type) {
				case 2: $msg = $wheel->email_link_message; break;
				case 3: $msg = empty($wheel->email_redirect_message) ? $wheel->email_link_message : $wheel->email_redirect_message; break;
				case 4: $msg = empty($wheel->email_html_message) ? $wheel->email_link_message : $wheel->email_html_message; break;
				case 5: $msg = $wheel->email_gift_message; break;
				case 1:
				default:$msg = $wheel->email_coupon_message;
			}

            if(empty( $msg)) $msg = '';

			$msg = nl2br(htmlspecialchars_decode($msg));

			$msg = str_replace(
				['{label}','{coupon}','{link}','{email}','{value}'],
				[$segment->label, $segment->value, $segment->value, $email, $segment->value],
				$msg
			);

            if( $segment->type == 5 ) {
                $product = wc_get_product( intval( $segment->value ) );
                if( $product ) {
                    $add_to_cart_url = add_query_arg( ['wof-add-to-cart' => $product->get_id(), 'wof-code' => $gift_coupon], wc_get_cart_url() );
                    $msg = str_replace(
                        ['{product_title}','{product_price}','{add_to_cart_url}','{add_to_cart_link}'],
                        [$product->get_name(), $product->get_price(), $add_to_cart_url , '<a href="' . $add_to_cart_url . '">' . __('Claim free gift', 'mabel-wheel-of-fortune') . '</a>' ],
                        $msg
                    );
                }
            }

			return $this->replace_field_names($msg, $fields);
		}

		public function wof_set_content_type(){
			return "text/html";
		}

		public function wof_set_from_email($email) {
			$admin_mail = get_bloginfo('admin_email');
			$admin_mail = empty($admin_mail) ? $email : $admin_mail;
			$admin_mail = apply_filters('wof-set-from-address',$admin_mail);

			return $admin_mail;
		}

		public function wof_set_from_name($name) {
			$site_title = get_bloginfo('name');
			return empty($site_title) ? $name : $site_title;
		}

		public function optin() {

			if(!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['seq']) || !isset($_POST['pseq']))
				wp_send_json_error(__('Not allowed.',Config_Manager::$slug));

			$wheel = Wheel_service::get_wheel(intval($_POST['id']));

            if( empty( $wheel ) )
                wp_send_json_error(__('Wheel does not exist anymore.',Config_Manager::$slug));

			$integration = Integrations_Service::get_integrations_by_id($wheel->list_provider);

			if($integration->needsEmail && !isset($_POST['mail']))
				wp_send_json_error(__('Not allowed.',Config_Manager::$slug));

			$email = empty($_POST['mail']) ? '' : sanitize_email($_POST['mail']);
			if($integration->needsEmail) {
				if(!Email_Service::is_valid_email($email))
					wp_send_json_error(__('Badly formatted email.', Config_Manager::$slug));

				if($wheel->check_mail_domains && !Email_Service::is_valid_email_domain($email))
					wp_send_json_error(
						$wheel->setting_or_default('invalid_mail_error', __('Email address is invalid.', 'mabel-wheel-of-fortune'))
					);
			}

			$fields = isset($_POST['fields']) ? json_decode(sanitize_text_field(stripslashes($_POST['fields']))) : [];

			for ($i = 0; $i < count($fields); $i++) {
				$fid = $fields[$i]->id;
				$wheel_field = Enumerable::from($wheel->fields)->firstOrDefault(function($x) use ($fid) { return $x->id === $fid;});
				if($wheel_field && isset($wheel_field->options)){
					$fields[$i]->options = (array) $wheel_field->options;
				}
			}

			$fields_without_type = Enumerable::from($fields)->select(function($x){
				return (object) ['id' => $x->id, 'value' => $x->value];
			})->toArray();

            $validation_result = ['is_valid' => true];

            $validation_result = apply_filters('wof-validate-optin',$validation_result,$wheel,$email,$fields);
            $validation_result = apply_filters('wof-validate-optin-'.$wheel->id,$validation_result,$wheel,$email,$fields);

			if($validation_result['is_valid'] === false)
				wp_send_json_error($validation_result['message']);

			$response = null;
			$allow_duplicates = $wheel->retries;
			$should_optin = true;

			if ( !$allow_duplicates && Log_Service::has_played_yet( $wheel,$integration, $email, -1, $out_type ) ) {

				wp_send_json_error(
					$out_type === 'mail' ? $wheel->setting_or_default( 'email_already_used', __('Email address already used.', 'mabel-wheel-of-fortune') ) :
						$wheel->setting_or_default( 'ip_used_error', __("You've already played.", 'mabel-wheel-of-fortune') )
				);

			} else {

				$days = -1;

				if($wheel->usage === 'sc' && $wheel->play_after === 'delay' && $wheel->play_after_delay > 0) {
					$days = $wheel->play_after_delay;
				}
				if( $wheel->usage === 'popup' && $wheel->occurance_after === 'delay' && $wheel-> occurance_after_delay > 0) {
					$days = $wheel->occurance_after_delay;
				}

				if( $days > 0 && Log_Service::has_played_yet( $wheel, $integration, $email, $days, $out_type )) {
					wp_send_json_error( $wheel->setting_or_default( 'ip_used_error', "You've already played. You can play again in a few days." ) );
				}
			}

			if($wheel->list_provider != 'custom' && $wheel->list_provider != 'none') {

				if ( $wheel->has_setting( 'optin_if_checked' ) && $wheel->list_provider !== 'chatfuel' ) {
					$checkboxes = explode( ',', $wheel->optin_if_checked );
					foreach ( $checkboxes as $checkbox ) {

						$field = Enumerable::from( $fields )->firstOrDefault( function ( $x ) use ( $checkbox ) {
							return $x->id === $checkbox;
						} );

						if ( $field != null && $field->value === false ) {
							$should_optin = false;
							break;
						}

					}
				}

				$should_optin = apply_filters( 'wof_should_optin', $should_optin, $wheel, $email, $fields );

				if ( $should_optin ) {

					$fieldsForProvider = Enumerable::from( $fields )->where( function ( $x ) {
						return $x->type !== 'consent_checkbox';
					} )->toArray();

					switch ( $wheel->list_provider ) {
						case 'cm' :
							$response = CM_service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'ac' :
							$response = AC_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'mailchimp' :
							$response = MailChimp_Service::add_to_list( $wheel->list, $email, $fieldsForProvider, $wheel->use_mailchimp_group ? $wheel->mailchimp_group : null );
							break;
						case 'wordpress' :
							$response = WordPress_service::add_optin( $wheel->id, $email, $fields_without_type );
							break;
						case 'gr' :
							$response = GR_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'ml' :
							$response = ML_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'kv' :
							$response = KV_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'mailster':
							$response = Mailster_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'rm':
							$response = RM_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'ck':
							$response = CK_Service::add_to_list( $wheel->list, $email, $fieldsForProvider );
							break;
						case 'newsletter2go':
							$response = Nl2Go_Service::add_to_list($wheel->list, $email, $fieldsForProvider);
							break;
						case 'sib':
							$response = SIB_Service::action('add to list', ['list' => $wheel->list, 'email' => $email, 'fields' => $fieldsForProvider]);
							break;
                        case 'drip':
                            $response = Drip_Service::add_to_list($wheel->list, $email, $fieldsForProvider);
                            break;

						default:
							$response = null;
					}

					if ( has_filter( 'wof-add-to-list-' . $wheel->list_provider ) ) {
						$response = apply_filters( 'wof-add-to-list-' . $wheel->list_provider, $wheel, $email, $fields, $allow_duplicates );
					}

					if ( is_string( $response ) ) {
						wp_send_json_error( __( $response, 'mabel-wheel-of-fortune' ) );
					}

					if ( $response === false ) {
						wp_send_json_error( $wheel->setting_or_default( 'email_already_used', __('Email address already used.', 'mabel-wheel-of-fortune') ) );
					}

				}

			}

			$optin_data = [
				'wheel' => $wheel->id,
				'wheel_name' => $wheel->name,
				'fields' => $fields_without_type,
				'list_provider' => $this->provider_to_text($wheel->list_provider),
				'list_provider_id' => $wheel->list_provider,
				'type' => 'optin',
				'timestamp' => current_time('mysql'),
				'opted_in' => $should_optin
			];

			if(!empty($email))
				$optin_data['email'] = $email;

			do_action('wof_optin', array_merge($optin_data, ['wheel_object' => $wheel] ));

			if($wheel->has_setting('optin_webhook')){
                $zapier_data = $optin_data;
                $zapier_data = apply_filters('wof_zapier_optin_values', $zapier_data);
                wp_remote_post($wheel->optin_webhook, ['method' => 'POST','body' => $zapier_data] );
            }


			$should_log = true;
			$log_type = Log_Service::type_of_logging($wheel);
			if($wheel->list_provider === 'wordpress' && $should_optin)
				$should_log = false;

			if($should_log)
				Log_Service::log_optin_to_db(
					$wheel->id,
					empty($email) ? null : $email,
					$log_type === 'full' ? $fields_without_type: null
				);

			Wheel_service::add_to_stats('optins', $_POST['id']);

			$this->play($wheel);

		}

		private function provider_to_text($provider){

			switch($provider) {

				case 'mailchimp' : return 'Mailchimp';
				case 'ac': return 'ActiveCampaign';
				case 'cm': return 'Campaign Monitor';
				case 'zbscrm': return 'Zero BS CRM';
				case 'wordpress': return 'WordPress';
				case 'none': return 'None';
				case 'custom': return 'Zapier';
				case 'gr': return 'GetResponse';
				case 'ml': return 'MailerLite';
				case 'kv': return 'Klaviyo';
				case 'mailster': return 'Mailster';
                case 'rm': return 'Remarkety';
				case 'chatfuel': return 'ChatFuel';
				case 'newsletter2go': return 'Newsletter2Go';
                case 'sib': return 'SendInBlue';
                case 'drip': return 'Drip';
			}

			return $provider;
		}

		public function register_view() {
			Wheel_service::add_to_stats('views', $_POST['id']);
			wp_send_json_success();
		}

		public function add_couponbars() {
			$wheels = Wheel_service::get_all_wheels();
			$model = new CouponBar_VM();

			foreach($wheels as $wheel) {
				if($wheel->coupon_bar) {
					$bar = new CouponBar();
					if(isset($wheel->bar_bgcolor))
						$bar->bgcolor = $wheel->bar_bgcolor;
					if(isset($wheel->bar_fgcolor))
						$bar->fgcolor = $wheel->bar_fgcolor;
					$bar->text = $wheel->get_coupon_bar_text();
					$bar->wheel_id = $wheel->id;
					$bar->duration = Settings_Manager::get_setting('woo_coupon_duration');
					$bar->timeframe = Settings_Manager::get_setting('woo_coupon_timeperiod');

					$bar = apply_filters('wof_coupon_bar',$bar, $wheel);

				 	array_push($model->coupon_bars, $bar);

				}
			}

			if(!empty($model->coupon_bars)) {
				Script_Style_Manager::publish_script(Config_Manager::$slug);
				Html::partial('code/views/couponbars', $model);
			}
		}

		public function add_wheels() {
			$model = new Wheels_VM();

			$wheels = $this->get_active_wheels();
            $model->wheels = apply_filters('wof_active_wheels', $wheels);

			if(!empty($model->wheels)) {

				Script_Style_Manager::publish_script(Config_Manager::$slug);
				Script_Style_Manager::add_script_vars();

				$html = Html::view('wheels', $model);
				$html = apply_filters('wof_wheels_html', $html);

				echo $html;
			}
		}


		private function get_segment_title(Wheel_Model $wheel, $segment) {
			if($segment->type == 0)
				return $wheel->has_setting('losing_title') ? $wheel->losing_title : __('Uh oh!', Config_Manager::$slug);

			return str_replace(
				'{x}',
				'<em>'.$segment->label.'</em>',
				$wheel->has_setting('winning_title')? $wheel->winning_title : __('Hurray!', Config_Manager::$slug)
			);
		}

		private function get_segment_text(Wheel_Model $wheel, $segment) {
			switch($segment->type){
				case 0: return $wheel->losing_text;
				case 1: return $wheel->winning_text_coupon;
				case 2:
				case 3: return $wheel->winning_text_link;
				case 4: return $wheel->winning_text_texthtml;
                case 5: return $wheel->winning_text_gift;
			}
			return $wheel->losing_text;
		}

		private function get_active_wheels() {
			$wheels = Wheel_service::get_all_wheels();
			$allowed_wheels = [];

			foreach($wheels as $wheel) {

				if($wheel->active != 1 || $wheel->usage !== 'popup')
					continue;

				if(!Wheel_service::can_show_to_user($wheel))
					continue;
				if(!Wheel_service::can_show_on_page($wheel))
					continue;
				if(!Wheel_service::can_show_on_language($wheel))
					continue;

				array_push($allowed_wheels,$wheel);
			}

			return $allowed_wheels;
		}
	}
}