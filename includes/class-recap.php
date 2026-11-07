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
 * records, its achievements and the books added. A reading is a read date of the year: a book read again counts once per
 * reading, as on the home; what counts books, the ratings and the records, takes each book once.
 *
 * @since      9.27.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-import-type Achievement from Achievements
 * @phpstan-type Named array{id:int,name:string,readings:int}
 * @phpstan-type Recap_Series array{id:int,name:string,readings:int,started:bool,finished:bool}
 * @phpstan-type Recap_Data array{year:int,years:list<int>,days:int,readings:int,previous:int,rereads:int,books:list<int>,pages:array{total:int,counted:int},months:list<int>,forms:list<Named>,people:list<Named>,discoveries:list<array{id:int,name:string}>,languages:list<array{code:string,readings:int}>,series:list<Recap_Series>,rating:float|null,best:list<int>,records:array{longest:int,shortest:int,oldest:int},achievements:list<Achievement>,added:int}
 */
class Recap {

	/**
	 * The people read the most a recap names, and the books rated the highest it shows.
	 *
	 * @var int
	 */
	private const int TOP = 5;

	/**
	 * The recap of `$year`.
	 *
	 * @param int $year Year.
	 * @return Recap_Data
	 */
	public static function get_recap( int $year ): array {
		$books    = Books::get_books();
		$readings = self::get_readings( $books, $year );
		$read     = array_map( fn( int $id ) => $books[ $id ], array_values( array_unique( array_column( $readings, 'book' ) ) ) );
		$counted  = array_filter( $readings, fn( array $reading ) => null !== $books[ $reading['book'] ]->pages );

		return array(
			'year'         => $year,
			'years'        => self::get_years( $books ),
			'days'         => self::get_days( $year ),
			'readings'     => count( $readings ),
			'previous'     => count( self::get_readings( $books, $year - 1 ) ),
			'rereads'      => count( $readings ) - count( $read ),
			'books'        => array_map( fn( Book $book ) => $book->id, $read ),
			'pages'        => array(
				'total'   => (int) array_sum( array_map( fn( array $reading ) => $books[ $reading['book'] ]->pages, $counted ) ),
				'counted' => count( $counted ),
			),
			'months'       => self::get_months( $readings ),
			'forms'        => self::count_terms( $readings, $books, fn( Book $book ) => array_combine( $book->form_ids, $book->forms ) ),
			'people'       => array_slice( self::count_terms( $readings, $books, fn( Book $book ) => array_combine( $book->author_ids, $book->authors ) ), 0, self::TOP ),
			'discoveries'  => self::get_discoveries( $books, $year ),
			'languages'    => array_map(
				fn( array $language ) => array(
					'code'     => $language['name'],
					'readings' => $language['readings'],
				),
				self::count_terms( $readings, $books, fn( Book $book ) => array_combine( $book->language_ids, array_keys( $book->languages ) ) )
			),
			'series'       => self::get_series( $books, $readings, $year ),
			'rating'       => self::get_average_rating( $read ),
			'best'         => self::get_best( $read ),
			'records'      => self::get_records( $read ),
			'achievements' => array_values( array_filter( new Achievements()->get_achievements()['earned'], fn( array $achievement ) => str_starts_with( $achievement['date'], "$year-" ) ) ),
			'added'        => count( array_filter( $books, fn( Book $book ) => Status::To_Buy !== $book->status && str_starts_with( $book->added, "$year-" ) ) ),
		);
	}

	/**
	 * The readings of `$year`, in the order they were read: a read date and its book.
	 *
	 * @param array<int,Book> $books Books.
	 * @param int             $year  Year.
	 * @return list<array{date:string,book:int}>
	 */
	private static function get_readings( array $books, int $year ): array {
		$readings = array();

		foreach ( $books as $book ) {
			foreach ( $book->read_dates as $date ) {
				if ( str_starts_with( $date, "$year-" ) ) {
					$readings[] = array(
						'date' => $date,
						'book' => $book->id,
					);
				}
			}
		}

		usort( $readings, fn( array $a, array $b ) => array( $a['date'], $a['book'] ) <=> array( $b['date'], $b['book'] ) );

		return $readings;
	}

	/**
	 * The years with a reading, the earliest first, for a recap to lead to the one before and the one after.
	 *
	 * @param array<int,Book> $books Books.
	 * @return list<int>
	 */
	private static function get_years( array $books ): array {
		$years = array();

		foreach ( $books as $book ) {
			foreach ( array_filter( $book->read_dates ) as $date ) {
				$years[ (int) substr( $date, 0, 4 ) ] = true;
			}
		}

		ksort( $years );

		return array_keys( $years );
	}

