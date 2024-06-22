<?php

namespace MABEL_WOF\Code\Services
{

	use MABEL_WOF\Code\Models\Wheel_Model;
	use MABEL_WOF\Core\Common\Linq\Enumerable;

	class Wheel_service
	{
		private static $wheel_cache = null;
		private static $post_type = 'mb_woc_wheel';
		private static $allow_html_minimal = [
			'a' => [
				'href' => [],
				'title' => [],
				'target' => []
			],
			'br' => [],
			'p' => [ 'class' => [], 'style' => [] ],
			'b' => [],
			'em' => [],
			'strong' => [],
			'i' => [],
			'span' => [ 'style' => [],'class' => [] ],
			'ul' => [],
			'ol' => [],
			'li' => []
		];

		public static function can_show_to_user(Wheel_Model $wheel) {
			if(empty($wheel->user_inclusion) || $wheel->user_inclusion == 0)
				return true;
			if($wheel->user_inclusion == 1 && is_user_logged_in())
				return true;
			if($wheel->user_inclusion == 2 && !is_user_logged_in())
				return true;

			return false;
		}

		public static function can_show_on_language(Wheel_Model $wheel){

			if(!defined('ICL_LANGUAGE_CODE')) return true;

			if($wheel->wpml_options === null || $wheel->wpml_options === '') return false;

			$show_on_languages = explode(';',$wheel->wpml_options);

			if(in_array('-1', $show_on_languages))
				return true;

			if(in_array(ICL_LANGUAGE_CODE, $show_on_languages)) return true;

			return false;
		}

		public static function can_show_on_page(Wheel_Model $wheel)	{
			if($wheel->show_on_pages === null || $wheel->show_on_pages === '') return false;

			$show_on_pages = explode(';',$wheel->show_on_pages);

			if(in_array('-1', $show_on_pages))
				return true;
			if(is_front_page() && in_array('-2', $show_on_pages))
				return true;
			if(is_home() && in_array('-3', $show_on_pages))
				return true;
			if(is_singular('post') && in_array('-4', $show_on_pages))
				return true;
			if(function_exists('is_product') && is_product() && in_array('-5', $show_on_pages))
				return true;

			if(in_array('-6', $show_on_pages) && function_exists('is_order_received_page') && is_order_received_page())
				return true;
			if(in_array('-7', $show_on_pages) && function_exists('is_view_order_page') && is_view_order_page())
				return true;
			if(in_array('cpt-'.get_post_type(), $show_on_pages))
				return true;

            if(function_exists('wc_get_page_id')) {
                if(in_array(wc_get_page_id('shop'), $show_on_pages) && is_shop())
                    return true;
            }

			$queried_object = get_queried_object();

			if ($queried_object) {
				if(isset($queried_object->ID)){
					if(in_array($queried_object->ID, $show_on_pages))
						return true;
				}

                $product_category_pages = Enumerable::from($show_on_pages)->where(function($x){
                    return substr($x, 0, 5) === 'wcpc-';
                })->toArray();

                if(!empty($product_category_pages) && function_exists('is_product') && is_product()) {
                    $terms = wp_get_post_terms( $queried_object->ID, 'product_cat' );
                    foreach($product_category_pages as $pcp) {
                        if(Enumerable::from($terms)->any(function($x) use($pcp){
                            return $x->term_id == str_replace('wcpc-','',$pcp);
                        }))
                            return true;
                    }

                }
			}



			return false;
		}

		public static function get_sequence(Wheel_Model $w, $play = 1) {
			return Helper_Service::encrypt(join('::', [
				$w->id,
				$play,
				time()-1505392740
			]));
		}

		public static function validate_sequence(Wheel_Model $wheel,$sequence, $psequence) {
			$seq = explode('::',Helper_Service::decrypt($sequence));
			$pseq = explode('::',Helper_Service::decrypt($psequence));
			$current_play = intval($seq[1]);
			$previous_play = intval($pseq[1]);

			if((intval($wheel->plays)+1) < $current_play) return false;
			if(sizeof($seq) != 3 || sizeof($pseq) != 3) return false;
			if($wheel->id != $seq[0] || $wheel->id != $pseq[0]) return false;
			if($current_play > 1 && $sequence === $psequence) return false;
			if($previous_play > $current_play) return false;
			if($current_play === $previous_play && $current_play != 1) return false;

			if($previous_play != $current_play) {
				$time = intval($seq[2]);
				if((time()-1505392740) - $time > 1200) return false;
			}

			return $current_play;
		}

