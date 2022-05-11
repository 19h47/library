<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://www.19h47.fr
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */


/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Levron Jérémy <jeremylevron@19h47.fr>
 */
class Library_Admin {

	/**
	 * The name of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_name The ID of this plugin.
	 */
	protected $plugin_name;


	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_version The current version of this plugin.
	 */
	protected $plugin_version;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $plugin_version The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->load_dependencies();
	}


	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-posts.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-taxonomies.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-metaboxes.php';

		/**
		 * Columns
		 */
		// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-columns.php';

		// new Library_Columns( $this->plugin_name, $this->plugin_version );

		/**
		 * Quick edit
		 */
		// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-library-quick-edit.php';

		// new Library_Quick_Edit( $this->plugin_name, $this->plugin_version );
	}


	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name . '-admin-read', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-admin-read.js', array( 'jquery', 'wp-api' ), $this->plugin_version, true );
		wp_enqueue_script( $this->plugin_name . '-admin-inline-edit', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-admin-inline-edit.js', array( 'jquery' ), $this->plugin_version, true );
	}
}
