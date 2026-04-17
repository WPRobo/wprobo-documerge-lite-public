<?php
/**
 * IP Address field type for WPRobo DocuMerge form builder.
 *
 * A hidden field that auto-captures the submitter's IP address.
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
 * Class WPRobo_DocuMerge_Field_Ip_Address
 *
 * Handles IP address fields that automatically capture the
 * submitter's IP address without any visible UI on the frontend.
 *
 * @since 1.4.0
 */
class WPRobo_DocuMerge_Field_Ip_Address {

	/**
	 * Returns the field type slug.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_type() {
		return 'ip_address';
	}

	/**
	 * Returns the translated human-readable label.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_label() {
		return __( 'IP Address', 'wprobo-documerge-lite' );
	}

	/**
	 * Returns the dashicon class name.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function wprobo_documerge_get_icon() {
		return 'dashicons-admin-site-alt3';
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
			'id'         => '',
			'type'       => 'ip_address',
			'label'      => 'IP Address',
			'name'       => 'ip_address',
			'width'      => 'full',
			'conditions' => array(),
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

		return $html;
	}

	/**
	 * Renders the frontend form HTML for this field.
	 *
	 * Outputs a hidden input pre-filled with the client IP address.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $field_data The field configuration data.
	 * @param string $value      The current field value.
	 * @return string
	 */
	public function wprobo_documerge_render_frontend( $field_data, $value = '' ) {
		$field_data = wp_parse_args( $field_data, $this->wprobo_documerge_get_default_config() );

		$id   = esc_attr( $field_data['id'] );
		$name = esc_attr( $field_data['name'] );
		$ip   = $this->wprobo_documerge_get_client_ip();

		return '<input type="hidden" id="wdm-field-' . $id . '" name="' . $name . '" value="' . esc_attr( $ip ) . '">';
	}

	/**
	 * Get the client IP address.
	 *
	 * Checks multiple server variables for proxy-forwarded IPs
	 * and validates the result.
	 *
	 * @since 1.4.0
	 *
	 * @return string The validated IP address or empty string.
	 */
	private function wprobo_documerge_get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $value      The raw submitted value.
	 * @param array $field_data The field configuration data.
	 * @return string
	 */
	public function wprobo_documerge_sanitize( $value, $field_data ) {
		$value = sanitize_text_field( $value );
		return filter_var( $value, FILTER_VALIDATE_IP ) ? $value : '';
	}

	/**
	 * Validates the submitted value.
	 *
	 * IP Address fields always pass validation as they are
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
