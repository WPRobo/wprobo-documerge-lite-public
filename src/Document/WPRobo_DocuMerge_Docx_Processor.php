<?php
/**
 * DOCX document processor.
 *
 * Merges form submission data into a DOCX template using PHPWord
 * TemplateProcessor and outputs the final document to disk.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Document
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Document;

use PhpOffice\PhpWord\TemplateProcessor;
use WPRobo\DocuMerge\Template\WPRobo_DocuMerge_Merge_Engine;
use WPRobo\DocuMerge\Helpers\WPRobo_DocuMerge_Logger;
use WP_Error;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Docx_Processor
 *
 * Handles the complete DOCX merge workflow: validates the template,
 * prepares the output directory, processes conditionals and merge tags,
 * saves the resulting document, and cleans up temporary files.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Docx_Processor {

	/**
	 * Process a DOCX template with the supplied merge data.
	 *
	 * Validates the template file, creates a date-based output directory
	 * with .htaccess protection, runs conditional logic and tag
	 * replacement via the Merge Engine, and writes the final DOCX.
	 *
	 * @since  1.0.0
	 * @param  string $template_path Full path to the DOCX template file.
	 * @param  array  $merge_data    Associative array of merge tag => value pairs.
	 * @param  int    $submission_id The submission ID for output file naming.
	 * @return string|WP_Error The full path to the generated DOCX file on success,
	 *                         or WP_Error on failure.
	 */
	public function wprobo_documerge_process( $template_path, $merge_data, $submission_id = 0 ) {

		// Validate template file exists.
		if ( ! file_exists( $template_path ) ) {
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'error',
				sprintf(
					/* translators: %s: template file path. */
					__( 'Template file not found: %s', 'wprobo-documerge-lite' ),
					$template_path
				)
			);

			return new WP_Error(
				'wprobo_documerge_docx_failed',
				__( 'Template file does not exist.', 'wprobo-documerge-lite' )
			);
		}

		// Build date-based output directory.
		$output_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir()
			. gmdate( 'Y' ) . '/'
			. gmdate( 'm' ) . '/'
			. $submission_id . '/';

		// Create output directory if it does not exist.
		if ( ! is_dir( $output_dir ) ) {
			wp_mkdir_p( $output_dir );
		}

		// Add .htaccess protection if not already present.
		$htaccess_path = $output_dir . '.htaccess';
		if ( ! file_exists( $htaccess_path ) ) {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$wp_filesystem->put_contents(
				$htaccess_path,
				"Options -Indexes\ndeny from all\n",
				FS_CHMOD_FILE
			);
		}

		try {
			/**
			 * Filters additional arguments passed to the DOCX processor.
			 *
			 * Allows developers to pass extra configuration to the DOCX
			 * processing pipeline before the TemplateProcessor is created.
			 *
			 * @since 1.1.0
			 *
			 * @param array  $args          Extra arguments (default empty array).
			 * @param string $template_path Full path to the DOCX template file.
			 * @param int    $submission_id The submission ID.
			 */
			$args = apply_filters( 'wprobo_documerge_docx_processor_args', array(), $template_path, $submission_id );

			// Instantiate PHPWord TemplateProcessor.
			$processor = new TemplateProcessor( $template_path );

			// Instantiate the merge engine and run the full pipeline.
			// Pipeline order: pipes -> conditionals -> repeaters -> images -> tags.
			$merge_engine = new WPRobo_DocuMerge_Merge_Engine();
			$merge_engine->wprobo_documerge_run_pipeline( $processor, $merge_data );

			// Generate output filename from the template name.
			$template_name = pathinfo( $template_path, PATHINFO_FILENAME );
			$output_name   = sanitize_file_name( $template_name ) . '-' . $submission_id . '.docx';
			$output_path   = $output_dir . $output_name;

			/**
			 * Filters the output path after merge is complete, before saveAs().
			 *
			 * @since 1.1.0
			 *
			 * @param string $output_path   The full path where the DOCX will be saved.
			 * @param int    $submission_id The submission ID.
			 */
			$output_path = apply_filters( 'wprobo_documerge_after_merge', $output_path, $submission_id );
			$output_path = sanitize_text_field( $output_path );

			// Save the processed document.
			$processor->saveAs( $output_path );

			// Clean up temporary files tracked by the merge engine.
			$this->wprobo_documerge_cleanup_temp_files( $merge_engine->wprobo_documerge_get_temp_files() );

			// Clean up any legacy temporary signature image files.
			$this->wprobo_documerge_cleanup_temp_signatures( $output_dir );

			// Log success.
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'info',
				sprintf(
					/* translators: 1: submission ID, 2: output file path. */
					__( 'DOCX generated successfully for submission #%1$d: %2$s', 'wprobo-documerge-lite' ),
					$submission_id,
					$output_path
				)
			);

			return $output_path;

		} catch ( \Exception $e ) {

			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'error',
				sprintf(
					/* translators: 1: submission ID, 2: error message. */
					__( 'DOCX generation failed for submission #%1$d: %2$s', 'wprobo-documerge-lite' ),
					$submission_id,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'wprobo_documerge_docx_failed',
				$e->getMessage()
			);
		}
	}

	/**
	 * Remove temporary signature image files from the output directory.
	 *
	 * Signature images are generated during merge tag replacement and
	 * embedded into the DOCX. The temporary source files are no longer
	 * needed after the document is saved.
	 *
	 * @since  1.0.0
	 * @param  string $directory The directory to scan for temp signature files.
	 * @return void
	 */
	private function wprobo_documerge_cleanup_temp_signatures( $directory ) {
		$temp_files = glob( $directory . 'sig_temp_*' );

		if ( ! is_array( $temp_files ) ) {
			return;
		}

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( $temp_files as $temp_file ) {
			$wp_filesystem->delete( $temp_file );
		}
	}

	/**
	 * Remove temporary files created by the merge engine.
	 *
	 * Accepts an array of absolute file paths and deletes each one
	 * using the WP Filesystem API. Called after saveAs() to clean
	 * up decoded image files and other intermediary assets.
	 *
	 * @since  1.3.0
	 * @param  array $file_paths List of absolute file paths to delete.
	 * @return void
	 */
	private function wprobo_documerge_cleanup_temp_files( array $file_paths ) {

		if ( empty( $file_paths ) ) {
			return;
		}

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( $file_paths as $file_path ) {
			if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
				$wp_filesystem->delete( $file_path );
			}
		}
	}
}
