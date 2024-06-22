<?php

namespace Jet_Smart_Filters\Bricks_Views\Filters;

use Bricks\Query;
use Bricks\Assets;
use Bricks\Helpers;
use Bricks\Database;
use Bricks\Frontend;
use Bricks\Theme_Styles;

/**
 * Query loop bricks provider
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Any custom provider class should extend base provider class
 */
class Provider extends \Jet_Smart_Filters_Provider_Base {

	/**
	 * Allow to add specific query ID to element.
	 * Query ID required if you have 2+ filtered elements of same provider on the page
	 * Example of CSS class with query ID - 'jsfb-query--my-query'. 'jsfb-query--my-query' - its exact query ID.
	 * You need to set the same query ID into appropriate filter Query ID control.
	 * Then this filter will applied only for element with this class
	 * Its optional part implemented in this way for exact provider. Implementation for other providers may be different.
	 * Prefix required because Query Loop element may contain other class which not related to Query ID
	 *
	 * @var string
	 */
	protected $query_id_class_prefix = 'jsfb-query--';

	public $initial_cache_query_loops = false;

	/**
	 * Add hooks specific for exact provider
	 */
	public function __construct() {
		add_filter( 'bricks/query/force_run', [ $this, 'force_query_run' ] );
		add_filter( 'bricks/element/set_root_attributes', [ $this, 'set_attributes' ], 999, 2 );
		add_filter( 'bricks/element/settings', [ $this, 'store_default_provider_query' ], 10, 2 );
	}

	/**
	 * Set prefix for unique ID selector. Mostly is default '#' sign, but sometimes class '.' sign needed.
	 * For example for Query Loop element we don't have HTML/CSS ID attribute, so we need to use class as unique identifier.
	 */
	public function id_prefix() {
		return '.' . $this->query_id_class_prefix;
	}

	/**
	 * Get provider name
	 * @required: true
	 */
	public function get_name() {
		return BRICKS_QUERY_LOOP_PROVIDER_NAME;
	}

	/**
	 * Get provider ID
	 * @required: true
	 */
	public function get_id() {
		return BRICKS_QUERY_LOOP_PROVIDER_ID;
	}

	/**
	 * Check if this providers requires custom renderer on the front-end
	 *
	 * @return [type] [description]
	 */
	public function is_data() {
		return true;
	}

	/**
	 * First of all you need to store default provider query and required attributes to allow
	 * JetSmartFilters attach this data to AJAX request.
	 *
	 * @param array $settings The current settings.
	 * @param object $element The element object.
	 * @return array The modified settings.
	 */
	public function store_default_provider_query( $settings, $element ) {
		if ( ! isset( $settings['jsfb_is_filterable'] ) ) {
			return $settings;
		}

		$object_type = $this->get_object_type( $settings );

		if ( $this->check_default_query_type( $object_type ) ) {
			// Set default properties for the query.
			$this->set_default_props( $settings );

			add_filter( "bricks/{$object_type}s/query_vars", [ $this, 'store_default_query' ], 10, 3 );
		} else {
			add_filter( 'bricks/query/run', [ $this, 'store_custom_query' ], 10, 2 );
		}

		return $settings;
	}

	/**
	 * Set attributes for an element based on its settings.
	 *
	 * @param array $attributes The existing attributes for the element.
	 * @param object $element The element object.
	 * @return array The modified attributes.
	 */
	public function set_attributes( $attributes, $element ) {
		$settings = $element->settings;

		if ( ! isset( $settings['jsfb_is_filterable'] ) || ! Query::is_looping() ) {
			return $attributes;
		}

		// Prepare classes for the filterable element.
		$classes   = [ 'jsfb-filterable' ];
		$query_id  = $settings['jsfb_query_id'] ?? 'default';
		$classes[] = $this->query_id_class_prefix . $query_id;

		return $this->merge_attributes( $attributes, 'class', $classes );
	}


	/**
	 * Merge specified attributes into an existing array of attributes.
	 *
	 * @param array $attributes The existing attributes array.
	 * @param string|null $attr_key The key for the attribute to merge.
	 * @param array $attr_value The array of attribute values to merge.
	 * @return array The modified attributes array.
	 */
	public function merge_attributes( $attributes = [], $attr_key = null, $attr_value = [] ) {
		if ( ! isset( $attributes[ $attr_key ] ) ) {
			$attributes[ $attr_key ] = [];
		}

		// If the attribute value is not an array, convert it to an array.
		if ( ! is_array( $attributes[ $attr_key ] ) ) {
			$attributes[ $attr_key ] = [ $attributes[ $attr_key ] ];
		}

		$attributes[ $attr_key ] = array_merge( $attributes[ $attr_key ], $attr_value );

		return $attributes;
	}

