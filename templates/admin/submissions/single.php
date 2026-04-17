<?php
/**
 * Single Submission full-page view template.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/submissions
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.4.0
 *
 * Variables available:
 * @var object $submission  The submission row with joined form_title + template_name.
 * @var array  $form_data   Decoded form_data JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$fields = isset( $form_data['fields'] ) ? $form_data['fields'] : $form_data;
$meta   = isset( $form_data['meta'] ) ? $form_data['meta'] : array();

// Status badge map.
$status_map = array(
	'completed'  => 'success',
	'processing' => 'info',
	'error'      => 'error',
);
$badge      = isset( $status_map[ $submission->status ] ) ? $status_map[ $submission->status ] : 'info';
?>
<div class="wdm-admin-wrap">

	<!-- Header with back link -->
	<div class="wdm-page-header">
		<div class="wdm-page-header-left">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-submissions' ) ); ?>" class="wdm-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<?php esc_html_e( 'Back to Submissions', 'wprobo-documerge-lite' ); ?>
			</a>
			<div class="wdm-page-header-text" style="margin-left:12px;">
				<h1 class="wdm-page-title">
					<?php
					printf(
						/* translators: %d: submission ID */
						esc_html__( 'Submission #%d', 'wprobo-documerge-lite' ),
						absint( $submission->id )
					);
					?>
					<span class="wdm-badge wdm-badge-<?php echo esc_attr( $badge ); ?>" style="margin-left:8px;vertical-align:middle;">
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $submission->status ) ) ); ?>
					</span>
				</h1>
				<p class="wdm-page-subtitle">
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->created_at ) ) ); ?>
					&mdash; <?php echo esc_html( $submission->form_title ? $submission->form_title : __( 'Unknown Form', 'wprobo-documerge-lite' ) ); ?>
				</p>
			</div>
		</div>
		<div class="wdm-page-header-right">
			<?php if ( ! empty( $submission->doc_path_pdf ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wprobo_documerge_download_document&submission_id=' . absint( $submission->id ) . '&format=pdf&nonce=' . wp_create_nonce( 'wprobo_documerge_admin' ) ) ); ?>" class="wdm-btn wdm-btn-primary" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Download PDF', 'wprobo-documerge-lite' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $submission->doc_path_docx ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wprobo_documerge_download_document&submission_id=' . absint( $submission->id ) . '&format=docx&nonce=' . wp_create_nonce( 'wprobo_documerge_admin' ) ) ); ?>" class="wdm-btn" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Download DOCX', 'wprobo-documerge-lite' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="wdm-notices-wrap" id="wdm-notices"></div>

	<div class="wdm-submission-view">
		<!-- Left column: submitted data -->
		<div class="wdm-submission-main">

			<!-- Submitted Fields Card -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-list-view"></span>
					<div>
						<h3><?php esc_html_e( 'Submitted Data', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'All field values from this submission.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<?php if ( ! empty( $fields ) && is_array( $fields ) ) : ?>
						<table class="wdm-submission-fields-table">
							<?php foreach ( $fields as $key => $value ) : ?>
								<tr>
									<td class="wdm-field-label-col"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></td>
									<td class="wdm-field-value-col">
										<?php if ( is_string( $value ) && strpos( $value, 'data:image/' ) === 0 ) : ?>
											<img src="<?php echo esc_attr( $value ); ?>" alt="<?php esc_attr_e( 'Signature', 'wprobo-documerge-lite' ); ?>" style="max-width:250px;height:auto;border:1px solid #dde5f0;border-radius:6px;padding:8px;background:#fff;">
										<?php elseif ( is_array( $value ) ) : ?>
											<pre class="wdm-json-value"><?php echo esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT ) ); ?></pre>
										<?php elseif ( is_string( $value ) && strlen( $value ) > 0 && ( '{' === $value[0] || '[' === $value[0] ) ) : ?>
											<pre class="wdm-json-value"><?php echo esc_html( $value ); ?></pre>
										<?php else : ?>
											<?php echo esc_html( $value ? $value : '—' ); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					<?php else : ?>
						<p class="wdm-text-muted"><?php esc_html_e( 'No field data recorded.', 'wprobo-documerge-lite' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Error Log Card (if applicable) -->
			<?php if ( ! empty( $submission->error_log ) ) : ?>
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-warning"></span>
					<div>
						<h3><?php esc_html_e( 'Error Log', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Error details for this submission.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<pre class="wdm-json-value"><?php echo esc_html( $submission->error_log ); ?></pre>
				</div>
			</div>
			<?php endif; ?>

			<!-- Admin Notes Card -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-edit"></span>
					<div>
						<h3><?php esc_html_e( 'Admin Notes', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Internal notes visible only to administrators.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<textarea class="wdm-input wdm-textarea wdm-admin-notes" id="wdm-admin-note-<?php echo absint( $submission->id ); ?>"
								rows="4" placeholder="<?php esc_attr_e( 'Add internal notes about this submission...', 'wprobo-documerge-lite' ); ?>"><?php echo esc_textarea( isset( $submission->admin_notes ) ? $submission->admin_notes : '' ); ?></textarea>
					<button type="button" class="wdm-btn wdm-btn-primary wdm-save-note" data-id="<?php echo absint( $submission->id ); ?>" style="margin-top:10px;">
						<?php esc_html_e( 'Save Note', 'wprobo-documerge-lite' ); ?>
					</button>
				</div>
			</div>

		</div>

		<!-- Right sidebar: meta info -->
		<div class="wdm-submission-sidebar">

			<!-- Submission Info Card -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-info"></span>
					<div><h3><?php esc_html_e( 'Submission Info', 'wprobo-documerge-lite' ); ?></h3></div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-meta-list">
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'ID', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value">#<?php echo absint( $submission->id ); ?></span>
						</div>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'Form', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><?php echo esc_html( $submission->form_title ? $submission->form_title : '—' ); ?></span>
						</div>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'Template', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><?php echo esc_html( $submission->template_name ? $submission->template_name : '—' ); ?></span>
						</div>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'Email', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><?php echo esc_html( $submission->submitter_email ? $submission->submitter_email : '—' ); ?></span>
						</div>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'IP Address', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><code><?php echo esc_html( $submission->ip_address ? $submission->ip_address : '—' ); ?></code></span>
						</div>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'Submitted', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->created_at ) ) ); ?></span>
						</div>
						<?php if ( isset( $submission->delivery_status ) && ! empty( $submission->delivery_status ) ) : ?>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php esc_html_e( 'Delivery', 'wprobo-documerge-lite' ); ?></span>
							<span class="wdm-meta-value"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $submission->delivery_status ) ) ); ?></span>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Tracking / Meta Card -->
			<?php if ( ! empty( $meta ) ) : ?>
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-chart-area"></span>
					<div><h3><?php esc_html_e( 'Tracking Data', 'wprobo-documerge-lite' ); ?></h3></div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-meta-list">
						<?php foreach ( $meta as $meta_key => $meta_val ) : ?>
						<div class="wdm-meta-item">
							<span class="wdm-meta-label"><?php echo esc_html( ucwords( str_replace( '_', ' ', $meta_key ) ) ); ?></span>
							<span class="wdm-meta-value" style="word-break:break-all;"><?php echo esc_html( $meta_val ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Actions Card -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-body" style="text-align:center;">
					<button type="button" class="wdm-btn wdm-btn-danger wdm-delete-submission-single" data-id="<?php echo absint( $submission->id ); ?>">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Submission', 'wprobo-documerge-lite' ); ?>
					</button>
				</div>
			</div>

		</div>
	</div>

</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