	/**
	 * The days of `$year` gone by: all of them for a year gone, those until today for this one, none for one to come.
	 *
	 * @param int $year Year.
	 * @return int
	 */
	private static function get_days( int $year ): int {
		$today   = new DateTimeImmutable( 'now', wp_timezone() );
		$current = (int) $today->format( 'Y' );

		if ( $year > $current ) {
			return 0;
		}

		return $year < $current ? (int) new DateTimeImmutable( "$year-12-31" )->format( 'z' ) + 1 : (int) $today->format( 'z' ) + 1;
	}

	/**
	 * The readings of each month, from January.
	 *
	 * @param list<array{date:string,book:int}> $readings Readings.
	 * @return list<int>
	 */
	private static function get_months( array $readings ): array {
		$counts = array_count_values( array_map( fn( array $reading ) => (int) substr( $reading['date'], 5, 2 ), $readings ) );

		return array_map( fn( int $month ) => $counts[ $month ] ?? 0, range( 1, 12 ) );
	}

	/**
	 * The terms of the books read, such as their forms, with their readings, the most read first, then by name: `$terms`
	 * gives the names of the terms of a book, by term ID.
	 *
	 * @param list<array{date:string,book:int}> $readings Readings.
	 * @param array<int,Book>                   $books    Books.
	 * @param callable                          $terms    Names of the terms of a book, by term ID.
	 * @return list<Named>
	 *
	 * @phpstan-param callable(Book):array<int,string> $terms
	 */
	private static function count_terms( array $readings, array $books, callable $terms ): array {
		$counted = array();

		foreach ( $readings as $reading ) {
			foreach ( $terms( $books[ $reading['book'] ] ) as $id => $name ) {
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
	 * reading is of the year, none of an unknown date, which was before.
	 *
	 * @param array<int,Book> $books Books.
	 * @param int             $year  Year.
	 * @return list<array{id:int,name:string}>
	 */
	private static function get_discoveries( array $books, int $year ): array {
		$first = array();

		foreach ( array_filter( $books, fn( Book $book ) => $book->read ) as $book ) {
			$date = self::get_first_reading( $book );

			foreach ( array_combine( $book->author_ids, $book->authors ) as $id => $name ) {
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
	 * The series read in `$year`, the most read first: started when their first reading is of the year, finished when
	 * every book of theirs is read, the last one first read in the year. The books to buy count, which a series lacks.
	 *
	 * @param array<int,Book>                   $books    Books.
	 * @param list<array{date:string,book:int}> $readings Readings of the year.
	 * @param int                               $year     Year.
	 * @return list<Recap_Series>
	 */
	private static function get_series( array $books, array $readings, int $year ): array {
		$series = self::count_terms( $readings, $books, fn( Book $book ) => $book->series_id ? array( $book->series_id => $book->series ) : array() );

		return array_map(
			function ( array $item ) use ( $books, $year ): array {
				$volumes = array_filter( $books, fn( Book $book ) => $item['id'] === $book->series_id );
				$firsts  = array_map( array( self::class, 'get_first_reading' ), array_filter( $volumes, fn( Book $book ) => $book->read ) );

				return $item + array(
					'started'  => $firsts && str_starts_with( min( $firsts ), "$year-" ),
					'finished' => $firsts && count( $firsts ) === count( $volumes ) && str_starts_with( max( $firsts ), "$year-" ),
				);
			},
			$series
		);
	}

	/**
	 * The first reading of a book read: its first read date, empty when unknown, which comes first.
	 *
	 * @param Book $book Book read.
	 * @return string
	 */
	private static function get_first_reading( Book $book ): string {
		return $book->read_dates[0];
	}

	/**
	 * The average rating of the books read, out of 10, none without a rating.
	 *
	 * @param array<int,Book> $read Books read.
	 * @return float|null
	 */
	private static function get_average_rating( array $read ): ?float {
		$ratings = array_filter( array_map( fn( Book $book ) => $book->rating, $read ), fn( ?float $rating ) => null !== $rating );

		return $ratings ? round( array_sum( $ratings ) / count( $ratings ), 1 ) : null;
	}

	/**
	 * The books read rated the highest, the latest read first among a rating.
	 *
	 * @param array<int,Book> $read Books read, in the order they were read.
	 * @return list<int>
	 */
	private static function get_best( array $read ): array {
		$rated = array_reverse( array_filter( $read, fn( Book $book ) => null !== $book->rating ) );

		usort( $rated, fn( Book $a, Book $b ) => $b->rating <=> $a->rating );

		return array_map( fn( Book $book ) => $book->id, array_slice( $rated, 0, self::TOP ) );
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
