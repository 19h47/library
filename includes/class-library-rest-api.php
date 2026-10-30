<?php
/**
 * REST API: a book added or edited, the name of its cover, reading stats, readings by month, reading idea, achievements, authors and the credits of a book.
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
				'args'                => $this->get_book_args( true ),
			),
		);

		$can_edit_book = fn( WP_REST_Request $request ) => current_user_can( 'edit_post', $request['id'] );

		register_rest_route(
			'library/v1',
			'books/(?P<id>\d+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'ID of the book.', 'library' ),
						'type'              => 'integer',
						'validate_callback' => fn( $id ) => 'book' === get_post_type( (int) $id ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_book' ),
					'permission_callback' => $can_edit_book,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'edit_book' ),
					'permission_callback' => $can_edit_book,
					'args'                => $this->get_book_args( false ),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'books/cover-name',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cover_name' ),
				'permission_callback' => fn() => current_user_can( 'upload_files' ),
				'args'                => array_intersect_key( $this->get_book_args( true ), array_flip( array( 'title', 'authors', 'meta' ) ) ) + array(
					'photo' => array(
						'description' => __( 'File name of the photo picked, whose extension the cover keeps.', 'library' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( Library_Stats::get_reading_stats() + array( 'year_to_date' => Library_Stats::get_reading_year_to_date() ) ),
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
	 * Arguments of a book as its form sends them: adding a book takes them all, with their defaults, and needs a title;
	 * editing a book takes the ones sent, and leaves the others as they are. Its status is chosen when it is added,
	 * then with its reading.
	 *
	 * @param bool $adding Whether the book is added.
	 * @return array
	 */
	private function get_book_args( bool $adding ): array {
		$args = array(
			'title'      => array(
				'description'       => __( 'Title of the book.', 'library' ),
				'type'              => 'string',
				'required'          => true,
				'pattern'           => '\\S',
				// A sanitize callback leaves out the check against the schema, unless it is asked for.
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'excerpt'    => array(
				'description' => __( 'Summary of the book in a few words, its excerpt.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'content'    => array(
				'description' => __( 'Notes on the book, its content.', 'library' ),
				'type'        => 'string',
				'default'     => '',
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
				'description' => __( 'Name of the collection, under the publisher and sent with it; a name not found adds a collection.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'language'   => array(
				'description' => __( 'Term ID of the original language.', 'library' ),
				'type'        => 'integer',
				'default'     => 0,
			),
			'cover'      => array(
				'description' => __( 'ID of the image uploaded as the cover, 0 for none.', 'library' ),
				'type'        => 'integer',
				'default'     => 0,
			),
			'meta'       => array(
				'description' => __( 'Book meta, such as isbn, as the book form saves them.', 'library' ),
				'type'        => 'object',
				'default'     => array(),
			),
		);

		if ( $adding ) {
			return $args + array(
				'status' => array(
					'description' => __( 'Status of the book: in the library, i.e. published, not kept or to buy.', 'library' ),
					'type'        => 'string',
					'enum'        => array_keys( Library_Posts::get_status_choices() ),
					'default'     => 'publish',
				),
			);
		}

		return array_map( fn( array $arg ) => array_diff_key( $arg, array_flip( array( 'required', 'default' ) ) ), $args );
	}

	/**
	 * Add a book, as the book form does: its post, whose slug is built from its title and ISBN, then its meta, terms
	 * and cover, as save_book() saves them.
	 *
	 * The book is linked, and embeddable with _embed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_book( WP_REST_Request $request ) {
		$meta  = (array) $request['meta'];
		$error = Library_Meta::check_isbn( 0, (string) ( $meta['isbn'] ?? '' ) );

		if ( $error ) {
			return $error;
		}

		// The names first, so that a book is never left half added.
		$terms = $this->get_terms( $request );
		$post  = array(
			'post_type'    => 'book',
			'post_status'  => $request['status'],
			'post_title'   => $request['title'],
			'post_excerpt' => $request['excerpt'],
			'post_content' => $request['content'],
		) + $this->get_slug_fields( 0, $meta );

		$book_id = wp_insert_post( wp_slash( $post ), true );

		if ( is_wp_error( $book_id ) ) {
			return $book_id;
		}

		$this->save_book( $book_id, $request, $terms );

		$response = $this->get_book_response( $book_id );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * A book as its form edits it, as the route that edits it takes it back: its title, excerpt and notes as typed, the names of
	 * its authors as the book gives them, its publisher and collection, its original language and its meta.
	 *
	 * The form edits one publisher and one collection: a book given more gives its first collection, or else publisher.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_book( WP_REST_Request $request ): WP_REST_Response {
		$book       = get_post( $request['id'] );
		$collection = current( Library_Taxonomies::get_collections( $book->ID ) );
		$publishers = get_the_terms( $book, 'library-publisher' );
		$publisher  = $collection ? get_term( $collection->parent, 'library-publisher' ) : current( is_array( $publishers ) ? $publishers : array() );
		$languages  = wp_get_post_terms( $book->ID, 'library-language', array( 'fields' => 'ids' ) );
		$meta       = array();

		// The read dates are the reading of the book, which its own form edits.
		foreach ( array_diff( Library_Meta::KEYS, array( 'read_date' ) ) as $key ) {
			$meta[ $key ] = get_post_meta( $book->ID, $key, true );
		}

		return rest_ensure_response(
			array(
				'id'         => $book->ID,
				'title'      => $book->post_title,
				'excerpt'    => $book->post_excerpt,
				'content'    => $book->post_content,
				'authors'    => wp_get_post_terms( $book->ID, 'library-author', array( 'fields' => 'names' ) ),
				'publisher'  => $publisher instanceof WP_Term ? $publisher->name : '',
				'collection' => $collection ? $collection->name : '',
				'language'   => (int) current( $languages ),
				'meta'       => $meta,
			)
		);
	}

	/**
	 * Edit a book, as the book form does: only what is sent changes. Its post first, whose slug follows its title and
	 * ISBN as in the book form, then its meta, terms and cover, as save_book() saves them.
	 *
	 * The book is linked, and embeddable with _embed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function edit_book( WP_REST_Request $request ) {
		$book_id = $request['id'];
		$meta    = (array) $request['meta'];
		$error   = Library_Meta::check_isbn( $book_id, (string) ( $meta['isbn'] ?? '' ) );

		if ( $error ) {
			return $error;
		}

		// The names first, so that a book is never left half edited.
		$terms = $this->get_terms( $request );
		$post  = array( 'ID' => $book_id ) + $this->get_slug_fields( $book_id, $meta );

		if ( $request->has_param( 'title' ) ) {
			$post['post_title'] = $request['title'];
		}

		if ( $request->has_param( 'excerpt' ) ) {
			$post['post_excerpt'] = $request['excerpt'];
		}

		if ( $request->has_param( 'content' ) ) {
			$post['post_content'] = $request['content'];
		}

		$updated = wp_update_post( wp_slash( $post ), true );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$this->save_book( $book_id, $request, $terms );

		return $this->get_book_response( $book_id );
	}

	/**
	 * Term IDs of a book by taxonomy, from the names sent, for the taxonomies sent only: its authors through the rule
	 * of their tag box, its collection, or else its publisher, and its original language. A name not found adds a term.
	 *
	 * A collection is under its publisher, and goes with it: a book in a collection has the collection only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string,int[]>
	 */
	private function get_terms( WP_REST_Request $request ): array {
		$terms = array();

		if ( $request->has_param( 'authors' ) ) {
			$terms['library-author'] = Library_Taxonomies::sanitize_author_names( 'library-author', implode( ',', $request['authors'] ) );
		}

		if ( $request->has_param( 'publisher' ) ) {
			$publisher_id  = Library_Taxonomies::get_term_id( 'library-publisher', $request['publisher'] );
			$collection_id = $publisher_id ? Library_Taxonomies::get_term_id( 'library-publisher', (string) $request['collection'], $publisher_id ) : 0;

			$terms['library-publisher'] = array_filter( array( $collection_id ? $collection_id : $publisher_id ) );
		}

		if ( $request->has_param( 'language' ) ) {
			$terms['library-language'] = array_filter( array( $request['language'] ) );
		}

		return $terms;
	}

	/**
	 * Fields the slug of a book is built from, which Library_Posts::slug() reads with its post: the ISBN, the ISSN and the
	 * volume number sent, or else those of the book.
	 *
	 * @param int   $book_id Book ID, 0 for a book to add.
	 * @param array $meta    Book meta sent.
	 * @return array<string,string>
	 */
	private function get_slug_fields( int $book_id, array $meta ): array {
		$fields = array();

		foreach ( array( 'isbn', 'issn', 'volume_number' ) as $key ) {
			$fields[ $key ] = $meta[ $key ] ?? (string) get_post_meta( $book_id, $key, true );
		}

		return $fields;
	}

	/**
	 * Save what is sent for a book, after its post: its meta through Library_Meta, its terms, and its cover, which becomes
	 * an image of the book; a cover of 0 removes it.
	 *
	 * @param int             $book_id Book ID.
	 * @param WP_REST_Request $request Request.
	 * @param array           $terms   Term IDs by taxonomy, from get_terms().
	 * @return void
	 */
	private function save_book( int $book_id, WP_REST_Request $request, array $terms ): void {
		if ( $request->has_param( 'meta' ) ) {
			Library_Meta::save( $book_id, (array) $request['meta'] );
		}

		foreach ( $terms as $taxonomy => $term_ids ) {
			wp_set_object_terms( $book_id, $term_ids, $taxonomy );
		}

		if ( ! $request->has_param( 'cover' ) ) {
			return;
		}

		$cover_id = $request['cover'];

		if ( ! $cover_id ) {
			delete_post_thumbnail( $book_id );
			return;
		}

		if ( wp_attachment_is_image( $cover_id ) && current_user_can( 'edit_post', $cover_id ) ) {
			wp_update_post(
				array(
					'ID'          => $cover_id,
					'post_parent' => $book_id,
				)
			);
			set_post_thumbnail( $book_id, $cover_id );
		}
	}

	/**
	 * The response of a book added or edited: its ID, the book linked, and embeddable with _embed.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_REST_Response
	 */
	private function get_book_response( int $book_id ): WP_REST_Response {
		$response = rest_ensure_response( array( 'id' => $book_id ) );
		$response->add_link( 'book', rest_url( 'wp/v2/books/' . $book_id ), array( 'embeddable' => true ) );

		return $response;
	}

	/**
	 * The name of the image of a cover, from the fields of its book as its form sends them, before the photo is uploaded:
	 * its first author and the title to show, from Library_Books::cover_name().
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_cover_name( WP_REST_Request $request ): WP_REST_Response {
		$name = Library_Books::cover_name( $request['title'], $request['authors'], (array) $request['meta'], $request['photo'] );

		return rest_ensure_response( array( 'name' => $name ) );
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
	 * An author under their real name, with their pseudonyms, their description, their books and their reading: the books read,
	 * the average rating, the pages of their books and the completion their books count for.
	 *
	 * The description is the one of the term, as a theme shows it: its paragraphs and line breaks become HTML,
	 * among the few tags core keeps when it saves a description.
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
		$author = $term instanceof WP_Term ? current( Library_Taxonomies::get_top_level_terms( array( $term ) ) ) : null;

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
				'id'          => $author->term_id,
				'name'        => $author->name,
				'pseudonyms'  => is_array( $pseudonyms ) ? $pseudonyms : array(),
				'description' => term_description( $author ),
				'books'       => array_column( $books, 'id' ),
				'read'        => count( array_filter( $books, fn( array $book ) => $book['read'] ) ),
				'rating'      => $ratings ? round( array_sum( $ratings ) / count( $ratings ), 1 ) : null,
				'pages'       => array_sum( array_column( $books, 'pages' ) ),
				'completion'  => ( new Library_Achievements() )->get_author_completion( $author->term_id ),
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
			return new WP_Error( 'library_no_idea', __( 'No book to read.', 'library' ), array( 'status' => 404 ) );
		}

		$response = rest_ensure_response( $idea );
		$response->add_link( 'book', rest_url( 'wp/v2/books/' . $idea['id'] ), array( 'embeddable' => true ) );

		return $response;
	}
}
