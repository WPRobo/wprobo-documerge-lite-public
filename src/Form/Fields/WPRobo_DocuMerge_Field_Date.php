<?php
/**
 * Date field type for WPRobo DocuMerge form builder.
 *
 * @package   WPRobo_DocuMerge
 * @since     1.0.0
 */

namespace WPRobo\DocuMerge\Form\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Field_Date
 *
 * Handles date input fields within the DocuMerge form builder.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Field_Date {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'date';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'Date', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-calendar-alt';
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
			'type'              => 'date',
			'label'             => 'Date',
			'name'              => '',
			'placeholder'       => '',
			'help_text'         => '',
			'required'          => false,
			'width'             => 'full',
			'date_format'       => 'Y-m-d',
			'min_date'          => '',
			'max_date'          => '',
			'disable_past'      => false,
			'max_future_months' => '',
			'error_message'     => '',
			'conditions'        => array(),
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
		$date_format   = esc_attr( $field_data['date_format'] );
		$min_date      = esc_attr( $field_data['min_date'] );
		$max_date      = esc_attr( $field_data['max_date'] );
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

		// Date Format.
		$today_preview = wp_date( 'Y-m-d' );
		$date_formats  = array(
			'Y-m-d'  => wp_date( 'Y-m-d' ),
			'd/m/Y'  => wp_date( 'd/m/Y' ),
			'm/d/Y'  => wp_date( 'm/d/Y' ),
			'd-m-Y'  => wp_date( 'd-m-Y' ),
			'F j, Y' => wp_date( 'F j, Y' ),
		);

		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Date Format', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<select data-setting="date_format" class="wdm-builder-setting-input">';
		foreach ( $date_formats as $format_value => $format_preview ) {
			$html .= '<option value="' . esc_attr( $format_value ) . '"' . selected( $date_format, $format_value, false ) . '>' . esc_html( $format_value . ' (' . $format_preview . ')' ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</div>';

		// Min Date.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Min Date', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="min_date" class="wdm-builder-setting-input" value="' . $min_date . '" placeholder="' . esc_attr__( 'YYYY-MM-DD', 'wprobo-documerge-lite' ) . '">';
		$html .= '</div>';

		// Max Date.
		$html .= '<div class="wdm-builder-field-setting">';
		$html .= '<label>' . esc_html__( 'Max Date', 'wprobo-documerge-lite' ) . '</label>';
		$html .= '<input type="text" data-setting="max_date" class="wdm-builder-setting-input" value="' . $max_date . '" placeholder="' . esc_attr__( 'YYYY-MM-DD', 'wprobo-documerge-lite' ) . '">';
		$html .= '</div>';

		// Disable past dates.
		$disable_past_checked = ! empty( $field_data['disable_past'] ) ? 'checked' : '';
		$html                .= '<div class="wdm-builder-field-setting">';
		$html                .= '<label><input type="checkbox" data-setting="disable_past" class="wdm-builder-setting-input" ' . $disable_past_checked . '> ';
		$html                .= esc_html__( 'Disable past dates (today is the earliest)', 'wprobo-documerge-lite' ) . '</label>';
		$html                .= '</div>';

		// Max future months.
		$max_future_months = esc_attr( isset( $field_data['max_future_months'] ) ? $field_data['max_future_months'] : '' );
		$html             .= '<div class="wdm-builder-field-setting">';
		$html             .= '<label>' . esc_html__( 'Max Future Months', 'wprobo-documerge-lite' ) . '</label>';
		$html             .= '<input type="number" data-setting="max_future_months" class="wdm-builder-setting-input wdm-input" value="' . $max_future_months . '" placeholder="' . esc_attr__( 'e.g. 6 (leave blank for unlimited)', 'wprobo-documerge-lite' ) . '" min="0">';
		$html             .= '<span class="wdm-description">' . esc_html__( 'How many months ahead users can select. 0 or blank = unlimited.', 'wprobo-documerge-lite' ) . '</span>';
		$html             .= '</div>';

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
		$help_text   = esc_html( $field_data['help_text'] );
		$required    = ! empty( $field_data['required'] ) ? 'required' : '';
		$value_attr  = esc_attr( $value );
		$date_format = ! empty( $field_data['date_format'] ) ? $field_data['date_format'] : 'Y-m-d';

		// Build a human-readable example from the PHP date format.
		$format_examples = array(
			'Y-m-d'  => '2026-03-25',
			'd/m/Y'  => '25/03/2026',
			'm/d/Y'  => '03/25/2026',
			'd-m-Y'  => '25-03-2026',
			'd.m.Y'  => '25.03.2026',
			'F j, Y' => 'March 25, 2026',
			'M j, Y' => 'Mar 25, 2026',
			'j F Y'  => '25 March 2026',
		);
		$format_hint     = isset( $format_examples[ $date_format ] ) ? $format_examples[ $date_format ] : wp_date( $date_format );

		// Use the format example as placeholder if no custom placeholder is set.
		$placeholder = ! empty( $field_data['placeholder'] ) ? esc_attr( $field_data['placeholder'] ) : esc_attr( $format_hint );

		$html  = '<div class="wdm-field-group" data-field-type="date">';
		$html .= '<label for="wdm-field-' . $id . '">' . $label;
		if ( ! empty( $field_data['required'] ) ) {
			$html .= ' <span class="wdm-required">*</span>';
		}
		$html .= '</label>';
		// Build Flatpickr data attributes.
		$disable_past      = ! empty( $field_data['disable_past'] );
		$max_future_months = ! empty( $field_data['max_future_months'] ) ? absint( $field_data['max_future_months'] ) : 0;

		$html .= '<input type="text" id="wdm-field-' . $id . '" name="' . $name . '" value="' . $value_attr . '" placeholder="' . $placeholder . '" class="wdm-input wdm-datepicker" ' . $required;
		$html .= ' data-date-format="' . esc_attr( $date_format ) . '"';
		if ( $disable_past ) {
			$html .= ' data-disable-past="1"';
		}
		if ( $max_future_months > 0 ) {
			$html .= ' data-max-future-months="' . esc_attr( $max_future_months ) . '"';
		}
		if ( ! empty( $field_data['min_date'] ) ) {
			$html .= ' data-min-date="' . esc_attr( $field_data['min_date'] ) . '"';
		}
		if ( ! empty( $field_data['max_date'] ) ) {
			$html .= ' data-max-date="' . esc_attr( $field_data['max_date'] ) . '"';
		}
		$html .= '>';
		$html .= '<p class="wdm-help-text">';
		if ( ! empty( $field_data['help_text'] ) ) {
			$html .= $help_text . ' ';
		}
		/* translators: %s: date format example like 25/03/2026 */
		$html .= '<span class="wdm-date-format-hint">' . sprintf( esc_html__( 'Format: %s', 'wprobo-documerge-lite' ), esc_html( $format_hint ) ) . '</span>';
		$html .= '</p>';
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

		if ( '' !== $value ) {
			$format   = $field_data['date_format'];
			$date_obj = \DateTime::createFromFormat( $format, $value );

			if ( ! $date_obj || $date_obj->format( $format ) !== $value ) {
				return new \WP_Error(
					'wprobo_documerge_invalid_date',
					'' !== $custom_error
						? esc_html( $custom_error )
						/* translators: 1: field label, 2: expected date format */
						: sprintf( __( '%1$s must be a valid date in the format %2$s.', 'wprobo-documerge-lite' ), $field_data['label'], $format )
				);
			}

			// Min date check.
			if ( ! empty( $field_data['min_date'] ) ) {
				$min_date_obj = \DateTime::createFromFormat( 'Y-m-d', $field_data['min_date'] );
				if ( $min_date_obj && $date_obj < $min_date_obj ) {
					return new \WP_Error(
						'wprobo_documerge_min_date',
						'' !== $custom_error
							? esc_html( $custom_error )
							/* translators: 1: field label, 2: minimum date */
							: sprintf( __( '%1$s must be on or after %2$s.', 'wprobo-documerge-lite' ), $field_data['label'], $field_data['min_date'] )
					);
				}
			}

			// Max date check.
			if ( ! empty( $field_data['max_date'] ) ) {
				$max_date_obj = \DateTime::createFromFormat( 'Y-m-d', $field_data['max_date'] );
				if ( $max_date_obj && $date_obj > $max_date_obj ) {
					return new \WP_Error(
						'wprobo_documerge_max_date',
						'' !== $custom_error
							? esc_html( $custom_error )
							/* translators: 1: field label, 2: maximum date */
							: sprintf( __( '%1$s must be on or before %2$s.', 'wprobo-documerge-lite' ), $field_data['label'], $field_data['max_date'] )
					);
				}
			}
		}

		return true;
	}
}
