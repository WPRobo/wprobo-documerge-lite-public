<?php
/**
 * Admin menu registration class.
 *
 * Registers the top-level DocuMerge menu and all submenu pages
 * inside the WordPress admin sidebar.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Admin
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPRobo_DocuMerge_Admin_Menu
 *
 * Registers the parent menu and six submenu items for the
 * DocuMerge plugin in the WordPress admin area.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Admin_Menu {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Admin_Menu|null
	 */
	private static $wprobo_documerge_instance = null;

	/**
	 * Base64-encoded SVG icon for the admin menu.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private $wprobo_documerge_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0iY3VycmVudENvbG9yIj48cmVjdCB4PSIzIiB5PSIyIiB3aWR0aD0iMTEiIGhlaWdodD0iMTQiIHJ4PSIxLjUiIGZpbGw9Im5vbmUiIHN0cm9rZT0iY3VycmVudENvbG9yIiBzdHJva2Utd2lkdGg9IjEuNSIvPjxwYXRoIGQ9Ik02IDZoNU02IDloNE02IDEyaDMiIHN0cm9rZT0iY3VycmVudENvbG9yIiBzdHJva2Utd2lkdGg9IjEiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPjxwYXRoIGQ9Ik0xNCA3bDMgMy0zIDMiIHN0cm9rZT0iY3VycmVudENvbG9yIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+PC9zdmc+';

	/**
	 * Required capability for accessing admin pages.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private $wprobo_documerge_capability = 'manage_options';

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return WPRobo_DocuMerge_Admin_Menu
	 */
	public static function get_instance() {
		if ( null === self::$wprobo_documerge_instance ) {
			self::$wprobo_documerge_instance = new self();
		}
		return self::$wprobo_documerge_instance;
	}

	/**
	 * Constructor — private for singleton.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Intentionally empty — hooks registered via wprobo_documerge_init_hooks().
	}

	/**
	 * Register WordPress hooks for the admin menu.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_init_hooks() {
		add_action( 'admin_menu', array( $this, 'wprobo_documerge_register_menus' ) );
	}

	/**
	 * Register the parent menu and all submenu pages.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_register_menus() {

		/**
		 * Filters the capability required to access DocuMerge admin pages.
		 *
		 * Allows changing the minimum capability from the default
		 * 'manage_options' to a custom capability for finer access control.
		 *
		 * @since 1.2.0
		 *
		 * @param string $capability The WordPress capability. Default 'manage_options'.
		 */
		$this->wprobo_documerge_capability = apply_filters( 'wprobo_documerge_admin_menu_capability', $this->wprobo_documerge_capability );

		// Parent menu — DocuMerge.
		$menu_label = defined( 'WPROBO_DOCUMERGE_LITE' ) && WPROBO_DOCUMERGE_LITE ? __( 'DocuMerge Lite', 'wprobo-documerge-lite' ) : __( 'DocuMerge', 'wprobo-documerge-lite' );
		add_menu_page(
			__( 'DocuMerge', 'wprobo-documerge-lite' ),
			$menu_label,
			$this->wprobo_documerge_capability,
			'wprobo-documerge',
			array( $this, 'wprobo_documerge_render_dashboard' ),
			$this->wprobo_documerge_icon,
			30
		);

		// Submenu — Dashboard (replaces default parent duplicate).
		add_submenu_page(
			'wprobo-documerge',
			__( 'Dashboard', 'wprobo-documerge-lite' ),
			__( 'Dashboard', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge',
			array( $this, 'wprobo_documerge_render_dashboard' )
		);

		// Submenu — Templates.
		add_submenu_page(
			'wprobo-documerge',
			__( 'Templates', 'wprobo-documerge-lite' ),
			__( 'Templates', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge-templates',
			array( $this, 'wprobo_documerge_render_templates' )
		);

		// Submenu — Forms (with screen options for WP_List_Table).
		$forms_hook = add_submenu_page(
			'wprobo-documerge',
			__( 'Forms', 'wprobo-documerge-lite' ),
			__( 'Forms', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge-forms',
			array( $this, 'wprobo_documerge_render_forms' )
		);

		// Register screen options for per-page setting.
		add_action( 'load-' . $forms_hook, array( $this, 'wprobo_documerge_forms_screen_options' ) );

		// Submenu — Submissions (with screen options for WP_List_Table).
		$submissions_hook = add_submenu_page(
			'wprobo-documerge',
			__( 'Submissions', 'wprobo-documerge-lite' ),
			__( 'Submissions', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge-submissions',
			array( $this, 'wprobo_documerge_render_submissions' )
		);

		// Register screen options for per-page setting.
		add_action( 'load-' . $submissions_hook, array( $this, 'wprobo_documerge_submissions_screen_options' ) );

		// Submenu — Settings.
		add_submenu_page(
			'wprobo-documerge',
			__( 'Settings', 'wprobo-documerge-lite' ),
			__( 'Settings', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge-settings',
			array( $this, 'wprobo_documerge_render_settings' )
		);

		// Submenu — Help & Support.
		add_submenu_page(
			'wprobo-documerge',
			__( 'Help & Support', 'wprobo-documerge-lite' ),
			__( 'Help & Support', 'wprobo-documerge-lite' ),
			$this->wprobo_documerge_capability,
			'wprobo-documerge-help',
			array( $this, 'wprobo_documerge_render_help' )
		);
	}

	/**
	 * Render the Dashboard admin page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_dashboard() {
		$dashboard_page = new WPRobo_DocuMerge_Dashboard_Page();
		$dashboard_page->wprobo_documerge_render();
	}

	/**
	 * Render the Templates admin page (placeholder).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_templates() {
		$page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Templates_Page();
		$page->wprobo_documerge_render();
	}

	/**
	 * Render the Forms admin page (placeholder).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_forms() {
		$page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Forms_Page();
		$page->wprobo_documerge_render();
	}

	/**
	 * Render the Submissions admin page (placeholder).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_submissions() {
		$page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Submissions_Page();
		$page->wprobo_documerge_render();
	}

	/**
	 * Register screen options for the forms list table.
	 *
	 * Called via load-{page} hook before any output, enabling
	 * the per-page screen option and initialising the list table.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function wprobo_documerge_forms_screen_options() {
		$page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Forms_Page();
		$page->wprobo_documerge_screen_options();
	}

	/**
	 * Register screen options for the submissions list table.
	 *
	 * Called via load-{page} hook before any output, enabling
	 * the per-page screen option and initialising the list table.
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function wprobo_documerge_submissions_screen_options() {
		$page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Submissions_Page();
		$page->wprobo_documerge_screen_options();
	}

	/**
	 * Render the Settings admin page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_settings() {
		$settings_page = new WPRobo_DocuMerge_Settings_Page();
		$settings_page->wprobo_documerge_render();
	}

	/**
	 * Render the Help & Support admin page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_render_help() {
		$help_page = new WPRobo_DocuMerge_Help_Page();
		$help_page->wprobo_documerge_render();
	}
}
