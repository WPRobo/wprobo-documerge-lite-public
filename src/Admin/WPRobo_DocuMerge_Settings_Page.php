<?php
/**
 * Settings page controller.
 *
 * Handles the multi-tab settings page (General, Stripe, Email,
 * reCAPTCHA, Advanced, Styles, Custom CSS) and AJAX save handlers for each tab.
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

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Settings_Page
 *
 * Renders the Settings page with seven tabs and processes AJAX
 * requests to save each tab's options independently.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Settings_Page {

	/**
	 * Register WordPress hooks for settings AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'wp_ajax_wprobo_documerge_save_general', array( $this, 'wprobo_documerge_ajax_save_general' ) );
		add_action( 'wp_ajax_wprobo_documerge_save_advanced', array( $this, 'wprobo_documerge_ajax_save_advanced' ) );
		add_action( 'wp_ajax_wprobo_documerge_reset_wizard', array( $this, 'wprobo_documerge_ajax_reset_wizard' ) );
		add_action( 'wp_ajax_wprobo_documerge_danger_zone', array( $this, 'wprobo_documerge_ajax_danger_zone' ) );
		add_action( 'wp_ajax_wprobo_documerge_export_data', array( $this, 'wprobo_documerge_ajax_export_data' ) );
		add_action( 'wp_ajax_wprobo_documerge_import_data', array( $this, 'wprobo_documerge_ajax_import_data' ) );
	}

	/**
	 * AJAX handler — Danger Zone destructive actions.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_danger_zone() {
		check_ajax_referer( 'wprobo_documerge_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$action = isset( $_POST['danger_action'] ) ? sanitize_key( wp_unslash( $_POST['danger_action'] ) ) : '';

		global $wpdb;

		// Init WP Filesystem for file deletion.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		\WP_Filesystem();
		global $wp_filesystem;

		$upload_dir = wp_upload_dir();

		switch ( $action ) {

			case 'delete_submissions':
				// Delete document files.
				$submissions = $wpdb->get_results( "SELECT doc_path_docx, doc_path_pdf FROM {$wpdb->prefix}wprdm_submissions" );
				foreach ( $submissions as $sub ) {
					if ( ! empty( $sub->doc_path_docx ) && $wp_filesystem->exists( $sub->doc_path_docx ) ) {
						$wp_filesystem->delete( $sub->doc_path_docx );
					}
					if ( ! empty( $sub->doc_path_pdf ) && $wp_filesystem->exists( $sub->doc_path_pdf ) ) {
						$wp_filesystem->delete( $sub->doc_path_pdf );
					}
				}
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_submissions" );
				wp_send_json_success( array( 'message' => __( 'All submissions and documents deleted.', 'wprobo-documerge-lite' ) ) );
				break;

			case 'delete_forms':
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_forms" );
				delete_transient( 'wprobo_documerge_forms_count' );
				wp_send_json_success( array( 'message' => __( 'All forms deleted.', 'wprobo-documerge-lite' ) ) );
				break;

			case 'delete_templates':
				$templates = $wpdb->get_results( "SELECT file_path FROM {$wpdb->prefix}wprdm_templates" );
				foreach ( $templates as $tpl ) {
					if ( ! empty( $tpl->file_path ) && $wp_filesystem->exists( $tpl->file_path ) ) {
						$wp_filesystem->delete( $tpl->file_path );
					}
				}
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_templates" );
				delete_transient( 'wprobo_documerge_templates_count' );
				delete_transient( 'wprobo_documerge_templates_list' );
				wp_send_json_success( array( 'message' => __( 'All templates and DOCX files deleted.', 'wprobo-documerge-lite' ) ) );
				break;

			case 'delete_documents':
				$docs_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir();
				if ( $wp_filesystem->is_dir( $docs_dir ) ) {
					$wp_filesystem->delete( $docs_dir, true );
					wp_mkdir_p( $docs_dir );
				}
				// Clear file paths in submissions.
				$wpdb->query( "UPDATE {$wpdb->prefix}wprdm_submissions SET doc_path_docx = '', doc_path_pdf = ''" );
				wp_send_json_success( array( 'message' => __( 'All generated documents deleted.', 'wprobo-documerge-lite' ) ) );
				break;

			case 'reset_settings':
				$like = $wpdb->esc_like( 'wprobo_documerge_' ) . '%';
				$options_to_delete = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT IN ('wprobo_documerge_db_version', 'wprobo_documerge_wizard_completed')",
						$like
					)
				);
				foreach ( $options_to_delete as $opt ) {
					delete_option( $opt );
				}
				wp_send_json_success( array( 'message' => __( 'All settings reset to defaults.', 'wprobo-documerge-lite' ) ) );
				break;

			case 'factory_reset':
				// Delete all document files.
				$docs_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir();
				$temp_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_temp_dir();
				$logs_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_logs_dir();

				foreach ( array( $docs_dir, $temp_dir, $logs_dir ) as $dir ) {
					if ( $wp_filesystem->is_dir( $dir ) ) {
						$wp_filesystem->delete( $dir, true );
					}
				}

				// Truncate all tables.
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_submissions" );
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_forms" );
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wprdm_templates" );

				// Delete all plugin options.
				$factory_like = $wpdb->esc_like( 'wprobo_documerge_' ) . '%';
				$all_options = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$factory_like
					)
				);
				foreach ( $all_options as $opt ) {
					delete_option( $opt );
				}

				// Delete all transients.
				$transient_like = '%' . $wpdb->esc_like( 'wprobo_documerge' ) . '%';
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
						$transient_like
					)
				);

				// Clear crons.
				wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_temp_files' );
				wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_log_files' );
				wp_clear_scheduled_hook( 'wprobo_documerge_retry_failed_emails' );

				// Recreate directories.
				wp_mkdir_p( $docs_dir );
				wp_mkdir_p( $temp_dir );
				wp_mkdir_p( $logs_dir );

				// Mark as needing wizard.
				update_option( 'wprobo_documerge_wizard_completed', 'no' );

				wp_send_json_success( array( 'message' => __( 'Factory reset complete. The setup wizard will launch on next page load.', 'wprobo-documerge-lite' ) ) );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'wprobo-documerge-lite' ) ) );
		}
	}

	/**
	 * AJAX handler — reset wizard so it runs again on next page load.
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_reset_wizard() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		update_option( 'wprobo_documerge_wizard_completed', 'no' );

		wp_send_json_success(
			array(
				'redirect' => admin_url( 'admin.php?page=wprobo-documerge-wizard' ),
			)
		);
	}

	/**
	 * Render the Settings page.
	 *
	 * Checks user capabilities and includes the settings template.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/settings/main.php';
	}

	/**
	 * AJAX handler — save General tab settings.
	 *
	 * Saves output format, delivery methods, download expiry,
	 * form mode, integration, date/time formats, and notification preferences.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_ajax_save_general() {
		check_ajax_referer( 'wprobo_documerge_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Output format.
		$output_format   = isset( $_POST['wprobo_documerge_default_output_format'] )
			? sanitize_key( wp_unslash( $_POST['wprobo_documerge_default_output_format'] ) )
			: 'pdf';
		$allowed_formats = array( 'pdf', 'docx', 'both' );
		if ( ! in_array( $output_format, $allowed_formats, true ) ) {
			$output_format = 'pdf';
		}
		update_option( 'wprobo_documerge_default_output_format', $output_format );

		// Delivery methods (checkboxes — '1' or '0').
		$delivery_download = isset( $_POST['wprobo_documerge_delivery_download'] ) ? '1' : '0';
		$delivery_email    = isset( $_POST['wprobo_documerge_delivery_email'] ) ? '1' : '0';
		$delivery_media    = isset( $_POST['wprobo_documerge_delivery_media'] ) ? '1' : '0';
		update_option( 'wprobo_documerge_delivery_download', $delivery_download );
		update_option( 'wprobo_documerge_delivery_email', $delivery_email );
		update_option( 'wprobo_documerge_delivery_media', $delivery_media );

		// Download expiry hours.
		$expiry_hours = isset( $_POST['wprobo_documerge_download_expiry_hours'] )
			? absint( wp_unslash( $_POST['wprobo_documerge_download_expiry_hours'] ) )
			: 48;
		update_option( 'wprobo_documerge_download_expiry_hours', $expiry_hours );

		// Form mode.
		$form_mode     = isset( $_POST['wprobo_documerge_form_mode'] )
			? sanitize_key( wp_unslash( $_POST['wprobo_documerge_form_mode'] ) )
			: 'standalone';
		$allowed_modes = array( 'standalone', 'integrated' );
		if ( ! in_array( $form_mode, $allowed_modes, true ) ) {
			$form_mode = 'standalone';
		}
		update_option( 'wprobo_documerge_form_mode', $form_mode );

		// Active integration.
		$active_integration = isset( $_POST['wprobo_documerge_active_integration'] )
			? sanitize_key( wp_unslash( $_POST['wprobo_documerge_active_integration'] ) )
			: '';
		update_option( 'wprobo_documerge_active_integration', $active_integration );

		// Date and time formats.
		$date_format = isset( $_POST['wprobo_documerge_date_format'] )
			? sanitize_text_field( wp_unslash( $_POST['wprobo_documerge_date_format'] ) )
			: 'Y-m-d';
		$time_format = isset( $_POST['wprobo_documerge_time_format'] )
			? sanitize_text_field( wp_unslash( $_POST['wprobo_documerge_time_format'] ) )
			: 'H:i';
		update_option( 'wprobo_documerge_date_format', $date_format );
		update_option( 'wprobo_documerge_time_format', $time_format );

		// Notification preferences.
		$notify_submission = isset( $_POST['wprobo_documerge_notify_new_submission'] ) ? '1' : '0';
		$notify_error      = isset( $_POST['wprobo_documerge_notify_on_error'] ) ? '1' : '0';
		update_option( 'wprobo_documerge_notify_new_submission', $notify_submission );
		update_option( 'wprobo_documerge_notify_on_error', $notify_error );

		// Notification email.
		$notification_email = isset( $_POST['wprobo_documerge_notification_email'] )
			? sanitize_email( wp_unslash( $_POST['wprobo_documerge_notification_email'] ) )
			: '';
		update_option( 'wprobo_documerge_notification_email', $notification_email );

		/**
		 * Fires after plugin settings are saved.
		 *
		 * Allows triggering cache busting, external sync, or logging
		 * after settings change. Important for sites using object caching.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab      The settings tab that was saved.
		 * @param array  $settings The saved settings data.
		 */
		do_action(
			'wprobo_documerge_settings_saved',
			'general',
			array(
				'output_format'      => $output_format,
				'delivery_download'  => $delivery_download,
				'delivery_email'     => $delivery_email,
				'delivery_media'     => $delivery_media,
				'expiry_hours'       => $expiry_hours,
				'form_mode'          => $form_mode,
				'active_integration' => $active_integration,
				'date_format'        => $date_format,
				'time_format'        => $time_format,
				'notify_submission'  => $notify_submission,
				'notify_error'       => $notify_error,
				'notification_email' => $notification_email,
			)
		);

		wp_send_json_success( array( 'message' => __( 'General settings saved.', 'wprobo-documerge-lite' ) ) );
	}


	/**
	 * AJAX handler — save Advanced tab settings.
	 *
	 * Saves auto-delete days, log retention, debug logging toggle,
	 * and uninstall data removal preference.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_ajax_save_advanced() {
		check_ajax_referer( 'wprobo_documerge_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Auto-delete days.
		$auto_delete_days = isset( $_POST['wprobo_documerge_auto_delete_days'] )
			? absint( wp_unslash( $_POST['wprobo_documerge_auto_delete_days'] ) )
			: 0;
		update_option( 'wprobo_documerge_auto_delete_days', $auto_delete_days );

		// Log retention days.
		$log_retention_days = isset( $_POST['wprobo_documerge_log_retention_days'] )
			? absint( wp_unslash( $_POST['wprobo_documerge_log_retention_days'] ) )
			: 30;
		update_option( 'wprobo_documerge_log_retention_days', $log_retention_days );

		// Debug logging.
		$debug_logging = isset( $_POST['wprobo_documerge_debug_logging'] ) ? '1' : '0';
		update_option( 'wprobo_documerge_debug_logging', $debug_logging );

		// Uninstall data removal.
		$uninstall_data = isset( $_POST['wprobo_documerge_uninstall_data'] ) ? '1' : '0';
		update_option( 'wprobo_documerge_uninstall_data', $uninstall_data );

		/** This action is documented in src/Admin/WPRobo_DocuMerge_Settings_Page.php */
		do_action(
			'wprobo_documerge_settings_saved',
			'advanced',
			array(
				'auto_delete_days'   => $auto_delete_days,
				'log_retention_days' => $log_retention_days,
				'debug_logging'      => $debug_logging,
				'uninstall_data'     => $uninstall_data,
			)
		);

		wp_send_json_success( array( 'message' => __( 'Advanced settings saved.', 'wprobo-documerge-lite' ) ) );
	}

	/**
	 * AJAX handler — export plugin data as JSON.
	 *
	 * Collects the requested data types (templates, forms, submissions,
	 * settings, analytics) and sends a JSON file download response.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_export_data() {
		check_ajax_referer( 'wprobo_documerge_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$types = isset( $_POST['export_types'] ) ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['export_types'] ) ) : array();

		if ( empty( $types ) ) {
			wp_send_json_error( array( 'message' => __( 'No data types selected for export.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$allowed_types = array( 'templates', 'forms', 'submissions', 'settings' );
		$types         = array_intersect( $types, $allowed_types );

		if ( empty( $types ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data types selected.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;
		$export_data = array();

		// Templates.
		if ( in_array( 'templates', $types, true ) ) {
			$export_data['templates'] = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}wprdm_templates ORDER BY id ASC",
				ARRAY_A
			);
		}

		// Forms.
		if ( in_array( 'forms', $types, true ) ) {
			$export_data['forms'] = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}wprdm_forms ORDER BY id ASC",
				ARRAY_A
			);
		}

		// Submissions.
		if ( in_array( 'submissions', $types, true ) ) {
			$export_data['submissions'] = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}wprdm_submissions ORDER BY id ASC",
				ARRAY_A
			);
		}

		// Settings.
		if ( in_array( 'settings', $types, true ) ) {
			$export_like = $wpdb->esc_like( 'wprobo_documerge_' ) . '%';
			$options  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC",
					$export_like
				),
				ARRAY_A
			);
			$settings = array();
			foreach ( $options as $row ) {
				$settings[ $row['option_name'] ] = $row['option_value'];
			}
			$export_data['settings'] = $settings;
		}

		$payload = array(
			'plugin'      => 'wprobo-documerge',
			'version'     => defined( 'WPROBO_DOCUMERGE_VERSION' ) ? WPROBO_DOCUMERGE_VERSION : '1.0.0',
			'exported_at' => gmdate( 'c' ),
			'site_url'    => get_site_url(),
			'data'        => $export_data,
		);

		wp_send_json_success(
			array(
				'json'     => $payload,
				'filename' => 'documerge-export-' . gmdate( 'Y-m-d-His' ) . '.json',
			)
		);
	}

	/**
	 * AJAX handler — import plugin data from a JSON upload.
	 *
	 * Validates the JSON payload, checks structure, then imports
	 * the selected data types using merge or replace strategy.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_import_data() {
		check_ajax_referer( 'wprobo_documerge_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Read and sanitize the JSON payload from POST body. sanitize_textarea_field
		// preserves line breaks inside the JSON while stripping any raw HTML/tags.
		$json_raw = isset( $_POST['import_json'] )
			? sanitize_textarea_field( wp_unslash( $_POST['import_json'] ) )
			: '';
		$mode     = isset( $_POST['import_mode'] ) ? sanitize_key( wp_unslash( $_POST['import_mode'] ) ) : 'merge';
		$types    = isset( $_POST['import_types'] ) ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['import_types'] ) ) : array();

		if ( empty( $json_raw ) ) {
			wp_send_json_error( array( 'message' => __( 'No import data received.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Decode — json_decode() does NOT sanitize. Every decoded field must
		// be sanitized per-type below before being inserted/updated.
		$data = json_decode( $json_raw, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON file. Could not parse the data.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Validate structure.
		if ( ! isset( $data['plugin'] ) || 'wprobo-documerge' !== $data['plugin'] ) {
			wp_send_json_error( array( 'message' => __( 'This file is not a valid DocuMerge export.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Export file contains no data.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		if ( ! in_array( $mode, array( 'merge', 'replace' ), true ) ) {
			$mode = 'merge';
		}

		$allowed_types = array( 'templates', 'forms', 'submissions', 'settings' );
		$types         = array_intersect( $types, $allowed_types );

		if ( empty( $types ) ) {
			wp_send_json_error( array( 'message' => __( 'No data types selected for import.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;
		$results = array();

		// Templates.
		if ( in_array( 'templates', $types, true ) && isset( $data['data']['templates'] ) && is_array( $data['data']['templates'] ) ) {
			$results['templates'] = $this->wprobo_documerge_import_table_rows(
				$wpdb->prefix . 'wprdm_templates',
				$data['data']['templates'],
				$mode
			);
		}

		// Forms.
		if ( in_array( 'forms', $types, true ) && isset( $data['data']['forms'] ) && is_array( $data['data']['forms'] ) ) {
			$results['forms'] = $this->wprobo_documerge_import_table_rows(
				$wpdb->prefix . 'wprdm_forms',
				$data['data']['forms'],
				$mode
			);
		}

		// Submissions.
		if ( in_array( 'submissions', $types, true ) && isset( $data['data']['submissions'] ) && is_array( $data['data']['submissions'] ) ) {
			$results['submissions'] = $this->wprobo_documerge_import_table_rows(
				$wpdb->prefix . 'wprdm_submissions',
				$data['data']['submissions'],
				$mode
			);
		}

		// Settings.
		if ( in_array( 'settings', $types, true ) && isset( $data['data']['settings'] ) && is_array( $data['data']['settings'] ) ) {
			$settings_count = 0;
			if ( 'replace' === $mode ) {
				// Delete existing settings.
				$import_like = $wpdb->esc_like( 'wprobo_documerge_' ) . '%';
				$existing = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$import_like
					)
				);
				foreach ( $existing as $opt ) {
					delete_option( $opt );
				}
			}
			foreach ( $data['data']['settings'] as $option_name => $option_value ) {
				$option_name = sanitize_key( $option_name );
				// Only import our own options.
				if ( strpos( $option_name, 'wprobo_documerge_' ) !== 0 ) {
					continue;
				}
				// Sanitize option values based on type.
				if ( is_string( $option_value ) ) {
					$option_value = sanitize_text_field( $option_value );
				} elseif ( is_array( $option_value ) ) {
					$option_value = array_map( 'sanitize_text_field', $option_value );
				}
				update_option( $option_name, $option_value );
				++$settings_count;
			}
			$results['settings'] = $settings_count;
		}

		// Bust caches.
		delete_transient( 'wprobo_documerge_templates_list' );
		delete_transient( 'wprobo_documerge_templates_count' );
		delete_transient( 'wprobo_documerge_forms_count' );

		wp_send_json_success(
			array(
				'message' => __( 'Import completed successfully.', 'wprobo-documerge-lite' ),
				'results' => $results,
			)
		);
	}

	/**
	 * Import rows into a database table.
	 *
	 * Handles merge (insert, skip duplicates) and replace (truncate then insert) modes.
	 *
	 * @since  1.6.0
	 * @param  string $table Table name with prefix.
	 * @param  array  $rows  Array of associative row arrays.
	 * @param  string $mode  'merge' or 'replace'.
	 * @return int    Number of rows imported.
	 */
	private function wprobo_documerge_import_table_rows( $table, $rows, $mode ) {
		global $wpdb;

		if ( empty( $rows ) ) {
			return 0;
		}

		// Whitelist: only allow imports into our own tables.
		$allowed_tables = array(
			$wpdb->prefix . 'wprdm_templates',
			$wpdb->prefix . 'wprdm_forms',
			$wpdb->prefix . 'wprdm_submissions',
		);
		if ( ! in_array( $table, $allowed_tables, true ) ) {
			return 0;
		}

		// Discover the table's actual columns so we can drop anything unknown
		// from the payload. Cached across calls in one request.
		static $column_cache = array();
		if ( ! isset( $column_cache[ $table ] ) ) {
			$column_cache[ $table ] = $wpdb->get_col(
				$wpdb->prepare( 'SHOW COLUMNS FROM %i', $table ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				0
			);
		}
		$known_columns = $column_cache[ $table ];
		if ( empty( $known_columns ) ) {
			return 0;
		}

		// Replace mode: truncate first. Merge mode: skip duplicates by id.
		if ( 'replace' === $mode ) {
			$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		$imported = 0;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$filtered = array();
			foreach ( $row as $col => $val ) {
				$col = sanitize_key( $col );
				if ( ! in_array( $col, $known_columns, true ) ) {
					continue;
				}
				$filtered[ $col ] = $this->wprobo_documerge_sanitize_import_value( $col, $val );
			}

			if ( empty( $filtered ) ) {
				continue;
			}

			// In merge mode, skip if a row with this ID already exists.
			if ( 'merge' === $mode && isset( $filtered['id'] ) ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i WHERE id = %d', // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$table,
						absint( $filtered['id'] )
					)
				);
				if ( $exists ) {
					continue;
				}
			}

			$result = $wpdb->insert( $table, $filtered ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( false !== $result ) {
				++$imported;
			}
		}

		return $imported;
	}

	/**
	 * Sanitize a single value from an import payload based on column name.
	 *
	 * Columns ending in _id, numeric counters, or the primary id are cast to
	 * integers. Columns holding serialized JSON (fields, settings, form_data,
	 * merge_tags, error_log) are preserved with textarea sanitization that
	 * keeps newlines but strips HTML. Everything else is treated as a single-
	 * line text field.
	 *
	 * @since  1.0.0
	 * @param  string $col   Column name (already sanitize_key()d).
	 * @param  mixed  $value Raw value from the decoded JSON payload.
	 * @return string|int    Safe value ready for $wpdb->insert().
	 */
	private function wprobo_documerge_sanitize_import_value( $col, $value ) {
		// Integer columns.
		$int_cols = array(
			'id',
			'form_id',
			'template_id',
			'retry_count',
			'payment_enabled',
			'multistep_enabled',
		);
		if ( in_array( $col, $int_cols, true ) ) {
			return absint( $value );
		}

		// Float columns (monetary amounts).
		if ( 'payment_amount' === $col ) {
			return (float) $value;
		}

		// Long-text JSON / structured columns — preserve newlines, strip HTML.
		$long_text_cols = array(
			'fields',
			'settings',
			'form_data',
			'merge_tags',
			'error_log',
			'description',
			'success_message',
		);
		if ( in_array( $col, $long_text_cols, true ) ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode( $value );
			}
			return sanitize_textarea_field( (string) $value );
		}

		// Email column.
		if ( 'submitter_email' === $col ) {
			return sanitize_email( (string) $value );
		}

		// URL column.
		if ( 'redirect_url' === $col ) {
			return esc_url_raw( (string) $value );
		}

		// IP address.
		if ( 'ip_address' === $col ) {
			$ip = (string) $value;
			return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
		}

		// Default: single-line text field.
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		return sanitize_text_field( (string) $value );
	}
}
