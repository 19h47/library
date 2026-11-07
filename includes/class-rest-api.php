<?php
/**
 * REST API: a book added or edited, the name of its cover, reading stats, readings by month, reading idea, achievements, the recap of a year, people and the credits of a book.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Term;

/**
 * Registers REST routes for books.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Rest_API {

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
	 * Register the fields added to the books of the core REST API: its credits, and what a list of books shows of its
	 * cover and terms, so that it needs to embed neither the image nor the terms, whole; and to the series and the
	 * publishers, their names as a book types them.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		$fields = array(
			'credits'                => array(
				'get_callback' => fn( array $book ) => Books::get_credits( $book['id'] ),
				'schema'       => array(
					'description' => __( 'Credits of the book, to show under its title: authors · publisher, collection · edition year.', 'library' ),
					'type'        => 'string',
				),
			),
			'cover'                  => array(
				'get_callback' => fn( array $book ) => $this->get_cover( $book['id'] ),
				'schema'       => array(
					'description' => __( 'Cover of the book, its medium size, else the image itself, with its width and height, and its sizes as a srcset; null without cover.', 'library' ),
					'type'        => array( 'object', 'null' ),
					'properties'  => array(
						'url'    => array( 'type' => 'string' ),
						'width'  => array( 'type' => 'integer' ),
						'height' => array( 'type' => 'integer' ),
						'srcset' => array( 'type' => 'string' ),
					),
				),
			),
			'names'                  => array(
				'get_callback' => fn( array $book ) => array_map(
					fn( WP_Term $term ) => array(
						'id'   => $term->term_id,
						'name' => $term->name,
					),
					$this->get_book_terms( $book['id'], 'library_person' )
				),
				'schema'       => array(
					'description' => __( 'Names of the book, as named on it, each with its term ID; the role meta tell the ones with another role than author.', 'library' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'object' ),
				),
			),
			'language_codes'         => array(
				'get_callback' => fn( array $book ) => wp_list_pluck( $this->get_book_terms( $book['id'], 'library_language' ), 'slug' ),
				'schema'       => array(
					'description' => __( 'Original languages of the book, as ISO 639 codes, the slugs of their terms.', 'library' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
				),
			),
			'edition_language_codes' => array(
				'get_callback' => fn( array $book ) => self::get_language_codes( Meta::get_edition_languages( $book['id'] ) ),
				'schema'       => array(
					'description' => __( 'Languages of the text of the book, as ISO 639 codes, the slugs of their terms.', 'library' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
				),
			),
			'series_name'            => array(
				'get_callback' => function ( array $book ): string {
					$series = Taxonomies::get_series( $book['id'] );

					return $series ? Taxonomies::get_series_path( $series ) : '';
				},
				'schema'       => array(
					'description' => __( 'Series of the book, after its cycles, in plain text, such as “Star Wars, Young Jedi Knights”, or empty.', 'library' ),
					'type'        => 'string',
				),
			),
			'form_name'              => array(
				'get_callback' => fn( array $book ) => (string) ( current( $this->get_book_terms( $book['id'], 'library_form' ) )->name ?? '' ),
				'schema'       => array(
					'description' => __( 'Name of the form of the book, such as a novel, or empty.', 'library' ),
					'type'        => 'string',
				),
			),
		);

		foreach ( $fields as $name => $field ) {
			$field['schema'] += array(
				'context'  => array( 'view', 'edit' ),
				'readonly' => true,
			);

			register_rest_field( 'library_book', $name, $field );
		}

		// The names the form of a book suggests, as a book types them: a series after its cycles, a collection after the
		// collections it is part of, under the ID of its publisher.
		$term_fields = array(
			array( 'library_series', 'path', 'string', array( Taxonomies::class, 'get_series_path' ) ),
			array( 'library_publisher', 'collection', 'string', array( Taxonomies::class, 'get_collection_name' ) ),
			array( 'library_publisher', 'publisher', 'integer', fn( WP_Term $term ) => current( Taxonomies::get_top_level_terms( array( $term ) ) )->term_id ?? $term->term_id ),
		);

		foreach ( $term_fields as [ $taxonomy, $name, $type, $callback ] ) {
			$field = array(
				'get_callback' => function ( array $item ) use ( $taxonomy, $callback ) {
					$term = get_term( $item['id'], $taxonomy );

					return $term instanceof WP_Term ? $callback( $term ) : null;
				},
				'schema'       => array(
					'type'     => $type,
					'context'  => array( 'view', 'edit' ),
					'readonly' => true,
				),
			);

			register_rest_field( $taxonomy, $name, $field );
		}
	}

	/**
	 * The cover of a book: its medium size, else the image itself, as for an AVIF the server cannot resize, with its size,
	 * which gives the image its place before it loads, and its sizes of the same shape, as WordPress lists them for a
	 * srcset, for the browser to take the one the width it shows needs: a list small, the page of the book sharp.
	 *
	 * @param int $book_id Book ID.
	 * @return array{url:string,width:int,height:int,srcset:string}|null
	 */
	private function get_cover( int $book_id ): ?array {
		$image_id = (int) get_post_thumbnail_id( $book_id );
		$image    = wp_get_attachment_image_src( $image_id, 'medium' );

		if ( ! $image ) {
			return null;
		}

		return array(
			'url'    => $image[0],
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
			'srcset' => (string) wp_get_attachment_image_srcset( $image_id, 'medium' ),
		);
	}

	/**
	 * Codes of languages, the slugs of their terms, in the order of their term IDs: those the library still has.
	 *
	 * @param int[] $term_ids Term IDs of languages.
	 * @return string[]
	 */
	private static function get_language_codes( array $term_ids ): array {
		$terms = array_filter( array_map( fn( int $term_id ) => get_term( $term_id, 'library_language' ), $term_ids ), fn( $term ) => $term instanceof WP_Term );

		return array_values( wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * Terms of a book in a taxonomy, from the cache the query of the books filled.
	 *
	 * @param int    $book_id  Book ID.
	 * @param string $taxonomy Taxonomy.
	 * @return WP_Term[]
	 */
	private function get_book_terms( int $book_id, string $taxonomy ): array {
		$terms = get_the_terms( $book_id, $taxonomy );

		return is_array( $terms ) ? $terms : array();
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
				'callback'            => array( $this, 'create_book' ),
				'permission_callback' => fn() => current_user_can( get_post_type_object( 'library_book' )->cap->publish_posts ),
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
						'validate_callback' => fn( $id ) => 'library_book' === get_post_type( (int) $id ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_book' ),
					'permission_callback' => $can_edit_book,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_book' ),
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
				'args'                => array_intersect_key( $this->get_book_args( true ), array_flip( array( 'title', 'people', 'series', 'meta' ) ) ) + array(
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
			'books/bnf',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_bnf_book' ),
				'permission_callback' => fn() => current_user_can( get_post_type_object( 'library_book' )->cap->publish_posts ),
				'args'                => array(
					'isbn' => array(
						'description'       => __( 'ISBN of the book, with or without hyphens.', 'library' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => fn( string $isbn ) => '' !== ISBN::format( $isbn ),
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => rest_ensure_response( Stats::get_reading_stats() + array( 'year_to_date' => Stats::get_reading_year_to_date() ) ),
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
				'callback'            => fn() => rest_ensure_response( new Achievements()->get_achievements() ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			),
		);

		register_rest_route(
			'library/v1',
			'recaps/(?P<year>\d{4})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn( WP_REST_Request $request ) => rest_ensure_response( Recap::get_recap( (int) $request['year'] ) ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'year' => array(
						'description' => __( 'Year of the recap.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'series/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_series' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'Term ID of the series, or of the cycle.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'publishers/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_publisher' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'Term ID of the collection, or of the publisher.', 'library' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
				),
			),
		);

		register_rest_route(
			'library/v1',
			'people/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_person' ),
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'Term ID of the person, or of one of their other names.', 'library' ),
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
	 * @return array<string,array<string,mixed>>
	 */
	private function get_book_args( bool $adding ): array {
		$args = array(
			'title'          => array(
				'description'       => __( 'Title of the book.', 'library' ),
				'type'              => 'string',
				'required'          => true,
				'pattern'           => '\\S',
				// A sanitize callback leaves out the check against the schema, unless it is asked for.
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'excerpt'        => array(
				'description' => __( 'Summary of the book in a few words, its excerpt.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'content'        => array(
				'description' => __( 'Notes on the book, its content.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'people'         => array(
				'description' => __( 'People of the book, by their name or another one, each with its role, such as colorist; a name not found adds a person.', 'library' ),
				'type'        => 'array',
				'items'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'name' => array(
							'type'     => 'string',
							'required' => true,
						),
						'role' => array(
							'type'     => 'string',
							'enum'     => array_keys( Meta::get_role_choices() ),
							'required' => true,
						),
					),
					'additionalProperties' => false,
				),
				'default'     => array(),
			),
			'publisher'      => array(
				'description' => __( 'Name of the publisher; a name not found adds a publisher.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'collection'     => array(
				'description' => __( 'Collection, under the publisher and sent with it, a sub-collection after its collection, separated with commas, such as “Folio, Science-fiction”; a name not found adds a collection.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'series'         => array(
				'description' => __( 'Series, after its cycles, separated with commas, such as “Star Wars, Young Jedi Knights”; a name not found adds a series, or a cycle.', 'library' ),
				'type'        => 'string',
				'default'     => '',
			),
			'languages'      => array(
				'description' => __( 'Term IDs of the original languages.', 'library' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
				'default'     => array(),
			),
			'form'           => array(
				'description' => __( 'Term ID of the form, such as a novel or a comic.', 'library' ),
				'type'        => 'integer',
				'default'     => 0,
			),
			'featured_media' => array(
				'description' => __( 'ID of the image uploaded as the cover, 0 for none.', 'library' ),
				'type'        => 'integer',
				'default'     => 0,
			),
			'meta'           => array(
				'description' => __( 'Book meta, such as library_isbn, as the book form saves them.', 'library' ),
				'type'        => 'object',
				'default'     => array(),
			),
			'status'         => array(
				'description' => __( 'Status of the book: in the library, i.e. published, not kept or to buy.', 'library' ),
				'type'        => 'string',
				'enum'        => array_keys( Status::get_choices() ),
				'default'     => 'publish',
			),
		);

		if ( $adding ) {
			return $args;
		}

		return array_map( fn( array $arg ) => array_diff_key( $arg, array_flip( array( 'required', 'default' ) ) ), $args );
	}

	/**
	 * Add a book, as the book form does: its post, whose slug is built from its title and ISBN, then its meta, terms
	 * and cover, as save_book() saves them.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_book( WP_REST_Request $request ) {
		$meta  = (array) $request['meta'];
		$error = Meta::check_isbn( 0, (string) ( $meta['library_isbn'] ?? '' ) );

		if ( $error ) {
			return $error;
		}

		// The names first, so that a book is never left half added.
		$roles = $this->get_roles( $request );
		$terms = $this->get_terms( $request, $roles );
		$post  = array(
			'post_type'    => 'library_book',
			'post_status'  => $request['status'],
			'post_title'   => $request['title'],
			'post_excerpt' => $request['excerpt'],
			'post_content' => $request['content'],
		) + $this->get_slug_fields( 0, $meta );

		$book_id = wp_insert_post( wp_slash( $post ), true );

		if ( is_wp_error( $book_id ) ) {
			return $book_id;
		}

		$this->save_book( $book_id, $request, $terms, $roles );

		$response = $this->get_book_response( $book_id );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * A book as its form edits it, as the route that edits it takes it back: its title, excerpt and notes as typed, the names of
	 * its names as the book gives them, each with its role, its publisher and collection, its original languages, its form and its meta, its edition languages among them.
	 *
	 * The form edits one publisher and one collection, after the collections it is part of: a book given more gives its first
	 * collection, or else publisher.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_book( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$book = get_post( $request['id'] );

		if ( ! $book instanceof WP_Post ) {
			return new WP_Error( 'library_no_book', get_post_type_object( 'library_book' )->labels->not_found, array( 'status' => 404 ) );
		}

		$collection = current( Taxonomies::get_collections( $book->ID ) );
		$publishers = get_the_terms( $book, 'library_publisher' );
		$publisher  = current( Taxonomies::get_top_level_terms( $collection ? array( $collection ) : ( is_array( $publishers ) ? $publishers : array() ) ) );
		$people     = wp_get_post_terms( $book->ID, 'library_person' );
		$languages  = wp_get_post_terms( $book->ID, 'library_language', array( 'fields' => 'ids' ) );
		$forms      = wp_get_post_terms( $book->ID, 'library_form', array( 'fields' => 'ids' ) );
		$series     = Taxonomies::get_series( $book->ID );
		$roles      = Meta::get_roles( $book->ID );
		$meta       = array();

		// The read dates are the reading of the book, which its own form edits.
		foreach ( array_diff( Meta::KEYS, array( 'library_read_date' ) ) as $key ) {
			$meta[ $key ] = get_post_meta( $book->ID, $key, true );
		}

		$meta['library_edition_languages'] = Meta::get_edition_languages( $book->ID );

		return rest_ensure_response(
			array(
				'id'         => $book->ID,
				'title'      => $book->post_title,
				'excerpt'    => $book->post_excerpt,
				'content'    => $book->post_content,
				'people'     => array_map(
					fn( WP_Term $term ) => array(
						'name' => $term->name,
						'role' => $roles[ $term->term_id ] ?? 'author',
					),
					is_array( $people ) ? $people : array()
				),
				'publisher'  => $publisher instanceof WP_Term ? $publisher->name : '',
				'collection' => $collection ? Taxonomies::get_collection_name( $collection ) : '',
				'series'     => $series ? Taxonomies::get_series_path( $series ) : '',
				'languages'  => $languages,
				'form'       => (int) current( $forms ),
				'meta'       => $meta,
			)
		);
	}

	/**
	 * A book to add as the catalogue of the BnF describes it, from its ISBN, as its form takes it: its title, its people
	 * with their roles, its publisher, collection and series, its original languages, its form and its meta, its edition
	 * languages among them, with the names of the library.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_bnf_book( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$book = BnF::get_book( $request['isbn'] );

		if ( null === $book ) {
			return new WP_Error( 'library_not_in_bnf', __( 'The catalogue of the BnF has no book of this ISBN.', 'library' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $book );
	}

	/**
	 * Edit a book, as the book form does: only what is sent changes. Its post first, whose slug follows its title and
	 * ISBN as in the book form, then its meta, terms and cover, as save_book() saves them.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_book( WP_REST_Request $request ) {
		$book_id = $request['id'];
		$meta    = (array) $request['meta'];
		$error   = Meta::check_isbn( $book_id, (string) ( $meta['library_isbn'] ?? '' ) );

		if ( $error ) {
			return $error;
		}

		// The names first, so that a book is never left half edited.
		$roles = $this->get_roles( $request );
		$terms = $this->get_terms( $request, $roles );
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

		if ( $request->has_param( 'status' ) ) {
			$post['post_status'] = $request['status'];
		}

		$updated = wp_update_post( wp_slash( $post ), true );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$this->save_book( $book_id, $request, $terms, $roles );

		return $this->get_book_response( $book_id );
	}

	/**
	 * Term IDs of a book by taxonomy, from the names sent, for the taxonomies sent only: its people, the terms of their
	 * roles, its collection, or else its publisher, its original languages and its form. A name not found adds a term.
	 *
	 * A collection is under its publisher, a sub-collection under its collection, and goes with them: a book in a collection
	 * has the narrowest one only.
	 *
	 * @param WP_REST_Request   $request Request.
	 * @param array<int,string> $roles   Roles of the names sent, by term ID, from get_roles().
	 * @return array<string,int[]>
	 */
	private function get_terms( WP_REST_Request $request, array $roles ): array {
		$terms = array();

		if ( $request->has_param( 'people' ) ) {
			$terms['library_person'] = array_keys( $roles );
		}

		if ( $request->has_param( 'publisher' ) ) {
			$publisher_id  = Taxonomies::get_term_id( 'library_publisher', $request['publisher'] );
			$collection_id = $publisher_id ? Taxonomies::get_path_id( 'library_publisher', (string) $request['collection'], true, $publisher_id ) : 0;

			$terms['library_publisher'] = array_filter( array( $collection_id ? $collection_id : $publisher_id ) );
		}

		if ( $request->has_param( 'series' ) ) {
			$terms['library_series'] = array_filter( array( Taxonomies::get_path_id( 'library_series', $request['series'], true ) ) );
		}

		if ( $request->has_param( 'languages' ) ) {
			$terms['library_language'] = array_filter( $request['languages'] );
		}

		if ( $request->has_param( 'form' ) ) {
			$terms['library_form'] = array_filter( array( $request['form'] ) );
		}

		return $terms;
	}

	/**
	 * Roles of the names sent, by term ID, in their order: a name stands for the term its tag box gives it, a new name
	 * for a person added. Each name is looked up once, for the terms of the book and for their roles.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<int,string>
	 */
	private function get_roles( WP_REST_Request $request ): array {
		$roles = array();

		foreach ( $request->has_param( 'people' ) ? $request['people'] : array() as $person ) {
			foreach ( Taxonomies::sanitize_person_names( 'library_person', $person['name'] ) as $term_id ) {
				$roles[ $term_id ] = $person['role'];
			}
		}

		return $roles;
	}

	/**
	 * Fields the slug of a book is built from, which Posts::slug() reads with its post: the ISBN, the ISSN and the
	 * volume number sent, or else those of the book.
	 *
	 * @param int                 $book_id Book ID, 0 for a book to add.
	 * @param array<string,mixed> $meta    Book meta sent.
	 * @return array<string,string>
	 */
	private function get_slug_fields( int $book_id, array $meta ): array {
		$fields = array();

		foreach ( array( 'library_isbn', 'library_issn', 'library_volume_number' ) as $key ) {
			$fields[ $key ] = $meta[ $key ] ?? (string) get_post_meta( $book_id, $key, true );
		}

		return $fields;
	}

	/**
	 * Save what is sent for a book, after its post: its meta through Meta, its terms, and its cover, the featured_media
	 * sent, which becomes an image of the book; 0 removes it.
	 *
	 * @param int                 $book_id Book ID.
	 * @param WP_REST_Request     $request Request.
	 * @param array<string,int[]> $terms   Term IDs by taxonomy, from get_terms().
	 * @param array<int,string>   $roles   Roles of the names sent, by term ID, from get_roles().
	 * @return void
	 */
	private function save_book( int $book_id, WP_REST_Request $request, array $terms, array $roles ): void {
		if ( $request->has_param( 'meta' ) ) {
			Meta::save( $book_id, (array) $request['meta'] );
		}

		foreach ( $terms as $taxonomy => $term_ids ) {
			wp_set_object_terms( $book_id, $term_ids, $taxonomy );
		}

		if ( $request->has_param( 'people' ) ) {
			Meta::save_roles( $book_id, $roles );
		}

		if ( ! $request->has_param( 'featured_media' ) ) {
			return;
		}

		$cover_id = $request['featured_media'];

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
	 * The response of a book added or edited: the book as it is now, as `/wp/v2/books/<id>` gives it, so that a client
	 * shows it without asking for it again; `_fields` keeps the fields it reads, as for the core route.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_REST_Response
	 */
	private function get_book_response( int $book_id ): WP_REST_Response {
		return rest_ensure_response( rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/books/' . $book_id ) ) );
	}

	/**
	 * The name of the image of a cover, from the fields of its book as its form sends them, before the photo is uploaded:
	 * its first author and the title to show, from Books::build_cover_name().
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_cover_name( WP_REST_Request $request ): WP_REST_Response {
		$authors = wp_list_filter( $request['people'], array( 'role' => 'author' ) );
		$name    = Books::build_cover_name( $request['title'], array_column( $authors, 'name' ), (string) $request['series'], (array) $request['meta'], $request['photo'] );

		return rest_ensure_response( array( 'name' => $name ) );
	}

	/**
	 * The readings by month, latest first: one per read date, so a book read again comes once per reading, kept or not.
	 *
	 * A page holds whole months, so a month never goes on in the next one; the totals in the X-WP-Total and
	 * X-WP-TotalPages headers count months.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_readings( WP_REST_Request $request ): WP_REST_Response {
		$per_page = $request['per_page'];
		$months   = array();

		foreach ( Stats::get_readings() as $reading ) {
			$months[ substr( $reading->read_date, 0, 7 ) ][] = array(
				'date' => $reading->read_date,
				'book' => $reading->id,
			);
		}

		$total    = count( $months );
		$by_month = array_slice( $months, ( $request['page'] - 1 ) * $per_page, $per_page, true );

		$response = rest_ensure_response(
			array_map(
				fn( string $month, array $month_readings ) => array(
					'month'    => $month,
					'readings' => $month_readings,
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
	 * A person under the name they are known by, with their other names, such as a pseudonym or their birth name,
	 * their description, their series, their books and their reading: the books read, the average rating, the pages of
	 * their books and the completion their books count for.
	 *
	 * The description is the one of the term, as a theme shows it: its paragraphs and line breaks become HTML,
	 * among the few tags core keeps when it saves a description.
	 *
	 * The pages add up those of the books with a number of pages, read or not, each book once: 0 when none has one.
	 *
	 * The books are those of the person under any of their names, kept or not, whatever their role, author or another one,
	 * such as colorist, as IDs of the core API: in the order they first came out, then by title, those without date last.
	 *
	 * The series are those of these books, each one the narrowest, named after its cycles, with its description and how
	 * many of these books it holds and are read: those with the most first, then by name.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_person( WP_REST_Request $request ) {
		$term   = get_term( $request['id'], 'library_person' );
		$person = $term instanceof WP_Term ? current( Taxonomies::get_top_level_terms( array( $term ) ) ) : null;

		if ( ! $person instanceof WP_Term ) {
			return new WP_Error( 'library_no_person', get_taxonomy( 'library_person' )->labels->not_found, array( 'status' => 404 ) );
		}

		$books       = array_filter( Books::get_books(), fn( Book $book ) => in_array( $person->term_id, array( ...$book->author_ids, ...$book->contributor_ids ), true ) );
		$other_names = get_terms(
			array(
				'taxonomy'   => 'library_person',
				'parent'     => $person->term_id,
				'hide_empty' => false,
				'fields'     => 'names',
			)
		);
		$book_count  = array_count_values( array_filter( array_column( $books, 'series_id' ) ) );
		$read_count  = array_count_values( array_filter( array_column( array_filter( $books, fn( Book $book ) => $book->read ), 'series_id' ) ) );
		$series      = $book_count ? get_terms(
			array(
				'taxonomy'   => 'library_series',
				'include'    => array_keys( $book_count ),
				'hide_empty' => false,
			)
		) : array();
		$series      = is_array( $series ) ? $series : array();

		usort(
			$series,
			function ( WP_Term $a, WP_Term $b ) use ( $book_count ): int {
				$order = $book_count[ $b->term_id ] <=> $book_count[ $a->term_id ];

				return 0 !== $order ? $order : strnatcasecmp( Taxonomies::get_series_path( $a ), Taxonomies::get_series_path( $b ) );
			}
		);

		usort(
			$books,
			function ( Book $a, Book $b ): int {
				$a_date = Books::get_date( $a );
				$b_date = Books::get_date( $b );
				$order  = array( '' === $a_date, $a_date ) <=> array( '' === $b_date, $b_date );

				return 0 !== $order ? $order : strnatcasecmp( $a->full_title, $b->full_title );
			}
		);

		return rest_ensure_response(
			array(
				'id'          => $person->term_id,
				'name'        => $person->name,
				'other_names' => is_array( $other_names ) ? $other_names : array(),
				'description' => term_description( $person->term_id ),
				'series'      => array_map(
					fn( WP_Term $term ) => array(
						'id'          => $term->term_id,
						'name'        => Taxonomies::get_series_path( $term ),
						'description' => term_description( $term->term_id ),
						'books'       => $book_count[ $term->term_id ],
						'read'        => $read_count[ $term->term_id ] ?? 0,
					),
					$series
				),
				'books'       => array_column( $books, 'id' ),
				'read'        => count( array_filter( $books, fn( Book $book ) => $book->read ) ),
				'rating'      => Books::get_average_rating( $books ),
				'pages'       => array_sum( array_column( $books, 'pages' ) ),
				'completion'  => new Achievements()->get_completion( 'author', $person->term_id ),
			)
		);
	}

	/**
	 * A series, or a cycle: its name after its cycles, its cycle, its description, its series if it is a cycle, its
	 * authors, its books in reading order, those of its series with them, how many are read, and the completion they count for.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_series( WP_REST_Request $request ) {
		$series = get_term( $request['id'], 'library_series' );

		if ( ! $series instanceof WP_Term ) {
			return new WP_Error( 'library_no_series', get_taxonomy( 'library_series' )->labels->not_found, array( 'status' => 404 ) );
		}

		$books = array_filter( Books::get_books(), fn( Book $book ) => in_array( $series->term_id, $book->series_ids, true ) );
		$cycle = $series->parent ? get_term( $series->parent, 'library_series' ) : null;
		$parts = get_terms(
			array(
				'taxonomy'   => 'library_series',
				'parent'     => $series->term_id,
				'hide_empty' => false,
			)
		);

		return rest_ensure_response(
			array(
				'id'          => $series->term_id,
				'name'        => Taxonomies::get_series_path( $series ),
				'cycle'       => self::build_term_link( $cycle, array( Taxonomies::class, 'get_series_path' ) ),
				'description' => term_description( $series->term_id ),
				'series'      => array_map( fn( WP_Term $part ) => self::build_term_link( $part ), is_array( $parts ) ? $parts : array() ),
				'authors'     => self::get_authors_of( $books ),
				'books'       => array_column( Books::sort_in_reading_order( $books ), 'id' ),
				'read'        => count( array_filter( $books, fn( Book $book ) => $book->read ) ),
				'completion'  => new Achievements()->get_completion( 'series', $series->term_id ),
			)
		);
	}

	/**
	 * A collection, or a publisher: its name, a sub-collection after the collections it is part of, its publisher and the
	 * collection it is part of, its description, its collections, its authors, its books, those of its collections with
	 * them, how many are read, and the completion they count for.
	 *
	 * The books are grouped by collection, each one followed by its own collections, by name: those of the publisher, or of
	 * the collection, first; in each one, in the order of their number in it, those without number last, in reading order.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_publisher( WP_REST_Request $request ) {
		$publisher = get_term( $request['id'], 'library_publisher' );

		if ( ! $publisher instanceof WP_Term ) {
			return new WP_Error( 'library_no_publisher', get_taxonomy( 'library_publisher' )->labels->not_found, array( 'status' => 404 ) );
		}

		$places = array_flip( self::get_collection_order( $publisher ) );
		// Every term of a book and every one it is under: its publishers and its collections.
		$terms_of    = fn( Book $book ): array => array( ...$book->publisher_ids, ...$book->collection_ids );
		$books       = array_filter( Books::get_books(), fn( Book $book ) => in_array( $publisher->term_id, $terms_of( $book ), true ) );
		$books       = Books::sort_in_reading_order( $books );
		$ancestors   = array_map( fn( int $term_id ) => get_term( $term_id, 'library_publisher' ), get_ancestors( $publisher->term_id, 'library_publisher', 'taxonomy' ) );
		$top         = end( $ancestors );
		$parent      = count( $ancestors ) > 1 ? $ancestors[0] : null;
		$collections = get_terms(
			array(
				'taxonomy'   => 'library_publisher',
				'parent'     => $publisher->term_id,
				'hide_empty' => false,
			)
		);
		$path        = array( Taxonomies::class, 'get_collection_name' );
		// A book is in the narrowest of its collections here: the last one in their order, after those it is part of.
		$place = fn( Book $book ): int => max( 0, ...array_intersect_key( $places, array_flip( $terms_of( $book ) ) ) );

		// A stable sort: the books without number stay in reading order.
		usort(
			$books,
			function ( Book $a, Book $b ) use ( $place ): int {
				$order = array( $place( $a ), '' === $a->collection_number ) <=> array( $place( $b ), '' === $b->collection_number );

				return 0 !== $order ? $order : strnatcasecmp( $a->collection_number, $b->collection_number );
			}
		);

		return rest_ensure_response(
			array(
				'id'          => $publisher->term_id,
				'name'        => Taxonomies::get_collection_name( $publisher ),
				'publisher'   => self::build_term_link( $top, $path ),
				'collection'  => self::build_term_link( $parent, $path ),
				'description' => term_description( $publisher->term_id ),
				'collections' => array_map( fn( WP_Term $collection ) => self::build_term_link( $collection ), is_array( $collections ) ? $collections : array() ),
				'authors'     => self::get_authors_of( $books ),
				'books'       => array_column( $books, 'id' ),
				'read'        => count( array_filter( $books, fn( Book $book ) => $book->read ) ),
				'completion'  => new Achievements()->get_completion( 'collection', $publisher->term_id ),
			)
		);
	}

	/**
	 * A term as a page links to it: its ID and its name, in plain text unless `$name` says it otherwise, such as a series
	 * after its cycles; none for no term.
	 *
	 * @param WP_Term|mixed $term Term, or what get_term() gives for none.
	 * @param callable|null $name Name of the term, from the term.
	 * @return array{id:int,name:string}|null
	 */
	private static function build_term_link( $term, ?callable $name = null ): ?array {
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		return array(
			'id'   => $term->term_id,
			'name' => $name ? $name( $term ) : wp_specialchars_decode( $term->name, ENT_QUOTES ),
		);
	}

	/**
	 * A publisher, or a collection, then each of its collections by name, each one followed by its own: the order of their books.
	 *
	 * @param WP_Term $publisher Term of the publishers.
	 * @return list<int> Term IDs.
	 */
	private static function get_collection_order( WP_Term $publisher ): array {
		// Every collection under it, at any depth, by name.
		$parents  = get_terms(
			array(
				'taxonomy'   => 'library_publisher',
				'child_of'   => $publisher->term_id,
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);
		$children = array();

		foreach ( is_array( $parents ) ? $parents : array() as $term_id => $parent_id ) {
			$children[ $parent_id ][] = $term_id;
		}

		$order = array();
		$next  = array( $publisher->term_id );

		while ( $next ) {
			$term_id = array_shift( $next );
			$order[] = $term_id;
			$next    = array( ...( $children[ $term_id ] ?? array() ), ...$next );
		}

		return $order;
	}

	/**
	 * The authors of books under the name they are known by, other roles left out, with their description and how many
	 * of the books they wrote: those who wrote the most first, then by name.
	 *
	 * @param array<int,Book> $books Books, from Books::get_books().
	 * @return array<int,array{id:int,name:string,description:string,books:int}>
	 */
	private static function get_authors_of( array $books ): array {
		$book_count = array_count_values( array_merge( ...array_column( $books, 'author_ids' ) ) );
		$authors    = $book_count ? get_terms(
			array(
				'taxonomy'   => 'library_person',
				'include'    => array_keys( $book_count ),
				'hide_empty' => false,
			)
		) : array();
		$authors    = is_array( $authors ) ? $authors : array();

		usort(
			$authors,
			function ( WP_Term $a, WP_Term $b ) use ( $book_count ): int {
				$order = $book_count[ $b->term_id ] <=> $book_count[ $a->term_id ];

				return 0 !== $order ? $order : strnatcasecmp( $a->name, $b->name );
			}
		);

		return array_map(
			fn( WP_Term $author ) => array(
				'id'          => $author->term_id,
				'name'        => wp_specialchars_decode( $author->name, ENT_QUOTES ),
				'description' => term_description( $author->term_id ),
				'books'       => $book_count[ $author->term_id ],
			),
			$authors
		);
	}

	/**
	 * The reading idea of the week, or the one at the given position.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_reading_idea( WP_REST_Request $request ) {
		$idea = new Reading_Idea()->get_idea( $request['position'] );

		if ( ! $idea ) {
			return new WP_Error( 'library_no_idea', __( 'No book to read.', 'library' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $idea );
	}
}
