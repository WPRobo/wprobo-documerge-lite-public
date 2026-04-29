<?php
/**
 * Template merge-tag replacement engine.
 *
 * Processes DOCX templates by replacing merge tags with field data,
 * embedding signature images, and applying format modifiers.
 * Conditional blocks, repeater/table rows, image tags, and QR codes
 * are available in WPRobo DocuMerge Pro.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Template
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Template;

use PhpOffice\PhpWord\TemplateProcessor;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Merge_Engine
 *
 * Core tag replacement engine that processes DOCX templates by
 * injecting system auto-tags, applying modifiers, and embedding
 * signature images.
 *
 * This class is NOT a singleton; create a new instance per merge operation.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Merge_Engine {

	/**
	 * Temporary file paths created during processing.
	 *
	 * Stores paths to temp files (e.g. decoded images) so the caller
	 * can clean them up after saveAs().
	 *
	 * @since 1.3.0
	 * @var   array
	 */
	private $wprobo_documerge_temp_files = array();

	/**
	 * Run the full merge pipeline on a template.
	 *
	 * Executes every processing step in the correct order:
	 * 1. Format / modifier pipes (direct XML replacement)
	 * 2. Standard tag replacement (setValue) — includes signature, system tags
	 *
	 * Conditional blocks, repeater/table rows, image tags, and QR codes
	 * are available in WPRobo DocuMerge Pro.
	 *
	 * @since  1.3.0
	 * @param  TemplateProcessor $template_processor The PhpWord template processor instance.
	 * @param  array             $field_data         Associative array of tag_name => value pairs.
	 * @return TemplateProcessor The modified template processor instance.
	 */
	public function wprobo_documerge_run_pipeline( TemplateProcessor $template_processor, array $field_data ) {

		// Step 1: Process format / modifier pipes directly in the XML.
		$field_data = $this->wprobo_documerge_process_format_pipes( $template_processor, $field_data );

		// Step 2: Standard tag replacement (includes signature, system tags).
		$this->wprobo_documerge_replace_tags( $template_processor, $field_data );

		return $template_processor;
	}

	/**
	 * Get temporary file paths created during processing.
	 *
	 * The caller should delete these files after calling saveAs()
	 * on the template processor.
	 *
	 * @since  1.3.0
	 * @return array List of absolute file paths.
	 */
	public function wprobo_documerge_get_temp_files() {
		return $this->wprobo_documerge_temp_files;
	}

	/**
	 * Replace merge tags in a template with field data.
	 *
	 * Injects system auto-tags (current_date, current_time, site_name,
	 * site_url), then processes each user-supplied field value. Supports
	 * modifiers (|upper, |lower, |ucfirst, |ucwords, |format:X) and
	 * signature embedding. All values are XML-escaped before insertion.
	 * Any remaining unfilled tags are stripped after processing.
	 *
	 * @since  1.0.0
	 * @param  TemplateProcessor $template_processor The PhpWord template processor instance.
	 * @param  array             $field_data         Associative array of tag_name => value pairs.
	 * @return TemplateProcessor The modified template processor instance.
	 */
	public function wprobo_documerge_replace_tags( TemplateProcessor $template_processor, array $field_data ) {

		// Inject system auto-tags.
		$date_format = get_option( 'wprobo_documerge_date_format', 'd/m/Y' );
		$time_format = get_option( 'wprobo_documerge_time_format', 'H:i' );

		$system_tags = array(
			'current_date' => gmdate( $date_format ),
			'current_time' => gmdate( $time_format ),
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => home_url(),
		);

		/**
		 * Filters the system-level merge tags added to every document.
		 *
		 * Allows adding custom auto-populated tags like {order_number},
		 * {invoice_id}, or {custom_date}. Developers frequently need
		 * custom system-level merge tags beyond the built-in set.
		 *
		 * @since 1.0.0
		 *
		 * @param array $system_tags Key-value pairs of system tag => value.
		 * @param array $field_data  The user-supplied field data for context.
		 */
		$system_tags = apply_filters( 'wprobo_documerge_system_tags', $system_tags, $field_data );

		foreach ( $system_tags as $tag => $value ) {
			$escaped = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			$template_processor->setValue( $tag, $escaped );
		}

		// Process each user-supplied field.
		foreach ( $field_data as $tag => $value ) {

			// Skip repeater arrays — already handled by process_repeater_tags.
			if ( is_array( $value ) ) {
				continue;
			}

			// Handle signature tag separately.
			if ( 'signature' === $tag ) {
				$this->wprobo_documerge_embed_signature( $template_processor, $value );
				continue;
			}

			// Parse modifiers from the tag name.
			$raw_tag  = $tag;
			$modifier = '';

			if ( false !== strpos( $tag, '|' ) ) {
				$parts    = explode( '|', $tag, 2 );
				$raw_tag  = $parts[0];
				$modifier = $parts[1];
			}

			// Apply modifiers to the value.
			$value = $this->wprobo_documerge_apply_modifier( (string) $value, $modifier, $raw_tag );

			// XML-escape the value for safe insertion into DOCX XML.
			$escaped_value = htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );

			$template_processor->setValue( $raw_tag, $escaped_value );
		}

		// Strip any remaining unfilled tags.
		$remaining = $template_processor->getVariables();

		foreach ( $remaining as $unfilled_tag ) {
			$template_processor->setValue( $unfilled_tag, '' );
		}

		return $template_processor;
	}

	/**
	 * Process format/modifier pipe syntax in the template XML.
	 *
	 * Scans the template for tags containing pipe modifiers such as
	 * `{date_signed|format:d M Y}`, `{name|upper}`, `{name|lower}`,
	 * `{name|ucfirst}`, or `{name|ucwords}`. These are replaced
	 * directly in the XML because PHPWord's getVariables() does not
	 * always return pipe-containing variable names reliably.
	 *
	 * Must be called BEFORE standard tag replacement.
	 *
	 * @since  1.3.0
	 * @param  TemplateProcessor $template_processor The PhpWord template processor instance.
	 * @param  array             $field_data         Associative array of tag_name => value pairs.
	 * @return array The field_data array (unmodified — pipes are resolved in XML).
	 */
	public function wprobo_documerge_process_format_pipes( TemplateProcessor $template_processor, array $field_data ) {

		$reflection = new \ReflectionClass( $template_processor );
		$property   = $reflection->getProperty( 'tempDocumentMainPart' );
		$property->setAccessible( true );

		$xml = $property->getValue( $template_processor );

		// Match tags with pipes: {field|modifier} or {field|format:pattern}
		// DocuMerge templates use {var} syntax (PHPWord's macro chars are
		// reconfigured to '{' / '}' in the Docx Processor).
		$pattern = '/\{([a-zA-Z0-9_]+)\|([^}]+)\}/';

		$xml = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $field_data ) {

				$field_name = $matches[1];
				$modifier   = $matches[2];
				$value      = isset( $field_data[ $field_name ] ) ? (string) $field_data[ $field_name ] : '';

				$value = $this->wprobo_documerge_apply_modifier( $value, $modifier, $field_name );

				return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			},
			$xml
		);

		$property->setValue( $template_processor, $xml );

		return $field_data;
	}

	/**
	 * Apply a modifier string to a value.
	 *
	 * Supported modifiers:
	 * - `upper`          — Convert to uppercase.
	 * - `lower`          — Convert to lowercase.
	 * - `ucfirst`        — Uppercase first character.
	 * - `ucwords`        — Uppercase first character of each word.
	 * - `format:PATTERN` — Format a date value using PHP gmdate() syntax.
	 *
	 * Returns the original value unchanged if the modifier is empty
	 * or unrecognised.
	 *
	 * @since  1.3.0
	 * @param  string $value      The raw field value.
	 * @param  string $modifier   The modifier string (e.g. "upper", "format:d M Y").
	 * @param  string $field_name Optional. The merge tag field name for filter context.
	 * @return string The modified value.
	 */
	public function wprobo_documerge_apply_modifier( $value, $modifier, $field_name = '' ) {

		$modifier = trim( $modifier );

		if ( empty( $modifier ) ) {
			return $value;
		}

		if ( 'upper' === $modifier ) {
			return strtoupper( $value );
		}

		if ( 'lower' === $modifier ) {
			return strtolower( $value );
		}

		if ( 'ucfirst' === $modifier ) {
			return ucfirst( $value );
		}

		if ( 'ucwords' === $modifier ) {
			return ucwords( $value );
		}

		if ( 0 === strpos( $modifier, 'format:' ) ) {
			$format_string = substr( $modifier, 7 );
			$timestamp     = strtotime( $value );

			if ( false !== $timestamp ) {
				$value = gmdate( $format_string, $timestamp );
			}
		}

		/**
		 * Filters a merge tag value after a modifier is applied.
		 *
		 * Allows custom modifiers beyond the built-in upper/lower/format/ucfirst/ucwords.
		 * For example, a developer could add a 'currency' modifier:
		 * `{price|currency}` for custom currency formatting.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value      The modified value.
		 * @param string $modifier   The modifier name (e.g., 'upper', 'currency').
		 * @param string $field_name The merge tag field name.
		 */
		$value = apply_filters( 'wprobo_documerge_modifier_value', $value, $modifier, $field_name );

		return $value;
	}


	/**
	 * Embed a signature image into the template.
	 *
	 * Decodes a base64-encoded PNG image and writes it to a temporary
	 * file, then injects it into the template at the {signature} tag
	 * position using PhpWord's setImageValue().
	 *
	 * @since  1.0.0
	 * @param  TemplateProcessor $template_processor The PhpWord template processor instance.
	 * @param  string            $base64_data        Base64-encoded PNG image data (with or without data URI prefix).
	 * @return string The path to the temporary signature image file. The caller is responsible for cleanup after save.
	 */
	public function wprobo_documerge_embed_signature( TemplateProcessor $template_processor, $base64_data ) {

		// Strip data URI prefix if present.
		if ( 0 === strpos( $base64_data, 'data:image/png;base64,' ) ) {
			$base64_data = substr( $base64_data, strlen( 'data:image/png;base64,' ) );
		}

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$image_data = base64_decode( $base64_data, true );

		if ( false === $image_data ) {
			return '';
		}

		// Ensure the temp directory exists.
		$temp_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_temp_dir();

		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$temp_path = $temp_dir . 'sig_' . uniqid() . '.png';

		// Write signature image to temp file using WP Filesystem.
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$wp_filesystem->put_contents( $temp_path, $image_data, FS_CHMOD_FILE );

		$this->wprobo_documerge_temp_files[] = $temp_path;

		// Embed the image in the template at the signature placeholder.
		$template_processor->setImageValue(
			'signature',
			array(
				'path'   => $temp_path,
				'width'  => 200,
				'height' => 50,
				'ratio'  => true,
			)
		);

		return $temp_path;
	}
}
