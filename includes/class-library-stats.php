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
 * Queries the books and their readings: the SQL definition of a reading, the books read, this year against last year.
 *
 * @since      6.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Stats {

	/**
	 * SQL condition on the posts table aliased "p": all books, kept or not, for the reading stats.
	 *
	 * @var string
	 */
	private const BOOKS = "p.post_type = 'book' AND p.post_status NOT IN ( 'trash', 'auto-draft' )";

	/**
	 * SQL condition on the posts table aliased "p": the books in the library, i.e. kept.
	 *
	 * @var string
	 */
	private const LIBRARY = self::BOOKS . " AND p.post_status != '" . Library_Posts::NOT_KEPT . "'";

	/**
	 * Run a SELECT on the books; "{books}" and "{library}" in the SQL stand for the BOOKS and LIBRARY conditions.
	 *
	 * "{readings}" stands for the readings: a table aliased "r" of a `book` and its read `date`, once per book and date,
	 * kept or not. It is the one SQL definition of a reading, as Library_Meta::get_read_dates() is the PHP one.
	 *
	 * @param string $sql  SQL, with placeholders for the arguments.
	 * @param mixed  ...$args Values for the placeholders.
	 * @return object[]
	 */
	public static function select( string $sql, mixed ...$args ): array {
		global $wpdb;

		$readings = "( SELECT DISTINCT m.post_id AS book, m.meta_value AS date
			FROM {$wpdb->postmeta} AS m
			INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
			WHERE m.meta_key = 'read_date' AND m.meta_value != '' AND {books} ) AS r";
		// The readings first: they hold the books condition.
		$sql = str_replace( array( '{readings}', '{books}', '{library}' ), array( $readings, self::BOOKS, self::LIBRARY ), $sql );
		$sql = $args ? $wpdb->prepare( $sql, ...$args ) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Constant condition, values prepared here.

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above; dashboard widgets and REST routes, run on demand.
	}

	/**
	 * Reading stats: books read out of the books in the library, i.e. without trash, auto-drafts and books not kept.
	 *
	 * @return array{read:int,total:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$counts = (array) wp_count_posts( 'book' );
		unset( $counts['trash'], $counts['auto-draft'], $counts[ Library_Posts::NOT_KEPT ] );

		$total = (int) array_sum( $counts );

		// The list table sort filters must not alter this count.
		$read_query = new WP_Query(
			array(
				'post_type'        => 'book',
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'meta_key'         => 'read', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Single count on a small dataset.
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
	 * Books read this year and by the same date last year, and the number expected by the end of the year;
	 * a book read again counts once per reading.
	 *
	 * @return array{year:int,this_year:int,last_year:int,projection:int|null}
	 */
	public static function get_year_to_date(): array {
		$today          = new DateTimeImmutable( 'now', wp_timezone() );
		$same_day       = $today->modify( '-1 year' );
		$this_year_from = $today->format( 'Y-01-01' );
		$last_year_from = $same_day->format( 'Y-01-01' );

		[ $row ] = self::select(
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

		// A month of readings is the least to project a whole year from.
		$projection = $day_of_year >= 31 ? (int) round( $this_year * $days_in_year / $day_of_year ) : null;

		return array(
			'year'       => (int) $today->format( 'Y' ),
			'this_year'  => $this_year,
			'last_year'  => (int) $row->last_year,
			'projection' => $projection,
		);
	}
}