		private static function pick_losing_segment($segments){
			$losing_segments = Enumerable::from($segments)->where(function($x){return $x->type == 0;})->toArray();
			$losing_segment = $losing_segments[array_rand($losing_segments)];
			return $losing_segment;
		}

		public static function calculate_segment_hit(Wheel_Model $wheel) {
			$winning_chance = floatval($wheel->winning_chance);

			if($winning_chance === 0) $will_win = false;
			else $will_win = mt_rand(0,100) <= $winning_chance;

			if(!$will_win) {
				return self::pick_losing_segment($wheel->slices);
			} else { 

				$max_rand = 100;
				$winning_segments = Enumerable::from($wheel->slices)->where(function($x){return $x->type != 0 && $x->chance != 0;})->toArray();

				if($wheel->limit_prizes) { 
					$winning_segments = WordPress_service::filter_segments_by_prize_limit($winning_segments,$wheel->id);

					if(empty($winning_segments)) 
						return self::pick_losing_segment($wheel->slices);

					$max_rand = 0;
					foreach($winning_segments as $segment) {
						$max_rand = $max_rand + floatval($segment->chance);
					}
				}

				usort($winning_segments, function($a,$b) {
					if(floatval($a->chance) === floatval($b->chance)) return 0;
					return (floatval($a->chance) < floatval($b->chance)) ? -1 : 1;
				});

				$rand = mt_rand(1, $max_rand);
				$cumul = 0;
				foreach($winning_segments as $segment) {
					$cumul += floatval($segment->chance);
					if($rand <= $cumul)
						return $segment;
				}

				return null;

			}
		}

