<?php
/**
 * REST API: a book added, reading stats, readings by month, reading idea, achievements, authors and the credits of a book.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Registers REST routes for books.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
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
			'books',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_book' ),
				'permission_callback' => fn() => current_user_can( get_post_type_object( 'book' )->cap->publish_posts ),
				'args'                => array(
					'title'      => array(
						'description'       => __( 'Title of the book.', 'library' ),
						'type'              => 'string',
						'required'          => true,
						'pattern'           => '\\S',
						// A sanitize callback leaves out the check against the schema, unless it is asked for.
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'authors'    => array(
						'description' => __( 'Names of the authors or pseudonyms; a name not found adds an author.', 'library' ),
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'default'     => array(),
					),
					'publisher'  => array(
						'description' => __( 'Name of the publisher; a name not found adds a publisher.', 'library' ),
						'type'        => 'string',
						'default'     => '',
					),
					'collection' => array(
						'description' => __( 'Name of the collection, under the publisher; a name not found adds a collection.', 'library' ),
						'type'        => 'string',
						'default'     => '',
					),
					'language'   => array(
						'description' => __( 'Term ID of the original language.', 'library' ),
						'type'        => 'integer',
						'default'     => 0,
					),
					'cover'      => array(
						'description' => __( 'ID of the image uploaded as the cover.', 'library' ),
						'type'        => 'integer',
						'default'     => 0,
					),
					'meta'       => array(
						'description' => __( 'Book meta, such as isbn, as the book form saves them.', 'library' ),
						'type'        => 'object',
						'default'     => array(),
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( Library_Stats::get_reading_stats() + array( 'year_to_date' => Library_Stats::get_year_to_date() ) ),
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
						'description' => __( 'Page of the months.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
						'default'     => 1,
					),
					'per_page' => array(
						'description' => __( 'Number of months per page.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 12,
						'default'     => 3,
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
	 * Add a book, as the book form does: the names of its authors go through their tag box rule, its slug is built
	 * from its title and ISBN, its meta are saved by Library_Meta. A book in a collection has the collection only,
	 * its publisher being the parent; the cover becomes an image of the book.
	 *
	 * The book is linked, and embeddable with _embed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_book( WP_REST_Request $request ) {
		// The names first, so that a book is never left half added.
		$authors       = Library_Taxonomies::sanitize_author_names( 'library-author', implode( ',', $request['authors'] ) );
		$publisher_id  = Library_Taxonomies::get_term_id( 'library-publisher', $request['publisher'] );
		$collection_id = $publisher_id ? Library_Taxonomies::get_term_id( 'library-publisher', $request['collection'], $publisher_id ) : 0;
		$publishers    = array_filter( array( $collection_id ? $collection_id : $publisher_id ) );
		$meta          = (array) $request['meta'];
		// The slug comes from the fields of the form the post is saved with, such as the ISBN.
		$post = array(
			'post_type'   => 'book',
			'post_status' => 'publish',
			'post_title'  => $request['title'],
		) + array_intersect_key( $meta, array_flip( array( 'isbn', 'issn', 'volume_number' ) ) );

		$book_id = wp_insert_post( wp_slash( $post ), true );

		if ( is_wp_error( $book_id ) ) {
			return $book_id;
		}

		Library_Meta::save( $book_id, $meta );
		wp_set_object_terms( $book_id, $authors, 'library-author' );
		wp_set_object_terms( $book_id, $publishers, 'library-publisher' );
		wp_set_object_terms( $book_id, array_filter( array( $request['language'] ) ), 'library-language' );

		$cover_id = $request['cover'];

		if ( $cover_id && wp_attachment_is_image( $cover_id ) && current_user_can( 'edit_post', $cover_id ) ) {
			wp_update_post(
				array(
					'ID'          => $cover_id,
					'post_parent' => $book_id,
				)
			);
			set_post_thumbnail( $book_id, $cover_id );
		}

		$response = rest_ensure_response( array( 'id' => $book_id ) );
		$response->set_status( 201 );
		$response->add_link( 'book', rest_url( 'wp/v2/books/' . $book_id ), array( 'embeddable' => true ) );

		return $response;
	}

	/**
	 * The readings by month, latest first: one per read date, so a book read again comes once per reading, kept or not.
	 *
	 * A page holds whole months, so a month never goes on in the next one; the totals in the X-WP-Total and
	 * X-WP-TotalPages headers count months. Each month links its books, embeddable with _embed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_readings( WP_REST_Request $request ): WP_REST_Response {
		$per_page = $request['per_page'];
		$total    = (int) Library_Stats::select( 'SELECT COUNT( DISTINCT LEFT( r.date, 7 ) ) AS total FROM {readings}' )[0]->total;
		$months   = wp_list_pluck( Library_Stats::select( 'SELECT DISTINCT LEFT( r.date, 7 ) AS month FROM {readings} ORDER BY month DESC LIMIT %d OFFSET %d', $per_page, ( $request['page'] - 1 ) * $per_page ), 'month' );
		$rows     = $months ? Library_Stats::select(
			'SELECT r.book, r.date FROM {readings} WHERE LEFT( r.date, 7 ) IN ( ' . implode( ', ', array_fill( 0, count( $months ), '%s' ) ) . ' ) ORDER BY r.date DESC, r.book DESC',
			...$months
		) : array();
		$by_month = array_fill_keys( $months, array() );

		foreach ( $rows as $row ) {
			$by_month[ substr( $row->date, 0, 7 ) ][] = array(
				'date' => $row->date,
				'book' => (int) $row->book,
			);
		}

		$response = rest_ensure_response(
			array_map(
				fn( string $month, array $month_readings ) => array(
					'month'    => $month,
					'readings' => $month_readings,
					'_links'   => array(
						'book' => array_map(
							fn( int $book_id ) => array(
								'href'       => rest_url( 'wp/v2/books/' . $book_id ),
								'embeddable' => true,
							),
							array_values( array_unique( array_column( $month_readings, 'book' ) ) )
						),
					),
				),
				array_keys( $by_month ),
				$by_month
			)
		);

		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * An author under their real name, with their pseudonyms, their books and their reading: the books read,
	 * the average rating, the pages of their books and the completion their books count for.
	 *
	 * The pages add up those of the books with a number of pages, read or not, each book once: 0 when none has one.
	 *
	 * The books are those of the author and of their pseudonyms, kept or not, as the achievements count them,
	 * as IDs of the core API: in the order they first came out, then by title, those without date last.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_author( WP_REST_Request $request ) {
		$term   = get_term( $request['id'], 'library-author' );
		$author = $term instanceof WP_Term ? current( Library_Taxonomies::get_top_terms( array( $term ) ) ) : null;

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

		usort(
			$books,
			function ( array $a, array $b ): int {
				$a_date = Library_Books::date( $a );
				$b_date = Library_Books::date( $b );
				$order  = array( '' === $a_date, $a_date ) <=> array( '' === $b_date, $b_date );

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
				'pages'      => array_sum( array_column( $books, 'pages' ) ),
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
