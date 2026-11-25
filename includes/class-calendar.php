<?php
/**
 * The days of reading of a year.
 *
 * @link       https://github.com/19h47/library
 * @since      10.7.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use DateTimeImmutable;

/**
 * The days of a year gone by, from January 1st, and the readings finished on each, as the calendar shows them, and the
 * recap of a year from it: a day counts on the end of a reading finished, its start left aside, as a reading may have
 * paused. A reading abandoned or in progress finishes none.
 *
 * @since      10.7.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-type Calendar_Days array{pages:list<int>,ends:array<int,list<int>>}
 * @phpstan-type Diary_Calendar array{year:int,years:list<int>,levels:list<int>,pages:list<int>,ends:object,form_ends:object}
 */
class Calendar {

	/**
	 * The levels of a day, as on a calendar of contributions: from the quarter of the days that finished the fewest pages
	 * to the quarter that finished the most.
	 *
	 * @var int
	 */
	private const int LEVELS = 4;

	/**
	 * The days of `$year` gone by, from January 1st: the pages of the books finished on each and the books finished on
	 * each, by their IDs.
	 *
	 * @param Book[] $books Books.
	 * @param int    $year  Year.
	 * @return Calendar_Days
	 */
	public static function get_days( array $books, int $year ): array {
		$count = Stats::count_days( $year );
		$last  = new DateTimeImmutable( "$year-01-01", wp_timezone() )->modify( '+' . max( 0, $count - 1 ) . ' days' )->format( 'Y-m-d' );
		$pages = array_fill( 0, $count, 0 );
		$ends  = array();

		foreach ( $books as $book ) {
			foreach ( Readings::get_finished( $book->readings ) as $reading ) {
				$end = substr( $reading->end, 0, 10 );

				if ( ! $count || ! str_starts_with( $end, "$year-" ) || $end > $last ) {
					continue;
				}

				$day = self::get_day( $end );

				$ends[ $day ][] = $book->id;
				$pages[ $day ] += $book->pages ?? 0;
			}
		}

		ksort( $ends );

		return array(
			'pages' => array_values( $pages ),
			'ends'  => $ends,
		);
	}

	/**
	 * The level of each day, from 0, none finished, to LEVELS, by the pages finished that day against the other days: 1
	 * for a day whose pages are unknown.
	 *
	 * @param int[]            $pages The pages finished on each day.
	 * @param array<int,int[]> $ends  The books finished on each day, by the day.
	 * @return list<int>
	 *
	 * @phpstan-param list<int> $pages
	 */
	public static function get_levels( array $pages, array $ends ): array {
		$paged = array_values( array_filter( $pages ) );

		sort( $paged );

		// The pages under which each level stops, from the first.
		$bounds = array_map( fn( int $level ) => $paged[ (int) floor( $level / self::LEVELS * ( count( $paged ) - 1 ) ) ] ?? 0, range( 1, self::LEVELS - 1 ) );

		return array_map(
			fn( int $day, int $day_pages ): int => match ( true ) {
				! isset( $ends[ $day ] ) => 0,
				! $day_pages => 1,
				default => 1 + count( array_filter( $bounds, fn( int $bound ) => $day_pages > $bound ) ),
			},
			array_keys( $pages ),
			$pages
		);
	}

	/**
	 * The day of its year of `$date`, from 0 for January 1st.
	 *
	 * @param string $date Date, Y-m-d.
	 * @return int
	 */
	public static function get_day( string $date ): int {
		return (int) new DateTimeImmutable( $date, wp_timezone() )->format( 'z' );
	}

	/**
	 * The calendar of the diary for `$year`, of the books of `$form_ids`, of every form when none: the years with a
	 * reading, the level of each day gone by and the pages finished on it, and the books finished on each day, by the day
	 * from 0 for January 1st. The readings of each form ended on each day, of every form, by the day, then by term ID: its
	 * filter counts them for the year, the month or the day shown.
	 *
	 * @param int   $year     Year.
	 * @param int[] $form_ids Term IDs of forms, none for every form.
	 * @return Diary_Calendar
	 */
	public static function get_diary_calendar( int $year, array $form_ids = array() ): array {
		$books     = array_filter( Books::get_shelved(), fn( Book $book ) => ! $form_ids || array_intersect( $book->form_ids, $form_ids ) );
		$days      = self::get_days( $books, $year );
		$form_ends = array();

		foreach ( Stats::get_year_readings( $year ) as $ended ) {
			$form_ends[ self::get_day( substr( $ended->read_date, 0, 10 ) ) ][] = $ended;
		}

		ksort( $form_ends );
		$form_ends = array_map( fn( array $readings ) => (object) Stats::count_by_form( $readings ), $form_ends );

		return array(
			'year'      => $year,
			'years'     => array_keys( Stats::count_by_year() ),
			'levels'    => self::get_levels( $days['pages'], $days['ends'] ),
			'pages'     => $days['pages'],
			'ends'      => (object) $days['ends'],
			'form_ends' => (object) $form_ends,
		);
	}
}
