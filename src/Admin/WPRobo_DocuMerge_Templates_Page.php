<?php
/**
 * Templates admin page controller.
 *
 * Handles the Templates management page and all template-related
 * AJAX operations (upload, save, delete, get).
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

use WPRobo\DocuMerge\Template\WPRobo_DocuMerge_Template_Manager;
use WPRobo\DocuMerge\Template\WPRobo_DocuMerge_Template_Scanner;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Templates_Page
 *
 * Renders the Templates admin page listing all document templates
 * and processes AJAX requests for uploading, saving, deleting,
 * and retrieving individual templates.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Templates_Page {

	/**
	 * Register WordPress hooks for template AJAX handlers.
	 *
	 * Registers admin-only AJAX actions for uploading, saving,
	 * deleting, and retrieving templates.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'wp_ajax_wprobo_documerge_upload_template', array( $this, 'wprobo_documerge_ajax_upload_template' ) );
		add_action( 'wp_ajax_wprobo_documerge_save_template', array( $this, 'wprobo_documerge_ajax_save_template' ) );
		add_action( 'wp_ajax_wprobo_documerge_delete_template', array( $this, 'wprobo_documerge_ajax_delete_template' ) );
		add_action( 'wp_ajax_wprobo_documerge_get_template', array( $this, 'wprobo_documerge_ajax_get_template' ) );
	}

	/**
	 * Render the Templates admin page.
	 *
	 * Checks user capabilities, retrieves all templates with their
	 * associated form counts and merge tag counts, then includes
	 * the template manager view file.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		$template_manager = new WPRobo_DocuMerge_Template_Manager();
		$raw_templates    = $template_manager->wprobo_documerge_get_all_templates();
		$templates        = array();

		if ( is_array( $raw_templates ) ) {
			global $wpdb;
			$forms_table = $wpdb->prefix . 'wprdm_forms';

			foreach ( $raw_templates as $template ) {
				$template->form_count = (int) $template_manager->wprobo_documerge_get_forms_using_template( $template->id );

				$decoded_tags         = json_decode( $template->merge_tags, true );
				$template->tags_array = is_array( $decoded_tags ) ? $decoded_tags : array();
				$template->tag_count  = count( $template->tags_array );

				// Get form names using this template.
				$template->linked_forms = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, title FROM {$forms_table} WHERE template_id = %d ORDER BY title ASC",
						$template->id
					)
				);
				if ( ! is_array( $template->linked_forms ) ) {
					$template->linked_forms = array();
				}

				$templates[] = $template;
			}
		}

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/template-manager/main.php';
	}

	/**
	 * AJAX handler — upload a template file.
	 *
	 * Validates the uploaded file (extension, MIME type, size),
	 * moves it to the DocuMerge templates directory, scans it
	 * for merge tags, and returns the file path, name, and tags.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_upload_template() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Validate file exists.
		if ( empty( $_FILES['template_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Sanitize the client-reported fields before any further use.
		// tmp_name is a PHP-generated temp filesystem path (not user-controlled
		// text); is_uploaded_file() below is the authoritative validator for it.
		$file = array(
			'name'     => isset( $_FILES['template_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['template_file']['name'] ) ) : '',
			'type'     => isset( $_FILES['template_file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['template_file']['type'] ) ) : '',
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server-generated upload path; validated below via is_uploaded_file().
			'tmp_name' => isset( $_FILES['template_file']['tmp_name'] ) ? $_FILES['template_file']['tmp_name'] : '',
			'error'    => isset( $_FILES['template_file']['error'] ) ? (int) $_FILES['template_file']['error'] : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $_FILES['template_file']['size'] ) ? (int) $_FILES['template_file']['size'] : 0,
		);

		if ( UPLOAD_ERR_OK !== $file['error'] || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'File upload failed.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Validate name + MIME against WordPress's allowed-filetype list.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) );
		if ( empty( $check['ext'] ) || 'docx' !== $check['ext'] || empty( $check['type'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Only .docx files are allowed.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Check file size (max 10 MB).
		$max_size = 10 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => __( 'File size exceeds the 10 MB limit.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Handle upload via WordPress.
		$overrides = array(
			'test_form'                => false,
			'unique_filename_callback' => array( $this, 'wprobo_documerge_unique_filename' ),
		);

		$uploaded = wp_handle_upload( $file, $overrides );

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error( array( 'message' => $uploaded['error'] ) );
			return;
		}

		// Move file to the DocuMerge templates directory.
		$templates_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir() . 'templates/';
		$original_file = $uploaded['file'];
		$file_name     = basename( $original_file );
		$destination   = $templates_dir . $file_name;

		// Create directory if it does not exist.
		if ( ! is_dir( $templates_dir ) ) {
			wp_mkdir_p( $templates_dir );
		}

		// Use WP Filesystem to move the file.
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem->move( $original_file, $destination, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to move uploaded file to templates directory.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Scan the template for merge tags.
		$scanner    = new WPRobo_DocuMerge_Template_Scanner();
		$merge_tags = $scanner->wprobo_documerge_scan_docx( $destination );

		wp_send_json_success(
			array(
				'file_path'  => $destination,
				'file_name'  => $file_name,
				'merge_tags' => is_array( $merge_tags ) ? $merge_tags : array(),
			)
		);
	}

	/**
	 * Generate a unique filename for uploaded templates.
	 *
	 * Appends a Unix timestamp to the filename to avoid collisions.
	 *
	 * @since  1.0.0
	 * @param  string $dir  Upload directory path.
	 * @param  string $name Original filename.
	 * @param  string $ext  File extension (with leading dot).
	 * @return string Unique filename.
	 */
	public function wprobo_documerge_unique_filename( $dir, $name, $ext ) {
		$name_without_ext = basename( $name, $ext );
		return sanitize_file_name( $name_without_ext . '-' . time() . $ext );
	}

	/**
	 * AJAX handler — save a template.
	 *
	 * Creates a new template or updates an existing one by sanitizing
	 * all POST inputs and delegating to the Template_Manager.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_save_template() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Sanitize template ID (0 for new templates).
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		// Template name (required).
		$name = isset( $_POST['name'] )
			? sanitize_text_field( wp_unslash( $_POST['name'] ) )
			: '';

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Template name is required.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Description.
		$description = isset( $_POST['description'] )
			? sanitize_textarea_field( wp_unslash( $_POST['description'] ) )
			: '';

		// File path.
		$file_path = isset( $_POST['file_path'] )
			? sanitize_text_field( wp_unslash( $_POST['file_path'] ) )
			: '';

		// File name.
		$file_name = isset( $_POST['file_name'] )
			? sanitize_file_name( wp_unslash( $_POST['file_name'] ) )
			: '';

		// Output format.
		$output_format   = isset( $_POST['output_format'] )
			? sanitize_key( wp_unslash( $_POST['output_format'] ) )
			: 'pdf';
		$allowed_formats = array( 'pdf', 'docx', 'both' );
		if ( ! in_array( $output_format, $allowed_formats, true ) ) {
			$output_format = 'pdf';
		}

		// Merge tags — sanitize the raw JSON string before decoding. Every
		// tag is then sanitize_text_field()'d below before re-encoding.
		$merge_tags_raw = isset( $_POST['merge_tags'] )
			? sanitize_textarea_field( wp_unslash( $_POST['merge_tags'] ) )
			: '[]';

		$merge_tags_decoded = json_decode( $merge_tags_raw, true );

		if ( ! is_array( $merge_tags_decoded ) ) {
			$merge_tags_decoded = array();
		}

		$merge_tags_sanitized = array_map( 'sanitize_text_field', $merge_tags_decoded );
		$merge_tags           = wp_json_encode( array_values( $merge_tags_sanitized ) );

		$data = array(
			'id'            => $id,
			'name'          => $name,
			'description'   => $description,
			'file_path'     => $file_path,
			'file_name'     => $file_name,
			'output_format' => $output_format,
			'merge_tags'    => $merge_tags,
		);

		$template_manager = new WPRobo_DocuMerge_Template_Manager();
		$template_id      = $template_manager->wprobo_documerge_save_template( $data );

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save template.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		wp_send_json_success( array( 'template_id' => $template_id ) );
	}

	/**
	 * AJAX handler — delete a template.
	 *
	 * Verifies the template exists and is not in use by any forms
	 * before delegating deletion to the Template_Manager.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_delete_template() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$template_manager = new WPRobo_DocuMerge_Template_Manager();

		// Verify template exists.
		$template = $template_manager->wprobo_documerge_get_template( $id );
		if ( ! $template ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Check if any forms are using this template.
		$forms_using = (int) $template_manager->wprobo_documerge_get_forms_using_template( $id );
		if ( $forms_using > 0 ) {
			$form_count = $forms_using;
			wp_send_json_error(
				array(
					'message' => sprintf(
					/* translators: %d: number of forms using the template */
						__( 'Cannot delete — template is used by %d form(s).', 'wprobo-documerge-lite' ),
						$form_count
					),
				)
			);
			return;
		}

		$deleted = $template_manager->wprobo_documerge_delete_template( $id );

		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete template.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'Template deleted successfully.', 'wprobo-documerge-lite' ) ) );
	}

	/**
	 * AJAX handler — get a single template.
	 *
	 * Retrieves template data by ID and returns it as JSON
	 * with merge tags decoded into an array.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_get_template() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$template_manager = new WPRobo_DocuMerge_Template_Manager();
		$template         = $template_manager->wprobo_documerge_get_template( $id );

		if ( ! $template ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Decode merge_tags JSON string into an array before sending.
		$template->merge_tags = json_decode( $template->merge_tags, true );

		if ( ! is_array( $template->merge_tags ) ) {
			$template->merge_tags = array();
		}

		wp_send_json_success( $template );
	}
}
