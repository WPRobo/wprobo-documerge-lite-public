<?php
/**
 * Template Manager — slide-in panel for uploading / editing templates.
 *
 * Included by templates/admin/template-manager/main.php.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/template-manager
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wdm-slide-panel" id="wdm-template-panel">

	<!-- ── Panel Header ────────────────────────────────────────── -->
	<div class="wdm-slide-panel-header">
		<h2 id="wdm-panel-title"><?php esc_html_e( 'Upload New Template', 'wprobo-documerge-lite' ); ?></h2>
		<button type="button" class="wdm-slide-panel-close" id="wdm-panel-close" aria-label="<?php esc_attr_e( 'Close', 'wprobo-documerge-lite' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<!-- ── Panel Notices ───────────────────────────────────────── -->
	<div class="wdm-panel-notices" id="wdm-panel-notices"></div>

	<!-- ── Panel Body ──────────────────────────────────────────── -->
	<div class="wdm-slide-panel-body">

		<input type="hidden" id="wdm-template-id" value="0">
		<input type="hidden" id="wdm-template-file-path" value="">

		<!-- Template Name -->
		<div class="wdm-field-group">
			<label for="wdm-template-name"><?php esc_html_e( 'Template Name', 'wprobo-documerge-lite' ); ?> <span class="wdm-required">*</span></label>
			<input type="text" id="wdm-template-name" class="wdm-input" required placeholder="<?php esc_attr_e( 'e.g. Invoice Template', 'wprobo-documerge-lite' ); ?>">
		</div>

		<!-- Description -->
		<div class="wdm-field-group">
			<label for="wdm-template-description"><?php esc_html_e( 'Description', 'wprobo-documerge-lite' ); ?></label>
			<textarea id="wdm-template-description" class="wdm-textarea" rows="3" placeholder="<?php esc_attr_e( 'Optional description for this template', 'wprobo-documerge-lite' ); ?>"></textarea>
		</div>

		<!-- Upload DOCX -->
		<div class="wdm-field-group">
			<label><?php esc_html_e( 'Upload DOCX File', 'wprobo-documerge-lite' ); ?></label>
			<div class="wdm-dropzone" id="wdm-dropzone">
				<span class="dashicons dashicons-upload"></span>
				<p class="wdm-dropzone-text"><?php esc_html_e( 'Drag & drop your .docx file here, or click to browse', 'wprobo-documerge-lite' ); ?></p>
				<p class="wdm-dropzone-hint"><?php esc_html_e( 'Maximum file size: 10 MB', 'wprobo-documerge-lite' ); ?></p>
				<span class="wdm-btn wdm-btn-secondary wdm-btn-sm wdm-dropzone-browse"><?php esc_html_e( 'Choose File', 'wprobo-documerge-lite' ); ?></span>
				<input type="file" id="wdm-template-file" accept=".docx" class="wdm-dropzone-input">
			</div>
		</div>

		<!-- Upload Progress -->
		<div class="wdm-progress-wrap wdm-hidden" id="wdm-upload-progress">
			<div class="wdm-progress-bar">
				<div class="wdm-progress-bar-fill" id="wdm-progress-fill"></div>
			</div>
			<span class="wdm-progress-text" id="wdm-progress-text">0%</span>
		</div>

		<!-- Uploaded File Info -->
		<div class="wdm-uploaded-file wdm-hidden" id="wdm-uploaded-file">
			<span class="dashicons dashicons-media-document"></span>
			<span class="wdm-uploaded-file-name" id="wdm-uploaded-file-name"></span>
		</div>

		<!-- Output Format -->
		<div class="wdm-field-group">
			<label><?php esc_html_e( 'Output Format', 'wprobo-documerge-lite' ); ?></label>
			<div class="wdm-radio-group">
				<label class="wdm-radio-label">
					<input type="radio" name="wdm_output_format" value="docx">
					<?php esc_html_e( 'DOCX', 'wprobo-documerge-lite' ); ?>
				</label>
				<label class="wdm-radio-label">
					<input type="radio" name="wdm_output_format" value="pdf" checked>
					<?php esc_html_e( 'PDF', 'wprobo-documerge-lite' ); ?>
				</label>
				<label class="wdm-radio-label">
					<input type="radio" name="wdm_output_format" value="both">
					<?php esc_html_e( 'Both', 'wprobo-documerge-lite' ); ?>
				</label>
			</div>
		</div>

		<!-- Detected Merge Tags -->
		<div class="wdm-detected-tags wdm-hidden" id="wdm-detected-tags">
			<h3><?php esc_html_e( 'Detected Merge Tags', 'wprobo-documerge-lite' ); ?></h3>
			<p class="wdm-tag-count" id="wdm-tag-count"></p>
			<ul class="wdm-tag-list" id="wdm-tag-list"></ul>
		</div>

	</div>

	<!-- ── Panel Footer ────────────────────────────────────────── -->
	<div class="wdm-slide-panel-footer">
		<button type="button" class="wdm-btn" id="wdm-panel-cancel">
			<?php esc_html_e( 'Cancel', 'wprobo-documerge-lite' ); ?>
		</button>
		<button type="button" class="wdm-btn wdm-btn-primary" id="wdm-save-template">
			<?php esc_html_e( 'Save Template', 'wprobo-documerge-lite' ); ?>
		</button>
	</div>

</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
