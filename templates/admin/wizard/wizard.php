<?php
/**
 * Setup Wizard — full-screen template.
 *
 * 4-step wizard: Welcome → Detect → Configure → Done.
 * Navigation handled client-side via jQuery (no page reload).
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/wizard
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'DocuMerge Setup', 'wprobo-documerge-lite' ); ?></title>
	<?php wp_print_styles( 'wprobo-documerge-wizard' ); ?>
</head>
<body class="wdm-wizard-body">

<div class="wdm-wizard-wrap">

	<!-- ── Header ──────────────────────────────────────────────── -->
	<div class="wdm-wizard-header">
		<div class="wdm-wizard-logo">
			<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<rect width="36" height="36" rx="8" fill="#042157"/>
				<path d="M10 8h12a2 2 0 012 2v16a2 2 0 01-2 2H10a2 2 0 01-2-2V10a2 2 0 012-2z" fill="#ffffff" opacity="0.9"/>
				<path d="M12 14h8M12 18h6M12 22h4" stroke="#042157" stroke-width="1.5" stroke-linecap="round"/>
				<path d="M26 12l-4 4 4 4" stroke="#166441" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			<span class="wdm-wizard-logo-text"><?php esc_html_e( 'DocuMerge Setup', 'wprobo-documerge-lite' ); ?></span>
		</div>
	</div>

	<!-- ── Progress Bar ────────────────────────────────────────── -->
	<div class="wdm-wizard-progress">
		<div class="wdm-wizard-steps-track">
			<div class="wdm-wizard-progress-line">
				<div class="wdm-wizard-progress-fill" id="wdm-progress-fill"></div>
			</div>
			<div class="wdm-wizard-step-indicators">
				<div class="wdm-wizard-step-dot wdm-active" data-step="1">
					<span class="wdm-dot-circle"></span>
					<span class="wdm-dot-label"><?php esc_html_e( 'Welcome', 'wprobo-documerge-lite' ); ?></span>
				</div>
				<div class="wdm-wizard-step-dot" data-step="2">
					<span class="wdm-dot-circle"></span>
					<span class="wdm-dot-label"><?php esc_html_e( 'Detect', 'wprobo-documerge-lite' ); ?></span>
				</div>
				<div class="wdm-wizard-step-dot" data-step="3">
					<span class="wdm-dot-circle"></span>
					<span class="wdm-dot-label"><?php esc_html_e( 'Configure', 'wprobo-documerge-lite' ); ?></span>
				</div>
				<div class="wdm-wizard-step-dot" data-step="4">
					<span class="wdm-dot-circle"></span>
					<span class="wdm-dot-label"><?php esc_html_e( 'Done', 'wprobo-documerge-lite' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- ── Step Content Container ──────────────────────────────── -->
	<div class="wdm-wizard-content">

		<!-- ── STEP 1: Welcome ─────────────────────────────────── -->
		<div class="wdm-wizard-step wdm-step-active" data-step="1">
			<div class="wdm-wizard-step-inner">
				<div class="wdm-wizard-illustration">
					<svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<circle cx="60" cy="60" r="56" fill="#eaf4ee" stroke="#166441" stroke-width="2"/>
						<rect x="35" y="25" width="40" height="52" rx="4" fill="#ffffff" stroke="#166441" stroke-width="2"/>
						<path d="M43 38h24M43 46h18M43 54h12M43 62h20" stroke="#042157" stroke-width="2" stroke-linecap="round"/>
						<path d="M68 58l8 8-8 8" stroke="#166441" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
						<rect x="78" y="55" width="18" height="22" rx="3" fill="#166441" opacity="0.15" stroke="#166441" stroke-width="1.5"/>
						<path d="M83 63h8M83 68h6M83 73h4" stroke="#166441" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
				</div>
				<h2 class="wdm-wizard-title"><?php esc_html_e( 'Welcome to WPRobo DocuMerge', 'wprobo-documerge-lite' ); ?></h2>
				<p class="wdm-wizard-subtitle"><?php esc_html_e( 'Automate your document generation in minutes.', 'wprobo-documerge-lite' ); ?></p>
				<ul class="wdm-wizard-features">
					<li>
						<span class="dashicons dashicons-media-document"></span>
						<?php esc_html_e( 'Upload Word/DOCX templates with merge tags', 'wprobo-documerge-lite' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-forms"></span>
						<?php esc_html_e( 'Connect to your existing forms or use our built-in form builder', 'wprobo-documerge-lite' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-email-alt"></span>
						<?php esc_html_e( 'Deliver personalised documents automatically on every submission', 'wprobo-documerge-lite' ); ?>
					</li>
				</ul>
			</div>
		</div>

		<!-- ── STEP 2: Detect ──────────────────────────────────── -->
		<div class="wdm-wizard-step" data-step="2">
			<div class="wdm-wizard-step-inner">
				<h2 class="wdm-wizard-title"><?php esc_html_e( 'Form Builder', 'wprobo-documerge-lite' ); ?></h2>
				<p class="wdm-wizard-subtitle"><?php esc_html_e( 'DocuMerge Lite uses the built-in form builder.', 'wprobo-documerge-lite' ); ?></p>

				<div class="wdm-wizard-detect-results">
					<div class="wdm-detect-card wdm-detect-none">
						<span class="wdm-detect-icon dashicons dashicons-edit-large"></span>
						<div class="wdm-detect-text">
							<strong><?php esc_html_e( 'Built-in Form Builder', 'wprobo-documerge-lite' ); ?></strong>
							<p><?php esc_html_e( 'Create forms with our drag-and-drop builder. Upgrade to Pro to integrate with WPForms, Contact Form 7, Gravity Forms, and Fluent Forms.', 'wprobo-documerge-lite' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- ── STEP 3: Configure ───────────────────────────────── -->
		<div class="wdm-wizard-step" data-step="3">
			<div class="wdm-wizard-step-inner">
				<h2 class="wdm-wizard-title"><?php esc_html_e( 'Basic Configuration', 'wprobo-documerge-lite' ); ?></h2>
				<p class="wdm-wizard-subtitle"><?php esc_html_e( 'Set your default preferences. You can change these later in Settings.', 'wprobo-documerge-lite' ); ?></p>

				<div class="wdm-wizard-config-section">
					<h3 class="wdm-wizard-config-heading"><?php esc_html_e( 'Document Storage', 'wprobo-documerge-lite' ); ?></h3>
					<div class="wdm-wizard-radio-group">
						<label class="wdm-wizard-radio">
							<input type="radio" name="wdm_doc_storage" value="keep" checked>
							<span class="wdm-radio-mark"></span>
							<span class="wdm-radio-text"><?php esc_html_e( 'Save generated documents to server (recommended)', 'wprobo-documerge-lite' ); ?></span>
						</label>
						<label class="wdm-wizard-radio">
							<input type="radio" name="wdm_doc_storage" value="delete">
							<span class="wdm-radio-mark"></span>
							<span class="wdm-radio-text"><?php esc_html_e( 'Delete after delivery', 'wprobo-documerge-lite' ); ?></span>
						</label>
					</div>
				</div>

				<div class="wdm-wizard-config-section">
					<h3 class="wdm-wizard-config-heading"><?php esc_html_e( 'Default Output Format', 'wprobo-documerge-lite' ); ?></h3>
					<div class="wdm-wizard-radio-group">
						<label class="wdm-wizard-radio">
							<input type="radio" name="wdm_output_format" value="docx">
							<span class="wdm-radio-mark"></span>
							<span class="wdm-radio-text"><?php esc_html_e( 'DOCX only', 'wprobo-documerge-lite' ); ?></span>
						</label>
						<label class="wdm-wizard-radio">
							<input type="radio" name="wdm_output_format" value="pdf" checked>
							<span class="wdm-radio-mark"></span>
							<span class="wdm-radio-text"><?php esc_html_e( 'PDF only', 'wprobo-documerge-lite' ); ?></span>
						</label>
						<label class="wdm-wizard-radio">
							<input type="radio" name="wdm_output_format" value="both">
							<span class="wdm-radio-mark"></span>
							<span class="wdm-radio-text"><?php esc_html_e( 'Both DOCX and PDF', 'wprobo-documerge-lite' ); ?></span>
						</label>
					</div>
				</div>

				<div class="wdm-wizard-config-section">
					<h3 class="wdm-wizard-config-heading"><?php esc_html_e( 'Default Delivery Method', 'wprobo-documerge-lite' ); ?></h3>
					<div class="wdm-wizard-checkbox-group">
						<label class="wdm-wizard-checkbox">
							<input type="checkbox" name="wdm_delivery_methods[]" value="email" checked>
							<span class="wdm-checkbox-mark"></span>
							<span class="wdm-checkbox-text"><?php esc_html_e( 'Email to submitter', 'wprobo-documerge-lite' ); ?></span>
						</label>
						<label class="wdm-wizard-checkbox">
							<input type="checkbox" name="wdm_delivery_methods[]" value="download" checked>
							<span class="wdm-checkbox-mark"></span>
							<span class="wdm-checkbox-text"><?php esc_html_e( 'Download in browser', 'wprobo-documerge-lite' ); ?></span>
						</label>
						<label class="wdm-wizard-checkbox">
							<input type="checkbox" name="wdm_delivery_methods[]" value="media">
							<span class="wdm-checkbox-mark"></span>
							<span class="wdm-checkbox-text"><?php esc_html_e( 'Save to media library', 'wprobo-documerge-lite' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- ── STEP 4: Done ────────────────────────────────────── -->
		<div class="wdm-wizard-step" data-step="4">
			<div class="wdm-wizard-step-inner wdm-wizard-done">
				<div class="wdm-wizard-done-icon">
					<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<circle cx="40" cy="40" r="38" fill="#eaf4ee" stroke="#166441" stroke-width="2"/>
						<path d="M24 42l10 10 22-22" stroke="#166441" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<h2 class="wdm-wizard-title"><?php esc_html_e( "You're all set!", 'wprobo-documerge-lite' ); ?></h2>
				<p class="wdm-wizard-subtitle"><?php esc_html_e( 'WPRobo DocuMerge is ready to use.', 'wprobo-documerge-lite' ); ?></p>
				<div class="wdm-wizard-done-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-templates' ) ); ?>" class="wdm-btn wdm-btn-primary" id="wdm-wizard-go-templates">
						<?php esc_html_e( 'Upload Your First Template', 'wprobo-documerge-lite' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge' ) ); ?>" class="wdm-btn wdm-btn-secondary" id="wdm-wizard-go-dashboard">
						<?php esc_html_e( 'Go to Dashboard', 'wprobo-documerge-lite' ); ?>
					</a>
				</div>
			</div>
		</div>

	</div>

	<!-- ── Footer Navigation ───────────────────────────────────── -->
	<div class="wdm-wizard-footer" id="wdm-wizard-footer">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge' ) ); ?>" class="wdm-wizard-skip" id="wdm-wizard-skip">
			<?php esc_html_e( 'Skip Setup', 'wprobo-documerge-lite' ); ?>
		</a>
		<button type="button" class="wdm-btn wdm-btn-primary" id="wdm-wizard-continue">
			<?php esc_html_e( 'Continue', 'wprobo-documerge-lite' ); ?>
			<span class="dashicons dashicons-arrow-right-alt2"></span>
		</button>
	</div>

</div>

<?php wp_print_scripts( 'wprobo-documerge-wizard' ); ?>
</body>
</html>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
