<?php
/**
 * Forms List Table — extends WP_List_Table.
 *
 * Provides a native WordPress admin table with search, sortable
 * columns, per-page screen option, bulk actions, and row actions
 * for the Forms admin page.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.6.0
 */

namespace WPRobo\DocuMerge\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

// Load WP_List_Table if not available.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class WPRobo_DocuMerge_Forms_List_Table
 *
 * Renders a WP_List_Table for the forms listing with checkboxes,
 * sortable columns, search, bulk delete, and row actions.
 *
 * @since 1.6.0
 */
class WPRobo_DocuMerge_Forms_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'form',
				'plural'   => 'forms',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define table columns.
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'ID', 'wprobo-documerge-lite' ),
			'title'       => __( 'Name', 'wprobo-documerge-lite' ),
			'template'    => __( 'Template', 'wprobo-documerge-lite' ),
			'fields'      => __( 'Fields', 'wprobo-documerge-lite' ),
			'submissions' => __( 'Submissions', 'wprobo-documerge-lite' ),
			'views'       => __( 'Views', 'wprobo-documerge-lite' ),
			'starts'      => __( 'Starts', 'wprobo-documerge-lite' ),
			'completions' => __( 'Completions', 'wprobo-documerge-lite' ),
			'shortcode'   => __( 'Shortcode', 'wprobo-documerge-lite' ),
			'mode'        => __( 'Mode', 'wprobo-documerge-lite' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'    => array( 'id', true ), // Default sort DESC.
			'title' => array( 'title', false ),
		);
	}

	/**
	 * Checkbox column for bulk actions.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="form_ids[]" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * ID column.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_id( $item ) {
		return '<code>' . absint( $item->id ) . '</code>';
	}

	/**
	 * Title column with row actions.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_title( $item ) {
		$edit_url = admin_url( 'admin.php?page=wprobo-documerge-forms&action=edit&id=' . absint( $item->id ) );
		$title    = sprintf(
			'<a href="%s"><strong>%s</strong></a>',
			esc_url( $edit_url ),
			esc_html( $item->title )
		);

		// Row actions (show on hover like WordPress posts).
		$row_actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				__( 'Edit', 'wprobo-documerge-lite' )
			),
			'delete' => sprintf(
				'<a href="#" class="wdm-form-delete" data-id="%d" style="color:#dc2626;">%s</a>',
				absint( $item->id ),
				__( 'Delete', 'wprobo-documerge-lite' )
			),
		);

		return $title . $this->row_actions( $row_actions );
	}

	/**
	 * Template column.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_template( $item ) {
		if ( ! empty( $item->template_name ) ) {
			return sprintf(
				'<a href="%s" class="wdm-template-link">%s</a>',
				esc_url( admin_url( 'admin.php?page=wprobo-documerge-templates&edit=' . absint( $item->template_id ) ) ),
				esc_html( $item->template_name )
			);
		}
		return '<span class="wdm-text-muted">' . esc_html__( 'None', 'wprobo-documerge-lite' ) . '</span>';
	}

	/**
	 * Fields column.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_fields( $item ) {
		return absint( $item->field_count );
	}

	/**
	 * Submissions column with link to filtered submissions page.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_submissions( $item ) {
		$count = absint( $item->submission_count );
		if ( $count > 0 ) {
			return sprintf(
				'<a href="%s"><strong>%d</strong></a>',
				esc_url( admin_url( 'admin.php?page=wprobo-documerge-submissions&form_id=' . absint( $item->id ) ) ),
				$count
			);
		}
		return '0';
	}

	/**
	 * Views column (30-day analytics).
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_views( $item ) {
		return esc_html( number_format_i18n( isset( $item->stats_views ) ? $item->stats_views : 0 ) );
	}

	/**
	 * Starts column (30-day analytics).
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_starts( $item ) {
		return esc_html( number_format_i18n( isset( $item->stats_starts ) ? $item->stats_starts : 0 ) );
	}

	/**
	 * Completions column with abandonment percentage (30-day analytics).
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_completions( $item ) {
		$completions = isset( $item->stats_completions ) ? $item->stats_completions : 0;
		$abandonment = isset( $item->stats_abandonment ) ? $item->stats_abandonment : 0;

		$output = esc_html( number_format_i18n( $completions ) );

		if ( $abandonment > 0 ) {
			$output .= ' <span class="wdm-text-muted" title="' . esc_attr__( 'Abandonment rate', 'wprobo-documerge-lite' ) . '">('
				. esc_html( $abandonment ) . '%&nbsp;' . esc_html__( 'drop', 'wprobo-documerge-lite' ) . ')</span>';
		}

		return $output;
	}

	/**
	 * Shortcode column with copy button.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_shortcode( $item ) {
		$shortcode = '[wprobo_documerge_form id="' . absint( $item->id ) . '"]';

		return '<div class="wdm-shortcode-cell">' .
			'<code class="wdm-shortcode-code">' . esc_html( $shortcode ) . '</code>' .
			'<button type="button" class="wdm-copy-shortcode-btn wdm-copy-shortcode" data-shortcode="' . esc_attr( $shortcode ) . '" title="' . esc_attr__( 'Copy', 'wprobo-documerge-lite' ) . '">' .
				'<span class="dashicons dashicons-clipboard"></span>' .
			'</button>' .
		'</div>';
	}

	/**
	 * Mode column with badge.
	 *
	 * @since  1.6.0
	 * @param  object $item Row data.
	 * @return string
	 */
	public function column_mode( $item ) {
		$mode = ! empty( $item->mode ) ? sanitize_key( $item->mode ) : 'standalone';

		$labels = array(
			'standalone' => __( 'Standalone', 'wprobo-documerge-lite' ),
			'integrated' => __( 'Integrated', 'wprobo-documerge-lite' ),
			'wpforms'    => __( 'WPForms', 'wprobo-documerge-lite' ),
			'gravity'    => __( 'Gravity Forms', 'wprobo-documerge-lite' ),
			'cf7'        => __( 'Contact Form 7', 'wprobo-documerge-lite' ),
		);

		$label = isset( $labels[ $mode ] ) ? $labels[ $mode ] : ucfirst( $mode );

		return '<span class="wdm-badge wdm-badge-info">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Define bulk actions.
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'wprobo-documerge-lite' ),
		);
	}

	/**
	 * Message when no items found.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function no_items() {
		echo '<div class="wdm-empty-state" style="padding:40px 20px;">';
		echo '<span class="dashicons dashicons-feedback"></span>';
		echo '<h3>' . esc_html__( 'No forms yet', 'wprobo-documerge-lite' ) . '</h3>';
		echo '<p>' . esc_html__( 'Create your first form to start collecting submissions.', 'wprobo-documerge-lite' ) . '</p>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=wprobo-documerge-forms&action=new' ) ) . '" class="wdm-btn wdm-btn-primary">';
		echo '<span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Create Form', 'wprobo-documerge-lite' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Prepare table items — query database with search, sort, pagination.
	 *
	 * Fetches forms with joined template names, computes field counts
	 * from stored JSON, fetches submission counts, and loads 30-day
	 * analytics stats for each form.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = $this->get_items_per_page( 'wprobo_documerge_forms_per_page', 20 );

		$forms_table     = $wpdb->prefix . 'wprdm_forms';
		$templates_table = $wpdb->prefix . 'wprdm_templates';

		// Search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin list-table filter read; cap-checked, sanitized, idempotent (no state change).
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		// Search fragment — prepared with %s placeholder, value bound below.
		$where_sql    = '';
		$where_values = array();
		if ( ! empty( $search ) ) {
			$where_sql      = ' AND f.title LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Count total.
		if ( ! empty( $where_values ) ) {
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i f WHERE 1=1' . $where_sql, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_sql is a hardcoded fragment containing only a %s placeholder.
					array_merge( array( $forms_table ), $where_values )
				)
			);
		} else {
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i f WHERE 1=1', $forms_table )
			);
		}

		// ORDER BY column — strict whitelist. Value is ONLY ever one of these
		// literals, never user input after this point.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin list-table filter read; cap-checked, sanitized, idempotent (no state change).
		$orderby_raw = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$orderby     = in_array( $orderby_raw, array( 'id', 'title' ), true ) ? $orderby_raw : 'id';

		// ORDER direction — strict whitelist. Value is ONLY ever 'ASC' or 'DESC'.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin list-table filter read; cap-checked, sanitized, idempotent (no state change).
		$order_raw = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '';
		$order     = ( 'asc' === strtolower( $order_raw ) ) ? 'ASC' : 'DESC';

		// Pagination.
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Fetch forms with template name. Table/column identifiers use %i
		// (WP 6.2+), $order is safe to interpolate because of the whitelist.
		$select_sql = 'SELECT f.*, t.name AS template_name'
			. ' FROM %i f'
			. ' LEFT JOIN %i t ON f.template_id = t.id'
			. ' WHERE 1=1' . $where_sql
			. ' ORDER BY f.%i ' . $order
			. ' LIMIT %d OFFSET %d';

		$query_values = array_merge(
			array( $forms_table, $templates_table ),
			$where_values,
			array( $orderby, $per_page, $offset )
		);

		$this->items = $wpdb->get_results(
			$wpdb->prepare( $select_sql, $query_values ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $select_sql is built from hardcoded fragments plus a whitelisted $order keyword.
		);

		if ( null === $this->items ) {
			$this->items = array();
		}

		// Add derived data: field count, submission count.
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		foreach ( $this->items as &$item ) {
			// Field count from JSON.
			$fields_arr        = json_decode( isset( $item->fields ) ? $item->fields : '[]', true );
			$item->field_count = is_array( $fields_arr ) ? count( $fields_arr ) : 0;

			// Submission count.
			$item->submission_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d",
					$item->id
				)
			);

			// Analytics stats — Pro only.
			$item->stats_views       = 0;
			$item->stats_starts      = 0;
			$item->stats_completions = 0;
			$item->stats_abandonment = 0;
		}
		unset( $item );

		// Set column headers.
		$this->_column_headers = array(
			$this->get_columns(),
			array(), // Hidden columns.
			$this->get_sortable_columns(),
		);

		// Set pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
