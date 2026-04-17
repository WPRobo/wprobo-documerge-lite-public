<?php
/**
 * Setup Wizard controller.
 *
 * Shows a 4-step setup wizard on first plugin activation.
 * Full-screen overlay: Welcome → Detect → Configure → Done.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Wizard
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Wizard;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Setup_Wizard
 *
 * Handles the first-activation setup wizard — registers the admin page,
 * enqueues assets, detects form plugins, and saves initial configuration.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Setup_Wizard {

	/**
	 * Register WordPress hooks for the wizard.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'admin_menu', array( $this, 'wprobo_documerge_register_wizard_page' ) );
		add_action( 'admin_init', array( $this, 'wprobo_documerge_redirect_after_activation' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wprobo_documerge_enqueue_wizard_assets' ) );
		add_action( 'wp_ajax_wprobo_documerge_wizard_save', array( $this, 'wprobo_documerge_ajax_wizard_save' ) );
	}

	/**
	 * Register hidden admin page for the wizard.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_register_wizard_page() {
		add_submenu_page(
			null,
			__( 'DocuMerge Setup', 'wprobo-documerge-lite' ),
			__( 'DocuMerge Setup', 'wprobo-documerge-lite' ),
			'manage_options',
			'wprobo-documerge-wizard',
			array( $this, 'wprobo_documerge_render_wizard' )
		);
	}

	/**
	 * Redirect to wizard page after first activation.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_redirect_after_activation() {
		if ( ! get_transient( 'wprobo_documerge_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'wprobo_documerge_activation_redirect' );

		// Do not redirect on bulk activations or network admin.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wprobo-documerge-wizard' ) );
		exit;
	}

	/**
	 * Enqueue wizard-specific assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook suffix.
	 */
	public function wprobo_documerge_enqueue_wizard_assets( $hook ) {
		if ( 'admin_page_wprobo-documerge-wizard' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wprobo-documerge-wizard',
			WPROBO_DOCUMERGE_URL . 'assets/css/admin/wizard.min.css',
			array(),
			WPROBO_DOCUMERGE_VERSION
		);

		wp_enqueue_script(
			'wprobo-documerge-wizard',
			WPROBO_DOCUMERGE_URL . 'assets/js/admin/setup-wizard.min.js',
			array( 'jquery' ),
			WPROBO_DOCUMERGE_VERSION,
			true
		);

		wp_localize_script(
			'wprobo-documerge-wizard',
			'wprobo_documerge_wizard_vars',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'wprobo_documerge_wizard' ),
				'dashboard_url'    => admin_url( 'admin.php?page=wprobo-documerge' ),
				'templates_url'    => admin_url( 'admin.php?page=wprobo-documerge-templates' ),
				'detected_plugins' => $this->wprobo_documerge_detect_form_plugins(),
				'i18n'             => array(
					'saving'           => __( 'Saving...', 'wprobo-documerge-lite' ),
					'saved'            => __( 'Saved!', 'wprobo-documerge-lite' ),
					'error'            => __( 'An error occurred. Please try again.', 'wprobo-documerge-lite' ),
					'network_error'    => __( 'Network error. Please check your connection.', 'wprobo-documerge-lite' ),
					'no_plugins_title' => __( 'No supported form plugins detected.', 'wprobo-documerge-lite' ),
					'no_plugins_desc'  => __( 'DocuMerge will use its built-in form builder. You can change this at any time in Settings.', 'wprobo-documerge-lite' ),
					'detected_suffix'  => __( 'detected', 'wprobo-documerge-lite' ),
					'integrate_prefix' => __( 'Would you like DocuMerge to integrate with', 'wprobo-documerge-lite' ),
					'yes_use_prefix'   => __( 'Yes — use', 'wprobo-documerge-lite' ),
					'yes_use_suffix'   => __( 'with DocuMerge', 'wprobo-documerge-lite' ),
					'standalone_label' => __( 'No — use DocuMerge standalone forms', 'wprobo-documerge-lite' ),
				),
			)
		);
	}

	/**
	 * Detect installed and active form plugins.
	 *
	 * @since  1.0.0
	 * @return array List of detected form plugins with name, slug, and version.
	 */
	private function wprobo_documerge_detect_form_plugins() {
		$detected = array();

		if ( function_exists( 'wpforms' ) ) {
			$version    = defined( 'WPFORMS_VERSION' ) ? WPFORMS_VERSION : '';
			$detected[] = array(
				'name'    => 'WPForms',
				'slug'    => 'wpforms',
				'version' => $version,
			);
		}

		if ( class_exists( 'WPCF7' ) ) {
			$version    = defined( 'WPCF7_VERSION' ) ? WPCF7_VERSION : '';
			$detected[] = array(
				'name'    => 'Contact Form 7',
				'slug'    => 'cf7',
				'version' => $version,
			);
		}

		if ( class_exists( 'GFForms' ) ) {
			$version    = class_exists( 'GFCommon' ) ? \GFCommon::$version : '';
			$detected[] = array(
				'name'    => 'Gravity Forms',
				'slug'    => 'gravity',
				'version' => $version,
			);
		}

		if ( class_exists( '\FluentForm\App\Http\Controllers\FormController' ) ) {
			$version    = defined( 'FLUENTFORM_VERSION' ) ? FLUENTFORM_VERSION : '';
			$detected[] = array(
				'name'    => 'Fluent Forms',
				'slug'    => 'fluent',
				'version' => $version,
			);
		}

		return $detected;
	}

	/**
	 * Render the wizard page.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_render_wizard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/wizard/wizard.php';
	}

	/**
	 * AJAX handler — save wizard settings and mark wizard complete.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_ajax_wizard_save() {
		check_ajax_referer( 'wprobo_documerge_wizard', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$integration   = isset( $_POST['integration'] ) ? sanitize_key( wp_unslash( $_POST['integration'] ) ) : '';
		$output_format = isset( $_POST['output_format'] ) ? sanitize_key( wp_unslash( $_POST['output_format'] ) ) : 'pdf';
		$doc_storage   = isset( $_POST['doc_storage'] ) ? sanitize_key( wp_unslash( $_POST['doc_storage'] ) ) : 'keep';

		$delivery_methods = array();
		if ( isset( $_POST['delivery_methods'] ) && is_array( $_POST['delivery_methods'] ) ) {
			$delivery_methods = array_map( 'sanitize_key', wp_unslash( $_POST['delivery_methods'] ) );
		}

		// Validate allowed values.
		$allowed_formats  = array( 'pdf', 'docx', 'both' );
		$allowed_delivery = array( 'download', 'email', 'media' );
		$allowed_storage  = array( 'keep', 'delete' );

		if ( ! in_array( $output_format, $allowed_formats, true ) ) {
			$output_format = 'pdf';
		}
		if ( ! in_array( $doc_storage, $allowed_storage, true ) ) {
			$doc_storage = 'keep';
		}
		$delivery_methods = array_intersect( $delivery_methods, $allowed_delivery );
		if ( empty( $delivery_methods ) ) {
			$delivery_methods = array( 'download' );
		}

		// Save settings.
		$settings = get_option( 'wprobo_documerge_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['output_format']    = $output_format;
		$settings['delivery_method']  = implode( ',', $delivery_methods );
		$settings['delete_docs_days'] = ( 'delete' === $doc_storage ) ? 30 : 0;
		update_option( 'wprobo_documerge_settings', $settings );

		// Lite is always standalone. Integration is Pro-only.
		update_option( 'wprobo_documerge_form_mode', 'standalone' );

		// Save delivery method options individually (same keys Settings page uses).
		update_option( 'wprobo_documerge_default_output_format', $output_format );
		update_option( 'wprobo_documerge_delivery_download', in_array( 'download', $delivery_methods, true ) ? '1' : '0' );
		update_option( 'wprobo_documerge_delivery_email', in_array( 'email', $delivery_methods, true ) ? '1' : '0' );
		update_option( 'wprobo_documerge_delivery_media', in_array( 'media', $delivery_methods, true ) ? '1' : '0' );
		update_option( 'wprobo_documerge_auto_delete_days', ( 'delete' === $doc_storage ) ? 30 : 0 );

		// Mark wizard as completed.
		update_option( 'wprobo_documerge_wizard_completed', 'yes' );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'wprobo-documerge-lite' ) ) );
	}
}