		public static function raw_to_wheel($raw) {

			$wheel = new Wheel_Model();
			$options = $raw['options'];

			$wheel->id = $raw['id'];
			$wheel->active = $raw['active'];
			$wheel->theme = $options->theme;
			if(isset($options->amount_of_slices))
				$wheel->amount_of_slices = $options->amount_of_slices;
			$wheel->slices = $options->slices;
			$wheel->winning_chance = $options->winning_chance;
			$wheel->limit_prizes = $options->limit_prizes;

			if(!empty($options->name ))
				$wheel->name = $options->name;

			if(!empty($options->usage ))
				$wheel->usage = $options->usage;

			if(!empty($options->plays))
				$wheel->plays = $options->plays;

			if(!empty($options->title))
				$wheel->title = wp_kses($options->title, self::$allow_html_minimal);

			$wheel->bgpattern = $options->bgpattern;

			if(!empty($options->explainer))
				$wheel->explainer = wp_kses($options->explainer, self::$allow_html_minimal);
			if(!empty($options->disclaimer))
				$wheel->disclaimer = wp_kses($options->disclaimer, self::$allow_html_minimal);
			if(!empty($options->email_placeholder))
				$wheel->email_placeholder = wp_strip_all_tags($options->email_placeholder);
			if(!empty($options->button_text))
				$wheel->button_text = wp_strip_all_tags($options->button_text);
			if(!empty($options->close_text))
				$wheel->close_text = wp_strip_all_tags($options->close_text);

			if(!empty($options->list_provider))
				$wheel->list_provider = $options->list_provider;
			if(!empty($options->list))
				$wheel->list = $options->list;

			if(!empty($options->losing_title))
				$wheel->losing_title = $options->losing_title;
			if(!empty($options->winning_title))
				$wheel->winning_title = $options->winning_title;
			if(!empty($options->losing_text))
				$wheel->losing_text = $options->losing_text;
			if(!empty($options->winning_text_coupon))
				$wheel->winning_text_coupon = $options->winning_text_coupon;
            if(!empty($options->winning_text_gift))
                $wheel->winning_text_gift = $options->winning_text_gift;

            if(!empty($options->winning_text_link))
				$wheel->winning_text_link = $options->winning_text_link;
			if(!empty($options->winning_text_texthtml))
				$wheel->winning_text_texthtml = $options->winning_text_texthtml;
			if(!empty($options->button_done))
				$wheel->button_done = $options->button_done;
			if(!empty($options->button_again))
				$wheel->button_again = $options->button_again;
			if(!empty($options->games_left_text))
				$wheel->games_left_text = $options->games_left_text;
			if(!empty($options->email_already_used))
				$wheel->email_already_used = $options->email_already_used;

			if(!empty($options->show_on_pages))
				$wheel->show_on_pages = $options->show_on_pages;
			if(!empty($options->user_inclusion))
				$wheel->user_inclusion = $options->user_inclusion;
			if(!empty($options->wpml_options))
				$wheel->wpml_options = $options->wpml_options;
			if(!empty($options->appeartype))
				$wheel->appeartype = $options->appeartype;
			if(!empty($options->appearscroll))
				$wheel->appearscroll = $options->appearscroll;
			if(!empty($options->appeardelay))
				$wheel->appeardelay = $options->appeardelay;
			if(!empty($options->appearclass))
				$wheel->appearclass = $options->appearclass;
			if(!empty($options->occurance))
				$wheel->occurance = $options->occurance;
			if(!empty($options->occurancedelay))
				$wheel->occurancedelay = intval($options->occurancedelay);

			if(isset($options->hide_mobile))
				$wheel->hide_mobile = $options->hide_mobile;
            if(isset($options->hide_desktop))
                $wheel->hide_desktop = $options->hide_desktop;
            if(isset($options->hide_tablet))
                $wheel->hide_tablet = $options->hide_tablet;
            if(isset($options->esc_key))
                $wheel->esc_key = $options->esc_key;
			if(isset($options->sound))
				$wheel->sound = $options->sound;
            if(isset($options->pointer))
                $wheel->pointer = $options->pointer;
			if(isset($options->confetti))
				$wheel->confetti = $options->confetti;
            if(isset($options->spinspeed))
                $wheel->spinspeed = intval($options->spinspeed);
            if(isset($options->spintime))
                $wheel->spintime = intval($options->spintime);

			if(!empty($options->bgcolor))
				$wheel->bgcolor = $options->bgcolor;
			if(!empty($options->fgcolor))
				$wheel->fgcolor = $options->fgcolor;
			if(!empty($options->secondary_color))
				$wheel->secondary_color = $options->secondary_color;
			if(!empty($options->pointer_color))
				$wheel->pointer_color = $options->pointer_color;
			if(!empty($options->button_bgcolor))
				$wheel->button_bgcolor = $options->button_bgcolor;
			if(!empty($options->button_fgcolor))
				$wheel->button_fgcolor = $options->button_fgcolor;
			if(!empty($options->error_color))
				$wheel->error_color = $options->error_color;
			if(!empty($options->wheel_color))
				$wheel->wheel_color = $options->wheel_color;
			if(!empty($options->dots_color))
				$wheel->dots_color = $options->dots_color;
			if(!empty($options->logo))
				$wheel->logo = $options->logo;
			if(!empty($options->custom_bg))
				$wheel->custom_bg = $options->custom_bg;
			if(isset($options->shadows))
				$wheel->shadows = $options->shadows;
			if(isset($options->handles))
				$wheel->handles = $options->handles;

			if(!empty($options->fields))
				$wheel->fields = $options->fields;
			if(!empty($options->coupon_settings))
				$wheel->coupon_settings = $options->coupon_settings;

			if(isset($options->use_mailchimp_group))
				$wheel->use_mailchimp_group = $options->use_mailchimp_group;
			if(isset($options->mailchimp_group))
				$wheel->mailchimp_group = $options->mailchimp_group;

			if(isset($options->send_emails))
				$wheel->send_emails = $options->send_emails;
			else $wheel->send_emails = false;

			if(isset($options->notify))
				$wheel->notify = $options->notify;
			else $wheel->notify = false;
			if(isset($options->notify_email))
				$wheel->notify_email = $options->notify_email;
			if(isset($options->notify_message))
				$wheel->notify_message = $options->notify_message;
			if(isset($options->notify_subject))
				$wheel->notify_subject = $options->notify_subject;

			if(isset($options->send_lost_email))
				$wheel->send_lost_email = $options->send_lost_email;
			else $wheel->send_lost_email = false;
			if(isset($options->winnings_only_in_email))
				$wheel->winnings_only_in_email = $options->winnings_only_in_email;
			else $wheel->winnings_only_in_email = false;

			if(isset($options->email_coupon_subject))
				$wheel->email_coupon_subject = $options->email_coupon_subject;
			if(isset($options->email_coupon_message))
				$wheel->email_coupon_message = $options->email_coupon_message;
            if(isset($options->email_gift_subject))
                $wheel->email_gift_subject = $options->email_gift_subject;
            if(isset($options->email_gift_message))
                $wheel->email_gift_message = $options->email_gift_message;
			if(isset($options->email_link_subject))
				$wheel->email_link_subject = $options->email_link_subject;
			if(isset($options->email_link_message))
				$wheel->email_link_message = $options->email_link_message;
			if(isset($options->email_html_message))
				$wheel->email_html_message = $options->email_html_message;
			if(isset($options->email_html_subject))
				$wheel->email_html_subject = $options->email_html_subject;
			if(isset($options->email_redirect_message))
				$wheel->email_redirect_message = $options->email_redirect_message;
			if(isset($options->email_redirect_subject))
				$wheel->email_redirect_subject = $options->email_redirect_subject;
			if(isset($options->email_noprize_message))
				$wheel->email_noprize_message = $options->email_noprize_message;
			if(isset($options->email_noprize_subject))
				$wheel->email_noprize_subject = $options->email_noprize_subject;

			if(isset($options->optin_webhook))
				$wheel->optin_webhook = $options->optin_webhook;
			if(isset($options->play_webhook))
				$wheel->play_webhook = $options->play_webhook;

			if(isset($options->check_mail_domains))
				$wheel->check_mail_domains = $options->check_mail_domains;
			else $wheel->check_mail_domains = false;
			if(isset($options->invalid_mail_error))
				$wheel->invalid_mail_error = $options->invalid_mail_error;

			if(isset($options->retries))
				$wheel->retries = $options->retries;
			else $wheel->retries = false;
			if(!empty($options->occurance_after))
				$wheel->occurance_after = $options->occurance_after;
			if(!empty($options->occurance_after_delay))
				$wheel->occurance_after_delay = intval($options->occurance_after_delay);
			if(!empty($options->play_after_delay))
				$wheel->play_after_delay = intval($options->play_after_delay);
			if(!empty($options->play_after))
				$wheel->play_after = $options->play_after;

			if(!empty($options->widget))
				$wheel->widget = $options->widget;
			if(!empty($options->widget_bgcolor))
				$wheel->widget_bgcolor = $options->widget_bgcolor;
			if(!empty($options->widget_position))
				$wheel->widget_position = $options->widget_position;
			if(!empty($options->widget_text))
				$wheel->widget_text = $options->widget_text;

			if(!empty($options->coupon_bar))
				$wheel->coupon_bar = $options->coupon_bar;
			else $wheel->coupon_bar = false;
            if(!empty($options->woo_auto_apply))
                $wheel->woo_auto_apply = $options->woo_auto_apply;
            else $wheel->woo_auto_apply = false;
			if(!empty($options->bar_text))
				$wheel->bar_text = $options->bar_text;
			if(!empty($options->bar_days))
				$wheel->bar_days = $options->bar_days;
			if(!empty($options->bar_hours))
				$wheel->bar_hours = $options->bar_hours;
			if(!empty($options->bar_minutes))
				$wheel->bar_minutes = $options->bar_minutes;
			if(!empty($options->bar_seconds))
				$wheel->bar_seconds = $options->bar_seconds;
			if(!empty($options->bar_fgcolor))
				$wheel->bar_fgcolor = $options->bar_fgcolor;
			if(!empty($options->bar_bgcolor))
				$wheel->bar_bgcolor = $options->bar_bgcolor;

			$wheel->log_ips = !empty($options->log_ips) ? $options->log_ips : false;
			if(!empty($options->ip_used_error))
				$wheel->ip_used_error = $options->ip_used_error;

			$wheel->log = !empty($options->log) ? $options->log : false;

			$wheel->enable_fb = !empty($options->enable_fb) ? $options->enable_fb : false;
			$wheel->fb_obligated = !empty($options->fb_obligated) ? $options->fb_obligated : false;

			if(!empty($options->optin_if_checked))
				$wheel->optin_if_checked = $options->optin_if_checked;

			$wheel->popup_theme = '';
			if(isset($options->popup_theme))
				$wheel->popup_theme = $options->popup_theme;

			return $wheel;
		}

