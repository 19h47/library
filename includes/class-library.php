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

		add_filter(
			'site_transient_update_plugins',
			function ( $value ) {
				unset( $value->response['library/library.php'] );
				return $value;
			},
		);
	}


	/**
	 * Load required dependencies.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-library-loader.php';
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
		$plugin_metaboxes  = new Library_Metaboxes( $this->plugin_name, $this->plugin_version );
		$plugin_posts      = new Library_Posts( $this->plugin_name, $this->plugin_version );
		$plugin_rest_api   = new Library_Rest_API( $this->plugin_name, $this->plugin_version );
		$plugin_settings   = new Library_Settings( $this->plugin_name, $this->plugin_version );
		$plugin_taxonomies = new Library_Taxonomies( $this->plugin_name, $this->plugin_version );
		$plugin_wp_query   = new Library_WP_Query( $this->plugin_name, $this->plugin_version );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Posts.
		$this->loader->add_action( 'admin_head', $plugin_posts, 'css' );
		$this->loader->add_action( 'manage_book_posts_custom_column', $plugin_posts, 'render_custom_columns', 10, 2 );
		$this->loader->add_action( 'quick_edit_custom_box', $plugin_posts, 'render_quick_edit', 10, 3 );
		$this->loader->add_filter( 'manage_book_posts_columns', $plugin_posts, 'add_custom_columns' );
		$this->loader->add_action( 'rest_api_init', $plugin_posts, 'register_rest_fields' );
		$this->loader->add_filter( 'manage_edit-book_sortable_columns', $plugin_posts, 'sortable_columns' );
		$this->loader->add_action( 'pre_get_posts', $plugin_posts, 'pre_get_books' );
		$this->loader->add_action( 'save_post_book', $plugin_posts, 'save', 10, 3 );
		$this->loader->add_action( 'save_post_book', $plugin_posts, 'save_quick_edit', 10, 3 );
		$this->loader->add_action( 'the_title', $plugin_posts, 'add_series_to_title', 10, 2 );
		$this->loader->add_action( 'the_title', $plugin_posts, 'add_volume_number_to_title', 10, 2 );
		$this->loader->add_action( 'views_edit-book', $plugin_posts, 'add_list_table_views', 10, 1 );

		// Settings.
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

		// Rest API.
		$this->loader->add_action( 'rest_api_init', $plugin_rest_api, 'register_rest_routes', 10, 1 );

		// Taxonomies.
		$this->loader->add_action( 'init', $plugin_taxonomies, 'register', 10, 0 );
		$this->loader->add_action( 'term_updated_messages', $plugin_taxonomies, 'messages', 10, 1 );
		$this->loader->add_action( 'pre_get_posts', $plugin_taxonomies, 'pre_get_books', 10, 1 );
		$this->loader->add_filter( 'get_terms_args', $plugin_taxonomies, 'get_terms_args', 10, 2 );

		$this->loader->add_filter( 'posts_join', $plugin_wp_query, 'search_join', 10, 2 );
		$this->loader->add_filter( 'posts_where', $plugin_wp_query, 'search_where', 10, 2 );
		$this->loader->add_filter( 'posts_distinct', $plugin_wp_query, 'search_distinct', 10, 2 );
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
