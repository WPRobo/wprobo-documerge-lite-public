<?php
/**
 * Frontend form template.
 *
 * @package WPRobo\DocuMerge
 *
 * @var object $form      Form post object.
 * @var array  $fields    Array of field arrays.
 * @var array  $settings  Form settings.
 * @var int    $form_id   Form ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Decode settings with defaults.
// $submit_label and $classes are set by the renderer before this template is included.
$submit_label      = isset( $submit_label ) ? $submit_label : ( ! empty( $settings['submit_label'] ) ? $settings['submit_label'] : __( 'Submit', 'wprobo-documerge-lite' ) );
$success_message   = ! empty( $settings['success_message'] ) ? $settings['success_message'] : '';
$step_labels       = ! empty( $settings['multistep_labels'] ) ? $settings['multistep_labels'] : array( 'Step 1', 'Step 2', 'Step 3' );
$classes           = isset( $classes ) ? $classes : array( 'wdm-form-wrap' );
$multistep_enabled = isset( $form->multistep_enabled ) ? absint( $form->multistep_enabled ) : 0;
?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="wdm-form-<?php echo absint( $form_id ); ?>" data-form-id="<?php echo absint( $form_id ); ?>">
	<form class="wdm-form" id="wdm-form-el-<?php echo absint( $form_id ); ?>" method="post" novalidate>

		<?php wp_nonce_field( 'wprobo_documerge_frontend', 'nonce' ); ?>
		<input type="hidden" name="action" value="wprobo_documerge_submit_form">
		<input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>">

		<!-- Honeypot — hidden via CSS, not type="hidden" -->
		<div class="wdm-hp" aria-hidden="true">
			<label for="wdm-trap-<?php echo absint( $form_id ); ?>"><?php esc_html_e( 'Leave empty', 'wprobo-documerge-lite' ); ?></label>
			<input type="text" name="wdm_trap" id="wdm-trap-<?php echo absint( $form_id ); ?>" tabindex="-1" autocomplete="off" value="">
		</div>

		<?php if ( $multistep_enabled ) : ?>
			<!-- Multi-step progress -->
			<div class="wdm-multistep-progress">
				<div class="wdm-progress-bar">
					<div class="wdm-progress-fill" id="wdm-step-progress"></div>
				</div>
				<div class="wdm-step-labels">
					<?php foreach ( $step_labels as $i => $label ) : ?>
						<span class="wdm-step-label<?php echo 0 === $i ? ' wdm-step-active' : ''; ?>" data-step="<?php echo absint( $i + 1 ); ?>">
							<?php echo esc_html( $label ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Fields -->
		<div class="wdm-form-fields">
			<?php
			$current_step = 0;
			foreach ( $fields as $field ) :
				$step = isset( $field['step'] ) ? (int) $field['step'] : 1;

				if ( $multistep_enabled && $step !== $current_step ) :
					if ( $current_step > 0 ) :
						?>
						</div><!-- close previous step -->
						<?php
					endif;
					$current_step = $step;
					?>
					<div class="wdm-step<?php echo 1 === $step ? ' wdm-step-active' : ''; ?>" data-step="<?php echo absint( $step ); ?>">
					<?php
				endif;

				// Render field via FormRenderer.
				// The renderer already escapes every dynamic value via esc_attr()
				// when building the HTML. wp_kses() here is the final safety net
				// with a form-element allowlist (see allowed_form_html()).
				$renderer = \WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Renderer::get_instance();
				echo wp_kses(
					$renderer->wprobo_documerge_render_field( $field, $form_id ),
					\WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Renderer::wprobo_documerge_allowed_form_html()
				);

			endforeach;

			if ( $multistep_enabled && $current_step > 0 ) :
				?>
				</div><!-- close last step -->
			<?php endif; ?>
		</div>

		<!-- Navigation for multi-step -->
		<?php if ( $multistep_enabled ) : ?>
			<div class="wdm-form-nav">
				<button type="button" class="wdm-btn wdm-step-back wdm-step-back-btn" style="display:none;">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php esc_html_e( 'Previous', 'wprobo-documerge-lite' ); ?>
				</button>
				<button type="button" class="wdm-btn wdm-btn-primary wdm-step-next">
					<?php esc_html_e( 'Next Step', 'wprobo-documerge-lite' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</button>
			</div>
		<?php endif; ?>

		<!-- Submit button -->
		<?php
		// Button appearance settings.
		$btn_width      = ! empty( $settings['btn_width'] ) ? $settings['btn_width'] : 'auto';
		$btn_align      = ! empty( $settings['btn_align'] ) ? $settings['btn_align'] : 'right';
		$btn_style      = ! empty( $settings['btn_style'] ) ? $settings['btn_style'] : 'filled';
		$btn_size       = ! empty( $settings['btn_size'] ) ? $settings['btn_size'] : 'medium';
		$btn_radius     = isset( $settings['btn_radius'] ) ? $settings['btn_radius'] : '6';
		$btn_bg_color   = ! empty( $settings['btn_bg_color'] ) ? $settings['btn_bg_color'] : '#042157';
		$btn_text_color = ! empty( $settings['btn_text_color'] ) ? $settings['btn_text_color'] : '#ffffff';
		$btn_hover_bg   = ! empty( $settings['btn_hover_bg'] ) ? $settings['btn_hover_bg'] : '#0a3d8f';
		$btn_hover_text = ! empty( $settings['btn_hover_text'] ) ? $settings['btn_hover_text'] : '#ffffff';

		// Build submit wrapper alignment.
		$submit_align_map  = array(
			'left'   => 'left',
			'center' => 'center',
			'right'  => 'right',
		);
		$submit_text_align = isset( $submit_align_map[ $btn_align ] ) ? $submit_align_map[ $btn_align ] : 'right';

		// Build button CSS classes.
		$btn_classes = array( 'wdm-btn', 'wdm-submit-btn' );
		if ( 'outline' === $btn_style ) {
			$btn_classes[] = 'wdm-btn-outline';
		} else {
			$btn_classes[] = 'wdm-btn-primary';
		}
		if ( 'rounded' === $btn_style ) {
			$btn_classes[] = 'wdm-btn-rounded';
		}
		if ( 'full' === $btn_width ) {
			$btn_classes[] = 'wdm-btn-full-width';
		} elseif ( 'half' === $btn_width ) {
			$btn_classes[] = 'wdm-btn-half-width';
		}
		if ( 'small' === $btn_size ) {
			$btn_classes[] = 'wdm-btn-small';
		} elseif ( 'large' === $btn_size ) {
			$btn_classes[] = 'wdm-btn-large';
		}

		// Use CSS custom properties for all colors so hover can override inline specificity.
		$btn_inline_parts   = array();
		$btn_inline_parts[] = '--wdm-btn-bg:' . esc_attr( $btn_bg_color );
		$btn_inline_parts[] = '--wdm-btn-text:' . esc_attr( $btn_text_color );
		$btn_inline_parts[] = '--wdm-btn-hover-bg:' . esc_attr( $btn_hover_bg );
		$btn_inline_parts[] = '--wdm-btn-hover-text:' . esc_attr( $btn_hover_text );
		if ( 'outline' === $btn_style ) {
			$btn_inline_parts[] = '--wdm-btn-bg:transparent';
			$btn_inline_parts[] = '--wdm-btn-border:' . esc_attr( $btn_bg_color );
		}
		if ( '6' !== (string) $btn_radius && 'rounded' !== $btn_style ) {
			$btn_inline_parts[] = 'border-radius:' . absint( $btn_radius ) . 'px';
		}

		$btn_inline_style = implode( ';', $btn_inline_parts );
		?>
		<div class="wdm-form-submit" style="text-align:<?php echo esc_attr( $submit_text_align ); ?>;
		<?php
		if ( $multistep_enabled ) :
			?>
			display:none;<?php endif; ?>">
			<button type="submit" class="<?php echo esc_attr( implode( ' ', $btn_classes ) ); ?>" id="wdm-submit-<?php echo absint( $form_id ); ?>" style="<?php echo esc_attr( $btn_inline_style ); ?>">
				<span class="wdm-submit-text"><?php echo esc_html( $submit_label ); ?></span>
				<span class="wdm-submit-spinner" style="display:none;">
					<span class="wdm-spinner"></span>
					<?php esc_html_e( 'Processing...', 'wprobo-documerge-lite' ); ?>
				</span>
			</button>
		</div>
	</form>

	<!-- Success panel (hidden by default) -->
	<div class="wdm-form-success" style="display:none;">
		<div class="wdm-success-icon">
			<span class="dashicons dashicons-yes-alt"></span>
		</div>
		<h3 class="wdm-success-title"><?php esc_html_e( 'Your document is ready!', 'wprobo-documerge-lite' ); ?></h3>
		<p class="wdm-success-message" id="wdm-success-msg"></p>
		<a href="#" class="wdm-btn wdm-btn-primary wdm-download-btn" id="wdm-download-link" target="_blank">
			<span class="dashicons dashicons-download"></span>
			<?php esc_html_e( 'Download Your Document', 'wprobo-documerge-lite' ); ?>
		</a>
		<p class="wdm-success-email" id="wdm-success-email" style="display:none;"></p>
	</div>

	<!-- Error panel (hidden by default) -->
	<div class="wdm-form-error" style="display:none;">
		<div class="wdm-error-icon">
			<span class="dashicons dashicons-warning"></span>
		</div>
		<h3 class="wdm-error-title"><?php esc_html_e( 'Something went wrong', 'wprobo-documerge-lite' ); ?></h3>
		<p class="wdm-error-message"><?php esc_html_e( 'We could not process your request. Please try again or contact us.', 'wprobo-documerge-lite' ); ?></p>
		<button type="button" class="wdm-btn wdm-btn-primary wdm-try-again">
			<?php esc_html_e( 'Try Again', 'wprobo-documerge-lite' ); ?>
		</button>
	</div>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
