<?php
/**
 * Query modifications: search in book meta and terms, and search keywords.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use WP_Query;
use WP_REST_Request;

/**
 * Extends book searches to the book text meta (ISBN, series…) and terms (people, publishers, collections),
 * and narrows them with keywords, such as "series:SAS" or "author:Tabucchi"; the REST API lists a series in reading order,
 * and books by rating or by latest reading too.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Query {

	/**
	 * Search keywords, each one followed by a colon and its value: "series:SAS".
	 *
	 * @var string[]
	 */
	public const array KEYWORDS = array( 'series', 'author', 'publisher', 'translator', 'language', 'form', 'isbn', 'read', 'progress', 'rating' );

	/**
	 * Orders of the books the REST API takes besides those of core, highest or latest first: by rating, by latest read date.
	 *
	 * @var string[]
	 */
	public const array ORDERS = array( 'rating', 'read', 'started' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'pre_get_posts', array( $this, 'parse_keywords' ) );
		add_filter( 'posts_search', array( $this, 'search_meta_and_terms' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'search_keywords' ), 10, 2 );
		add_filter( 'rest_library_book_query', array( $this, 'allow_reading_order' ), 10, 2 );
		add_filter( 'rest_library_book_collection_params', array( $this, 'add_orders' ) );
		add_filter( 'rest_library_book_query', array( $this, 'pass_order' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'order_series_search' ), 10, 2 );
		add_filter( 'posts_clauses', array( $this, 'order_books' ), 10, 2 );
		add_filter( 'get_search_query', array( $this, 'show_typed_search' ) );
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );
	}

	/**
	 * A keyword search for a value, e.g. 'series:"Le Cycle de Dune"'.
	 *
	 * @param string $keyword Keyword, one of KEYWORDS.
	 * @param string $value   Value.
	 * @return string
	 */
	public static function build_keyword_search( string $keyword, string $value ): string {
		return sprintf( '%s:"%s"', $keyword, $value );
	}

	/**
	 * Take the keywords out of a book search, so that core searches the rest of the text.
	 *
	 * Keywords add up; the values of a keyword separated with commas are a choice, a hyphen before a keyword
	 * excludes its books and quotes keep the spaces of a value: 'author:Tabucchi,Pessoa -series:"Le Cycle"'.
	 * A word followed by a colon that is not a keyword stays in the text. The search as typed is kept for display.
	 *
	 * @param WP_Query $query Query instance.
	 * @return void
	 */
	public function parse_keywords( WP_Query $query ): void {
		// The search of the books list comes slashed from the request; core only unslashes it when it searches.
		$search = stripslashes( (string) $query->get( 's' ) );

		if ( '' === $search || ! in_array( 'library_book', (array) $query->get( 'post_type' ), true ) ) {
			return;
		}

		$keywords = array();
		$value    = '(?:"[^"]*"|[^\s",]+)';
		$pattern  = '/(?:^|(?<=\s))(-?)(' . implode( '|', self::KEYWORDS ) . "):($value(?:,$value)*)(?=\s|$)/i";
		$text     = preg_replace_callback(
			$pattern,
			function ( array $found ) use ( &$keywords ): string {
				preg_match_all( '/"([^"]*)"|([^,]+)/', $found[3], $values, PREG_SET_ORDER );

				$values = array_values( array_filter( array_map( fn( array $value ) => trim( $value[2] ?? $value[1] ?? '' ), $values ), fn( string $name ) => '' !== $name ) );

				// Only empty quotes: not a keyword.
				if ( ! $values ) {
					return $found[0];
				}

				$keywords[] = array(
					'exclude' => '-' === $found[1],
					'keyword' => strtolower( $found[2] ),
					'values'  => $values,
				);

				return '';
			},
			$search
		);

		if ( ! $keywords ) {
			return;
		}

		$query->set( 's', wp_slash( trim( (string) preg_replace( '/\s+/', ' ', (string) $text ) ) ) );
		$query->set( 'library_keywords', $keywords );
		$query->set( 'library_search', $search );
	}

	/**
	 * Also match books whose text meta or terms contain the search phrase; a term matches by its ancestors too, so searching
	 * for the name of a person finds the books of their other names and a publisher the books of its collections.
	 *
	 * @param string   $search Search SQL, starting with " AND ".
	 * @param WP_Query $query  Query instance.
	 * @return string
	 */
	public function search_meta_and_terms( string $search, WP_Query $query ): string {
		$post_types = (array) $query->get( 'post_type' );

		if ( '' === $search || ! in_array( 'library_book', $post_types, true ) ) {
			return $search;
		}

		global $wpdb;

		$like         = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';
		$meta_values  = array( ...Meta::TEXT_KEYS, $like );
		$meta_matches = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( " . implode( ', ', array_fill( 0, count( Meta::TEXT_KEYS ), '%s' ) ) . ' ) AND meta_value LIKE %s',
			$meta_values
		);
		$term_matches = self::build_term_matches( Taxonomies::TAXONOMIES, $query->get( 's' ) );

		return " AND ( {$wpdb->posts}.ID IN ( $meta_matches ) OR {$wpdb->posts}.ID IN ( $term_matches ) OR ( 1 = 1 $search ) )";
	}

	/**
	 * Narrow a book search to the books matching its keywords: one of the values of each keyword, none of an excluded one.
	 *
	 * @param string   $where WHERE clause.
	 * @param WP_Query $query Query instance.
	 * @return string
	 */
	public function search_keywords( string $where, WP_Query $query ): string {
		global $wpdb;

		foreach ( $query->get( 'library_keywords', array() ) as $keyword ) {
			$matches = array_map( fn( string $value ) => "{$wpdb->posts}.ID IN ( " . self::build_keyword_matches( $keyword['keyword'], $value ) . ' )', $keyword['values'] );
			$where  .= ( $keyword['exclude'] ? ' AND NOT ( ' : ' AND ( ' ) . implode( ' OR ', $matches ) . ' )';
		}

		return $where;
	}

	/**
	 * Let a search of the REST API that asks for no order, such as the one of the reading app, list a series in reading
	 * order rather than by date added: core orders by date when none is asked.
	 *
	 * @param array<string,mixed> $args    WP_Query arguments.
	 * @param WP_REST_Request     $request Request.
	 * @return array<string,mixed>
	 */
	public function allow_reading_order( array $args, WP_REST_Request $request ): array {
		$args['library_reading_order'] = ! isset( $request->get_query_params()['orderby'] );

		return $args;
	}

	/**
	 * Let the REST API order the books by rating or by latest read date.
	 *
	 * @param array<string,array<string,mixed>> $params Collection parameters, each with its schema.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_orders( array $params ): array {
		$params['orderby']['enum'] = array( ...$params['orderby']['enum'], ...self::ORDERS );

		return $params;
	}

	/**
	 * Pass an order of ORDERS to the query, which core does not know: order_books() reads it.
	 *
	 * @param array<string,mixed> $args    WP_Query arguments.
	 * @param WP_REST_Request     $request Request.
	 * @return array<string,mixed>
	 */
	public function pass_order( array $args, WP_REST_Request $request ): array {
		if ( in_array( $request['orderby'], self::ORDERS, true ) ) {
			$args['library_order'] = $request['orderby'];
		}

		return $args;
	}

	/**
	 * Order the books by rating, highest first, by latest read date, latest first, or by the start of their reading in
	 * progress, latest first; the books without come last, by date added. A read date or a start unknown orders nothing.
	 * The end of a reading follows its slash, its start comes before it.
	 *
	 * @param array<string,string> $clauses Query clauses.
	 * @param WP_Query             $query   Query instance.
	 * @return array<string,string>
	 */
	public function order_books( array $clauses, WP_Query $query ): array {
		global $wpdb;

		$order = $query->get( 'library_order' );

		if ( 'rating' === $order ) {
			$clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS library_order ON library_order.post_id = {$wpdb->posts}.ID AND library_order.meta_key = 'library_rating'";
			$clauses['orderby'] = "library_order.meta_value IS NULL, CAST( library_order.meta_value AS DECIMAL(3,1) ) DESC, {$wpdb->posts}.post_date DESC";
		}

		if ( 'read' === $order ) {
			$clauses['join']   .= " LEFT JOIN ( SELECT post_id, MAX( SUBSTRING_INDEX( meta_value, '/', -1 ) ) AS read_date FROM {$wpdb->postmeta} WHERE meta_key = 'library_reading' AND meta_value NOT LIKE '%/' AND meta_value NOT LIKE '%/..' GROUP BY post_id ) AS library_order ON library_order.post_id = {$wpdb->posts}.ID";
			$clauses['orderby'] = "library_order.read_date IS NULL, library_order.read_date DESC, {$wpdb->posts}.post_date DESC";
		}

		if ( 'started' === $order ) {
			$clauses['join']   .= " LEFT JOIN ( SELECT post_id, MAX( SUBSTRING_INDEX( meta_value, '/', 1 ) ) AS started FROM {$wpdb->postmeta} WHERE meta_key = 'library_reading' AND meta_value LIKE '_%/..' GROUP BY post_id ) AS library_order ON library_order.post_id = {$wpdb->posts}.ID";
			$clauses['orderby'] = "library_order.started IS NULL, library_order.started DESC, {$wpdb->posts}.post_date DESC";
		}

		return $clauses;
	}

	/**
	 * List the books a search for a series finds in reading order, as Books::sort_in_reading_order() sorts them,
	 * when the search lets it.
	 *
	 * @param string   $orderby ORDER BY clause.
	 * @param WP_Query $query   Query instance.
	 * @return string
	 */
	public function order_series_search( string $orderby, WP_Query $query ): string {
		$series = array(
			'keyword' => 'series',
			'exclude' => false,
		);

		if ( ! $query->get( 'library_reading_order' ) || ! wp_list_filter( $query->get( 'library_keywords', array() ), $series ) ) {
			return $orderby;
		}

		global $wpdb;

		$book_ids = self::get_reading_order();

		return '' !== $book_ids ? "FIELD( {$wpdb->posts}.ID, $book_ids )" : $orderby;
	}

	/**
	 * IDs of the books in reading order, as Books::sort_in_reading_order() sorts them, kept until a book changes:
	 * sorting needs every book, which takes longer than the search itself.
	 *
	 * @return string IDs, separated with commas.
	 */
	private static function get_reading_order(): string {
		return Cache::remember( 'reading_order', fn() => implode( ', ', array_column( Books::sort_in_reading_order( Books::get_books() ), 'id' ) ) );
	}

	/**
	 * Show the search as typed, keywords included, instead of the text left to core.
	 *
	 * @param mixed $search Search query.
	 * @return mixed
	 */
	public function show_typed_search( $search ) {
		global $wp_query;

		return $wp_query instanceof WP_Query ? $wp_query->get( 'library_search', $search ) : $search;
	}

	/**
	 * Explain the search keywords in the help of the books list.
	 *
	 * @return void
	 */
	public function add_help_tab(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'library_book' !== $screen->post_type ) {
			return;
		}

		/* translators: Search keyword with a form, such as a novel, as named in the library: the keyword form stays in English. */
		$form_example = _x( 'form:novel', 'search example', 'library' );
		$keywords     = array(
			array( array( 'series:SAS' ), __( 'a series, or a cycle with its series, by its exact name', 'library' ) ),
			array( array( 'author:Tabucchi' ), __( 'an author, with their other names', 'library' ) ),
			array( array( 'publisher:Gallimard' ), __( 'a publisher, with its collections', 'library' ) ),
			array( array( 'translator:Bruneau' ), __( 'a translator', 'library' ) ),
			array( array( 'language:it' ), __( 'the original language, by its code or the start of its name', 'library' ) ),
			array( array( $form_example ), __( 'the form, by its name', 'library' ) ),
			array( array( 'isbn:9782070368228' ), __( 'the ISBN, with or without hyphens', 'library' ) ),
			array( array( 'read:yes', 'read:no', 'read:2024', 'read:2024-05' ), __( 'read or not, or read in a year or a month, rereads included', 'library' ) ),
			array( array( 'progress:yes', 'progress:no' ), __( 'being read or not, for the first time or again', 'library' ) ),
			array( array( 'rating:9' ), __( 'rated 9 or more; a half point with a dot: 8.5', 'library' ) ),
		);
		$items        = '';

		foreach ( $keywords as [ $examples, $description ] ) {
			$codes  = array_map( fn( string $example ) => '<code>' . esc_html( $example ) . '</code>', $examples );
			$items .= '<li>' . implode( ', ', $codes ) . ' – ' . esc_html( $description ) . '</li>';
		}

		$screen->add_help_tab(
			array(
				'id'      => 'library-search',
				'title'   => __( 'Search', 'library' ),
				'content' =>
					'<p>' . esc_html__( 'The search finds the text in the title, subtitle, series, ISBN, ISSN, volume number, collection number, people, publishers, collections, original languages and forms. Keywords narrow it to a field:', 'library' ) . '</p>' .
					'<ul>' . $items . '</ul>' .
					'<p>' . sprintf(
						/* translators: 1: search with a keyword and a word, 2: keyword with two values, 3: keyword excluded, 4: value with spaces. */
						esc_html__( 'Keywords add up, with the rest of the search: %1$s. A comma gives a choice, %2$s, a hyphen excludes, %3$s, and quotes keep the spaces of a value, %4$s.', 'library' ),
						'<code>series:SAS Malko</code>',
						'<code>language:it,ja</code>',
						'<code>publisher:Gallimard -publisher:Folio</code>',
						'<code>series:"Le Cycle de Dune"</code>'
					) . '</p>',
			)
		);
	}

	/**
	 * SQL selecting the IDs of the books matching a keyword value; a value the keyword cannot read matches no book.
	 *
	 * @param string $keyword Keyword, one of KEYWORDS.
	 * @param string $value   Value.
	 * @return string
	 */
	private static function build_keyword_matches( string $keyword, string $value ): string {
		global $wpdb;

		$none  = 'SELECT 0';
		$value = mb_strtolower( $value );

		switch ( $keyword ) {
			case 'series':
				// A series by its name, or after its cycles, with the series of a cycle: "Star Wars" finds those of
				// "Star Wars, Young Jedi Knights".
				$series_ids = Taxonomies::find_series_ids( $value );

				if ( ! $series_ids ) {
					return $none;
				}

				return "SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE tt.taxonomy = 'library_series' AND tt.term_id IN ( " . implode( ', ', array_map( 'intval', $series_ids ) ) . ' )';
			case 'author':
				// A name of the book without another role, or the name its person is known by: a colorist is not one of its authors.
				$roles = Meta::build_roles_table();

				return self::build_term_matches( array( 'library_person' ), $value, "LEFT JOIN {$roles} AS role ON role.post_id = tr.object_id AND role.term_id = tt.term_id", 'role.post_id IS NULL' );
			case 'publisher':
				return self::build_term_matches( array( 'library_publisher' ), $value );
			case 'translator':
				// A name of the book with the role of translator, or the name its person is known by.
				return self::build_term_matches( array( 'library_person' ), $value, "INNER JOIN {$wpdb->postmeta} AS m ON m.post_id = tr.object_id AND m.meta_key = 'library_translators' AND m.meta_value = tt.term_id" );
			case 'form':
				return self::build_term_matches( array( 'library_form' ), $value );
			case 'language':
				return $wpdb->prepare(
					"SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
					WHERE tt.taxonomy = 'library_language' AND ( t.slug = %s OR t.name LIKE %s )",
					$value,
					$wpdb->esc_like( $value ) . '%'
				);
			case 'isbn':
				$digits = '%' . $wpdb->esc_like( ISBN::get_digits( $value ) ) . '%';

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_isbn' AND REPLACE( REPLACE( meta_value, '-', '' ), ' ', '' ) LIKE %s", $digits );
			case 'read':
				// A book is read once it has a reading ended, of a known date or not.
				$read = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_reading' AND meta_value NOT LIKE '%/..'";

				if ( 'yes' === $value ) {
					return $read;
				}

				// NOT IN lists the books read once; a LEFT JOIN would search the meta of each book, slower on MySQL 5.7.
				if ( 'no' === $value ) {
					return "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'library_book' AND ID NOT IN ( $read )";
				}

				if ( ! preg_match( '/^\d{4}(-\d{2})?$/', $value ) ) {
					return $none;
				}

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_reading' AND SUBSTRING_INDEX( meta_value, '/', -1 ) LIKE %s", $value . '%' );
			case 'progress':
				$in_progress = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_reading' AND meta_value LIKE '%/..'";

				if ( 'yes' === $value ) {
					return $in_progress;
				}

				return 'no' === $value ? "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'library_book' AND ID NOT IN ( $in_progress )" : $none;
			case 'rating':
				if ( ! is_numeric( $value ) ) {
					return $none;
				}

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_rating' AND CAST( meta_value AS DECIMAL(3,1) ) >= %f", (float) $value );
		}

		return $none;
	}

	/**
	 * SQL selecting the IDs of the books with a term of the given taxonomies whose name, or one of its ancestors', contains
	 * the text.
	 *
	 * @param string[] $taxonomies Taxonomies.
	 * @param string   $text       Text the name contains, whatever its case.
	 * @param string   $join       SQL joins its condition needs, without placeholder.
	 * @param string   $condition  SQL condition the term tt of the book tr meets too, without placeholder.
	 * @return string
	 */
	private static function build_term_matches( array $taxonomies, string $text, string $join = '', string $condition = '1 = 1' ): string {
		global $wpdb;

		$term_ids = self::find_term_ids( $taxonomies, $text );

		if ( ! $term_ids ) {
			return 'SELECT 0';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join and $condition are built from constants, the IDs are integers.
		return "SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			{$join}
			WHERE tt.term_id IN ( " . implode( ', ', $term_ids ) . " ) AND {$condition}";
	}

	/**
	 * IDs of the terms of the given taxonomies whose name contains the text, with the terms under them: the other names of
	 * a person, the collections of a publisher and their sub-collections.
	 *
	 * @param string[] $taxonomies Taxonomies.
	 * @param string   $text       Text the name contains, whatever its case.
	 * @return int[]
	 */
	private static function find_term_ids( array $taxonomies, string $text ): array {
		$term_ids = array();

		foreach ( $taxonomies as $taxonomy ) {
			$found = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'name__like' => $text,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			foreach ( is_array( $found ) ? $found : array() as $term_id ) {
				$children = is_taxonomy_hierarchical( $taxonomy ) ? get_term_children( $term_id, $taxonomy ) : array();
				$term_ids = array( ...$term_ids, $term_id, ...( is_array( $children ) ? $children : array() ) );
			}
		}

		return array_values( array_unique( array_map( 'intval', $term_ids ) ) );
	}
}