	/**
	 * Set default properties for the given settings.
	 *
	 * @param array $settings The settings array.
	 * @return void
	 */
	public function set_default_props( $settings ) {
		$query_bricks = Query::get_query_object();
		$query_id     = $settings['jsfb_query_id'] ?? 'default';
		$object_type  = $this->get_object_type( $settings );
		$props        = [];

		if ( ! $query_bricks ) {
			$props = [
				'found_posts'   => 0,
				'max_num_pages' => 0,
				'page'          => 0,
			];
		}

		// Set properties for 'post' query type.
		if ( $object_type === 'post' && $query_bricks !== false ) {
			$query = $query_bricks->query_result;

			if ( empty( $query ) ) {
				return;
			}

			$props = [
				'found_posts'   => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
				'page'          => max( 1, $query->get( 'paged', 1 ) ),
			];
		}

		// Set properties for 'user' query type.
		if ( $object_type === 'user' && $query_bricks !== false ) {
			$props = [
				'found_posts'   => $query_bricks->count,
				'max_num_pages' => $query_bricks->max_num_pages,
				'page'          => $this->get_current_page(),
			];
		}

		// Set properties for 'term' query type
		if ( $object_type === 'term' && $query_bricks !== false ) {
			$query_vars = $settings['query'];
			unset( $query_vars['objectType'] );
			$props = $this->get_term_props( $query_vars );
		}

		// Set the properties
		jet_smart_filters()->query->set_props(
			$this->get_id(),
			$props,
			$query_id
		);
	}

	/**
	 * Store default brick attributes to add them to filters AJAX request
	 *
	 * @param array $query_vars The original query_vars.
	 * @param array $settings The settings array.
	 * @param int $element_id The ID of the element.
	 * @return array The modified query_vars.
	 */
	public function store_default_query( $query_vars, $settings, $element_id ) {
		$this->store_query( $query_vars, $settings, $element_id );
		$this->turn_off_cache_query_loop( $element_id );

		return $query_vars;
	}

	/**
	 * Store custom brick attributes to add them to filters AJAX request
	 *
	 * @param array $arr The original array.
	 * @param object $query The query object.
	 * @return array The modified array.
	 */
	public function store_custom_query( $arr, $query ) {
		$settings = $query->settings;

		if ( empty( $settings['jsfb_is_filterable'] ) || $query->object_type !== 'jet_engine_query_builder' ) {
			return $arr;
		}

		$query_id         = $settings['jsfb_query_id'] ?? 'default';
		$query_builder_id = $settings['jet_engine_query_builder_id'] ?? '';

		if ( ! $query_builder_id ) {
			return $arr;
		}

		$query_builder = \Jet_Engine\Query_Builder\Manager::instance()->get_query_by_id( $query_builder_id );
		$query_vars    = $query_builder->get_query_args();

		$this->store_query( $query_vars, $settings, $query->element_id );

		// Prepare properties.
		$props = [
			'found_posts'   => $query_builder->get_items_total_count(),
			'max_num_pages' => $query_builder->get_items_pages_count(),
			'page'          => $query_builder->get_current_items_page(),
		];

		jet_smart_filters()->query->set_props(
			$this->get_id(),
			$props,
			$query_id
		);

		return $arr;
	}

	/**
	 * Store query.
	 *
	 * @param array $query_vars The query variables.
	 * @param array $settings The settings array.
	 * @param int $element_id The ID of the element.
	 * @return void
	 */
	public function store_query( $query_vars, $settings, $element_id ) {
		$query_id              = $settings['jsfb_query_id'] ?? 'default';
		$is_archive_main_query = $query_vars['is_archive_main_query'] ?? '';
		$post_id               = Database::$page_data['original_post_id'] ?? Database::$page_data['preview_or_post_id'];
		$template_content_type = Database::$active_templates['content_type'] ?? '';

		if ( $template_content_type === 'archive' ) {
			$post_id = Database::$active_templates['content'];
		}

		if ( $template_content_type === 'search' ) {
			$post_id = Database::$active_templates['search'];
		}

		$attrs = [
			'filtered_post_id'      => $post_id,
			'element_id'            => $element_id,
			'is_archive_main_query' => $is_archive_main_query,
		];

		if ( Query::get_query_object()->object_type === 'user' ) {
			$query_vars['_query_type'] = 'users';
		}

		jet_smart_filters()->query->store_provider_default_query(
			$this->get_id(),
			$query_vars,
			$query_id
		);

		jet_smart_filters()->providers->add_provider_settings(
			$this->get_id(),
			$attrs,
			$query_id
		);
	}

