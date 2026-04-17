<?php
/**
 * Document delivery email template.
 *
 * Sent to the submitter when their document has been generated.
 *
 * Variables available:
 * @var object $submission    The submission object.
 * @var string $form_title    The form title.
 * @var string $template_name The document template name.
 * @var string $download_url  The document download URL.
 * @var string $submitter_name The submitter's name.
 *
 * @package WPRobo_DocuMerge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$email_title = __( 'Your Document Is Ready', 'wprobo-documerge-lite' );

ob_start();
?>
<h2 style="margin: 0 0 20px 0; color: #042157; font-size: 20px; font-weight: 700; line-height: 1.3;">
	<?php esc_html_e( 'Your Document Is Ready', 'wprobo-documerge-lite' ); ?>
</h2>

<p style="margin: 0 0 16px 0; color: #1a1a1a; font-size: 15px; line-height: 1.6;">
	<?php
	printf(
		/* translators: %s: submitter name */
		esc_html__( 'Hi %s,', 'wprobo-documerge-lite' ),
		esc_html( $submitter_name )
	);
	?>
</p>

<p style="margin: 0 0 24px 0; color: #1a1a1a; font-size: 15px; line-height: 1.6;">
	<?php
	printf(
		/* translators: %s: form title */
		esc_html__( 'Thank you for submitting the %s. Your personalised document has been generated.', 'wprobo-documerge-lite' ),
		esc_html( $form_title )
	);
	?>
</p>

<p style="margin: 0 0 16px 0; text-align: center;">
	<a href="<?php echo esc_url( $download_url ); ?>" style="display: inline-block; background-color: #042157; color: #ffffff; padding: 12px 28px; border-radius: 6px; font-weight: 700; font-size: 15px; text-decoration: none; line-height: 1.4;">
		<?php esc_html_e( 'Download Your Document', 'wprobo-documerge-lite' ); ?>
	</a>
</p>

<p style="margin: 0 0 28px 0; color: #6b7280; font-size: 13px; line-height: 1.5; word-break: break-all;">
	<?php esc_html_e( "If the button doesn't work, copy this link:", 'wprobo-documerge-lite' ); ?>
	<br />
	<a href="<?php echo esc_url( $download_url ); ?>" style="color: #042157; text-decoration: underline; font-size: 13px;">
		<?php echo esc_url( $download_url ); ?>
	</a>
</p>

<hr style="border: none; border-top: 1px solid #dde5f0; margin: 28px 0;" />

<div style="background-color: #f0f4fa; padding: 20px 24px; border-radius: 6px; margin: 0 0 24px 0;">
	<table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #1a1a1a;">
		<tr>
			<td style="padding: 6px 0; color: #6b7280; font-weight: 600; width: 120px; vertical-align: top;">
				<?php esc_html_e( 'Submitted', 'wprobo-documerge-lite' ); ?>
			</td>
			<td style="padding: 6px 0;">
				<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->created_at ) ) ); ?>
			</td>
		</tr>
		<tr>
			<td style="padding: 6px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
				<?php esc_html_e( 'Form', 'wprobo-documerge-lite' ); ?>
			</td>
			<td style="padding: 6px 0;">
				<?php echo esc_html( $form_title ); ?>
			</td>
		</tr>
	</table>
</div>

<p style="margin: 0; color: #1a1a1a; font-size: 15px; line-height: 1.6;">
	<?php esc_html_e( 'If you have any questions, please reply to this email.', 'wprobo-documerge-lite' ); ?>
</p>
<?php
$email_content = ob_get_clean();

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

require __DIR__ . '/base.php';
