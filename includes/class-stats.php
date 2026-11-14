<?php
/**
 * Reading stats shared by the dashboard, the books list, the reading idea and the REST API.
 *
 * @link       https://github.com/19h47/library
 * @since      6.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use DateTimeImmutable;

/**
 * Counts the books and their readings, from Books::get_books(): the readings, the books read and to read, this
 * year against last year.
 *
 * @since      6.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Stats {

	/**
	 * The readings with a date, latest first: see Books::get_readings(). A date of a reading of the day may have its time,
	 * 2026-11-01T21:40: its day is its first 10 characters.
	 *
	 * @return list<Book>
	 */
	public static function get_readings(): array {
		return array_values( array_reverse( array_filter( Books::get_readings(), fn( Book $reading ) => '' !== $reading->read_date ) ) );
	}

	/**
	 * The readings of `$year`, in the order they were read.
	 *
	 * @param int $year Year.
	 * @return list<Book>
	 */
	public static function get_year_readings( int $year ): array {
		return array_values( array_filter( Books::get_readings(), fn( Book $reading ) => str_starts_with( $reading->read_date, "$year-" ) ) );
	}

	/**
	 * The books read in `$year`, each one once, in the order they were first read that year, as its latest reading of the
	 * year: its read date, and the rating it ended with, the one the book had that year.
	 *
	 * @param int $year Year.
	 * @return list<Book>
	 */
	public static function get_year_books( int $year ): array {
		$books = array();

		// A book keeps its place, that of its first reading of the year, and takes its latest.
		foreach ( self::get_year_readings( $year ) as $reading ) {
			$books[ $reading->id ] = $reading;
		}

		return array_values( $books );
	}

	/**
	 * The pages of readings, over those with their number of pages, and how many have it; a book read again counts once
	 * per reading.
	 *
	 * @param Book[] $readings Readings.
	 * @return array{total:int,counted:int}
	 */
	public static function count_pages( array $readings ): array {
		$counted = array_filter( $readings, fn( Book $reading ) => null !== $reading->pages );

		return array(
			'total'   => (int) array_sum( array_column( $counted, 'pages' ) ),
			'counted' => count( $counted ),
		);
	}

	/**
	 * The readings of each year with one, the earliest first.
	 *
	 * @return array<int,int> Readings by year.
	 */
	public static function count_by_year(): array {
		$counts = array_count_values( array_map( fn( Book $reading ) => (int) substr( $reading->read_date, 0, 4 ), self::get_readings() ) );

		ksort( $counts );

		return $counts;
	}

	/**
	 * The readings of each month, from January, whatever their year.
	 *
	 * @param Book[] $readings Readings with a date.
	 * @return array<int,int> Readings by month, from 1 to 12.
	 */
	public static function count_by_month( array $readings ): array {
		$counts = array_count_values( array_map( fn( Book $reading ) => (int) substr( $reading->read_date, 5, 2 ), $readings ) );

		return array_replace( array_fill( 1, 12, 0 ), $counts );
	}

	/**
	 * The days of `$year` gone by: all of them for a year gone, those until today for this one, none for one to come.
	 *
	 * @param int $year Year.
	 * @return int
	 */
	public static function count_days( int $year ): int {
		$today   = new DateTimeImmutable( 'now', wp_timezone() );
		$current = (int) $today->format( 'Y' );

		if ( $year > $current ) {
			return 0;
		}

		return $year < $current ? (int) new DateTimeImmutable( "$year-12-31" )->format( 'z' ) + 1 : (int) $today->format( 'z' ) + 1;
	}

	/**
	 * The books in the library, i.e. kept, published.
	 *
	 * @return array<int,Book>
	 */
	public static function get_kept_books(): array {
		return array_filter( Books::get_books(), fn( Book $book ) => Status::In_Library === $book->status );
	}

	/**
	 * Reading stats: books read out of the books in the library.
	 *
	 * @return array{read_count:int,book_count:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$library = self::get_kept_books();
		$read    = Books::count_read( $library );
		$total   = count( $library );
		$percent = $total > 0 ? (int) round( $read / $total * 100 ) : 0;

		return array(
			'read_count' => $read,
			'book_count' => $total,
			'percent'    => $percent,
		);
	}

	/**
	 * The books on the reading pile: see Books::is_on_the_pile().
	 *
	 * @return array<int,Book>
	 */
	public static function get_books_on_the_pile(): array {
		return array_filter( Books::get_books(), fn( Book $book ) => $book->is_on_the_pile() );
	}

	/**
	 * Books read this year and by the same date last year, the number expected by the end of the year, and the year
	 * the books to read would all be read in; both at the pace of this year, a book read again counting once per reading.
	 *
	 * @return array{year:int,read_count:int,last_year_read_count:int,projected_read_count:int|null,to_read_count:int,all_read_in:int|null}
	 */
	public static function get_reading_year_to_date(): array {
		$today    = new DateTimeImmutable( 'now', wp_timezone() );
		$same_day = $today->modify( '-1 year' );
		$days     = array_map( fn( Book $reading ) => substr( $reading->read_date, 0, 10 ), self::get_readings() );
		$count    = fn( DateTimeImmutable $day ) => count( array_filter( $days, fn( string $read_on ) => $read_on >= $day->format( 'Y-01-01' ) && $read_on <= $day->format( 'Y-m-d' ) ) );

		$this_year    = $count( $today );
		$day_of_year  = self::count_days( (int) $today->format( 'Y' ) );
		$days_in_year = 365 + (int) $today->format( 'L' );
		$to_read      = count( self::get_books_on_the_pile() );

		// A month of readings is the least to project from; without readings, the books to read are never read.
		$has_pace    = $day_of_year >= 31;
		$projection  = $has_pace ? (int) round( $this_year * $days_in_year / $day_of_year ) : null;
		$all_read_in = null;

		if ( $has_pace && $this_year && $to_read ) {
			$days_left   = (int) ceil( $to_read * $day_of_year / $this_year );
			$all_read_in = (int) $today->modify( "+$days_left days" )->format( 'Y' );
		}

		return array(
			'year'                 => (int) $today->format( 'Y' ),
			'read_count'           => $this_year,
			'last_year_read_count' => $count( $same_day ),
			'projected_read_count' => $projection,
			'to_read_count'        => $to_read,
			'all_read_in'          => $all_read_in,
		);
	}
}
