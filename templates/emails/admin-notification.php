<?php
/**
 * Admin notification email template.
 *
 * Sent to the site admin when a new form submission is received.
 *
 * Variables available:
 * @var object $submission    The submission object.
 * @var string $form_title    The form title.
 * @var string $template_name The document template name.
 * @var string $admin_url     The admin submission view URL.
 *
 * @package WPRobo_DocuMerge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$email_title = __( 'New Form Submission', 'wprobo-documerge-lite' );

ob_start();
?>
<h2 style="margin: 0 0 20px 0; color: #042157; font-size: 20px; font-weight: 700; line-height: 1.3;">
	<?php esc_html_e( 'New Form Submission', 'wprobo-documerge-lite' ); ?>
</h2>

<p style="margin: 0 0 24px 0; color: #1a1a1a; font-size: 15px; line-height: 1.6;">
	<?php
	printf(
		/* translators: %s: form title */
		esc_html__( 'A new submission has been received for %s.', 'wprobo-documerge-lite' ),
		esc_html( $form_title )
	);
	?>
</p>

<div style="background-color: #f0f4fa; padding: 20px 24px; border-radius: 6px; margin: 0 0 28px 0;">
	<table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #1a1a1a;">
		<tr>
			<td style="padding: 6px 0; color: #6b7280; font-weight: 600; width: 130px; vertical-align: top;">
				<?php esc_html_e( 'Submission ID', 'wprobo-documerge-lite' ); ?>
			</td>
			<td style="padding: 6px 0;">
				<?php
				printf(
					'#%s',
					esc_html( $submission->id )
				);
				?>
			</td>
		</tr>
		<tr>
			<td style="padding: 6px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
				<?php esc_html_e( 'Date', 'wprobo-documerge-lite' ); ?>
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
		<tr>
			<td style="padding: 6px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
				<?php esc_html_e( 'Submitter', 'wprobo-documerge-lite' ); ?>
			</td>
			<td style="padding: 6px 0;">
				<?php echo esc_html( isset( $submission->submitter_email ) ? $submission->submitter_email : '—' ); ?>
			</td>
		</tr>
	</table>
</div>

<p style="margin: 0; text-align: center;">
	<a href="<?php echo esc_url( $admin_url ); ?>" style="display: inline-block; background-color: #042157; color: #ffffff; padding: 12px 28px; border-radius: 6px; font-weight: 700; font-size: 15px; text-decoration: none; line-height: 1.4;">
		<?php esc_html_e( 'View Submission in Admin', 'wprobo-documerge-lite' ); ?>
	</a>
</p>
<?php
$email_content = ob_get_clean();

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

require __DIR__ . '/base.php';
