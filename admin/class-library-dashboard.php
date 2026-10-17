<?php
/**
 * Dashboard widget for Library.
 *
 * @link       https://github.com/19h47/library
 * @since      1.1.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Adds a small dashboard widget summarizing books read.
 *
 * @since      1.1.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Dashboard {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue dashboard assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'index.php' !== $hook ) {
			return;
		}

		[ $url, $version ] = Library_Admin::asset( 'css/library-dashboard.css' );

		wp_enqueue_style( 'library-dashboard', $url, array(), $version );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget(
			'library_dashboard_overview',
			__( 'Books reading overview', 'library' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 */
	public function render_widget(): void {
		$stats   = Library_Posts::get_reading_stats();
		$ratings = $this->get_ratings();
		$charts  = array(
			...$this->get_reading_charts(),
			$this->get_publication_chart(),
			$ratings['chart'],
		);
		$charts  = array_filter( $charts, fn( array $chart ) => array_sum( array_column( $chart['items'], 'value' ) ) > 0 );

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-widget.php';
	}

	/**
	 * Count books by the start of a meta value, e.g. "2024-05" (length 7) or "2024" (length 4) for a date.
	 *
	 * Books are counted like in the reading stats: trash and auto-drafts excluded.
	 *
	 * @param string $meta_key Meta key.
	 * @param int    $length   Number of characters to group by.
	 * @return array<string,int> Books keyed by value start, in ascending order.
	 */
	private function count_by_meta_start( string $meta_key, int $length ): array {
		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard widget only.
			$wpdb->prepare(
				"SELECT LEFT( m.meta_value, %d ) AS value_start, COUNT( * ) AS books
				FROM {$wpdb->postmeta} AS m
				INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
				WHERE m.meta_key = %s
					AND m.meta_value != ''
					AND p.post_type = 'book'
					AND p.post_status NOT IN ( 'trash', 'auto-draft' )
				GROUP BY value_start
				ORDER BY value_start",
				$length,
				$meta_key
			)
		);

		return array_map( 'intval', wp_list_pluck( $rows, 'books', 'value_start' ) );
	}

	/**
	 * A chart bar.
	 *
	 * @param string $label Short label under the bar.
	 * @param string $name  Full name, for the tooltip and the data table.
	 * @param int    $value Number of books.
	 * @return array{label:string,name:string,value:int}
	 */
	private static function item( string $label, string $name, int $value ): array {
		return array(
			'label' => $label,
			'name'  => $name,
			'value' => $value,
		);
	}

	/**
	 * Books read by year, by month this year and by month over all years, from the read dates.
	 *
	 * @return array<int,array{title:string,column:string,items:array}>
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

		$first_year = $books_by_year ? min( array_keys( $books_by_year ) ) : $current_year;
		$last_year  = $books_by_year ? max( $current_year, ...array_keys( $books_by_year ) ) : $current_year;

		$year_items = array_map(
			fn( int $year ) => self::item( '’' . substr( (string) $year, -2 ), (string) $year, $books_by_year[ $year ] ?? 0 ),
			range( $first_year, $last_year )
		);

		$month_items = fn( array $books ) => array_map(
			fn( int $month ) => self::item(
				mb_strtoupper( mb_substr( $wp_locale->get_month( $month ), 0, 1 ) ),
				$wp_locale->get_month( $month ),
				$books[ $month ]
			),
			range( 1, 12 )
		);

		return array(
			array(
				'title'  => __( 'Books read by year', 'library' ),
				'column' => __( 'Year', 'library' ),
				'items'  => $year_items,
			),
			array(
				/* translators: %d: year. */
				'title'  => sprintf( __( 'Books read in %d by month', 'library' ), $current_year ),
				'column' => __( 'Month', 'library' ),
				'items'  => $month_items( $books_this_year ),
			),
			array(
				'title'  => __( 'Reading distribution by month', 'library' ),
				'column' => __( 'Month', 'library' ),
				'items'  => $month_items( $books_by_month ),
			),
		);
	}

	/**
	 * Books by publication year, by decade when there are too many years to show.
	 *
	 * @return array{title:string,column:string,items:array}
	 */
	private function get_publication_chart(): array {
		$books_by_year = $this->count_by_meta_start( 'date_published', 4 );

		if ( count( $books_by_year ) <= 40 ) {
			return array(
				'title'  => __( 'Publication year distribution', 'library' ),
				'column' => __( 'Publication year', 'library' ),
				'items'  => array_map(
					fn( string $year, int $books ) => self::item( $year, $year, $books ),
					array_map( 'strval', array_keys( $books_by_year ) ),
					$books_by_year
				),
			);
		}

		$books_by_decade = array();

		foreach ( $books_by_year as $year => $books ) {
			$decade = intdiv( (int) $year, 10 ) * 10;

			$books_by_decade[ $decade ] = ( $books_by_decade[ $decade ] ?? 0 ) + $books;
		}

		return array(
			'title'  => __( 'Publication year distribution (by decade)', 'library' ),
			'column' => __( 'Publication years', 'library' ),
			'items'  => array_map(
				fn( int $decade, int $books ) => self::item( (string) $decade, sprintf( '%d–%d', $decade, $decade + 9 ), $books ),
				array_keys( $books_by_decade ),
				$books_by_decade
			),
		);
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

		return array(
			'count'   => $rated,
			'average' => $rated ? round( $sum / $rated, 1 ) : null,
			'chart'   => array(
				'title'  => __( 'Rating distribution', 'library' ),
				'column' => __( 'Rating', 'library' ),
				'items'  => array_map(
					fn( int $point ) => self::item( (string) $point, $point . '/10', $books_by_point[ $point ] ),
					range( 1, 10 )
				),
			),
		);
	}
}
