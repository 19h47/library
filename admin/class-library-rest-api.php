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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'library/v1',
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( Library_Posts::get_reading_stats() ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			),
		);

		register_rest_route(
			'library/v1',
			'books/reading-idea',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_reading_idea' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'position' => array(
						'description' => __( 'Position in the week\'s list of ideas, 0 for the idea of the week.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 0,
						'default'     => 0,
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'books/achievements',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( ( new Library_Achievements() )->get_achievements() ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			),
		);
	}

	/**
	 * The reading idea of the week, or the one at the given position; the book is linked, and embeddable with _embed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_reading_idea( WP_REST_Request $request ) {
		$idea = ( new Library_Reading_Idea() )->get_idea( $request['position'] );

		if ( ! $idea ) {
			return new WP_Error( 'library_no_idea', __( 'Every book has been read.', 'library' ), array( 'status' => 404 ) );
		}

		$response = rest_ensure_response( $idea );
		$response->add_link( 'book', rest_url( 'wp/v2/books/' . $idea['id'] ), array( 'embeddable' => true ) );

		return $response;
	}
}
