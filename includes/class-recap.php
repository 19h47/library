<?php
/**
 * The recap of a year of reading.
 *
 * @link       https://github.com/19h47/library
 * @since      9.27.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use DateTimeImmutable;

/**
 * The recap of a year, from Books::get_books(): its readings, those of the year before, its pages, its months, its forms,
 * the people read the most and those read for the first time, its original languages, its series, its ratings, its
 * records, its achievements, the books added and its days of reading. A reading is a read date of the year: a book read
 * again counts once per reading, as on the home; what counts books, the ratings and the records, takes each book once.
 *
 * @since      9.27.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-import-type Achievement from Achievements
 * @phpstan-type Named array{id:int,name:string,readings:int}
 * @phpstan-type Recap_Series array{id:int,name:string,readings:int,started:bool,finished:bool}
 * @phpstan-type Recap_Calendar array{levels:list<int>,memorable:list<int>,read:int,streak:int,best:array{day:int,pages:int}|null,dated:int}
 * @phpstan-type Recap_Data array{year:int,years:list<int>,days:int,readings:int,previous:int,rereads:int,books:list<int>,pages:array{total:int,counted:int},months:list<int>,forms:list<Named>,people:list<Named>,discoveries:list<array{id:int,name:string}>,languages:list<array{code:string,readings:int}>,series:list<Recap_Series>,rating:float|null,best:list<int>,records:array{longest:int,shortest:int,oldest:int},achievements:list<Achievement>,added:int,calendar:Recap_Calendar}
 */
class Recap {

	/**
	 * The people read the most a recap names.
	 *
	 * @var int
	 */
	private const int TOP = 5;

	/**
	 * The levels of a day read, as on a calendar of contributions: from the quarter of the days that read the fewest
	 * pages to the quarter that read the most.
	 *
	 * @var int
	 */
	private const int LEVELS = 4;

	/**
	 * The recap of `$year`.
	 *
	 * @param int $year Year.
	 * @return Recap_Data
	 */
	public static function get_recap( int $year ): array {
		$books    = Books::get_books();
		$readings = Stats::get_year_readings( $year );
		$read     = Stats::get_year_books( $year );

		return array(
			'year'         => $year,
			'years'        => array_keys( Stats::count_by_year() ),
			'days'         => Stats::count_days( $year ),
			'readings'     => count( $readings ),
			'previous'     => count( Stats::get_year_readings( $year - 1 ) ),
			'rereads'      => count( $readings ) - count( $read ),
			'books'        => array_map( fn( Book $book ) => $book->id, $read ),
			'pages'        => Stats::count_pages( $readings ),
			'months'       => array_values( Stats::count_by_month( $readings ) ),
			'forms'        => self::count_terms( $readings, fn( Book $book ) => array_combine( $book->form_ids, $book->forms ) ),
			'people'       => array_slice( self::count_terms( $readings, fn( Book $book ) => $book->get_work_authors() ), 0, self::TOP ),
			'discoveries'  => self::get_discoveries( $books, $year ),
			'languages'    => array_map(
				fn( array $language ) => array(
					'code'     => $language['name'],
					'readings' => $language['readings'],
				),
				self::count_terms( $readings, fn( Book $book ) => array_combine( $book->language_ids, array_keys( $book->languages ) ) )
			),
			'series'       => self::get_series( $books, $readings, $year ),
			'rating'       => Books::get_average_rating( $read ),
			'best'         => self::get_best( $read ),
			'records'      => self::get_records( $read ),
			'achievements' => array_values( array_filter( new Achievements()->get_achievements()['earned'], fn( array $achievement ) => str_starts_with( $achievement['date'], "$year-" ) ) ),
			'added'        => count( array_filter( $books, fn( Book $book ) => $book->is_at_hand() && str_starts_with( $book->added, "$year-" ) ) ),
			'calendar'     => self::get_calendar( $books, $read, $year ),
		);
	}

