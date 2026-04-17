<?php
/**
 * Logging helper.
 *
 * Provides static methods for writing structured log entries to
 * daily-rotated log files when WP_DEBUG is enabled.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Helpers
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Logger
 *
 * Static logger that writes structured, daily-rotated log files
 * to the WordPress uploads directory. Logging only occurs when
 * WP_DEBUG is enabled.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Logger {

	/**
	 * Write a log entry to the daily log file.
	 *
	 * Logs are written only when the WP_DEBUG constant is true. Each entry
	 * includes a timestamp, severity level, message, and optional context
	 * data encoded as JSON.
	 *
	 * Log files are stored at:
	 * wp-content/uploads/documerge-logs/documerge-YYYY-MM-DD.log
	 *
	 * Since the WP Filesystem API does not support file appending, the
	 * existing file content is read and the new entry is appended before
	 * writing the full content back.
	 *
	 * @since  1.0.0
	 * @param  string $message The log message to record.
	 * @param  string $level   The severity level: debug, info, warning, or error. Default 'info'.
	 * @param  array  $context Optional associative array of contextual data to include. Default empty array.
	 * @return bool True if the log entry was written successfully, false otherwise.
	 */
	public static function wprobo_documerge_log( $message, $level = 'info', $context = array() ) {

		// Only log when WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return false;
		}

		// Validate the log level.
		$valid_levels = array( 'debug', 'info', 'warning', 'error' );

		if ( ! in_array( $level, $valid_levels, true ) ) {
			$level = 'info';
		}

		$log_path = self::wprobo_documerge_get_log_path();
		$log_dir  = dirname( $log_path );

		// Create the log directory if it does not exist.
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Build the log entry.
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$level_tag = strtoupper( $level );

		$entry = sprintf(
			'[%s] [%s] %s',
			$timestamp,
			$level_tag,
			$message
		);

		if ( ! empty( $context ) ) {
			$entry .= ' | Context: ' . wp_json_encode( $context );
		}

		$entry .= PHP_EOL;

		// Initialize the WP Filesystem.
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Read existing content and append the new entry.
		$existing = '';

		if ( $wp_filesystem->exists( $log_path ) ) {
			$existing = $wp_filesystem->get_contents( $log_path );

			if ( false === $existing ) {
				$existing = '';
			}
		}

		$new_content = $existing . $entry;

		return $wp_filesystem->put_contents( $log_path, $new_content, FS_CHMOD_FILE );
	}

	/**
	 * Get the file path for the current day's log file.
	 *
	 * Returns the absolute path to the daily log file based on the
	 * current UTC date. The log directory is located within the
	 * WordPress uploads directory.
	 *
	 * @since  1.0.0
	 * @return string Absolute path to the current day's log file.
	 */
	public static function wprobo_documerge_get_log_path() {

		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/documerge-logs/documerge-' . gmdate( 'Y-m-d' ) . '.log';
	}
}
