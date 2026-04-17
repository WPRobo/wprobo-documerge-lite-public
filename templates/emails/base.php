<?php
/**
 * Base email layout template.
 *
 * Variables available:
 * @var string $email_title   The email subject/title.
 * @var string $email_content Pre-sanitized HTML content.
 *
 * @package WPRobo_DocuMerge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( $email_title ); ?></title>
	<style>
		/* Reset */
		body, table, td, p, a, li, blockquote {
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		table, td {
			mso-table-lspace: 0;
			mso-table-rspace: 0;
		}
		img {
			-ms-interpolation-mode: bicubic;
			border: 0;
			outline: none;
			text-decoration: none;
		}
		body {
			margin: 0;
			padding: 0;
			background-color: #f0f4fa;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			color: #1a1a1a;
			font-size: 15px;
			line-height: 1.6;
		}
		.wdm-email-wrap {
			max-width: 600px;
			margin: 0 auto;
			padding: 40px 20px;
		}
		.wdm-email-header {
			background-color: #042157;
			padding: 28px 32px;
			border-radius: 8px 8px 0 0;
			text-align: center;
		}
		.wdm-email-header h1 {
			margin: 0;
			color: #ffffff;
			font-size: 22px;
			font-weight: 700;
			line-height: 1.3;
		}
		.wdm-email-body {
			background-color: #ffffff;
			padding: 36px 32px;
			border-left: 1px solid #dde5f0;
			border-right: 1px solid #dde5f0;
			border-bottom: 1px solid #dde5f0;
		}
		.wdm-email-footer {
			background-color: #f0f4fa;
			padding: 20px 32px;
			border-radius: 0 0 8px 8px;
			border-left: 1px solid #dde5f0;
			border-right: 1px solid #dde5f0;
			border-bottom: 1px solid #dde5f0;
			text-align: center;
		}
		.wdm-email-footer p {
			margin: 4px 0;
			color: #6b7280;
			font-size: 13px;
		}
		.wdm-email-footer a {
			color: #042157;
			text-decoration: none;
		}
	</style>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1a1a1a; font-size: 15px; line-height: 1.6;">
	<div class="wdm-email-wrap" style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">

		<!-- Header -->
		<div class="wdm-email-header" style="background-color: #042157; padding: 28px 32px; border-radius: 8px 8px 0 0; text-align: center;">
			<h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; line-height: 1.3;">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</h1>
		</div>

		<!-- Body -->
		<div class="wdm-email-body" style="background-color: #ffffff; padding: 36px 32px; border-left: 1px solid #dde5f0; border-right: 1px solid #dde5f0; border-bottom: 1px solid #dde5f0;">
			<?php
			// HTML email body — escape with wp_kses_post so common post-content
			// HTML (headings, paragraphs, links, lists, images) is preserved
			// while anything unsafe is stripped.
			echo wp_kses_post( $email_content );
			?>
		</div>

		<!-- Footer -->
		<div class="wdm-email-footer" style="background-color: #f0f4fa; padding: 20px 32px; border-radius: 0 0 8px 8px; border-left: 1px solid #dde5f0; border-right: 1px solid #dde5f0; border-bottom: 1px solid #dde5f0; text-align: center;">
			<p style="margin: 4px 0; color: #6b7280; font-size: 13px;">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color: #042157; text-decoration: none;">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>
			</p>
		</div>

	</div>
</body>
</html>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
