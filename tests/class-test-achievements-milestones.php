<?php
/**
 * Tests of the milestones: books and pages read, record year and month, a book read again.
 *
 * @package Library
 */

namespace Library\Tests;

/**
 * Books read count once each, from 100; pages count at each reading, from 10,000; the record year and month count
 * readings; a book read again is at the level of the most times one was.
 */
class Test_Achievements_Milestones extends TestCase {

	/**
	 * A hundred books read earn it, dated by the hundredth, and the next milestone is close.
	 *
	 * @return void
	 */
	public function test_a_hundred_books_read(): void {
		$this->add_read_books( 100, '2024-01-01' );

		$read = $this->get_earned()['read'];

		$this->assertCount( 1, $read );
		$this->assertSame( '100 books read', $read[0]['title'] );
		$this->assertSame( '', $read[0]['subject'] );
		$this->assertSame( 'Book 100', $read[0]['detail'] );
		$this->assertSame( '2024-04-09', $read[0]['date'] );

		$close = $this->get_close()['read'];

		$this->assertSame( '250 books read', $close[0]['title'] );
		$this->assertSame( 100, $close[0]['done'] );
		$this->assertSame( 250, $close[0]['total'] );
	}

	/**
	 * Ninety-nine books read earn nothing, however many are to read or to buy: a hundred are close.
	 *
	 * @return void
	 */
	public function test_ninety_nine_books_read(): void {
		$this->add_read_books( 99, '2024-01-01' );
		$this->add_book( array() );
		$this->add_book( array( 'status' => 'library-to-buy' ) );

		$this->assertArrayNotHasKey( 'read', $this->get_earned() );

		$close = $this->get_close()['read'];

		$this->assertCount( 1, $close );
		$this->assertSame( '100 books read', $close[0]['title'] );
		$this->assertSame( '', $close[0]['detail'] );
		$this->assertSame( '', $close[0]['date'] );
		$this->assertSame( 99, $close[0]['done'] );
		$this->assertSame( 100, $close[0]['total'] );
	}

	/**
	 * A book read again counts once: ninety-nine books read a hundred times earn nothing.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_counts_once(): void {
		$this->add_read_books( 98, '2024-01-01' );
		$this->add_book( array( 'meta' => array( 'reading' => array( '/2023-01-01', '/2024-06-01' ) ) ) );

		$this->assertArrayNotHasKey( 'read', $this->get_earned() );
		$this->assertSame( 99, $this->get_close()['read'][0]['done'] );
	}

	/**
	 * A book not kept is read: it counts.
	 *
	 * @return void
	 */
	public function test_a_book_not_kept_counts(): void {
		$this->add_read_books( 99, '2024-01-01' );
		$this->add_book(
			array(
				'title'  => 'Given away',
				'status' => 'library-not-kept',
				'meta'   => array( 'reading' => array( '/2024-12-01' ) ),
			)
		);

		$read = $this->get_earned()['read'];

		$this->assertSame( 'Given away', $read[0]['detail'] );
		$this->assertSame( '2024-12-01', $read[0]['date'] );
	}

	/**
	 * Books read on an unknown date come first: the hundredth is the latest one read.
	 *
	 * @return void
	 */
	public function test_books_read_on_an_unknown_date_come_first(): void {
		$this->add_read_books( 99, '2024-01-01' );
		$this->add_book(
			array(
				'title' => 'Read once upon a time',
				'meta'  => array( 'reading' => array( '/' ) ),
			)
		);

		$read = $this->get_earned()['read'];

		$this->assertSame( 'Book 099', $read[0]['detail'] );
		$this->assertSame( '2024-04-08', $read[0]['date'] );
	}

	/**
	 * Only the highest milestone reached is earned, dated by the book that reached it.
	 *
	 * @return void
	 */
	public function test_only_the_highest_milestone_read_is_earned(): void {
		$this->add_read_books( 250, '2024-01-01' );

		$read = $this->get_earned()['read'];

		$this->assertCount( 1, $read );
		$this->assertSame( '250 books read', $read[0]['title'] );
		$this->assertSame( 'Book 250', $read[0]['detail'] );
		$this->assertSame( '2024-09-06', $read[0]['date'] );
		$this->assertSame( 500, $this->get_close()['read'][0]['total'] );
	}

