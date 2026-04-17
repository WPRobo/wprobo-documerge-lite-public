<?php
/**
 * Dashboard admin page class.
 *
 * Renders the main DocuMerge dashboard with stats overview
 * and recent submissions table.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Dashboard_Page
 *
 * Gathers dashboard statistics and recent submissions,
 * then loads the dashboard template for rendering.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Dashboard_Page {

	/**
	 * Render the dashboard page.
	 *
	 * Checks user capability, gathers stats and recent submissions,
	 * then includes the dashboard template file.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats              = $this->wprobo_documerge_get_dashboard_stats();
		$recent_submissions = $this->wprobo_documerge_get_recent_submissions();
		$chart_data         = $this->wprobo_documerge_get_chart_data();

		// Pass chart data + translated strings to the dashboard-charts script.
		wp_localize_script(
			'wprobo-documerge-dashboard-charts',
			'wprobo_documerge_dashboard',
			array(
				'daily'    => isset( $chart_data['daily'] ) ? $chart_data['daily'] : array(),
				'statuses' => isset( $chart_data['statuses'] ) ? $chart_data['statuses'] : array(),
				'i18n'     => array(
					'submissions'     => __( 'Submissions', 'wprobo-documerge-lite' ),
					'no_submissions'  => __( 'No submissions yet', 'wprobo-documerge-lite' ),
				),
			)
		);

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/dashboard/main.php';
	}

	/**
	 * Retrieve dashboard statistics.
	 *
	 * Returns an associative array containing template count, form count,
	 * monthly completed submissions, monthly revenue, Stripe status,
	 * and the current month label. Template and form counts are cached
	 * via WordPress transients with a one-hour TTL.
	 *
	 * @since  1.0.0
	 * @return array{
	 *     templates:          int,
	 *     forms:              int,
	 *     submissions:        int,
	 *     revenue:            float,
	 *     revenue_formatted:  string,
	 *     stripe_active:      bool,
	 *     month_label:        string,
	 * }
	 */
	public function wprobo_documerge_get_dashboard_stats() {
		global $wpdb;

		// --- Templates count (cached) ---
		$wprobo_documerge_templates = get_transient( 'wprobo_documerge_templates_count' );

		if ( false === $wprobo_documerge_templates ) {
			$wprobo_documerge_templates = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_templates"
			);
			set_transient( 'wprobo_documerge_templates_count', $wprobo_documerge_templates, HOUR_IN_SECONDS );
		}

		// --- Forms count (cached) ---
		$wprobo_documerge_forms = get_transient( 'wprobo_documerge_forms_count' );

		if ( false === $wprobo_documerge_forms ) {
			$wprobo_documerge_forms = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_forms"
			);
			set_transient( 'wprobo_documerge_forms_count', $wprobo_documerge_forms, HOUR_IN_SECONDS );
		}

		// First day of the current month in UTC.
		$wprobo_documerge_month_start = gmdate( 'Y-m-01 00:00:00' );

		// --- Completed submissions this month ---
		$wprobo_documerge_submissions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_submissions WHERE status = %s AND created_at >= %s",
				'completed',
				$wprobo_documerge_month_start
			)
		);

		// --- Revenue (disabled in Lite) ---
		$wprobo_documerge_revenue = 0.00;

		// --- Stripe active check (disabled in Lite) ---
		$wprobo_documerge_stripe_active = false;

		$stats = array(
			'templates'         => (int) $wprobo_documerge_templates,
			'forms'             => (int) $wprobo_documerge_forms,
			'submissions'       => $wprobo_documerge_submissions,
			'revenue'           => $wprobo_documerge_revenue,
			'revenue_formatted' => number_format( $wprobo_documerge_revenue, 2 ),
			'stripe_active'     => $wprobo_documerge_stripe_active,
			'month_label'       => gmdate( 'F Y' ),
		);

		/**
		 * Filters the dashboard statistics array.
		 *
		 * Allows adding custom stat cards or modifying dashboard values.
		 * Useful for add-ons that track additional metrics.
		 *
		 * @since 1.0.0
		 *
		 * @param array $stats The stats array with keys: templates, forms, submissions, etc.
		 */
		return apply_filters( 'wprobo_documerge_admin_dashboard_stats', $stats );
	}

	/**
	 * Retrieve recent submissions with form and template names.
	 *
	 * Performs a LEFT JOIN across submissions, forms, and templates
	 * tables to return the most recent submissions ordered by
	 * creation date descending.
	 *
	 * @since  1.0.0
	 * @param  int $wprobo_documerge_limit Maximum number of submissions to return.
	 * @return array<int, object> Array of submission row objects.
	 */
	public function wprobo_documerge_get_recent_submissions( $wprobo_documerge_limit = 10 ) {
		global $wpdb;

		$wprobo_documerge_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.id, s.submitter_email, s.status, s.doc_path_pdf,
                        s.doc_path_docx, s.created_at,
                        f.title AS form_title,
                        t.name AS template_name
                 FROM {$wpdb->prefix}wprdm_submissions s
                 LEFT JOIN {$wpdb->prefix}wprdm_forms f ON s.form_id = f.id
                 LEFT JOIN {$wpdb->prefix}wprdm_templates t ON s.template_id = t.id
                 ORDER BY s.created_at DESC
                 LIMIT %d",
				$wprobo_documerge_limit
			)
		);

		return is_array( $wprobo_documerge_results ) ? $wprobo_documerge_results : array();
	}

	/**
	 * Retrieve chart data for the dashboard.
	 *
	 * Returns daily submission counts for the last 7 days,
	 * status breakdown, and top 5 forms by submission count.
	 *
	 * @since  1.1.0
	 * @return array{
	 *     daily:    array,
	 *     statuses: array,
	 *     forms:    array,
	 * }
	 */
	public function wprobo_documerge_get_chart_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'wprdm_submissions';

		// Get all 7 days in one query.
		$seven_days_ago = gmdate( 'Y-m-d', strtotime( '-6 days' ) );
		$daily_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as day, COUNT(*) as count FROM {$table} WHERE created_at >= %s GROUP BY DATE(created_at)",
				$seven_days_ago . ' 00:00:00'
			),
			ARRAY_A
		);
		$count_map    = array();
		if ( is_array( $daily_counts ) ) {
			foreach ( $daily_counts as $row ) {
				$count_map[ $row['day'] ] = (int) $row['count'];
			}
		}

		$daily_data = array();
		for ( $i = 6; $i >= 0; $i-- ) {
			$date         = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$label        = gmdate( 'M j', strtotime( "-{$i} days" ) );
			$daily_data[] = array(
				'label' => $label,
				'count' => isset( $count_map[ $date ] ) ? $count_map[ $date ] : 0,
			);
		}

		// Status breakdown (pie chart).
		$statuses = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			ARRAY_A
		);

		return array(
			'daily'    => $daily_data,
			'statuses' => $statuses ? $statuses : array(),
		);
	}
}
