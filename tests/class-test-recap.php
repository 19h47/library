<?php
/**
 * Tests of the recap of a year.
 *
 * @package Library
 */

namespace Library\Tests;

use DateTimeImmutable;
use Library\Recap;
use Library\Status;

/**
 * The recap of a year: its readings, against the year before, its pages, its months, its terms, the people read for
 * the first time, its series, its ratings, its records and the books added.
 */
class Test_Recap extends TestCase {

	/**
	 * A reading is a read date of the year: a book read again counts once per reading, its books once each, in the order
	 * they were read; the readings of the year before are counted, and each month has its own.
	 *
	 * @return void
	 */
	public function test_readings(): void {
		$first  = $this->add_read( '2025-03-02' );
		$again  = $this->add_book( array( 'meta' => array( 'library_reading' => array( '/2025-01-10', '/2025-03-20' ) ) ) );
		$before = $this->add_read( '2024-12-31' );
		$this->add_read( '2024-06-01' );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame( 3, $recap['reading_count'] );
		$this->assertSame( 2, $recap['previous_reading_count'] );
		$this->assertSame( 1, $recap['reread_count'] );
		$this->assertSame( array( $again, $first ), $recap['books'] );
		$this->assertSame( array( 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0 ), $recap['months'] );
		$this->assertSame( array( 2024, 2025 ), $recap['years'] );
		$this->assertSame( 365, $recap['day_count'] );
		$this->assertNotContains( $before, $recap['books'] );
	}

	/**
	 * The pages count the readings of a book with its number of pages, and say how many those are; the records are the
	 * longest and the shortest of them, and the oldest book by the date it first came out.
	 *
	 * @return void
	 */
	public function test_pages_and_records(): void {
		$long  = $this->add_read( '2025-02-01', array( 'meta' => array( 'library_pages' => 600 ) ) );
		$short = $this->add_read(
			'2025-04-01',
			array(
				'meta' => array(
					'library_pages'          => 40,
					'library_published_date' => '1932',
				),
			)
		);
		$this->add_read( '2025-05-01', array( 'meta' => array( 'library_published_date' => '1990-04' ) ) );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame(
			array(
				'total'   => 640,
				'counted' => 2,
			),
			$recap['pages']
		);
		$this->assertSame(
			array(
				'longest'  => $long,
				'shortest' => $short,
				'oldest'   => $short,
			),
			$recap['records']
		);
	}

