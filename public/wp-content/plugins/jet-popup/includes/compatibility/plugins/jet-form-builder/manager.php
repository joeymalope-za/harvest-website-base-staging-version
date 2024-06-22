<?php
namespace Jet_Popup\Compatibility;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Compatibility Manager
 */
class Jet_Form_Builder {

	/**
	 * Include files
	 */
	public function load_files() {}

	/**
	 * @param $render_data
	 * @param $popup_id
	 * @param $widgets
	 * @param $content_type
	 * @return mixed
	 */
	public function modify_render_data( $render_data, $popup_id, $content_type ) {
		$content_elements = $render_data['contentElements'];

		if ( 'elementor' === $content_type && in_array( 'jet-form-builder-form', $content_elements ) ) {
			wp_enqueue_editor();
		}

		if ( 'default' === $content_type && in_array( 'jet-forms/form-block', $content_elements ) ) {
			wp_enqueue_editor();
		}

		return $render_data;

	}

	/**
	 * @param $blocks
	 * @return mixed
	 */
	public function modify_not_supported_blocks( $blocks ) {
		$not_supported_blocks = [
			'jet-forms/calculated-field',
			'jet-forms/checkbox-field',
			'jet-forms/color-picker-field',
			'jet-forms/conditional-block',
			'jet-forms/date-field',
			'jet-forms/datetime-field',
			'jet-forms/form-break-field',
			'jet-forms/form-break-start',
			'jet-forms/group-break-field',
			'jet-forms/heading-field',
			'jet-forms/hidden-field',
			'jet-forms/map-field',
			'jet-forms/media-field',
			'jet-forms/number-field',
			'jet-forms/progress-bar',
			'jet-forms/radio-field',
			'jet-forms/range-field',
			'jet-forms/repeater-field',
			'jet-forms/select-field',
			'jet-forms/submit-field',
			'jet-forms/text-field',
			'jet-forms/textarea-field',
			'jet-forms/time-field',
			'jet-forms/wysiwyg-field',
		];

		return array_merge( $blocks, $not_supported_blocks );
	}

	/**
	 * [__construct description]
	 */
	public function __construct() {

		if ( ! defined( 'JET_FORM_BUILDER_VERSION' ) ) {
			return false;
		}

		$this->load_files();

		add_filter( 'jet-plugins/render/render-data', [ $this, 'modify_render_data' ], 10, 4 );
		add_filter( 'jet-popup/block-manager/not-supported-blocks', [ $this, 'modify_not_supported_blocks' ] );

	}

}
