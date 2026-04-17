<?php
/**
 * Text field type for WPRobo DocuMerge form builder.
 *
 * @package   WPRobo_DocuMerge
 * @since     1.0.0
 */

namespace WPRobo\DocuMerge\Form\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Field_Text
 *
 * Handles text input fields within the DocuMerge form builder.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Field_Text {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'text';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'Text Field', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-editor-textcolor';
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
			'type'          => 'text',
			'label'         => 'Text Field',
			'name'          => '',
			'placeholder'   => '',
			'help_text'     => '',
			'required'      => false,
			'width'         => 'full',
			'min_length'    => '',
			'max_length'    => '',
			'error_message' => '',
			'conditions'    => array(),
		);

		/**
		 * Filters the default configuration for a form field type.
		 *
		 * @since 1.2.0
		 *
		 * @param array  $config The default field configuration array.
		 * @param string $type   The field type slug.
		 */
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
		$min_length    = esc_attr( $field_data['min_length'] );
		$max_length    = esc_attr( $field_data['max_length'] );
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

		// Required.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Required', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="checkbox" data-setting="required" class="wdm-builder-setting-input" ' . $required . '>';
		$html .= '</div>';

		// Min Length.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Min Length', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="number" data-setting="min_length" class="wdm-builder-setting-input" value="' . $min_length . '">';
		$html .= '</div>';

		// Max Length.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Max Length', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="number" data-setting="max_length" class="wdm-builder-setting-input" value="' . $max_length . '">';
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
		$max_length  = ! empty( $field_data['max_length'] ) ? 'maxlength="' . esc_attr( $field_data['max_length'] ) . '"' : '';
		$min_length  = ! empty( $field_data['min_length'] ) ? 'minlength="' . esc_attr( $field_data['min_length'] ) . '"' : '';
		$value_attr  = esc_attr( $value );

		$html  = '<div class="wdm-field-group" data-field-type="text">';
		$html .= '<label for="wdm-field-' . $id . '">' . $label;
		if ( ! empty( $field_data['required'] ) ) {
			$html .= ' <span class="wdm-required">*</span>';
		}
		$html .= '</label>';
		$html .= '<input type="text" id="wdm-field-' . $id . '" name="' . $name . '" class="wdm-input" value="' . $value_attr . '" placeholder="' . $placeholder . '" ' . $min_length . ' ' . $max_length . ' ' . $required . '>';
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

		if ( '' !== $value && ! empty( $field_data['min_length'] ) && mb_strlen( $value ) < (int) $field_data['min_length'] ) {
			return new \WP_Error(
				'wprobo_documerge_min_length',
				'' !== $custom_error
					? esc_html( $custom_error )
					/* translators: 1: field label, 2: minimum length */
					: sprintf( __( '%1$s must be at least %2$d characters.', 'wprobo-documerge-lite' ), $field_data['label'], (int) $field_data['min_length'] )
			);
		}

		if ( '' !== $value && ! empty( $field_data['max_length'] ) && mb_strlen( $value ) > (int) $field_data['max_length'] ) {
			return new \WP_Error(
				'wprobo_documerge_max_length',
				'' !== $custom_error
					? esc_html( $custom_error )
					/* translators: 1: field label, 2: maximum length */
					: sprintf( __( '%1$s must be no more than %2$d characters.', 'wprobo-documerge-lite' ), $field_data['label'], (int) $field_data['max_length'] )
			);
		}

		return true;
	}
}
