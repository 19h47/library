<?php
/**
 * Tests of the easter eggs: odd days, odd books and odd habits.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Books;
use Library\Posts;

/**
 * The achievements of the family "easter-eggs": the days a book was read on, the books themselves, and the habits of a
 * reader over days, months and a year.
 */
class Test_Achievements_Easter_Eggs extends TestCase {

	/**
	 * Five hundred books to read make a tsundoku, undated.
	 *
	 * @return void
	 */
	public function test_tsundoku_from_500_books_to_read(): void {
		for ( $i = 0; $i < 500; $i++ ) {
			$this->add_book( array() );
		}

		$tsundoku = $this->get_earned()['tsundoku'];

		$this->assertCount( 1, $tsundoku );
		$this->assertSame( 'Tsundoku', $tsundoku[0]['title'] );
		$this->assertSame( '', $tsundoku[0]['subject'] );
		$this->assertSame( '500 books waiting to be read', $tsundoku[0]['detail'] );
		$this->assertSame( '', $tsundoku[0]['date'] );
	}

	/**
	 * A book to buy is not on the pile, a book not kept is: 499 books to read and one to buy earn nothing, and one not
	 * kept the tsundoku.
	 *
	 * @dataProvider provide_pile_statuses
	 *
	 * @param string $status         Status of the book not read.
	 * @param bool   $is_on_the_pile Whether it is on the pile.
	 * @return void
	 */
	public function test_tsundoku_from_the_books_on_the_pile( string $status, bool $is_on_the_pile ): void {
		for ( $i = 0; $i < 499; $i++ ) {
			$this->add_book();
		}

		$this->add_book( array( 'status' => $status ) );

		$this->assertSame( $is_on_the_pile, isset( $this->get_earned()['tsundoku'] ) );
	}

	/**
	 * Statuses of a book not read, and whether it is on the pile: a book to buy is not, a book not kept is, until read.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function provide_pile_statuses(): array {
		return array(
			'to buy'   => array( Posts::TO_BUY, false ),
			'not kept' => array( Posts::NOT_KEPT, true ),
		);
	}

	/**
	 * A book read is off the pile: 499 books to read out of 500 earn nothing.
	 *
	 * @return void
	 */
	public function test_tsundoku_not_from_499_books_to_read(): void {
		for ( $i = 0; $i < 499; $i++ ) {
			$this->add_book( array() );
		}

		$this->add_reads( array( '2024-05-10' ) );

		$this->assertArrayNotHasKey( 'tsundoku', $this->get_earned() );
	}

	/**
	 * Days of the year that earn an easter egg, with a day before and a day after that do not.
	 *
	 * @return array<string,array{string,string,string,string,string[]}>
	 */
	public function provide_days(): array {
		return array(
			'leap day'        => array( 'leap-day', 'Leap day', 'Read on 29 February', '2024-02-29', array( '2024-02-28', '2024-03-01' ) ),
			'pi day'          => array( 'pi-day', 'Pi day', 'Read on 14 March', '2025-03-14', array( '2025-03-13', '2025-03-15' ) ),
			'new year'        => array( 'new-year', 'New Year\'s resolution', 'Read on 1 January', '2025-01-01', array( '2024-12-31', '2025-01-02' ) ),
			'new year\'s eve' => array( 'new-years-eve', 'Last of the year', 'Read on 31 December', '2024-12-31', array( '2024-12-30', '2025-01-01' ) ),
			'friday 13th'     => array( 'friday-13th', 'Friday the 13th', 'Read on a Friday the 13th', '2024-09-13', array( '2024-09-12', '2024-09-14' ) ),
			'palindrome'      => array( 'palindrome', 'Palindrome date', 'Read on a date that reads the same backwards', '2021-12-02', array( '2021-12-01', '2021-12-03' ) ),
		);
	}

	/**
	 * A book read on the day earns it, with the book as subject, dated by the reading.
	 *
	 * @dataProvider provide_days
	 *
	 * @param string $kind   Kind.
	 * @param string $title  Title.
	 * @param string $detail Detail.
	 * @param string $day    Day that earns it.
	 * @return void
	 */
	public function test_read_on_the_day( string $kind, string $title, string $detail, string $day ): void {
		$book_id = $this->add_read( $day, array( 'title' => 'On the day' ) );

		$egg = $this->get_earned()[ $kind ];

		$this->assertCount( 1, $egg );
		$this->assertSame( $title, $egg[0]['title'] );
		$this->assertSame( 'On the day', $egg[0]['subject'] );
		$this->assertSame( $detail, $egg[0]['detail'] );
		$this->assertSame( $day, $egg[0]['date'] );
		$this->assertSame( $book_id, $egg[0]['target']['id'] );
		$this->assertTrue( $egg[0]['easter_egg'] );
	}

