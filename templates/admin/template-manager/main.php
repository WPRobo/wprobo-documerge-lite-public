<?php
/**
 * Template Manager — main page template.
 *
 * Displays the template card grid with upload/edit slide panel.
 *
 * Receives:
 *   $templates (array) — Array of template objects. Each object has:
 *       id, name, description, file_name, output_format,
 *       merge_tags (JSON string), created_at, form_count (int).
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

// Page header variables.
$page_title     = __( 'Document Templates', 'wprobo-documerge-lite' );
$page_subtitle  = __( 'Manage your Word/DOCX document templates', 'wprobo-documerge-lite' );
$primary_action = array(
	'url'   => '#',
	'label' => __( 'Upload Template', 'wprobo-documerge-lite' ),
	'icon'  => 'dashicons-upload',
	'id'    => 'wdm-upload-template-btn',
);
?>
<div class="wdm-admin-wrap">

	<?php require WPROBO_DOCUMERGE_PATH . 'templates/admin/partials/page-header.php'; ?>

	<?php if ( empty( $templates ) ) : ?>

		<!-- ── Empty State ─────────────────────────────────────────── -->
		<div class="wdm-empty-state">
			<span class="dashicons dashicons-media-document"></span>
			<h3><?php esc_html_e( 'No templates yet', 'wprobo-documerge-lite' ); ?></h3>
			<p><?php esc_html_e( 'Upload your first document template to get started.', 'wprobo-documerge-lite' ); ?></p>
			<button type="button" class="wdm-btn wdm-btn-primary wdm-template-upload-btn">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Upload Template', 'wprobo-documerge-lite' ); ?>
			</button>
		</div>

	<?php else : ?>

		<!-- ── Template Card Grid ──────────────────────────────────── -->
		<div class="wdm-template-cards">

			<?php foreach ( $templates as $template ) : ?>
				<?php
				$merge_tags = json_decode( $template->merge_tags, true );
				$tag_count  = is_array( $merge_tags ) ? count( $merge_tags ) : 0;
				$form_count = absint( $template->form_count );
				$output_fmt = $template->output_format;

				// Build format badge label.
				$format_labels = array(
					'pdf'  => __( 'PDF', 'wprobo-documerge-lite' ),
					'docx' => __( 'DOCX', 'wprobo-documerge-lite' ),
					'both' => __( 'Both', 'wprobo-documerge-lite' ),
				);
				$format_label  = isset( $format_labels[ $output_fmt ] ) ? $format_labels[ $output_fmt ] : esc_html( $output_fmt );
				?>
				<div class="wdm-template-card" data-id="<?php echo esc_attr( $template->id ); ?>">

					<div class="wdm-template-card-top">
						<div class="wdm-template-card-icon">
							<span class="dashicons dashicons-media-document"></span>
						</div>
						<span class="wdm-badge wdm-badge-info"><?php echo esc_html( $format_label ); ?></span>
					</div>

					<h3 class="wdm-template-card-title"><?php echo esc_html( $template->name ); ?></h3>

					<?php if ( ! empty( $template->description ) ) : ?>
						<p class="wdm-template-card-desc"><?php echo esc_html( $template->description ); ?></p>
					<?php endif; ?>

					<div class="wdm-template-card-divider"></div>

					<!-- Merge Tags -->
					<div class="wdm-template-card-tags">
						<span class="wdm-template-meta-label"><?php esc_html_e( 'Tags', 'wprobo-documerge-lite' ); ?></span>
						<div class="wdm-template-tag-pills">
							<?php
							$tags_to_show = array_slice( $template->tags_array, 0, 5 );
							foreach ( $tags_to_show as $tag ) :
								?>
								<code class="wdm-tag-pill"><?php echo esc_html( $tag ); ?></code>
							<?php endforeach; ?>
							<?php if ( $tag_count > 5 ) : ?>
								<button type="button" class="wdm-tag-more" data-tags="<?php echo esc_attr( wp_json_encode( $template->tags_array ) ); ?>">
									+<?php echo esc_html( $tag_count - 5 ); ?> <?php esc_html_e( 'more', 'wprobo-documerge-lite' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( 0 === $tag_count ) : ?>
								<span class="wdm-text-muted"><?php esc_html_e( 'No tags detected', 'wprobo-documerge-lite' ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Linked Forms -->
					<div class="wdm-template-card-forms">
						<span class="wdm-template-meta-label"><?php esc_html_e( 'Forms', 'wprobo-documerge-lite' ); ?></span>
						<?php if ( ! empty( $template->linked_forms ) ) : ?>
							<div class="wdm-template-form-links">
								<?php foreach ( $template->linked_forms as $linked_form ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-forms&action=edit&id=' . absint( $linked_form->id ) ) ); ?>" class="wdm-template-form-link">
										<?php echo esc_html( $linked_form->title ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<span class="wdm-text-muted"><?php esc_html_e( 'Not used in any form', 'wprobo-documerge-lite' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="wdm-template-card-actions">
						<button type="button" class="wdm-btn wdm-btn-sm wdm-template-edit" data-id="<?php echo esc_attr( $template->id ); ?>">
							<span class="dashicons dashicons-edit"></span>
							<?php esc_html_e( 'Edit', 'wprobo-documerge-lite' ); ?>
						</button>
						<button type="button" class="wdm-btn wdm-btn-sm wdm-btn-danger wdm-template-delete" data-id="<?php echo esc_attr( $template->id ); ?>">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?>
						</button>
					</div>

				</div>
			<?php endforeach; ?>

		</div>

	<?php endif; ?>

</div>

<?php
// Slide panel for upload / edit.
require WPROBO_DOCUMERGE_PATH . 'templates/admin/template-manager/slide-panel.php';
?>

<div class="wdm-overlay" id="wdm-overlay"></div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
