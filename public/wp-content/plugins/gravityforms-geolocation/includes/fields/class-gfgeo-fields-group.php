<?php
/**
 * Gravity Forms Geolocation field group class.
 *
 * @package gravityforms-geolocation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register geolocation fields group.
 */
class GFGEO_Fields_Group extends GF_Field {

	/**
	 * Field group type.
	 *
	 * @var string
	 */
	public $type = 'gfgeo_group_field';

	/**
	 * Button fields and group.
	 *
	 * @param [type] $field_groups [description].
	 */
	public function add_button( $field_groups ) {

		global $__gf_tooltips;

		/* Translators: %s : link */
		$__gf_tooltips['form_gfgeo_geolocation_fields'] = '<h6>' . __( 'Geolocation Fields', 'gravityforms' ) . '</h6>' . sprintf( __( 'Check out the <a href="%s" target="_blank">documentation site</a> for details about the geolocation fields.', 'gfgeo' ), 'https://docs.gravitygeolocation.com/article/228-geolocation-fields-overview' );

		// Geolocation fields group.
		$geo_group = apply_filters(
			'gfgeo_field_settings_args',
			array(
				'name'   => 'gfgeo_geolocation_fields',
				'label'  => __( 'Geolocation Fields', 'gfgeo' ),
				'fields' => apply_filters( 'gfgeo_field_buttons', array(), $field_groups ),
			)
		);

		if ( ! empty( $geo_group ) ) {
			$field_groups[] = $geo_group;
		}

		return $field_groups;
	}
}
GF_Fields::register( new GFGEO_Fields_Group() );
