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
	 * The readings with a date, latest first: a read date and its book, once per book and date, kept or not. A date of a
	 * reading of the day may have its time, 2026-11-01T21:40: its day is its first 10 characters.
	 *
	 * @return array<int,array{date:string,book:int}>
	 */
	public static function get_readings(): array {
		$readings = array();

		foreach ( Books::get_books() as $book ) {
			foreach ( array_filter( $book->read_dates ) as $date ) {
				$readings[] = array(
					'date' => $date,
					'book' => $book->id,
				);
			}
		}

		usort( $readings, fn( array $a, array $b ) => array( $b['date'], $b['book'] ) <=> array( $a['date'], $a['book'] ) );

		return $readings;
	}

	/**
	 * Reading stats: books read out of the books in the library, i.e. published.
	 *
	 * @return array{read:int,total:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$library = array_filter( Books::get_books(), fn( Book $book ) => Status::In_Library === $book->status );
		$read    = count( array_filter( $library, fn( Book $book ) => $book->read ) );
		$total   = count( $library );
		$percent = $total > 0 ? (int) round( $read / $total * 100 ) : 0;

		return array(
			'read'    => $read,
			'total'   => $total,
			'percent' => $percent,
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
	 * @return array{year:int,this_year:int,last_year:int,projection:int|null,to_read:int,all_read_in:int|null}
	 */
	public static function get_reading_year_to_date(): array {
		$today    = new DateTimeImmutable( 'now', wp_timezone() );
		$same_day = $today->modify( '-1 year' );
		$days     = array_map( fn( array $reading ) => substr( $reading['date'], 0, 10 ), self::get_readings() );
		$count    = fn( DateTimeImmutable $day ) => count( array_filter( $days, fn( string $read_on ) => $read_on >= $day->format( 'Y-01-01' ) && $read_on <= $day->format( 'Y-m-d' ) ) );

		$this_year    = $count( $today );
		$day_of_year  = (int) $today->format( 'z' ) + 1;
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
			'year'        => (int) $today->format( 'Y' ),
			'this_year'   => $this_year,
			'last_year'   => $count( $same_day ),
			'projection'  => $projection,
			'to_read'     => $to_read,
			'all_read_in' => $all_read_in,
		);
	}
}
