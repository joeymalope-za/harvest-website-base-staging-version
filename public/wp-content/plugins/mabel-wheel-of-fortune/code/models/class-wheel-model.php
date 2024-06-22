<?php

namespace MABEL_WOF\Code\Models {

	use MABEL_WOF\Code\Services\Integrations_Service;
	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Config_Manager;

	class Coupon_Settings {
		public $slice;
		public $include_products;
		public $exclude_products;
	}

	class Wheel_Model {
		public $is_preview = false;
		public $id;
		public $name = '';
		public $usage = 'popup';
		public $active = '1';
		public $theme = 'blue';

		public $winning_chance = 70;
		public $limit_prizes = false;
		public $plays = 0;
		public $amount_of_slices = 12;
		public $slices = [];

		public $bgpattern = 'hearts';

		public $title;
		public $explainer;
		public $disclaimer;
		public $email_placeholder;
		public $button_text;
		public $close_text;

		public $show_on_pages; 
		public $wpml_options = '-1';
		public $user_inclusion = '0';
		public $appeartype = 'exit';
		public $appeardelay = 5;
		public $appearclass;
		public $appearscroll = 60;
		public $occurance = 'session';
		public $occurancedelay = 14;

		public $list_provider;
		public $list;

		public $losing_title;
		public $losing_text;
		public $winning_title;
		public $winning_text_coupon;
		public $winning_text_gift;
		public $winning_text_link;
		public $winning_text_texthtml;
		public $button_done;
		public $button_again;
		public $games_left_text;
		public $email_already_used;

		public $hide_mobile = true;
		public $hide_desktop = false;
		public $hide_tablet = false;
		public $esc_key = false;
		public $sound = false;
		public $pointer = true;
		public $confetti = false;
        public $spinspeed = 2;
        public $spintime = 7;

		public $bgcolor;
		public $fgcolor;
		public $secondary_color;
		public $pointer_color;
		public $button_bgcolor;
		public $button_fgcolor;
		public $handles = true;
		public $shadows = true;
		public $error_color;
		public $wheel_color = 'white';
		public $dots_color = 'black';
		public $logo;
		public $custom_bg;
		public $standalone;

		public $fields = [];
		public $coupon_settings = [];

		public $use_mailchimp_group;
		public $mailchimp_group;

		public $send_emails;
		public $notify;
		public $notify_message = '';
		public $notify_subject = '';
		public $notify_email = '';
		public $winnings_only_in_email;
		public $email_coupon_subject;
		public $email_coupon_message;
        public $email_gift_subject;
        public $email_gift_message;
		public $email_link_subject;
		public $email_link_message;
		public $email_redirect_subject;
		public $email_redirect_message;
		public $email_html_subject;
		public $email_html_message;
		public $email_noprize_subject;
		public $email_noprize_message;


		public $optin_webhook;
		public $play_webhook;

		public $check_mail_domains;
		public $invalid_mail_error;

		public $retries;
		public $occurance_after = 'session';
		public $occurance_after_delay = 5;
		public $play_after_delay = 1;
		public $play_after = 'page';

		public $widget = 'none';
		public $widget_bgcolor;
		public $widget_position = 'left';
		public $widget_text;

		public $coupon_bar = false;
		public $woo_auto_apply = false;
		public $bar_text;
		public $bar_days;
		public $bar_hours;
		public $bar_minutes;
		public $bar_seconds;
		public $bar_fgcolor;
		public $bar_bgcolor;

		public $log_ips = false;
		public $ip_used_error;
		public $log = false;

		public $enable_fb = false;
		public $fb_obligated = false;

		public $optin_if_checked;

		public $popup_theme = '';

