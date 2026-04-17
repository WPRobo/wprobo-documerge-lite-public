<?php
/**
 * Document generation orchestrator.
 *
 * Coordinates the full document-generation pipeline: fetches submission
 * and template data, merges tags via DocxProcessor, optionally converts
 * to PDF, and updates the submission record.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Document
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Document;

use WPRobo\DocuMerge\Helpers\WPRobo_DocuMerge_Logger;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Document_Generator
 *
 * Main orchestrator for the document generation pipeline. Pulls
 * submission, form, and template records from the database, delegates
 * DOCX processing and optional PDF conversion to dedicated classes,
 * and persists the resulting file paths back to the submission row.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Document_Generator {

	/**
	 * DOCX processor instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Docx_Processor
	 */
	private $wprobo_documerge_docx_processor;

	/**
	 * PDF converter instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Pdf_Converter
	 */
	private $wprobo_documerge_pdf_converter;

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Logger
	 */
	private $wprobo_documerge_logger;

	/**
	 * Constructor.
	 *
	 * Accepts optional dependency instances for testability. When omitted
	 * the constructor creates default instances.
	 *
	 * @since 1.0.0
	 * @param WPRobo_DocuMerge_Docx_Processor|null $docx_processor Optional DOCX processor.
	 * @param WPRobo_DocuMerge_Pdf_Converter|null   $pdf_converter  Optional PDF converter.
	 * @param WPRobo_DocuMerge_Logger|null           $logger         Optional logger.
	 */
	public function __construct( $docx_processor = null, $pdf_converter = null, $logger = null ) {
		$this->wprobo_documerge_docx_processor = $docx_processor ? $docx_processor : new WPRobo_DocuMerge_Docx_Processor();
		$this->wprobo_documerge_pdf_converter  = $pdf_converter ? $pdf_converter : new WPRobo_DocuMerge_Pdf_Converter();
		$this->wprobo_documerge_logger         = $logger ? $logger : new WPRobo_DocuMerge_Logger();
	}

	/**
	 * Generate a document for a given submission.
	 *
	 * Orchestrates the full pipeline:
	 * 1. Fetch submission, form, and template records.
	 * 2. Validate the template file exists on disk.
	 * 3. Decode form_data JSON and extract merge fields.
	 * 4. Process the DOCX template with merge data.
	 * 5. Optionally convert to PDF based on the form output format.
	 * 6. Update submission paths and status.
	 * 7. Fire the `wprobo_documerge_document_generated` action.
	 *
	 * @since  1.0.0
	 * @param  int $submission_id The submission ID to generate a document for.
	 * @return array|\WP_Error Array with 'docx' and 'pdf' paths on success, WP_Error on failure.
	 */
	public function wprobo_documerge_generate( $submission_id ) {
		global $wpdb;

		$submission_id = absint( $submission_id );

		// ── Step A: Get submission ───────────────────────────────────────
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$submissions_table} WHERE id = %d",
				$submission_id
			)
		);

		if ( ! $submission ) {
			return new \WP_Error(
				'submission_not_found',
				/* translators: %d: submission ID */
				sprintf( __( 'Submission #%d not found.', 'wprobo-documerge-lite' ), $submission_id )
			);
		}

		// ── Step C: Get form ─────────────────────────────────────────────
		$forms_table = $wpdb->prefix . 'wprdm_forms';

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$forms_table} WHERE id = %d",
				absint( $submission->form_id )
			)
		);

		if ( ! $form ) {
			return new \WP_Error(
				'form_not_found',
				/* translators: %d: form ID */
				sprintf( __( 'Form #%d not found.', 'wprobo-documerge-lite' ), absint( $submission->form_id ) )
			);
		}

		// ── Step D: Get template ─────────────────────────────────────────
		$templates_table = $wpdb->prefix . 'wprdm_templates';
		$template_id     = ! empty( $submission->template_id ) ? absint( $submission->template_id ) : absint( $form->template_id );

		$template = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$templates_table} WHERE id = %d",
				$template_id
			)
		);

		if ( ! $template ) {
			return new \WP_Error(
				'template_not_found',
				/* translators: %d: template ID */
				sprintf( __( 'Template #%d not found.', 'wprobo-documerge-lite' ), $template_id )
			);
		}

		/**
		 * Fires at the start of document generation, after loading submission/form/template.
		 *
		 * @since 1.1.0
		 *
		 * @param int $submission_id The submission ID.
		 * @param int $form_id       The form ID.
		 * @param int $template_id   The template ID.
		 */
		do_action( 'wprobo_documerge_before_generate', $submission_id, (int) $submission->form_id, (int) $submission->template_id );

		// ── Step E: Validate template file exists ────────────────────────
		if ( empty( $template->file_path ) || ! file_exists( $template->file_path ) ) {
			$error_msg = sprintf(
				/* translators: %s: file path */
				__( 'Template file does not exist: %s', 'wprobo-documerge-lite' ),
				$template->file_path
			);
			$this->wprobo_documerge_update_submission_status( $submission_id, 'error', $error_msg );
			$this->wprobo_documerge_notify_admin_error( $submission_id, $error_msg );

			return new \WP_Error( 'template_file_missing', $error_msg );
		}

		// ── Step F: Decode form_data JSON ────────────────────────────────
		$form_data = json_decode( $submission->form_data, true );

		if ( ! is_array( $form_data ) ) {
			$error_msg = __( 'Invalid form data JSON.', 'wprobo-documerge-lite' );
			$this->wprobo_documerge_update_submission_status( $submission_id, 'error', $error_msg );

			return new \WP_Error( 'invalid_form_data', $error_msg );
		}

		// ── Step G: Build merge data ─────────────────────────────────────
		$merge_data = isset( $form_data['fields'] ) && is_array( $form_data['fields'] )
			? $form_data['fields']
			: array();

		/**
		 * Filters the merge data before document processing.
		 *
		 * Allows modification of the tag => value pairs that will be merged
		 * into the document template. Non-scalar values are removed after filtering.
		 *
		 * @since 1.0.0
		 *
		 * @param array $merge_data    Tag => value pairs.
		 * @param int   $submission_id The submission ID.
		 * @param int   $form_id       The form ID.
		 */
		$merge_data = apply_filters( 'wprobo_documerge_merge_data', $merge_data, $submission_id, (int) $submission->form_id );
		foreach ( $merge_data as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				unset( $merge_data[ $key ] );
			}
		}

		// ── Step H: Process DOCX template ────────────────────────────────
		$docx_result = $this->wprobo_documerge_docx_processor->wprobo_documerge_process(
			$template->file_path,
			$merge_data,
			$submission_id
		);

		// ── Step I: Handle DOCX processing error ─────────────────────────
		if ( is_wp_error( $docx_result ) ) {
			$error_msg = $docx_result->get_error_message();
			$this->wprobo_documerge_update_submission_status( $submission_id, 'error', $error_msg );
			$this->wprobo_documerge_notify_admin_error( $submission_id, $error_msg );
			$this->wprobo_documerge_logger->wprobo_documerge_log(
				sprintf( 'DOCX processing failed for submission #%d: %s', $submission_id, $error_msg ),
				'error'
			);

			/**
			 * Fires when document generation fails.
			 *
			 * @since 1.1.0
			 *
			 * @param int       $submission_id The submission ID.
			 * @param \WP_Error $result        The WP_Error describing the failure.
			 */
			do_action( 'wprobo_documerge_generation_failed', $submission_id, $docx_result );

			return $docx_result;
		}

		$docx_path = $docx_result;
		$pdf_path  = '';

		// ── Step J–K: PDF conversion if required ─────────────────────────
		$output_format = ! empty( $form->output_format ) ? sanitize_key( $form->output_format ) : 'pdf';

		/**
		 * Filters the document output format before conversion.
		 *
		 * Allows overriding whether the document is generated as
		 * 'pdf', 'docx', or 'both' on a per-submission basis.
		 *
		 * @since 1.2.0
		 *
		 * @param string $output_format The output format (pdf, docx, both).
		 * @param int    $submission_id The submission ID.
		 * @param int    $form_id       The form ID.
		 */
		$output_format = apply_filters( 'wprobo_documerge_output_format', $output_format, $submission_id, (int) $submission->form_id );

		if ( 'pdf' === $output_format || 'both' === $output_format ) {
			$pdf_result = $this->wprobo_documerge_pdf_converter->wprobo_documerge_convert( $docx_path );

			// ── Step L: Handle PDF conversion error ──────────────────────
			if ( is_wp_error( $pdf_result ) ) {
				$error_msg = $pdf_result->get_error_message();
				$this->wprobo_documerge_logger->wprobo_documerge_log(
					sprintf( 'PDF conversion failed for submission #%d: %s', $submission_id, $error_msg ),
					'error'
				);
				$this->wprobo_documerge_update_submission_status( $submission_id, 'error', $error_msg );
				$this->wprobo_documerge_notify_admin_error( $submission_id, $error_msg );

				/** This action is documented in src/Document/WPRobo_DocuMerge_Document_Generator.php */
				do_action( 'wprobo_documerge_generation_failed', $submission_id, $pdf_result );

				return $pdf_result;
			}

			$pdf_path = $pdf_result;
		}

		// ── Step M: Respect output format — only keep requested files ────
		//    DOCX is always generated first (PHPWord), but if output is
		//    "pdf" only, we delete the intermediate DOCX file.
		$final_docx = $docx_path;
		$final_pdf  = $pdf_path;

		if ( 'pdf' === $output_format ) {
			// PDF only — delete the intermediate DOCX.
			if ( ! empty( $docx_path ) && file_exists( $docx_path ) ) {
				@unlink( $docx_path ); // phpcs:ignore
			}
			$final_docx = '';
		} elseif ( 'docx' === $output_format ) {
			// DOCX only — no PDF was generated ($pdf_path is already empty).
			$final_pdf = '';
		}
		// 'both' — keep both files as-is.

		// ── Filter output paths before saving ────────────────────────────

		if ( ! empty( $final_docx ) ) {
			/**
			 * Filters the output path for a generated document.
			 *
			 * Allows overriding where generated documents are stored. Needed
			 * for custom storage backends (S3, Google Cloud Storage) or
			 * multisite configurations.
			 *
			 * @since 1.0.0
			 *
			 * @param string $path          Absolute file path for the generated document.
			 * @param int    $submission_id The submission ID.
			 * @param string $format        The document format ('pdf' or 'docx').
			 */
			$final_docx = apply_filters( 'wprobo_documerge_document_output_path', $final_docx, $submission_id, 'docx' );
		}

		if ( ! empty( $final_pdf ) ) {
			/** This filter is documented in src/Document/WPRobo_DocuMerge_Document_Generator.php */
			$final_pdf = apply_filters( 'wprobo_documerge_document_output_path', $final_pdf, $submission_id, 'pdf' );
		}

		// ── Update submission with file paths ────────────────────────────
		$this->wprobo_documerge_update_submission_paths( $submission_id, $final_docx, $final_pdf );

		// ── Step N: Fire action ──────────────────────────────────────────
		/**
		 * Fires after a document has been successfully generated.
		 *
		 * @since 1.0.0
		 * @param int    $submission_id The submission ID.
		 * @param string $docx_path     Absolute path to the generated DOCX file.
		 * @param string $pdf_path      Absolute path to the generated PDF file (empty if not converted).
		 */
		do_action( 'wprobo_documerge_document_generated', $submission_id, $final_docx, $final_pdf );

		// ── Step O: Return paths ─────────────────────────────────────────
		return array(
			'docx' => $final_docx,
			'pdf'  => $final_pdf,
		);
	}

	/**
	 * Update a submission's status and optional error log.
	 *
	 * @since  1.0.0
	 * @param  int    $submission_id The submission ID.
	 * @param  string $status        New status value (e.g. 'processing', 'completed', 'error').
	 * @param  string $error_msg     Optional error message to store.
	 * @return bool True on success, false on failure.
	 */
	public function wprobo_documerge_update_submission_status( $submission_id, $status, $error_msg = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_submissions';

		$data = array(
			'status'     => sanitize_text_field( $status ),
			'error_log'  => sanitize_textarea_field( $error_msg ),
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s' );

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => absint( $submission_id ) ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Update a submission's document file paths and mark as completed.
	 *
	 * @since  1.0.0
	 * @param  int    $submission_id The submission ID.
	 * @param  string $docx_path     Absolute path to the generated DOCX file.
	 * @param  string $pdf_path      Absolute path to the generated PDF file.
	 * @return bool True on success, false on failure.
	 */
	public function wprobo_documerge_update_submission_paths( $submission_id, $docx_path, $pdf_path ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_submissions';

		$data = array(
			'doc_path_docx' => sanitize_text_field( $docx_path ),
			'doc_path_pdf'  => sanitize_text_field( $pdf_path ),
			'status'        => 'completed',
			'updated_at'    => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%s' );

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => absint( $submission_id ) ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Notify the site administrator about a document generation error.
	 *
	 * Sends an email to the admin address configured in the plugin
	 * settings (falls back to the WordPress general admin email).
	 *
	 * @since  1.0.0
	 * @param  int    $submission_id  The submission ID that failed.
	 * @param  string $error_message  Human-readable error description.
	 * @return bool Whether wp_mail() reported success.
	 */
	public function wprobo_documerge_notify_admin_error( $submission_id, $error_message ) {
		$admin_email = get_option( 'wprobo_documerge_admin_email', get_option( 'admin_email' ) );
		$site_name   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] DocuMerge: Document generation failed', 'wprobo-documerge-lite' ), $site_name );

		$admin_url = admin_url( 'admin.php?page=wprobo-documerge-submissions&id=' . absint( $submission_id ) );

		$body = sprintf(
			/* translators: 1: submission ID, 2: error message, 3: admin URL */
			__(
				"A document generation error has occurred.\n\nSubmission ID: #%1\$d\nError: %2\$s\n\nView submission: %3\$s",
				'wprobo-documerge-lite'
			),
			absint( $submission_id ),
			sanitize_textarea_field( $error_message ),
			esc_url( $admin_url )
		);

		return wp_mail( sanitize_email( $admin_email ), $subject, $body );
	}
}