	/**
	 * The days of `$year` gone by, from January 1st, and the reading of each: a day is read while a reading ended was open,
	 * from its start to its end, or on its end alone when its start is unknown; a reading in progress, which may have been
	 * left for years, reads none until it ends, as the recap counts the readings ended. The pages of a book read from a
	 * known start to its end spread over its days, as many each.
	 *
	 * The level of a day, from 0, none read, to LEVELS, by the pages read that day against the other days read: 1 for a
	 * day read whose pages are unknown. The memorable days: the day the most pages were read, and the days a favourite of
	 * the year, of its best rating, was finished. The readings dated, those ended in the year with their start known.
	 *
	 * @param array<int,Book> $books Books.
	 * @param Book[]          $read  Books read in the year.
	 * @param int             $year  Year.
	 * @return Recap_Calendar
	 */
	private static function get_calendar( array $books, array $read, int $year ): array {
		$count  = Stats::count_days( $year );
		$first  = new DateTimeImmutable( "$year-01-01", wp_timezone() );
		$last   = $first->modify( '+' . max( 0, $count - 1 ) . ' days' )->format( 'Y-m-d' );
		$pages  = array_fill( 0, $count, 0.0 );
		$days   = array_fill( 0, $count, false );
		$dated  = 0;
		$day_of = fn( string $date ): int => (int) new DateTimeImmutable( $date, wp_timezone() )->format( 'z' );

		foreach ( $books as $book ) {
			foreach ( $book->readings as $reading ) {
				[ $start, $end ] = array_map( fn( string $date ) => substr( $date, 0, 10 ), Meta::split_reading( $reading ) );
				$is_dated        = '' !== $start;

				if ( '' === $end || Meta::IN_PROGRESS === $end ) {
					continue;
				}

				if ( $is_dated && str_starts_with( $end, "$year-" ) ) {
					++$dated;
				}

				$start = '' !== $start ? $start : $end;
				$from  = max( $start, $first->format( 'Y-m-d' ) );
				$to    = min( $end, $last );

				if ( ! $count || $from > $to ) {
					continue;
				}

				$length  = (int) new DateTimeImmutable( $start )->diff( new DateTimeImmutable( $end ) )->days + 1;
				$per_day = $is_dated && $book->pages ? $book->pages / $length : 0.0;

				foreach ( range( $day_of( $from ), $day_of( $to ) ) as $day ) {
					$days[ $day ]   = true;
					$pages[ $day ] += $per_day;
				}
			}
		}

		$paged = array_values( array_filter( $pages ) );

		sort( $paged );

		// The pages under which each level stops, from the first.
		$bounds = array_map( fn( int $level ) => $paged[ (int) floor( $level / self::LEVELS * ( count( $paged ) - 1 ) ) ] ?? 0.0, range( 1, self::LEVELS - 1 ) );
		$levels = array_map(
			fn( bool $is_read, float $day_pages ): int => match ( true ) {
				! $is_read => 0,
				! $day_pages => 1,
				default => 1 + count( array_filter( $bounds, fn( float $bound ) => $day_pages > $bound ) ),
			},
			$days,
			$pages
		);
		$best   = $paged ? (int) array_search( end( $paged ), $pages, true ) : null;
		$rating = max( array( 0.0, ...array_map( fn( Book $book ) => (float) $book->rating, $read ) ) );
		$ends   = array();

		foreach ( $rating ? array_filter( $read, fn( Book $book ) => $rating === $book->rating ) : array() as $book ) {
			foreach ( array_filter( $book->read_dates, fn( string $date ) => str_starts_with( $date, "$year-" ) && substr( $date, 0, 10 ) <= $last ) as $date ) {
				$ends[] = $day_of( substr( $date, 0, 10 ) );
			}
		}

		$memorable = array_unique( null !== $best ? array( $best, ...$ends ) : $ends );

		sort( $memorable );

		return array(
			'levels'    => $levels,
			'memorable' => $memorable,
			'read'      => count( array_filter( $days ) ),
			'streak'    => self::get_streak( $days ),
			'best'      => null !== $best ? array(
				'day'   => $best,
				'pages' => (int) round( $pages[ $best ] ),
			) : null,
			'dated'     => $dated,
		);
	}

	/**
	 * The most days read in a row.
	 *
	 * @param bool[] $days Whether each day was read, in order.
	 * @return int
	 */
	private static function get_streak( array $days ): int {
		$streak  = 0;
		$current = 0;

		foreach ( $days as $is_read ) {
			$current = $is_read ? $current + 1 : 0;
			$streak  = max( $streak, $current );
		}

		return $streak;
	}

