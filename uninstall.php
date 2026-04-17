<?php
/**
 * WPRobo DocuMerge uninstall handler.
 *
 * Fired when the plugin is deleted via WP Admin → Plugins → Delete.
 * Removes ALL plugin data: database tables, options, files, and cron events.
 *
 * @package    WPRobo_DocuMerge
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

// Only run if WordPress initiated the uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Drop custom DB tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wprdm_submissions" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wprdm_forms" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wprdm_templates" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// 2. Delete all plugin options.
$wprobo_documerge_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT option_name FROM {$wpdb->options}
     WHERE option_name LIKE 'wprobo_documerge_%'"
);
foreach ( $wprobo_documerge_options as $wprobo_documerge_option ) {
	delete_option( $wprobo_documerge_option );
}

// 3. Delete all generated document files.
// Remove the entire plugin-slug directory plus any legacy top-level dirs
// from previous versions.
$wprobo_documerge_upload_dir = wp_upload_dir();
$wprobo_documerge_basedir    = trailingslashit( $wprobo_documerge_upload_dir['basedir'] );
$wprobo_documerge_base       = $wprobo_documerge_basedir . 'wprobo-documerge-lite/';
$wprobo_documerge_docs_dir   = $wprobo_documerge_basedir . 'documerge-docs/';
$wprobo_documerge_temp_dir   = $wprobo_documerge_basedir . 'documerge-temp/';
$wprobo_documerge_logs_dir   = $wprobo_documerge_basedir . 'documerge-logs/';

/**
 * Recursively delete a directory and all its contents.
 *
 * @since 1.0.0
 * @param string $dir Absolute path to directory.
 */
function wprobo_documerge_delete_directory( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$files = array_diff( scandir( $dir ), array( '.', '..' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	foreach ( $files as $file ) {
		$path = $dir . DIRECTORY_SEPARATOR . $file;
		if ( is_dir( $path ) ) {
			wprobo_documerge_delete_directory( $path );
		} else {
			unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

// New plugin-slug directory (current layout).
wprobo_documerge_delete_directory( $wprobo_documerge_base );

// Legacy top-level directories (pre-1.0 installs may still have these).
wprobo_documerge_delete_directory( $wprobo_documerge_docs_dir );
wprobo_documerge_delete_directory( $wprobo_documerge_temp_dir );
wprobo_documerge_delete_directory( $wprobo_documerge_logs_dir );

// 4. Clear scheduled cron events.
wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_temp_files' );
wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_log_files' );
wp_clear_scheduled_hook( 'wprobo_documerge_retry_failed_emails' );
wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_expired_tokens' );
