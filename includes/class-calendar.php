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
 * The days of a year gone by, from January 1st, and the reading of each, as the calendar shows them, and the recap of a
 * year from it: a day is read while a reading ended was open, from its start to its end, or on its end alone when its
 * start is unknown; a reading in progress, which may have been left for years, reads none until it ends. The pages of a
 * book read from a known start to its end spread over its days, as many each.
 *
 * @since      10.7.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-type Calendar_Days array{read:list<bool>,pages:list<float>,ends:array<int,list<int>>,reads:array<int,list<int>>}
 * @phpstan-type Diary_Calendar array{year:int,years:list<int>,levels:list<int>,pages:list<int>,ends:object,reads:object,form_ends:object}
 */
class Calendar {

	/**
	 * The levels of a day read, as on a calendar of contributions: from the quarter of the days that read the fewest
	 * pages to the quarter that read the most.
	 *
	 * @var int
	 */
	private const int LEVELS = 4;

	/**
	 * The days of `$year` gone by, from January 1st: whether each was read, the pages read on each, the books whose reading
	 * was finished on each and the books read on each, by their IDs. A reading abandoned reads its days, of pages unknown,
	 * and finishes none.
	 *
	 * @param Book[] $books Books.
	 * @param int    $year  Year.
	 * @return Calendar_Days
	 */
	public static function get_days( array $books, int $year ): array {
		$count = Stats::count_days( $year );
		$first = new DateTimeImmutable( "$year-01-01", wp_timezone() );
		$last  = $first->modify( '+' . max( 0, $count - 1 ) . ' days' )->format( 'Y-m-d' );
		$pages = array_fill( 0, $count, 0.0 );
		$read  = array_fill( 0, $count, false );
		$ends  = array();
		$reads = array();

		foreach ( $books as $book ) {
			foreach ( Readings::get_ended( $book->readings ) as $reading ) {
				$start    = substr( $reading->start, 0, 10 );
				$end      = substr( $reading->end, 0, 10 );
				$is_dated = '' !== $start;

				if ( '' === $end ) {
					continue;
				}

				if ( $count && $reading->is_finished() && str_starts_with( $end, "$year-" ) && $end <= $last ) {
					$ends[ self::get_day( $end ) ][] = $book->id;
				}

				$start = '' !== $start ? $start : $end;
				$from  = max( $start, $first->format( 'Y-m-d' ) );
				$to    = min( $end, $last );

				if ( ! $count || $from > $to ) {
					continue;
				}

				$length  = (int) new DateTimeImmutable( $start )->diff( new DateTimeImmutable( $end ) )->days + 1;
				$per_day = $is_dated && $book->pages && $reading->is_finished() ? $book->pages / $length : 0.0;

				foreach ( range( self::get_day( $from ), self::get_day( $to ) ) as $day ) {
					$read[ $day ]    = true;
					$pages[ $day ]  += $per_day;
					$reads[ $day ][] = $book->id;
				}
			}
		}

		ksort( $ends );
		ksort( $reads );

		return array(
			'read'  => array_values( $read ),
			'pages' => array_values( $pages ),
			'ends'  => $ends,
			'reads' => $reads,
		);
	}

	/**
	 * The level of each day, from 0, none read, to LEVELS, by the pages read that day against the other days read: 1 for
	 * a day read whose pages are unknown.
	 *
	 * @param bool[]  $read  Whether each day was read.
	 * @param float[] $pages The pages read on each day.
	 * @return list<int>
	 *
	 * @phpstan-param list<bool> $read
	 * @phpstan-param list<float> $pages
	 */
	public static function get_levels( array $read, array $pages ): array {
		$paged = array_values( array_filter( $pages ) );

		sort( $paged );

		// The pages under which each level stops, from the first.
		$bounds = array_map( fn( int $level ) => $paged[ (int) floor( $level / self::LEVELS * ( count( $paged ) - 1 ) ) ] ?? 0.0, range( 1, self::LEVELS - 1 ) );

		return array_map(
			fn( bool $is_read, float $day_pages ): int => match ( true ) {
				! $is_read => 0,
				! $day_pages => 1,
				default => 1 + count( array_filter( $bounds, fn( float $bound ) => $day_pages > $bound ) ),
			},
			$read,
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
	 * reading, the level of each day gone by and the pages read on it, the books whose reading ended on each day, and the
	 * books read on each day, by the day from 0 for January 1st. A reading in progress, which counts for no level until it
	 * ends and may have been left long ago, is read today alone: the day of today lists what is being read. The readings of
	 * each form ended on each day, of every form, by the day, then by term ID: its filter counts them for the year, the
	 * month or the day shown.
	 *
	 * @param int   $year     Year.
	 * @param int[] $form_ids Term IDs of forms, none for every form.
	 * @return Diary_Calendar
	 */
	public static function get_diary_calendar( int $year, array $form_ids = array() ): array {
		$books = array_filter( Books::get_shelved(), fn( Book $book ) => ! $form_ids || array_intersect( $book->form_ids, $form_ids ) );
		$days  = self::get_days( $books, $year );
		$reads = $days['reads'];
		$today = current_datetime()->format( 'Y-m-d' );

		// The books being read, today alone: a reading in progress may have been left long ago.
		if ( str_starts_with( $today, "$year-" ) ) {
			foreach ( $books as $book ) {
				if ( null !== Readings::get_in_progress( $book->readings ) ) {
					$reads[ self::get_day( $today ) ][] = $book->id;
				}
			}
		}

		ksort( $reads );

		$form_ends = array();

		foreach ( Stats::get_year_readings( $year ) as $ended ) {
			$form_ends[ self::get_day( substr( $ended->read_date, 0, 10 ) ) ][] = $ended;
		}

		ksort( $form_ends );
		$form_ends = array_map( fn( array $readings ) => (object) Stats::count_by_form( $readings ), $form_ends );

		return array(
			'year'      => $year,
			'years'     => array_keys( Stats::count_by_year() ),
			'levels'    => self::get_levels( $days['read'], $days['pages'] ),
			'pages'     => array_map( fn( float $pages ) => (int) round( $pages ), $days['pages'] ),
			'ends'      => (object) $days['ends'],
			'reads'     => (object) $reads,
			'form_ends' => (object) $form_ends,
		);
	}
}