	/**
	 * Disable caching for a specific query loop when using AJAX filters.
	 *
	 * @param string $element_id The ID of the filtered element.
	 */
	public function turn_off_cache_query_loop( $element_id ) {
		if ( ! jet_smart_filters()->query->is_ajax_filter() ) {
			return;
		}

		if ( isset( Database::$global_settings['cacheQueryLoops'] ) && ! $this->initial_cache_query_loops ) {
			$jsf_query_settings = jet_smart_filters()->query->get_query_settings();
			$filtered_element_id = $jsf_query_settings['element_id'] ?? '';

			if ( $element_id === $filtered_element_id ) {
				unset( Database::$global_settings['cacheQueryLoops'] );
				$this->initial_cache_query_loops = true;
			}
		}
	}

	/**
	 * Restore caching for query loops after AJAX filter request.
	 */
	public function restore_cache_query_loop() {
		if ( ! isset( Database::$global_settings['cacheQueryLoops'] ) && $this->initial_cache_query_loops ) {
			Database::$global_settings['cacheQueryLoops'] = 1;
			$this->initial_cache_query_loops = false;
		}
	}

	/**
	 * Get filtered provider content.
	 * @required: true
	 */
	public function ajax_get_content() {
		$settings   = jet_smart_filters()->query->get_query_settings();
		$query_vars = jet_smart_filters()->query->get_query_args();
		$query_id   = jet_smart_filters()->query->get_current_provider( 'query_id' );
		$paged      = $query_vars['paged'] ?? 1;

		$post_id          = ( isset( $settings['filtered_post_id'] ) && $settings['filtered_post_id'] !== '' )
			? absint( $settings['filtered_post_id'] )
			: false;
		$query_element_id = ( isset( $settings['element_id'] ) && $settings['element_id'] !== '' )
			? esc_attr( $settings['element_id'] )
			: false;

		if ( $post_id === false || $query_element_id === false ) {
			return;
		}

		Database::$page_data['preview_or_post_id'] = $post_id;

		$bricks_data = Helpers::get_element_data( $post_id, $query_element_id );

		// STEP: Build the flat list index
		$indexed_elements = [];

		foreach ( $bricks_data['elements'] as $element ) {
			$indexed_elements[ $element['id'] ]   = $element;
		}

		// STEP: Set the query element pagination
		$query_element = $indexed_elements[ $query_element_id ];

		$object_type            = $this->get_object_type( $query_element['settings'] );
		$is_default_object_type = $this->check_default_query_type( $object_type );

		if ( $is_default_object_type ) {
			// If the 'is_archive_main_query' setting is not empty, override the main WordPress query
			if ( $object_type === 'post' && ! empty( $settings['is_archive_main_query'] ) ) {
				global $wp_query;
				$wp_query = new \WP_Query( $query_vars );
			}

			if ( in_array( $object_type, [ 'term', 'user' ] ) ) {
				// Don't use request's offset, Term and User query offset should be calculated Query::inside prepare_query_vars_from_settings()
				unset( $query_vars['offset'] );
			}

			// Set the page number which comes from the request
			$query_vars['paged'] = $paged;

			// Set the page number - This is needed for term query
			$query_element['settings']['query']['paged'] = $paged;

			// Add an action to modify the query before it is executed
			add_filter(
				"bricks/{$object_type}s/query_vars",
				function( $vars, $settings, $element_id ) use ( $query_element_id, $query_vars ) {
					if ( $element_id !== $query_element_id ) {
						return $vars;
					}

					// Merge the query vars
					$merged_query_vars = Query::merge_query_vars( $vars, $query_vars );

					return $merged_query_vars;
				}, 10, 3
			);
		}

		if ( $object_type === 'jet_engine_query_builder' ) {
			// Set the current loop iteration index for correct rendering of dynamic data.
			add_filter( 'bricks/query/force_loop_index', [ $this, 'force_loop_index' ] );
			add_filter( 'jet-smart-filters/query/final-query', [ $this, 'add_query_vars' ] );
		}

		// Remove the parent
		if ( ! empty( $query_element['parent'] ) ) {
			$query_element['parent']       = 0;
			$query_element['_noRootClass'] = 1;
		}

		// STEP: Get the query loop elements (main and children)
		$loop_elements = [ $query_element ];

		$children = $query_element['children'];

		while ( ! empty( $children ) ) {
			$child_id = array_shift( $children );

			if ( array_key_exists( $child_id, $indexed_elements ) ) {
				$loop_elements[] = $indexed_elements[ $child_id ];

				if ( ! empty( $indexed_elements[ $child_id ]['children'] ) ) {
					$children = array_merge( $children, $indexed_elements[ $child_id ]['children'] );
				}
			}
		}

		add_filter(
			'bricks/query/no_results_content',
			function ( $content ) use ( $query_element_id ) {
				return $this->get_no_results_content( $query_element_id );
			}
		);

		// Set Theme Styles (for correct preview of query loop nodes)
		Theme_Styles::load_set_styles( $post_id );

		// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
		$jsf_query_page_id = "jsf_{$query_element_id}";

		Assets::generate_css_from_elements( $loop_elements, $jsf_query_page_id );

		$inline_css = ! empty( Assets::$inline_css[ $jsf_query_page_id ] ) ? Assets::$inline_css[ $jsf_query_page_id ] : '';

		// STEP: Render the element after styles are generated as data-query-loop-index might be inserted through hook in Assets class (@since 1.7.2)
		echo Frontend::render_data( $loop_elements );

		// STEP: Add dynamic data styles after render_data() to catch dynamic data changes (eg. background-image) (@since 1.8.2)
		$inline_css .= Assets::$inline_css_dynamic_data;

		$styles = ! empty( $inline_css ) ? "\n<style class='brx-jsf-query-styles-{$query_element_id}'>/* JSF CSS */\n{$inline_css}</style>\n" : '';

		add_filter(
			'jet-smart-filters/render/ajax/data',
			function ( $data ) use ( $query_id, $query_element_id, $styles ) {
				$data['query_id']         = $query_id;
				$data['rendered_content'] = $data['content'];
				$data['content']          = false;
				$data['styles']           = $styles;
				$data['element_id']       = $query_element_id;

				return $data;
			}
		);

		if ( $is_default_object_type ) {
			remove_filter( "bricks/{$object_type}s/query_vars", [ $this, 'store_default_query' ] );
		} else {
			remove_filter( 'bricks/query/run', [ $this, 'store_custom_query' ] );
		}

		add_action( 'bricks/query/after_loop', [ $this, 'restore_cache_query_loop' ], 10, 2 );
	}