	/**
	 * The day before and the day after earn nothing.
	 *
	 * @dataProvider provide_days
	 *
	 * @param string   $kind   Kind.
	 * @param string   $title  Title.
	 * @param string   $detail Detail.
	 * @param string   $day    Day that earns it.
	 * @param string[] $around Days around it.
	 * @return void
	 */
	public function test_read_around_the_day( string $kind, string $title, string $detail, string $day, array $around ): void {
		$this->add_reads( $around );

		$this->assertArrayNotHasKey( $kind, $this->get_earned() );
	}

	/**
	 * A book read again on the day earns it, though its first reading was another day.
	 *
	 * @return void
	 */
	public function test_read_again_on_the_day(): void {
		$this->add_book(
			array(
				'title' => 'Read again',
				'meta'  => array( 'library_read_date' => array( '2023-05-01', '2024-02-29' ) ),
			)
		);

		$leap_day = $this->get_earned()['leap-day'];

		$this->assertSame( 'Read again', $leap_day[0]['subject'] );
		$this->assertSame( '2024-02-29', $leap_day[0]['date'] );
	}

	/**
	 * A book read on two 29 Februaries is one book, dated by its latest reading.
	 *
	 * @return void
	 */
	public function test_a_book_read_on_the_day_twice_counts_once(): void {
		$this->add_book(
			array(
				'title' => 'Twice',
				'meta'  => array( 'library_read_date' => array( '2020-02-29', '2024-02-29' ) ),
			)
		);

		$leap_day = $this->get_earned()['leap-day'];

		$this->assertSame( 'Read on 29 February', $leap_day[0]['detail'] );
		$this->assertSame( '2024-02-29', $leap_day[0]['date'] );
	}

	/**
	 * Of several books read on the day, the latest is the subject, and the detail says how many.
	 *
	 * @return void
	 */
	public function test_latest_of_the_books_read_on_the_day(): void {
		$this->add_read( '2024-12-13', array( 'title' => 'Later' ) );
		$this->add_read( '2024-09-13', array( 'title' => 'Earlier' ) );

		$friday = $this->get_earned()['friday-13th'];

		$this->assertCount( 1, $friday );
		$this->assertSame( 'Later', $friday[0]['subject'] );
		$this->assertSame( 'Read on a Friday the 13th (2 books)', $friday[0]['detail'] );
		$this->assertSame( '2024-12-13', $friday[0]['date'] );
	}

	/**
	 * A 13th that is not a Friday earns nothing, nor does a Friday that is not a 13th.
	 *
	 * @return void
	 */
	public function test_friday_13th_needs_both(): void {
		$this->add_reads( array( '2024-06-13', '2024-10-13', '2024-09-20' ) );

		$this->assertArrayNotHasKey( 'friday-13th', $this->get_earned() );
	}

	/**
	 * A book added and read the same day is love at first sight.
	 *
	 * @return void
	 */
	public function test_love_at_first_sight(): void {
		$this->add_read( '2024-05-10', array( 'title' => 'Crush' ), '2024-05-10 08:00:00' );

		$love = $this->get_earned()['love-at-first-sight'];

		$this->assertCount( 1, $love );
		$this->assertSame( 'Love at first sight', $love[0]['title'] );
		$this->assertSame( 'Crush', $love[0]['subject'] );
		$this->assertSame( 'Added and read the same day', $love[0]['detail'] );
		$this->assertSame( '2024-05-10', $love[0]['date'] );
	}

	/**
	 * A book added the evening before it is read earns nothing.
	 *
	 * @return void
	 */
	public function test_love_at_first_sight_not_the_day_after(): void {
		$this->add_read( '2024-05-10', array(), '2024-05-09 23:59:59' );

		$this->assertArrayNotHasKey( 'love-at-first-sight', $this->get_earned() );
	}

	/**
	 * A book of 1,000 pages is a brick.
	 *
	 * @return void
	 */
	public function test_brick(): void {
		$this->add_read( '2024-05-10', array( 'meta' => array( 'library_pages' => 1000 ) ) );

		$brick = $this->get_earned()['brick'];

		$this->assertCount( 1, $brick );
		$this->assertSame( 'Brick', $brick[0]['title'] );
		$this->assertSame( 'A book of 1,000 pages or more', $brick[0]['detail'] );
		$this->assertSame( '2024-05-10', $brick[0]['date'] );
	}

	/**
	 * A book of 999 pages is not a brick.
	 *
	 * @return void
	 */
	public function test_brick_not_under_1000_pages(): void {
		$this->add_read( '2024-05-10', array( 'meta' => array( 'library_pages' => 999 ) ) );

		$this->assertArrayNotHasKey( 'brick', $this->get_earned() );
	}

	/**
	 * A brick read without read date earns it, undated: it counts the books read, not their readings.
	 *
	 * @return void
	 */
	public function test_brick_read_without_date(): void {
		$this->add_read( '', array( 'meta' => array( 'library_pages' => 1200 ) ) );

		$brick = $this->get_earned()['brick'];

		$this->assertCount( 1, $brick );
		$this->assertSame( '', $brick[0]['date'] );
	}

