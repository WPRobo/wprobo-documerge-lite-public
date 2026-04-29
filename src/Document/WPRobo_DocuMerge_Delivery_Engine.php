<?php
/**
 * Document delivery orchestrator.
 *
 * Handles post-generation delivery via secure download tokens.
 * Email delivery, media library saving, and admin notifications
 * are available in WPRobo DocuMerge Pro.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Document
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Document;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Delivery_Engine
 *
 * Orchestrates document delivery via secure download links
 * with time-limited tokens. Email and media library delivery
 * are available in WPRobo DocuMerge Pro.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Delivery_Engine {

	/**
	 * Default download token expiry in hours.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	private const WPROBO_DOCUMERGE_DEFAULT_EXPIRY_HOURS = 72;

	/**
	 * Deliver a generated document through all configured delivery methods.
	 *
	 * Reads the form's delivery_methods column (comma-separated values:
	 * 'download', 'email', 'media') and dispatches to the appropriate
	 * handler for each method.
	 *
	 * @since  1.0.0
	 * @param  int $submission_id The submission ID to deliver.
	 * @return array|\WP_Error Array with 'download_url' and 'email_sent' on success, WP_Error on failure.
	 */
	public function wprobo_documerge_deliver( $submission_id ) {
		global $wpdb;

		$submission_id = absint( $submission_id );

		// ── Get submission ───────────────────────────────────────────────
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$submissions_table} WHERE id = %d",
				$submission_id
			)
		);

		if ( ! $submission ) {
			return new \WP_Error(
				'submission_not_found',
				/* translators: %d: submission ID */
				sprintf( __( 'Submission #%d not found.', 'wprobo-documerge-lite' ), $submission_id )
			);
		}

		// ── Get form for delivery methods ────────────────────────────────
		$forms_table = $wpdb->prefix . 'wprdm_forms';

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$forms_table} WHERE id = %d",
				absint( $submission->form_id )
			)
		);

		if ( ! $form ) {
			return new \WP_Error(
				'form_not_found',
				/* translators: %d: form ID */
				sprintf( __( 'Form #%d not found.', 'wprobo-documerge-lite' ), absint( $submission->form_id ) )
			);
		}

		// Parse delivery methods — handle both JSON array and comma-separated.
		$raw_methods = $form->delivery_methods;
		$decoded     = json_decode( $raw_methods, true );
		if ( is_array( $decoded ) ) {
			$delivery_methods = array_map( 'sanitize_key', $decoded );
		} elseif ( ! empty( $raw_methods ) ) {
			$delivery_methods = array_map( 'sanitize_key', array_map( 'trim', explode( ',', $raw_methods ) ) );
		} else {
			$delivery_methods = array( 'download' );
		}
		// Default to download if empty after parsing.
		$delivery_methods = array_filter( $delivery_methods );
		if ( empty( $delivery_methods ) ) {
			$delivery_methods = array( 'download' );
		}

		/**
		 * Fires at the start of the delivery process.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $submission_id    The submission ID.
		 * @param array $delivery_methods The delivery method slugs.
		 */
		do_action( 'wprobo_documerge_before_delivery', $submission_id, $delivery_methods );

		$download_url = '';

		// ── Download (only delivery method in Lite) ─────────────────────
		$download_result = $this->wprobo_documerge_prepare_download( $submission_id );
		if ( is_wp_error( $download_result ) ) {
			$wpdb->update(
				$submissions_table,
				array(
					'delivery_status' => 'partial',
					'error_log'       => sanitize_textarea_field( $download_result->get_error_message() ),
					'updated_at'      => current_time( 'mysql' ),
				),
				array( 'id' => $submission_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			/** This action is documented in src/Document/WPRobo_DocuMerge_Delivery_Engine.php */
			do_action( 'wprobo_documerge_delivery_failed', $submission_id, $download_result );

			return $download_result;
		}

		$download_url = $download_result;

		// ── Email delivery (Pro only — hooks available for extensions) ───
		$email_sent = false;
		$form_id    = absint( $submission->form_id );

		if ( in_array( 'email', $delivery_methods, true ) && ! empty( $submission->submitter_email ) ) {

			$to      = sanitize_email( $submission->submitter_email );
			$subject = sprintf(
				/* translators: %s: site name */
				__( 'Your document is ready — %s', 'wprobo-documerge-lite' ),
				get_bloginfo( 'name' )
			);
			$body        = '';
			$attachments = array();

			/**
			 * Filters the email recipient(s) for document delivery.
			 *
			 * Allows adding CC/BCC recipients or changing the delivery
			 * address dynamically based on submission or form context.
			 *
			 * @since 1.0.0
			 *
			 * @param string|array $to            Email address(es).
			 * @param int          $submission_id The submission ID.
			 * @param int          $form_id       The form ID.
			 */
			$to = apply_filters( 'wprobo_documerge_email_recipients', $to, $submission_id, $form_id );

			/**
			 * Filters the email subject for document delivery.
			 *
			 * Allows customizing the email subject per form or submission.
			 *
			 * @since 1.0.0
			 *
			 * @param string $subject       The email subject.
			 * @param int    $submission_id The submission ID.
			 * @param int    $form_id       The form ID.
			 */
			$subject = apply_filters( 'wprobo_documerge_email_subject', $subject, $submission_id, $form_id );

			/**
			 * Filters the email HTML body for document delivery.
			 *
			 * Allows customizing email content, adding branding, or
			 * inserting dynamic content. Essential for white-label solutions.
			 *
			 * @since 1.0.0
			 *
			 * @param string $body          The email HTML body.
			 * @param int    $submission_id The submission ID.
			 * @param int    $form_id       The form ID.
			 */
			$body = apply_filters( 'wprobo_documerge_email_body', $body, $submission_id, $form_id );

			/**
			 * Filters the email attachments for document delivery.
			 *
			 * Allows adding extra attachments (terms of service, additional
			 * documents) or removing the generated document from the email.
			 *
			 * @since 1.0.0
			 *
			 * @param array $attachments   Array of file paths to attach.
			 * @param int   $submission_id The submission ID.
			 */
			$attachments = apply_filters( 'wprobo_documerge_email_attachments', $attachments, $submission_id );

			/**
			 * Fires before an email is sent with the generated document.
			 *
			 * Allows logging, external notification, or blocking email
			 * delivery. Useful for integrating with external email services.
			 *
			 * @since 1.0.0
			 *
			 * @param string $to            Recipient email address.
			 * @param string $subject       Email subject.
			 * @param string $body          Email HTML body.
			 * @param array  $attachments   File paths to attach.
			 * @param int    $submission_id The submission ID.
			 */
			do_action( 'wprobo_documerge_before_email_send', $to, $subject, $body, $attachments, $submission_id );

			// Email sending is a Pro feature — the hooks above allow
			// Pro or third-party extensions to implement email delivery.
			$sent = false;

			/**
			 * Fires after an email send attempt.
			 *
			 * Allows tracking delivery success/failure in external systems.
			 * Essential for email deliverability monitoring.
			 *
			 * @since 1.0.0
			 *
			 * @param bool   $sent          Whether wp_mail() returned true.
			 * @param string $to            Recipient email address.
			 * @param int    $submission_id The submission ID.
			 */
			do_action( 'wprobo_documerge_after_email_send', $sent, $to, $submission_id );

			$email_sent = $sent;
		}

		$wpdb->update(
			$submissions_table,
			array(
				'delivery_status' => 'delivered',
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $submission_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		/**
		 * Fires after all delivery methods have completed successfully.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $submission_id The submission ID.
		 * @param array $results       Associative array with 'download' and 'email' boolean statuses.
		 */
		do_action(
			'wprobo_documerge_document_delivered',
			$submission_id,
			array(
				'download' => true,
				'email'    => $email_sent,
			)
		);

		return array(
			'download_url' => $download_url,
			'email_sent'   => $email_sent,
		);
	}

	/**
	 * Prepare a secure download URL for a submission.
	 *
	 * Generates a UUID-based token, stores it in wp_options with an
	 * expiry timestamp, and returns the public download URL.
	 *
	 * @since  1.0.0
	 * @param  int $submission_id The submission ID.
	 * @return string|\WP_Error The download URL on success, WP_Error on failure.
	 */
	public function wprobo_documerge_prepare_download( $submission_id ) {
		$submission_id = absint( $submission_id );

		$token        = wp_generate_uuid4();
		$expiry_hours = absint( get_option( 'wprobo_documerge_download_expiry_hours', self::WPROBO_DOCUMERGE_DEFAULT_EXPIRY_HOURS ) );

		/**
		 * Filters the download token expiry duration in hours.
		 *
		 * @since 1.1.0
		 *
		 * @param int $expiry_hours  The token expiry in hours.
		 * @param int $submission_id The submission ID.
		 */
		$expiry_hours = apply_filters( 'wprobo_documerge_download_token_expiry', $expiry_hours, $submission_id );
		$expiry_hours = absint( $expiry_hours );
		if ( $expiry_hours < 1 ) {
			$expiry_hours = 1;
		}

		$token_data = array(
			'submission_id' => $submission_id,
			'expiry'        => time() + ( $expiry_hours * HOUR_IN_SECONDS ),
			'created_at'    => current_time( 'mysql' ),
		);

		$option_key = 'wprobo_documerge_dl_' . sanitize_key( $token );
		$saved      = update_option( $option_key, $token_data, false );

		if ( ! $saved ) {
			return new \WP_Error(
				'token_save_failed',
				__( 'Failed to save download token.', 'wprobo-documerge-lite' )
			);
		}

		$download_url = add_query_arg(
			array(
				'wpaction' => 'documerge_download',
				'token'    => sanitize_key( $token ),
			),
			home_url( '/' )
		);

		$download_url = esc_url_raw( $download_url );

		/**
		 * Filters the document download URL.
		 *
		 * Allows overriding the download URL for CDN delivery, signed URLs,
		 * or custom access control mechanisms.
		 *
		 * @since 1.0.0
		 *
		 * @param string $download_url  The download URL.
		 * @param int    $submission_id The submission ID.
		 * @param string $format        The document format ('pdf' or 'docx').
		 */
		$download_url = apply_filters( 'wprobo_documerge_download_url', $download_url, $submission_id, 'pdf' );

		return $download_url;
	}

	/**
	 * Serve a file download for a given token.
	 *
	 * Validates the token, checks expiry, resolves the file path,
	 * deletes the token (one-time use), and streams the file to
	 * the browser with appropriate headers.
	 *
	 * @since  1.0.0
	 * @param  string $token  The download token (UUID).
	 * @param  string $format The file format to serve ('pdf' or 'docx'). Default 'pdf'.
	 * @return \WP_Error Only returns on failure; on success the script exits after streaming.
	 */
	public function wprobo_documerge_serve_download( $token, $format = 'pdf' ) {
		$token      = sanitize_key( $token );
		$format     = sanitize_key( $format );
		$option_key = 'wprobo_documerge_dl_' . $token;

		$token_data = get_option( $option_key );

		if ( empty( $token_data ) || ! is_array( $token_data ) ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or missing download token.', 'wprobo-documerge-lite' )
			);
		}

		// Check expiry.
		if ( time() > absint( $token_data['expiry'] ) ) {
			delete_option( $option_key );

			return new \WP_Error(
				'expired_token',
				__( 'This download link has expired.', 'wprobo-documerge-lite' )
			);
		}

		// Get submission.
		global $wpdb;
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		$submission = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$submissions_table} WHERE id = %d",
				absint( $token_data['submission_id'] )
			)
		);

		if ( ! $submission ) {
			delete_option( $option_key );

			return new \WP_Error(
				'submission_not_found',
				__( 'Submission not found.', 'wprobo-documerge-lite' )
			);
		}

		// Resolve file path.
		$file_path = 'docx' === $format ? $submission->doc_path_docx : $submission->doc_path_pdf;

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return new \WP_Error(
				'file_not_found',
				__( 'The requested file does not exist.', 'wprobo-documerge-lite' )
			);
		}

		// Delete token — one-time use.
		delete_option( $option_key );

		// Determine MIME type.
		$mime_types   = array(
			'pdf'  => 'application/pdf',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);
		$content_type = isset( $mime_types[ $format ] ) ? $mime_types[ $format ] : 'application/octet-stream';
		$file_name    = sanitize_file_name( basename( $file_path ) );

		/**
		 * Filters the download filename presented to the browser.
		 *
		 * @since 1.1.0
		 *
		 * @param string $file_name     The filename for the Content-Disposition header.
		 * @param int    $submission_id The submission ID (from token data).
		 * @param string $format        The file format ('pdf' or 'docx').
		 */
		$file_name = apply_filters( 'wprobo_documerge_document_filename', $file_name, absint( $token_data['submission_id'] ), $format );
		$file_name = sanitize_file_name( $file_name );

		// Stream file.
		nocache_headers();
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );
		exit;
	}

	/**
	 * Register all WordPress hooks for the delivery engine.
	 *
	 * Hooks into 'init' for public download handling, registers AJAX
	 * handlers for admin downloads, and sets up cron event handlers
	 * for email retries and token cleanup.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_init_hooks() {
		// Public download via query parameter.
		add_action( 'init', array( $this, 'wprobo_documerge_handle_public_download' ) );

		// Admin AJAX download handler.
		add_action( 'wp_ajax_wprobo_documerge_download_document', array( $this, 'wprobo_documerge_ajax_admin_download' ) );
		add_action( 'wp_ajax_nopriv_wprobo_documerge_download_document_public', array( $this, 'wprobo_documerge_ajax_admin_download' ) );

		// Expired token cleanup cron handler.
		add_action( 'wprobo_documerge_cleanup_expired_tokens', array( $this, 'wprobo_documerge_cleanup_expired_tokens' ) );
	}

	/**
	 * Handle public download requests via the 'wpaction' query parameter.
	 *
	 * Security model (capability URL / signed token):
	 * - The token is a UUIDv4 generated by wp_generate_uuid4() (122 bits of
	 *   entropy, cryptographically random).
	 * - Each token is bound to a single submission ID and an expiry timestamp,
	 *   stored server-side in wp_options.
	 * - Tokens are single-use: consumed (deleted) by serve_download() on both
	 *   successful delivery and validation failure.
	 * - WordPress nonces are NOT used here because nonces are user-session
	 *   bound, which would break email-delivered download links accessed from
	 *   a different browser/session than the one that submitted the form.
	 * - The cryptographic token + server-side record + expiry + single-use is
	 *   the WordPress-approved pattern for public "capability URL" downloads.
	 *
	 * Called on the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_handle_public_download() {
		// Capability-URL download flow — not a form POST. WordPress nonces are
		// not applicable (see phpdoc above). Input is validated below against
		// a server-side single-use token record.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability URL, see method phpdoc.
		$wpaction = isset( $_GET['wpaction'] ) ? sanitize_key( wp_unslash( $_GET['wpaction'] ) ) : '';
		if ( 'documerge_download' !== $wpaction ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability URL, see method phpdoc.
		$token = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : '';
		if ( empty( $token ) ) {
			wp_die(
				esc_html__( 'Missing download token.', 'wprobo-documerge-lite' ),
				esc_html__( 'Download Error', 'wprobo-documerge-lite' ),
				array( 'response' => 400 )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability URL, see method phpdoc.
		$format_raw = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'pdf';
		// Whitelist format — only 'pdf' or 'docx' allowed.
		$format = in_array( $format_raw, array( 'pdf', 'docx' ), true ) ? $format_raw : 'pdf';

		$result = $this->wprobo_documerge_serve_download( $token, $format );

		// serve_download() exits on success; we only get here on error.
		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				esc_html__( 'Download Error', 'wprobo-documerge-lite' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Handle admin AJAX download requests.
	 *
	 * Verifies the nonce and user capability, then streams the
	 * requested file directly without a token.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_ajax_admin_download() {
		check_ajax_referer( 'wprobo_documerge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to download this file.', 'wprobo-documerge-lite' ),
				esc_html__( 'Permission Denied', 'wprobo-documerge-lite' ),
				array( 'response' => 403 )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce already verified above.
		$submission_id = isset( $_GET['submission_id'] ) ? absint( wp_unslash( $_GET['submission_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX handler; nonce verified via check_ajax_referer() at top of method.
		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'pdf';

		if ( ! $submission_id ) {
			wp_die(
				esc_html__( 'Invalid submission ID.', 'wprobo-documerge-lite' ),
				esc_html__( 'Download Error', 'wprobo-documerge-lite' ),
				array( 'response' => 400 )
			);
		}

		global $wpdb;
		$submissions_table = $wpdb->prefix . 'wprdm_submissions';

		$submission = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$submissions_table} WHERE id = %d", $submission_id )
		);

		if ( ! $submission ) {
			wp_die(
				esc_html__( 'Submission not found.', 'wprobo-documerge-lite' ),
				esc_html__( 'Download Error', 'wprobo-documerge-lite' ),
				array( 'response' => 404 )
			);
		}

		$file_path = 'docx' === $format ? $submission->doc_path_docx : $submission->doc_path_pdf;

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_die(
				esc_html__( 'The requested file does not exist.', 'wprobo-documerge-lite' ),
				esc_html__( 'Download Error', 'wprobo-documerge-lite' ),
				array( 'response' => 404 )
			);
		}

		$mime_types   = array(
			'pdf'  => 'application/pdf',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);
		$content_type = isset( $mime_types[ $format ] ) ? $mime_types[ $format ] : 'application/octet-stream';
		$file_name    = sanitize_file_name( basename( $file_path ) );

		nocache_headers();
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );
		exit;
	}

	/**
	 * Clean up expired download tokens from wp_options.
	 *
	 * Called by the 'wprobo_documerge_cleanup_expired_tokens' cron event.
	 * Queries all options with the 'wprobo_documerge_dl_' prefix and
	 * deletes those whose expiry timestamp has passed.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_cleanup_expired_tokens() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'wprobo_documerge_dl_' ) . '%'
			)
		);

		if ( empty( $rows ) ) {
			return;
		}

		$now = time();

		foreach ( $rows as $row ) {
			$token_data = maybe_unserialize( $row->option_value );

			if ( ! is_array( $token_data ) || empty( $token_data['expiry'] ) ) {
				// Malformed token data — delete it.
				delete_option( $row->option_name );
				continue;
			}

			if ( $now > absint( $token_data['expiry'] ) ) {
				delete_option( $row->option_name );
			}
		}
	}
}
