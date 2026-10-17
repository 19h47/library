<?php
/**
 * REST API: reading stats.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers REST routes for books.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Rest_API {

	/**
	 * Plugin identifier.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_name
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @var    string $version
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin identifier.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register REST routes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->plugin_name . '/v1',
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( Library_Posts::get_reading_stats() ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			),
		);
	}
}