	/**
	 * A book read, not kept and rated 9 is the one that got away.
	 *
	 * @return void
	 */
	public function test_one_that_got_away(): void {
		$this->add_read(
			'2024-05-10',
			array(
				'title'  => 'Gone',
				'status' => 'library-not-kept',
				'meta'   => array( 'library_rating' => 9 ),
			)
		);

		$away = $this->get_earned()['one-that-got-away'];

		$this->assertCount( 1, $away );
		$this->assertSame( 'The one that got away', $away[0]['title'] );
		$this->assertSame( 'Gone', $away[0]['subject'] );
		$this->assertSame( 'Not kept, rated 9 or more', $away[0]['detail'] );
		$this->assertSame( '2024-05-10', $away[0]['date'] );
	}

	/**
	 * A book not kept rated 8.5 earns nothing, nor does a book kept rated 10.
	 *
	 * @return void
	 */
	public function test_one_that_got_away_needs_both(): void {
		$this->add_read(
			'2024-05-10',
			array(
				'status' => 'library-not-kept',
				'meta'   => array( 'library_rating' => 8.5 ),
			)
		);
		$this->add_read( '2024-05-11', array( 'meta' => array( 'library_rating' => 10 ) ) );

		$this->assertArrayNotHasKey( 'one-that-got-away', $this->get_earned() );
	}

	/**
	 * A title of 60 characters is a mouthful, counted in characters, not bytes.
	 *
	 * @return void
	 */
	public function test_mouthful(): void {
		$title = str_repeat( 'é', 60 );

		$this->add_read( '2024-05-10', array( 'title' => $title ) );

		$mouthful = $this->get_earned()['mouthful'];

		$this->assertCount( 1, $mouthful );
		$this->assertSame( 'Mouthful', $mouthful[0]['title'] );
		$this->assertSame( $title, $mouthful[0]['subject'] );
		$this->assertSame( 'A title of 60 characters or more', $mouthful[0]['detail'] );
	}

	/**
	 * A title of 59 characters is not a mouthful, even when its series makes the full title longer.
	 *
	 * @return void
	 */
	public function test_mouthful_not_under_60_characters(): void {
		$this->add_read(
			'2024-05-10',
			array(
				'title'  => str_repeat( 'é', 59 ),
				'series' => 'A series whose name is long enough',
				'meta'   => array( 'library_volume_number' => 'Tome 01' ),
			)
		);

		$this->assertArrayNotHasKey( 'mouthful', $this->get_earned() );
	}

	/**
	 * Books of odd numbers of pages, with their title and detail.
	 *
	 * @return array<string,array{string,int,string,string}>
	 */
	public function provide_odd_pages(): array {
		return array(
			'404' => array( 'page-404', 404, 'Error 404', 'Page not found: a book of 404 pages' ),
			'451' => array( 'fahrenheit-451', 451, 'Fahrenheit 451', 'A book of 451 pages, the temperature at which paper burns' ),
			'42'  => array( 'ultimate-question', 42, 'The Ultimate Question', 'A book of 42 pages, or the 42nd read of a year' ),
		);
	}

	/**
	 * A book of the exact number of pages earns it.
	 *
	 * @dataProvider provide_odd_pages
	 *
	 * @param string $kind   Kind.
	 * @param int    $pages  Number of pages.
	 * @param string $title  Title.
	 * @param string $detail Detail.
	 * @return void
	 */
	public function test_odd_pages( string $kind, int $pages, string $title, string $detail ): void {
		$this->add_read(
			'2024-05-10',
			array(
				'title' => 'Odd',
				'meta'  => array( 'library_pages' => $pages ),
			)
		);

		$egg = $this->get_earned()[ $kind ];

		$this->assertCount( 1, $egg );
		$this->assertSame( $title, $egg[0]['title'] );
		$this->assertSame( 'Odd', $egg[0]['subject'] );
		$this->assertSame( $detail, $egg[0]['detail'] );
		$this->assertSame( '2024-05-10', $egg[0]['date'] );
	}

	/**
	 * A page less or a page more earns nothing.
	 *
	 * @dataProvider provide_odd_pages
	 *
	 * @param string $kind  Kind.
	 * @param int    $pages Number of pages.
	 * @return void
	 */
	public function test_odd_pages_one_page_off( string $kind, int $pages ): void {
		$this->add_read( '2024-05-10', array( 'meta' => array( 'library_pages' => $pages - 1 ) ) );
		$this->add_read( '2024-05-11', array( 'meta' => array( 'library_pages' => $pages + 1 ) ) );

		$this->assertArrayNotHasKey( $kind, $this->get_earned() );
	}