	/**
	 * Ten thousand pages read earn it, dated by the reading that reached them, and the next milestone is close.
	 *
	 * @return void
	 */
	public function test_ten_thousand_pages_read(): void {
		$this->add_book( $this->get_reading( 'The Count of Monte Cristo', 6000, array( '2024-01-01' ) ) );
		$this->add_book( $this->get_reading( 'War and Peace', 4000, array( '2024-02-01' ) ) );

		$pages = $this->get_earned()['pages'];

		$this->assertCount( 1, $pages );
		$this->assertSame( '10,000 pages read', $pages[0]['title'] );
		$this->assertSame( 'War and Peace', $pages[0]['detail'] );
		$this->assertSame( '2024-02-01', $pages[0]['date'] );

		$close = $this->get_close()['pages'];

		$this->assertSame( '25,000 pages read', $close[0]['title'] );
		$this->assertSame( 10000, $close[0]['done'] );
		$this->assertSame( 25000, $close[0]['total'] );
	}

	/**
	 * A page short of ten thousand earns nothing: they are close.
	 *
	 * @return void
	 */
	public function test_a_page_short_of_ten_thousand(): void {
		$this->add_book( $this->get_reading( 'The Count of Monte Cristo', 6000, array( '2024-01-01' ) ) );
		$this->add_book( $this->get_reading( 'War and Peace', 3999, array( '2024-02-01' ) ) );

		$this->assertArrayNotHasKey( 'pages', $this->get_earned() );

		$close = $this->get_close()['pages'];

		$this->assertSame( '10,000 pages read', $close[0]['title'] );
		$this->assertSame( 9999, $close[0]['done'] );
		$this->assertSame( 10000, $close[0]['total'] );
	}

	/**
	 * The pages of a book read again count at each reading, dated by the reading that reached the milestone.
	 *
	 * @return void
	 */
	public function test_the_pages_of_a_book_read_again_count_again(): void {
		$this->add_book( $this->get_reading( 'The Count of Monte Cristo', 5000, array( '2023-01-01', '2024-01-01' ) ) );

		$pages = $this->get_earned()['pages'];

		$this->assertSame( '10,000 pages read', $pages[0]['title'] );
		$this->assertSame( '2024-01-01', $pages[0]['date'] );
	}

	/**
	 * The pages of a book to read do not count.
	 *
	 * @return void
	 */
	public function test_the_pages_of_a_book_to_read_do_not_count(): void {
		$this->add_book( $this->get_reading( 'War and Peace', 4000, array( '2024-02-01' ) ) );
		$this->add_book(
			array(
				'title' => 'In Search of Lost Time',
				'meta'  => array( 'pages' => 20000 ),
			)
		);

		$this->assertArrayNotHasKey( 'pages', $this->get_earned() );
		$this->assertSame( 4000, $this->get_close()['pages'][0]['done'] );
	}

	/**
	 * A book past two milestones earns the highest only.
	 *
	 * @return void
	 */
	public function test_only_the_highest_milestone_of_pages_is_earned(): void {
		$this->add_book( $this->get_reading( 'In Search of Lost Time', 30000, array( '2024-02-01' ) ) );

		$pages = $this->get_earned()['pages'];

		$this->assertCount( 1, $pages );
		$this->assertSame( '25,000 pages read', $pages[0]['title'] );
		$this->assertSame( '50,000 pages read', $this->get_close()['pages'][0]['title'] );
	}

	/**
	 * Once the last milestone is reached, none is close.
	 *
	 * @return void
	 */
	public function test_no_milestone_of_pages_is_close_past_the_last(): void {
		$this->add_book( $this->get_reading( 'In Search of Lost Time', 100000, array( '2024-02-01' ) ) );

		$this->assertSame( '100,000 pages read', $this->get_earned()['pages'][0]['title'] );
		$this->assertArrayNotHasKey( 'pages', $this->get_close() );
	}

