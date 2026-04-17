<?php
/**
 * Template CRUD manager.
 *
 * Provides create, read, update, and delete operations for the
 * wprdm_templates database table with transient caching.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Template
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Template;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Template_Manager
 *
 * Handles all CRUD operations for document templates stored in the
 * wprdm_templates table. Uses WordPress transients for caching list
 * and count queries with a one-hour TTL.
 *
 * This is NOT a singleton — instantiate normally with `new`.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Template_Manager {

	/**
	 * Cache TTL in seconds (1 hour).
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	private const WPROBO_DOCUMERGE_CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Retrieve all templates, with transient caching.
	 *
	 * Checks the 'wprobo_documerge_templates_list' transient first.
	 * On a cache miss, queries the database and stores the result
	 * for one hour.
	 *
	 * @since  1.0.0
	 * @return array Array of template objects, ordered by name ascending.
	 */
	public function wprobo_documerge_get_all_templates() {
		$cached = get_transient( 'wprobo_documerge_templates_list' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_templates';

		$results = $wpdb->get_results(
			"SELECT id, name, description, file_name, output_format, merge_tags, created_at, updated_at
             FROM {$table}
             ORDER BY name ASC"
		);

		if ( ! is_array( $results ) ) {
			$results = array();
		}

		set_transient( 'wprobo_documerge_templates_list', $results, self::WPROBO_DOCUMERGE_CACHE_TTL );

		return $results;
	}

	/**
	 * Retrieve a single template by ID.
	 *
	 * @since  1.0.0
	 * @param  int $id The template ID.
	 * @return object|null Template row object on success, null on failure.
	 */
	public function wprobo_documerge_get_template( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_templates';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);

		return $row;
	}

	/**
	 * Save a template (insert or update).
	 *
	 * When `$data['id']` is present and greater than zero the existing
	 * row is updated; otherwise a new row is inserted. The `merge_tags`
	 * value is JSON-encoded automatically when passed as an array.
	 *
	 * After a successful save the template-list and template-count
	 * transients are deleted and the `wprobo_documerge_template_saved`
	 * action is fired.
	 *
	 * @since  1.0.0
	 * @param  array $data {
	 *     Template data.
	 *
	 *     @type int    $id            Optional. Template ID for updates.
	 *     @type string $name          Template name.
	 *     @type string $description   Template description.
	 *     @type string $file_path     Absolute path to the template file on disk.
	 *     @type string $file_name     Original uploaded file name.
	 *     @type string $output_format Output format (e.g. 'pdf', 'docx').
	 *     @type mixed  $merge_tags    Array or JSON string of merge tag names.
	 * }
	 * @return int|false The template ID on success, false on failure.
	 */
	public function wprobo_documerge_save_template( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_templates';

		// JSON-encode merge_tags when provided as an array.
		if ( isset( $data['merge_tags'] ) && is_array( $data['merge_tags'] ) ) {
			$data['merge_tags'] = wp_json_encode( $data['merge_tags'] );
		}

		$now         = current_time( 'mysql' );
		$template_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		/**
		 * Fires before a template is saved (created or updated).
		 *
		 * Allows third-party code to perform actions or modify
		 * external state before the database write occurs.
		 *
		 * @since 1.2.0
		 *
		 * @param array $data        The template data being saved.
		 * @param int   $template_id The template ID (0 for new templates).
		 */
		do_action( 'wprobo_documerge_before_template_save', $data, $template_id );

		if ( ! empty( $data['id'] ) && $data['id'] > 0 ) {
			// ── UPDATE ──────────────────────────────────────────────────
			$id = absint( $data['id'] );

			$update_data = array(
				'name'          => $data['name'],
				'description'   => $data['description'],
				'file_path'     => $data['file_path'],
				'file_name'     => $data['file_name'],
				'output_format' => $data['output_format'],
				'merge_tags'    => $data['merge_tags'],
				'updated_at'    => $now,
			);

			$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

			$result = $wpdb->update(
				$table,
				$update_data,
				array( 'id' => $id ),
				$format,
				array( '%d' )
			);

			if ( false === $result ) {
				return false;
			}
		} else {
			// ── INSERT ──────────────────────────────────────────────────
			$insert_data = array(
				'name'          => $data['name'],
				'description'   => $data['description'],
				'file_path'     => $data['file_path'],
				'file_name'     => $data['file_name'],
				'output_format' => $data['output_format'],
				'merge_tags'    => $data['merge_tags'],
				'created_at'    => $now,
				'updated_at'    => $now,
			);

			$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

			$result = $wpdb->insert(
				$table,
				$insert_data,
				$format
			);

			if ( false === $result ) {
				return false;
			}

			$id = $wpdb->insert_id;
		}

		// Bust caches.
		delete_transient( 'wprobo_documerge_templates_list' );
		delete_transient( 'wprobo_documerge_templates_count' );

		/**
		 * Fires after a template has been saved (created or updated).
		 *
		 * @since 1.0.0
		 * @param int $id The saved template ID.
		 */
		do_action( 'wprobo_documerge_template_saved', $id );

		return $id;
	}

	/**
	 * Delete a template by ID.
	 *
	 * Removes the template file from disk via WP_Filesystem, deletes
	 * the database row, busts caches, and fires the
	 * `wprobo_documerge_template_deleted` action.
	 *
	 * @since  1.0.0
	 * @param  int $id The template ID to delete.
	 * @return bool True on success, false on failure.
	 */
	public function wprobo_documerge_delete_template( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = $wpdb->prefix . 'wprdm_templates';

		// Retrieve the template so we can delete its file.
		$template = $this->wprobo_documerge_get_template( $id );

		if ( ! $template ) {
			return false;
		}

		// Delete the template file from disk.
		if ( ! empty( $template->file_path ) ) {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			if ( $wp_filesystem->exists( $template->file_path ) ) {
				$wp_filesystem->delete( $template->file_path );
			}
		}

		// Delete the database row.
		$deleted = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return false;
		}

		// Bust caches.
		delete_transient( 'wprobo_documerge_templates_list' );
		delete_transient( 'wprobo_documerge_templates_count' );

		/**
		 * Fires after a template has been deleted.
		 *
		 * @since 1.0.0
		 * @param int $id The deleted template ID.
		 */
		do_action( 'wprobo_documerge_template_deleted', $id );

		return true;
	}

	/**
	 * Get the total number of templates.
	 *
	 * Uses the 'wprobo_documerge_templates_count' transient with a
	 * one-hour TTL. Returns an integer count.
	 *
	 * @since  1.0.0
	 * @return int Total number of templates.
	 */
	public function wprobo_documerge_get_template_count() {
		$cached = get_transient( 'wprobo_documerge_templates_count' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_templates';

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		set_transient( 'wprobo_documerge_templates_count', $count, self::WPROBO_DOCUMERGE_CACHE_TTL );

		return $count;
	}

	/**
	 * Count forms that reference a given template.
	 *
	 * Queries the wprdm_forms table to determine how many forms
	 * are currently using the specified template.
	 *
	 * @since  1.0.0
	 * @param  int $template_id The template ID to check.
	 * @return int Number of forms using this template.
	 */
	public function wprobo_documerge_get_forms_using_template( $template_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wprdm_forms';

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE template_id = %d",
				$template_id
			)
		);

		return $count;
	}
}
