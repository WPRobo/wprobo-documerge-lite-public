<?php
/**
 * Admin page header partial.
 *
 * Shared header component used by all admin pages.
 *
 * Receives:
 *   $page_title    (string) — Page heading text.
 *   $page_subtitle (string) — Descriptive subtitle text.
 *   $primary_action (array|null) — Optional. Keys: 'url', 'label', 'icon'.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/partials
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wdm-page-header">
	<div class="wdm-page-header-left">
		<div class="wdm-page-header-icon">
			<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<rect width="36" height="36" rx="8" fill="#042157"/>
				<path d="M10 8h12a2 2 0 012 2v16a2 2 0 01-2 2H10a2 2 0 01-2-2V10a2 2 0 012-2z" fill="#ffffff" opacity="0.9"/>
				<path d="M12 14h8M12 18h6M12 22h4" stroke="#042157" stroke-width="1.5" stroke-linecap="round"/>
				<path d="M26 12l-4 4 4 4" stroke="#166441" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</div>
		<div class="wdm-page-header-text">
			<h1 class="wdm-page-title"><?php echo esc_html( $page_title ); ?></h1>
			<p class="wdm-page-subtitle"><?php echo esc_html( $page_subtitle ); ?></p>
		</div>
	</div>
	<div class="wdm-page-header-right">
		<?php if ( ! empty( $primary_action ) && is_array( $primary_action ) ) : ?>
			<a href="<?php echo esc_url( $primary_action['url'] ); ?>" class="wdm-btn wdm-btn-primary"
			<?php
			if ( ! empty( $primary_action['id'] ) ) :
				?>
				id="<?php echo esc_attr( $primary_action['id'] ); ?>"<?php endif; ?>>
				<?php if ( ! empty( $primary_action['icon'] ) ) : ?>
					<span class="dashicons <?php echo esc_attr( $primary_action['icon'] ); ?>"></span>
				<?php endif; ?>
				<?php echo esc_html( $primary_action['label'] ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
<div class="wdm-notices-wrap" id="wdm-notices"></div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