	/**
	 * Books read without their number of pages show no milestone of pages.
	 *
	 * @return void
	 */
	public function test_no_milestone_of_pages_without_pages(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ) );

		$this->assertArrayNotHasKey( 'pages', $this->get_earned() );
		$this->assertArrayNotHasKey( 'pages', $this->get_close() );
	}

	/**
	 * The year with the most readings is the record, dated by the reading that beat the former record.
	 *
	 * @return void
	 */
	public function test_the_record_year(): void {
		$this->add_reads( array( '2023-03-01', '2023-09-01', '2024-01-05', '2024-02-05', '2024-03-05', '2024-04-05' ) );

		$record = $this->get_earned()['record-year'];

		$this->assertCount( 1, $record );
		$this->assertSame( 'Record year', $record[0]['title'] );
		$this->assertSame( '2024', $record[0]['subject'] );
		$this->assertSame( '4 books', $record[0]['detail'] );
		$this->assertSame( '2024-03-05', $record[0]['date'] );
	}

	/**
	 * A record later beaten is no longer the record.
	 *
	 * @return void
	 */
	public function test_the_record_year_beaten_by_a_later_one(): void {
		$this->add_reads( array( '2022-03-01', '2022-04-01', '2023-03-01', '2024-01-05', '2024-02-05', '2024-03-05' ) );

		$record = $this->get_earned()['record-year'];

		$this->assertSame( '2024', $record[0]['subject'] );
		$this->assertSame( '3 books', $record[0]['detail'] );
		$this->assertSame( '2024-03-05', $record[0]['date'] );
	}

	/**
	 * A year equal to the record does not beat it: the earliest one keeps it.
	 *
	 * @return void
	 */
	public function test_a_year_equal_to_the_record_does_not_beat_it(): void {
		$this->add_reads( array( '2023-03-01', '2023-09-01', '2024-01-05', '2024-02-05' ) );

		$record = $this->get_earned()['record-year'];

		$this->assertSame( '2023', $record[0]['subject'] );
		$this->assertSame( '2 books', $record[0]['detail'] );
		$this->assertSame( '2023-03-01', $record[0]['date'] );
	}

	/**
	 * A single year of readings is no record, whatever the readings on an unknown date.
	 *
	 * @return void
	 */
	public function test_a_single_year_is_no_record(): void {
		$this->add_reads( array( '', '', '2024-01-05', '2024-02-05' ) );

		$this->assertArrayNotHasKey( 'record-year', $this->get_earned() );
	}

	/**
	 * A book read again counts in the record at each reading.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_counts_in_the_record_at_each_reading(): void {
		$this->add_reads( array( '2023-03-01', '2023-09-01' ) );
		$this->add_book( array( 'meta' => array( 'reading' => array( '/2024-01-05', '/2024-06-05', '/2024-11-05' ) ) ) );

		$record = $this->get_earned()['record-year'];

		$this->assertSame( '2024', $record[0]['subject'] );
		$this->assertSame( '3 books', $record[0]['detail'] );
		$this->assertSame( '2024-11-05', $record[0]['date'] );
	}

	/**
	 * The month with the most readings is the record, across years, dated by the reading that beat the former record.
	 *
	 * @return void
	 */
	public function test_the_record_month(): void {
		$this->add_reads( array( '2023-12-01', '2023-12-20', '2024-01-05', '2024-01-10', '2024-01-15', '2024-01-20' ) );

		$record = $this->get_earned()['record-month'];

		$this->assertCount( 1, $record );
		$this->assertSame( 'Record month', $record[0]['title'] );
		$this->assertSame( 'January 2024', $record[0]['subject'] );
		$this->assertSame( '4 books', $record[0]['detail'] );
		$this->assertSame( '2024-01-15', $record[0]['date'] );
	}

	/**
	 * A single month of readings is no record.
	 *
	 * @return void
	 */
	public function test_a_single_month_is_no_record(): void {
		$this->add_reads( array( '2024-01-05', '2024-01-10', '2024-01-15' ) );

		$earned = $this->get_earned();

		$this->assertArrayNotHasKey( 'record-month', $earned );
		$this->assertArrayNotHasKey( 'record-year', $earned );
	}

	/**
	 * No book read again earns nothing.
	 *
	 * @return void
	 */
	public function test_no_book_read_again(): void {
		$this->add_reads( array( '2023-03-01', '2024-03-01' ) );

		$this->assertArrayNotHasKey( 'reread', $this->get_earned() );
	}

	/**
	 * A book read again earns it, dated by the reading again, and leads to the book.
	 *
	 * @return void
	 */
	public function test_a_book_read_again(): void {
		$book_id = $this->add_book(
			array(
				'title' => 'The Left Hand of Darkness',
				'meta'  => array( 'reading' => array( '/2024-06-01', '/2019-05-01' ) ),
			)
		);

		$reread = $this->get_earned()['reread'];

		$this->assertCount( 1, $reread );
		$this->assertSame( 'Read again 1 time', $reread[0]['title'] );
		$this->assertSame( 'The Left Hand of Darkness', $reread[0]['subject'] );
		$this->assertSame( 'First read in 2019', $reread[0]['detail'] );
		$this->assertSame( '2024-06-01', $reread[0]['date'] );
		$this->assertSame(
			array(
				'type' => 'book',
				'id'   => $book_id,
			),
			$reread[0]['target']
		);
	}

	/**
	 * A book first read on an unknown date, then again, says no year of its first reading.
	 *
	 * @return void
	 */
	public function test_a_book_first_read_on_an_unknown_date(): void {
		$this->add_book( array( 'meta' => array( 'reading' => array( '/', '/2024-03-01' ) ) ) );

		$reread = $this->get_earned()['reread'];

		$this->assertSame( 'Read again 1 time', $reread[0]['title'] );
		$this->assertSame( '', $reread[0]['detail'] );
		$this->assertSame( '2024-03-01', $reread[0]['date'] );
	}

	/**
	 * Of books read again as many times, the first to be read again earns it.
	 *
	 * @return void
	 */
	public function test_the_first_book_read_again_earns_it(): void {
		$this->add_book(
			array(
				'title' => 'Dune',
				'meta'  => array( 'reading' => array( '/2020-01-01', '/2024-01-01' ) ),
			)
		);
		$this->add_book(
			array(
				'title' => 'Hyperion',
				'meta'  => array( 'reading' => array( '/2021-01-01', '/2022-01-01' ) ),
			)
		);

		$reread = $this->get_earned()['reread'];

		$this->assertSame( 'Hyperion', $reread[0]['subject'] );
		$this->assertSame( 'First read in 2021', $reread[0]['detail'] );
		$this->assertSame( '2022-01-01', $reread[0]['date'] );
	}

	/**
	 * The level is the most times a book was read again, by the book read again the most, even an earlier one.
	 *
	 * @return void
	 */
	public function test_the_level_is_the_most_times_a_book_was_read_again(): void {
		$this->add_book(
			array(
				'title' => 'Dune',
				'meta'  => array( 'reading' => array( '/2020-01-01', '/2021-01-01', '/2022-01-01' ) ),
			)
		);
		$this->add_book(
			array(
				'title' => 'Hyperion',
				'meta'  => array( 'reading' => array( '/2023-01-01', '/2024-01-01' ) ),
			)
		);

		$reread = $this->get_earned()['reread'];

		$this->assertCount( 1, $reread );
		$this->assertSame( 'Read again 2 times', $reread[0]['title'] );
		$this->assertSame( 'Dune', $reread[0]['subject'] );
		$this->assertSame( 'First read in 2020', $reread[0]['detail'] );
		$this->assertSame( '2022-01-01', $reread[0]['date'] );
	}

	/**
	 * Add books read, one a day, titled "Book 001" and so on.
	 *
	 * @param int    $count Number of books.
	 * @param string $first Read date of the first one, Y-m-d.
	 * @return int[] Book IDs.
	 */
	private function add_read_books( int $count, string $first ): array {
		return array_map(
			fn( int $index ) => $this->add_book(
				array(
					'title' => sprintf( 'Book %03d', $index + 1 ),
					'meta'  => array( 'reading' => array( '/' . gmdate( 'Y-m-d', strtotime( "$first +$index days" ) ) ) ),
				)
			),
			range( 0, $count - 1 )
		);
	}

	/**
	 * A book read with its number of pages, as add_book() takes it.
	 *
	 * @param string   $title      Title.
	 * @param int      $pages      Number of pages.
	 * @param string[] $read_dates Read dates, Y-m-d.
	 * @return array
	 */
	private function get_reading( string $title, int $pages, array $read_dates ): array {
		return array(
			'title' => $title,
			'meta'  => array(
				'pages'   => $pages,
				'reading' => array_map( fn( string $read_date ) => "/$read_date", $read_dates ),
			),
		);
	}
}