	/**
	 * Apply filters on page reload
	 * Filter arguments in this case passed with $_GET request
	 *
	 * @required: true
	 */
	public function apply_filters_in_request() {
		$query_vars = jet_smart_filters()->query->get_query_args();

		if ( ! $query_vars ) {
			return;
		}

		add_filter( 'bricks/assets/generate_css_from_element', [ $this, 'modify_bricks_query' ], 10, 2 );
	}


	/**
	 * Here we checking - if will be rendered filtered element - we hook 'bricks/{$object_type}s/query_vars' method
	 * to modify brick query.
	 */
	public function modify_bricks_query( $arr, $element ) {
		if ( ! $this->is_filtered_element( $element ) ) {
			return $arr;
		}

		$object_type      = $this->get_object_type( $element['settings'] );
		$query_element_id = $element['id'];

		if ( $this->check_default_query_type( $object_type ) ) {
			// Add an action to modify the query before it is executed

			add_filter(
				"bricks/{$object_type}s/query_vars",
				function( $vars, $settings, $element_id ) use ( $query_element_id, $object_type ) {
					if ( $element_id !== $query_element_id ) {
						return $vars;
					}

					$query_vars = jet_smart_filters()->query->get_query_args();

					if ( $object_type === 'term' ) {
						// Pagination: Fix the offset value (@since 1.5)
						$offset = ! empty( $vars['offset'] ) ? $vars['offset'] : 0;

						// If pagination exists, and number is limited (!= 0), use $offset as the pagination trigger
						if ( $query_vars['paged'] !== 1 && ! empty( $vars['number'] ) ) {
							$vars['offset'] = ( $query_vars['paged'] - 1 ) * $vars['number'] + $offset;
						}
					}

					if ( $object_type === 'user' ) {
						// Pagination: Fix the offset value (@since 1.5)
						$offset = ! empty( $vars['offset'] ) ? $vars['offset'] : 0;

						if ( ! empty( $offset ) && $query_vars['paged'] !== 1 ) {
							$vars['offset'] = ( $query_vars['paged'] - 1 ) * $vars['number'] + $offset;
						}
					}

					$merged_query_vars = Query::merge_query_vars( $vars, $query_vars );

					return $merged_query_vars;
				}, 11, 3
			);
		}

		add_filter(
			'bricks/query/no_results_content',
			function ( $content ) use ( $query_element_id ) {
				return $this->get_no_results_content( $query_element_id );
			}
		);

		return $arr;
	}

