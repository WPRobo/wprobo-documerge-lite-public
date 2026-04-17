<?php
/**
 * Help & System Info page controller.
 *
 * Renders the Help page and gathers system diagnostic information
 * for support and troubleshooting.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Help_Page
 *
 * Displays the Help & System Info admin page, including environment
 * details useful for diagnosing issues.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Help_Page {

	/**
	 * Render the Help page.
	 *
	 * Checks user capabilities, gathers system information,
	 * and includes the template file.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		$system_info = $this->wprobo_documerge_get_system_info();

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/help/main.php';
	}

	/**
	 * Gather system and environment information.
	 *
	 * Collects plugin version, WordPress version, PHP version,
	 * library versions, Stripe mode, detected form plugins,
	 * upload limits, and memory configuration.
	 *
	 * @since  1.0.0
	 * @return array {
	 *     Associative array of system information.
	 *
	 *     @type string $plugin_version  Current plugin version.
	 *     @type string $wp_version      WordPress version.
	 *     @type string $php_version     PHP version.
	 *     @type string $phpword_version PHPWord library version or 'Not installed'.
	 *     @type string $mpdf_version    mPDF library version or 'Not installed'.
	 *     @type string $stripe_mode     Current Stripe mode (test or live).
	 *     @type array  $detected_plugins List of detected form plugins.
	 *     @type string $max_upload_size  Human-readable max upload size.
	 *     @type string $memory_limit    PHP memory limit.
	 * }
	 */
	public function wprobo_documerge_get_system_info() {
		global $wp_version;

		// PHPWord version detection.
		$phpword_version = __( 'Not installed', 'wprobo-documerge-lite' );
		if ( class_exists( '\PhpOffice\PhpWord\PhpWord' ) ) {
			$phpword_composer = WPROBO_DOCUMERGE_PATH . 'vendor/phpoffice/phpword/composer.json';
			if ( file_exists( $phpword_composer ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$composer_data = json_decode( file_get_contents( $phpword_composer ), true );
				if ( isset( $composer_data['version'] ) ) {
					$phpword_version = sanitize_text_field( $composer_data['version'] );
				}
			}
		}

		// mPDF version detection.
		$mpdf_version = __( 'Not installed', 'wprobo-documerge-lite' );
		if ( class_exists( '\Mpdf\Mpdf' ) ) {
			$mpdf_composer = WPROBO_DOCUMERGE_PATH . 'vendor/mpdf/mpdf/composer.json';
			if ( file_exists( $mpdf_composer ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$composer_data = json_decode( file_get_contents( $mpdf_composer ), true );
				if ( isset( $composer_data['version'] ) ) {
					$mpdf_version = sanitize_text_field( $composer_data['version'] );
				}
			}
		}

		// Detected form plugins (same detection as wizard).
		$detected_plugins = array();

		if ( function_exists( 'wpforms' ) ) {
			$detected_plugins[] = array(
				'name'    => 'WPForms',
				'slug'    => 'wpforms',
				'version' => defined( 'WPFORMS_VERSION' ) ? WPFORMS_VERSION : '',
			);
		}

		if ( class_exists( 'WPCF7' ) ) {
			$detected_plugins[] = array(
				'name'    => 'Contact Form 7',
				'slug'    => 'cf7',
				'version' => defined( 'WPCF7_VERSION' ) ? WPCF7_VERSION : '',
			);
		}

		if ( class_exists( 'GFForms' ) ) {
			$detected_plugins[] = array(
				'name'    => 'Gravity Forms',
				'slug'    => 'gravity',
				'version' => class_exists( 'GFCommon' ) ? \GFCommon::$version : '',
			);
		}

		if ( class_exists( '\FluentForm\App\Http\Controllers\FormController' ) ) {
			$detected_plugins[] = array(
				'name'    => 'Fluent Forms',
				'slug'    => 'fluent',
				'version' => defined( 'FLUENTFORM_VERSION' ) ? FLUENTFORM_VERSION : '',
			);
		}

		return array(
			'plugin_version'   => WPROBO_DOCUMERGE_VERSION,
			'wp_version'       => $wp_version,
			'php_version'      => PHP_VERSION,
			'phpword_version'  => $phpword_version,
			'mpdf_version'     => $mpdf_version,
			'detected_plugins' => ! empty( $detected_plugins )
				? implode(
					', ',
					array_map(
						function ( $p ) {
							return $p['name'] . ( ! empty( $p['version'] ) ? ' v' . $p['version'] : '' );
						},
						$detected_plugins
					)
				)
				: __( 'None detected', 'wprobo-documerge-lite' ),
			'max_upload_size'  => size_format( wp_max_upload_size() ),
			'memory_limit'     => ini_get( 'memory_limit' ),
		);
	}
}
