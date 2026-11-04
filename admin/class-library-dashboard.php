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
 * Adds one dashboard widget per topic: reading, ratings, reading pile and collection, and the number of books to "At a Glance".
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
	 * Number of entries in the rankings and the recent reads.
	 *
	 * @var int
	 */
	private const int LIST_LENGTH = 5;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'dashboard_glance_items', array( $this, 'add_books_at_a_glance' ) );
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
		[ $url, $version ] = Library::get_asset( 'admin/css/library-dashboard.css' );

		wp_enqueue_style( 'library-dashboard', $url, array(), $version );
	}

	/**
	 * Register the dashboard widgets.
	 */
	public function register_widgets(): void {
		wp_add_dashboard_widget( 'library_dashboard_reading', __( 'Reading', 'library' ), array( $this, 'render_reading' ) );
		wp_add_dashboard_widget( 'library_dashboard_ratings', __( 'Ratings', 'library' ), array( $this, 'render_ratings' ) );
		wp_add_dashboard_widget( 'library_dashboard_pile', __( 'Reading pile', 'library' ), array( $this, 'render_pile' ) );
		wp_add_dashboard_widget( 'library_dashboard_collection', __( 'Library', 'library' ), array( $this, 'render_collection' ) );
	}

	/**
	 * Reading widget: books read, this year against last year, charts by year and month.
	 */
	public function render_reading(): void {
		$stats        = Library_Stats::get_reading_stats();
		$year_to_date = Library_Stats::get_reading_year_to_date();
		$pages_read   = $this->get_pages_read_this_year();
		$charts       = $this->get_reading_charts();

		$this->render_partial( 'reading', compact( 'stats', 'year_to_date', 'pages_read', 'charts' ) );
	}

	/**
	 * Ratings widget: average, distribution and average by year read.
	 */
	public function render_ratings(): void {
		$ratings = $this->get_ratings();
		$charts  = array( $ratings['chart'], $this->get_average_rating_chart() );

		$this->render_partial( 'ratings', compact( 'ratings', 'charts' ) );
	}

	/**
	 * Reading pile widget: books to read, kept or not, the oldest one and the latest reads.
	 */
	public function render_pile(): void {
		$to_read      = Library_Stats::get_reading_year_to_date()['to_read'];
		$oldest       = $this->get_oldest_unread_book();
		$recent_reads = $this->get_recent_reads();

		$this->render_partial( 'pile', compact( 'to_read', 'oldest', 'recent_reads' ) );
	}

	/**
	 * Collection widget, on the books kept: authors, publishers, collections, languages, translations and edition years.
	 */
	public function render_collection(): void {
		$stats       = Library_Stats::get_reading_stats();
		$books       = self::get_kept_books();
		$authors     = self::count_books_by( $books, 'author_ids' );
		$publishers  = self::count_books_by( $books, 'publisher_ids' );
		$collections = self::count_books_by( $books, 'collection_ids' );
		$languages   = self::count_books_by( $books, 'language_ids' );
		$forms       = self::count_books_by( $books, 'form_ids' );
		$rankings    = array(
			array(
				'title' => _x( 'Forms', 'library forms general name', 'library' ),
				// Few, so every one of them: how the books are shared out.
				'items' => self::build_term_items( 'library_form', $forms, null ),
			),
			array(
				'title' => __( 'Most present authors', 'library' ),
				'items' => self::build_term_items( 'library_person', $authors ),
			),
			array(
				'title' => __( 'Most present publishers', 'library' ),
				'items' => self::build_term_items( 'library_publisher', $publishers ),
			),
			array(
				'title' => __( 'Most present collections', 'library' ),
				'items' => self::build_term_items( 'library_publisher', $collections ),
			),
			array(
				'title' => __( 'Most present original languages', 'library' ),
				'items' => self::build_term_items( 'library_language', $languages ),
			),
		);
		$counts      = array(
			'books'      => $stats['total'],
			'authors'    => count( $authors ),
			'publishers' => count( $publishers ),
			'languages'  => count( $languages ),
			'translated' => count( array_filter( $books, fn( array $book ) => $book['translators'] ) ),
		);
		$charts      = array( $this->get_edition_chart() );

		$this->render_partial( 'collection', compact( 'counts', 'rankings', 'charts' ) );
	}

	/**
	 * Render a widget partial; charts without data are left out.
	 *
	 * @param string $widget Widget name, i.e. the partial suffix.
	 * @param array  $data   Variables for the partial.
	 */
	private function render_partial( string $widget, array $data ): void {
		$data['charts'] = array_filter( $data['charts'] ?? array(), fn( array $chart ) => array_sum( array_column( $chart['items'], 'value' ) ) > 0 );

		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Partial variables, built above.

		include plugin_dir_path( __FILE__ ) . "partials/library-dashboard-$widget.php";
	}

	/**
	 * Count books by the start of a meta value, e.g. "2024-05" (length 7) or "2024" (length 4) for a date.
	 *
	 * A book counts once per value.
	 *
	 * @param string $meta_key Meta key.
	 * @param int    $length   Number of characters to group by.
	 * @param string $scope    "{books}" for all books, "{library}" for the books kept.
	 * @return array<string,int> Books keyed by value start, in ascending order.
	 */
	private function count_by_meta_start( string $meta_key, int $length, string $scope = '{books}' ): array {
		global $wpdb;

		$rows = Library_Stats::get_results(
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
	 * A chart bar.
	 *
	 * @param string    $label Short label under the bar.
	 * @param string    $name  Full name, for the tooltip, the data table and the peak.
	 * @param int|float $value Bar value.
	 * @param string    $text  Value as text, e.g. "12 books".
	 * @return array{label:string,name:string,value:int|float,text:string}
	 */
	private static function build_chart_item( string $label, string $name, int|float $value, string $text ): array {
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
	 * @param array  $items  Bars, from build_chart_item().
	 * @return array
	 */
	private static function build_books_chart( string $title, string $column, array $items ): array {
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
	private static function get_reading_years( array $by_year ): array {
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
	private static function format_short_year( int $year ): string {
		return '’' . substr( (string) $year, -2 );
	}

	/**
	 * Pages read this year, over the books read this year with their number of pages;
	 * a book read again counts once per reading.
	 *
	 * @return array{pages:int,books:int}
	 */
	private function get_pages_read_this_year(): array {
		global $wpdb;

		[ $row ] = Library_Stats::get_results(
			"SELECT SUM( n.meta_value ) AS pages, COUNT(*) AS books
			FROM {readings}
			INNER JOIN {$wpdb->postmeta} AS n ON n.post_id = r.book AND n.meta_key = 'library_pages'
			WHERE r.date >= %s",
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
		$books_by_yearmon = array_map( 'intval', wp_list_pluck( Library_Stats::get_results( 'SELECT LEFT( r.date, 7 ) AS month, COUNT(*) AS readings FROM {readings} GROUP BY month ORDER BY month' ), 'readings', 'month' ) );

		foreach ( $books_by_yearmon as $year_month => $books ) {
			[ $year, $month ] = array_map( 'intval', explode( '-', $year_month ) );

			$books_by_year[ $year ]    = ( $books_by_year[ $year ] ?? 0 ) + $books;
			$books_by_month[ $month ] += $books;

			if ( $current_year === $year ) {
				$books_this_year[ $month ] += $books;
			}
		}

		$year_items = array_map(
			fn( int $year ) => self::build_chart_item( self::format_short_year( $year ), (string) $year, $books_by_year[ $year ] ?? 0, Library_Books::format_books( $books_by_year[ $year ] ?? 0 ) ),
			self::get_reading_years( $books_by_year )
		);

		$month_items = fn( array $books ) => array_map(
			fn( int $month ) => self::build_chart_item(
				mb_strtoupper( mb_substr( $wp_locale->get_month( $month ), 0, 1 ) ),
				$wp_locale->get_month( $month ),
				$books[ $month ],
				Library_Books::format_books( $books[ $month ] )
			),
			range( 1, 12 )
		);

		return array(
			self::build_books_chart( __( 'Books read by year', 'library' ), __( 'Year', 'library' ), $year_items ),
			/* translators: %d: year. */
			self::build_books_chart( sprintf( __( 'Books read in %d by month', 'library' ), $current_year ), __( 'Month', 'library' ), $month_items( $books_this_year ) ),
			self::build_books_chart( __( 'Reading distribution by month', 'library' ), __( 'Month', 'library' ), $month_items( $books_by_month ) ),
		);
	}

	/**
	 * Books by edition year, by decade when there are too many years to show.
	 *
	 * @return array
	 */
	private function get_edition_chart(): array {
		$books_by_year = $this->count_by_meta_start( 'library_edition_date', 4, '{library}' );

		if ( count( $books_by_year ) <= 40 ) {
			$items = array();

			foreach ( $books_by_year as $year => $books ) {
				$items[] = self::build_chart_item( (string) $year, (string) $year, $books, Library_Books::format_books( $books ) );
			}

			return self::build_books_chart( __( 'Edition year distribution', 'library' ), __( 'Edition year', 'library' ), $items );
		}

		$books_by_decade = array();

		foreach ( $books_by_year as $year => $books ) {
			$decade = intdiv( (int) $year, 10 ) * 10;

			$books_by_decade[ $decade ] = ( $books_by_decade[ $decade ] ?? 0 ) + $books;
		}

		$items = array();

		foreach ( $books_by_decade as $decade => $books ) {
			$items[] = self::build_chart_item( (string) $decade, sprintf( '%d–%d', $decade, $decade + 9 ), $books, Library_Books::format_books( $books ) );
		}

		return self::build_books_chart( __( 'Edition year distribution (by decade)', 'library' ), __( 'Edition years', 'library' ), $items );
	}

	/**
	 * Number of rated books, average rating and distribution, rounded to the nearest point.
	 *
	 * @return array{count:int,average:float|null,chart:array}
	 */
	private function get_ratings(): array {
		$books_by_rating = $this->count_by_meta_start( 'library_rating', 4 );
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
			fn( int $point ) => self::build_chart_item( (string) $point, $point . '/10', $books_by_point[ $point ], Library_Books::format_books( $books_by_point[ $point ] ) ),
			range( 1, 10 )
		);

		return array(
			'count'   => $rated,
			'average' => $rated ? round( $sum / $rated, 1 ) : null,
			'chart'   => self::build_books_chart( __( 'Rating distribution', 'library' ), __( 'Rating', 'library' ), $items ),
		);
	}

	/**
	 * Average rating of the books read each year.
	 *
	 * @return array
	 */
	private function get_average_rating_chart(): array {
		global $wpdb;

		// The ratings first: from the readings, MySQL 5.7 would search the meta of each book read for its rating.
		$rows = Library_Stats::get_results(
			"SELECT LEFT( r.date, 4 ) AS year, AVG( n.meta_value ) AS average
			FROM {$wpdb->postmeta} AS n
			STRAIGHT_JOIN {readings} ON r.book = n.post_id
			WHERE n.meta_key = 'library_rating'
			GROUP BY year"
		);

		$average_by_year = array_map( fn( $average ) => round( (float) $average, 1 ), wp_list_pluck( $rows, 'average', 'year' ) );

		$items = array_map(
			function ( int $year ) use ( $average_by_year ) {
				$average = $average_by_year[ $year ] ?? 0;
				/* translators: %s: average rating, e.g. 7.5. */
				$text = $average ? sprintf( __( '%s/10', 'library' ), number_format_i18n( $average, 1 ) ) : __( 'No rating', 'library' );

				return self::build_chart_item( self::format_short_year( $year ), (string) $year, $average, $text );
			},
			self::get_reading_years( $average_by_year )
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
	 * The unread book at hand with the oldest edition date.
	 *
	 * @return WP_Post|null
	 */
	private function get_oldest_unread_book(): ?WP_Post {
		global $wpdb;

		// NOT IN lists the books read once; a LEFT JOIN would search the meta of each book, five times slower on MySQL 5.7.
		$rows = Library_Stats::get_results(
			"SELECT p.ID
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS d ON d.post_id = p.ID AND d.meta_key = 'library_edition_date' AND d.meta_value != ''
			WHERE p.ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'library_read' AND meta_value = '1' )
				AND {at_hand}
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
		$rows = Library_Stats::get_results( 'SELECT r.book, r.date FROM {readings} ORDER BY r.date DESC, r.book DESC LIMIT %d', self::LIST_LENGTH );

		return array_map(
			fn( object $row ) => array(
				'name' => get_the_title( (int) $row->book ),
				'url'  => (string) get_edit_post_link( (int) $row->book ),
				'text' => date_i18n( get_option( 'date_format' ), strtotime( $row->date ) ),
			),
			$rows
		);
	}

	/**
	 * Books and books read of a ranked term, e.g. "12 books, 3 read".
	 *
	 * @param array{books:int,books_read:int} $count Books and books read.
	 * @return string
	 */
	private static function format_books_read( array $count ): string {
		$books = Library_Books::format_books( $count['books'] );
		/* translators: %s: number of books read, after the number of books of a person, a series or a collection. */
		$read = sprintf( _n( '%s read', '%s read', $count['books_read'], 'library' ), number_format_i18n( $count['books_read'] ) );

		/* translators: 1: number of books, 2: number of books read. */
		return sprintf( __( '%1$s, %2$s', 'library' ), $books, $read );
	}

	/**
	 * The books in the library, i.e. kept, from Library_Books::get_books().
	 *
	 * @return array<int,array>
	 */
	private static function get_kept_books(): array {
		return array_filter( Library_Books::get_books(), fn( array $book ) => ! in_array( $book['status'], array( Library_Posts::TO_BUY, Library_Posts::NOT_KEPT ), true ) );
	}

	/**
	 * Books and books read of each term the books count for, as their field of term IDs gives them, such as author_ids: an
	 * author under the name they are known by, a publisher with its collections, a collection with its sub-collections. A
	 * collective book is not part of the work of its authors. The terms with the most books come first, then the most read,
	 * then the oldest.
	 *
	 * @param array<int,array> $books Books, from Library_Books::get_books().
	 * @param string           $field Field of term IDs: author_ids, publisher_ids, collection_ids, language_ids or form_ids.
	 * @return array<int,array{books:int,books_read:int}> Keyed by term ID.
	 */
	private static function count_books_by( array $books, string $field ): array {
		$counts = array();

		foreach ( $books as $book ) {
			if ( 'author_ids' === $field && $book['collective'] ) {
				continue;
			}

			foreach ( $book[ $field ] as $term_id ) {
				$counts[ $term_id ] = array(
					'books'      => ( $counts[ $term_id ]['books'] ?? 0 ) + 1,
					'books_read' => ( $counts[ $term_id ]['books_read'] ?? 0 ) + (int) $book['read'],
				);
			}
		}

		uksort( $counts, fn( int $a, int $b ) => array( $counts[ $b ]['books'], $counts[ $b ]['books_read'], $a ) <=> array( $counts[ $a ]['books'], $counts[ $a ]['books_read'], $b ) );

		return $counts;
	}

	/**
	 * Ranking items of the first terms counted, each one linked to its books.
	 *
	 * @param string                                     $taxonomy Taxonomy.
	 * @param array<int,array{books:int,books_read:int}> $counts   Books and books read, by term ID, in their order.
	 * @param int|null                                   $length   Number of terms, or null for all of them.
	 * @return array<int,array{name:string,url:string,text:string}>
	 */
	private static function build_term_items( string $taxonomy, array $counts, ?int $length = self::LIST_LENGTH ): array {
		$counts = array_slice( $counts, 0, $length, true );
		$terms  = $counts ? get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'include'    => array_keys( $counts ),
				'hide_empty' => false,
			)
		) : array();
		$terms  = is_array( $terms ) ? array_column( $terms, null, 'term_id' ) : array();
		$items  = array();

		foreach ( $counts as $term_id => $count ) {
			$term = $terms[ $term_id ] ?? null;

			if ( ! $term ) {
				continue;
			}

			$items[] = array(
				'name' => 'library_publisher' === $taxonomy ? Library_Taxonomies::get_collection_name( $term ) : $term->name,
				'url'  => admin_url( 'edit.php?post_type=library_book&' . $taxonomy . '=' . $term->slug ),
				'text' => self::format_books_read( $count ),
			);
		}

		return $items;
	}

	/**
	 * Add the number of published books to the "At a Glance" dashboard widget.
	 *
	 * @param string[] $items Items.
	 * @return string[]
	 */
	public function add_books_at_a_glance( array $items ): array {
		$count = (int) wp_count_posts( 'library_book' )->publish;

		if ( ! $count ) {
			return $items;
		}

		$text = Library_Books::format_books( $count );

		if ( ! current_user_can( get_post_type_object( 'library_book' )->cap->edit_posts ) ) {
			$items[] = sprintf( '<span class="book-count">%s</span>', esc_html( $text ) );

			return $items;
		}

		$items[] = sprintf(
			'<a class="book-count" href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=library_book' ) ),
			esc_html( $text )
		);

		return $items;
	}
}
