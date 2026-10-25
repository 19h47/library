<?php
/**
 * REST API: reading stats, readings, reading idea, achievements, authors and the credits of a book.
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

		register_rest_route(
			'library/v1',
			'authors/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_author' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'Term ID of the author, or of one of their pseudonyms.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
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
		$total    = (int) Library_Dashboard::select( "SELECT COUNT( DISTINCT m.post_id, m.meta_value ) AS total $readings" )[0]->total;
		$rows     = Library_Dashboard::select( "SELECT DISTINCT m.post_id, m.meta_value $readings ORDER BY m.meta_value DESC, m.post_id DESC LIMIT %d OFFSET %d", $per_page, ( $request['page'] - 1 ) * $per_page );

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
	 * An author under their real name, with their pseudonyms, their books and their reading: the books read,
	 * the average rating and the completion their books count for.
	 *
	 * The books are those of the author and of their pseudonyms, kept or not, as the achievements count them,
	 * as IDs of the core API: in the order they first came out, then by title, those without date last.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_author( WP_REST_Request $request ) {
		$term   = get_term( $request['id'], 'library-author' );
		$author = $term instanceof WP_Term && $term->parent ? get_term( $term->parent, 'library-author' ) : $term;

		if ( ! $author instanceof WP_Term ) {
			return new WP_Error( 'library_no_author', get_taxonomy( 'library-author' )->labels->not_found, array( 'status' => 404 ) );
		}

		$books      = array_filter( Library_Books::all(), fn( array $book ) => in_array( $author->term_id, $book['author_ids'], true ) );
		$ratings    = array_filter( array_column( $books, 'rating' ), fn( ?float $rating ) => null !== $rating );
		$pseudonyms = get_terms(
			array(
				'taxonomy'   => 'library-author',
				'parent'     => $author->term_id,
				'hide_empty' => false,
				'fields'     => 'names',
			)
		);
		$first_date = fn( array $book ) => '' !== $book['published_date'] ? $book['published_date'] : $book['edition_date'];

		usort(
			$books,
			function ( array $a, array $b ) use ( $first_date ): int {
				$order = array( '' === $first_date( $a ), $first_date( $a ) ) <=> array( '' === $first_date( $b ), $first_date( $b ) );

				return 0 !== $order ? $order : strnatcasecmp( $a['full_title'], $b['full_title'] );
			}
		);

		return rest_ensure_response(
			array(
				'id'         => $author->term_id,
				'name'       => $author->name,
				'pseudonyms' => is_array( $pseudonyms ) ? $pseudonyms : array(),
				'books'      => array_column( $books, 'id' ),
				'read'       => count( array_filter( $books, fn( array $book ) => $book['read'] ) ),
				'rating'     => $ratings ? round( array_sum( $ratings ) / count( $ratings ), 1 ) : null,
				'completion' => ( new Library_Achievements() )->get_author_completion( $author->term_id ),
			)
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
