<?php
/**
 * REST API: reading stats, readings, reading idea, achievements and the credits of a book.
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
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	/**
	 * Register the fields added to the books of the core REST API.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		register_rest_field(
			'book',
			'credits',
			array(
				'get_callback' => fn( array $book ) => Library_Books::credits( $book['id'] ),
				'schema'       => array(
					'description' => __( 'Credits of the book, to show under its title: authors · publisher, collection · edition year.', 'library' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
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
				'callback'            => fn() => rest_ensure_response( Library_Posts::get_reading_stats() + array( 'year_to_date' => Library_Dashboard::get_year_to_date() ) ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			),
		);

		register_rest_route(
			'library/v1',
			'books/readings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_readings' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'page'     => array(
						'description' => __( 'Page of the readings.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
						'default'     => 1,
					),
					'per_page' => array(
						'description' => __( 'Number of readings per page.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
						'default'     => 30,
					),
				),
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
	 * The readings, latest first: one per read date, so a book read again comes once per reading, kept or not.
	 *
	 * The book of each reading is linked, and embeddable with _embed; the totals are in the X-WP-Total and X-WP-TotalPages headers.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_readings( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$per_page = $request['per_page'];
		$readings = "FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id WHERE m.meta_key = 'read_date' AND m.meta_value != '' AND {books}";
		$total    = (int) Library_Dashboard::select( "SELECT COUNT(*) AS total $readings" )[0]->total;
		$rows     = Library_Dashboard::select( "SELECT m.post_id, m.meta_value $readings ORDER BY m.meta_value DESC, m.meta_id DESC LIMIT %d OFFSET %d", $per_page, ( $request['page'] - 1 ) * $per_page );

		$response = rest_ensure_response(
			array_map(
				fn( object $row ) => array(
					'date'   => $row->meta_value,
					'book'   => (int) $row->post_id,
					'_links' => array(
						'book' => array(
							array(
								'href'       => rest_url( 'wp/v2/books/' . $row->post_id ),
								'embeddable' => true,
							),
						),
					),
				),
				$rows
			)
		);

		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );

		return $response;
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