	/**
	 * The 42nd reading of a year answers the ultimate question, dated by that reading.
	 *
	 * @return void
	 */
	public function test_42nd_read_of_a_year(): void {
		$this->add_reads( self::get_days( '2024-01-01', 41 ) );
		$this->add_read( '2024-12-20', array( 'title' => 'The answer' ) );

		$answer = $this->get_earned()['ultimate-question'];

		$this->assertCount( 1, $answer );
		$this->assertSame( 'The answer', $answer[0]['subject'] );
		$this->assertSame( 'A book of 42 pages, or the 42nd read of a year', $answer[0]['detail'] );
		$this->assertSame( '2024-12-20', $answer[0]['date'] );
	}

	/**
	 * A book of 42 pages read as the 42nd of a year answers once.
	 *
	 * @return void
	 */
	public function test_42_pages_and_42nd_read_count_once(): void {
		$this->add_reads( self::get_days( '2024-01-01', 41 ) );
		$this->add_read(
			'2024-12-20',
			array(
				'title' => 'The answer',
				'meta'  => array( 'library_pages' => 42 ),
			)
		);

		$answer = $this->get_earned()['ultimate-question'];

		$this->assertSame( 'A book of 42 pages, or the 42nd read of a year', $answer[0]['detail'] );
		$this->assertSame( '2024-12-20', $answer[0]['date'] );
	}

	/**
	 * A book of 42 pages and another read as the 42nd of a year make two answers, by the latest.
	 *
	 * @return void
	 */
	public function test_42_pages_and_42nd_read_of_two_books(): void {
		$this->add_read(
			'2023-06-01',
			array(
				'title' => 'Short',
				'meta'  => array( 'library_pages' => 42 ),
			)
		);
		$this->add_reads( self::get_days( '2024-01-01', 41 ) );
		$this->add_read( '2024-12-20', array( 'title' => 'The answer' ) );

		$answer = $this->get_earned()['ultimate-question'];

		$this->assertSame( 'The answer', $answer[0]['subject'] );
		$this->assertSame( 'A book of 42 pages, or the 42nd read of a year (2 books)', $answer[0]['detail'] );
		$this->assertSame( '2024-12-20', $answer[0]['date'] );
	}

	/**
	 * Forty-two readings over two years, 41 of them in the same year, earn nothing.
	 *
	 * @return void
	 */
	public function test_42_reads_over_two_years(): void {
		$this->add_reads( array( ...self::get_days( '2024-01-01', 41 ), '2025-01-01' ) );

		$this->assertArrayNotHasKey( 'ultimate-question', $this->get_earned() );
	}

	/**
	 * A book under 50 pages and one over 800 read the same month are the splits, dated by the reading that made them.
	 *
	 * @return void
	 */
	public function test_splits(): void {
		$this->add_read( '2024-03-05', array( 'meta' => array( 'library_pages' => 49 ) ) );
		$this->add_read( '2024-03-20', array( 'meta' => array( 'library_pages' => 801 ) ) );
		$this->add_read( '2024-03-25', array( 'meta' => array( 'library_pages' => 30 ) ) );

		$splits = $this->get_earned()['splits'];

		$this->assertCount( 1, $splits );
		$this->assertSame( 'The splits', $splits[0]['title'] );
		$this->assertSame( 'March 2024', $splits[0]['subject'] );
		$this->assertSame( 'Under 50 and over 800 pages in the same month', $splits[0]['detail'] );
		$this->assertSame( '2024-03-20', $splits[0]['date'] );
	}

	/**
	 * Of two months with the splits, the latest earns it.
	 *
	 * @return void
	 */
	public function test_splits_latest_month(): void {
		$this->add_read( '2023-06-01', array( 'meta' => array( 'library_pages' => 20 ) ) );
		$this->add_read( '2023-06-02', array( 'meta' => array( 'library_pages' => 900 ) ) );
		$this->add_read( '2024-03-05', array( 'meta' => array( 'library_pages' => 900 ) ) );
		$this->add_read( '2024-03-06', array( 'meta' => array( 'library_pages' => 20 ) ) );

		$splits = $this->get_earned()['splits'];

		$this->assertCount( 1, $splits );
		$this->assertSame( 'March 2024', $splits[0]['subject'] );
		$this->assertSame( '2024-03-06', $splits[0]['date'] );
	}

	/**
	 * A book of 50 pages is not short, nor is one of 800 pages long.
	 *
	 * @return void
	 */
	public function test_splits_not_at_50_and_800_pages(): void {
		$this->add_read( '2024-03-05', array( 'meta' => array( 'library_pages' => 50 ) ) );
		$this->add_read( '2024-03-06', array( 'meta' => array( 'library_pages' => 801 ) ) );
		$this->add_read( '2024-04-05', array( 'meta' => array( 'library_pages' => 49 ) ) );
		$this->add_read( '2024-04-06', array( 'meta' => array( 'library_pages' => 800 ) ) );

		$this->assertArrayNotHasKey( 'splits', $this->get_earned() );
	}

