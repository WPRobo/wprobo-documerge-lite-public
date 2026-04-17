<?php
/**
 * Pro Upsell — renders upgrade prompts and locked UI elements.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Pro_Upsell
 *
 * Provides static helper methods for rendering Pro upsell UI elements
 * such as badges, overlays, disabled toggles, and locked field types.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Pro_Upsell {

	/**
	 * Render a small PRO badge inline.
	 *
	 * @since  1.0.0
	 * @return string HTML markup for the PRO badge.
	 */
	public static function wprobo_documerge_render_badge() {
		return '<span class="wdm-pro-badge">' . esc_html__( 'PRO', 'wprobo-documerge-lite' ) . '</span>';
	}

	/**
	 * Render a blurred overlay with lock icon, title, description, and upgrade button.
	 *
	 * @since  1.0.0
	 * @param  string $title       The feature title to display.
	 * @param  string $description A short description of the locked feature.
	 * @return string HTML markup for the overlay.
	 */
	public static function wprobo_documerge_render_overlay( $title, $description ) {
		$upgrade_url = WPROBO_DOCUMERGE_UPGRADE_URL;

		$html  = '<div class="wdm-pro-overlay">';
		$html .= '<div class="wdm-pro-overlay-content">';
		$html .= '<span class="dashicons dashicons-lock wdm-pro-overlay-icon" aria-hidden="true"></span>';
		$html .= '<h3 class="wdm-pro-overlay-title">' . esc_html( $title ) . '</h3>';
		$html .= '<p class="wdm-pro-overlay-desc">' . esc_html( $description ) . '</p>';
		$html .= '<a href="' . esc_url( $upgrade_url ) . '" class="wdm-btn wdm-btn-primary wdm-pro-overlay-btn" target="_blank" rel="noopener noreferrer">';
		$html .= esc_html__( 'Upgrade to Pro', 'wprobo-documerge-lite' );
		$html .= '</a>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a disabled toggle (checkbox) with a PRO badge.
	 *
	 * @since  1.0.0
	 * @param  string $label The label text for the toggle.
	 * @return string HTML markup for the disabled toggle.
	 */
	public static function wprobo_documerge_render_disabled_toggle( $label ) {
		$html  = '<label class="wdm-pro-disabled-toggle">';
		$html .= '<input type="checkbox" disabled="disabled" />';
		$html .= ' ' . esc_html( $label );
		$html .= ' ' . self::wprobo_documerge_render_badge();
		$html .= '</label>';

		return $html;
	}

	/**
	 * Render a locked field type button for the form builder sidebar.
	 *
	 * @since  1.0.0
	 * @param  string $icon  Dashicon class name (e.g. 'dashicons-edit').
	 * @param  string $label The field type label.
	 * @return string HTML markup for the locked field type.
	 */
	public static function wprobo_documerge_render_field_type_locked( $icon, $label ) {
		$upgrade_url = WPROBO_DOCUMERGE_UPGRADE_URL;

		$html  = '<a href="' . esc_url( $upgrade_url ) . '" class="wdm-pro-locked-field" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Upgrade to Pro to unlock this field type', 'wprobo-documerge-lite' ) . '">';
		$html .= '<span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
		$html .= '<span class="wdm-pro-locked-field-label">' . esc_html( $label ) . '</span>';
		$html .= ' ' . self::wprobo_documerge_render_badge();
		$html .= '<span class="dashicons dashicons-lock wdm-pro-locked-field-lock" aria-hidden="true"></span>';
		$html .= '</a>';

		return $html;
	}
}
