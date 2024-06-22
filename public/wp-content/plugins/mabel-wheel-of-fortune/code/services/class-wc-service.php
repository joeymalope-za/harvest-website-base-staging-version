<?php

namespace MABEL_WOF\Code\Services
{

	use MABEL_WOF\Core\Common\Linq\Enumerable;

	class WC_Service {

        public static function is_coupon_valid( $code ) {
            $coupon = new \WC_Coupon( $code );
            return $coupon->get_amount() == 100 && $coupon->get_status() === 'publish' && is_int( $coupon->get_id() );
        }

        public static function create_coupon_for_free_product($product_id, $duration, $time_interval) {

            $quantifiers = [
                'minutes' => 60,
                'hours' => 3600,
                'days' => 86400
            ];
            $expiry_unix = current_time('timestamp', true) + ($duration * $quantifiers[$time_interval] );
            $coupon_code = strtolower( self::get_random_coupon_code() );

            $coupon = new \WC_Coupon();
            $coupon->set_code( $coupon_code );
            $coupon->set_amount( 100 );
            $coupon->set_discount_type( 'percent' );
            $coupon->set_date_expires( $expiry_unix );
            $coupon->set_individual_use( true );
            $coupon->set_limit_usage_to_x_items(1);
            $coupon->set_usage_limit( 1 );
            $coupon->set_product_ids( [ $product_id ] );

            $coupon->save();

            return $coupon_code;

        }

		public static function create_coupon($wheel,$segment, $duration, $time_interval, $extra_settings = null) {

			$amount = $segment->value;

			$coupon_code = strtolower(self::get_random_coupon_code());
            $coupon_code = apply_filters('wof_woocommerce_coupon_code',$coupon_code, $wheel, $segment);

			$coupon = [
				'post_title' => $coupon_code,
				'post_content' => '',
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type'		=> 'shop_coupon'
			];

			$quantifiers = [
				'minutes' => 60,
				'hours' => 3600,
				'days' => 86400
			];

			$expiry_unix = current_time('timestamp', true) + ($duration * $quantifiers[$time_interval] );

			$coupon_id = wp_insert_post( $coupon );

			$meta_data = [
			    'discount_type' =>  empty($extra_settings->discount_type) ? 'percent' : $extra_settings->discount_type,
                'coupon_amount' => $amount,
                'individual_use' => 'yes',
                'product_ids' => isset($extra_settings->include_products) ?  $extra_settings->include_products : '',
                'exclude_product_ids' => isset($extra_settings->exclude_products) ? $extra_settings->exclude_products : '',
                'usage_limit' => '1',
                'usage_count' => 0,
                'expiry_date' => $expiry_unix,
                'date_expires' => $expiry_unix,
                'apply_before_tax' => 'yes',
                'free_shipping' => isset($extra_settings->free_shipping) && $extra_settings->free_shipping ? 'yes' : 'no',
                'exclude_sale_items' => isset($extra_settings->exclude_sales) && $extra_settings->exclude_sales ? 'yes' : 'no',
            ];
			if(!empty($extra_settings->min_spend)){
				$meta_data['minimum_amount'] = $extra_settings->min_spend;
			}
			if(!empty($extra_settings->max_spend)){
				$meta_data['maximum_amount'] = $extra_settings->max_spend;
			}
            if(isset($extra_settings->exclude_categories) && !empty($extra_settings->exclude_categories)) {
                $exclude_cats = explode(',', $extra_settings->exclude_categories);
                $exclude_cats = Enumerable::from($exclude_cats)->select(function($x) {
                    return (int)$x;
                })->toArray();
                $meta_data['exclude_product_categories'] = $exclude_cats;
            }
            if(isset($extra_settings->include_categories) && !empty($extra_settings->include_categories)) {
                $include_cats = explode(',', $extra_settings->include_categories);
                $include_cats = Enumerable::from($include_cats)->select(function($x){
                    return (int)$x;
                })->toArray();
                $meta_data['product_categories'] = $include_cats;
            }

            $meta_data = apply_filters('wof_woocommerce_coupon_data', $meta_data, $wheel, $segment);

            foreach ($meta_data as $k => $v){
                update_post_meta( $coupon_id, $k, $v );
            }

			return $coupon_code;
		}