	/**
	 * Check if is currently filtered brick
	 *
	 * @param array $brick Parsed brick
	 *
	 * @return boolean
	 */
	public function is_filtered_element( $element ) {
		$settings         = $element['settings'] ?? [];
		$element_query_id = $settings['jsfb_query_id'] ?? '';
		$query_id         = jet_smart_filters()->query->get_current_provider( 'query_id' );

		// Bricks Query Loop
		if ( ! isset( $settings['hasLoop'] ) ) {
			return false;
		}

		if ( ! isset( $settings['jsfb_is_filterable'] ) ) {
			return false;
		}

		if ( 'default' === $query_id && empty( $element_query_id ) ) {
			return true;
		}

		return $element_query_id === $query_id;
	}

	/**
	 * Get provider wrapper selector
	 * Its CSS selector of HTML element with provider content.
	 * @required: true
	 */
	public function get_wrapper_selector() {
		return '.jsfb-filterable';
	}

	public function check_default_query_type( $object_type ) {
		$object_types = [ 'post', 'term', 'user' ];

		foreach ( $object_types as $type ) {
			if ( $type === $object_type ) {
				return true;
			}
		}

		return false;
	}

	public function get_object_type( $settings ) {
		return ! empty( $settings['query']['objectType'] ) ? $settings['query']['objectType'] : 'post';
	}

	public function get_term_props( $query_vars ) {
		// Pagination: Fix the offset value
		$offset = ! empty( $query_vars['offset'] ) ? $query_vars['offset'] : 0;

		// Hide empty
		if ( isset( $query_vars['show_empty'] ) ) {
			$query_vars['hide_empty'] = false;

			unset( $query_vars['show_empty'] );
		} else {
			$query_vars['hide_empty'] = true;
		}

		$query_args  = jet_smart_filters()->query->get_query_args();
		$query_vars  = array_merge( $query_vars, $query_args );
		$terms_query = new \WP_Term_Query( $query_vars );
		$result      = $terms_query->get_terms();

		// STEP: Populate the total count
		if ( empty( $query_vars['number'] ) ) {
			$count = ! empty( $result ) && is_array( $result ) ? count( $result ) : 0;
		} else {
			$args = $query_vars;

			unset( $args['offset'] );
			unset( $args['number'] );

			// Numeric string containing the number of terms in that taxonomy or WP_Error if the taxonomy does not exist.
			$count = wp_count_terms( $args );

			if ( is_wp_error( $count ) ) {
				$count = 0;
			} else {
				$count = (int) $count;

				$count = $offset <= $count ? $count - $offset : 0;
			}
		}

		// STEP : Populate the max number of pages
		$max_num_pages = empty( $query_vars['number'] ) ? 1 : ceil( $count / $query_vars['number'] );

		return [
			'found_posts'   => $count,
			'max_num_pages' => $max_num_pages,
			'page'          => $this->get_current_page(),
		];
	}

	public function get_current_page() {
		return jet_smart_filters()->query->get_query_args()['paged'] ?? 1;
	}

	// to skip add_to_history() method in Bricks query
	public function force_query_run() {
		return true;
	}

	public function force_loop_index( $index ) {
		$query_vars  = jet_smart_filters()->query->get_query_args();
		$object_type = $query_vars['_query_type'] ?? 'posts';
		$paged       = $query_vars['paged'] ?? 1;

		if ( $object_type === 'posts' ) {
			$posts_per_page = $query_vars['posts_per_page'] ?? 0;
		} elseif ( $object_type === 'terms' ) {
			$posts_per_page = $query_vars['number_per_page'] ?? 0;
		} elseif ( $object_type === 'users' ) {
			$posts_per_page = $query_vars['number'] ?? 0;
		}

		$query            = Query::get_query_object();
		$query_loop_index = $query && $query->is_looping ? $query->loop_index : '';

		if ( $paged > 1 && $posts_per_page > 0 && $query_loop_index !== '' ) {
			$index = $query_loop_index + $posts_per_page * ( $paged - 1 );
		}

		return $index;
	}

	function add_query_vars( $query ) {
		if ( empty( $query['paged'] ) ) {
			$query['paged'] = 1;
		}

		return $query;
	}

	function get_no_results_content( $element_id ) {
		$query_id = jet_smart_filters()->query->get_current_provider( 'query_id' );

		$classes = implode( ' ', [
			'brxe-' . $element_id,
			'jsfb-filterable',
			$this->query_id_class_prefix . $query_id,
		] );

		return '<div class="' . $classes . '">' . 'No data was found' . '</div>';
	}
}