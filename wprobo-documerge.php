<?php
/**
 * WPRobo DocuMerge Lite
 *
 * @package           WPRobo_DocuMerge
 * @author            Ali Shan
 * @copyright         2026 WPRobo Limited
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WPRobo DocuMerge Lite
 * Plugin URI:        https://wprobo.com/plugins/wprobo-documerge/
 * Description:       Automate document generation from form submissions. Upload Word/DOCX templates, collect data via forms, and deliver personalised documents automatically. <a href="https://wprobo.com/plugins/wprobo-documerge/?utm_source=lite&utm_medium=plugin&utm_campaign=upgrade">Upgrade to Pro</a> for signature fields, Stripe payments, conditional logic, and more.
 * Version:           1.0.2
 * Requires at least: 6.2
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Ali Shan
 * Author URI:        https://wprobo.com
 * Text Domain:       wprobo-documerge-lite
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Activation / Deactivation hooks MUST be registered before any bail-out ───
// Otherwise they never fire when Pro is active (because the early return skips them).
// The Lite plugin does NOT touch Pro's activation status — users deactivate Pro
// themselves if they want Lite to take over.

// Activation: run installer.
register_activation_hook(
	__FILE__,
	function () {
		// Constants may not be set yet during activation, define path manually.
		$plugin_path = plugin_dir_path( __FILE__ );

		// Load autoloader for installer.
		spl_autoload_register(
			function ( $fqcn ) use ( $plugin_path ) {
				$prefix   = 'WPRobo\\DocuMerge\\';
				$base_dir = $plugin_path . 'src/';
				$len      = strlen( $prefix );
				if ( 0 !== strncmp( $prefix, $fqcn, $len ) ) {
						return;
				}
				$relative_class = substr( $fqcn, $len );
				$parts          = explode( '\\', $relative_class );
				$classname      = array_pop( $parts );
				$subdir         = implode( '/', $parts );
				$file           = $base_dir . ( $subdir ? $subdir . '/' : '' ) . $classname . '.php';
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		);

		if ( class_exists( 'WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer' ) ) {
			\WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_activate();
		}
	}
);

// Deactivation.
register_deactivation_hook(
	__FILE__,
	function () {
		if ( class_exists( 'WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Deactivator' ) ) {
			\WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Deactivator::wprobo_documerge_deactivate();
		}
	}
);

// ─── Check if Pro is actually active (not just in memory from this request) ───
$wprobo_documerge_active_plugins = (array) get_option( 'active_plugins', array() );
if ( in_array( 'wprobo-docu-merge/wprobo-documerge.php', $wprobo_documerge_active_plugins, true ) ) {
	return;
}

// Lite marker.
if ( ! defined( 'WPROBO_DOCUMERGE_LITE' ) ) {
	define( 'WPROBO_DOCUMERGE_LITE', true );
}

// All constants guarded to prevent collisions during Lite/Pro switch.
if ( ! defined( 'WPROBO_DOCUMERGE_VERSION' ) ) {
	define( 'WPROBO_DOCUMERGE_VERSION', '1.0.2' );
}
if ( ! defined( 'WPROBO_DOCUMERGE_DB_VERSION' ) ) {
	define( 'WPROBO_DOCUMERGE_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'WPROBO_DOCUMERGE_FILE' ) ) {
	define( 'WPROBO_DOCUMERGE_FILE', __FILE__ );
}
if ( ! defined( 'WPROBO_DOCUMERGE_PATH' ) ) {
	define( 'WPROBO_DOCUMERGE_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPROBO_DOCUMERGE_URL' ) ) {
	define( 'WPROBO_DOCUMERGE_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WPROBO_DOCUMERGE_BASENAME' ) ) {
	define( 'WPROBO_DOCUMERGE_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'WPROBO_DOCUMERGE_UPGRADE_URL' ) ) {
	define( 'WPROBO_DOCUMERGE_UPGRADE_URL', 'https://wprobo.com/plugins/wprobo-documerge/?utm_source=lite&utm_medium=plugin&utm_campaign=upgrade' );
}

// Composer autoloader — loads PHPWord, mPDF, and other vendor dependencies.
if ( file_exists( WPROBO_DOCUMERGE_PATH . 'vendor/autoload.php' ) ) {
	require_once WPROBO_DOCUMERGE_PATH . 'vendor/autoload.php';
}

// Custom PSR-4 autoloader for plugin classes (src/).
spl_autoload_register(
	function ( $fqcn ) {
		$prefix   = 'WPRobo\\DocuMerge\\';
		$base_dir = WPROBO_DOCUMERGE_PATH . 'src/';
		$len      = strlen( $prefix );

		if ( 0 !== strncmp( $prefix, $fqcn, $len ) ) {
				return;
		}

		$relative_class = substr( $fqcn, $len );
		$parts          = explode( '\\', $relative_class );
		$classname      = array_pop( $parts );
		$subdir         = implode( '/', $parts );
		$file           = $base_dir . ( $subdir ? $subdir . '/' : '' ) . $classname . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Bootstrap the plugin.
add_action(
	'plugins_loaded',
	function () {
		// Check if Pro is actually active (not just loaded in memory from this request).
		// During activation, Pro may have been deactivated but its constants remain in memory.
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$pro_is_active  = in_array( 'wprobo-docu-merge/wprobo-documerge.php', $active_plugins, true );
		if ( $pro_is_active ) {
			return;
		}
		\WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Plugin::get_instance()->wprobo_documerge_run();
	}
);
