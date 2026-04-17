<?php
/**
 * Radio buttons field type for WPRobo DocuMerge form builder.
 *
 * @package   WPRobo_DocuMerge
 * @since     1.0.0
 */

namespace WPRobo\DocuMerge\Form\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Field_Radio
 *
 * Handles radio button fields within the DocuMerge form builder.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Field_Radio {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'radio';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'Radio Buttons', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-marker';
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
			'id'            => '',
			'type'          => 'radio',
			'label'         => 'Radio Buttons',
			'name'          => '',
			'placeholder'   => '',
			'help_text'     => '',
			'required'      => false,
			'width'         => 'full',
			'options'       => array(
				array(
					'label' => 'Option 1',
					'value' => 'option_1',
				),
			),
			'error_message' => '',
			'conditions'    => array(),
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
		$help_text     = esc_attr( $field_data['help_text'] );
		$required      = ! empty( $field_data['required'] ) ? 'checked' : '';
		$width         = esc_attr( $field_data['width'] );
		$error_message = esc_attr( $field_data['error_message'] );
		$raw_opts      = isset( $field_data['options'] ) ? $field_data['options'] : array();
		$options       = is_array( $raw_opts ) ? $raw_opts : ( is_string( $raw_opts ) ? (array) json_decode( $raw_opts, true ) : array() );

		$html = '';

		// Label.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Label', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="label" class="wdm-builder-setting-input" value="' . $label . '">';
		$html .= '</div>';

		// Placeholder.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Placeholder', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="placeholder" class="wdm-builder-setting-input" value="' . esc_attr( $field_data['placeholder'] ) . '">';
		$html .= '</div>';

		// Help Text.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Help Text', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="help_text" class="wdm-builder-setting-input" value="' . $help_text . '">';
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

		// Options List.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Options', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<div class="wdm-options-list">';

		foreach ( $options as $index => $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			$opt_label = isset( $option['label'] ) ? esc_attr( $option['label'] ) : '';
			$opt_value = isset( $option['value'] ) ? esc_attr( $option['value'] ) : '';

			$html .= '<div class="wdm-option-row" data-index="' . esc_attr( $index ) . '">';
			$html .= '<span class="wdm-option-drag dashicons dashicons-menu"></span>';
			$html .= '<input type="text" data-setting="option_label" class="wdm-input" placeholder="' . esc_attr__( 'Label', 'wprobo-documerge-lite' ) . '" value="' . $opt_label . '">';
			$html .= '<input type="text" data-setting="option_value" class="wdm-input" placeholder="' . esc_attr__( 'Value', 'wprobo-documerge-lite' ) . '" value="' . $opt_value . '">';
			$html .= '<button type="button" class="wdm-option-remove"><span class="dashicons dashicons-no-alt"></span></button>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '<button type="button" class="wdm-add-option">' . esc_html__( '+ Add Option', 'wprobo-documerge-lite' ) . '</button>';
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

		$name      = esc_attr( $field_data['name'] );
		$label     = esc_html( $field_data['label'] );
		$help_text = esc_html( $field_data['help_text'] );
		$raw_opts  = isset( $field_data['options'] ) ? $field_data['options'] : array();
		$options   = is_array( $raw_opts ) ? $raw_opts : ( is_string( $raw_opts ) ? (array) json_decode( $raw_opts, true ) : array() );

		$html  = '<div class="wdm-field-group">';
		$html .= '<label>' . $label;
		if ( ! empty( $field_data['required'] ) ) {
			$html .= ' <span class="wdm-required">*</span>';
		}
		$html .= '</label>';
		$html .= '<div class="wdm-radio-group">';

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			$opt_value = isset( $option['value'] ) ? esc_attr( $option['value'] ) : '';
			$opt_label = isset( $option['label'] ) ? esc_html( $option['label'] ) : '';
			$checked   = checked( $value, $opt_value, false );

			$html .= '<label class="wdm-radio-label">';
			$html .= '<input type="radio" name="' . $name . '" value="' . $opt_value . '"' . $checked . '>';
			$html .= '<span class="wdm-radio-mark"></span>';
			$html .= '<span class="wdm-radio-text">' . $opt_label . '</span>';
			$html .= '</label>';
		}

		$html .= '</div>';

		if ( ! empty( $field_data['help_text'] ) ) {
			$html .= '<p class="wdm-help-text">' . $help_text . '</p>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * Sanitizes the value with sanitize_text_field and verifies it exists
	 * in the configured options list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value      The raw submitted value.
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_sanitize( $value, $field_data ) {
		$value      = sanitize_text_field( $value );
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );
		$options    = isset( $field_data['options'] ) && is_array( $field_data['options'] ) ? $field_data['options'] : array();

		if ( '' !== $value ) {
			$valid_values = wp_list_pluck( $options, 'value' );

			if ( ! in_array( $value, $valid_values, true ) ) {
				return '';
			}
		}

		return $value;
	}

	/**
	 * Validates the submitted value.
	 *
	 * Checks that the value is not empty when required and that the
	 * selected value exists in the configured options list.
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

		if ( '' !== $value ) {
			$options      = isset( $field_data['options'] ) && is_array( $field_data['options'] ) ? $field_data['options'] : array();
			$valid_values = wp_list_pluck( $options, 'value' );

			if ( ! in_array( $value, $valid_values, true ) ) {
				return new \WP_Error(
					'wprobo_documerge_invalid_option',
					'' !== $custom_error
						? esc_html( $custom_error )
						/* translators: %s: field label */
						: sprintf( __( '%s has an invalid selection.', 'wprobo-documerge-lite' ), $field_data['label'] )
				);
			}
		}

		return true;
	}
}
