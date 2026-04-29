<?php
/**
 * Form Builder — forms list template.
 *
 * Displays the table of all document collection forms with actions.
 *
 * Receives:
 *   $forms     (array) — Array of form objects. Each object has:
 *       id, title, template_id, mode, integration, fields (JSON),
 *       output_format, created_at, field_count (int), submission_count (int).
 *   $templates (array) — Associative array of template objects keyed by ID.
 *       Each template has: id, name, and other properties.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/templates/admin/form-builder
 * @author     Ali Shan <hello@wprobo.com>
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Page header variables.
$page_title     = __( 'Forms', 'wprobo-documerge-lite' );
$page_subtitle  = __( 'Build and manage your document collection forms', 'wprobo-documerge-lite' );
$primary_action = array(
	'url'   => admin_url( 'admin.php?page=wprobo-documerge-forms&action=new' ),
	'label' => __( 'Create Form', 'wprobo-documerge-lite' ),
	'icon'  => 'dashicons-plus-alt2',
);

// Mode badge labels.
$mode_labels = array(
	'standalone' => __( 'Standalone', 'wprobo-documerge-lite' ),
	'wpforms'    => __( 'WPForms', 'wprobo-documerge-lite' ),
	'gravity'    => __( 'Gravity Forms', 'wprobo-documerge-lite' ),
	'cf7'        => __( 'Contact Form 7', 'wprobo-documerge-lite' ),
);
?>
<div class="wdm-admin-wrap">

	<?php require WPROBO_DOCUMERGE_PATH . 'templates/admin/partials/page-header.php'; ?>

	<?php if ( empty( $forms ) ) : ?>

		<!-- Empty State -->
		<div class="wdm-empty-state">
			<span class="dashicons dashicons-feedback"></span>
			<h3><?php esc_html_e( 'No forms yet', 'wprobo-documerge-lite' ); ?></h3>
			<p><?php esc_html_e( 'Create your first form to start collecting submissions.', 'wprobo-documerge-lite' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-forms&action=new' ) ); ?>" class="wdm-btn wdm-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Create Form', 'wprobo-documerge-lite' ); ?>
			</a>
		</div>

	<?php else : ?>

		<!-- Forms Table -->
		<table class="wdm-table">
			<thead>
				<?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin list-table sort-column read; cap-checked, sanitized, idempotent.
				$current_orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id';
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin list-table sort-direction read; cap-checked, strict whitelist to 'asc'/'desc'.
				$current_order = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ? 'asc' : 'desc';
				$base_url      = admin_url( 'admin.php?page=wprobo-documerge-forms' );
				?>
				<tr>
					<th>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'orderby' => 'id',
									'order'   => ( 'id' === $current_orderby && 'asc' === $current_order ) ? 'desc' : 'asc',
								),
								$base_url
							)
						);
						?>
									" style="display:inline-flex;align-items:center;gap:2px;">
							<?php esc_html_e( 'ID', 'wprobo-documerge-lite' ); ?>
							<span class="dashicons dashicons-<?php echo ( 'id' === $current_orderby ) ? ( 'asc' === $current_order ? 'arrow-up-alt2' : 'arrow-down-alt2' ) : 'sort'; ?>" style="font-size:14px;width:14px;height:14px;<?php echo ( 'id' !== $current_orderby ) ? 'opacity:0.3;' : ''; ?>"></span>
						</a>
					</th>
					<th>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'orderby' => 'title',
									'order'   => ( 'title' === $current_orderby && 'asc' === $current_order ) ? 'desc' : 'asc',
								),
								$base_url
							)
						);
						?>
									" style="display:inline-flex;align-items:center;gap:2px;">
							<?php esc_html_e( 'Name', 'wprobo-documerge-lite' ); ?>
							<span class="dashicons dashicons-<?php echo ( 'title' === $current_orderby ) ? ( 'asc' === $current_order ? 'arrow-up-alt2' : 'arrow-down-alt2' ) : 'sort'; ?>" style="font-size:14px;width:14px;height:14px;<?php echo ( 'title' !== $current_orderby ) ? 'opacity:0.3;' : ''; ?>"></span>
						</a>
					</th>
					<th><?php esc_html_e( 'Template', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Fields', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Submissions', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Views', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Starts', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Completions', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Shortcode', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Mode', 'wprobo-documerge-lite' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wprobo-documerge-lite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $forms as $form ) : ?>
					<?php
					$form_stats    = array(
						'views'       => 0,
						'starts'      => 0,
						'completions' => 0,
						'abandonment' => 0,
					);
					$template_id   = absint( $form->template_id );
					$template_name = isset( $templates[ $template_id ] )
						? $templates[ $template_id ]->name
						: __( 'Unknown', 'wprobo-documerge-lite' );

					$form_mode  = isset( $form->mode ) ? sanitize_key( $form->mode ) : 'standalone';
					$mode_label = isset( $mode_labels[ $form_mode ] )
						? $mode_labels[ $form_mode ]
						: esc_html( $form_mode );

					$edit_url  = admin_url( 'admin.php?page=wprobo-documerge-forms&action=edit&id=' . absint( $form->id ) );
					$shortcode = '[wprobo_documerge_form id="' . absint( $form->id ) . '"]';
					?>
					<tr>
						<td><code><?php echo absint( $form->id ); ?></code></td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="wdm-form-title-link">
								<strong><?php echo esc_html( $form->title ); ?></strong>
							</a>
						</td>
						<td>
							<?php if ( $template_id > 0 ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-templates&edit=' . $template_id ) ); ?>" class="wdm-template-link" title="<?php esc_attr_e( 'Edit template', 'wprobo-documerge-lite' ); ?>">
									<?php echo esc_html( $template_name ); ?>
								</a>
							<?php else : ?>
								<span class="wdm-text-muted"><?php esc_html_e( 'None', 'wprobo-documerge-lite' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo absint( $form->field_count ); ?></td>
						<td>
							<?php if ( $form->submission_count > 0 ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-documerge-submissions&form_id=' . absint( $form->id ) ) ); ?>" class="wdm-submission-count-link">
									<?php echo absint( $form->submission_count ); ?>
								</a>
							<?php else : ?>
								0
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( $form_stats['views'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $form_stats['starts'] ) ); ?></td>
						<td>
							<?php echo esc_html( number_format_i18n( $form_stats['completions'] ) ); ?>
							<?php if ( $form_stats['abandonment'] > 0 ) : ?>
								<span class="wdm-text-muted" title="<?php esc_attr_e( 'Abandonment rate', 'wprobo-documerge-lite' ); ?>">(<?php echo esc_html( $form_stats['abandonment'] ); ?>%&nbsp;<?php esc_html_e( 'drop', 'wprobo-documerge-lite' ); ?>)</span>
							<?php endif; ?>
						</td>
						<td>
							<div class="wdm-shortcode-cell">
								<code class="wdm-shortcode-code"><?php echo esc_html( $shortcode ); ?></code>
								<button type="button" class="wdm-copy-shortcode-btn wdm-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>" title="<?php esc_attr_e( 'Copy', 'wprobo-documerge-lite' ); ?>">
									<span class="dashicons dashicons-clipboard"></span>
								</button>
							</div>
						</td>
						<td>
							<span class="wdm-badge wdm-badge-info">
								<?php echo esc_html( $mode_label ); ?>
							</span>
						</td>
						<td>
							<div class="wdm-table-actions">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="wdm-btn wdm-btn-sm">
									<span class="dashicons dashicons-edit"></span>
									<?php esc_html_e( 'Edit', 'wprobo-documerge-lite' ); ?>
								</a>
								<button type="button" class="wdm-btn wdm-btn-sm wdm-btn-danger wdm-form-delete" data-id="<?php echo esc_attr( $form->id ); ?>">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete', 'wprobo-documerge-lite' ); ?>
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>

</div>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
