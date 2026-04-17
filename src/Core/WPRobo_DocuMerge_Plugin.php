<?php
/**
 * Plugin bootstrap class.
 *
 * Singleton that initialises the plugin and registers all WordPress hooks.
 *
 * @package    WPRobo_DocuMerge
 * @subpackage WPRobo_DocuMerge/src/Core
 * @author     Ali Shan <hello@wprobo.com>
 * @link       https://wprobo.com/plugins/wprobo-documerge
 * @since      1.0.0
 */

namespace WPRobo\DocuMerge\Core;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.Security.NonceVerification.Recommended, PluginCheck.Security.DirectDB
}

/**
 * Class WPRobo_DocuMerge_Plugin
 *
 * Main plugin bootstrap — registers hooks, loads text domain,
 * and wires all plugin components together.
 *
 * @since 1.0.0
 */
class WPRobo_DocuMerge_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var   WPRobo_DocuMerge_Plugin|null
	 */
	private static $wprobo_documerge_instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return WPRobo_DocuMerge_Plugin
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
		// Intentionally empty — hooks registered in wprobo_documerge_run().
	}

	/**
	 * Run the plugin — register all hooks.
	 *
	 * Called on the `plugins_loaded` action to ensure WordPress
	 * and all other plugins are fully loaded before we initialise.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_run() {
		$this->wprobo_documerge_register_hooks();
	}

	/**
	 * Register the Gutenberg block for embedding forms.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_register_blocks() {
		// Register the frontend CSS so the block preview is styled in the editor.
		wp_register_style(
			'wprobo-documerge-frontend-block',
			WPROBO_DOCUMERGE_URL . 'assets/css/frontend/form.min.css',
			array( 'dashicons' ),
			WPROBO_DOCUMERGE_VERSION
		);

		register_block_type(
			WPROBO_DOCUMERGE_PATH . 'blocks/form-embed/',
			array(
				'render_callback' => array( $this, 'wprobo_documerge_render_form_block' ),
				'style'           => 'wprobo-documerge-frontend-block',
			)
		);

		// Pass forms list to block editor with extended details.
		if ( is_admin() ) {
			global $wpdb;
			$forms = $wpdb->get_results(
				"SELECT f.id, f.title, f.fields, t.name AS template_name
                 FROM {$wpdb->prefix}wprdm_forms f
                 LEFT JOIN {$wpdb->prefix}wprdm_templates t ON f.template_id = t.id
                 ORDER BY f.title ASC"
			);

			$forms_data = array();
			if ( $forms ) {
				foreach ( $forms as $form ) {
					$fields_arr   = json_decode( isset( $form->fields ) ? $form->fields : '[]', true );
					$forms_data[] = array(
						'id'            => absint( $form->id ),
						'title'         => sanitize_text_field( $form->title ),
						'field_count'   => is_array( $fields_arr ) ? count( $fields_arr ) : 0,
						'template_name' => sanitize_text_field( $form->template_name ? $form->template_name : '' ),
					);
				}
			}

			wp_localize_script(
				'wprobo-documerge-form-embed-editor-script',
				'wprobo_documerge_block_vars',
				array(
					'forms'     => $forms_data,
					'admin_url' => admin_url( 'admin.php' ),
				)
			);
		}
	}

	/**
	 * Render the form embed block on the frontend.
	 *
	 * @since  1.0.0
	 * @param  array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function wprobo_documerge_render_form_block( $attributes ) {
		$form_id = isset( $attributes['formId'] ) ? absint( $attributes['formId'] ) : 0;
		if ( ! $form_id ) {
			return '<p class="wdm-block-placeholder">' .
					esc_html__( 'Please select a form in the block settings.', 'wprobo-documerge-lite' ) .
					'</p>';
		}
		return \WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Renderer::get_instance()
			->wprobo_documerge_render( $form_id );
	}

	/**
	 * Register all WordPress hooks for the plugin.
	 *
	 * Each component class will be instantiated and its hooks
	 * registered here as the plugin grows across sessions.
	 *
	 * @since 1.0.0
	 */
	private function wprobo_documerge_register_hooks() {
		// Delivery Engine — handles public download URLs and cron hooks (runs on all requests).
		$delivery = new \WPRobo\DocuMerge\Document\WPRobo_DocuMerge_Delivery_Engine();
		$delivery->wprobo_documerge_init_hooks();

		// Form Renderer — shortcode + frontend assets (runs on all requests).
		$renderer = \WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Renderer::get_instance();
		$renderer->wprobo_documerge_register_shortcode();

		// Gutenberg block registration.
		add_action( 'init', array( $this, 'wprobo_documerge_register_blocks' ) );

		// Form Submission AJAX handler (runs on all requests — handles nopriv too).
		$submission = new \WPRobo\DocuMerge\Form\WPRobo_DocuMerge_Form_Submission();
		$submission->wprobo_documerge_init_hooks();

		// Admin Bar — add DocuMerge menu to WordPress toolbar.
		$admin_bar = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Admin_Bar();
		$admin_bar->wprobo_documerge_init_hooks();

		// Assets — registers both admin AND frontend enqueue hooks.
		$assets = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Assets::get_instance();
		$assets->wprobo_documerge_init_hooks();

		// Plugin action links on the Plugins page (Settings, Forms).
		add_filter( 'plugin_action_links_' . WPROBO_DOCUMERGE_BASENAME, array( $this, 'wprobo_documerge_plugin_action_links' ) );

		if ( is_admin() ) {
			// Admin menu.
			$admin_menu = \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Admin_Menu::get_instance();
			$admin_menu->wprobo_documerge_init_hooks();

			// Settings AJAX handlers.
			$settings = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Settings_Page();
			$settings->wprobo_documerge_init_hooks();

			// Template Manager AJAX handlers.
			$templates_page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Templates_Page();
			$templates_page->wprobo_documerge_init_hooks();

			// Form Builder AJAX handlers.
			$forms_page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Forms_Page();
			$forms_page->wprobo_documerge_init_hooks();

			// Submissions AJAX handlers.
			$submissions_page = new \WPRobo\DocuMerge\Admin\WPRobo_DocuMerge_Submissions_Page();
			$submissions_page->wprobo_documerge_init_hooks();

			// Allow saving the per-page screen options for list tables.
			add_filter( 'set-screen-option', array( $this, 'wprobo_documerge_set_screen_option' ), 10, 3 );
			add_filter( 'set_screen_option_wprobo_documerge_submissions_per_page', array( $this, 'wprobo_documerge_set_screen_option' ), 10, 3 );
			add_filter( 'set_screen_option_wprobo_documerge_forms_per_page', array( $this, 'wprobo_documerge_set_screen_option' ), 10, 3 );

			// Setup Wizard — show on first activation only.
			if ( 'yes' !== get_option( 'wprobo_documerge_wizard_completed', 'no' ) ) {
				$wizard = new \WPRobo\DocuMerge\Wizard\WPRobo_DocuMerge_Setup_Wizard();
				$wizard->wprobo_documerge_init_hooks();
			}
		}

		// Show Pro upgrade banner on admin pages.
		add_action( 'admin_notices', array( $this, 'wprobo_documerge_lite_admin_notice' ) );

		// Dev-only: seed demo data when ?seed_demo=1 on a DocuMerge admin page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && isset( $_GET['seed_demo'] ) && '1' === $_GET['seed_demo'] ) {
			add_action( 'admin_init', array( $this, 'wprobo_documerge_run_seeder' ) );
		}
	}

	/**
	 * Display Lite edition admin notice with upgrade prompt.
	 *
	 * Shows on DocuMerge admin pages only. Dismissible via user meta.
	 *
	 * @since 1.0.0
	 */
	public function wprobo_documerge_lite_admin_notice() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'wprobo-documerge' ) === false ) {
			return;
		}
		// Only show once per session (dismiss with user meta).
		if ( get_user_meta( get_current_user_id(), 'wprobo_documerge_lite_notice_dismissed', true ) ) {
			return;
		}
		$upgrade_url = WPROBO_DOCUMERGE_UPGRADE_URL;
		?>
		<div class="wdm-lite-upgrade-banner">
			<div class="wdm-lite-upgrade-banner-content">
				<span class="dashicons dashicons-star-filled wdm-lite-upgrade-banner-icon"></span>
				<div>
					<strong class="wdm-lite-upgrade-banner-title"><?php esc_html_e( 'Upgrade to DocuMerge Pro', 'wprobo-documerge-lite' ); ?></strong>
					<p class="wdm-lite-upgrade-banner-desc"><?php esc_html_e( 'Unlock Stripe payments, signature fields, multi-step forms, conditional logic, email delivery, and 20+ more features.', 'wprobo-documerge-lite' ); ?></p>
				</div>
			</div>
			<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="wdm-lite-upgrade-banner-btn">
				<?php esc_html_e( 'Upgrade Now', 'wprobo-documerge-lite' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Save the per-page screen option for submissions list table.
	 *
	 * @since  1.2.0
	 * @param  mixed  $status Screen option value (false to skip saving).
	 * @param  string $option The option name.
	 * @param  mixed  $value  The submitted value.
	 * @return mixed
	 */
	public function wprobo_documerge_set_screen_option( $status, $option, $value ) {
		$allowed_options = array(
			'wprobo_documerge_submissions_per_page',
			'wprobo_documerge_forms_per_page',
		);

		if ( in_array( $option, $allowed_options, true ) ) {
			return absint( $value );
		}

		return $status;
	}

	/**
	 * Add Settings and Forms links to the Plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Existing action links.
	 * @return array Modified links.
	 */
	public function wprobo_documerge_plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wprobo-documerge-settings' ) ) . '">' . esc_html__( 'Settings', 'wprobo-documerge-lite' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=wprobo-documerge-forms' ) ) . '">' . esc_html__( 'Forms', 'wprobo-documerge-lite' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Run the demo data seeder script (dev-only).
	 *
	 * Triggered by ?seed_demo=1 on any admin page.
	 * The seeder file is excluded from production builds.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function wprobo_documerge_run_seeder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$seeder_path = WPROBO_DOCUMERGE_PATH . 'scripts/seed-demo-data.php';

		if ( ! file_exists( $seeder_path ) ) {
			wp_die(
				esc_html__( 'Seeder script not found. This feature is only available in the development version.', 'wprobo-documerge-lite' ),
				esc_html__( 'DocuMerge Seeder', 'wprobo-documerge-lite' ),
				array( 'back_link' => true )
			);
		}

		include $seeder_path;
		exit;
	}
}
