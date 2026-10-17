<?php
/**
 * Admin functionality: scripts and dependencies.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Enqueues admin scripts for the book post type.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Admin {

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
	}


	/**
	 * Load admin dependencies.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-metaboxes.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-rest-api.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-posts.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-dashboard.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-taxonomies.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-library-wp-query.php';
	}


	/**
	 * Enqueue admin scripts for the books list.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-book' !== $screen->id ) {
			return;
		}

		$read_handle = $this->plugin_name . '-admin-read';
		wp_enqueue_script( $read_handle, plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-admin-read.js', array(), $this->plugin_version, true );
		wp_localize_script(
			$read_handle,
			'libraryAdminRead',
			array(
				'restUrl'           => rest_url(),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				/* translators: %s: reading percentage. */
				'readPercentFormat' => str_replace( '%%', '%', __( '%s%% read', 'library' ) ),
			)
		);
		wp_enqueue_script( $this->plugin_name . '-admin-inline-edit', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-admin-inline-edit.js', array( 'inline-edit-post' ), $this->plugin_version, true );
	}
}