	/**
	 * The average rating takes each book once; every book rated shows, the highest first, the latest read among a rating.
	 *
	 * @return void
	 */
	public function test_ratings(): void {
		$good   = $this->add_read( '2025-01-01', array( 'meta' => array( 'library_rating' => 8 ) ) );
		$better = $this->add_read( '2025-02-01', array( 'meta' => array( 'library_rating' => 8 ) ) );
		$best   = $this->add_read( '2025-03-01', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$lower  = $this->add_reads( array( '2025-04-02', '2025-04-03', '2025-04-04', '2025-04-05' ), array( 'meta' => array( 'library_rating' => 5 ) ) );
		$this->add_read( '2025-04-01' );

		$recap = Recap::get_recap( 2025 );

		$this->assertEqualsWithDelta( 6.6, $recap['rating'], 0.01 );
		$this->assertSame( array( $best, $better, $good, ...array_reverse( $lower ) ), $recap['best'] );
	}

	/**
	 * The forms, the people and the original languages count their readings, the most read first, then by name.
	 *
	 * @return void
	 */
	public function test_terms(): void {
		$novel = $this->get_form( 'Novel' );
		$comic = $this->get_form( 'Comic' );

		$this->add_reads(
			array( '2025-01-01', '2025-02-01' ),
			$this->by( 'Ann Writer' ) + array(
				'form'      => $novel,
				'languages' => array( $this->get_language( 'it', 'Italian' ) ),
			)
		);
		$this->add_read( '2025-03-01', $this->by( 'Bob Drawer' ) + array( 'form' => $comic ) );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame( array( 'Novel', 'Comic' ), array_column( $recap['forms'], 'name' ) );
		$this->assertSame( array( 2, 1 ), array_column( $recap['forms'], 'reading_count' ) );
		$this->assertSame( array( 'Ann Writer', 'Bob Drawer' ), array_column( $recap['people'], 'name' ) );
		$this->assertSame(
			array(
				array(
					'code'          => 'it',
					'reading_count' => 2,
				),
			),
			$recap['languages']
		);
	}

	/**
	 * A person is read for the first time when their first reading is of the year; one read before, or on an unknown
	 * date, which was before, is not.
	 *
	 * @return void
	 */
	public function test_discoveries(): void {
		$this->add_read( '2024-05-01', $this->by( 'Known Before' ) );
		$this->add_read( '2025-05-01', $this->by( 'Known Before' ) );
		$this->add_read( '', $this->by( 'Known Undated' ) );
		$this->add_read( '2025-06-01', $this->by( 'Known Undated' ) );
		$this->add_read( '2025-07-01', $this->by( 'New Later' ) );
		$this->add_read( '2025-02-01', $this->by( 'New Sooner' ) );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame( array( 'New Sooner', 'New Later' ), array_column( $recap['discoveries'], 'name' ) );
	}

	/**
	 * A series is started when its first reading is of the year, finished when every book of it is read, the last one
	 * first read in the year, as the achievements finish it: a book to buy lacks to a series, and a series of one book is
	 * never finished.
	 *
	 * @return void
	 */
	public function test_series(): void {
		$this->add_read( '2025-01-01', array( 'series' => 'Started And Finished' ) );
		$this->add_read( '2025-02-01', array( 'series' => 'Started And Finished' ) );
		$this->add_read( '2024-01-01', array( 'series' => 'Finished' ) );
		$this->add_read( '2025-03-01', array( 'series' => 'Finished' ) );
		$this->add_read( '2025-04-01', array( 'series' => 'Started' ) );
		$this->add_book(
			array(
				'series' => 'Started',
				'status' => Status::To_Buy->value,
			)
		);
		$this->add_read( '2025-05-01', array( 'series' => 'Alone' ) );

		$series = array_column( Recap::get_recap( 2025 )['series'], null, 'name' );

		$this->assertTrue( $series['Started And Finished']['started'] );
		$this->assertTrue( $series['Started And Finished']['finished'] );
		$this->assertSame( 2, $series['Started And Finished']['reading_count'] );
		$this->assertFalse( $series['Finished']['started'] );
		$this->assertTrue( $series['Finished']['finished'] );
		$this->assertTrue( $series['Started']['started'] );
		$this->assertFalse( $series['Started']['finished'] );
		$this->assertTrue( $series['Alone']['started'] );
		$this->assertFalse( $series['Alone']['finished'] );
	}

	/**
	 * A collective book, an anthology, is part of the work of none of its authors: they are neither the people read the
	 * most nor read for the first time.
	 *
	 * @return void
	 */
	public function test_a_collective_book_counts_for_no_author(): void {
		$this->add_read( '2025-01-01', $this->by( 'Ann Writer' ) );
		$this->add_read( '2025-02-01', $this->by( 'Contributor' ) + array( 'meta' => array( 'library_collective' => true ) ) );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame( array( 'Ann Writer' ), array_column( $recap['people'], 'name' ) );
		$this->assertSame( array( 'Ann Writer' ), array_column( $recap['discoveries'], 'name' ) );
	}

	/**
	 * The books added in the year count, but those to buy.
	 *
	 * @return void
	 */
	public function test_books_added(): void {
		$this->add_book( array(), '2025-03-01 10:00:00' );
		$this->add_book( array( 'status' => Status::Not_Kept->value ), '2025-04-01 10:00:00' );
		$this->add_book( array( 'status' => Status::To_Buy->value ), '2025-05-01 10:00:00' );
		$this->add_book( array(), '2024-12-31 10:00:00' );

		$this->assertSame( 2, Recap::get_recap( 2025 )['added_count'] );
	}

	/**
	 * A day is read while a reading is open, on its end alone when its start is unknown; the pages of a book read from a
	 * known start spread over its days, which give the levels, the most days in a row and the biggest day. The memorable
	 * days are the biggest one and the days a favourite was finished; the readings dated have their start known.
	 *
	 * @return void
	 */
	public function test_calendar(): void {
		$this->add_book( $this->read_from( '2025-03-01/2025-03-03', 300, 7 ) );
		$this->add_book( $this->read_from( '2025-03-03/2025-03-03', 50, 9 ) );
		$this->add_book( $this->read_from( '/2025-03-10', 200, 9 ) );
		$this->add_book( $this->read_from( '2024-12-30/2025-01-02', 40 ) );

		$calendar = Recap::get_recap( 2025 )['calendar'];

		$this->assertCount( 365, $calendar['levels'] );
		// January 1st and 2nd, March 1st to 3rd and 10th: 10, 10, 100, 100, 150 pages and an unknown number.
		$this->assertSame(
			array(
				0  => 1,
				1  => 1,
				59 => 2,
				60 => 2,
				61 => 4,
				68 => 1,
			),
			array_filter( $calendar['levels'] )
		);
		$this->assertSame( 6, $calendar['read_day_count'] );
		$this->assertSame( 3, $calendar['streak'] );
		$this->assertSame(
			array(
				'day'   => 61,
				'pages' => 150,
			),
			$calendar['best']
		);
		$this->assertSame( array( 61, 68 ), $calendar['memorable'] );
		$this->assertSame( 3, $calendar['dated_reading_count'] );
	}

	/**
	 * A reading in progress, which may have been left for years, reads no day until it ends; no day after today is gone by.
	 *
	 * @return void
	 */
	public function test_calendar_leaves_out_the_reading_in_progress(): void {
		$today = new DateTimeImmutable( 'now', wp_timezone() );
		$year  = (int) $today->format( 'Y' );

		$this->add_read( "$year-01-01" );
		$this->add_book( $this->read_from( '2022-09-04/..', 300 ) );

		$calendar = Recap::get_recap( $year )['calendar'];

		$this->assertCount( (int) $today->format( 'z' ) + 1, $calendar['levels'] );
		$this->assertSame( 1, $calendar['read_day_count'] );
		$this->assertSame( 0, $calendar['dated_reading_count'] );
		$this->assertNull( $calendar['best'] );
	}

	/**
	 * A book read once, from its start to its end, with its pages and its rating.
	 *
	 * @param string     $reading Reading, start/end.
	 * @param int        $pages   Number of pages.
	 * @param float|null $rating  Rating, none when null.
	 * @return array<string,mixed>
	 */
	private function read_from( string $reading, int $pages, ?float $rating = null ): array {
		return array(
			'meta' => array_filter(
				array(
					'library_reading' => array( $reading ),
					'library_pages'   => $pages,
					'library_rating'  => $rating,
				)
			),
		);
	}
}
