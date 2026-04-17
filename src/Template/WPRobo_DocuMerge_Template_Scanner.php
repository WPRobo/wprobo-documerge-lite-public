<?php
/**
 * Template merge-tag scanner.
 *
 * Extracts and classifies merge tags from DOCX template files
 * using the PhpWord TemplateProcessor.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Template
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Template;

use PhpOffice\PhpWord\TemplateProcessor;
use WP_Error;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Template_Scanner
 *
 * Scans DOCX template files to extract merge-tag variable names and
 * classifies them into system, signature, and user-defined categories.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Template_Scanner {

	/**
	 * Scan a DOCX file and extract merge tags.
	 *
	 * Uses PhpWord's TemplateProcessor to read ${variable} placeholders
	 * from the document. Each tag is cleaned (trimmed, lowercased,
	 * spaces replaced with underscores), de-duplicated, and sorted
	 * alphabetically.
	 *
	 * @since  1.0.0
	 * @param  string $file_path Absolute path to the DOCX file.
	 * @return array|WP_Error Array of clean tag names on success, WP_Error on failure.
	 */
	public function wprobo_documerge_scan_docx( $file_path ) {

		/**
		 * Fires before a DOCX template is scanned for merge tags.
		 *
		 * Allows developers to log, validate, or pre-process the DOCX file
		 * before tag scanning begins. Useful for checking file integrity
		 * or custom preprocessing.
		 *
		 * @since 1.0.0
		 *
		 * @param string $file_path Absolute path to the DOCX file.
		 */
		do_action( 'wprobo_documerge_before_template_scan', $file_path );

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return new WP_Error(
				'wprobo_documerge_file_not_found',
				__( 'The template file does not exist.', 'wprobo-documerge-lite' ),
				array( 'file_path' => $file_path )
			);
		}

		try {
			$processor = new TemplateProcessor( $file_path );
			$variables = $processor->getVariables();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'wprobo_documerge_scan_failed',
				__( 'Failed to scan the DOCX template.', 'wprobo-documerge-lite' ),
				array(
					'file_path' => $file_path,
					'error'     => $e->getMessage(),
				)
			);
		}

		if ( ! is_array( $variables ) || empty( $variables ) ) {
			return array();
		}

		// Also scan the raw XML for non-standard tags that PHPWord
		// does not extract via getVariables() (conditionals, repeaters).
		$extra_tags = $this->wprobo_documerge_scan_extended_tags( $file_path );

		// Clean each tag: trim, lowercase, spaces to underscores.
		// Also normalise image dimension suffixes (photo:200x150 -> photo).
		$clean_tags = array_map(
			function ( $tag ) {
				$tag = trim( $tag );
				$tag = strtolower( $tag );
				$tag = str_replace( ' ', '_', $tag );

				// Strip pipe modifiers for the tag list (name|upper -> name).
				if ( false !== strpos( $tag, '|' ) ) {
						$tag = explode( '|', $tag, 2 )[0];
				}

				// Strip image dimension suffix (photo:200x150 -> photo).
				if ( preg_match( '/^([a-z0-9_]+):\d+x\d+$/', $tag, $m ) ) {
					$tag = $m[1];
				}

				return $tag;
			},
			$variables
		);

		// Merge in extended tags found by raw XML scan.
		$clean_tags = array_merge( $clean_tags, $extra_tags );

		// Remove duplicates and sort alphabetically.
		$clean_tags = array_unique( $clean_tags );
		sort( $clean_tags );

		/**
		 * Filters the extracted merge tags after scanning a DOCX template.
		 *
		 * @since 1.1.0
		 *
		 * @param array  $clean_tags The cleaned, de-duplicated, sorted tag names.
		 * @param int    $template_id The template ID (0 when scanning by file path only).
		 * @param string $file_path   The path to the scanned DOCX file.
		 */
		$clean_tags = apply_filters( 'wprobo_documerge_merge_tags', $clean_tags, 0, $file_path );

		/**
		 * Fires after a DOCX template has been scanned for merge tags.
		 *
		 * Allows third-party code to react to a template scan, for
		 * example to log tag counts or trigger tag-mapping UI updates.
		 *
		 * @since 1.2.0
		 *
		 * @param array  $clean_tags The cleaned, de-duplicated, sorted tag names.
		 * @param string $file_path  The absolute path to the scanned DOCX file.
		 */
		do_action( 'wprobo_documerge_template_scanned', $clean_tags, $file_path );

		// Re-index after unique/sort.
		return array_values( $clean_tags );
	}

	/**
	 * Get the list of system-provided merge tags.
	 *
	 * System tags are automatically filled by the plugin at merge time
	 * and do not require manual form-field mapping.
	 *
	 * @since  1.0.0
	 * @return array Sorted array of system tag names.
	 */
	public function wprobo_documerge_get_system_tags() {
		return array(
			'current_date',
			'current_time',
			'site_name',
			'site_url',
			'submission_id',
		);
	}

	/**
	 * Classify tags into system, signature, QR, repeater, image, and user categories.
	 *
	 * - **system**: Tags that match the built-in system tags list.
	 * - **signature**: Tags whose name contains the word "signature".
	 * - **qr**: Tags prefixed with `qr:` that generate QR code images.
	 * - **repeater**: Tags detected from `{#name}` block markers (table rows).
	 * - **image**: Tags detected with dimension syntax `{name:WxH}`.
	 * - **user**: All remaining tags that need form-field mapping.
	 *
	 * @since  1.0.0
	 * @since  1.3.0 Added `qr`, `repeater`, and `image` categories.
	 * @param  array $tags Array of tag names to classify.
	 * @return array {
	 *     Associative array with classified tags.
	 *
	 *     @type array $system    Tags matching the system tags list.
	 *     @type array $signature Tags containing 'signature'.
	 *     @type array $qr        Tags prefixed with 'qr:'.
	 *     @type array $repeater  Tags from repeater block markers.
	 *     @type array $image     Tags with image dimension syntax.
	 *     @type array $user      All remaining tags.
	 * }
	 */
	public function wprobo_documerge_classify_tags( $tags ) {
		$system_tags = $this->wprobo_documerge_get_system_tags();

		$classified = array(
			'system'    => array(),
			'signature' => array(),
			'qr'        => array(),
			'repeater'  => array(),
			'image'     => array(),
			'user'      => array(),
		);

		foreach ( $tags as $tag ) {
			if ( in_array( $tag, $system_tags, true ) ) {
				$classified['system'][] = $tag;
			} elseif ( false !== strpos( $tag, 'signature' ) ) {
				$classified['signature'][] = $tag;
			} elseif ( 0 === strpos( $tag, 'qr:' ) ) {
				$classified['qr'][] = $tag;
			} elseif ( 0 === strpos( $tag, '#' ) ) {
				// Repeater block marker — strip the '#' prefix for the tag name.
				$classified['repeater'][] = substr( $tag, 1 );
			} elseif ( preg_match( '/^\w+:\d+x\d+$/', $tag ) ) {
				// Image tag with dimensions — already normalised by scan_docx.
				$classified['image'][] = $tag;
			} else {
				$classified['user'][] = $tag;
			}
		}

		return $classified;
	}

	/**
	 * Scan the raw DOCX XML for extended tag patterns.
	 *
	 * PHPWord's getVariables() only returns `${var}` style placeholders.
	 * This method reads the raw document.xml from the DOCX ZIP archive
	 * to detect additional patterns:
	 *
	 * - Conditional blocks: `{if:field_name=value}`, `{if:field_name}`,
	 *   `{if:!field_name}` — extracts the field names referenced.
	 * - Repeater blocks: `{#fieldname}` — extracts the repeater name.
	 *
	 * The detected field names are returned as clean, lowercase strings
	 * ready for merging into the main tag list.
	 *
	 * @since  1.3.0
	 * @param  string $file_path Absolute path to the DOCX file.
	 * @return array Array of additional tag names found.
	 */
	public function wprobo_documerge_scan_extended_tags( $file_path ) {

		$tags = array();

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return $tags;
		}

		// Open the DOCX as a ZIP archive and read document.xml.
		$zip = new \ZipArchive();

		if ( true !== $zip->open( $file_path, \ZipArchive::RDONLY ) ) {
			return $tags;
		}

		$xml = $zip->getFromName( 'word/document.xml' );
		$zip->close();

		if ( empty( $xml ) ) {
			return $tags;
		}

		// Strip all XML tags to get a plain-text representation of
		// the document content. This avoids false negatives caused
		// by XML tags splitting our merge-tag markers mid-token.
		$plain = wp_strip_all_tags( $xml );

		// Detect conditional field names: {if:field_name...} patterns.
		// Enhanced syntax: {if:field=value}, {if:field!=value}, {if:field}, {if:!field}.
		if ( preg_match_all( '/\{if:!?(\w+)/', $plain, $cond_matches ) ) {
			foreach ( $cond_matches[1] as $field ) {
				$tags[] = strtolower( trim( $field ) );
			}
		}

		// Detect repeater block markers: {#fieldname}.
		if ( preg_match_all( '/\{#(\w+)\}/', $plain, $rep_matches ) ) {
			foreach ( $rep_matches[1] as $field ) {
				$tags[] = '#' . strtolower( trim( $field ) );
			}
		}

		// De-duplicate (main caller also de-duplicates, but be safe).
		return array_unique( $tags );
	}
}
