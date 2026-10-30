<?php
/**
 * Reading stats shared by the dashboard, the reading idea and the REST API.
 *
 * @link       https://github.com/19h47/library
 * @since      6.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Queries the books and their readings: the SQL definition of a reading, the books read and to read, this year against last year.
 *
 * @since      6.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Stats {

	/**
	 * SQL condition on the posts table aliased "p": all books, kept or not, for the reading stats; "{statuses}" stands
	 * for the statuses of the books, from Library_Books::get_statuses().
	 *
	 * @var string
	 */
	private const BOOKS = "p.post_type = 'library_book' AND p.post_status IN ( {statuses} )";

	/**
	 * SQL condition on the posts table aliased "p": the books at hand, kept or not, but not those to buy, for the reading pile.
	 *
	 * @var string
	 */
	private const AT_HAND = self::BOOKS . " AND p.post_status != '" . Library_Posts::TO_BUY . "'";

	/**
	 * SQL condition on the posts table aliased "p": the books in the library, i.e. kept.
	 *
	 * @var string
	 */
	private const LIBRARY = self::AT_HAND . " AND p.post_status != '" . Library_Posts::NOT_KEPT . "'";

	/**
	 * Run a SELECT on the books; "{books}", "{at_hand}" and "{library}" in the SQL stand for the BOOKS, AT_HAND and LIBRARY conditions.
	 *
	 * "{readings}" stands for the readings: a table aliased "r" of a `book` and its read `date`, once per book and date,
	 * kept or not. It is the one SQL definition of a reading, as Library_Meta::get_read_dates() is the PHP one.
	 *
	 * @param string $sql  SQL, with placeholders for the arguments.
	 * @param mixed  ...$args Values for the placeholders.
	 * @return object[]
	 */
	public static function get_results( string $sql, mixed ...$args ): array {
		global $wpdb;

		$readings = "( SELECT DISTINCT m.post_id AS book, m.meta_value AS date
			FROM {$wpdb->postmeta} AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			WHERE m.meta_key = 'library_read_date' AND m.meta_value != '' AND {books} ) AS r";
		$statuses = "'" . implode( "', '", Library_Books::get_statuses() ) . "'";
		// The readings first: they hold the books condition; the statuses last: every condition holds them.
		$sql = str_replace( array( '{readings}', '{books}', '{at_hand}', '{library}', '{statuses}' ), array( $readings, self::BOOKS, self::AT_HAND, self::LIBRARY, $statuses ), $sql );
		$sql = $args ? $wpdb->prepare( $sql, ...$args ) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Constant condition, values prepared here.

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above; dashboard widgets and REST routes, run on demand.
	}

	/**
	 * Reading stats: books read out of the books in the library, i.e. published.
	 *
	 * @return array{read:int,total:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$total = (int) wp_count_posts( 'library_book' )->publish;

		// The list table sort filters must not alter this count.
		$read_query = new WP_Query(
			array(
				'post_type'        => 'library_book',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'meta_key'         => 'library_read', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Single count on a small dataset.
				'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Same.
			)
		);

		$read    = (int) $read_query->found_posts;
		$percent = $total > 0 ? (int) round( $read / $total * 100 ) : 0;

		return array(
			'read'    => $read,
			'total'   => $total,
			'percent' => $percent,
		);
	}

	/**
	 * Number of books not read yet, kept or not, but not those to buy: the reading pile.
	 *
	 * @return int
	 */
	private static function count_unread_books(): int {
		global $wpdb;

		[ $row ] = self::get_results(
			"SELECT COUNT( DISTINCT p.ID ) AS books
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'library_read' AND r.meta_value = '1'
			WHERE r.post_id IS NULL
				AND {at_hand}"
		);

		return (int) $row->books;
	}

	/**
	 * Books read this year and by the same date last year, the number expected by the end of the year, and the year
	 * the books to read would all be read in; both at the pace of this year, a book read again counting once per reading.
	 *
	 * Kept for the request: the reading and reading pile widgets and the reading idea of the dashboard all ask for it.
	 *
	 * @return array{year:int,this_year:int,last_year:int,projection:int|null,to_read:int,all_read_in:int|null}
	 */
	public static function get_reading_year_to_date(): array {
		static $year_to_date = null;

		if ( null !== $year_to_date ) {
			return $year_to_date;
		}

		$today          = new DateTimeImmutable( 'now', wp_timezone() );
		$same_day       = $today->modify( '-1 year' );
		$this_year_from = $today->format( 'Y-01-01' );
		$last_year_from = $same_day->format( 'Y-01-01' );

		[ $row ] = self::get_results(
			'SELECT
				COUNT( IF( r.date BETWEEN %s AND %s, 1, NULL ) ) AS this_year,
				COUNT( IF( r.date BETWEEN %s AND %s, 1, NULL ) ) AS last_year
			FROM {readings}',
			$this_year_from,
			$today->format( 'Y-m-d' ),
			$last_year_from,
			$same_day->format( 'Y-m-d' )
		);

		$this_year    = (int) $row->this_year;
		$day_of_year  = (int) $today->format( 'z' ) + 1;
		$days_in_year = 365 + (int) $today->format( 'L' );
		$to_read      = self::count_unread_books();

		// A month of readings is the least to project from; without readings, the books to read are never read.
		$has_pace    = $day_of_year >= 31;
		$projection  = $has_pace ? (int) round( $this_year * $days_in_year / $day_of_year ) : null;
		$all_read_in = null;

		if ( $has_pace && $this_year && $to_read ) {
			$days_left   = (int) ceil( $to_read * $day_of_year / $this_year );
			$all_read_in = (int) $today->modify( "+$days_left days" )->format( 'Y' );
		}

		$year_to_date = array(
			'year'        => (int) $today->format( 'Y' ),
			'this_year'   => $this_year,
			'last_year'   => (int) $row->last_year,
			'projection'  => $projection,
			'to_read'     => $to_read,
			'all_read_in' => $all_read_in,
		);

		return $year_to_date;
	}
}