		public function get_coupon_bar_text($code = '') {
			$search = ['{code}','{countdown}'];
			$replace = [
				'<span class="wof-bar-code">'.$code.'</span>',
				'<div class="wof-bar-timer">
					<span class="wof-bar-d" data-text="'.htmlspecialchars($this->bar_days).'"></span>
					<span class="wof-bar-h" data-text="'.htmlspecialchars($this->bar_hours).'"></span>
					<span class="wof-bar-m" data-text="'.htmlspecialchars($this->bar_minutes).'"></span>
					<span class="wof-bar-s" data-text="'.htmlspecialchars($this->bar_seconds).'"></span></div>'
			];

			return str_replace($search,$replace,$this->bar_text);
		}

		public function get_options_for_frontend() {
			$options = [];
			$options['appear'] = $this->appeartype;

			$appearances = explode(';',$this->appeartype);

			if(in_array('delay',$appearances)){
				$options['delay'] = $this->appeardelay;
			}
			if(in_array('click',$appearances)){
				$options['selector'] = $this->appearclass;
			}
			if(in_array('scroll',$appearances)){
				$options['scroll'] = $this->appearscroll;
			}

			switch($this->occurance) {
				case 'delay':
					$options['occurance'] = 'time';
					$options['occuranceData'] = $this->occurancedelay;
					break;
				default:
					$options['occurance'] = $this->occurance;
					break;
			}

			$options['hideMobile'] = $this->hide_mobile;
			$options['hideTablet'] = $this->hide_tablet;
			$options['hideDesktop'] = $this->hide_desktop;
			$options['sound'] = $this->sound;
			$options['confetti'] = $this->confetti;
			$options['plays'] = $this->plays;
			$options['retry'] = $this->retries;

			if($this->retries) {
				switch($this->occurance_after) {
					case 'delay':
						$options['retryOccurance'] = 'time';
						$options['retryOccuranceData'] = $this->occurance_after_delay;
						break;
					default:
						$options['retryOccurance'] = $this->occurance_after;
						break;
				}
			}

			return htmlspecialchars(json_encode($options), ENT_QUOTES, 'UTF-8');
		}

		public function classes() {
			$classes = [
				'wof-wheel',
				'wof-theme-'.$this->theme,
			];
			if($this !== 'sc' && isset($this->popup_theme) && $this->popup_theme === 'fullscreen')
				$classes[] = 'wof-fs';

			return join(' ', $classes);
		}

		public function data_attributes() {

			$integration = Integrations_Service::get_integrations_by_id($this->list_provider);

			if($integration->needsEmail) {
				$primary_email_field = Enumerable::from( $this->fields )->firstOrDefault( function ( $x ) {
					return $x->id === 'primary_email';
				} );
				if ( ! $primary_email_field ) {
					array_unshift( $this->fields, (object) [
						'id'          => 'primary_email',
						'placeholder' => $this->email_placeholder,
						'required'    => true,
						'type'        => 'primary_email'
					] );
				}
			}

			$elements = [
				'id'            => $this->id,
				'options'       => esc_attr($this->get_options_for_frontend()),
				'standalone'    => $this->standalone,
				'fields'        => $this->list_provider === 'none' ? '' : esc_attr(json_encode($this->fields)),
				'slice-count'   => count($this->slices),
                'time'          => $this->spintime,
                'speed'         => $this->spinspeed,
                'pointer'       => $this->pointer
			];

			return join(' ', Enumerable::from($elements)->select(function($v,$k){
				return 'data-'.$k .'="'.esc_attr($v) .'"';
			})->toArray());
		}

		public function has_setting($key) {
			return !empty($this->{$key});
		}

		public function setting_or_default($key,$default){
			return $this->has_setting($key) ? $this->{$key} : $default;
		}

		public function get_background_bottom() {
			if(!empty($this->custom_bg))
				return '';

			$url = Config_Manager::$url . 'public/img/';
			if($this->theme === 'christmas' && $this->bgpattern === 'christmas')
				return 'background-image:url(\''.$url.'bg-bottom-winter.png\')';

			return '';
		}