		public static function toggle_activation($id, $toggle) {
			wp_update_post( [
				'ID' => $id,
				'post_status' => $toggle == 1 ? 'publish' : 'draft'
			]);
		}

		public static function get_wheel($id) {
			$post = get_post($id);

            if( empty( $post ) ) {
                return false;
            }

			$wheel = [
				'id' => $post->ID,
				'options'  => json_decode(get_post_meta($post->ID,'options',true)),
				'active' => $post->post_status === 'publish' ? 1 : 0
			];

			return self::raw_to_wheel($wheel);
		}

		public static function get_all_statistics() {
			$post_ids = new \WP_Query( [
				'post_type' =>  self::$post_type,
				'fields' => 'ids',
				'posts_per_page' => -1
			] );

			$stats = [];

			foreach ($post_ids->posts as $id) {
				$stat = json_decode(get_post_meta($id, 'stats', true));
				$stat->wheel_id = $id;
				$stat->rate = $stat->optins == 0 ? 0 : round(($stat->optins/$stat->views)*100,2);
				array_push($stats, $stat);
			}

			return $stats;
		}

		public static function get_all_wheels() {

			if(self::$wheel_cache != null){
				return self::$wheel_cache;
			}

			$post_ids = new \WP_Query( [
				'post_type' => self::$post_type,
				'fields' => 'ids',
				'posts_per_page' => -1
			]);

			$wheels = [];

			foreach ($post_ids->posts as $id) {
				$obj = [
					'id' => $id,
					'options' => json_decode(get_post_meta($id, 'options', true)),
					'active' =>  get_post_status($id) === 'publish' ? 1 : 0
				];
				array_push($wheels,self::raw_to_wheel($obj));
			}

			wp_reset_postdata();
			self::$wheel_cache = $wheels;

			return $wheels;
		}