	/**
	 * A short book and a long one read a day apart, across a month end, earn nothing.
	 *
	 * @return void
	 */
	public function test_splits_not_across_months(): void {
		$this->add_read( '2024-03-31', array( 'meta' => array( 'library_pages' => 20 ) ) );
		$this->add_read( '2024-04-01', array( 'meta' => array( 'library_pages' => 900 ) ) );

		$this->assertArrayNotHasKey( 'splits', $this->get_earned() );
	}

	/**
	 * 8,848 pages read within 365 days, the last day of the window included, across a leap year, reach the Everest.
	 *
	 * @return void
	 */
	public function test_everest_on_the_last_day_of_the_window(): void {
		$this->add_read( '2024-01-01', array( 'meta' => array( 'library_pages' => 4424 ) ) );
		$this->add_read( '2024-12-30', array( 'meta' => array( 'library_pages' => 4424 ) ) );

		$everest = $this->get_earned()['everest'];

		$this->assertCount( 1, $everest );
		$this->assertSame( 'Everest', $everest[0]['title'] );
		$this->assertSame( '', $everest[0]['subject'] );
		$this->assertSame( '8,848 pages read within a year', $everest[0]['detail'] );
		$this->assertSame( '2024-12-30', $everest[0]['date'] );
	}

	/**
	 * A reading on the 366th day leaves the first one out of the window.
	 *
	 * @return void
	 */
	public function test_everest_not_on_the_day_after_the_window(): void {
		$this->add_read( '2024-01-01', array( 'meta' => array( 'library_pages' => 4424 ) ) );
		$this->add_read( '2024-12-31', array( 'meta' => array( 'library_pages' => 4424 ) ) );

		$this->assertArrayNotHasKey( 'everest', $this->get_earned() );
	}

	/**
	 * A page short of 8,848 within the window earns nothing.
	 *
	 * @return void
	 */
	public function test_everest_not_a_page_short(): void {
		$this->add_read( '2024-01-01', array( 'meta' => array( 'library_pages' => 4424 ) ) );
		$this->add_read( '2024-06-01', array( 'meta' => array( 'library_pages' => 4423 ) ) );

		$this->assertArrayNotHasKey( 'everest', $this->get_earned() );
	}

	/**
	 * Of two climbs, the latest reading at the summit dates it.
	 *
	 * @return void
	 */
	public function test_everest_latest_climb(): void {
		$this->add_read( '2020-06-01', array( 'meta' => array( 'library_pages' => 8848 ) ) );
		$this->add_read( '2023-02-01', array( 'meta' => array( 'library_pages' => 5000 ) ) );
		$this->add_read( '2024-01-31', array( 'meta' => array( 'library_pages' => 3848 ) ) );

		$everest = $this->get_earned()['everest'];

		$this->assertCount( 1, $everest );
		$this->assertSame( '2024-01-31', $everest[0]['date'] );
	}

	/**
	 * Works from three centuries read the same month are a time machine, the edition date standing for a work without
	 * first published date.
	 *
	 * @return void
	 */
	public function test_time_machine(): void {
		$this->add_read( '2024-03-02', array( 'meta' => array( 'library_published_date' => '1850' ) ) );
		$this->add_read( '2024-03-10', array( 'meta' => array( 'library_published_date' => '1950-06' ) ) );
		$this->add_read( '2024-03-28', array( 'meta' => array( 'library_edition_date' => '2010-05-01' ) ) );

		$time_machine = $this->get_earned()['time-machine'];

		$this->assertCount( 1, $time_machine );
		$this->assertSame( 'Time machine', $time_machine[0]['title'] );
		$this->assertSame( 'March 2024', $time_machine[0]['subject'] );
		$this->assertSame( 'Works from 3 centuries in the same month', $time_machine[0]['detail'] );
		$this->assertSame( '2024-03-28', $time_machine[0]['date'] );
	}

	/**
	 * Two centuries and an undated work earn nothing, nor do three centuries over two months.
	 *
	 * @return void
	 */
	public function test_time_machine_not_two_centuries(): void {
		$this->add_read( '2024-03-02', array( 'meta' => array( 'library_published_date' => '1850' ) ) );
		$this->add_read( '2024-03-10', array( 'meta' => array( 'library_published_date' => '1950' ) ) );
		$this->add_read( '2024-03-28', array() );
		$this->add_read( '2024-04-01', array( 'meta' => array( 'library_published_date' => '2010' ) ) );

		$this->assertArrayNotHasKey( 'time-machine', $this->get_earned() );
	}

