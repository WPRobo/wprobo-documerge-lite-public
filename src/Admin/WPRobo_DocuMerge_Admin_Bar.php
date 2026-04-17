<?php
/**
 * Admin Bar integration for WPRobo DocuMerge.
 *
 * Adds a DocuMerge node to the WordPress admin bar with links to
 * Dashboard, Forms, Templates, Submissions, and Settings. On the
 * frontend, detects if the current page contains a DocuMerge form
 * and adds an "Edit Form" shortcut when exactly one form is found.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Admin_Bar
 *
 * Registers a DocuMerge parent node in the WordPress admin bar with
 * sub-menu links. On frontend pages that embed a single DocuMerge
 * form (via shortcode or Gutenberg block), an
 * additional "Edit Form" link is shown.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Admin_Bar {

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'admin_bar_menu', array( $this, 'wprobo_documerge_add_admin_bar_menu' ), 80 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wprobo_documerge_admin_bar_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wprobo_documerge_admin_bar_css' ) );
	}

	/**
	 * Add DocuMerge menu to the WordPress admin bar.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 */
	public function wprobo_documerge_add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Parent node.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'wprobo-documerge',
				'title' => '<span class="ab-icon dashicons dashicons-media-document" style="font-family:dashicons;font-size:18px;line-height:1.6;"></span>' . esc_html__( 'DocuMerge', 'wprobo-documerge-lite' ),
				'href'  => admin_url( 'admin.php?page=wprobo-documerge' ),
			)
		);

		// Dashboard.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'wprobo-documerge-dashboard',
				'parent' => 'wprobo-documerge',
				'title'  => esc_html__( 'Dashboard', 'wprobo-documerge-lite' ),
				'href'   => admin_url( 'admin.php?page=wprobo-documerge' ),
			)
		);

		// Forms.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'wprobo-documerge-forms',
				'parent' => 'wprobo-documerge',
				'title'  => esc_html__( 'Forms', 'wprobo-documerge-lite' ),
				'href'   => admin_url( 'admin.php?page=wprobo-documerge-forms' ),
			)
		);

		// Templates.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'wprobo-documerge-templates',
				'parent' => 'wprobo-documerge',
				'title'  => esc_html__( 'Templates', 'wprobo-documerge-lite' ),
				'href'   => admin_url( 'admin.php?page=wprobo-documerge-templates' ),
			)
		);

		// Submissions.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'wprobo-documerge-submissions',
				'parent' => 'wprobo-documerge',
				'title'  => esc_html__( 'Submissions', 'wprobo-documerge-lite' ),
				'href'   => admin_url( 'admin.php?page=wprobo-documerge-submissions' ),
			)
		);

		// Settings.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'wprobo-documerge-settings',
				'parent' => 'wprobo-documerge',
				'title'  => esc_html__( 'Settings', 'wprobo-documerge-lite' ),
				'href'   => admin_url( 'admin.php?page=wprobo-documerge-settings' ),
			)
		);

		// Frontend: detect form(s) on the current page and add Edit Form link.
		if ( ! is_admin() && is_singular() ) {
			$form_ids = $this->wprobo_documerge_detect_forms_on_page();

			if ( 1 === count( $form_ids ) ) {
				$form_id = $form_ids[0];

				// Separator.
				$wp_admin_bar->add_node(
					array(
						'id'     => 'wprobo-documerge-separator',
						'parent' => 'wprobo-documerge',
						'title'  => '<hr style="margin:4px 0;border:0;border-top:1px solid rgba(255,255,255,0.2);">',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'id'     => 'wprobo-documerge-edit-form',
						'parent' => 'wprobo-documerge',
						'title'  => esc_html__( 'Edit This Form', 'wprobo-documerge-lite' ),
						'href'   => admin_url( 'admin.php?page=wprobo-documerge-forms&action=edit&id=' . absint( $form_id ) ),
						'meta'   => array(
							'title' => esc_attr__( 'Edit the DocuMerge form on this page', 'wprobo-documerge-lite' ),
						),
					)
				);
			}
		}
	}

	/**
	 * Detect DocuMerge form IDs embedded on the current page.
	 *
	 * Checks for the [wprobo_documerge_form] shortcode, the Gutenberg block
	 * wprobo-documerge/form-embed.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of unique form IDs found on the page.
	 */
	private function wprobo_documerge_detect_forms_on_page() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) || empty( $post->post_content ) ) {
			return array();
		}

		$form_ids = array();
		$content  = $post->post_content;

		// 1. Shortcode detection: [wprobo_documerge_form id="X"]
		if ( has_shortcode( $content, 'wprobo_documerge_form' ) ) {
			if ( preg_match_all( '/\[wprobo_documerge_form\s[^\]]*id=["\']?(\d+)["\']?/i', $content, $matches ) ) {
				foreach ( $matches[1] as $id ) {
					$form_ids[] = absint( $id );
				}
			}
		}

		// 2. Gutenberg block detection: <!-- wp:wprobo-documerge/form-embed {"formId":X} -->
		if ( has_block( 'wprobo-documerge/form-embed', $post ) ) {
			if ( preg_match_all( '/<!-- wp:wprobo-documerge\/form-embed\s+(\{[^}]*\})/i', $content, $matches ) ) {
				foreach ( $matches[1] as $json ) {
					$attrs = json_decode( $json, true );
					if ( ! empty( $attrs['formId'] ) ) {
						$form_ids[] = absint( $attrs['formId'] );
					}
				}
			}
		}

		// Remove duplicates and zeros.
		$form_ids = array_unique( array_filter( $form_ids ) );

		return array_values( $form_ids );
	}

	/**
	 * Attach minimal CSS for the admin bar DocuMerge icon via
	 * wp_add_inline_style() on the core admin-bar stylesheet.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_admin_bar_css() {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$css = '#wp-admin-bar-wprobo-documerge > .ab-item .ab-icon{margin-right:4px;top:2px}'
			. '#wp-admin-bar-wprobo-documerge-separator > .ab-item{padding:0!important;height:auto!important;line-height:1!important}';
		wp_add_inline_style( 'admin-bar', $css );
	}
}
