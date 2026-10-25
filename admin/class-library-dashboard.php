<?php
/**
 * Dashboard widgets for Library.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Adds one dashboard widget per topic: reading, ratings, reading pile and collection.
 *
 * Each widget can be hidden, moved and collapsed like any other dashboard widget.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Dashboard {

	/**
	 * SQL condition on the posts table aliased "p": all books, kept or not, for the reading stats.
	 *
	 * @var string
	 */
	public const BOOKS = "p.post_type = 'book' AND p.post_status NOT IN ( 'trash', 'auto-draft' )";

	/**
	 * SQL condition on the posts table aliased "p": the books in the library, i.e. kept.
	 *
	 * @var string
	 */
	private const LIBRARY = self::BOOKS . " AND p.post_status != '" . Library_Posts::NOT_KEPT . "'";

	/**
	 * Number of entries in the rankings and the recent reads.
	 *
	 * @var int
	 */
	private const LIST_LENGTH = 5;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue dashboard assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'index.php' === $hook ) {
			self::enqueue_style();
		}
	}

	/**
	 * Enqueue the styles of the widgets, shared with the achievements page.
	 */
	public static function enqueue_style(): void {
		[ $url, $version ] = Library::asset( 'admin/css/library-dashboard.css' );

		wp_enqueue_style( 'library-dashboard', $url, array(), $version );
	}

	/**
	 * Register the dashboard widgets.
	 *
	 * The reading widget keeps the ID of the former single widget, and so its position.
	 */
	public function register_widgets(): void {
		wp_add_dashboard_widget( 'library_dashboard_overview', __( 'Reading', 'library' ), array( $this, 'render_reading' ) );
		wp_add_dashboard_widget( 'library_dashboard_ratings', __( 'Ratings', 'library' ), array( $this, 'render_ratings' ) );
		wp_add_dashboard_widget( 'library_dashboard_pile', __( 'Reading pile', 'library' ), array( $this, 'render_pile' ) );
		wp_add_dashboard_widget( 'library_dashboard_collection', __( 'Library', 'library' ), array( $this, 'render_collection' ) );
	}

	/**
	 * Reading widget: books read, this year against last year, charts by year and month.
	 */
	public function render_reading(): void {
		$stats        = Library_Posts::get_reading_stats();
		$year_to_date = self::get_year_to_date();
		$pages_read   = $this->get_pages_this_year();
		$charts       = $this->get_reading_charts();

		$this->render( 'reading', compact( 'stats', 'year_to_date', 'pages_read', 'charts' ) );
	}

	/**
	 * Ratings widget: average, distribution and average by year read.
	 */
	public function render_ratings(): void {
		$ratings = $this->get_ratings();
		$charts  = array( $ratings['chart'], $this->get_average_rating_chart() );

		$this->render( 'ratings', compact( 'ratings', 'charts' ) );
	}

	/**
	 * Reading pile widget: books to read, kept or not, the oldest one and the latest reads.
	 */
	public function render_pile(): void {
		$to_read      = $this->count_unread_books();
		$oldest       = $this->get_oldest_unread_book();
		$recent_reads = $this->get_recent_reads();

		$this->render( 'pile', compact( 'to_read', 'oldest', 'recent_reads' ) );
	}

	/**
	 * Collection widget, on the books kept: authors, publishers, collections, languages, translations and edition years.
	 */
	public function render_collection(): void {
		$stats    = Library_Posts::get_reading_stats();
		$rankings = array(
			array(
				'title' => __( 'Most present authors', 'library' ),
				'items' => $this->get_top_terms( 'library-author' ),
			),
			array(
				'title' => __( 'Most present publishers', 'library' ),
				'items' => $this->get_top_terms( 'library-publisher' ),
			),
			array(
				'title' => __( 'Most present collections', 'library' ),
				'items' => $this->get_top_collections(),
			),
			array(
				'title' => __( 'Most present original languages', 'library' ),
				'items' => $this->get_top_terms( 'library-language' ),
			),
		);
		$counts   = array(
			'books'      => $stats['total'],
			'authors'    => $this->count_terms( 'library-author' ),
			'publishers' => $this->count_terms( 'library-publisher' ),
			'languages'  => $this->count_terms( 'library-language' ),
			'translated' => $this->count_translated_books(),
		);
		$charts   = array( $this->get_edition_chart() );

		$this->render( 'collection', compact( 'counts', 'rankings', 'charts' ) );
	}

	/**
	 * Render a widget partial; charts without data are left out.
	 *
	 * @param string $widget Widget name, i.e. the partial suffix.
	 * @param array  $data   Variables for the partial.
	 */
	private function render( string $widget, array $data ): void {
		$data['charts'] = array_filter( $data['charts'] ?? array(), fn( array $chart ) => array_sum( array_column( $chart['items'], 'value' ) ) > 0 );

		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Partial variables, built above.

		include plugin_dir_path( __FILE__ ) . "partials/library-dashboard-$widget.php";
	}

	/**
	 * Run a SELECT on the books; "{books}" and "{library}" in the SQL stand for the BOOKS and LIBRARY conditions.
	 *
	 * @param string $sql  SQL, with placeholders for the arguments.
	 * @param mixed  ...$args Values for the placeholders.
	 * @return object[]
	 */
	public static function select( string $sql, mixed ...$args ): array {
		global $wpdb;

		$sql = str_replace( array( '{books}', '{library}' ), array( self::BOOKS, self::LIBRARY ), $sql );
		$sql = $args ? $wpdb->prepare( $sql, ...$args ) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Constant condition, values prepared here.

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above; dashboard widgets only.
	}

	/**
	 * Count books by the start of a meta value, e.g. "2024-05" (length 7) or "2024" (length 4) for a date.
	 *
	 * A book counts once per value: once per reading for the read dates.
	 *
	 * @param string $meta_key Meta key.
	 * @param int    $length   Number of characters to group by.
	 * @param string $scope    "{books}" for all books, "{library}" for the books kept.
	 * @return array<string,int> Books keyed by value start, in ascending order.
	 */
	private function count_by_meta_start( string $meta_key, int $length, string $scope = '{books}' ): array {
		global $wpdb;

		$rows = self::select(
			"SELECT LEFT( m.meta_value, %d ) AS value_start, COUNT( DISTINCT p.ID, m.meta_value ) AS books
			FROM {$wpdb->postmeta} AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			WHERE m.meta_key = %s
				AND m.meta_value != ''
				AND $scope
			GROUP BY value_start
			ORDER BY value_start",
			$length,
			$meta_key
		);

		return array_map( 'intval', wp_list_pluck( $rows, 'books', 'value_start' ) );
	}

	/**
	 * "12 books".
	 *
	 * @param int $books Number of books.
	 * @return string
	 */
	private static function books_text( int $books ): string {
		/* translators: %s: number of books. */
		return sprintf( _n( '%s book', '%s books', $books, 'library' ), number_format_i18n( $books ) );
	}

	/**
	 * A chart bar.
	 *
	 * @param string    $label Short label under the bar.
	 * @param string    $name  Full name, for the tooltip, the data table and the peak.
	 * @param int|float $value Bar value.
	 * @param string    $text  Value as text, e.g. "12 books".
	 * @return array{label:string,name:string,value:int|float,text:string}
	 */
	private static function item( string $label, string $name, int|float $value, string $text ): array {
		return array(
			'label' => $label,
			'name'  => $name,
			'value' => $value,
			'text'  => $text,
		);
	}

	/**
	 * A chart of numbers of books.
	 *
	 * @param string $title  Chart title.
	 * @param string $column Name of what the bars stand for, e.g. "Year".
	 * @param array  $items  Bars, from item().
	 * @return array
	 */
	private static function books_chart( string $title, string $column, array $items ): array {
		return array(
			'title'        => $title,
			'column'       => $column,
			'value_column' => __( 'Books', 'library' ),
			/* translators: 1: year, month or rating, 2: number of books, e.g. "2022 (71 books)". */
			'peak'         => __( 'Most: %1$s (%2$s)', 'library' ),
			'items'        => $items,
		);
	}

	/**
	 * Years from the first year with data to the current year.
	 *
	 * @param array<int,mixed> $by_year Values keyed by year.
	 * @return int[]
	 */
	private static function reading_years( array $by_year ): array {
		$current_year = (int) wp_date( 'Y' );
		$first_year   = $by_year ? min( array_keys( $by_year ) ) : $current_year;
		$last_year    = $by_year ? max( $current_year, ...array_keys( $by_year ) ) : $current_year;

		return range( $first_year, $last_year );
	}

	/**
	 * Short year label under a bar: "’22".
	 *
	 * @param int $year Year.
	 * @return string
	 */
	private static function year_label( int $year ): string {
		return '’' . substr( (string) $year, -2 );
	}

	/**
	 * Books read this year and by the same date last year, and the number expected by the end of the year;
	 * a book read again counts once per reading.
	 *
	 * @return array{year:int,this_year:int,last_year:int,projection:int|null}
	 */
	public static function get_year_to_date(): array {
		global $wpdb;

		$today          = new DateTimeImmutable( 'now', wp_timezone() );
		$same_day       = $today->modify( '-1 year' );
		$this_year_from = $today->format( 'Y-01-01' );
		$last_year_from = $same_day->format( 'Y-01-01' );

		[ $row ] = self::select(
			"SELECT
				COUNT( DISTINCT IF( m.meta_value BETWEEN %s AND %s, p.ID, NULL ), m.meta_value ) AS this_year,
				COUNT( DISTINCT IF( m.meta_value BETWEEN %s AND %s, p.ID, NULL ), m.meta_value ) AS last_year
			FROM {$wpdb->postmeta} AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			WHERE m.meta_key = 'read_date'
				AND {books}",
			$this_year_from,
			$today->format( 'Y-m-d' ),
			$last_year_from,
			$same_day->format( 'Y-m-d' )
		);

		$this_year    = (int) $row->this_year;
		$day_of_year  = (int) $today->format( 'z' ) + 1;
		$days_in_year = 365 + (int) $today->format( 'L' );

		// A month of readings is the least to project a whole year from.
		$projection = $day_of_year >= 31 ? (int) round( $this_year * $days_in_year / $day_of_year ) : null;

		return array(
			'year'       => (int) $today->format( 'Y' ),
			'this_year'  => $this_year,
			'last_year'  => (int) $row->last_year,
			'projection' => $projection,
		);
	}

	/**
	 * Pages read this year, over the books read this year with their number of pages;
	 * a book read again counts once per reading.
	 *
	 * @return array{pages:int,books:int}
	 */
	private function get_pages_this_year(): array {
		global $wpdb;

		[ $row ] = self::select(
			"SELECT SUM( n.meta_value ) AS pages, COUNT( DISTINCT p.ID, m.meta_value ) AS books
			FROM ( SELECT DISTINCT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'read_date' AND meta_value >= %s ) AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			INNER JOIN {$wpdb->postmeta} AS n ON n.post_id = p.ID AND n.meta_key = 'pages'
			WHERE {books}",
			wp_date( 'Y-01-01' )
		);

		return array(
			'pages' => (int) $row->pages,
			'books' => (int) $row->books,
		);
	}

	/**
	 * Books read by year, by month this year and by month over all years, from the read dates;
	 * a book read again counts once per reading.
	 *
	 * @return array<int,array>
	 */
	private function get_reading_charts(): array {
		global $wp_locale;

		$current_year     = (int) wp_date( 'Y' );
		$books_by_year    = array();
		$books_this_year  = array_fill( 1, 12, 0 );
		$books_by_month   = array_fill( 1, 12, 0 );
		$books_by_yearmon = $this->count_by_meta_start( 'read_date', 7 );

		foreach ( $books_by_yearmon as $year_month => $books ) {
			[ $year, $month ] = array_map( 'intval', explode( '-', $year_month ) );

			$books_by_year[ $year ]    = ( $books_by_year[ $year ] ?? 0 ) + $books;
			$books_by_month[ $month ] += $books;

			if ( $current_year === $year ) {
				$books_this_year[ $month ] += $books;
			}
		}

		$year_items = array_map(
			fn( int $year ) => self::item( self::year_label( $year ), (string) $year, $books_by_year[ $year ] ?? 0, self::books_text( $books_by_year[ $year ] ?? 0 ) ),
			self::reading_years( $books_by_year )
		);

		$month_items = fn( array $books ) => array_map(
			fn( int $month ) => self::item(
				mb_strtoupper( mb_substr( $wp_locale->get_month( $month ), 0, 1 ) ),
				$wp_locale->get_month( $month ),
				$books[ $month ],
				self::books_text( $books[ $month ] )
			),
			range( 1, 12 )
		);

		return array(
			self::books_chart( __( 'Books read by year', 'library' ), __( 'Year', 'library' ), $year_items ),
			/* translators: %d: year. */
			self::books_chart( sprintf( __( 'Books read in %d by month', 'library' ), $current_year ), __( 'Month', 'library' ), $month_items( $books_this_year ) ),
			self::books_chart( __( 'Reading distribution by month', 'library' ), __( 'Month', 'library' ), $month_items( $books_by_month ) ),
		);
	}

	/**
	 * Books by edition year, by decade when there are too many years to show.
	 *
	 * @return array
	 */
	private function get_edition_chart(): array {
		$books_by_year = $this->count_by_meta_start( 'edition_date', 4, '{library}' );

		if ( count( $books_by_year ) <= 40 ) {
			$items = array();

			foreach ( $books_by_year as $year => $books ) {
				$items[] = self::item( (string) $year, (string) $year, $books, self::books_text( $books ) );
			}

			return self::books_chart( __( 'Edition year distribution', 'library' ), __( 'Edition year', 'library' ), $items );
		}

		$books_by_decade = array();

		foreach ( $books_by_year as $year => $books ) {
			$decade = intdiv( (int) $year, 10 ) * 10;

			$books_by_decade[ $decade ] = ( $books_by_decade[ $decade ] ?? 0 ) + $books;
		}

		$items = array();

		foreach ( $books_by_decade as $decade => $books ) {
			$items[] = self::item( (string) $decade, sprintf( '%d–%d', $decade, $decade + 9 ), $books, self::books_text( $books ) );
		}

		return self::books_chart( __( 'Edition year distribution (by decade)', 'library' ), __( 'Edition years', 'library' ), $items );
	}

	/**
	 * Number of rated books, average rating and distribution, rounded to the nearest point.
	 *
	 * @return array{count:int,average:float|null,chart:array}
	 */
	private function get_ratings(): array {
		$books_by_rating = $this->count_by_meta_start( 'rating', 4 );
		$books_by_point  = array_fill( 1, 10, 0 );
		$rated           = array_sum( $books_by_rating );
		$sum             = 0;

		foreach ( $books_by_rating as $rating => $books ) {
			$point = (int) round( (float) $rating );
			$sum  += (float) $rating * $books;

			if ( isset( $books_by_point[ $point ] ) ) {
				$books_by_point[ $point ] += $books;
			}
		}

		$items = array_map(
			fn( int $point ) => self::item( (string) $point, $point . '/10', $books_by_point[ $point ], self::books_text( $books_by_point[ $point ] ) ),
			range( 1, 10 )
		);

		return array(
			'count'   => $rated,
			'average' => $rated ? round( $sum / $rated, 1 ) : null,
			'chart'   => self::books_chart( __( 'Rating distribution', 'library' ), __( 'Rating', 'library' ), $items ),
		);
	}

	/**
	 * Average rating of the books read each year.
	 *
	 * @return array
	 */
	private function get_average_rating_chart(): array {
		global $wpdb;

		$rows = self::select(
			"SELECT LEFT( d.meta_value, 4 ) AS year, AVG( r.meta_value ) AS average
			FROM {$wpdb->postmeta} AS d
			INNER JOIN {$wpdb->postmeta} AS r ON r.post_id = d.post_id AND r.meta_key = 'rating'
			INNER JOIN {$wpdb->posts} AS p ON p.ID = d.post_id
			WHERE d.meta_key = 'read_date'
				AND {books}
			GROUP BY year"
		);

		$average_by_year = array_map( fn( $average ) => round( (float) $average, 1 ), wp_list_pluck( $rows, 'average', 'year' ) );

		$items = array_map(
			function ( int $year ) use ( $average_by_year ) {
				$average = $average_by_year[ $year ] ?? 0;
				/* translators: %s: average rating, e.g. 7.5. */
				$text = $average ? sprintf( __( '%s/10', 'library' ), number_format_i18n( $average, 1 ) ) : __( 'No rating', 'library' );

				return self::item( self::year_label( $year ), (string) $year, $average, $text );
			},
			self::reading_years( $average_by_year )
		);

		return array(
			'title'        => __( 'Average rating by year read', 'library' ),
			'column'       => __( 'Year', 'library' ),
			'value_column' => __( 'Average rating', 'library' ),
			/* translators: 1: year, 2: average rating, e.g. "2011 (8.1/10)". */
			'peak'         => __( 'Best: %1$s (%2$s)', 'library' ),
			'max'          => 10,
			'items'        => $items,
		);
	}

	/**
	 * The unread book with the oldest edition date.
	 *
	 * @return WP_Post|null
	 */
	private function get_oldest_unread_book(): ?WP_Post {
		global $wpdb;

		$rows = self::select(
			"SELECT p.ID
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS d ON d.post_id = p.ID AND d.meta_key = 'edition_date' AND d.meta_value != ''
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			WHERE r.post_id IS NULL
				AND {books}
			ORDER BY d.meta_value ASC
			LIMIT 1"
		);

		return $rows ? get_post( (int) $rows[0]->ID ) : null;
	}

	/**
	 * The latest readings, as ranking items; a book read again shows once per reading.
	 *
	 * @return array<int,array{name:string,url:string,text:string}>
	 */
	private function get_recent_reads(): array {
		global $wpdb;

		$rows = self::select(
			"SELECT DISTINCT p.ID, m.meta_value AS read_date
			FROM {$wpdb->postmeta} AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			WHERE m.meta_key = 'read_date'
				AND m.meta_value != ''
				AND {books}
			ORDER BY m.meta_value DESC, p.ID DESC
			LIMIT %d",
			self::LIST_LENGTH
		);

		return array_map(
			fn( object $row ) => array(
				'name' => get_the_title( (int) $row->ID ),
				'url'  => (string) get_edit_post_link( (int) $row->ID ),
				'text' => date_i18n( get_option( 'date_format' ), strtotime( $row->read_date ) ),
			),
			$rows
		);
	}

	/**
	 * "39 books, 30 read".
	 *
	 * @param object $row Row with books and books_read.
	 * @return string
	 */
	private static function books_read_text( object $row ): string {
		return sprintf(
			/* translators: 1: number of books, 2: number of books read. */
			_n( '%1$s book, %2$s read', '%1$s books, %2$s read', (int) $row->books, 'library' ),
			number_format_i18n( (int) $row->books ),
			number_format_i18n( (int) $row->books_read )
		);
	}

	/**
	 * Terms with the most books; pseudonyms count for their real name and collections for their publisher, i.e. their parent.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array<int,array{name:string,url:string,text:string}>
	 */
	private function get_top_terms( string $taxonomy ): array {
		global $wpdb;

		$rows = self::select(
			"SELECT COALESCE( NULLIF( tt.parent, 0 ), tt.term_id ) AS term_id, COUNT( DISTINCT p.ID ) AS books, COUNT( DISTINCT r.post_id ) AS books_read
			FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
			INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			WHERE {library}
			GROUP BY 1
			ORDER BY books DESC, books_read DESC
			LIMIT %d",
			$taxonomy,
			self::LIST_LENGTH
		);

		return self::term_items( $taxonomy, $rows );
	}

	/**
	 * Collections with the most books, i.e. the publishers that have a parent.
	 *
	 * @return array<int,array{name:string,url:string,text:string}>
	 */
	private function get_top_collections(): array {
		global $wpdb;

		$rows = self::select(
			"SELECT tt.term_id, COUNT( DISTINCT p.ID ) AS books, COUNT( DISTINCT r.post_id ) AS books_read
			FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'library-publisher' AND tt.parent != 0
			INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			WHERE {library}
			GROUP BY tt.term_id
			ORDER BY books DESC, books_read DESC
			LIMIT %d",
			self::LIST_LENGTH
		);

		return self::term_items( 'library-publisher', $rows );
	}

	/**
	 * Ranking items from rows of term IDs with their number of books and books read, each one linked to its books.
	 *
	 * @param string   $taxonomy Taxonomy.
	 * @param object[] $rows     Rows with term_id, books and books_read.
	 * @return array<int,array{name:string,url:string,text:string}>
	 */
	private static function term_items( string $taxonomy, array $rows ): array {
		$term_ids = array_map( 'intval', wp_list_pluck( $rows, 'term_id' ) );
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'include'    => $term_ids ? $term_ids : array( 0 ),
				'hide_empty' => false,
			)
		);
		$terms    = is_array( $terms ) ? array_column( $terms, null, 'term_id' ) : array();
		$items    = array();

		foreach ( $rows as $row ) {
			$term = $terms[ (int) $row->term_id ] ?? null;

			if ( ! $term ) {
				continue;
			}

			$items[] = array(
				'name' => $term->name,
				'url'  => admin_url( 'edit.php?post_type=book&' . $taxonomy . '=' . $term->slug ),
				'text' => self::books_read_text( $row ),
			);
		}

		return $items;
	}

	/**
	 * Number of books not read yet, kept or not.
	 *
	 * @return int
	 */
	private function count_unread_books(): int {
		global $wpdb;

		[ $row ] = self::select(
			"SELECT COUNT( DISTINCT p.ID ) AS books
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			WHERE r.post_id IS NULL
				AND {books}"
		);

		return (int) $row->books;
	}

	/**
	 * Number of terms of a taxonomy with at least one book kept; pseudonyms count for their real name and collections for their publisher.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return int
	 */
	private function count_terms( string $taxonomy ): int {
		global $wpdb;

		[ $row ] = self::select(
			"SELECT COUNT( DISTINCT COALESCE( NULLIF( tt.parent, 0 ), tt.term_id ) ) AS terms
			FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
			INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
			WHERE {library}",
			$taxonomy
		);

		return (int) $row->terms;
	}

	/**
	 * Number of books kept with a translator.
	 *
	 * @return int
	 */
	private function count_translated_books(): int {
		global $wpdb;

		[ $row ] = self::select(
			"SELECT COUNT( DISTINCT p.ID ) AS books
			FROM {$wpdb->postmeta} AS t
			INNER JOIN {$wpdb->posts} AS p ON p.ID = t.post_id
			WHERE t.meta_key = 'translators'
				AND t.meta_value != ''
				AND {library}"
		);

		return (int) $row->books;
	}
}
