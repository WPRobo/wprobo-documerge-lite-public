<?php
/**
 * Form Builder CRUD operations.
 *
 * Handles creating, reading, updating, deleting, duplicating,
 * and counting forms stored in the wprdm_forms table.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage Form
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Form;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Form_Builder
 *
 * Provides CRUD operations for the wprdm_forms table.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Form_Builder {

	/**
	 * The full forms table name including WP prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $table_name;

	/**
	 * The full submissions table name including WP prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $submissions_table_name;

	/**
	 * Constructor.
	 *
	 * Sets up the table names using the WordPress database prefix.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name             = $wpdb->prefix . 'wprdm_forms';
		$this->submissions_table_name = $wpdb->prefix . 'wprdm_submissions';
	}

	/**
	 * Retrieve all forms with a subset of columns, cached via transient.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of form objects.
	 */
	public function wprobo_documerge_get_all_forms() {
		$cached = get_transient( 'wprobo_documerge_forms_list' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, title, template_id, mode, integration, fields, output_format, created_at FROM {$this->table_name} ORDER BY title ASC LIMIT %d",
				PHP_INT_MAX
			)
		);

		if ( null === $results ) {
			return array();
		}

		set_transient( 'wprobo_documerge_forms_list', $results, HOUR_IN_SECONDS );

		return $results;
	}

	/**
	 * Retrieve a single form by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The form ID.
	 * @return object|null The form object or null if not found.
	 */
	public function wprobo_documerge_get_form( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( 0 === $id ) {
			return null;
		}

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );

		return $result ? $result : null;
	}

	/**
	 * Save a form (insert or update).
	 *
	 * If $data['id'] is set and greater than zero, performs an UPDATE.
	 * Otherwise performs an INSERT. JSON-encodes the fields and settings
	 * columns when provided as arrays.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Associative array of column values.
	 * @return int|\WP_Error The form ID on success, or WP_Error on failure.
	 */
	public function wprobo_documerge_save_form( $data ) {
		global $wpdb;

		// JSON-encode array values for fields and settings columns.
		if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
			$data['fields'] = wp_json_encode( $data['fields'] );
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = wp_json_encode( $data['settings'] );
		}

		$now = current_time( 'mysql' );

		// Determine the form ID (0 for new forms).
		$form_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		/**
		 * Fires before a form configuration is saved to the database.
		 *
		 * Allows validation or modification of form configuration before
		 * it is persisted. Useful for enforcing custom business rules
		 * on form structure.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data    The form data array being saved.
		 * @param int   $form_id The form ID (0 for new forms).
		 */
		do_action( 'wprobo_documerge_before_form_save', $data, $form_id );

		if ( ! empty( $data['id'] ) && absint( $data['id'] ) > 0 ) {
			// Update existing form.
			$id                 = absint( $data['id'] );
			$data['updated_at'] = $now;
			unset( $data['id'] );

			$updated = $wpdb->update(
				$this->table_name,
				$data,
				array( 'id' => $id ),
				null,
				array( '%d' )
			);

			if ( false === $updated ) {
				return new \WP_Error(
					'wprobo_documerge_form_update_failed',
					__( 'Failed to update the form.', 'wprobo-documerge-lite' )
				);
			}
		} else {
			// Insert new form.
			unset( $data['id'] );
			$data['created_at'] = $now;
			$data['updated_at'] = $now;

			$inserted = $wpdb->insert(
				$this->table_name,
				$data
			);

			if ( false === $inserted ) {
				return new \WP_Error(
					'wprobo_documerge_form_insert_failed',
					__( 'Failed to insert the form.', 'wprobo-documerge-lite' )
				);
			}

			$id = (int) $wpdb->insert_id;
		}

		// Bust caches.
		delete_transient( 'wprobo_documerge_forms_list' );
		delete_transient( 'wprobo_documerge_forms_count' );

		/**
		 * Fires after a form has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id The saved form ID.
		 */
		do_action( 'wprobo_documerge_form_saved', $id );

		return $id;
	}

	/**
	 * Delete a form by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The form ID to delete.
	 * @return bool|\WP_Error True on success, or WP_Error on failure.
	 */
	public function wprobo_documerge_delete_form( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( 0 === $id ) {
			return new \WP_Error(
				'wprobo_documerge_invalid_form_id',
				__( 'Invalid form ID.', 'wprobo-documerge-lite' )
			);
		}

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE id = %d", $id ) );

		if ( false === $deleted ) {
			return new \WP_Error(
				'wprobo_documerge_form_delete_failed',
				__( 'Failed to delete the form.', 'wprobo-documerge-lite' )
			);
		}

		// Bust caches.
		delete_transient( 'wprobo_documerge_forms_list' );
		delete_transient( 'wprobo_documerge_forms_count' );

		/**
		 * Fires after a form has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id The deleted form ID.
		 */
		do_action( 'wprobo_documerge_form_deleted', $id );

		return true;
	}

	/**
	 * Retrieve all forms as a lightweight list for select dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of objects with id and title properties.
	 */
	public function wprobo_documerge_get_all_forms_for_select() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, title FROM {$this->table_name} ORDER BY title ASC LIMIT %d",
				PHP_INT_MAX
			)
		);

		if ( null === $results ) {
			return array();
		}

		return $results;
	}

	/**
	 * Duplicate a form by its ID.
	 *
	 * Creates a copy of the specified form with "Copy of " prepended to the title.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The form ID to duplicate.
	 * @return int|\WP_Error The new form ID on success, or WP_Error on failure.
	 */
	public function wprobo_documerge_duplicate_form( $id ) {
		$form = $this->wprobo_documerge_get_form( $id );

		if ( null === $form ) {
			return new \WP_Error(
				'wprobo_documerge_form_not_found',
				__( 'Form not found.', 'wprobo-documerge-lite' )
			);
		}

		$data = (array) $form;
		unset( $data['id'] );

		/* translators: %s: original form title */
		$data['title'] = sprintf( __( 'Copy of %s', 'wprobo-documerge-lite' ), $form->title );

		$now                = current_time( 'mysql' );
		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		return $this->wprobo_documerge_save_form( $data );
	}

	/**
	 * Get the total number of forms, cached via transient.
	 *
	 * @since 1.0.0
	 *
	 * @return int The form count.
	 */
	public function wprobo_documerge_get_form_count() {
		$cached = get_transient( 'wprobo_documerge_forms_count' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} LIMIT %d", 1 ) );

		set_transient( 'wprobo_documerge_forms_count', $count, HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Get the number of submissions for a specific form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form ID.
	 * @return int The submission count.
	 */
	public function wprobo_documerge_get_submission_count_for_form( $form_id ) {
		global $wpdb;

		$form_id = absint( $form_id );

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->submissions_table_name} WHERE form_id = %d", $form_id ) );

		return $count;
	}
}
