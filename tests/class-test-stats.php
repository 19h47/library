<?php
/**
 * Tests of the stats of the readings.
 *
 * @package Library
 */

namespace Library\Tests;

use DateTimeImmutable;
use Library\Books;
use Library\Stats;

/**
 * The pages of books, estimated for a book without its number, and the year the books to read would all be read in, at
 * the pace of pages of this year.
 */
class Test_Stats extends TestCase {

	/**
	 * A book without its number of pages counts the average of the books of its form, else of all the books with their
	 * number; a book read again counts once per reading.
	 *
	 * @return void
	 */
	public function test_estimate_pages(): void {
		$novel = $this->get_form( 'Novel' );
		$comic = $this->get_form( 'Comic' );

		$this->add_book(
			array(
				'form' => $novel,
				'meta' => array( 'pages' => 200 ),
			)
		);
		$this->add_book(
			array(
				'form' => $novel,
				'meta' => array( 'pages' => 400 ),
			)
		);
		$short    = $this->add_book(
			array(
				'form' => $comic,
				'meta' => array( 'pages' => 60 ),
			)
		);
		$no_pages = $this->add_book( array( 'form' => $novel ) );
		$no_form  = $this->add_book();
		$essay    = $this->add_book( array( 'form' => $this->get_form( 'Essay' ) ) );
		$books    = Books::get_shelved();

		$this->assertSame( 60.0, Stats::estimate_pages( array( $books[ $short ] ) ) );
		$this->assertSame( 300.0, Stats::estimate_pages( array( $books[ $no_pages ] ) ) );
		$this->assertSame( 220.0, Stats::estimate_pages( array( $books[ $no_form ] ) ) );
		$this->assertSame( 220.0, Stats::estimate_pages( array( $books[ $essay ] ) ) );
		$this->assertSame( 120.0, Stats::estimate_pages( array( $books[ $short ], $books[ $short ] ) ) );
	}

	/**
	 * Without any number of pages, a book counts one page: the pace is that of books.
	 *
	 * @return void
	 */
	public function test_pages_are_books_without_any_number(): void {
		$this->add_book();
		$this->add_book();

		$this->assertSame( 2.0, Stats::estimate_pages( Books::get_shelved() ) );
	}

	/**
	 * The books to read are all read in the year the pages of the pile take at the pace of pages of this year: two books
	 * of 100 pages read, a book of 400 pages to read takes twice the days of the year so far, where a pace of books would
	 * have half of them.
	 *
	 * @return void
	 */
	public function test_all_read_in_at_the_pace_of_pages(): void {
		$today       = new DateTimeImmutable( 'now', wp_timezone() );
		$day_of_year = (int) $today->format( 'z' ) + 1;

		if ( $day_of_year < 31 ) {
			$this->markTestSkipped( 'A month of readings is the least to project from.' );
		}

		$this->add_reads( array( $today->format( 'Y-01-01' ), $today->format( 'Y-01-02' ) ), array( 'meta' => array( 'pages' => 100 ) ) );
		$this->add_book( array( 'meta' => array( 'pages' => 400 ) ) );

		$year_to_date = Stats::get_reading_year_to_date();
		$days_left    = 2 * $day_of_year;

		$this->assertSame( 2, $year_to_date['read_count'] );
		$this->assertSame( 1, $year_to_date['to_read_count'] );
		$this->assertSame( (int) $today->modify( "+$days_left days" )->format( 'Y' ), $year_to_date['all_read_in'] );
	}
}
