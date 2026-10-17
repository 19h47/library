<?php
/**
 * Core plugin class.
 *
 * Defines internationalization, admin-specific hooks, and loader.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Core plugin class.
 *
 * Maintains the unique identifier and version of the plugin, and loads dependencies.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library {

	/**
	 * Plugin identifier.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_version
	 */
	protected $plugin_version;

	/**
	 * Loader instance.
	 *
	 * @since  1.0.0
	 * @var    Library_Loader $loader
	 */
	protected $loader;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name    Plugin identifier.
	 * @param string $plugin_version Plugin version.
	 */
	public function __construct( string $plugin_name, string $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_update_hooks();
	}


	/**
	 * Load required dependencies.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-library-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-library-updater.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-admin.php';

		$this->loader = new Library_Loader();
	}



	/**
	 * Register admin hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin      = new Library_Admin( $this->get_plugin_name(), $this->get_plugin_version() );
		$plugin_meta       = new Library_Meta();
		$plugin_metaboxes  = new Library_Metaboxes( $this->plugin_name, $this->plugin_version );
		$plugin_posts      = new Library_Posts( $this->plugin_name, $this->plugin_version );
		$plugin_rest_api   = new Library_Rest_API( $this->plugin_name, $this->plugin_version );
		$plugin_taxonomies = new Library_Taxonomies( $this->plugin_name, $this->plugin_version );
		$plugin_wp_query   = new Library_WP_Query();
		$plugin_dashboard  = new Library_Dashboard( $this->plugin_name, $this->plugin_version );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Meta.
		$this->loader->add_action( 'init', $plugin_meta, 'register' );
		$this->loader->add_action( 'added_post_meta', $plugin_meta, 'sync_reading_state', 10, 3 );
		$this->loader->add_action( 'updated_post_meta', $plugin_meta, 'sync_reading_state', 10, 3 );
		$this->loader->add_action( 'deleted_post_meta', $plugin_meta, 'sync_reading_state', 10, 3 );

		// Posts.
		$this->loader->add_action( 'admin_head', $plugin_posts, 'css' );
		$this->loader->add_action( 'manage_book_posts_custom_column', $plugin_posts, 'render_custom_columns', 10, 2 );
		$this->loader->add_action( 'quick_edit_custom_box', $plugin_posts, 'render_quick_edit', 10, 3 );
		$this->loader->add_filter( 'manage_book_posts_columns', $plugin_posts, 'add_custom_columns' );
		$this->loader->add_filter( 'manage_edit-book_sortable_columns', $plugin_posts, 'sortable_columns' );
		$this->loader->add_action( 'pre_get_posts', $plugin_posts, 'pre_get_books' );
		$this->loader->add_filter( 'wp_insert_post_data', $plugin_posts, 'slug', 10, 2 );
		$this->loader->add_action( 'save_post_book', $plugin_posts, 'save_quick_edit' );
		$this->loader->add_action( 'the_title', $plugin_posts, 'add_series_to_title', 10, 2 );
		$this->loader->add_action( 'the_title', $plugin_posts, 'add_volume_number_to_title', 10, 2 );
		$this->loader->add_action( 'views_edit-book', $plugin_posts, 'add_list_table_views', 10, 1 );

		// Rest API.
		$this->loader->add_action( 'rest_api_init', $plugin_rest_api, 'register_rest_routes', 10, 0 );

		// Taxonomies.
		$this->loader->add_action( 'init', $plugin_taxonomies, 'register', 10, 0 );
		$this->loader->add_action( 'term_updated_messages', $plugin_taxonomies, 'messages', 10, 1 );
		$this->loader->add_filter( 'get_terms_args', $plugin_taxonomies, 'get_terms_args', 10, 2 );

		// Dashboard.
		$this->loader->add_action( 'wp_dashboard_setup', $plugin_dashboard, 'register_widget' );

		$this->loader->add_filter( 'posts_search', $plugin_wp_query, 'search', 10, 2 );
	}

	/**
	 * Register update hooks (GitHub releases).
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_update_hooks() {
		$plugin_updater = new Library_Updater( plugin_basename( LIBRARY_DIR_PATH . 'library.php' ) );

		$this->loader->add_filter( 'update_plugins_github.com', $plugin_updater, 'check_update', 10, 3 );
		$this->loader->add_filter( 'plugins_api', $plugin_updater, 'plugin_information', 20, 3 );
		$this->loader->add_filter( 'upgrader_source_selection', $plugin_updater, 'rename_source', 10, 4 );
	}

	/**
	 * Run the loader.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Plugin name.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Loader instance.
	 *
	 * @since  1.0.0
	 * @return Library_Loader
	 */
	public function get_loader(): Library_Loader {
		return $this->loader;
	}

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_plugin_version(): string {
		return $this->plugin_version;
	}
}
