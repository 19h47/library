<?php
/**
 * Tests of the recap of a year.
 *
 * @package Library
 */

namespace Library\Tests;

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
		$again  = $this->add_book( array( 'meta' => array( 'library_read_date' => array( '2025-01-10', '2025-03-20' ) ) ) );
		$before = $this->add_read( '2024-12-31' );
		$this->add_read( '2024-06-01' );

		$recap = Recap::get_recap( 2025 );

		$this->assertSame( 3, $recap['readings'] );
		$this->assertSame( 2, $recap['previous'] );
		$this->assertSame( 1, $recap['rereads'] );
		$this->assertSame( array( $again, $first ), $recap['books'] );
		$this->assertSame( array( 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0 ), $recap['months'] );
		$this->assertSame( array( 2024, 2025 ), $recap['years'] );
		$this->assertSame( 365, $recap['days'] );
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
	 * The average rating takes each book once, the books rated the highest first, the latest read among a rating.
	 *
	 * @return void
	 */
	public function test_ratings(): void {
		$good   = $this->add_read( '2025-01-01', array( 'meta' => array( 'library_rating' => 8 ) ) );
		$better = $this->add_read( '2025-02-01', array( 'meta' => array( 'library_rating' => 8 ) ) );
		$best   = $this->add_read( '2025-03-01', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$this->add_read( '2025-04-01' );

		$recap = Recap::get_recap( 2025 );

		$this->assertEqualsWithDelta( 8.7, $recap['rating'], 0.01 );
		$this->assertSame( array( $best, $better, $good ), $recap['best'] );
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
		$this->assertSame( array( 2, 1 ), array_column( $recap['forms'], 'readings' ) );
		$this->assertSame( array( 'Ann Writer', 'Bob Drawer' ), array_column( $recap['people'], 'name' ) );
		$this->assertSame(
			array(
				array(
					'code'     => 'it',
					'readings' => 2,
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
	 * first read in the year; a book to buy lacks to a series.
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

		$series = array_column( Recap::get_recap( 2025 )['series'], null, 'name' );

		$this->assertTrue( $series['Started And Finished']['started'] );
		$this->assertTrue( $series['Started And Finished']['finished'] );
		$this->assertSame( 2, $series['Started And Finished']['readings'] );
		$this->assertFalse( $series['Finished']['started'] );
		$this->assertTrue( $series['Finished']['finished'] );
		$this->assertTrue( $series['Started']['started'] );
		$this->assertFalse( $series['Started']['finished'] );
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

		$this->assertSame( 2, Recap::get_recap( 2025 )['added'] );
	}
}
