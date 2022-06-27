<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.19h47.fr
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */


/**
 * The core plugin class.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Levron Jérémy <levronjeremy@19h47.fr>
 */
class Library {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this theme.
	 */
	protected $plugin_name;


	/**
	 * The version of the theme.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this theme.
	 */
	protected $plugin_version;


	/**
	 * Construct function
	 *
	 * @access public
	 */
	public function __construct( string $plugin_name, string $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->load_dependencies();
		$this->define_admin_hooks();

		add_filter(
			'site_transient_update_plugins',
			function( $value ) {
				unset( $value->response['library/library.php'] );
				return $value;
			},
		);
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Run_Loader. Orchestrates the hooks of the plugin.
	 * - Run_Admin. Defines all hooks for the dashboard.
	 * - Run_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-library-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-admin.php';

		/**
		 * The class responsible for all global functions.
		 */
		// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api.php';

		$this->loader = new Library_Loader();
	}



	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
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

		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Posts
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

		// Settings
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

		// Rest API
		$this->loader->add_action( 'rest_api_init', $plugin_rest_api, 'register_rest_routes', 10, 1 );

		// Taxonomies
		$this->loader->add_action( 'init', $plugin_taxonomies, 'register', 10, 0 );
		$this->loader->add_action( 'term_updated_messages', $plugin_taxonomies, 'messages', 10, 1 );

		$this->loader->add_filter( 'posts_join', $plugin_wp_query, 'search_join', 10, 2 );
		$this->loader->add_filter( 'posts_where', $plugin_wp_query, 'search_where', 10, 2 );
		$this->loader->add_filter( 'posts_distinct', $plugin_wp_query, 'search_distinct', 10, 2 );
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}


	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() : string {
		return $this->plugin_name;
	}


	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since 1.0.0
	 * @return Run_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() : Library_Loader {
		return $this->loader;
	}


	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 0.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_plugin_version() : string {
		return $this->plugin_version;
	}
}
