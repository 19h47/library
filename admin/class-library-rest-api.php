<?php
/**
 * REST API: reading stats and ISBN lookup (BnF).
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers REST routes for books and ISBN metadata.
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
	 * Reading stats (read count and total).
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_reading_stats( WP_REST_Request $request ) {
		unset( $request );

		$counts = (array) wp_count_posts( 'book' );
		$total  = array_sum( $counts );
		$read_q = new WP_Query(
			array(
				'post_type'      => 'book',
				'post_status'    => array_keys( get_post_stati() ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single meta_query for reading stats endpoint on limited dataset.
				'meta_query'     => array(
					array(
						'key'     => 'read',
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);
		$read   = $read_q->found_posts;

		return rest_ensure_response(
			array(
				'read'  => (int) $read,
				'total' => (int) $total,
			)
		);
	}

	/**
	 * Fetch book metadata from BnF Catalogue Général (SRU, Dublin Core).
	 *
	 * @since  1.0.0
	 * @see    https://data.bnf.fr/opendata
	 * @param  string $isbn Normalized ISBN (digits only).
	 * @return array|null Associative array or null on failure.
	 */
	private function fetch_from_bnf( $isbn ) {
		$query    = 'bib.isbn%20all%20%22' . rawurlencode( $isbn ) . '%22';
		$url      = 'https://catalogue.bnf.fr/api/SRU?version=1.2&operation=searchRetrieve&query=' . $query . '&maximumRecords=1&recordSchema=dublincore';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'Library-Plugin (WordPress; ' . home_url() . ')',
				'sslverify'  => true,
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$body = trim( wp_remote_retrieve_body( $response ) );
		$body = preg_replace( '/^\xEF\xBB\xBF/', '', $body );

		\libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOERROR );
		if ( ! $xml instanceof \SimpleXMLElement ) {
			\libxml_clear_errors();
			\libxml_use_internal_errors( false );
			return null;
		}
		\libxml_clear_errors();
		\libxml_use_internal_errors( false );

		$xml->registerXPathNamespace( 'srw', 'http://www.loc.gov/zing/srw/' );
		$xml->registerXPathNamespace( 'dc', 'http://purl.org/dc/elements/1.1/' );
		$num = $xml->xpath( '//srw:numberOfRecords' );
		if ( empty( $num ) || (int) (string) $num[0] < 1 ) {
			return null;
		}

		$titles   = $xml->xpath( '//dc:title' );
		$creators = $xml->xpath( '//dc:creator' );
		$pubs     = $xml->xpath( '//dc:publisher' );
		$dates    = $xml->xpath( '//dc:date' );
		$formats  = $xml->xpath( '//dc:format' );

		$title = ! empty( $titles[0] ) ? trim( (string) $titles[0] ) : '';
		if ( empty( $title ) ) {
			return null;
		}

		$authors = array();
		foreach ( $creators as $c ) {
			$name = trim( (string) $c );
			$name = preg_replace( '/\s*Auteur du texte\s*/', '', $name );
			$name = preg_replace( '/\s*\/\s*.*$/', '', $name );
			$name = preg_replace( '/\s*;\s*.*$/', '', $name );
			$name = preg_replace( '/\s*\([^)]*\)\s*$/', '', $name );
			$name = trim( $name, ':.,; ' );
			if ( '' !== $name ) {
				$authors[] = $name;
			}
		}

		$publisher = ! empty( $pubs[0] ) ? trim( (string) $pubs[0] ) : '';
		$publisher = preg_replace( '/\s*\(\s*\)\s*$/', '', $publisher );

		$publish_date = ! empty( $dates[0] ) ? trim( (string) $dates[0] ) : '';
		if ( preg_match( '/\d{4}/', $publish_date, $m ) ) {
			$publish_date = $m[0] . '-01-01';
		} else {
			$publish_date = '';
		}

		$number_of_pages = null;
		if ( ! empty( $formats[0] ) && preg_match( '/\(\s*(\d+)\s*p\.?\s*\)/', (string) $formats[0], $m ) ) {
			$number_of_pages = (int) $m[1];
		}

		return array(
			'title'           => $title,
			'authors'         => implode( ', ', $authors ),
			'publishers'      => $publisher,
			'date_published'  => $publish_date,
			'number_of_pages' => $number_of_pages,
			'cover'           => '',
		);
	}

	/**
	 * Get book metadata by ISBN (BnF API).
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request with 'isbn' param.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_book_by_isbn( WP_REST_Request $request ) {
		$isbn = $request->get_param( 'isbn' );
		$isbn = preg_replace( '/[\s\-]/', '', $isbn );
		if ( empty( $isbn ) || ! preg_match( '/^[0-9Xx]{9,17}$/', $isbn ) ) {
			return new WP_Error( 'invalid_isbn', __( 'Invalid ISBN.', 'library' ), array( 'status' => 400 ) );
		}

		$data = $this->fetch_from_bnf( $isbn );
		if ( is_array( $data ) ) {
			return rest_ensure_response( $data );
		}

		return new WP_Error( 'not_found', __( 'No book found for this ISBN.', 'library' ), array( 'status' => 404 ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Server $wp_rest_server Server instance.
	 * @return void
	 */
	public function register_rest_routes( WP_REST_Server $wp_rest_server ) {
		unset( $wp_rest_server );

		$namespace = $this->plugin_name . '/v1';

		register_rest_route(
			$namespace,
			'books/reading-stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_reading_stats' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			),
		);

		register_rest_route(
			$namespace,
			'isbn/(?P<isbn>[0-9Xx\- ]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_book_by_isbn' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'isbn' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		$route = '/settings/';

		register_rest_route(
			$namespace,
			$route . '(?P<name>[a-zA-Z0-9\-_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function ( $request ) {
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
					'callback'            => function ( $request ) {
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
