<?php
/**
 * PDF converter.
 *
 * Converts a generated DOCX document to PDF format using PHPWord
 * for HTML intermediary conversion and mPDF for final PDF output.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Document
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Document;

use PhpOffice\PhpWord\IOFactory;
use Mpdf\Mpdf;
use WPRobo\DocuMerge\Helpers\WPRobo_DocuMerge_Logger;
use WP_Error;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, Squiz.PHP.DiscouragedFunctions.Discouraged

/**
 * Class WPRobo_DocuMerge_Pdf_Converter
 *
 * Handles DOCX-to-PDF conversion through a three-step pipeline:
 * load the DOCX via PHPWord, export to an HTML intermediary, then
 * render the HTML to PDF with mPDF. Temporary resources and PHP
 * runtime limits are managed and restored automatically.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Pdf_Converter {

	/**
	 * Convert a DOCX file to PDF.
	 *
	 * Loads the DOCX document via PHPWord IOFactory, writes it to an
	 * HTML intermediary, then renders the HTML to PDF using mPDF.
	 * Memory and execution time limits are temporarily increased and
	 * always restored via a finally block.
	 *
	 * @since  1.0.0
	 * @param  string $docx_path Full path to the DOCX file to convert.
	 * @return string|WP_Error The full path to the generated PDF file on success,
	 *                         or WP_Error on failure.
	 */
	public function wprobo_documerge_convert( $docx_path ) {

		// Validate DOCX file exists.
		if ( ! file_exists( $docx_path ) ) {
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'error',
				sprintf(
					/* translators: %s: DOCX file path. */
					__( 'DOCX file not found for PDF conversion: %s', 'wprobo-documerge-lite' ),
					$docx_path
				)
			);

			return new WP_Error(
				'wprobo_documerge_pdf_failed',
				__( 'DOCX file does not exist.', 'wprobo-documerge-lite' )
			);
		}

		// Raise limits for this single PDF conversion only.
		// wp_raise_memory_limit() respects WP_MAX_MEMORY_LIMIT and the
		// {$context}_memory_limit filter so host/user constraints win.
		// Time limit is restored in the finally block below.
		wp_raise_memory_limit( 'wprobo_documerge_pdf' );

		$original_time_limit = (int) ini_get( 'max_execution_time' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 );
		}

		$html_temp_path = '';

		try {
			// Step 1: Load the DOCX via PHPWord.
			$php_word = IOFactory::load( $docx_path );

			// Step 2: Write to HTML intermediary.
			$html_writer    = IOFactory::createWriter( $php_word, 'HTML' );
			$html_temp_path = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_temp_dir() . basename( str_replace( '.docx', '.html', $docx_path ) );
			$html_writer->save( $html_temp_path );

			// Step 3: Create mPDF instance.
			$mpdf_args = array(
				'mode'        => 'utf-8',
				'format'      => 'A4',
				'orientation' => 'P',
				'tempDir'     => \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_temp_dir(),
			);

			/**
			 * Filters the mPDF constructor arguments before instantiation.
			 *
			 * @since 1.1.0
			 *
			 * @param array $mpdf_args     The mPDF configuration arguments.
			 * @param int   $submission_id The submission ID (0 if unknown).
			 */
			$mpdf_args = apply_filters( 'wprobo_documerge_pdf_converter_args', $mpdf_args, 0 );

			$mpdf = new Mpdf( $mpdf_args );

			// Step 4: Read HTML content and pass to mPDF.
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$html_content = $wp_filesystem->get_contents( $html_temp_path );

			if ( false === $html_content ) {
				return new WP_Error(
					'wprobo_documerge_pdf_failed',
					__( 'Failed to read HTML intermediary file.', 'wprobo-documerge-lite' )
				);
			}

			// ── Clean PHPWord HTML for mPDF compatibility ──────────
			// PHPWord's HTML writer generates CSS class names with spaces
			// (e.g. ".Normal Table", ".Heading 1 Char") that mPDF cannot
			// parse correctly — this causes hundreds of blank pages.
			// Solution: strip the PHPWord style block entirely; we feed
			// mPDF its own minimal stylesheet below via HEADER_CSS.

			$html_content = preg_replace( '#<style[^>]*>.*?</' . 'style>#s', '', $html_content );

			// Remove page-break CSS from inline styles.
			$html_content = preg_replace( '/page-break-(?:after|before)\s*:\s*[^;"\']+;?/i', '', $html_content );

			// Remove PHPWord page style references from divs.
			$html_content = preg_replace( '/\s*style\s*=\s*\'page:\s*page\d+\'/i', '', $html_content );

			// Constrain images that have explicit pixel dimensions.
			$html_content = preg_replace(
				'/(<img[^>]*?)style\s*=\s*"[^"]*width:\s*(\d+)px[^"]*height:\s*(\d+)px[^"]*"/i',
				'$1style="max-width:100%;width:$2px;height:auto;"',
				$html_content
			);

			/**
			 * Filters the HTML content before it is passed to mPDF for rendering.
			 *
			 * Allows modification of the intermediary HTML generated from the
			 * DOCX template, such as injecting custom CSS, adding headers/footers,
			 * or altering content before PDF conversion.
			 *
			 * @since 1.2.0
			 *
			 * @param string $html_content  The HTML content to be converted to PDF.
			 * @param string $docx_path     The source DOCX file path.
			 */
			$html_content = apply_filters( 'wprobo_documerge_pdf_html', $html_content, $docx_path );

			// Feed mPDF its base CSS via the HEADER_CSS parser, then the HTML body.
			$mpdf_css = 'body{font-family:Arial,sans-serif;font-size:12pt;color:#000;margin:0;padding:0}'
				. 'p{margin:0 0 6pt 0;line-height:1.4}'
				. 'h1{font-size:20pt;margin:12pt 0 6pt 0}'
				. 'h2{font-size:16pt;margin:10pt 0 5pt 0}'
				. 'h3{font-size:14pt;margin:8pt 0 4pt 0}'
				. 'table{border-collapse:collapse;width:100%}'
				. 'td,th{border:1px solid #999;padding:4pt 6pt}'
				. 'img{max-width:100%;height:auto}';
			$mpdf->WriteHTML( $mpdf_css, \Mpdf\HTMLParserMode::HEADER_CSS );
			$mpdf->WriteHTML( $html_content, \Mpdf\HTMLParserMode::HTML_BODY );

			// Step 5: Output PDF.
			$pdf_path = str_replace( '.docx', '.pdf', $docx_path );
			$mpdf->Output( $pdf_path, 'F' );

			// Step 6: Clean up HTML temp file.
			if ( $wp_filesystem->exists( $html_temp_path ) ) {
				$wp_filesystem->delete( $html_temp_path );
			}

			// Log success.
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'info',
				sprintf(
					/* translators: %s: PDF file path. */
					__( 'PDF generated successfully: %s', 'wprobo-documerge-lite' ),
					$pdf_path
				)
			);

			return $pdf_path;

		} catch ( \Exception $e ) {

			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'error',
				sprintf(
					/* translators: 1: DOCX file path, 2: error message. */
					__( 'PDF conversion failed for %1$s: %2$s', 'wprobo-documerge-lite' ),
					$docx_path,
					$e->getMessage()
				)
			);

			// Clean up HTML temp file on failure.
			if ( ! empty( $html_temp_path ) ) {
				global $wp_filesystem;

				if ( ! empty( $wp_filesystem ) && $wp_filesystem->exists( $html_temp_path ) ) {
					$wp_filesystem->delete( $html_temp_path );
				}
			}

			return new WP_Error(
				'wprobo_documerge_pdf_failed',
				$e->getMessage()
			);

		} finally {

			// Restore the original execution time limit. Memory limit is
			// raised via wp_raise_memory_limit() which has no reciprocal
			// "lower" API; the higher limit persists until request end.
			if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( $original_time_limit );
			}
		}
	}
}
