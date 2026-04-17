<?php
/**
 * Asset management class.
 *
 * Handles enqueuing of all admin and frontend CSS/JS assets.
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
 * Class WPRobo_DocuMerge_Assets
 *
 * Registers and enqueues all plugin scripts and styles
 * for both admin and frontend contexts.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Assets {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Assets|null
	 */
	private static $wprobo_documerge_instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return WPRobo_DocuMerge_Assets
	 */
	public static function get_instance() {
		if ( null === self::$wprobo_documerge_instance ) {
			self::$wprobo_documerge_instance = new self();
		}
		return self::$wprobo_documerge_instance;
	}

	/**
	 * Constructor — private for singleton.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Intentionally empty — hooks registered via wprobo_documerge_init_hooks().
	}

	/**
	 * Register WordPress hooks for asset enqueuing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'wprobo_documerge_enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wprobo_documerge_enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * Only loads assets on plugin admin pages to avoid
	 * unnecessary overhead on other WordPress screens.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public function wprobo_documerge_enqueue_admin_assets( $hook ) {

		// Only load on plugin pages.
		if ( strpos( $hook, 'wprobo-documerge' ) === false ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'wprobo-documerge-admin',
			WPROBO_DOCUMERGE_URL . 'assets/css/admin/main.min.css',
			array(),
			WPROBO_DOCUMERGE_VERSION
		);

		// JS — always in footer.
		wp_enqueue_script(
			'wprobo-documerge-admin',
			WPROBO_DOCUMERGE_URL . 'assets/js/admin/main.min.js',
			array( 'jquery' ),
			WPROBO_DOCUMERGE_VERSION,
			true
		);

		// Localize immediately after enqueue.
		wp_localize_script(
			'wprobo-documerge-admin',
			'wprobo_documerge_vars',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wprobo_documerge_admin' ),
				'settings_nonce' => wp_create_nonce( 'wprobo_documerge_settings' ),
				'plugin_url'     => WPROBO_DOCUMERGE_URL,
				'admin_url'      => admin_url( 'admin.php?page=wprobo-documerge-submissions' ),
				'i18n'           => array(
					'error'                      => __( 'An error occurred. Please try again.', 'wprobo-documerge-lite' ),
					'network_error'              => __( 'Network error. Please check your connection.', 'wprobo-documerge-lite' ),
					'confirm_delete'             => __( 'Are you sure you want to delete this?', 'wprobo-documerge-lite' ),
					'saving'                     => __( 'Saving...', 'wprobo-documerge-lite' ),
					'saved'                      => __( 'Saved!', 'wprobo-documerge-lite' ),
					'copied'                     => __( 'Copied to clipboard!', 'wprobo-documerge-lite' ),
					'no_submissions'             => __( 'No submissions found.', 'wprobo-documerge-lite' ),
					'confirm_bulk'               => __( 'Delete selected submissions? This cannot be undone.', 'wprobo-documerge-lite' ),
					'confirm_rerun_wizard'       => __( 'Re-run the setup wizard? You will be redirected to the wizard screen.', 'wprobo-documerge-lite' ),
					'export_none'                => __( 'Please select at least one data type to export.', 'wprobo-documerge-lite' ),
					'export_success'             => __( 'Export downloaded successfully.', 'wprobo-documerge-lite' ),
					'import_invalid_type'        => __( 'Please select a valid JSON file.', 'wprobo-documerge-lite' ),
					'import_too_large'           => __( 'File is too large. Maximum size is 50 MB.', 'wprobo-documerge-lite' ),
					'import_invalid_json'        => __( 'Could not parse JSON file.', 'wprobo-documerge-lite' ),
					'import_wrong_plugin'        => __( 'This file is not a valid DocuMerge export.', 'wprobo-documerge-lite' ),
					'import_no_data'             => __( 'Export file contains no data.', 'wprobo-documerge-lite' ),
					'import_none'                => __( 'Please select at least one data type to import.', 'wprobo-documerge-lite' ),
					'import_replace_confirm'     => __( 'Replace mode will DELETE all existing data for the selected types before importing. Continue?', 'wprobo-documerge-lite' ),
					'import_version'             => __( 'Version', 'wprobo-documerge-lite' ),
					'import_date'                => __( 'Exported', 'wprobo-documerge-lite' ),
					'import_site'                => __( 'Site', 'wprobo-documerge-lite' ),
					'templates'                  => __( 'Templates', 'wprobo-documerge-lite' ),
					'forms'                      => __( 'Forms', 'wprobo-documerge-lite' ),
					'submissions'                => __( 'Submissions', 'wprobo-documerge-lite' ),
					'settings_label'             => __( 'Settings', 'wprobo-documerge-lite' ),
					'analytics'                  => __( 'Analytics', 'wprobo-documerge-lite' ),
					'imported'                   => __( 'imported', 'wprobo-documerge-lite' ),
					'settings_saved'             => __( 'Settings saved.', 'wprobo-documerge-lite' ),

					// Field type labels.
					'field_type_text'            => __( 'Text', 'wprobo-documerge-lite' ),
					'field_type_textarea'        => __( 'Textarea', 'wprobo-documerge-lite' ),
					'field_type_email'           => __( 'Email', 'wprobo-documerge-lite' ),
					'field_type_phone'           => __( 'Phone', 'wprobo-documerge-lite' ),
					'field_type_number'          => __( 'Number', 'wprobo-documerge-lite' ),
					'field_type_date'            => __( 'Date', 'wprobo-documerge-lite' ),
					'field_type_dropdown'        => __( 'Dropdown', 'wprobo-documerge-lite' ),
					'field_type_radio'           => __( 'Radio', 'wprobo-documerge-lite' ),
					'field_type_checkbox'        => __( 'Checkbox', 'wprobo-documerge-lite' ),
					'field_type_file_upload'     => __( 'File Upload', 'wprobo-documerge-lite' ),
					'field_type_address'         => __( 'Address', 'wprobo-documerge-lite' ),
					'field_type_name'            => __( 'Name', 'wprobo-documerge-lite' ),
					'field_type_hidden'          => __( 'Hidden', 'wprobo-documerge-lite' ),
					'field_type_html'            => __( 'HTML Block', 'wprobo-documerge-lite' ),
					'field_type_section_divider' => __( 'Section Divider', 'wprobo-documerge-lite' ),
					'field_type_url'             => __( 'Website', 'wprobo-documerge-lite' ),
					'field_type_ip_address'      => __( 'IP Address', 'wprobo-documerge-lite' ),
					'field_type_tracking'        => __( 'Tracking', 'wprobo-documerge-lite' ),
					'field_type_signature'       => __( 'Signature', 'wprobo-documerge-lite' ),
					'field_type_payment'         => __( 'Payment', 'wprobo-documerge-lite' ),
					'field_type_captcha'         => __( 'CAPTCHA', 'wprobo-documerge-lite' ),
					'field_type_password'        => __( 'Password', 'wprobo-documerge-lite' ),
					'field_type_rating'          => __( 'Rating', 'wprobo-documerge-lite' ),
					'field_type_repeater'        => __( 'Repeater', 'wprobo-documerge-lite' ),

					// Form builder labels.
					'label_admin_reference'      => __( 'Label (admin reference)', 'wprobo-documerge-lite' ),
					'admin_reference_desc'       => __( 'For admin reference only. IP is captured automatically.', 'wprobo-documerge-lite' ),
					'merge_tag'                  => __( 'Merge Tag', 'wprobo-documerge-lite' ),
					'track_utm'                  => __( 'Track UTM Parameters', 'wprobo-documerge-lite' ),
					'track_utm_desc'             => __( 'Captures utm_source, utm_medium, utm_campaign, utm_content, utm_term', 'wprobo-documerge-lite' ),
					'track_referrer'             => __( 'Track Referrer URL', 'wprobo-documerge-lite' ),
					'track_landing'              => __( 'Track Landing Page URL', 'wprobo-documerge-lite' ),
					'default_value'              => __( 'Default Value', 'wprobo-documerge-lite' ),
					'dynamic_value'              => __( 'Dynamic Value', 'wprobo-documerge-lite' ),
					'label'                      => __( 'Label', 'wprobo-documerge-lite' ),
					'label_position'             => __( 'Label Position', 'wprobo-documerge-lite' ),
					'label_above_field'          => __( 'Above field', 'wprobo-documerge-lite' ),
					'label_below_field'          => __( 'Below field', 'wprobo-documerge-lite' ),
					'label_hidden_sr'            => __( 'Hidden (screen reader only)', 'wprobo-documerge-lite' ),
					'placeholder'                => __( 'Placeholder', 'wprobo-documerge-lite' ),
					'help_text'                  => __( 'Help Text', 'wprobo-documerge-lite' ),
					'required'                   => __( 'Required', 'wprobo-documerge-lite' ),
					'width'                      => __( 'Width', 'wprobo-documerge-lite' ),
					'width_full'                 => __( 'Full', 'wprobo-documerge-lite' ),
					'width_half'                 => __( 'Half', 'wprobo-documerge-lite' ),
					'width_third'                => __( 'Third', 'wprobo-documerge-lite' ),
					'custom_error_message'       => __( 'Custom Error Message', 'wprobo-documerge-lite' ),
					'leave_blank_default'        => __( 'Leave blank for default', 'wprobo-documerge-lite' ),
					'min_value'                  => __( 'Min Value', 'wprobo-documerge-lite' ),
					'max_value'                  => __( 'Max Value', 'wprobo-documerge-lite' ),
					'step'                       => __( 'Step', 'wprobo-documerge-lite' ),
					'min_length'                 => __( 'Min Length', 'wprobo-documerge-lite' ),
					'max_length'                 => __( 'Max Length', 'wprobo-documerge-lite' ),
					'css_class'                  => __( 'CSS Class', 'wprobo-documerge-lite' ),
					'css_class_desc'             => __( 'Add custom CSS class(es). Separate multiple with spaces.', 'wprobo-documerge-lite' ),
					'css_id'                     => __( 'CSS ID', 'wprobo-documerge-lite' ),
					'css_id_desc'                => __( 'Optional unique HTML ID for this field.', 'wprobo-documerge-lite' ),
					'untitled_form'              => __( 'Untitled Form', 'wprobo-documerge-lite' ),
					'enter_form_title'           => __( 'Please enter a form title.', 'wprobo-documerge-lite' ),
					'untitled'                   => __( 'Untitled', 'wprobo-documerge-lite' ),
					'documerge_form'             => __( 'DocuMerge Form', 'wprobo-documerge-lite' ),
					'general'                    => __( 'General', 'wprobo-documerge-lite' ),
					'validation'                 => __( 'Validation', 'wprobo-documerge-lite' ),
					'appearance'                 => __( 'Appearance', 'wprobo-documerge-lite' ),
					'not_shown_frontend'         => __( 'For admin reference only. Not shown on the frontend.', 'wprobo-documerge-lite' ),
					'date_format'                => __( 'Date Format', 'wprobo-documerge-lite' ),
					'date_format_desc'           => __( 'How the date appears in the form and document.', 'wprobo-documerge-lite' ),
					'options_label'              => __( 'Options', 'wprobo-documerge-lite' ),
					'add_option'                 => __( '+ Add Option', 'wprobo-documerge-lite' ),
					'enable_search'              => __( 'Enable search (users can type to filter options)', 'wprobo-documerge-lite' ),
					'enable_search_desc'         => __( 'Recommended for dropdowns with many options.', 'wprobo-documerge-lite' ),
					'allowed_file_types'         => __( 'Allowed File Types', 'wprobo-documerge-lite' ),
					'allowed_file_types_desc'    => __( 'Comma-separated file extensions', 'wprobo-documerge-lite' ),
					'max_file_size'              => __( 'Max File Size (MB)', 'wprobo-documerge-lite' ),
					'allow_multiple_files'       => __( 'Allow multiple files', 'wprobo-documerge-lite' ),
					'show_address_line2'         => __( 'Show Address Line 2', 'wprobo-documerge-lite' ),
					'show_country_field'         => __( 'Show Country field', 'wprobo-documerge-lite' ),
					'default_country'            => __( 'Default Country', 'wprobo-documerge-lite' ),
					'name_format'                => __( 'Name Format', 'wprobo-documerge-lite' ),
					'name_first_last'            => __( 'First + Last', 'wprobo-documerge-lite' ),
					'name_first_middle_last'     => __( 'First + Middle + Last', 'wprobo-documerge-lite' ),
					'name_title_first_last'      => __( 'Title + First + Last', 'wprobo-documerge-lite' ),
					'html_content'               => __( 'HTML Content', 'wprobo-documerge-lite' ),
					'html_content_desc'          => __( 'Supports HTML: headings, paragraphs, lists, links, images.', 'wprobo-documerge-lite' ),
					'title_text'                 => __( 'Title Text', 'wprobo-documerge-lite' ),
					'show_title'                 => __( 'Show Title', 'wprobo-documerge-lite' ),
					'title_alignment'            => __( 'Title Alignment', 'wprobo-documerge-lite' ),
					'align_left'                 => __( 'Left', 'wprobo-documerge-lite' ),
					'align_center'               => __( 'Center', 'wprobo-documerge-lite' ),
					'align_right'                => __( 'Right', 'wprobo-documerge-lite' ),
					'divider_style'              => __( 'Divider Style', 'wprobo-documerge-lite' ),
					'divider_solid'              => __( 'Solid Line', 'wprobo-documerge-lite' ),
					'divider_dashed'             => __( 'Dashed', 'wprobo-documerge-lite' ),
					'divider_dotted'             => __( 'Dotted', 'wprobo-documerge-lite' ),
					'divider_double'             => __( 'Double', 'wprobo-documerge-lite' ),
					'divider_none'               => __( 'No Line', 'wprobo-documerge-lite' ),
					'dynamic_none'               => __( 'None (use default)', 'wprobo-documerge-lite' ),
					'dynamic_user_id'            => __( 'Current User ID', 'wprobo-documerge-lite' ),
					'dynamic_user_email'         => __( 'Current User Email', 'wprobo-documerge-lite' ),
					'dynamic_user_name'          => __( 'Current User Name', 'wprobo-documerge-lite' ),
					'dynamic_page_url'           => __( 'Current Page URL', 'wprobo-documerge-lite' ),
					'dynamic_page_title'         => __( 'Page Title', 'wprobo-documerge-lite' ),
					'dynamic_referrer'           => __( 'Referrer URL', 'wprobo-documerge-lite' ),
					'dynamic_custom'             => __( 'Custom Value', 'wprobo-documerge-lite' ),
					'custom_value'               => __( 'Custom Value', 'wprobo-documerge-lite' ),
					'html_add_content'           => '<p>' . __( 'Add your content here.', 'wprobo-documerge-lite' ) . '</p>',
					'singleton_limit'            => __( 'field can only be added once per form.', 'wprobo-documerge-lite' ),
					'confirm_remove_field'       => __( 'Are you sure you want to remove this field?', 'wprobo-documerge-lite' ),
					'confirm_delete_form'        => __( 'Are you sure you want to delete this form? This cannot be undone.', 'wprobo-documerge-lite' ),
					'save_form_first'            => __( 'Please save the form first.', 'wprobo-documerge-lite' ),
					'page_created'               => __( 'Page created! Opening in new tab...', 'wprobo-documerge-lite' ),
					'failed_create_page'         => __( 'Failed to create page.', 'wprobo-documerge-lite' ),
					'network_error_retry'        => __( 'Network error. Please try again.', 'wprobo-documerge-lite' ),
					'save_error'                 => __( 'An error occurred while saving the form.', 'wprobo-documerge-lite' ),
					'preview_failed'             => __( 'Preview failed.', 'wprobo-documerge-lite' ),
					'form_deleted'               => __( 'Form deleted.', 'wprobo-documerge-lite' ),
					'an_error_occurred'          => __( 'An error occurred.', 'wprobo-documerge-lite' ),
					'select_external_form'       => __( 'Select an external form to see field mapping.', 'wprobo-documerge-lite' ),
					'loading_fields'             => __( 'Loading fields...', 'wprobo-documerge-lite' ),
					'no_fields_found'            => __( 'No fields found.', 'wprobo-documerge-lite' ),
					'failed_load_fields'         => __( 'Failed to load fields.', 'wprobo-documerge-lite' ),
					'showing'                    => __( 'Showing', 'wprobo-documerge-lite' ),
					'of'                         => __( 'of', 'wprobo-documerge-lite' ),
					'prev'                       => __( 'Prev', 'wprobo-documerge-lite' ),
					'next'                       => __( 'Next', 'wprobo-documerge-lite' ),
					'select_submissions_delete'  => __( 'Select submissions to delete', 'wprobo-documerge-lite' ),
					/* translators: %d: number of selected submissions. */
					'delete_selected_confirm'    => __( 'Delete %d selected submissions?', 'wprobo-documerge-lite' ),
					'delete_this_submission'     => __( 'Delete this submission?', 'wprobo-documerge-lite' ),
					'error_loading_submission'   => __( 'Error loading submission.', 'wprobo-documerge-lite' ),
					'submission_prefix'          => __( 'Submission #', 'wprobo-documerge-lite' ),
					'paid'                       => __( 'Paid', 'wprobo-documerge-lite' ),
					'save_note'                  => __( 'Save Note', 'wprobo-documerge-lite' ),

					// Submission status labels.
					'status_free'                => __( 'Free', 'wprobo-documerge-lite' ),
					'status_pending'             => __( 'Pending', 'wprobo-documerge-lite' ),
					'status_failed'              => __( 'Failed', 'wprobo-documerge-lite' ),
					'status_refunded'            => __( 'Refunded', 'wprobo-documerge-lite' ),

					// Phone validation.
					'phone_invalid'              => __( 'Invalid number', 'wprobo-documerge-lite' ),
					'phone_invalid_country'      => __( 'Invalid country code', 'wprobo-documerge-lite' ),
					'phone_too_short'            => __( 'Too short', 'wprobo-documerge-lite' ),
					'phone_too_long'             => __( 'Too long', 'wprobo-documerge-lite' ),
					'phone_invalid_number'       => __( 'Invalid phone number', 'wprobo-documerge-lite' ),

					// Common.
					'dismiss'                    => __( 'Dismiss', 'wprobo-documerge-lite' ),
				),
			)
		);

		// Chart.js + dashboard charts — only on dashboard page.
		if ( 'toplevel_page_wprobo-documerge' === $hook ) {
			wp_enqueue_script(
				'wprobo-documerge-chartjs',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/chartjs/chart.umd.min.js',
				array(),
				'4.5.0',
				true
			);
			wp_enqueue_script(
				'wprobo-documerge-dashboard-charts',
				WPROBO_DOCUMERGE_URL . 'assets/js/admin/dashboard-charts.min.js',
				array( 'wprobo-documerge-chartjs' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Settings JS + Select2 — only on settings page.
		if ( strpos( $hook, 'wprobo-documerge-settings' ) !== false ) {
			wp_enqueue_script(
				'wprobo-documerge-settings',
				WPROBO_DOCUMERGE_URL . 'assets/js/admin/settings.min.js',
				array( 'jquery', 'wprobo-documerge-admin' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
			// Select2 for searchable font dropdown in Styles tab.
			wp_enqueue_style(
				'wprobo-documerge-select2',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/select2/select2.min.css',
				array(),
				'4.1.0'
			);
			wp_enqueue_script(
				'wprobo-documerge-select2',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/select2/select2.min.js',
				array( 'jquery' ),
				'4.1.0',
				true
			);
		}

		// Template Manager JS — only on templates page.
		if ( strpos( $hook, 'wprobo-documerge-templates' ) !== false ) {
			wp_enqueue_script(
				'wprobo-documerge-template-manager',
				WPROBO_DOCUMERGE_URL . 'assets/js/admin/template-manager.min.js',
				array( 'jquery', 'wprobo-documerge-admin' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Form Builder JS — only on forms page.
		if ( strpos( $hook, 'wprobo-documerge-forms' ) !== false ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script(
				'wprobo-documerge-form-builder',
				WPROBO_DOCUMERGE_URL . 'assets/js/admin/form-builder.min.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wprobo-documerge-admin' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Submissions JS — only on submissions page.
		if ( strpos( $hook, 'wprobo-documerge-submissions' ) !== false ) {
			wp_enqueue_script(
				'wprobo-documerge-submissions',
				WPROBO_DOCUMERGE_URL . 'assets/js/admin/submissions.min.js',
				array( 'jquery', 'wprobo-documerge-admin' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * Only loads assets on pages that contain the DocuMerge
	 * shortcode or block to avoid unnecessary overhead.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wprobo_documerge_enqueue_frontend_assets() {

		global $post;

		// Bail if no post object available.
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Check for shortcode or block presence.
		$wprobo_documerge_has_shortcode = has_shortcode( $post->post_content, 'wprobo_documerge_form' );
		$wprobo_documerge_has_block     = has_block( 'wprobo-documerge/form-embed', $post );

		if ( ! $wprobo_documerge_has_shortcode && ! $wprobo_documerge_has_block ) {
			return;
		}

		// Dashicons for frontend icons.
		wp_enqueue_style( 'dashicons' );

		// Frontend CSS.
		wp_enqueue_style(
			'wprobo-documerge-frontend',
			WPROBO_DOCUMERGE_URL . 'assets/css/frontend/form.min.css',
			array( 'dashicons' ),
			WPROBO_DOCUMERGE_VERSION
		);


		// Frontend JS — form renderer + validator.
		wp_enqueue_script(
			'wprobo-documerge-frontend',
			WPROBO_DOCUMERGE_URL . 'assets/js/frontend/form-renderer.min.js',
			array( 'jquery' ),
			WPROBO_DOCUMERGE_VERSION,
			true
		);

		// Auto-save draft handler.
		wp_enqueue_script(
			'wprobo-documerge-autosave',
			WPROBO_DOCUMERGE_URL . 'assets/js/frontend/autosave-handler.min.js',
			array( 'jquery', 'wprobo-documerge-frontend' ),
			WPROBO_DOCUMERGE_VERSION,
			true
		);

		// Detect field types by loading actual form data from the DB.
		// The post content only has [wprobo_documerge_form id="X"] — field info is in wprdm_forms.
		$wprobo_documerge_has_phone      = false;
		$wprobo_documerge_has_date       = false;
		$wprobo_documerge_has_searchable = false;
		$wprobo_documerge_has_tracking   = false;

		// Extract form IDs from shortcodes and blocks in the post content.
		$wprobo_documerge_form_ids = array();
		if ( preg_match_all( '/\[wprobo_documerge_form\s+id=["\']?(\d+)["\']?\s*\]/', $post->post_content, $matches ) ) {
			$wprobo_documerge_form_ids = array_map( 'absint', $matches[1] );
		}
		// Also check for Gutenberg block formId attribute.
		if ( preg_match_all( '/"formId"\s*:\s*(\d+)/', $post->post_content, $matches ) ) {
			$wprobo_documerge_form_ids = array_merge( $wprobo_documerge_form_ids, array_map( 'absint', $matches[1] ) );
		}
		$wprobo_documerge_form_ids = array_unique( array_filter( $wprobo_documerge_form_ids ) );

		// Check each form's fields for special field types.
		if ( ! empty( $wprobo_documerge_form_ids ) ) {
			$form_builder = new \WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Builder();
			foreach ( $wprobo_documerge_form_ids as $fid ) {
				$form_obj = $form_builder->wprobo_documerge_get_form( $fid );
				if ( ! $form_obj ) {
					continue;
				}
				$fields_json = isset( $form_obj->fields ) ? $form_obj->fields : '';
				if ( strpos( $fields_json, '"phone"' ) !== false ) {
					$wprobo_documerge_has_phone = true;
				}
				if ( strpos( $fields_json, '"date"' ) !== false ) {
					$wprobo_documerge_has_date = true;
				}
				if ( strpos( $fields_json, '"searchable":true' ) !== false || strpos( $fields_json, '"searchable":"1"' ) !== false ) {
					$wprobo_documerge_has_searchable = true;
				}
				if ( strpos( $fields_json, '"tracking"' ) !== false ) {
					$wprobo_documerge_has_tracking = true;
				}
			}
		}

		// intl-tel-input — only on pages with phone field.
		if ( $wprobo_documerge_has_phone ) {
			wp_enqueue_style(
				'wprobo-documerge-intl-tel',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/intl-tel-input/intlTelInput.min.css',
				array(),
				'26.9.2'
			);
			wp_enqueue_script(
				'wprobo-documerge-intl-tel',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/intl-tel-input/intlTelInput.min.js',
				array(),
				'26.9.2',
				true
			);
			wp_enqueue_script(
				'wprobo-documerge-phone-handler',
				WPROBO_DOCUMERGE_URL . 'assets/js/frontend/phone-handler.min.js',
				array( 'jquery', 'wprobo-documerge-intl-tel' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Flatpickr datepicker — only on pages with date field.
		if ( $wprobo_documerge_has_date ) {
			wp_enqueue_style(
				'wprobo-documerge-flatpickr',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/flatpickr/flatpickr.min.css',
				array(),
				'4.6.13'
			);
			wp_enqueue_script(
				'wprobo-documerge-flatpickr',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/flatpickr/flatpickr.min.js',
				array(),
				'4.6.13',
				true
			);
			wp_enqueue_script(
				'wprobo-documerge-datepicker-handler',
				WPROBO_DOCUMERGE_URL . 'assets/js/frontend/datepicker-handler.min.js',
				array( 'jquery', 'wprobo-documerge-flatpickr' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Select2 — only on pages with searchable dropdown field.
		if ( $wprobo_documerge_has_searchable ) {
			wp_enqueue_style(
				'wprobo-documerge-select2',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/select2/select2.min.css',
				array(),
				'4.1.0'
			);
			wp_enqueue_script(
				'wprobo-documerge-select2',
				WPROBO_DOCUMERGE_URL . 'assets/vendor/select2/select2.min.js',
				array( 'jquery' ),
				'4.1.0',
				true
			);
			// Init Select2 on searchable dropdowns.
			wp_add_inline_script(
				'wprobo-documerge-select2',
				'jQuery(function($){ $(".wdm-select2").select2({ width: "100%", placeholder: $(this).data("placeholder") || "", allowClear: true }); });'
			);
		}

		// Tracking handler — only on pages with tracking field.
		if ( $wprobo_documerge_has_tracking ) {
			wp_enqueue_script(
				'wprobo-documerge-tracking',
				WPROBO_DOCUMERGE_URL . 'assets/js/frontend/tracking-handler.min.js',
				array( 'jquery' ),
				WPROBO_DOCUMERGE_VERSION,
				true
			);
		}

		// Localize frontend script.
		wp_localize_script(
			'wprobo-documerge-frontend',
			'wprobo_documerge_frontend_vars',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'wprobo_documerge_frontend' ),
				'intl_tel_utils_url' => WPROBO_DOCUMERGE_URL . 'assets/vendor/intl-tel-input/utils.js',
			)
		);

	}
}