		public static function delete_wheel($id) {

            $id = intval( $id );

            if( ! empty( $id ) ) {
                $post_type = get_post_type( $id );
                if( $post_type === 'mb_woc_wheel' ) {
                    wp_delete_post( $id, true );
                }
            }

		}

		public static function edit_wheel($id,$data) {
			update_post_meta($id,'options', $data);
		}

		public static function add_to_stats($type, $id) {

            if( ! empty( $type ) && ! empty( $id ) ) {

                $id = intval( $id );
                if( $id > 0 ) {

                    $raw_stat = get_post_meta( $id, 'stats', true );
                    if( ! empty( $raw_stat) ) {
                        $stat = json_decode( $raw_stat );
                        if( ! empty( $stat ) ) {
                            $stat->{$type} = $stat->{$type} + 1;
                            update_post_meta( $id,'stats', json_encode( $stat ) );
                        }
                    }


                }
            }
        }

		public static function add_wheel($data) {

			$id = wp_insert_post( [
				'post_type' => self::$post_type,
				'post_status' => 'publish'
			], true);

			if(!is_wp_error( $id ) && $id > 0){
				add_post_meta($id,'options', $data);
				add_post_meta($id, 'stats', json_encode( [
					'views' => 0,
					'optins' => 0
				]));

				return $id;
			}
			return null;
		}
	}
}