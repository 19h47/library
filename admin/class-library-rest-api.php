<?php
/**
 * The settings of the plugin.
 *
 * @link       https://github.com/19h47/sellsy-clients/
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Class Library_Rest_API
 */
class Library_Rest_API {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}


	/**
	 * Register rest routes.
	 *
	 * @param WP_REST_Server $wp_rest_server Server object.
	 */
	public function register_rest_routes( WP_REST_Server $wp_rest_server ) {
		$namespace = $this->plugin_name . '/v1';
		$route     = '/settings/';

		register_rest_route(
			$namespace,
			$route . '(?P<name>[a-zA-Z0-9\-_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function( $request ) {
						if ( null !== $request->get_param( 'name' ) ) {
							return rest_ensure_response( get_option( $request->get_param( 'name' ) ) );
						} else {
							return new WP_Error( 'missing_fields', __( 'Please include name as a parameter', 'library' ) );
						}
					},
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function( $request ) {
						foreach ( json_decode( $request->get_body() ) as $key => $value ) {
							update_option( $key, $value );
						}
					},
					'permission_callback' => '__return_true',
				),
			),
		);
	}
}
