<?php
/**
 * Submissions — slide-in detail panel.
 *
 * Included by templates/admin/submissions/main.php.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/submissions
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wdm-slide-panel" id="wdm-submission-panel">

	<!-- ── Panel Header ────────────────────────────────────────── -->
	<div class="wdm-slide-panel-header">
		<h2 id="wdm-detail-title"><?php echo esc_html__( 'Submission #0', 'wprobo-documerge-lite' ); ?></h2>
		<button type="button" class="wdm-slide-panel-close" id="wdm-detail-close" aria-label="<?php esc_attr_e( 'Close', 'wprobo-documerge-lite' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<!-- ── Panel Body ──────────────────────────────────────────── -->
	<div class="wdm-slide-panel-body" id="wdm-detail-body">
		<!-- Populated via AJAX -->
	</div>

	<!-- ── Panel Footer ────────────────────────────────────────── -->
	<div class="wdm-slide-panel-footer">
		<button type="button" class="wdm-btn wdm-btn-danger" id="wdm-detail-delete">
			<?php esc_html_e( 'Delete Submission', 'wprobo-documerge-lite' ); ?>
		</button>
	</div>

</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
