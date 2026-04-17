<?php
/**
 * Tracking/UTM Parameters field type for WPRobo DocuMerge form builder.
 *
 * A hidden field that auto-captures UTM parameters and referrer data.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Form/Fields
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.4.0
 */

namespace WPRobo\DocuMerge\Form\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Field_Tracking
 *
 * Handles tracking parameter fields that automatically capture
 * UTM parameters, referrer URL, and landing page URL from the
 * visitor's browser without any visible UI on the frontend.
 *
 * @since 1.4.0
 */
class WPRobo_DocuMerge_Field_Tracking {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'tracking';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'Tracking Parameters', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-chart-area';
	}

	/**
	 * Returns the default field configuration array.
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public function wprobo_documerge_get_default_config() {
		$config = array(
			'id'                 => '',
			'type'               => 'tracking',
			'label'              => 'Tracking Parameters',
			'name'               => 'tracking',
			'width'              => 'full',
			'track_utms'         => true,
			'track_referrer'     => true,
			'track_landing_page' => true,
			'conditions'         => array(),
		);

		/** This filter is documented in src/Form/Fields/WPRobo_DocuMerge_Field_Text.php */
		return apply_filters( 'wprobo_documerge_field_default_config', $config, $this->wprobo_documerge_get_type() );
	}

	/**
	 * Renders the admin settings panel HTML for this field.
	 *
	 * @since 1.4.0
	 *
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_render_admin_settings( $field_data ) {
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );

		$label = esc_attr( $field_data['label'] );

		$html = '';

		// Label (admin reference only).
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Label', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="label" class="wdm-builder-setting-input" value="' . $label . '">';
		$html .= '<span class="wdm-description">' . esc_html__( 'For admin reference only. Not shown on the frontend.', 'wprobo-documerge-lite' ) . '</span>';
		$html .= '</div>';

		// Track UTMs.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label><input type="checkbox" data-setting="track_utms" class="wdm-builder-setting-input"' . ( ! empty( $field_data['track_utms'] ) ? ' checked' : '' ) . '> ';
		$html .= esc_html__( 'Track UTM Parameters', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<span class="wdm-description">' . esc_html__( 'Captures utm_source, utm_medium, utm_campaign, utm_content, utm_term.', 'wprobo-documerge-lite' ) . '</span>';
		$html .= '</div>';

		// Track Referrer.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label><input type="checkbox" data-setting="track_referrer" class="wdm-builder-setting-input"' . ( ! empty( $field_data['track_referrer'] ) ? ' checked' : '' ) . '> ';
		$html .= esc_html__( 'Track Referrer URL', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '</div>';

		// Track Landing Page.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label><input type="checkbox" data-setting="track_landing_page" class="wdm-builder-setting-input"' . ( ! empty( $field_data['track_landing_page'] ) ? ' checked' : '' ) . '> ';
		$html .= esc_html__( 'Track Landing Page URL', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renders the frontend form HTML for this field.
	 *
	 * Outputs hidden inputs for each enabled tracking parameter.
	 * JavaScript fills these on page load from URL params and
	 * document.referrer.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $field_data The field configuration data.
	 * @param string $value      The current field value (unused).
	 * @return string
	 */
	public function wprobo_documerge_render_frontend( $field_data, $value = '' ) {
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );

		$id   = esc_attr( $field_data['id'] );
		$name = esc_attr( $field_data['name'] );
		$html = '';

		// UTM parameters.
		if ( ! empty( $field_data['track_utms'] ) ) {
			$utm_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );
			foreach ( $utm_params as $param ) {
				$html .= '<input type="hidden" id="wdm-field-' . $id . '-' . esc_attr( $param ) . '" name="' . $name . '_' . esc_attr( $param ) . '" class="wdm-tracking-input" data-param="' . esc_attr( $param ) . '" value="">';
			}
		}

		// Referrer.
		if ( ! empty( $field_data['track_referrer'] ) ) {
			$html .= '<input type="hidden" id="wdm-field-' . $id . '-referrer" name="' . $name . '_referrer" class="wdm-tracking-input" data-param="referrer" value="">';
		}

		// Landing page.
		if ( ! empty( $field_data['track_landing_page'] ) ) {
			$html .= '<input type="hidden" id="wdm-field-' . $id . '-landing_page" name="' . $name . '_landing_page" class="wdm-tracking-input" data-param="landing_page" value="">';
		}

		return $html;
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * Tracking data may come as individual sub-fields. This method
	 * sanitizes each value as a plain text string.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $value      The raw submitted value.
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_sanitize( $value, $field_data ) {
		if ( is_array( $value ) ) {
			return wp_json_encode( array_map( 'sanitize_text_field', $value ) );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Validates the submitted value.
	 *
	 * Tracking fields always pass validation as they are
	 * auto-populated and not user-editable.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $value      The sanitized value.
	 * @param array $field_data The field configuration data.
	 * @return true
	 */
	public function wprobo_documerge_validate( $value, $field_data ) {
		return true;
	}
}
