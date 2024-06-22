<?php
/**
 * Gravity Forms Geolocation locator button field.
 *
 * @package gravityforms-geolocation.
 */

if ( ! class_exists( 'GFForms' ) ) {
	die(); // abort if accessed directly.
}

/**
 * Register Locator button
 *
 * @since  2.0
 */
class GFGEO_Locator_Button_Field extends GF_Field {

	/**
	 * Field type
	 *
	 * @var string
	 */
	public $type = 'gfgeo_locator_button';

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--place';
	}

	/**
	 * Field button
	 *
	 * @return [type] [description]
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'gfgeo_geolocation_fields',
			'text'  => __( 'Locator Button', 'gfgeo' ),
		);
	}

	/**
	 * Field title
	 *
	 * @return [type] [description]
	 */
	public function get_form_editor_field_title() {
		return __( 'Locator Button', 'gfgeo' );
	}

	/**
	 * Field settings.
	 *
	 * @return [type] [description]
	 */
	public function get_form_editor_field_settings() {

		// Gravity Forms v2.5+.
		if ( GFGEO_GF_2_5 ) {

			return array(
				// ggf options.
				'gfgeo-geocoder-id',
				'gfgeo-locator-button-field-settings',
				// gform options.
				'conditional_logic_field_setting',
				'label_setting',
				'label_placement_setting',
				'admin_label_setting',
				'size_setting',
				'visibility_setting',
				'description_setting',
				'css_class_setting',
			);
		}

		return array(
			// ggf options.
			'gfgeo-location-found-message',
			'gfgeo-hide-location-failed-message',
			'gfgeo-geocoder-id',
			'gfgeo-locator-button-label',
			'gfgeo-locator-button-label-setting',
			// gform options.
			'gfgeo-ip-locator-status',
			'conditional_logic_field_setting',
			'label_setting',
			'description_setting',
			'css_class_setting',
			'visibility_setting',
		);
	}

	/**
	 * Conditional logic
	 *
	 * @return boolean [description]
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Generate field input
	 *
	 * @param  [type] $form  [description].
	 * @param  string $value [description].
	 * @param  [type] $entry [description].
	 *
	 * @return [type]        [description]
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		// get the button element.
		$input = GFGEO_Helper::get_locator_button( $form['id'], $this, 'button' );

		return sprintf( "<div class='ginput_container ginput_container_gfgeo_locator_button'>%s</div>", $input );
	}

	/**
	 * Display message in entry list page.
	 *
	 * @param  [type] $value         [description].
	 * @param  [type] $entry         [description].
	 * @param  [type] $field_id      [description].
	 * @param  [type] $columns       [description].
	 * @param  [type] $form          [description].
	 *
	 * @return [type]                [description]
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return __( 'Data is not available for this field', 'gfgeo' );
	}
}
GF_Fields::register( new GFGEO_Locator_Button_Field() );
