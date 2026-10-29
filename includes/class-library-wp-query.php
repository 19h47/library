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

/**
 * Extends book searches to the book text meta (ISBN, series, translators…) and terms (authors, publishers, collections),
 * and narrows them with keywords, such as "series:SAS" or "author:Tabucchi"; the REST API lists a series in reading order.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_WP_Query {

	/**
	 * Search keywords, each one followed by a colon and its value: "series:SAS".
	 *
	 * @var string[]
	 */
	public const KEYWORDS = array( 'series', 'author', 'publisher', 'translator', 'language', 'isbn', 'read', 'rating' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'pre_get_posts', array( $this, 'parse_keywords' ) );
		add_filter( 'posts_search', array( $this, 'search_meta_and_terms' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'search_keywords' ), 10, 2 );
		add_filter( 'rest_book_query', array( $this, 'allow_reading_order' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'order_series_search' ), 10, 2 );
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
	public static function keyword_search( string $keyword, string $value ): string {
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

		if ( '' === $search || ! in_array( 'book', (array) $query->get( 'post_type' ), true ) ) {
			return;
		}

		$keywords = array();
		$value    = '(?:"[^"]*"|[^\s",]+)';
		$pattern  = '/(?:^|(?<=\s))(-?)(' . implode( '|', self::KEYWORDS ) . "):($value(?:,$value)*)(?=\s|$)/i";
		$text     = preg_replace_callback(
			$pattern,
			function ( array $found ) use ( &$keywords ): string {
				preg_match_all( '/"([^"]*)"|([^,]+)/', $found[3], $values, PREG_SET_ORDER );

				$values = array_values( array_filter( array_map( fn( array $value ) => trim( $value[2] ?? $value[1] ), $values ), 'strlen' ) );

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
	 * Also match books whose text meta or terms contain the search phrase; a term matches by its parent too,
	 * so searching for a real name finds its pseudonyms' books and a publisher its collections' books.
	 *
	 * @param string   $search Search SQL, starting with " AND ".
	 * @param WP_Query $query  Query instance.
	 * @return string
	 */
	public function search_meta_and_terms( string $search, WP_Query $query ): string {
		$post_types = (array) $query->get( 'post_type' );

		if ( '' === $search || ! in_array( 'book', $post_types, true ) ) {
			return $search;
		}

		global $wpdb;

		$like         = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';
		$meta_values  = array( ...Library_Meta::TEXT_KEYS, $like );
		$meta_matches = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( " . implode( ', ', array_fill( 0, count( Library_Meta::TEXT_KEYS ), '%s' ) ) . ' ) AND meta_value LIKE %s',
			$meta_values
		);
		$term_matches = self::term_matches( Library_Taxonomies::TAXONOMIES, $like );

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
			$matches = array_map( fn( string $value ) => "{$wpdb->posts}.ID IN ( " . self::keyword_matches( $keyword['keyword'], $value ) . ' )', $keyword['values'] );
			$where  .= ( $keyword['exclude'] ? ' AND NOT ( ' : ' AND ( ' ) . implode( ' OR ', $matches ) . ' )';
		}

		return $where;
	}

	/**
	 * Let a search of the REST API that asks for no order, such as the one of the reading app, list a series in reading
	 * order rather than by date added: core orders by date when none is asked.
	 *
	 * @param array           $args    WP_Query arguments.
	 * @param WP_REST_Request $request Request.
	 * @return array
	 */
	public function allow_reading_order( array $args, WP_REST_Request $request ): array {
		$args['library_reading_order'] = ! isset( $request->get_query_params()['orderby'] );

		return $args;
	}

	/**
	 * List the books a search for a series finds in reading order, as Library_Books::in_reading_order() sorts them,
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

		$book_ids = implode( ', ', array_column( Library_Books::in_reading_order( Library_Books::all() ), 'id' ) );

		return '' !== $book_ids ? "FIELD( {$wpdb->posts}.ID, $book_ids )" : $orderby;
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

		if ( ! $screen || 'book' !== $screen->post_type ) {
			return;
		}

		$keywords = array(
			array( array( 'series:SAS' ), __( 'a series, or a cycle with its series, by its exact name', 'library' ) ),
			array( array( 'author:Tabucchi' ), __( 'an author, with their pseudonyms', 'library' ) ),
			array( array( 'publisher:Gallimard' ), __( 'a publisher, with its collections', 'library' ) ),
			array( array( 'translator:Bruneau' ), __( 'a translator', 'library' ) ),
			array( array( 'language:it' ), __( 'the original language, by its code or the start of its name', 'library' ) ),
			array( array( 'isbn:9782070368228' ), __( 'the ISBN, with or without hyphens', 'library' ) ),
			array( array( 'read:yes', 'read:no', 'read:2024', 'read:2024-05' ), __( 'read or not, or read in a year or a month, rereads included', 'library' ) ),
			array( array( 'rating:9' ), __( 'rated 9 or more; a half point with a dot: 8.5', 'library' ) ),
		);
		$items    = '';

		foreach ( $keywords as [ $examples, $description ] ) {
			$codes  = array_map( fn( string $example ) => '<code>' . esc_html( $example ) . '</code>', $examples );
			$items .= '<li>' . implode( ', ', $codes ) . ' – ' . esc_html( $description ) . '</li>';
		}

		$screen->add_help_tab(
			array(
				'id'      => 'library-search',
				'title'   => __( 'Search', 'library' ),
				'content' =>
					'<p>' . esc_html__( 'The search finds the text in the title, subtitle, series, ISBN, ISSN, volume number, collection number, translators, authors, publishers, collections and original languages. Keywords narrow it to a field:', 'library' ) . '</p>' .
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
	private static function keyword_matches( string $keyword, string $value ): string {
		global $wpdb;

		$like  = '%' . $wpdb->esc_like( $value ) . '%';
		$none  = 'SELECT 0';
		$value = mb_strtolower( $value );

		switch ( $keyword ) {
			case 'series':
				// A series is found from one comma to the next, so that a cycle finds the books of its series: "Star Wars"
				// those of "Star Wars, Young Jedi Knights". A volume without series is a volume of the series named by its
				// title, as in Library_Books.
				$series_like = '%,' . $wpdb->esc_like( str_replace( ', ', ',', $value ) ) . ',%';

				return $wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = 'series' AND CONCAT( ',', LOWER( REPLACE( meta_value, ', ', ',' ) ), ',' ) LIKE %s
					UNION
					SELECT p.ID FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->postmeta} AS v ON v.post_id = p.ID AND v.meta_key = 'volume_number' AND v.meta_value REGEXP '[0-9]'
					LEFT JOIN {$wpdb->postmeta} AS s ON s.post_id = p.ID AND s.meta_key = 'series' AND s.meta_value != ''
					WHERE s.post_id IS NULL AND LOWER( p.post_title ) = %s",
					$series_like,
					$value
				);
			case 'author':
				return self::term_matches( array( 'library-author' ), $like );
			case 'publisher':
				return self::term_matches( array( 'library-publisher' ), $like );
			case 'translator':
				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'translators' AND meta_value LIKE %s", $like );
			case 'language':
				return $wpdb->prepare(
					"SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
					WHERE tt.taxonomy = 'library-language' AND ( t.slug = %s OR t.name LIKE %s )",
					$value,
					$wpdb->esc_like( $value ) . '%'
				);
			case 'isbn':
				$digits = '%' . $wpdb->esc_like( (string) preg_replace( '/[\s-]/', '', $value ) ) . '%';

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'isbn' AND REPLACE( REPLACE( meta_value, '-', '' ), ' ', '' ) LIKE %s", $digits );
			case 'read':
				if ( 'yes' === $value ) {
					return "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'read' AND meta_value = '1'";
				}

				if ( 'no' === $value ) {
					return "SELECT p.ID FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
						WHERE p.post_type = 'book' AND r.post_id IS NULL";
				}

				if ( ! preg_match( '/^\d{4}(-\d{2})?$/', $value ) ) {
					return $none;
				}

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'read_date' AND meta_value LIKE %s", $value . '%' );
			case 'rating':
				if ( ! is_numeric( $value ) ) {
					return $none;
				}

				return $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'rating' AND CAST( meta_value AS DECIMAL(3,1) ) >= %f", (float) $value );
		}

		return $none;
	}

	/**
	 * SQL selecting the IDs of the books with a term of the given taxonomies whose name, or its parent's, matches.
	 *
	 * @param string[] $taxonomies Taxonomies.
	 * @param string   $like       LIKE pattern.
	 * @return string
	 */
	private static function term_matches( array $taxonomies, string $like ): string {
		global $wpdb;

		$values = array( ...$taxonomies, $like, $like );

		return $wpdb->prepare(
			"SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
			LEFT JOIN {$wpdb->terms} AS parent ON parent.term_id = tt.parent
			WHERE tt.taxonomy IN ( " . implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) ) . ' ) AND ( t.name LIKE %s OR parent.name LIKE %s )',
			$values
		);
	}
}