	/**
	 * The terms of the books read, such as their forms, with their readings, the most read first, then by name: `$terms`
	 * gives the names of the terms of a book, by term ID.
	 *
	 * @param Book[]   $readings Readings.
	 * @param callable $terms    Names of the terms of a book, by term ID.
	 * @return list<Named>
	 *
	 * @phpstan-param callable(Book):array<int,string> $terms
	 */
	private static function count_terms( array $readings, callable $terms ): array {
		$counted = array();

		foreach ( $readings as $reading ) {
			foreach ( $terms( $reading ) as $id => $name ) {
				$counted[ $id ] ??= array(
					'id'       => $id,
					'name'     => $name,
					'readings' => 0,
				);
				++$counted[ $id ]['readings'];
			}
		}

		usort( $counted, fn( array $a, array $b ) => array( $b['readings'], $a['name'] ) <=> array( $a['readings'], $b['name'] ) );

		return $counted;
	}

	/**
	 * The people read for the first time in `$year`, by the name they are known by, in the order they were read: their first
	 * reading is of the year, none of an unknown date, which was before. A collective book is part of the work of none of
	 * its authors, as for the people read the most: see Book::get_work_authors().
	 *
	 * @param array<int,Book> $books Books.
	 * @param int             $year  Year.
	 * @return list<array{id:int,name:string}>
	 */
	private static function get_discoveries( array $books, int $year ): array {
		$first = array();

		foreach ( array_filter( $books, fn( Book $book ) => $book->read ) as $book ) {
			$date = $book->get_first_read_date();

			foreach ( $book->get_work_authors() as $id => $name ) {
				if ( ! isset( $first[ $id ] ) || $date < $first[ $id ]['date'] ) {
					$first[ $id ] = array(
						'id'   => $id,
						'name' => $name,
						'date' => $date,
					);
				}
			}
		}

		$discovered = array_filter( $first, fn( array $person ) => str_starts_with( $person['date'], "$year-" ) );

		usort( $discovered, fn( array $a, array $b ) => array( $a['date'], $a['name'] ) <=> array( $b['date'], $b['name'] ) );

		return array_map(
			fn( array $person ) => array(
				'id'   => $person['id'],
				'name' => $person['name'],
			),
			$discovered
		);
	}

	/**
	 * The series read in `$year`, the most read first: started when their first reading is of the year, finished in the
	 * year as the achievements finish a series: see Achievements::get_finished().
	 *
	 * @param array<int,Book> $books    Books.
	 * @param Book[]          $readings Readings of the year.
	 * @param int             $year     Year.
	 * @return list<Recap_Series>
	 */
	private static function get_series( array $books, array $readings, int $year ): array {
		$series   = self::count_terms( $readings, fn( Book $book ) => $book->series_id ? array( $book->series_id => $book->series ) : array() );
		$finished = array_keys( array_filter( new Achievements()->get_finished( 'series' ), fn( string $date ) => str_starts_with( $date, "$year-" ) ) );

		return array_map(
			function ( array $item ) use ( $books, $year, $finished ): array {
				$volumes = array_filter( $books, fn( Book $book ) => $item['id'] === $book->series_id && $book->read );
				$firsts  = array_map( fn( Book $book ) => $book->get_first_read_date(), $volumes );

				return $item + array(
					'started'  => $firsts && str_starts_with( min( $firsts ), "$year-" ),
					'finished' => in_array( $item['id'], $finished, true ),
				);
			},
			$series
		);
	}

	/**
	 * The books read with a rating, every one, the highest first, the latest read first among a rating: the recap shows
	 * as many as its screen holds.
	 *
	 * @param array<int,Book> $read Books read, in the order they were read.
	 * @return list<int>
	 */
	private static function get_best( array $read ): array {
		$rated = array_reverse( array_filter( $read, fn( Book $book ) => null !== $book->rating ) );

		usort( $rated, fn( Book $a, Book $b ) => $b->rating <=> $a->rating );

		return array_map( fn( Book $book ) => $book->id, $rated );
	}

	/**
	 * The records of the books read: the longest and the shortest, by their pages, and the oldest, by the date it first
	 * came out; 0 for none.
	 *
	 * @param array<int,Book> $read Books read.
	 * @return array{longest:int,shortest:int,oldest:int}
	 */
	private static function get_records( array $read ): array {
		$paged = array_filter( $read, fn( Book $book ) => null !== $book->pages );
		$dated = array_filter( $read, fn( Book $book ) => '' !== Books::get_date( $book ) );

		usort( $paged, fn( Book $a, Book $b ) => $a->pages <=> $b->pages );
		usort( $dated, fn( Book $a, Book $b ) => Books::get_date( $a ) <=> Books::get_date( $b ) );

		return array(
			'longest'  => $paged ? end( $paged )->id : 0,
			'shortest' => $paged ? $paged[0]->id : 0,
			'oldest'   => $dated ? $dated[0]->id : 0,
		);
	}
}