	/**
	 * Two works out the same year, by different authors, read the same day are twins.
	 *
	 * @return void
	 */
	public function test_twins(): void {
		$this->add_read( '2024-05-10', self::get_work( 'Dune', '1965', 'Frank Herbert' ) );
		$twin_id = $this->add_read( '2024-05-10', self::get_work( 'Stand on Zanzibar', '1965-06', 'John Brunner' ) );

		$twins = $this->get_earned()['twins'];

		$this->assertCount( 1, $twins );
		$this->assertSame( 'Twins', $twins[0]['title'] );
		$this->assertSame( 'Stand on Zanzibar', $twins[0]['subject'] );
		$this->assertSame( 'Read the same day as Dune, both came out in 1965', $twins[0]['detail'] );
		$this->assertSame( $twin_id, $twins[0]['target']['id'] );
		$this->assertSame( '2024-05-10', $twins[0]['date'] );
	}

	/**
	 * Two works of the same author, out the same year and read the same day, are not twins.
	 *
	 * @return void
	 */
	public function test_twins_not_by_the_same_author(): void {
		$this->add_read( '2024-05-10', self::get_work( 'Ubik', '1969', 'Philip K. Dick' ) );
		$this->add_read( '2024-05-10', self::get_work( 'Galactic Pot-Healer', '1969', 'Philip K. Dick', 'Ray Nelson' ) );

		$this->assertArrayNotHasKey( 'twins', $this->get_earned() );
	}

	/**
	 * Works out a year apart, or read a day apart, are not twins.
	 *
	 * @return void
	 */
	public function test_twins_not_a_year_or_a_day_apart(): void {
		$this->add_read( '2024-05-10', self::get_work( 'Dune', '1965', 'Frank Herbert' ) );
		$this->add_read( '2024-05-10', self::get_work( 'Babel-17', '1966', 'Samuel R. Delany' ) );
		$this->add_read( '2024-05-11', self::get_work( 'Stand on Zanzibar', '1965', 'John Brunner' ) );

		$this->assertArrayNotHasKey( 'twins', $this->get_earned() );
	}

	/**
	 * A collective work has no author of its own: it has no twin.
	 *
	 * @return void
	 */
	public function test_twins_not_a_collective_work(): void {
		$this->add_read( '2024-05-10', self::get_work( 'Dune', '1965', 'Frank Herbert' ) );
		$this->add_read( '2024-05-10', array_replace_recursive( self::get_work( 'Anthology', '1965', 'John Brunner' ), array( 'meta' => array( 'library_collective' => true ) ) ) );

		$this->assertArrayNotHasKey( 'twins', $this->get_earned() );
	}

	/**
	 * Three books read the same day are a marathon; the latest day earns it, with its own count.
	 *
	 * @return void
	 */
	public function test_marathon_latest_day(): void {
		$this->add_reads( array( '2024-01-05', '2024-01-05', '2024-01-05', '2024-01-05', '2024-02-10', '2024-02-10', '2024-02-10' ) );

		$marathon = $this->get_earned()['marathon'];

		$this->assertCount( 1, $marathon );
		$this->assertSame( 'Marathon', $marathon[0]['title'] );
		$this->assertSame( '', $marathon[0]['subject'] );
		$this->assertSame( '3 books read the same day', $marathon[0]['detail'] );
		$this->assertSame( '2024-02-10', $marathon[0]['date'] );
	}

	/**
	 * Two books read the same day are not a marathon.
	 *
	 * @return void
	 */
	public function test_marathon_not_two_books(): void {
		$this->add_reads( array( '2024-01-05', '2024-01-05', '2024-01-06', '2024-01-06' ) );

		$this->assertArrayNotHasKey( 'marathon', $this->get_earned() );
	}

	/**
	 * A book finished each day for seven days in a row, across the end of a leap February, earns it, dated by the last day.
	 *
	 * @return void
	 */
	public function test_seven_days_a_week_across_a_month_end(): void {
		$this->add_reads( array( '2024-02-26', '2024-02-27', '2024-02-28', '2024-02-29', '2024-03-01', '2024-03-02', '2024-03-03' ) );

		$week = $this->get_earned()['seven-days-a-week'];

		$this->assertCount( 1, $week );
		$this->assertSame( 'Seven days a week', $week[0]['title'] );
		$this->assertSame( '', $week[0]['subject'] );
		$this->assertSame( 'A book finished every day for 7 days', $week[0]['detail'] );
		$this->assertSame( '2024-03-03', $week[0]['date'] );
	}

	/**
	 * Six days in a row earn nothing, even with two books on one of them, and a day missing breaks the run.
	 *
	 * @return void
	 */
	public function test_seven_days_a_week_not_six_days(): void {
		$this->add_reads( array( '2024-12-28', '2024-12-29', '2024-12-30', '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-02', '2025-01-04' ) );

		$this->assertArrayNotHasKey( 'seven-days-a-week', $this->get_earned() );
	}

