<?php
/**
 * Help & Support main page template.
 *
 * Displays documentation links, support resources, and system information.
 *
 * Receives:
 *   $system_info (array) — Key-value pairs of system information.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/help
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Page header variables.
$page_title     = __( 'Help & Support', 'wprobo-documerge-lite' );
$page_subtitle  = __( 'Documentation, resources and support for DocuMerge', 'wprobo-documerge-lite' );
$primary_action = null;
?>
<div class="wdm-admin-wrap">

	<?php require dirname( __DIR__ ) . '/partials/page-header.php'; ?>

	<!-- ── Help Cards ────────────────────────────────────────────── -->
	<div class="wdm-help-cards">

		<a href="<?php echo esc_url( 'https://wprobo.com/docs/documerge' ); ?>" class="wdm-help-card" target="_blank" rel="noopener noreferrer">
			<div class="wdm-help-card-header-row">
				<div class="wdm-help-card-icon wdm-help-icon-blue">
					<span class="dashicons dashicons-book"></span>
				</div>
				<span class="wdm-help-card-arrow dashicons dashicons-arrow-right-alt2"></span>
			</div>
			<h3 class="wdm-help-card-title"><?php esc_html_e( 'Documentation', 'wprobo-documerge-lite' ); ?></h3>
			<p class="wdm-help-card-desc"><?php esc_html_e( 'Full plugin documentation covering every feature, setting, and integration.', 'wprobo-documerge-lite' ); ?></p>
		</a>

		<a href="<?php echo esc_url( 'https://wprobo.com/docs/documerge/getting-started' ); ?>" class="wdm-help-card" target="_blank" rel="noopener noreferrer">
			<div class="wdm-help-card-header-row">
				<div class="wdm-help-card-icon wdm-help-icon-green">
					<span class="dashicons dashicons-welcome-learn-more"></span>
				</div>
				<span class="wdm-help-card-arrow dashicons dashicons-arrow-right-alt2"></span>
			</div>
			<h3 class="wdm-help-card-title"><?php esc_html_e( 'Getting Started', 'wprobo-documerge-lite' ); ?></h3>
			<p class="wdm-help-card-desc"><?php esc_html_e( 'Step-by-step guide: Upload template, create form, embed on page, collect documents.', 'wprobo-documerge-lite' ); ?></p>
		</a>

		<a href="<?php echo esc_url( 'https://wprobo.com/support' ); ?>" class="wdm-help-card" target="_blank" rel="noopener noreferrer">
			<div class="wdm-help-card-header-row">
				<div class="wdm-help-card-icon wdm-help-icon-purple">
					<span class="dashicons dashicons-format-chat"></span>
				</div>
				<span class="wdm-help-card-arrow dashicons dashicons-arrow-right-alt2"></span>
			</div>
			<h3 class="wdm-help-card-title"><?php esc_html_e( 'Support', 'wprobo-documerge-lite' ); ?></h3>
			<p class="wdm-help-card-desc"><?php esc_html_e( 'Need help? Open a support ticket and the WPRobo team will assist you.', 'wprobo-documerge-lite' ); ?></p>
		</a>

		<a href="<?php echo esc_url( 'https://codecanyon.net/item/reviews/' ); ?>" class="wdm-help-card" target="_blank" rel="noopener noreferrer">
			<div class="wdm-help-card-header-row">
				<div class="wdm-help-card-icon wdm-help-icon-amber">
					<span class="dashicons dashicons-star-filled"></span>
				</div>
				<span class="wdm-help-card-arrow dashicons dashicons-arrow-right-alt2"></span>
			</div>
			<h3 class="wdm-help-card-title"><?php esc_html_e( 'Rate DocuMerge', 'wprobo-documerge-lite' ); ?></h3>
			<p class="wdm-help-card-desc"><?php esc_html_e( 'Enjoying the plugin? Your 5-star review helps us grow and improve.', 'wprobo-documerge-lite' ); ?></p>
		</a>

	</div>

	<!-- ── System Information ────────────────────────────────────── -->
	<div class="wdm-card" style="margin-top:24px;">
		<div class="wdm-card-header">
			<h2 class="wdm-card-title"><?php esc_html_e( 'System Information', 'wprobo-documerge-lite' ); ?></h2>
			<button type="button" class="wdm-btn wdm-btn-sm" id="wdm-copy-system-info">
				<span class="dashicons dashicons-admin-page"></span>
				<?php esc_html_e( 'Copy System Info', 'wprobo-documerge-lite' ); ?>
			</button>
		</div>
		<div class="wdm-card-body">
			<table class="wdm-system-info-table">
				<tbody>
					<?php foreach ( $system_info as $info_label => $info_value ) : ?>
						<tr>
							<td class="wdm-system-info-label"><?php echo esc_html( $info_label ); ?></td>
							<td class="wdm-system-info-value"><?php echo esc_html( $info_value ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			// Build plain text version for clipboard copy.
			$plain_text_lines = array();
			foreach ( $system_info as $info_label => $info_value ) {
				$plain_text_lines[] = $info_label . ': ' . $info_value;
			}
			?>
			<textarea id="wdm-system-info-text" class="wdm-sr-only" aria-hidden="true" readonly><?php echo esc_textarea( implode( "\n", $plain_text_lines ) ); ?></textarea>
		</div>
	</div>

</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
