<?php
/**
 * Plugin activation handler.
 *
 * Creates database tables, schedules cron jobs, and sets up
 * protected upload directories on plugin activation.
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
 * Class WPRobo_DocuMerge_Installer
 *
 * Handles everything that needs to happen when the plugin is activated:
 * database table creation, cron scheduling, and directory setup.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Installer {

	/**
	 * Run on plugin activation.
	 *
	 * @since 1.0.0
	 */
	public static function wprobo_documerge_activate() {
		self::wprobo_documerge_check_requirements();
		self::wprobo_documerge_create_tables();
		self::wprobo_documerge_schedule_cron_events();
		self::wprobo_documerge_migrate_legacy_directories();
		self::wprobo_documerge_create_directories();
		self::wprobo_documerge_set_default_options();

		// Set transient for wizard redirect on first activation.
		set_transient( 'wprobo_documerge_activation_redirect', true, 30 );
	}

	/**
	 * Get the plugin's base upload directory.
	 *
	 * Uses wp_upload_dir() at runtime so multisite, renamed wp-content,
	 * and upload-dir filters are honored. Stores under a plugin-slug
	 * subfolder as recommended by the Plugin Review Team.
	 *
	 * @since  1.0.0
	 * @return string Absolute path ending with a trailing slash.
	 */
	public static function wprobo_documerge_get_base_dir() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'wprobo-documerge-lite/';
	}

	/**
	 * Get the docs directory for generated PDF/DOCX output.
	 *
	 * @since  1.0.0
	 * @return string Absolute path ending with a trailing slash.
	 */
	public static function wprobo_documerge_get_docs_dir() {
		return self::wprobo_documerge_get_base_dir() . 'docs/';
	}

	/**
	 * Get the temp directory for intermediary files during conversion.
	 *
	 * @since  1.0.0
	 * @return string Absolute path ending with a trailing slash.
	 */
	public static function wprobo_documerge_get_temp_dir() {
		return self::wprobo_documerge_get_base_dir() . 'temp/';
	}

	/**
	 * Get the logs directory for debug/error logs.
	 *
	 * @since  1.0.0
	 * @return string Absolute path ending with a trailing slash.
	 */
	public static function wprobo_documerge_get_logs_dir() {
		return self::wprobo_documerge_get_base_dir() . 'logs/';
	}

	/**
	 * Check minimum server requirements.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_check_requirements() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( WPROBO_DOCUMERGE_BASENAME );
			wp_die(
				esc_html__( 'WPRobo DocuMerge requires PHP 7.4 or higher.', 'wprobo-documerge-lite' ),
				esc_html__( 'Plugin Activation Error', 'wprobo-documerge-lite' ),
				array( 'back_link' => true )
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, '6.2', '<' ) ) {
			deactivate_plugins( WPROBO_DOCUMERGE_BASENAME );
			wp_die(
				esc_html__( 'WPRobo DocuMerge requires WordPress 6.2 or higher.', 'wprobo-documerge-lite' ),
				esc_html__( 'Plugin Activation Error', 'wprobo-documerge-lite' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create all plugin database tables.
	 *
	 * Uses the canonical schema defined in CLAUDE.md Section 10.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── TABLE 1: Templates ────────────────────────────────────────────
		$sql = "CREATE TABLE {$wpdb->prefix}wprdm_templates (
            id              bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
            name            varchar(200)          NOT NULL DEFAULT '',
            description     text                  NOT NULL,
            file_path       varchar(500)          NOT NULL DEFAULT '',
            file_name       varchar(255)          NOT NULL DEFAULT '',
            output_format   varchar(10)           NOT NULL DEFAULT 'pdf',
            merge_tags      longtext              NOT NULL,
            created_at      datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at      datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id)
        ) {$charset_collate};";
		dbDelta( $sql );

		// ── TABLE 2: Forms ────────────────────────────────────────────────
		$sql = "CREATE TABLE {$wpdb->prefix}wprdm_forms (
            id                  bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
            title               varchar(200)          NOT NULL DEFAULT '',
            template_id         bigint(20)   UNSIGNED NOT NULL DEFAULT 0,
            mode                varchar(20)           NOT NULL DEFAULT 'standalone',
            integration         varchar(50)           NOT NULL DEFAULT '',
            fields              longtext              NOT NULL,
            settings            longtext              NOT NULL,
            output_format       varchar(10)           NOT NULL DEFAULT 'pdf',
            delivery_methods    varchar(200)          NOT NULL DEFAULT 'download',
            submit_label        varchar(200)          NOT NULL DEFAULT 'Submit',
            success_message     text                  NOT NULL,
            redirect_url        varchar(500)          NOT NULL DEFAULT '',
            created_at          datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at          datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY template_id (template_id)
        ) {$charset_collate};";
		dbDelta( $sql );

		// ── TABLE 3: Submissions ──────────────────────────────────────────
		$sql = "CREATE TABLE {$wpdb->prefix}wprdm_submissions (
            id                  bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id             bigint(20)   UNSIGNED NOT NULL DEFAULT 0,
            template_id         bigint(20)   UNSIGNED NOT NULL DEFAULT 0,
            submitter_email     varchar(200)          NOT NULL DEFAULT '',
            form_data           longtext              NOT NULL,
            doc_path_docx       varchar(500)          NOT NULL DEFAULT '',
            doc_path_pdf        varchar(500)          NOT NULL DEFAULT '',
            status              varchar(30)           NOT NULL DEFAULT 'processing',
            error_log           text                  NOT NULL,
            retry_count         tinyint(3)            NOT NULL DEFAULT 0,
            delivery_status     varchar(30)           NOT NULL DEFAULT 'pending',
            admin_notes         text                  NOT NULL,
            ip_address          varchar(45)           NOT NULL DEFAULT '',
            created_at          datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at          datetime              NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
		dbDelta( $sql );

		// Store DB version for future migrations.
		update_option( 'wprobo_documerge_db_version', WPROBO_DOCUMERGE_DB_VERSION );
	}

	/**
	 * Schedule recurring cron events.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_schedule_cron_events() {
		$cron_schedules = array(
			'wprobo_documerge_cleanup_temp_files'     => 'hourly',
			'wprobo_documerge_cleanup_log_files'      => 'daily',
			'wprobo_documerge_retry_failed_emails'    => 'hourly',
			'wprobo_documerge_cleanup_expired_tokens' => 'hourly',
		);

		/**
		 * Filters the cron event schedules registered on plugin activation.
		 *
		 * Allows modifying the recurrence interval for each scheduled
		 * cron hook. Keys are the hook names and values are WordPress
		 * cron recurrence strings (e.g. 'hourly', 'daily', 'twicedaily').
		 *
		 * @since 1.2.0
		 *
		 * @param array $cron_schedules Associative array of hook_name => recurrence.
		 */
		$cron_schedules = apply_filters( 'wprobo_documerge_cron_schedules', $cron_schedules );

		foreach ( $cron_schedules as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time(), sanitize_key( $recurrence ), sanitize_key( $hook ) );
			}
		}
	}

	/**
	 * Create protected upload directories.
	 *
	 * Directories are protected with .htaccess (deny from all) and
	 * an empty index.php for belt-and-braces security.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_create_directories() {
		$directories = array(
			self::wprobo_documerge_get_docs_dir(),
			self::wprobo_documerge_get_temp_dir(),
			self::wprobo_documerge_get_logs_dir(),
		);

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( $directories as $dir ) {
			if ( ! $wp_filesystem->is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			// .htaccess — deny direct access.
			$htaccess_path = $dir . '.htaccess';
			if ( ! $wp_filesystem->exists( $htaccess_path ) ) {
				$wp_filesystem->put_contents(
					$htaccess_path,
					"Options -Indexes\ndeny from all\n",
					FS_CHMOD_FILE
				);
			}

			// index.php — belt and braces.
			$index_path = $dir . 'index.php';
			if ( ! $wp_filesystem->exists( $index_path ) ) {
				$wp_filesystem->put_contents(
					$index_path,
					"<?php\n// Silence is golden.\n",
					FS_CHMOD_FILE
				);
			}
		}
	}

	/**
	 * Migrate legacy upload directories to the plugin-slug layout.
	 *
	 * Earlier versions stored files under:
	 *   uploads/documerge-docs/
	 *   uploads/documerge-temp/
	 *   uploads/documerge-logs/
	 *
	 * The WordPress Plugin Review Team requires plugin files to live
	 * under a plugin-slug folder:
	 *   uploads/wprobo-documerge-lite/docs/
	 *   uploads/wprobo-documerge-lite/temp/
	 *   uploads/wprobo-documerge-lite/logs/
	 *
	 * This method renames the old directories to the new location on
	 * activation. If a new directory already exists it is left alone,
	 * preserving whatever data is there.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_migrate_legacy_directories() {
		$upload_dir = wp_upload_dir();
		$basedir    = trailingslashit( $upload_dir['basedir'] );

		$map = array(
			$basedir . 'documerge-docs' => self::wprobo_documerge_get_docs_dir(),
			$basedir . 'documerge-temp' => self::wprobo_documerge_get_temp_dir(),
			$basedir . 'documerge-logs' => self::wprobo_documerge_get_logs_dir(),
		);

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( $map as $legacy => $new ) {
			$legacy_clean = rtrim( $legacy, '/' );
			$new_clean    = rtrim( $new, '/' );

			if ( ! $wp_filesystem->is_dir( $legacy_clean ) ) {
				continue;
			}
			if ( $wp_filesystem->is_dir( $new_clean ) ) {
				// Do not overwrite an existing new-path directory.
				continue;
			}

			// Ensure the parent (wprobo-documerge-lite/) exists.
			wp_mkdir_p( dirname( $new_clean ) );

			$wp_filesystem->move( $legacy_clean, $new_clean );
		}
	}

	/**
	 * Set default plugin options on first activation.
	 *
	 * @since 1.0.0
	 */
	private static function wprobo_documerge_set_default_options() {
		if ( false === get_option( 'wprobo_documerge_settings' ) ) {
			update_option(
				'wprobo_documerge_settings',
				array(
					'output_format'    => 'pdf',
					'delivery_method'  => 'download',
					'delete_docs_days' => 0,
				)
			);
		}

		if ( false === get_option( 'wprobo_documerge_wizard_completed' ) ) {
			update_option( 'wprobo_documerge_wizard_completed', 'no' );
		}
	}
}
