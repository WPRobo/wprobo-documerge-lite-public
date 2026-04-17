<?php
/**
 * Submissions admin page controller.
 *
 * Handles the Submissions list page, submission detail view,
 * deletion, and CSV export via AJAX.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

use WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Builder;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Submissions_Page
 *
 * Renders the Submissions admin page listing all form submissions,
 * displays submission detail panels, handles bulk deletion with
 * file cleanup, and exports filtered submissions as CSV.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Submissions_Page {

	/**
	 * Register WordPress hooks for submission AJAX handlers.
	 *
	 * Registers admin-only AJAX actions for listing, viewing,
	 * deleting, and exporting submissions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'wp_ajax_wprobo_documerge_get_submissions', array( $this, 'wprobo_documerge_ajax_get_submissions' ) );
		add_action( 'wp_ajax_wprobo_documerge_get_submission_detail', array( $this, 'wprobo_documerge_ajax_get_submission_detail' ) );
		add_action( 'wp_ajax_wprobo_documerge_delete_submission', array( $this, 'wprobo_documerge_ajax_delete_submission' ) );
		add_action( 'wp_ajax_wprobo_documerge_export_submissions', array( $this, 'wprobo_documerge_ajax_export_submissions' ) );
		add_action( 'wp_ajax_wprobo_documerge_save_submission_note', array( $this, 'wprobo_documerge_ajax_save_note' ) );
	}

	/**
	 * The list table instance.
	 *
	 * @since 1.2.0
	 * @var   WPRobo_DocuMerge_Submissions_List_Table|null
	 */
	private $wprobo_documerge_list_table = null;

	/**
	 * Register screen options (called early via load-{page} hook).
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function wprobo_documerge_screen_options() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Submissions per page', 'wprobo-documerge-lite' ),
				'default' => 10,
				'option'  => 'wprobo_documerge_submissions_per_page',
			)
		);

		$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Submissions_List_Table();
	}

	/**
	 * Render the Submissions admin page.
	 *
	 * Uses WP_List_Table for a native WordPress admin experience
	 * with search, sortable columns, pagination, and bulk actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		// Single submission view.
		$view_id = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $view_id > 0 ) {
			$this->wprobo_documerge_render_single_submission( $view_id );
			return;
		}

		// Process bulk actions.
		$this->wprobo_documerge_process_bulk_action();

		// Prepare list table if not already done via screen options.
		if ( null === $this->wprobo_documerge_list_table ) {
			$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Submissions_List_Table();
		}

		$this->wprobo_documerge_list_table->prepare_items();

		// Page header variables.
		$page_title    = __( 'Submissions', 'wprobo-documerge-lite' );
		$page_subtitle = __( 'All form submissions and generated documents', 'wprobo-documerge-lite' );

		?>
		<div class="wdm-admin-wrap">

			<?php
			$primary_action = array();
			include WPROBO_DOCUMERGE_PATH . 'templates/admin/partials/page-header.php';
			?>

			<div class="wdm-list-table-wrap">
				<form method="get">
					<input type="hidden" name="page" value="wprobo-documerge-submissions" />

					<?php
					$this->wprobo_documerge_list_table->search_box( __( 'Search submissions', 'wprobo-documerge-lite' ), 'wdm-submission-search' );
					$this->wprobo_documerge_list_table->display();
					?>
				</form>
			</div>

		</div>

		<?php
		// Keep the detail panel for viewing individual submissions.
		include WPROBO_DOCUMERGE_PATH . 'templates/admin/submissions/detail-panel.php';
		?>
		<div class="wdm-overlay" id="wdm-overlay"></div>
		<?php
	}

	/**
	 * Process bulk delete action from the list table.
	 *
	 * @since  1.2.0
	 * @return void
	 */
	private function wprobo_documerge_process_bulk_action() {
		if ( null === $this->wprobo_documerge_list_table ) {
			$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Submissions_List_Table();
		}

		if ( 'delete' !== $this->wprobo_documerge_list_table->current_action() ) {
			return;
		}

		// Verify nonce (WP_List_Table uses _wpnonce with bulk-{plural}).
		check_admin_referer( 'bulk-submissions' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$ids = isset( $_GET['submission_ids'] ) ? array_map( 'absint', wp_unslash( (array) $_GET['submission_ids'] ) ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wprdm_submissions';

		// Initialize WP Filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		\WP_Filesystem();
		global $wp_filesystem;

		foreach ( $ids as $id ) {
			$submission = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, doc_path_docx, doc_path_pdf FROM {$table} WHERE id = %d",
					$id
				)
			);

			if ( null === $submission ) {
				continue;
			}

			if ( ! empty( $submission->doc_path_docx ) && $wp_filesystem->exists( $submission->doc_path_docx ) ) {
				$wp_filesystem->delete( $submission->doc_path_docx );
			}
			if ( ! empty( $submission->doc_path_pdf ) && $wp_filesystem->exists( $submission->doc_path_pdf ) ) {
				$wp_filesystem->delete( $submission->doc_path_pdf );
			}

			$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		}

		// Redirect to remove query args.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wprobo-documerge-submissions',
					'deleted' => count( $ids ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render a single submission full-page view.
	 *
	 * Fetches the submission with joined form and template data,
	 * decodes the stored form_data JSON, and includes the single
	 * submission template.
	 *
	 * @since  1.4.0
	 * @param  int $submission_id The submission ID to display.
	 * @return void
	 */
	private function wprobo_documerge_render_single_submission( $submission_id ) {
		global $wpdb;

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, f.title AS form_title, t.name AS template_name
                 FROM {$wpdb->prefix}wprdm_submissions s
                 LEFT JOIN {$wpdb->prefix}wprdm_forms f ON s.form_id = f.id
                 LEFT JOIN {$wpdb->prefix}wprdm_templates t ON s.template_id = t.id
                 WHERE s.id = %d",
				$submission_id
			)
		);

		if ( ! $submission ) {
			echo '<div class="wdm-admin-wrap"><div class="wdm-notice wdm-notice-error"><span class="wdm-notice-icon dashicons dashicons-warning"></span><span class="wdm-notice-text">' . esc_html__( 'Submission not found.', 'wprobo-documerge-lite' ) . '</span></div></div>';
			return;
		}

		$form_data = json_decode( $submission->form_data, true );
		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/submissions/single.php';
	}

	/**
	 * AJAX handler -- save an admin note on a submission.
	 *
	 * Updates the admin_notes column for the given submission ID.
	 *
	 * @since  1.4.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_save_note() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid submission ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'wprdm_submissions',
			array(
				'admin_notes' => $note,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Note saved.', 'wprobo-documerge-lite' ) ) );
	}

	/**
	 * AJAX handler -- retrieve paginated submissions with filters.
	 *
	 * Accepts filter parameters (form_id, status, date range) and
	 * pagination settings, builds a dynamic WHERE clause, and returns
	 * the matching submissions with join data for form title and
	 * template name.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_get_submissions() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;

		// Sanitize filter parameters.
		$form_id   = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
		$page      = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page  = 20;

		if ( $page < 1 ) {
			$page = 1;
		}

		$submissions_table = $wpdb->prefix . 'wprdm_submissions';
		$forms_table       = $wpdb->prefix . 'wprdm_forms';
		$templates_table   = $wpdb->prefix . 'wprdm_templates';

		// Build dynamic WHERE clauses.
		$where_clauses = array();
		$where_values  = array();

		if ( $form_id > 0 ) {
			$where_clauses[] = 's.form_id = %d';
			$where_values[]  = $form_id;
		}

		if ( ! empty( $status ) ) {
			$where_clauses[] = 's.status = %s';
			$where_values[]  = $status;
		}

		if ( ! empty( $date_from ) ) {
			$where_clauses[] = 's.created_at >= %s';
			$where_values[]  = $date_from . ' 00:00:00';
		}

		if ( ! empty( $date_to ) ) {
			$where_clauses[] = 's.created_at <= %s';
			$where_values[]  = $date_to . ' 23:59:59';
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' AND ' . implode( ' AND ', $where_clauses );
		}

		// Count total matching records.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count_query = "SELECT COUNT(*) FROM {$submissions_table} s WHERE 1=1{$where_sql}";

		if ( ! empty( $where_values ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $where_values ) );
		} else {
			$total = (int) $wpdb->get_var( $count_query );
		}

		// Calculate pagination.
		$pages  = (int) ceil( $total / $per_page );
		$offset = ( $page - 1 ) * $per_page;

		// Fetch submissions with joined form and template data.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$select_query = "SELECT s.*, f.title AS form_title, t.name AS template_name
            FROM {$submissions_table} s
            LEFT JOIN {$forms_table} f ON s.form_id = f.id
            LEFT JOIN {$templates_table} t ON s.template_id = t.id
            WHERE 1=1{$where_sql}
            ORDER BY s.created_at DESC
            LIMIT %d OFFSET %d";

		$query_values = array_merge( $where_values, array( $per_page, $offset ) );
		$submissions = $wpdb->get_results( $wpdb->prepare( $select_query, $query_values ) );

		if ( null === $submissions ) {
			$submissions = array();
		}

		// Add derived fields the JS expects.
		$status_labels = array(
			'completed'  => __( 'Completed', 'wprobo-documerge-lite' ),
			'processing' => __( 'Processing', 'wprobo-documerge-lite' ),
			'error'      => __( 'Error', 'wprobo-documerge-lite' ),
		);

		foreach ( $submissions as $sub ) {
			$sub->date_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $sub->created_at ) );
			$sub->status_label   = isset( $status_labels[ $sub->status ] ) ? $status_labels[ $sub->status ] : ucfirst( $sub->status );
		}

		wp_send_json_success(
			array(
				'submissions'  => $submissions,
				'total'        => $total,
				'pages'        => $pages,
				'current_page' => $page,
			)
		);
	}

	/**
	 * AJAX handler -- retrieve a single submission's detail HTML.
	 *
	 * Fetches the submission along with its associated form and template
	 * data, decodes the stored form_data JSON, and renders a detail
	 * panel via an included template file.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_get_submission_detail() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( 0 === $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid submission ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;

		$submissions_table = $wpdb->prefix . 'wprdm_submissions';
		$forms_table       = $wpdb->prefix . 'wprdm_forms';
		$templates_table   = $wpdb->prefix . 'wprdm_templates';

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, f.title AS form_title, t.name AS template_name
                FROM {$submissions_table} s
                LEFT JOIN {$forms_table} f ON s.form_id = f.id
                LEFT JOIN {$templates_table} t ON s.template_id = t.id
                WHERE s.id = %d",
				$id
			)
		);

		if ( null === $submission ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Decode the stored form data JSON.
		$form_data = json_decode( $submission->form_data, true );

		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}

		// Render detail content HTML (injected into #wdm-detail-body).
		ob_start();
		include WPROBO_DOCUMERGE_PATH . 'templates/admin/submissions/detail-content.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX handler -- delete one or more submissions.
	 *
	 * Accepts an array of submission IDs, deletes associated document
	 * files (DOCX and PDF) from the filesystem, then removes the
	 * database rows.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_delete_submission() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$raw_ids = isset( $_POST['ids'] ) ? wp_unslash( (array) $_POST['ids'] ) : array();
		$ids     = array_map( 'absint', $raw_ids );
		$ids     = array_filter( $ids );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid submission IDs provided.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;

		$submissions_table = $wpdb->prefix . 'wprdm_submissions';
		$deleted_count     = 0;

		// Initialize WP Filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		foreach ( $ids as $id ) {
			// Retrieve submission to find associated file paths.
			$submission = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, doc_path_docx, doc_path_pdf FROM {$submissions_table} WHERE id = %d",
					$id
				)
			);

			if ( null === $submission ) {
				continue;
			}

			// Delete DOCX file if it exists.
			if ( ! empty( $submission->doc_path_docx ) && $wp_filesystem->exists( $submission->doc_path_docx ) ) {
				$wp_filesystem->delete( $submission->doc_path_docx );
			}

			// Delete PDF file if it exists.
			if ( ! empty( $submission->doc_path_pdf ) && $wp_filesystem->exists( $submission->doc_path_pdf ) ) {
				$wp_filesystem->delete( $submission->doc_path_pdf );
			}

			// Delete the database row.
			$wpdb->delete(
				$submissions_table,
				array( 'id' => $id ),
				array( '%d' )
			);

			++$deleted_count;
		}

		wp_send_json_success( array( 'deleted' => $deleted_count ) );
	}

	/**
	 * AJAX handler -- export filtered submissions as CSV.
	 *
	 * Applies the same filter parameters as the list endpoint (form_id,
	 * status, date range) but without pagination. Outputs a CSV file
	 * directly to the browser with a maximum of 5000 rows.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_export_submissions() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		global $wpdb;

		// Sanitize filter parameters.
		$form_id   = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';

		$submissions_table = $wpdb->prefix . 'wprdm_submissions';
		$forms_table       = $wpdb->prefix . 'wprdm_forms';
		$templates_table   = $wpdb->prefix . 'wprdm_templates';

		// Build dynamic WHERE clauses.
		$where_clauses = array();
		$where_values  = array();

		if ( $form_id > 0 ) {
			$where_clauses[] = 's.form_id = %d';
			$where_values[]  = $form_id;
		}

		if ( ! empty( $status ) ) {
			$where_clauses[] = 's.status = %s';
			$where_values[]  = $status;
		}

		if ( ! empty( $date_from ) ) {
			$where_clauses[] = 's.created_at >= %s';
			$where_values[]  = $date_from . ' 00:00:00';
		}

		if ( ! empty( $date_to ) ) {
			$where_clauses[] = 's.created_at <= %s';
			$where_values[]  = $date_to . ' 23:59:59';
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' AND ' . implode( ' AND ', $where_clauses );
		}

		// Fetch submissions without pagination (capped at 5000).
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$select_query = "SELECT s.*, f.title AS form_title, t.name AS template_name
            FROM {$submissions_table} s
            LEFT JOIN {$forms_table} f ON s.form_id = f.id
            LEFT JOIN {$templates_table} t ON s.template_id = t.id
            WHERE 1=1{$where_sql}
            ORDER BY s.created_at DESC
            LIMIT %d";

		$query_values = array_merge( $where_values, array( 5000 ) );
		$submissions = $wpdb->get_results( $wpdb->prepare( $select_query, $query_values ) );

		if ( null === $submissions ) {
			$submissions = array();
		}

		// Output CSV headers.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="wprobo-documerge-submissions-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		// Header row.
		fputcsv(
			$output,
			array(
				__( 'ID', 'wprobo-documerge-lite' ),
				__( 'Date', 'wprobo-documerge-lite' ),
				__( 'Form', 'wprobo-documerge-lite' ),
				__( 'Email', 'wprobo-documerge-lite' ),
				__( 'Status', 'wprobo-documerge-lite' ),
				__( 'Delivery Status', 'wprobo-documerge-lite' ),
			)
		);

		// Data rows.
		foreach ( $submissions as $row ) {
			fputcsv(
				$output,
				array(
					$row->id,
					$row->created_at,
					$row->form_title,
					isset( $row->submitter_email ) ? $row->submitter_email : '',
					$row->status,
					isset( $row->delivery_status ) ? $row->delivery_status : '',
				)
			);
		}

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}
}
