<?php
/**
 * Main class for WP Hosting Directory plugin.
 *
 * @package WP_Hosting_Directory
 */

/**
 * Class WP_Hosting_Directory
 */
class WP_Hosting_Directory {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @var WP_Hosting_Directory_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->plugin_name = 'wp-hosting-directory';
		$this->version     = WP_HOSTING_DIRECTORY_VERSION;
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once WP_HOSTING_DIRECTORY_PLUGIN_DIR . 'includes/class-wp-hosting-directory-loader.php';
		require_once WP_HOSTING_DIRECTORY_PLUGIN_DIR . 'includes/class-wp-hosting-directory-admin.php';
		require_once WP_HOSTING_DIRECTORY_PLUGIN_DIR . 'includes/class-wp-hosting-directory-public.php';

		$this->loader = new WP_Hosting_Directory_Loader();
	}

	/**
	 * Define the admin-specific hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new WP_Hosting_Directory_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_admin, 'register_hosting_providers_cpt' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_review_menu' );
	}

	/**
	 * Define the public-facing hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public = new WP_Hosting_Directory_Public( $this->get_plugin_name(), $this->get_version() );
		 $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_shortcode( 'hosting_directory', $plugin_public, 'hosting_directory_shortcode' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
