<?php
/**
 * Phone field type for WPRobo DocuMerge form builder.
 *
 * @package   WPRobo_DocuMerge
 * @since     1.0.0
 */

namespace WPRobo\DocuMerge\Form\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Field_Phone
 *
 * Handles phone input fields within the DocuMerge form builder.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Field_Phone {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'phone';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'Phone', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-phone';
	}

	/**
	 * Returns the default field configuration array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function wprobo_documerge_get_default_config() {
		$config = array(
			'id'                => '',
			'type'              => 'phone',
			'label'             => 'Phone',
			'name'              => '',
			'placeholder'       => '',
			'help_text'         => '',
			'required'          => false,
			'width'             => 'full',
			'error_message'     => '',
			'conditions'        => array(),
			'show_country_code' => true,
			'default_country'   => 'GB',
		);

		/** This filter is documented in src/Form/Fields/WPRobo_DocuMerge_Field_Text.php */
		return apply_filters( 'wprobo_documerge_field_default_config', $config, $this->wprobo_documerge_get_type() );
	}

	/**
	 * Renders the admin settings panel HTML for this field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_render_admin_settings( $field_data ) {
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );

		$id            = esc_attr( $field_data['id'] );
		$label         = esc_attr( $field_data['label'] );
		$placeholder   = esc_attr( $field_data['placeholder'] );
		$help_text     = esc_attr( $field_data['help_text'] );
		$required      = ! empty( $field_data['required'] ) ? 'checked' : '';
		$width         = esc_attr( $field_data['width'] );
		$error_message = esc_attr( $field_data['error_message'] );

		$html = '';

		// Label.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Label', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="label" class="wdm-builder-setting-input" value="' . $label . '">';
		$html .= '</div>';

		// Placeholder.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Placeholder', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="placeholder" class="wdm-builder-setting-input" value="' . $placeholder . '">';
		$html .= '</div>';

		// Help Text.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Help Text', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="help_text" class="wdm-builder-setting-input" value="' . $help_text . '">';
		$html .= '</div>';

		// Show country code dropdown.
		$show_cc = ! empty( $field_data['show_country_code'] ) ? 'checked' : '';
		$html   .= '<div class="wdm-builder-field-setting">';
		$html   .= '<label><input type="checkbox" data-setting="show_country_code" class="wdm-builder-setting-input" ' . $show_cc . '> ';
		$html   .= esc_html__( 'Show country code dropdown', 'wprobo-documerge-lite' ) . '</label>';
		$html   .= '</div>';

		// Default country.
		$default_country = esc_attr( $field_data['default_country'] );
		$countries       = array(
			'US' => '+1 United States',
			'GB' => '+44 United Kingdom',
			'CA' => '+1 Canada',
			'AU' => '+61 Australia',
			'DE' => '+49 Germany',
			'FR' => '+33 France',
			'ES' => '+34 Spain',
			'IT' => '+39 Italy',
			'NL' => '+31 Netherlands',
			'SE' => '+46 Sweden',
			'IN' => '+91 India',
			'PK' => '+92 Pakistan',
			'AE' => '+971 UAE',
			'SA' => '+966 Saudi Arabia',
			'JP' => '+81 Japan',
			'CN' => '+86 China',
			'BR' => '+55 Brazil',
			'MX' => '+52 Mexico',
			'NG' => '+234 Nigeria',
			'ZA' => '+27 South Africa',
			'KE' => '+254 Kenya',
			'EG' => '+20 Egypt',
			'NZ' => '+64 New Zealand',
			'IE' => '+353 Ireland',
			'SG' => '+65 Singapore',
			'MY' => '+60 Malaysia',
			'PH' => '+63 Philippines',
			'TH' => '+66 Thailand',
			'TR' => '+90 Turkey',
			'RU' => '+7 Russia',
		);
		$html           .= '<div class="wdm-builder-field-setting">';
		$html           .= '<label>' . esc_html__( 'Default Country', 'wprobo-documerge-lite' ) . '</label>';
		$html           .= '<select data-setting="default_country" class="wdm-builder-setting-input wdm-select">';
		foreach ( $countries as $code => $label ) {
			$sel   = selected( $default_country, $code, false );
			$html .= '<option value="' . esc_attr( $code ) . '"' . $sel . '>' . esc_html( $label ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</div>';

		// Required.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Required', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="checkbox" data-setting="required" class="wdm-builder-setting-input" ' . $required . '>';
		$html .= '</div>';

		// Custom Error Message.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Custom Error Message', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="error_message" class="wdm-builder-setting-input wdm-input" value="' . $error_message . '" placeholder="' . esc_attr__( 'Leave blank for default', 'wprobo-documerge-lite' ) . '">';
		$html .= '<span class="wdm-description">' . esc_html__( 'Optional. Shown when validation fails.', 'wprobo-documerge-lite' ) . '</span>';
		$html .= '</div>';

		// Width.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Width', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<div class="wdm-width-selector">';
		$html .= '<label><input type="radio" name="width_' . $id . '" data-setting="width" value="full"' . checked( $width, 'full', false ) . '> ' . esc_html__( 'Full', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<label><input type="radio" name="width_' . $id . '" data-setting="width" value="half"' . checked( $width, 'half', false ) . '> ' . esc_html__( 'Half', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<label><input type="radio" name="width_' . $id . '" data-setting="width" value="third"' . checked( $width, 'third', false ) . '> ' . esc_html__( 'Third', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renders the frontend form HTML for this field.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $field_data The field configuration data.
	 * @param string $value      The current field value.
	 * @return string
	 */
	public function wprobo_documerge_render_frontend( $field_data, $value = '' ) {
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );

		$id          = esc_attr( $field_data['id'] );
		$name        = esc_attr( $field_data['name'] );
		$label       = esc_html( $field_data['label'] );
		$placeholder = esc_attr( $field_data['placeholder'] );
		$help_text   = esc_html( $field_data['help_text'] );
		$required    = ! empty( $field_data['required'] ) ? 'required' : '';
		$value_attr  = esc_attr( $value );

		$default_country = ! empty( $field_data['default_country'] ) ? strtolower( $field_data['default_country'] ) : 'gb';
		$show_country    = ! empty( $field_data['show_country_code'] );

		$html  = '<div class="wdm-field-group" data-field-type="phone">';
		$html .= '<label for="wdm-field-' . $id . '">' . $label;
		if ( ! empty( $field_data['required'] ) ) {
			$html .= ' <span class="wdm-required">*</span>';
		}
		$html .= '</label>';
		$html .= '<input type="tel" id="wdm-field-' . $id . '" name="' . $name . '" class="wdm-input wdm-intl-phone" value="' . $value_attr . '" placeholder="' . $placeholder . '" ' . $required;
		$html .= ' data-default-country="' . esc_attr( $default_country ) . '"';
		$html .= ' data-show-country="' . ( $show_country ? '1' : '0' ) . '"';
		$html .= '>';
		// Hidden input for full international number (with country code).
		$html .= '<input type="hidden" name="' . $name . '_full" id="wdm-field-' . $id . '-full" value="">';
		if ( ! empty( $field_data['help_text'] ) ) {
			$html .= '<p class="wdm-help-text">' . $help_text . '</p>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value      The raw submitted value.
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_sanitize( $value, $field_data ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Validates the submitted value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value      The sanitized value.
	 * @param array $field_data The field configuration data.
	 * @return true|\WP_Error
	 */
	public function wprobo_documerge_validate( $value, $field_data ) {
		$field_data   = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );
		$custom_error = ! empty( $field_data['error_message'] ) ? $field_data['error_message'] : '';

		if ( ! empty( $field_data['required'] ) && '' === trim( $value ) ) {
			return new \WP_Error(
				'wprobo_documerge_required',
				'' !== $custom_error
					? esc_html( $custom_error )
					/* translators: %s: field label */
					: sprintf( __( '%s is required.', 'wprobo-documerge-lite' ), $field_data['label'] )
			);
		}

		if ( '' !== $value && ! preg_match( '/^[+\d\s\-().]+$/', $value ) ) {
			return new \WP_Error(
				'wprobo_documerge_invalid_phone',
				'' !== $custom_error
					? esc_html( $custom_error )
					/* translators: %s: field label */
					: sprintf( __( '%s must be a valid phone number.', 'wprobo-documerge-lite' ), $field_data['label'] )
			);
		}

		return true;
	}
}
