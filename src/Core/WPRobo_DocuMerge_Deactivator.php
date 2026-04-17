<?php
/**
 * Plugin deactivation handler.
 *
 * Clears all scheduled cron events when the plugin is deactivated.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Core
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Deactivator
 *
 * Handles cleanup tasks when the plugin is deactivated.
 * Does NOT delete data — that happens in uninstall.php.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * Clears all scheduled cron hooks to prevent orphaned events.
	 *
	 * @since 1.0.0
	 */
	public static function wprobo_documerge_deactivate() {
		wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_temp_files' );
		wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_log_files' );
		wp_clear_scheduled_hook( 'wprobo_documerge_retry_failed_emails' );
		wp_clear_scheduled_hook( 'wprobo_documerge_cleanup_expired_tokens' );
	}
}
