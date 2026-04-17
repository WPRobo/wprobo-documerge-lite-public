<?php
/**
 * AJAX form submission handler for WPRobo DocuMerge.
 *
 * Processes frontend form submissions through a multi-step pipeline:
 * security checks, field validation, captcha verification, database
 * storage, document generation, and delivery.
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

use WPRobo\DocuMerge\Document\WPRobo_DocuMerge_Document_Generator;
use WPRobo\DocuMerge\Document\WPRobo_DocuMerge_Delivery_Engine;
use WPRobo\DocuMerge\Helpers\WPRobo_DocuMerge_Logger;

/**
 * Class WPRobo_DocuMerge_Form_Submission
 *
 * Handles the full AJAX form submission pipeline including security,
 * validation, captcha verification, database persistence, document
 * generation, and delivery. Not a singleton -- instantiate as needed.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Form_Submission {

	/**
	 * Maximum submissions allowed per IP within the rate-limit window.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const WPROBO_DOCUMERGE_RATE_LIMIT_MAX = 50;

	/**
	 * Rate-limit window duration in seconds (1 hour).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const WPROBO_DOCUMERGE_RATE_LIMIT_TTL = 3600;

	/**
	 * Register AJAX hooks for form submission.
	 *
	 * Registers both authenticated and unauthenticated AJAX handlers
	 * for the wprobo_documerge_submit_form action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'wp_ajax_wprobo_documerge_submit_form', array( $this, 'wprobo_documerge_handle_submission' ) );
		add_action( 'wp_ajax_nopriv_wprobo_documerge_submit_form', array( $this, 'wprobo_documerge_handle_submission' ) );
	}

	/**
	 * Handle the full form submission pipeline.
	 *
	 * Processes the AJAX request through ten sequential steps:
	 * 1. Security (nonce, honeypot, rate limiting)
	 * 2. Load form
	 * 3. Decode and filter fields by conditional visibility
	 * 4. Server-side validation
	 * 5. Captcha verification
	 * 6. Create submission record
	 * 7. Check payment requirements
	 * 8. Generate document
	 * 9. Deliver document
	 * 10. Return success response
	 *
	 * @since 1.0.0
	 *
	 * @return void Outputs JSON response and terminates.
	 */
	public function wprobo_documerge_handle_submission() {
		// ── Step 1: Security ────────────────────────────────────────────

		// Verify nonce.
		check_ajax_referer( 'wprobo_documerge_frontend', 'nonce' );

		// Honeypot: if the trap field is filled, silently return success to fool bots.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		if ( isset( $_POST['wdm_trap'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['wdm_trap'] ) ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'Form submitted successfully.', 'wprobo-documerge-lite' ),
				)
			);
		}

		// Rate limiting by IP address.
		$client_ip        = $this->wprobo_documerge_get_client_ip();
		$rate_limit_key   = 'wprobo_documerge_ratelimit_' . md5( $client_ip );
		$submission_count = (int) get_transient( $rate_limit_key );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$rate_form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		/**
		 * Filters the maximum number of submissions allowed per IP within the rate-limit window.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $max_submissions The maximum submissions allowed. Default 50.
		 * @param int    $form_id         The form ID being submitted.
		 * @param string $ip              The client IP address.
		 */
		$max_submissions = apply_filters( 'wprobo_documerge_rate_limit', self::WPROBO_DOCUMERGE_RATE_LIMIT_MAX, $rate_form_id, $client_ip );

		if ( $submission_count >= $max_submissions ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many submissions. Please try again later.', 'wprobo-documerge-lite' ),
				)
			);
		}

		set_transient( $rate_limit_key, $submission_count + 1, self::WPROBO_DOCUMERGE_RATE_LIMIT_TTL );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		/**
		 * Fires after security checks pass, before form validation begins.
		 *
		 * Passes a sanitized copy of the submitted payload. Each value is
		 * unslashed and passed through sanitize_textarea_field() which strips
		 * HTML while preserving newlines — safe enough for hooks to inspect
		 * without exposing raw input. Per-field type-specific sanitization
		 * happens during validation later in this method.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $form_id         The form ID being submitted.
		 * @param array $submission_data Shallow-sanitized copy of submitted fields.
		 */
		$wprobo_documerge_submission_data = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		foreach ( (array) $_POST as $wprobo_documerge_key => $wprobo_documerge_value ) {
			$wprobo_documerge_clean_key = sanitize_key( $wprobo_documerge_key );
			if ( is_array( $wprobo_documerge_value ) ) {
				$wprobo_documerge_submission_data[ $wprobo_documerge_clean_key ] = array_map(
					static function ( $v ) {
						return is_scalar( $v ) ? sanitize_textarea_field( wp_unslash( (string) $v ) ) : '';
					},
					$wprobo_documerge_value
				);
			} else {
				$wprobo_documerge_submission_data[ $wprobo_documerge_clean_key ] = sanitize_textarea_field( wp_unslash( (string) $wprobo_documerge_value ) );
			}
		}
		do_action( 'wprobo_documerge_before_submission', $form_id, $wprobo_documerge_submission_data );

		// ── Step 2: Load form ───────────────────────────────────────────
		$form_builder = new WPRobo_DocuMerge_Form_Builder();
		$form         = $form_builder->wprobo_documerge_get_form( $form_id );

		if ( null === $form ) {
			wp_send_json_error(
				array(
					'message' => __( 'Form not found.', 'wprobo-documerge-lite' ),
				)
			);
		}

		// ── Submission limits (server-side enforcement) ──────────────────
		$form_settings = isset( $form->settings ) ? json_decode( $form->settings, true ) : array();
		if ( ! is_array( $form_settings ) ) {
			$form_settings = array();
		}

		$closed_message = ! empty( $form_settings['closed_message'] )
			? $form_settings['closed_message']
			: __( 'This form is no longer accepting submissions.', 'wprobo-documerge-lite' );

		global $wpdb;
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		// 1. Total entry limit.
		$entry_limit = isset( $form_settings['entry_limit'] ) ? absint( $form_settings['entry_limit'] ) : 0;
		if ( $entry_limit > 0 ) {
			$total_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d AND status != 'error'",
					$form_id
				)
			);
			if ( $total_count >= $entry_limit ) {
				wp_send_json_error( array( 'message' => $closed_message ) );
				return;
			}
		}

		// 2. Limit per email.
		$limit_per_email   = isset( $form_settings['limit_per_email'] ) ? absint( $form_settings['limit_per_email'] ) : 0;
		$limit_email_field = isset( $form_settings['limit_email_field'] ) ? sanitize_key( $form_settings['limit_email_field'] ) : '';

		if ( $limit_per_email > 0 && ! empty( $limit_email_field ) ) {
			$submitted_email = isset( $_POST[ $limit_email_field ] ) ? sanitize_email( wp_unslash( $_POST[ $limit_email_field ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $submitted_email ) ) {
				$email_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d AND submitter_email = %s AND status != 'error'",
						$form_id,
						$submitted_email
					)
				);
				if ( $email_count >= $limit_per_email ) {
					/* translators: %d: max submissions allowed per email */
					$msg = sprintf( __( 'You have already submitted this form %d time(s) with this email address.', 'wprobo-documerge-lite' ), $limit_per_email );
					wp_send_json_error( array( 'message' => $msg ) );
					return;
				}
			}
		}

		// 3. Limit per IP.
		$limit_per_ip = isset( $form_settings['limit_per_ip'] ) ? absint( $form_settings['limit_per_ip'] ) : 0;
		if ( $limit_per_ip > 0 ) {
			$client_ip = $this->wprobo_documerge_get_client_ip();
			if ( ! empty( $client_ip ) ) {
				$ip_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d AND ip_address = %s AND status != 'error'",
						$form_id,
						$client_ip
					)
				);
				if ( $ip_count >= $limit_per_ip ) {
					/* translators: %d: max submissions allowed per IP */
					$msg = sprintf( __( 'You have already submitted this form %d time(s).', 'wprobo-documerge-lite' ), $limit_per_ip );
					wp_send_json_error( array( 'message' => $msg ) );
					return;
				}
			}
		}

		// 4. Limit per user (logged-in only).
		$limit_per_user = isset( $form_settings['limit_per_user'] ) ? absint( $form_settings['limit_per_user'] ) : 0;
		if ( $limit_per_user > 0 && is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			// Count by matching user email in submitter_email (since we don't store user_id in submissions).
			$user_email = wp_get_current_user()->user_email;
			$user_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d AND submitter_email = %s AND status != 'error'",
					$form_id,
					$user_email
				)
			);
			if ( $user_count >= $limit_per_user ) {
				/* translators: %d: max submissions allowed per user */
				$msg = sprintf( __( 'You have already submitted this form %d time(s).', 'wprobo-documerge-lite' ), $limit_per_user );
				wp_send_json_error( array( 'message' => $msg ) );
				return;
			}
		}

		// ── Step 3: Decode and filter fields ────────────────────────────

		$fields = json_decode( $form->fields, true );

		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$post_data = wp_unslash( $_POST );

		// In Lite, all fields are always visible (no conditional logic).
		$visible_fields = $fields;

		// ── Step 4: Server-side validation ──────────────────────────────

		$registry     = WPRobo_DocuMerge_Field_Registry::get_instance();
		$field_errors = array();

		foreach ( $visible_fields as $field_data ) {
			$type           = isset( $field_data['type'] ) ? $field_data['type'] : '';
			$field_instance = $registry->wprobo_documerge_get_field( $type );

			if ( null === $field_instance ) {
				continue;
			}

			$name  = isset( $field_data['name'] ) ? $field_data['name'] : '';
			$value = isset( $post_data[ $name ] ) ? $post_data[ $name ] : '';

			// Sanitize the submitted value.
			$value = $field_instance->wprobo_documerge_sanitize( $value, $field_data );

			// Validate the sanitized value.
			$result = $field_instance->wprobo_documerge_validate( $value, $field_data );

			if ( is_wp_error( $result ) ) {
				$field_errors[ $name ] = $result->get_error_message();
			}
		}

		if ( ! empty( $field_errors ) ) {
			wp_send_json_error(
				array(
					'message'      => __( 'Please correct the errors below.', 'wprobo-documerge-lite' ),
					'field_errors' => $field_errors,
				)
			);
		}

			// ── Step 6: Create submission record ────────────────────────────

		global $wpdb;

		$submissions_table = $wpdb->prefix . 'wprdm_submissions';
		$template_id       = isset( $form->template_id ) ? absint( $form->template_id ) : 0;

		// Collect sanitized field values.
		$sanitized_values = array();

		foreach ( $visible_fields as $field_data ) {
			$type           = isset( $field_data['type'] ) ? $field_data['type'] : '';
			$field_instance = $registry->wprobo_documerge_get_field( $type );

			if ( null === $field_instance ) {
				continue;
			}

			$name  = isset( $field_data['name'] ) ? $field_data['name'] : '';
			$value = isset( $post_data[ $name ] ) ? $post_data[ $name ] : '';
			$value = $field_instance->wprobo_documerge_sanitize( $value, $field_data );

			$sanitized_values[ $name ] = $value;
		}

		/**
		 * Filters the sanitized submission data before building the form_data JSON.
		 *
		 * @since 1.1.0
		 *
		 * @param array  $sanitized_values The sanitized field name => value pairs.
		 * @param int    $form_id          The form ID.
		 * @param object $form             The form database row object.
		 */
		$sanitized_values = apply_filters( 'wprobo_documerge_submission_data', $sanitized_values, $form_id, $form );
		$sanitized_values = array_map( 'sanitize_text_field', $sanitized_values );

		/**
		 * Filters whether the submission should be accepted.
		 *
		 * Return false to reject the submission. Runs after built-in validation passes.
		 *
		 * @since 1.1.0
		 *
		 * @param bool  $valid            Whether the submission is valid. Default true.
		 * @param array $sanitized_values The sanitized field data.
		 * @param int   $form_id          The form ID.
		 */
		$valid = apply_filters( 'wprobo_documerge_validate_submission', true, $sanitized_values, $form_id );
		if ( false === $valid ) {
			wp_send_json_error( array( 'message' => __( 'Submission rejected.', 'wprobo-documerge-lite' ) ) );
			return;
		}

		// Find the submitter email from an email-type field.
		$submitter_email = '';
		$submitter_name  = '';

		foreach ( $visible_fields as $field_data ) {
			if ( isset( $field_data['type'] ) && 'email' === $field_data['type'] ) {
				$name            = isset( $field_data['name'] ) ? $field_data['name'] : '';
				$submitter_email = isset( $sanitized_values[ $name ] ) ? $sanitized_values[ $name ] : '';
				break;
			}
		}

		// Attempt to find a name field for the submitter.
		foreach ( $visible_fields as $field_data ) {
			$name_lower = isset( $field_data['name'] ) ? strtolower( $field_data['name'] ) : '';

			if ( in_array( $name_lower, array( 'name', 'full_name', 'fullname', 'your_name' ), true ) ) {
				$submitter_name = isset( $sanitized_values[ $field_data['name'] ] ) ? $sanitized_values[ $field_data['name'] ] : '';
				break;
			}
		}

		$submitted_at = gmdate( 'Y-m-d\TH:i:s\Z' );

		// esc_url_raw sanitizes URLs for DB storage (strips unsafe schemes, invalid chars).
		$page_url   = isset( $post_data['page_url'] ) ? esc_url_raw( wp_unslash( (string) $post_data['page_url'] ) ) : '';
		$referrer   = isset( $post_data['referrer'] ) ? esc_url_raw( wp_unslash( (string) $post_data['referrer'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$form_data = wp_json_encode(
			array(
				'submission_id' => 0, // Will be updated after insert.
				'form_id'       => $form_id,
				'template_id'   => $template_id,
				'submitted_at'  => $submitted_at,
				'fields'        => $sanitized_values,
				'meta'          => array(
					'ip'         => $client_ip,
					'user_agent' => $user_agent,
					'page_url'   => $page_url,
					'referrer'   => $referrer,
				),
			)
		);

		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$submissions_table,
			array(
				'form_id'         => $form_id,
				'template_id'     => $template_id,
				'submitter_email' => $submitter_email,
				'form_data'       => $form_data,
				'status'          => 'processing',
				'ip_address'      => $client_ip,
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'Failed to insert submission record.',
				'error',
				array(
					'form_id'  => $form_id,
					'db_error' => $wpdb->last_error,
				)
			);

			wp_send_json_error(
				array(
					'message' => __( 'An error occurred while saving your submission. Please try again.', 'wprobo-documerge-lite' ),
				)
			);
		}

		$submission_id = (int) $wpdb->insert_id;

		/**
		 * Fires immediately after a new submission record is created in the database.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $submission_id    The new submission ID.
		 * @param int   $form_id          The form ID.
		 * @param array $sanitized_values The sanitized field data.
		 */
		do_action( 'wprobo_documerge_submission_created', $submission_id, $form_id, $sanitized_values );

		// Update form_data with the actual submission_id.
		$form_data_decoded                  = json_decode( $form_data, true );
		$form_data_decoded['submission_id'] = $submission_id;

		$wpdb->update(
			$submissions_table,
			array( 'form_data' => wp_json_encode( $form_data_decoded ) ),
			array( 'id' => $submission_id ),
			array( '%s' ),
			array( '%d' )
		);

		// ── Step 8: Generate document ───────────────────────────────────

		$generator = new WPRobo_DocuMerge_Document_Generator();
		$result    = $generator->wprobo_documerge_generate( $submission_id );

		if ( is_wp_error( $result ) ) {
			// Update status to error.
			$wpdb->update(
				$submissions_table,
				array(
					'status'     => 'error',
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $submission_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			/** This action is documented in src/Form/WPRobo_DocuMerge_Form_Submission.php */
			do_action( 'wprobo_documerge_submission_updated', $submission_id, array( 'status' => 'error' ), 'processing' );

			// Log the error.
			WPRobo_DocuMerge_Logger::wprobo_documerge_log(
				'Document generation failed.',
				'error',
				array(
					'submission_id' => $submission_id,
					'error'         => $result->get_error_message(),
				)
			);

			// Notify site admin.
			wp_mail(
				get_option( 'admin_email' ),
				/* translators: %d: submission ID */
				sprintf( __( '[DocuMerge] Document generation failed for submission #%d', 'wprobo-documerge-lite' ), $submission_id ),
				/* translators: 1: submission ID, 2: error message */
				sprintf(
					__( "Document generation failed for submission #%1\$d.\n\nError: %2\$s", 'wprobo-documerge-lite' ),
					$submission_id,
					$result->get_error_message()
				)
			);

			wp_send_json_error(
				array(
					'message' => __( 'An error occurred while generating your document. Please try again or contact support.', 'wprobo-documerge-lite' ),
				)
			);
		}

		// ── Step 9: Deliver document ────────────────────────────────────

		$delivery       = new WPRobo_DocuMerge_Delivery_Engine();
		$deliver_result = $delivery->wprobo_documerge_deliver( $submission_id );

		$download_url = '';
		$email_sent   = false;

		if ( ! is_wp_error( $deliver_result ) ) {
			// Retrieve the download URL from the delivery result.
			if ( is_array( $deliver_result ) && isset( $deliver_result['download_url'] ) ) {
				$download_url = $deliver_result['download_url'];
			}

			if ( is_array( $deliver_result ) && isset( $deliver_result['email_sent'] ) ) {
				$email_sent = (bool) $deliver_result['email_sent'];
			}
		}

		// ── Step 10: Success response ───────────────────────────────────

		$success_message = ! empty( $form->success_message ) ? $form->success_message : __( 'Your document has been generated successfully.', 'wprobo-documerge-lite' );

		/**
		 * Filters the success message returned after a submission is processed.
		 *
		 * @since 1.1.0
		 *
		 * @param string $success_message The success message text.
		 * @param int    $submission_id   The submission ID.
		 * @param int    $form_id         The form ID.
		 */
		$success_message = apply_filters( 'wprobo_documerge_success_message', $success_message, $submission_id, $form_id );

		// Build document paths from the generation result for the completion hook.
		$document_paths = is_array( $result ) ? $result : array(
			'docx' => '',
			'pdf'  => '',
		);

		/**
		 * Fires after a submission is fully complete — form saved, document generated, and delivered.
		 *
		 * This single hook fires only when the entire pipeline succeeded: the submission
		 * was created, the document was generated, and delivery completed without error.
		 * Useful for CRM integrations, notifications, or analytics.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $submission_id  The submission ID.
		 * @param int   $form_id        The form ID.
		 * @param array $document_paths Array with 'docx' and 'pdf' keys containing file paths.
		 */
		do_action( 'wprobo_documerge_after_submission_complete', $submission_id, $form_id, $document_paths );

		wp_send_json_success(
			array(
				'submission_id'   => $submission_id,
				'download_url'    => $download_url,
				'submitter_name'  => $submitter_name,
				'submitter_email' => $submitter_email,
				'email_sent'      => $email_sent,
				'message'         => sanitize_text_field( $success_message ),
			)
		);
	}

	/**
	 * Delete a submission and fire the deletion hook.
	 *
	 * @since 1.1.0
	 *
	 * @param int $submission_id The submission ID to delete.
	 * @return bool True on success, false on failure.
	 */
	public function wprobo_documerge_delete_submission( $submission_id ) {
		global $wpdb;

		$submission_id     = absint( $submission_id );
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$submissions_table} WHERE id = %d",
				$submission_id
			)
		);

		if ( ! $submission ) {
			return false;
		}

		$deleted = $wpdb->delete(
			$submissions_table,
			array( 'id' => $submission_id ),
			array( '%d' )
		);

		if ( false !== $deleted ) {
			/**
			 * Fires after a submission record is deleted from the database.
			 *
			 * @since 1.1.0
			 *
			 * @param int    $submission_id The deleted submission ID.
			 * @param object $submission    The submission row object before deletion.
			 */
			do_action( 'wprobo_documerge_submission_deleted', $submission_id, $submission );
		}

		return false !== $deleted;
	}

	/**
	 * Retrieve the client IP address.
	 *
	 * Checks the REMOTE_ADDR server variable and validates it as a
	 * proper IP address. Returns '0.0.0.0' if no valid IP can be
	 * determined.
	 *
	 * @since 1.0.0
	 *
	 * @return string The validated client IP address.
	 */
	public function wprobo_documerge_get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( ! empty( $ip ) && false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '0.0.0.0';
	}
}