		public static function auto_apply_coupon($coupon_code) {
			if(!function_exists('WC'))
				return;
			WC()->session->set_customer_session_cookie(true);
            if ( WC()->cart->has_discount( $coupon_code ) ) return;
            WC()->cart->add_discount( $coupon_code );
        }

		public static function get_product_names_by_ids(array $ids){
			if(empty($ids)) return [];

			$prods = wc_get_products( [
				'numberposts' => -1,
				'include' => (array) $ids,
				'type' => [ 'simple','external','grouped','variable' ]
			] );

			$variations = wc_get_products( [
				'numberposts' => -1,
				'include' => (array) $ids,
				'type' => 'variation',
			] );

			$products = array_merge( $prods, $variations );

			return Enumerable::from($products)->select(function($x){
				$title = $x->get_title();
				$attr_title = '';

				if($x->is_type('variation')) {
					$attributes = $x->get_variation_attributes();

					foreach ( $attributes as $key => $attribute ) {
						if ( $attribute === '' ) {
							$attributes[ $key ] = __( 'any', 'mabel-wheel-of-fortune' ) . ' ' . strtolower( wc_attribute_label( str_replace( 'attribute_', '', $key ) ) );
						}
					}
					$attr_title = join(', ',$attributes);
				}
				return [ 'id' => $x->get_id(),'title' => $title  . (empty($attr_title) ? '' : '('.$attr_title.')' ) ];
			})->toArray();
		}

		public static function get_products_by_name($name) {

			if(empty($name))
				return [];

			$ds = new \WC_Product_Data_Store_CPT();
			$product_ids = $ds->search_products($name, '',true, true,10);

			$products = [];

			foreach($product_ids as $pid) {
				if($pid === 0)
					continue;

				$product = wc_get_product($pid);
				if(empty($product))
					continue;

				$attr_title = '';

				if($product->is_type('variation')) {
					$attributes = $product->get_variation_attributes();

					foreach ( $attributes as $key => $attribute ) {
						if ( $attribute === '' ) {
							$attributes[ $key ] = __( 'any', 'mabel-wheel-of-fortune' ) . ' ' . strtolower( wc_attribute_label( str_replace( 'attribute_', '', $key ) ) );
						}
					}
					$attr_title = join(', ',$attributes);
				}

				$products[] = [
					'title' => $product->get_title() . (empty($attr_title) ? '' : '('.$attr_title.')' ),
					'id' => $product->get_id()
				];

			}

			return $products;
		}

		public static function get_product_categories_by_ids($ids){

			if(empty($ids)) return [];

			global $wp_version;
			$args = [
				'hide_empty' => false,
				'number' => 5,
				'fields' => 'id=>name',
				'include' => $ids
			];

			if(version_compare($wp_version,'4.5.0','>=')){
				$args['taxonomy'] = 'product_cat';
				$terms = get_terms($args);
			}else{
				$terms = get_terms('product_cat', $args);
			}

			return Enumerable::from($terms)->select(function($v, $k){
				return [ 'id' => $k,'title' => $v ];
			})->toArray();
		}

		public static function get_categories(){
			global $wp_version;
			$args = [
				'hide_empty' => false,
				'number' => false,
				'fields' => 'id=>name'
			];

			if(version_compare($wp_version,'4.5.0','>=')){
				$args['taxonomy'] = 'product_cat';
				$terms = get_terms($args);
			}else{
				$terms = get_terms('product_cat', $args);
			}

			return $terms;
		}

		public static function get_categories_by_name($name) {
			global $wp_version;
			$args = [
				'hide_empty' => false,
				'number' => 5,
				'fields' => 'id=>name',
				'name__like' => urldecode($name)
			];

			if(version_compare($wp_version,'4.5.0','>=')) {
				$args['taxonomy'] = 'product_cat';
				$terms = get_terms($args);
			}else{
				$terms = get_terms('product_cat', $args);
			}

			return Enumerable::from($terms)->select(function($v, $k){
				return [ 'id' => $k,'title' => $v ];
			})->toArray();
		}

		private static function get_random_coupon_code() {
			$random_coupon = '';
			$length  = 8;
			$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$count = strlen($charset );
			while ( $length-- ) :
				$random_coupon .= $charset[ mt_rand( 0, $count-1 ) ];
			endwhile;

			return $random_coupon;
		}
	}
}
