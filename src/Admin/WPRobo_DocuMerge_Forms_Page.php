<?php
/**
 * Forms admin page controller.
 *
 * Handles the Forms list page, form builder view, and all
 * form-related AJAX operations (save, delete).
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

use WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Builder;
use WPRobo\DocuMerge\Template\WPRobo_DocuMerge_Template_Manager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Forms_Page
 *
 * Renders the Forms admin page listing all document collection forms,
 * displays the form builder for creating/editing forms, and processes
 * AJAX requests for saving and deleting forms.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Forms_Page {

	/**
	 * The list table instance.
	 *
	 * @since 1.6.0
	 * @var   WPRobo_DocuMerge_Forms_List_Table|null
	 */
	private $wprobo_documerge_list_table = null;

	/**
	 * Register screen options (called early via load-{page} hook).
	 *
	 * Only registers per-page option for the list view, not the builder.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function wprobo_documerge_screen_options() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page routing read; cap-checked, sanitized below.
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

		// Only add screen options on the list view, not the builder.
		if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
			return;
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Forms per page', 'wprobo-documerge-lite' ),
				'default' => 20,
				'option'  => 'wprobo_documerge_forms_per_page',
			)
		);

		$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Forms_List_Table();
	}

	/**
	 * Register WordPress hooks for form AJAX handlers.
	 *
	 * Registers admin-only AJAX actions for saving and deleting forms.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'wp_ajax_wprobo_documerge_delete_form', array( $this, 'wprobo_documerge_ajax_delete_form' ) );
		add_action( 'wp_ajax_wprobo_documerge_save_form', array( $this, 'wprobo_documerge_ajax_save_form' ) );
		add_action( 'wp_ajax_wprobo_documerge_preview_document', array( $this, 'wprobo_documerge_ajax_preview_document' ) );
		add_action( 'wp_ajax_wprobo_documerge_serve_preview', array( $this, 'wprobo_documerge_ajax_serve_preview' ) );
		add_action( 'wp_ajax_wprobo_documerge_get_external_fields', array( $this, 'wprobo_documerge_ajax_get_external_fields' ) );
		add_action( 'wp_ajax_wprobo_documerge_create_form_page', array( $this, 'wprobo_documerge_ajax_create_form_page' ) );
	}

	/**
	 * AJAX handler — create a WordPress page with the form shortcode embedded.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_create_form_page() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$form_id    = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$form_title = isset( $_POST['form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['form_title'] ) ) : '';

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		if ( empty( $form_title ) ) {
			$form_title = 'DocuMerge Form';
		}

		// Create a draft page with the shortcode.
		$page_id = wp_insert_post(
			array(
				'post_title'   => $form_title,
				'post_content' => '<!-- wp:shortcode -->[wprobo_documerge_form id="' . $form_id . '"]<!-- /wp:shortcode -->',
				'post_status'  => 'draft',
				'post_type'    => 'page',
			)
		);

		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'message' => $page_id->get_error_message() ) );
			return;
		}

		wp_send_json_success(
			array(
				'page_id'  => $page_id,
				'edit_url' => get_edit_post_link( $page_id, 'raw' ),
				'view_url' => get_permalink( $page_id ),
				'message'  => __( 'Page created as draft.', 'wprobo-documerge-lite' ),
			)
		);
	}

	/**
	 * Render the Forms admin page.
	 *
	 * Checks user capabilities, then either renders the form builder
	 * (when action is 'edit' or 'new') or the forms list view.
	 *
	 * For the list view, retrieves all forms from the Form_Builder,
	 * decodes each form's fields JSON to count fields, fetches the
	 * submission count, and passes everything to the list template.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-documerge-lite' ) );
		}

		// Determine if we should show the builder instead of the list.
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page action router; cap-checked, sanitize_key applied.

		if ( 'edit' === $action || 'new' === $action ) {
			$this->wprobo_documerge_render_builder();
			return;
		}

		// Process bulk actions.
		$this->wprobo_documerge_process_bulk_action();

		// Prepare list table if not already done via screen options.
		if ( null === $this->wprobo_documerge_list_table ) {
			$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Forms_List_Table();
		}

		$this->wprobo_documerge_list_table->prepare_items();

		// Page header variables.
		$page_title     = __( 'Forms', 'wprobo-documerge-lite' );
		$page_subtitle  = __( 'Build and manage your document collection forms', 'wprobo-documerge-lite' );
		$primary_action = array(
			'url'   => admin_url( 'admin.php?page=wprobo-documerge-forms&action=new' ),
			'label' => __( 'Create Form', 'wprobo-documerge-lite' ),
			'icon'  => 'dashicons-plus-alt2',
		);

		?>
		<div class="wdm-admin-wrap">

			<?php include WPROBO_DOCUMERGE_PATH . 'templates/admin/partials/page-header.php'; ?>

			<div class="wdm-list-table-wrap">
				<form method="get">
					<input type="hidden" name="page" value="wprobo-documerge-forms" />

					<?php
					$this->wprobo_documerge_list_table->search_box( __( 'Search forms', 'wprobo-documerge-lite' ), 'wdm-form-search' );
					$this->wprobo_documerge_list_table->display();
					?>
				</form>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the form builder page.
	 *
	 * Loads existing form data when editing (action=edit with id),
	 * or sets form to null when creating a new form. Retrieves all
	 * templates for the template selection dropdown.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_builder() {
		$form = null;

		// Load existing form data when editing.
		$form_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page form-id read; cap-checked, sanitized via absint.

		if ( $form_id > 0 ) {
			$form_builder = new WPRobo_DocuMerge_Form_Builder();
			$form         = $form_builder->wprobo_documerge_get_form( $form_id );
		}

		// Get all templates for the template dropdown.
		$template_manager = new WPRobo_DocuMerge_Template_Manager();
		$templates        = $template_manager->wprobo_documerge_get_all_templates();

		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		include WPROBO_DOCUMERGE_PATH . 'templates/admin/form-builder/builder.php';
	}

	/**
	 * Process bulk delete action from the list table.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	private function wprobo_documerge_process_bulk_action() {
		if ( null === $this->wprobo_documerge_list_table ) {
			$this->wprobo_documerge_list_table = new WPRobo_DocuMerge_Forms_List_Table();
		}

		if ( 'delete' !== $this->wprobo_documerge_list_table->current_action() ) {
			return;
		}

		// Verify nonce (WP_List_Table uses _wpnonce with bulk-{plural}).
		check_admin_referer( 'bulk-forms' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$ids = isset( $_GET['form_ids'] ) ? array_map( 'absint', wp_unslash( (array) $_GET['form_ids'] ) ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return;
		}

		$form_builder = new WPRobo_DocuMerge_Form_Builder();
		foreach ( $ids as $id ) {
			$form_builder->wprobo_documerge_delete_form( $id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wprobo-documerge-forms',
					'deleted' => count( $ids ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX handler — delete a form.
	 *
	 * Verifies nonce and capabilities, validates the form ID,
	 * then delegates deletion to the Form_Builder.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_delete_form() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$form_builder = new WPRobo_DocuMerge_Form_Builder();
		$result       = $form_builder->wprobo_documerge_delete_form( $form_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'Form deleted successfully.', 'wprobo-documerge-lite' ) ) );
	}

	/**
	 * AJAX handler — save a form.
	 *
	 * Verifies nonce and capabilities, sanitizes all POST inputs
	 * (title, template_id, fields, settings, output_format,
	 * delivery_methods, payment fields), then delegates to the
	 * Form_Builder for insert or update.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_save_form() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Sanitize form ID (0 for new forms).
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		// Title (required).
		$title = isset( $_POST['title'] )
			? sanitize_text_field( wp_unslash( $_POST['title'] ) )
			: '';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Form title is required.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Template ID.
		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		// Fields — sanitize raw JSON first; per-field type-aware sanitization below.
		$fields_raw = isset( $_POST['fields'] )
			? sanitize_textarea_field( wp_unslash( $_POST['fields'] ) )
			: '[]';

		$fields_decoded = json_decode( $fields_raw, true );

		if ( ! is_array( $fields_decoded ) ) {
			$fields_decoded = array();
		}

		$fields_sanitized = array();
		foreach ( $fields_decoded as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$sanitized_field = array();
			foreach ( $field as $key => $value ) {
				$safe_key = sanitize_key( $key );
				if ( is_array( $value ) ) {
					$sanitized_field[ $safe_key ] = self::wprobo_documerge_sanitize_recursive( $value );
				} else {
					$sanitized_field[ $safe_key ] = sanitize_text_field( $value );
				}
			}
			$fields_sanitized[] = $sanitized_field;
		}

		// Settings — sanitize raw JSON before decoding; per-key sanitization below.
		$settings_raw = isset( $_POST['settings'] )
			? sanitize_textarea_field( wp_unslash( $_POST['settings'] ) )
			: '{}';

		$settings_decoded = json_decode( $settings_raw, true );

		if ( ! is_array( $settings_decoded ) ) {
			$settings_decoded = array();
		}

		$settings_sanitized = array();
		foreach ( $settings_decoded as $key => $value ) {
			$safe_key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$settings_sanitized[ $safe_key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$settings_sanitized[ $safe_key ] = sanitize_text_field( $value );
			}
		}

		// Output format.
		$output_format   = isset( $_POST['output_format'] )
			? sanitize_key( wp_unslash( $_POST['output_format'] ) )
			: 'pdf';
		$allowed_formats = array( 'pdf', 'docx', 'both' );
		if ( ! in_array( $output_format, $allowed_formats, true ) ) {
			$output_format = 'pdf';
		}

		// Delivery methods — sanitize raw JSON first, then each slug via sanitize_key.
		$delivery_methods_raw = isset( $_POST['delivery_methods'] )
			? sanitize_textarea_field( wp_unslash( $_POST['delivery_methods'] ) )
			: '[]';

		$delivery_methods_decoded = json_decode( $delivery_methods_raw, true );

		if ( ! is_array( $delivery_methods_decoded ) ) {
			$delivery_methods_decoded = array();
		}

		$delivery_methods_sanitized = array_map( 'sanitize_key', $delivery_methods_decoded );

		// Mode and integration.
		$mode = isset( $_POST['mode'] )
			? sanitize_key( wp_unslash( $_POST['mode'] ) )
			: 'standalone';

		$integration = isset( $_POST['integration'] )
			? sanitize_key( wp_unslash( $_POST['integration'] ) )
			: '';

		// Submit button label.
		$submit_label = isset( $_POST['submit_label'] )
			? sanitize_text_field( wp_unslash( $_POST['submit_label'] ) )
			: '';

		// Success message.
		$success_message = isset( $_POST['success_message'] )
			? sanitize_textarea_field( wp_unslash( $_POST['success_message'] ) )
			: '';

		// Build data array.
		$data = array(
			'id'               => $id,
			'title'            => $title,
			'template_id'      => $template_id,
			'fields'           => $fields_sanitized,
			'settings'         => $settings_sanitized,
			'output_format'    => $output_format,
			'delivery_methods' => wp_json_encode( $delivery_methods_sanitized ),
			'submit_label'     => $submit_label,
			'success_message'  => $success_message,
			'mode'             => $mode,
			'integration'      => $integration,
		);

		$form_builder = new WPRobo_DocuMerge_Form_Builder();
		$form_id      = $form_builder->wprobo_documerge_save_form( $data );

		if ( is_wp_error( $form_id ) ) {
			wp_send_json_error( array( 'message' => $form_id->get_error_message() ) );
			return;
		}

		wp_send_json_success( array( 'form_id' => $form_id ) );
	}

	/**
	 * Recursively sanitize an array of values.
	 *
	 * Handles nested arrays (e.g. conditions within field data)
	 * that cannot be sanitized with a flat array_map call.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data The data to sanitize.
	 * @return array The sanitized data.
	 */
	private static function wprobo_documerge_sanitize_recursive( $data ) {
		$sanitized = array();
		foreach ( $data as $key => $value ) {
			$safe_key = is_int( $key ) ? $key : sanitize_key( $key );
			if ( is_array( $value ) ) {
				$sanitized[ $safe_key ] = self::wprobo_documerge_sanitize_recursive( $value );
			} else {
				$sanitized[ $safe_key ] = sanitize_text_field( $value );
			}
		}
		return $sanitized;
	}

	/**
	 * AJAX handler — generate a preview document with sample data.
	 *
	 * Loads the form and its associated template, generates sample
	 * merge data from the field definitions, processes the DOCX
	 * template, converts to PDF, and returns a temporary download URL.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_preview_document() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Form ID is required.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Load form.
		$form_builder = new WPRobo_DocuMerge_Form_Builder();
		$form         = $form_builder->wprobo_documerge_get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$template_id = absint( $form->template_id );
		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'No template assigned to this form.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Load template.
		$template_manager = new WPRobo_DocuMerge_Template_Manager();
		$template         = $template_manager->wprobo_documerge_get_template( $template_id );

		if ( ! $template || empty( $template->file_path ) || ! file_exists( $template->file_path ) ) {
			wp_send_json_error( array( 'message' => __( 'Template file not found.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Generate sample merge data from form fields.
		$fields = json_decode( $form->fields, true );
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$sample_data = $this->wprobo_documerge_generate_sample_data( $fields );

		// Add system tags.
		$sample_data['current_date'] = current_time( get_option( 'date_format', 'Y-m-d' ) );
		$sample_data['current_time'] = current_time( get_option( 'time_format', 'H:i' ) );
		$sample_data['site_name']    = get_bloginfo( 'name' );

		// Process DOCX.
		$docx_processor = new \WPRobo\DocuMerge\Document\WPRobo_DocuMerge_Docx_Processor();
		$result         = $docx_processor->wprobo_documerge_process( $template->file_path, $sample_data, 0 );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			return;
		}

		// Convert to PDF.
		$pdf_converter = new \WPRobo\DocuMerge\Document\WPRobo_DocuMerge_Pdf_Converter();
		$pdf_path      = $pdf_converter->wprobo_documerge_convert( $result );

		if ( is_wp_error( $pdf_path ) ) {
			// Return DOCX download if PDF fails.
			$url = admin_url(
				'admin-ajax.php?action=wprobo_documerge_serve_preview&file='
				. rawurlencode( basename( $result ) )
				. '&nonce=' . wp_create_nonce( 'wprobo_documerge_preview' )
			);
			wp_send_json_success(
				array(
					'url'    => $url,
					'format' => 'docx',
				)
			);
			return;
		}

		// Create a temporary download URL.
		$url = admin_url(
			'admin-ajax.php?action=wprobo_documerge_serve_preview&file='
			. rawurlencode( basename( $pdf_path ) )
			. '&nonce=' . wp_create_nonce( 'wprobo_documerge_preview' )
		);

		wp_send_json_success(
			array(
				'url'    => $url,
				'format' => 'pdf',
			)
		);
	}

	/**
	 * Generate sample data from form fields for document preview.
	 *
	 * Creates realistic placeholder values for each field type so
	 * admins can preview how the generated document will look.
	 *
	 * @since  1.4.0
	 * @param  array $fields Array of field definition arrays.
	 * @return array Associative array of field_name => sample_value.
	 */
	private function wprobo_documerge_generate_sample_data( $fields ) {
		$data = array();

		foreach ( $fields as $field ) {
			$name  = isset( $field['name'] ) ? $field['name'] : '';
			$type  = isset( $field['type'] ) ? $field['type'] : 'text';
			$label = isset( $field['label'] ) ? $field['label'] : '';

			if ( empty( $name ) ) {
				continue;
			}

			switch ( $type ) {
				case 'text':
				case 'textarea':
					$data[ $name ] = 'Sample ' . $label;
					break;
				case 'email':
					$data[ $name ] = 'john.doe@example.com';
					break;
				case 'phone':
					$data[ $name ] = '+44 7700 900000';
					break;
				case 'url':
					$data[ $name ] = 'https://example.com';
					break;
				case 'number':
					$data[ $name ] = '42';
					break;
				case 'date':
					$fmt           = isset( $field['date_format'] ) ? $field['date_format'] : 'Y-m-d';
					$data[ $name ] = gmdate( $fmt );
					break;
				case 'dropdown':
				case 'radio':
					$options = isset( $field['options'] ) ? $field['options'] : array();
					if ( ! empty( $options ) && is_array( $options ) ) {
						$first         = reset( $options );
						$data[ $name ] = isset( $first['value'] ) ? $first['value'] : 'Option 1';
					} else {
						$data[ $name ] = 'Option 1';
					}
					break;
				case 'checkbox':
					$data[ $name ] = 'Yes';
					break;
				case 'name':
					$data[ $name . '_first' ] = 'John';
					$data[ $name . '_last' ]  = 'Doe';
					$data[ $name ]            = 'John Doe';
					break;
				case 'address':
					$data[ $name . '_line1' ]    = '123 Example Street';
					$data[ $name . '_city' ]     = 'London';
					$data[ $name . '_state' ]    = 'England';
					$data[ $name . '_postcode' ] = 'SW1A 1AA';
					$data[ $name . '_country' ]  = 'United Kingdom';
					$data[ $name ]               = '123 Example Street, London, England, SW1A 1AA, United Kingdom';
					break;
				case 'signature':
					$data[ $name ] = '[Signature]';
					break;
				case 'password':
					$data[ $name ] = '••••••••';
					break;
				case 'rating':
					$max           = isset( $field['max_stars'] ) ? $field['max_stars'] : 5;
					$data[ $name ] = (string) $max;
					break;
				case 'repeater':
					$cols = isset( $field['columns'] ) ? $field['columns'] : array();
					$row  = array();
					foreach ( $cols as $col ) {
						$row[ $col['name'] ] = 'Sample ' . $col['label'];
					}
					$data[ $name ] = wp_json_encode( array( $row, $row ) );
					break;
				default:
					$data[ $name ] = 'Sample ' . $label;
			}
		}

		return $data;
	}

	/**
	 * AJAX handler — serve a preview document file.
	 *
	 * Validates the nonce and capabilities, locates the requested
	 * file in the temp or docs directories, and streams it inline
	 * to the browser.
	 *
	 * @since  1.4.0
	 * @return void Outputs file content and exits.
	 */
	public function wprobo_documerge_ajax_serve_preview() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below.
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wprobo_documerge_preview' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'wprobo-documerge-lite' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wprobo-documerge-lite' ) );
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified above.
		$file = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';

		if ( empty( $file ) ) {
			wp_die( esc_html__( 'File not specified.', 'wprobo-documerge-lite' ) );
		}

		// Look in both temp and docs directories.
		$docs_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir();
		$temp_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_temp_dir();
		$paths    = array(
			$docs_dir . '0/' . $file,
			$docs_dir . gmdate( 'Y' ) . '/' . gmdate( 'm' ) . '/0/' . $file,
			$temp_dir . $file,
		);

		$found = '';
		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				$found = $path;
				break;
			}
		}

		if ( empty( $found ) ) {
			wp_die( esc_html__( 'File not found.', 'wprobo-documerge-lite' ) );
		}

		$ext          = strtolower( pathinfo( $found, PATHINFO_EXTENSION ) );
		$content_type = 'pdf' === $ext
			? 'application/pdf'
			: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: inline; filename="preview.' . esc_attr( $ext ) . '"' );
		header( 'Content-Length: ' . filesize( $found ) );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $found );
		exit;
	}

	/**
	 * AJAX handler — get external form fields and return mapping table HTML.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function wprobo_documerge_ajax_get_external_fields() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		$external_form_id = isset( $_POST['external_form_id'] ) ? absint( $_POST['external_form_id'] ) : 0;
		$integration_slug = isset( $_POST['integration'] ) ? sanitize_key( wp_unslash( $_POST['integration'] ) ) : '';
		$template_id      = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $external_form_id || empty( $integration_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Integrations are Pro-only.
		wp_send_json_error( array( 'message' => __( 'Form integrations require DocuMerge Pro.', 'wprobo-documerge-lite' ) ) );
		return;

        // phpcs:disable -- Unreachable code kept for Pro compatibility.
        $external_fields = array();

        // Get merge tags from template.
        $merge_tags = array();
        if ( $template_id > 0 ) {
            $tmpl_mgr = new \WPRobo\DocuMerge\Template\WPRobo_DocuMerge_Template_Manager();
            $tmpl     = $tmpl_mgr->wprobo_documerge_get_template( $template_id );
            if ( $tmpl && ! empty( $tmpl->merge_tags ) ) {
                $merge_tags = json_decode( $tmpl->merge_tags, true );
                if ( ! is_array( $merge_tags ) ) {
                    $merge_tags = array();
                }
            }
        }

        if ( empty( $merge_tags ) ) {
            wp_send_json_success( array( 'html' => '<p class="wdm-text-muted">' . esc_html__( 'Select a template first to see merge tags for mapping.', 'wprobo-documerge-lite' ) . '</p>' ) );
            return;
        }

        if ( empty( $external_fields ) ) {
            wp_send_json_success( array( 'html' => '<p class="wdm-text-muted">' . esc_html__( 'No fields found in the selected form.', 'wprobo-documerge-lite' ) . '</p>' ) );
            return;
        }

        // Build mapping table HTML.
        $integration_label = $integration->wprobo_documerge_get_name();
        $system_tags       = array( 'current_date', 'current_time', 'site_name' );

        $html  = '<label>' . esc_html__( 'Field Mapping', 'wprobo-documerge-lite' ) . '</label>';
        $html .= '<span class="wdm-description" style="margin-bottom:12px;display:block;">';
        $html .= esc_html__( 'Map each template merge tag to a field from your external form.', 'wprobo-documerge-lite' );
        $html .= '</span>';
        $html .= '<table class="wdm-field-map-table"><thead><tr>';
        $html .= '<th>' . esc_html__( 'Template Merge Tag', 'wprobo-documerge-lite' ) . '</th>';
        $html .= '<th>&rarr;</th>';
        /* translators: %s: integration name */
        $html .= '<th>' . esc_html( sprintf( __( '%s Field', 'wprobo-documerge-lite' ), $integration_label ) ) . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ( $merge_tags as $tag ) {
            if ( in_array( $tag, $system_tags, true ) ) {
                continue;
            }
            $html .= '<tr class="wdm-field-map-row">';
            $html .= '<td><code>{' . esc_html( $tag ) . '}</code></td>';
            $html .= '<td>&rarr;</td>';
            $html .= '<td><select class="wdm-field-map-select wdm-select" data-merge-tag="' . esc_attr( $tag ) . '">';
            $html .= '<option value="">' . esc_html__( '— Not mapped —', 'wprobo-documerge-lite' ) . '</option>';
            foreach ( $external_fields as $ef ) {
                $html .= '<option value="' . esc_attr( $ef['key'] ) . '">' . esc_html( $ef['label'] . ' (' . $ef['type'] . ')' ) . '</option>';
            }
            $html .= '</select></td></tr>';
        }

        $html .= '</tbody></table>';

        wp_send_json_success( array( 'html' => $html ) );
    }
}