	/**
	 * The longest run earns it, though a shorter one came after.
	 *
	 * @return void
	 */
	public function test_seven_days_a_week_longest_run(): void {
		$this->add_reads( self::get_days( '2023-12-28', 8 ) );
		$this->add_reads( self::get_days( '2024-06-01', 7 ) );

		$week = $this->get_earned()['seven-days-a-week'];

		$this->assertCount( 1, $week );
		$this->assertSame( 'A book finished every day for 8 days', $week[0]['detail'] );
		$this->assertSame( '2024-01-04', $week[0]['date'] );
	}

	/**
	 * Of two runs as long, the latest earns it.
	 *
	 * @return void
	 */
	public function test_seven_days_a_week_latest_of_the_longest(): void {
		$this->add_reads( self::get_days( '2023-03-01', 7 ) );
		$this->add_reads( self::get_days( '2024-06-01', 7 ) );

		$week = $this->get_earned()['seven-days-a-week'];

		$this->assertCount( 1, $week );
		$this->assertSame( '2024-06-07', $week[0]['date'] );
	}

	/**
	 * Three books rated 10 read in a row are a hat-trick, the third as subject.
	 *
	 * @return void
	 */
	public function test_hat_trick(): void {
		$this->add_read( '2024-05-01', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$this->add_read( '2024-05-02', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$third_id = $this->add_read(
			'2024-05-03',
			array(
				'title' => 'Third',
				'meta'  => array( 'library_rating' => 10 ),
			)
		);

		$hat_trick = $this->get_earned()['hat-trick'];

		$this->assertCount( 1, $hat_trick );
		$this->assertSame( 'Hat-trick', $hat_trick[0]['title'] );
		$this->assertSame( 'Third', $hat_trick[0]['subject'] );
		$this->assertSame( 'Three books rated 10 in a row', $hat_trick[0]['detail'] );
		$this->assertSame( $third_id, $hat_trick[0]['target']['id'] );
		$this->assertSame( '2024-05-03', $hat_trick[0]['date'] );
	}

	/**
	 * A book rated 9.5 between them breaks the run.
	 *
	 * @return void
	 */
	public function test_hat_trick_broken_by_a_lower_rating(): void {
		$this->add_read( '2024-05-01', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$this->add_read( '2024-05-02', array( 'meta' => array( 'library_rating' => 10 ) ) );
		$this->add_read( '2024-05-03', array( 'meta' => array( 'library_rating' => 9.5 ) ) );
		$this->add_read( '2024-05-04', array( 'meta' => array( 'library_rating' => 10 ) ) );

		$this->assertArrayNotHasKey( 'hat-trick', $this->get_earned() );
	}

	/**
	 * One book rated 10 read three times in a row is no hat-trick: it takes three books.
	 *
	 * @return void
	 */
	public function test_no_hat_trick_from_one_book_read_three_times(): void {
		$this->add_book(
			array(
				'meta' => array(
					'library_rating'    => 10,
					'library_read_date' => array( '2024-05-01', '2024-05-02', '2024-05-03' ),
				),
			)
		);

		$this->assertArrayNotHasKey( 'hat-trick', $this->get_earned() );
	}

	/**
	 * A book rated 10 read again in the run neither breaks it nor counts twice: the third book makes it.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_in_a_hat_trick(): void {
		$this->add_book(
			array(
				'meta' => array(
					'library_rating'    => 10,
					'library_read_date' => array( '2024-05-01', '2024-05-02' ),
				),
			)
		);
		$this->add_read( '2024-05-03', array( 'meta' => array( 'library_rating' => 10 ) ) );

		$this->assertArrayNotHasKey( 'hat-trick', $this->get_earned() );

		$this->add_read(
			'2024-05-04',
			array(
				'title' => 'Third',
				'meta'  => array( 'library_rating' => 10 ),
			)
		);

		$hat_trick = $this->get_earned()['hat-trick'];

		$this->assertSame( 'Third', $hat_trick[0]['subject'] );
		$this->assertSame( '2024-05-04', $hat_trick[0]['date'] );
	}

	/**
	 * Two books read with the same title, whatever its case, by different authors are namesakes.
	 *
	 * @return void
	 */
	public function test_namesakes(): void {
		$this->add_read( '2023-02-01', self::get_work( 'Solaris', '1961', 'Stanisław Lem' ) );
		$this->add_read( '2024-05-10', self::get_work( 'solaris', '2002', 'Someone Else' ) );

		$namesakes = $this->get_earned()['namesakes'];

		$this->assertCount( 1, $namesakes );
		$this->assertSame( 'Namesakes', $namesakes[0]['title'] );
		$this->assertSame( 'Solaris', $namesakes[0]['subject'] );
		$this->assertSame( 'The same title, different authors', $namesakes[0]['detail'] );
		$this->assertSame( 'search', $namesakes[0]['target']['type'] );
		$this->assertSame( '2024-05-10', $namesakes[0]['date'] );
	}

	/**
	 * Of two pairs of namesakes, the latest one completed shows, dated by its second book.
	 *
	 * @return void
	 */
	public function test_the_latest_namesakes(): void {
		$this->add_read( '2020-01-01', self::get_work( 'Alpha', '1990', 'First Author' ) );
		$this->add_read( '2021-01-01', self::get_work( 'Beta', '1990', 'First Author' ) );
		$this->add_read( '2022-01-01', self::get_work( 'Beta', '1995', 'Second Author' ) );
		$this->add_read( '2023-01-01', self::get_work( 'Alpha', '1995', 'Second Author' ) );

		$namesakes = $this->get_earned()['namesakes'];

		$this->assertSame( 'Alpha', $namesakes[0]['subject'] );
		$this->assertSame( '2023-01-01', $namesakes[0]['date'] );
	}

	/**
	 * The same title by the same author, by a collective work, or not read earns nothing.
	 *
	 * @return void
	 */
	public function test_namesakes_not_the_same_author(): void {
		$this->add_read( '2023-02-01', self::get_work( 'Solaris', '1961', 'Stanisław Lem' ) );
		$this->add_read( '2024-05-10', self::get_work( 'Solaris', '1961', 'Stanisław Lem' ) );
		$this->add_read( '2024-05-11', array_replace_recursive( self::get_work( 'Solaris', '2002', 'Someone Else' ), array( 'meta' => array( 'library_collective' => true ) ) ) );
		$this->add_book( self::get_work( 'Solaris', '2010', 'Another One' ) );

		$this->assertArrayNotHasKey( 'namesakes', $this->get_earned() );
	}

	/**
	 * The first volume of a series read after a later one is back to the start.
	 *
	 * @return void
	 */
	public function test_back_to_the_start(): void {
		$this->add_read( '2024-01-10', self::get_volume( 'Foundation', 'Tome 02', 'Foundation and Empire' ) );
		$first_id = $this->add_read( '2024-02-10', self::get_volume( 'Foundation', 'Tome 01', 'Foundation' ) );

		$back = $this->get_earned()['back-to-the-start'];

		$this->assertCount( 1, $back );
		$this->assertSame( 'Back to the start', $back[0]['title'] );
		$this->assertSame( Books::get_full_title( $first_id, 'Foundation' ), $back[0]['subject'] );
		$this->assertSame( 'Volume 1 read after a later volume', $back[0]['detail'] );
		$this->assertSame( $first_id, $back[0]['target']['id'] );
		$this->assertSame( '2024-02-10', $back[0]['date'] );
	}

	/**
	 * Volumes read in order earn nothing, nor does a first volume after a later one of another series.
	 *
	 * @return void
	 */
	public function test_back_to_the_start_not_in_order(): void {
		$this->add_read( '2024-01-10', self::get_volume( 'Foundation', 'Tome 01', 'Foundation' ) );
		$this->add_read( '2024-02-10', self::get_volume( 'Foundation', 'Tome 02', 'Foundation and Empire' ) );
		$this->add_read( '2024-03-10', self::get_volume( 'Dune', 'Tome 02', 'Dune Messiah' ) );
		$this->add_read( '2024-04-10', self::get_volume( 'Hyperion', 'Tome 01', 'Hyperion' ) );

		$this->assertArrayNotHasKey( 'back-to-the-start', $this->get_earned() );
	}

	/**
	 * A first volume read first, then read again after a later one, is not back to the start: a book counts at its
	 * first reading.
	 *
	 * @return void
	 */
	public function test_back_to_the_start_not_a_first_volume_read_again(): void {
		$first = self::get_volume( 'Foundation', 'Tome 01', 'Foundation' );

		$first['meta']['library_read_date'] = array( '2023-01-10', '2025-01-10' );

		$this->add_book( $first );
		$this->add_read( '2024-02-10', self::get_volume( 'Foundation', 'Tome 02', 'Foundation and Empire' ) );

		$this->assertArrayNotHasKey( 'back-to-the-start', $this->get_earned() );
	}

	/**
	 * A work, with when it first came out and its authors.
	 *
	 * @param string $title          Title.
	 * @param string $published_date First published.
	 * @param string ...$authors     Names of its authors.
	 * @return array
	 */
	private static function get_work( string $title, string $published_date, string ...$authors ): array {
		return array(
			'title'  => $title,
			'people' => array_map(
				fn( string $name ) => array(
					'name' => $name,
					'role' => 'author',
				),
				$authors
			),
			'meta'   => array( 'library_published_date' => $published_date ),
		);
	}

	/**
	 * A volume of a series.
	 *
	 * @param string $series Series.
	 * @param string $volume Volume number, as typed: "Tome 01".
	 * @param string $title  Title.
	 * @return array
	 */
	private static function get_volume( string $series, string $volume, string $title ): array {
		return array(
			'title'  => $title,
			'series' => $series,
			'meta'   => array( 'library_volume_number' => $volume ),
		);
	}
}
