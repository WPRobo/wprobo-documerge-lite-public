<?php
/**
 * Settings page template — 7 tabs.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/settings
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wdm-admin-wrap">

	<?php
	$page_title     = __( 'Settings', 'wprobo-documerge-lite' );
	$page_subtitle  = __( 'Configure WPRobo DocuMerge', 'wprobo-documerge-lite' );
	$primary_action = array();
	require WPROBO_DOCUMERGE_PATH . 'templates/admin/partials/page-header.php';
	?>

	<div class="wdm-settings-wrap">

		<div class="wdm-settings-tabs">
			<button type="button" class="wdm-settings-tab wdm-tab-active" data-tab="general"><?php esc_html_e( 'General', 'wprobo-documerge-lite' ); ?></button>
			<button type="button" class="wdm-settings-tab" data-tab="stripe"><?php esc_html_e( 'Stripe', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></button>
			<button type="button" class="wdm-settings-tab" data-tab="email"><?php esc_html_e( 'Email', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></button>
			<button type="button" class="wdm-settings-tab" data-tab="recaptcha"><?php esc_html_e( 'reCAPTCHA', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></button>
			<button type="button" class="wdm-settings-tab" data-tab="styles"><?php esc_html_e( 'Styles', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></button>
			<button type="button" class="wdm-settings-tab" data-tab="customcss"><?php esc_html_e( 'Custom CSS', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></button>
			<button type="button" class="wdm-settings-tab" data-tab="advanced"><?php esc_html_e( 'Advanced', 'wprobo-documerge-lite' ); ?></button>
			<button type="button" class="wdm-settings-tab" data-tab="importexport"><?php esc_html_e( 'Import / Export', 'wprobo-documerge-lite' ); ?></button>
			<button type="button" class="wdm-settings-tab wdm-tab-danger" data-tab="dangerzone"><?php esc_html_e( 'Danger Zone', 'wprobo-documerge-lite' ); ?></button>
			<?php
			/**
			 * Filters the settings page tabs array.
			 *
			 * Allows add-ons to register custom settings tabs. Each entry
			 * should be a slug => label pair. The add-on is responsible for
			 * rendering the corresponding panel content.
			 *
			 * @since 1.0.0
			 *
			 * @param array $tabs Array of tab slug => label pairs.
			 */
			$extra_tabs = apply_filters( 'wprobo_documerge_settings_tabs', array() );
			foreach ( $extra_tabs as $tab_slug => $tab_label ) {
				printf(
					'<button type="button" class="wdm-settings-tab" data-tab="%s">%s</button>',
					esc_attr( $tab_slug ),
					esc_html( $tab_label )
				);
			}
			?>
		</div>

		<!-- ══════════════ GENERAL ══════════════ -->
		<div class="wdm-settings-panel wdm-panel-active" data-tab="general">

			<!-- ── Document Output Card ─────────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-media-document"></span>
					<div>
						<h3><?php esc_html_e( 'Document Output', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Default format and delivery settings for generated documents.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-field-group">
						<label><?php esc_html_e( 'Output Format', 'wprobo-documerge-lite' ); ?></label>
						<?php $fmt = get_option( 'wprobo_documerge_default_output_format', 'pdf' ); ?>
						<div class="wdm-radio-group">
							<label class="wdm-radio-label"><input type="radio" name="wprobo_documerge_default_output_format" value="pdf" <?php checked( $fmt, 'pdf' ); ?>> <?php esc_html_e( 'PDF only', 'wprobo-documerge-lite' ); ?></label>
							<label class="wdm-radio-label"><input type="radio" name="wprobo_documerge_default_output_format" value="docx" <?php checked( $fmt, 'docx' ); ?>> <?php esc_html_e( 'DOCX only', 'wprobo-documerge-lite' ); ?></label>
							<label class="wdm-radio-label"><input type="radio" name="wprobo_documerge_default_output_format" value="both" <?php checked( $fmt, 'both' ); ?>> <?php esc_html_e( 'Both DOCX and PDF', 'wprobo-documerge-lite' ); ?></label>
						</div>
					</div>

					<div class="wdm-field-group">
						<label><?php esc_html_e( 'Delivery Method', 'wprobo-documerge-lite' ); ?></label>
						<span class="wdm-description"><?php esc_html_e( 'Can be overridden per form.', 'wprobo-documerge-lite' ); ?></span>
						<div class="wdm-checkbox-group">
							<label class="wdm-checkbox-label"><input type="checkbox" name="wprobo_documerge_delivery_download" value="1" <?php checked( get_option( 'wprobo_documerge_delivery_download', '1' ), '1' ); ?>> <?php esc_html_e( 'Download in browser', 'wprobo-documerge-lite' ); ?></label>
							<label class="wdm-checkbox-label wdm-pro-disabled-setting"><input type="checkbox" disabled> <?php esc_html_e( 'Email to submitter', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></label>
							<label class="wdm-checkbox-label wdm-pro-disabled-setting"><input type="checkbox" disabled> <?php esc_html_e( 'Save to Media Library', 'wprobo-documerge-lite' ); ?> <span class="wdm-pro-badge">PRO</span></label>
						</div>
					</div>

					<div class="wdm-field-group">
						<label for="wdm-expiry"><?php esc_html_e( 'Download Link Expiry', 'wprobo-documerge-lite' ); ?></label>
						<div class="wdm-input-row">
							<input type="number" id="wdm-expiry" name="wprobo_documerge_download_expiry_hours" class="wdm-input wdm-input-small" value="<?php echo esc_attr( get_option( 'wprobo_documerge_download_expiry_hours', 72 ) ); ?>" min="0">
							<span class="wdm-input-suffix"><?php esc_html_e( 'hours (0 = never)', 'wprobo-documerge-lite' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Form Configuration Card ──────────────────────── -->
			<div class="wdm-settings-card" id="wdm-form-mode-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-feedback"></span>
					<div>
						<h3><?php esc_html_e( 'Form Configuration', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'How forms work and display date/time values.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-field-group">
						<label><?php esc_html_e( 'Form Mode', 'wprobo-documerge-lite' ); ?></label>
						<?php $mode = get_option( 'wprobo_documerge_form_mode', 'standalone' ); ?>
						<div class="wdm-radio-group">
							<label class="wdm-radio-label"><input type="radio" name="wprobo_documerge_form_mode" value="standalone" <?php checked( $mode, 'standalone' ); ?>> <?php esc_html_e( 'Standalone (built-in form builder)', 'wprobo-documerge-lite' ); ?></label>
							<label class="wdm-radio-label wdm-pro-disabled-toggle"><input type="radio" disabled="disabled"> <?php esc_html_e( 'Integrated (WPForms / CF7 / Gravity Forms etc.)', 'wprobo-documerge-lite' ); ?> <?php echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_badge() ); ?></label>
						</div>
					</div>

					<div class="wdm-field-group wdm-integration-field-group"<?php echo ( 'integrated' !== $mode ) ? ' style="display:none;"' : ''; ?>>
						<label for="wdm-integration"><?php esc_html_e( 'Active Integration', 'wprobo-documerge-lite' ); ?></label>
						<?php
						$integ             = get_option( 'wprobo_documerge_active_integration', '' );
						$available_plugins = array(
							'wpforms' => array(
								'label'  => 'WPForms',
								'active' => function_exists( 'wpforms' ),
							),
							'cf7'     => array(
								'label'  => 'Contact Form 7',
								'active' => class_exists( 'WPCF7' ),
							),
							'gravity' => array(
								'label'  => 'Gravity Forms',
								'active' => class_exists( 'GFForms' ),
							),
							'fluent'  => array(
								'label'  => 'Fluent Forms',
								'active' => defined( 'FLUENTFORM' ),
							),
						);
						$has_any_active    = false;
						foreach ( $available_plugins as $p ) {
							if ( $p['active'] ) {
								$has_any_active = true;
								break; }
						}
						?>
						<select id="wdm-integration" name="wprobo_documerge_active_integration" class="wdm-select">
							<option value=""><?php esc_html_e( '— Select —', 'wprobo-documerge-lite' ); ?></option>
							<?php foreach ( $available_plugins as $slug => $plugin ) : ?>
								<?php if ( $plugin['active'] ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $integ, $slug ); ?>><?php echo esc_html( $plugin['label'] ); ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
						<?php if ( ! $has_any_active ) : ?>
							<span class="wdm-description" style="color:#d97706;"><?php esc_html_e( 'No supported form plugins detected. Install WPForms, Contact Form 7, Gravity Forms, or Fluent Forms.', 'wprobo-documerge-lite' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="wdm-settings-row-2col">
						<div class="wdm-field-group">
							<label for="wdm-date-fmt"><?php esc_html_e( 'Date Format', 'wprobo-documerge-lite' ); ?></label>
							<?php $df = get_option( 'wprobo_documerge_date_format', 'd/m/Y' ); ?>
							<select id="wdm-date-fmt" name="wprobo_documerge_date_format" class="wdm-select">
								<option value="d/m/Y" <?php selected( $df, 'd/m/Y' ); ?>>DD/MM/YYYY</option>
								<option value="m/d/Y" <?php selected( $df, 'm/d/Y' ); ?>>MM/DD/YYYY</option>
								<option value="Y-m-d" <?php selected( $df, 'Y-m-d' ); ?>>YYYY-MM-DD</option>
							</select>
						</div>
						<div class="wdm-field-group">
							<label for="wdm-time-fmt"><?php esc_html_e( 'Time Format', 'wprobo-documerge-lite' ); ?></label>
							<?php $tf = get_option( 'wprobo_documerge_time_format', 'H:i' ); ?>
							<select id="wdm-time-fmt" name="wprobo_documerge_time_format" class="wdm-select">
								<option value="H:i" <?php selected( $tf, 'H:i' ); ?>><?php esc_html_e( '24-hour', 'wprobo-documerge-lite' ); ?></option>
								<option value="g:i A" <?php selected( $tf, 'g:i A' ); ?>><?php esc_html_e( '12-hour', 'wprobo-documerge-lite' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Notifications Card ───────────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-bell"></span>
					<div>
						<h3><?php esc_html_e( 'Admin Notifications', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Email alerts when submissions arrive or errors occur.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-field-group">
						<div class="wdm-checkbox-group">
							<label class="wdm-checkbox-label"><input type="checkbox" name="wprobo_documerge_notify_new_submission" value="1" <?php checked( get_option( 'wprobo_documerge_notify_new_submission', '1' ), '1' ); ?>> <?php esc_html_e( 'Send notification on new submission', 'wprobo-documerge-lite' ); ?></label>
							<label class="wdm-checkbox-label"><input type="checkbox" name="wprobo_documerge_notify_on_error" value="1" <?php checked( get_option( 'wprobo_documerge_notify_on_error', '1' ), '1' ); ?>> <?php esc_html_e( 'Send notification on generation error', 'wprobo-documerge-lite' ); ?></label>
						</div>
					</div>

					<div class="wdm-field-group">
						<label for="wdm-notif-email"><?php esc_html_e( 'Notification Email', 'wprobo-documerge-lite' ); ?></label>
						<input type="email" id="wdm-notif-email" name="wprobo_documerge_notification_email" class="wdm-input" value="<?php echo esc_attr( get_option( 'wprobo_documerge_notification_email', '' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
						<span class="wdm-description"><?php esc_html_e( 'Defaults to WordPress admin email if left blank.', 'wprobo-documerge-lite' ); ?></span>
					</div>
				</div>
			</div>

			<div class="wdm-settings-actions">
				<button type="button" class="wdm-btn wdm-btn-primary wdm-settings-save" data-tab="general"><?php esc_html_e( 'Save General Settings', 'wprobo-documerge-lite' ); ?></button>
			</div>
		</div>

		<!-- ══════════════ STRIPE (PRO) ══════════════ -->
		<div class="wdm-settings-panel" data-tab="stripe">
			<?php
			echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_overlay(
				__( 'Stripe Payments', 'wprobo-documerge-lite' ),
				__( 'Accept payments before delivering documents. Supports test and live mode with full Stripe integration.', 'wprobo-documerge-lite' )
			) );
			?>
		</div>

		<!-- ══════════════ EMAIL (PRO) ══════════════ -->
		<div class="wdm-settings-panel" data-tab="email">
			<?php
			echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_overlay(
				__( 'Email Delivery', 'wprobo-documerge-lite' ),
				__( 'Send generated documents via email with customizable templates, sender details, and attachment settings.', 'wprobo-documerge-lite' )
			) );
			?>
		</div>

		<!-- ══════════════ RECAPTCHA (PRO) ══════════════ -->
		<div class="wdm-settings-panel" data-tab="recaptcha">
			<?php
			echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_overlay(
				__( 'CAPTCHA / Spam Protection', 'wprobo-documerge-lite' ),
				__( 'Protect your forms with Google reCAPTCHA v2, v3, or hCaptcha integration.', 'wprobo-documerge-lite' )
			) );
			?>
		</div>

		<!-- ══════════════ STYLES (PRO) ══════════════ -->
		<div class="wdm-settings-panel" data-tab="styles">
			<?php
			echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_overlay(
				__( 'Form Styles', 'wprobo-documerge-lite' ),
				__( 'Customize form appearance — colors, fonts, spacing, borders, and button styles with a visual designer.', 'wprobo-documerge-lite' )
			) );
			?>
		</div>

		<!-- ══════════════ CUSTOM CSS (PRO) ══════════════ -->
		<div class="wdm-settings-panel" data-tab="customcss">
			<?php
			echo wp_kses_post( \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Pro_Upsell::wprobo_documerge_render_overlay(
				__( 'Custom CSS', 'wprobo-documerge-lite' ),
				__( 'Add custom CSS to fully control the look of your forms with a syntax-highlighted code editor.', 'wprobo-documerge-lite' )
			) );
			?>
		</div>

		<!-- ══════════════ ADVANCED ══════════════ -->
		<div class="wdm-settings-panel" data-tab="advanced">

			<!-- ── Storage & Cleanup Card ───────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-cloud-saved"></span>
					<div>
						<h3><?php esc_html_e( 'Storage & Cleanup', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Automatic cleanup of generated documents and log files.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-settings-row-2col">
						<div class="wdm-field-group">
							<label><?php esc_html_e( 'Auto-delete documents after', 'wprobo-documerge-lite' ); ?></label>
							<div class="wdm-input-row">
								<input type="number" name="wprobo_documerge_auto_delete_days" class="wdm-input wdm-input-small" value="<?php echo esc_attr( get_option( 'wprobo_documerge_auto_delete_days', 0 ) ); ?>" min="0">
								<span class="wdm-input-suffix"><?php esc_html_e( 'days', 'wprobo-documerge-lite' ); ?></span>
							</div>
							<span class="wdm-description"><?php esc_html_e( '0 = never delete automatically.', 'wprobo-documerge-lite' ); ?></span>
						</div>
						<div class="wdm-field-group">
							<label><?php esc_html_e( 'Keep error logs for', 'wprobo-documerge-lite' ); ?></label>
							<div class="wdm-input-row">
								<input type="number" name="wprobo_documerge_log_retention_days" class="wdm-input wdm-input-small" value="<?php echo esc_attr( get_option( 'wprobo_documerge_log_retention_days', 30 ) ); ?>" min="1">
								<span class="wdm-input-suffix"><?php esc_html_e( 'days', 'wprobo-documerge-lite' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Developer Options Card ───────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-editor-code"></span>
					<div>
						<h3><?php esc_html_e( 'Developer Options', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Debug logging and data retention on uninstall.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-field-group">
						<label class="wdm-checkbox-label"><input type="checkbox" name="wprobo_documerge_debug_logging" value="1" <?php checked( get_option( 'wprobo_documerge_debug_logging', '0' ), '1' ); ?>> <?php esc_html_e( 'Enable debug logging', 'wprobo-documerge-lite' ); ?></label>
						<span class="wdm-description"><?php esc_html_e( 'Only works when WP_DEBUG is true. Logs are stored in wp-content/uploads/documerge-logs/.', 'wprobo-documerge-lite' ); ?></span>
					</div>

					<div class="wdm-field-group">
						<label class="wdm-checkbox-label"><input type="checkbox" name="wprobo_documerge_uninstall_data" value="1" <?php checked( get_option( 'wprobo_documerge_uninstall_data', '0' ), '1' ); ?>> <?php esc_html_e( 'Delete ALL plugin data when uninstalling', 'wprobo-documerge-lite' ); ?></label>
						<div class="wdm-notice wdm-notice-warning" style="margin-top:8px;">
							<span class="wdm-notice-icon dashicons dashicons-warning"></span>
							<span class="wdm-notice-text"><?php esc_html_e( 'This removes all database tables, generated documents, and settings permanently.', 'wprobo-documerge-lite' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Setup Wizard Card ────────────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-admin-tools"></span>
					<div>
						<h3><?php esc_html_e( 'Setup Wizard', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Re-run the initial setup wizard to reconfigure plugin defaults.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<button type="button" class="wdm-btn wdm-btn-secondary" id="wdm-rerun-wizard">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Re-run Setup Wizard', 'wprobo-documerge-lite' ); ?>
					</button>
				</div>
			</div>

			<div class="wdm-settings-actions">
				<button type="button" class="wdm-btn wdm-btn-primary wdm-settings-save" data-tab="advanced"><?php esc_html_e( 'Save Advanced Settings', 'wprobo-documerge-lite' ); ?></button>
			</div>
		</div>

		<!-- ══════════════ IMPORT / EXPORT ══════════════ -->
		<div class="wdm-settings-panel" data-tab="importexport">

			<div class="wdm-import-export-row">

			<!-- ── Export Card ─────────────────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-upload"></span>
					<div>
						<h3><?php esc_html_e( 'Export Data', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Download your DocuMerge data as a JSON file. Select which data to include.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<?php
					global $wpdb;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ie_tpl_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_templates" );
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ie_form_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_forms" );
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ie_sub_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_submissions" );

					?>
					<div class="wdm-export-options">
						<label class="wdm-checkbox-label">
							<input type="checkbox" class="wdm-export-checkbox" value="templates" checked>
							<?php
							printf(
								/* translators: %s: number of templates */
								esc_html__( 'Templates (%s)', 'wprobo-documerge-lite' ),
								esc_html( number_format_i18n( $ie_tpl_count ) )
							);
							?>
						</label>
						<label class="wdm-checkbox-label">
							<input type="checkbox" class="wdm-export-checkbox" value="forms" checked>
							<?php
							printf(
								/* translators: %s: number of forms */
								esc_html__( 'Forms (%s)', 'wprobo-documerge-lite' ),
								esc_html( number_format_i18n( $ie_form_count ) )
							);
							?>
						</label>
						<label class="wdm-checkbox-label">
							<input type="checkbox" class="wdm-export-checkbox" value="submissions" checked>
							<?php
							printf(
								/* translators: %s: number of submissions */
								esc_html__( 'Submissions (%s)', 'wprobo-documerge-lite' ),
								esc_html( number_format_i18n( $ie_sub_count ) )
							);
							?>
						</label>
						<label class="wdm-checkbox-label">
							<input type="checkbox" class="wdm-export-checkbox" value="settings" checked>
							<?php esc_html_e( 'Settings', 'wprobo-documerge-lite' ); ?>
						</label>
					</div>
					<div class="wdm-export-actions">
						<button type="button" class="wdm-btn wdm-btn-primary" id="wdm-export-selected">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export Selected', 'wprobo-documerge-lite' ); ?>
						</button>
						<button type="button" class="wdm-btn wdm-btn-secondary" id="wdm-export-all">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export All', 'wprobo-documerge-lite' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- ── Import Card ─────────────────────────────────── -->
			<div class="wdm-settings-card">
				<div class="wdm-settings-card-header">
					<span class="dashicons dashicons-download"></span>
					<div>
						<h3><?php esc_html_e( 'Import Data', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Restore data from a previously exported DocuMerge JSON file.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">

					<!-- Drop zone -->
					<div class="wdm-import-dropzone" id="wdm-import-dropzone">
						<div class="wdm-import-dropzone-inner">
							<span class="dashicons dashicons-upload"></span>
							<p><?php esc_html_e( 'Drag & drop your .json file here', 'wprobo-documerge-lite' ); ?></p>
							<span class="wdm-import-dropzone-or"><?php esc_html_e( 'or', 'wprobo-documerge-lite' ); ?></span>
							<button type="button" class="wdm-btn wdm-btn-secondary wdm-btn-sm" id="wdm-import-browse">
								<?php esc_html_e( 'Browse Files', 'wprobo-documerge-lite' ); ?>
							</button>
							<input type="file" id="wdm-import-file" accept=".json" style="display:none;">
						</div>
					</div>

					<!-- Preview (hidden until file loaded) -->
					<div class="wdm-import-preview" id="wdm-import-preview" style="display:none;">
						<div class="wdm-import-file-info">
							<span class="dashicons dashicons-media-code"></span>
							<span class="wdm-import-filename" id="wdm-import-filename"></span>
							<button type="button" class="wdm-btn wdm-btn-ghost wdm-btn-sm" id="wdm-import-clear" aria-label="<?php esc_attr_e( 'Remove file', 'wprobo-documerge-lite' ); ?>">
								&times;
							</button>
						</div>
						<div class="wdm-import-summary" id="wdm-import-summary"></div>

						<div class="wdm-import-select-items" id="wdm-import-select-items"></div>

						<div class="wdm-field-group">
							<label><?php esc_html_e( 'Import Mode', 'wprobo-documerge-lite' ); ?></label>
							<div class="wdm-radio-group">
								<label class="wdm-radio-label">
									<input type="radio" name="wdm_import_mode" value="merge" checked>
									<?php esc_html_e( 'Merge with existing data (default)', 'wprobo-documerge-lite' ); ?>
								</label>
								<label class="wdm-radio-label">
									<input type="radio" name="wdm_import_mode" value="replace">
									<?php esc_html_e( 'Replace all (destructive — removes existing data first)', 'wprobo-documerge-lite' ); ?>
								</label>
							</div>
						</div>

						<div class="wdm-import-actions">
							<button type="button" class="wdm-btn wdm-btn-primary" id="wdm-import-run" disabled>
								<span class="dashicons dashicons-upload"></span>
								<?php esc_html_e( 'Import Selected', 'wprobo-documerge-lite' ); ?>
							</button>
						</div>
					</div>

					<!-- Result (hidden until import completes) -->
					<div class="wdm-import-result" id="wdm-import-result" style="display:none;"></div>

				</div>
			</div>

			</div><!-- /.wdm-import-export-row -->

		</div>

		<!-- ══════════════ DANGER ZONE ══════════════ -->
		<div class="wdm-settings-panel wdm-danger-zone-panel" data-tab="dangerzone">

			<div class="wdm-notice wdm-notice-error" style="margin-bottom:24px;">
				<span class="wdm-notice-icon dashicons dashicons-warning"></span>
				<span class="wdm-notice-text">
					<strong><?php esc_html_e( 'Danger Zone', 'wprobo-documerge-lite' ); ?></strong> —
					<?php esc_html_e( 'These actions are destructive and cannot be undone. Proceed with extreme caution.', 'wprobo-documerge-lite' ); ?>
				</span>
			</div>

			<?php
			global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sub_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_submissions" );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$form_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_forms" );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tpl_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprdm_templates" );
			?>

			<!-- ── Group 1: Data Cleanup ──────────────────────── -->
			<div class="wdm-settings-card" style="border-left:3px solid #d97706;">
				<div class="wdm-settings-card-header" style="background:rgba(217,119,6,0.04);">
					<span class="dashicons dashicons-trash" style="color:#d97706;"></span>
					<div>
						<h3><?php esc_html_e( 'Data Cleanup', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Selectively remove specific types of data. Other data remains intact.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-danger-card">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Delete All Submissions', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Removes all submission records and generated documents (PDF/DOCX).', 'wprobo-documerge-lite' ); ?></p>
							<span class="wdm-danger-count"><?php echo esc_html( number_format_i18n( $sub_count ) ); ?> <?php esc_html_e( 'submissions', 'wprobo-documerge-lite' ); ?></span>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="delete_submissions"><?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?></button>
					</div>
					<div class="wdm-danger-card">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Delete All Forms', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Removes all form configurations and field layouts. Templates are not affected.', 'wprobo-documerge-lite' ); ?></p>
							<span class="wdm-danger-count"><?php echo esc_html( number_format_i18n( $form_count ) ); ?> <?php esc_html_e( 'forms', 'wprobo-documerge-lite' ); ?></span>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="delete_forms"><?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?></button>
					</div>
					<div class="wdm-danger-card">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Delete All Templates', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Removes all templates and their uploaded DOCX files.', 'wprobo-documerge-lite' ); ?></p>
							<span class="wdm-danger-count"><?php echo esc_html( number_format_i18n( $tpl_count ) ); ?> <?php esc_html_e( 'templates', 'wprobo-documerge-lite' ); ?></span>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="delete_templates"><?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?></button>
					</div>
					<div class="wdm-danger-card" style="border-bottom:none;">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Delete Generated Documents Only', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Deletes PDF/DOCX files only. Submission records are kept but downloads stop working.', 'wprobo-documerge-lite' ); ?></p>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="delete_documents"><?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?></button>
					</div>
				</div>
			</div>

			<!-- ── Group 2: Reset & Restore ───────────────────── -->
			<div class="wdm-settings-card" style="border-left:3px solid #dc2626;">
				<div class="wdm-settings-card-header" style="background:rgba(220,38,38,0.04);">
					<span class="dashicons dashicons-image-rotate" style="color:#dc2626;"></span>
					<div>
						<h3><?php esc_html_e( 'Reset & Restore', 'wprobo-documerge-lite' ); ?></h3>
						<p><?php esc_html_e( 'Reset settings, analytics, or perform a complete factory reset.', 'wprobo-documerge-lite' ); ?></p>
					</div>
				</div>
				<div class="wdm-settings-card-body">
					<div class="wdm-danger-card">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Reset All Settings', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Resets General, Stripe, Email, reCAPTCHA, Styles, and Custom CSS to defaults. Data is not affected.', 'wprobo-documerge-lite' ); ?></p>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="reset_settings"><?php esc_html_e( 'Reset', 'wprobo-documerge-lite' ); ?></button>
					</div>
					<div class="wdm-danger-card wdm-danger-card-critical" style="border-bottom:none;">
						<div class="wdm-danger-card-info">
							<h4><?php esc_html_e( 'Full Factory Reset', 'wprobo-documerge-lite' ); ?></h4>
							<p><?php esc_html_e( 'Wipes EVERYTHING — submissions, forms, templates, documents, settings, analytics, logs. Returns the plugin to a freshly-installed state. You must type "RESET" to confirm.', 'wprobo-documerge-lite' ); ?></p>
						</div>
						<button type="button" class="wdm-btn wdm-btn-danger wdm-danger-action" data-action="factory_reset">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Factory Reset', 'wprobo-documerge-lite' ); ?>
						</button>
					</div>
				</div>
			</div>

		</div>

	</div>
</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