		public function get_background() {

			if(!empty($this->custom_bg))
				return 'background-image:url(\''.esc_url($this->custom_bg).'\');background-position:center center;background-size:cover;';
			$url = Config_Manager::$url . 'public/img/';
			switch($this->bgpattern){
				case 'none': return ''; break;
				case 'hearts':
					return 'background-image:url(\''.$url.'bg-hearts.png\');opacity:.085;background-size:11%;';
					break;
				case 'swirl-light':
					return 'background-image:url(\''.$url.'bg-swirl-light.png\');opacity:.22;background-size:50%;';
					break;
				case 'swirl-dark':
					return 'background-image:url(\''.$url.'bg-swirl-dark.png\');opacity:.22;background-size:50%;';
					break;
				case 'hypnotize':
					return 'background-image:url(\''.$url.'bg-hypnotize.png\');opacity:.25;background-size:35%;';
					break;
				case 'vintage':
					return 'background-image:url(\''.$url.'bg-vintage.png\');opacity:.35;background-size:40%;';
					break;
				case 'halloween':
					return 'background-image:url(\''.$url.'bg-halloween.png\');opacity:.3;background-size:60%;';
					break;
				case 'christmas':
					return 'background-image:url(\''.$url.'bg-christmas.png\');opacity:.5;background-size:50%;';
					break;
				case 'memphis-light':
					return 'background-image:url(\''.$url.'bg-memphis-light.png\');opacity:.1;background-size:30%;';
					break;
				case 'memphis-dark':
					return 'background-image:url(\''.$url.'bg-memphis-dark.png\');opacity:.5;background-size:30%;';
					break;
				case 'waves-light':
					return 'background-image:url(\''.$url.'bg-waves-light.png\');opacity:.06;background-size:23%;';
					break;
				case 'waves-dark':
					return 'background-image:url(\''.$url.'bg-waves-dark.png\');opacity:.5;background-size:23%;';
					break;
				case 'waves-alt-light':
					return 'background-image:url(\''.$url.'bg-waves-alt-light.png\');opacity:.06;background-size:20%;';
					break;
				case 'waves-alt-dark':
					return 'background-image:url(\''.$url.'bg-waves-alt-dark.png\');opacity:.06;background-size:20%;';
					break;
				case 'ethnic-light':
					return 'background-image:url(\''.$url.'bg-ethnic-light.png\');opacity:.1;background-size:45%;';
					break;
				case 'ethnic-dark':
					return 'background-image:url(\''.$url.'bg-ethnic-dark.png\');opacity:.1;background-size:45%;';
					break;
			}

			return '';
		}

		private function get_coordinates_for_percent($percent,$slice_idx) {
			$start_percent = $percent * $slice_idx;
			$end_percent = $percent * ($slice_idx+1);
			$start_x = cos(2 * M_PI * $start_percent);
			$start_y = sin(2 * M_PI * $start_percent);
			$end_x = cos(2 * M_PI * $end_percent);
			$end_y = sin(2 * M_PI * $end_percent);
			return (object) [
				'start' => (object) ['x' => $start_x,'y' => $start_y],
				'end' => (object) ['x' => $end_x,'y' => $end_y]
			];
		}

		public function create_slice_path($slice_idx,$total_slices,$color) {
			$percent = (100/$total_slices)/100;
			$coords = $this->get_coordinates_for_percent($percent,$slice_idx);
			$path = [
				'M '.$coords->start->x.' '.$coords->start->y,
				'A 1 1 0 0 1 '.$coords->end->x.' '.$coords->end->y,
				'L 0 0'
			];

			return '<path stroke="'.$color.'" stroke-width="0.0025" class="wof-slice-bg" data-slice="'.($slice_idx+1).'" fill="'.$color.'" d="'.join(' ',$path).'"></path>';
		}

		public function get_dasharray($slice) {
			$offset = (($slice->id-1)/count($this->slices))*100;
			$percent = ((1/count($this->slices))+.05) * 100;

			return '0px ' . $offset . 'px ' . $percent . 'px 100px';
		}

	}
}