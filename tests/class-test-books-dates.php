<?php
/**
 * Tests of the dates of a book: the work first published, its edition and its copy printed.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Books;

/**
 * Three dates, each a year, a month or a day: the work, the edition of its ISBN, then the copy in hand.
 */
class Test_Books_Dates extends TestCase {

	/**
	 * Each date keeps a year, a month or a day; anything else is dropped.
	 *
	 * @return void
	 */
	public function test_each_date_is_a_year_a_month_or_a_day(): void {
		$book_id = $this->add_book(
			array(
				'meta' => array(
					'published_date' => '1949',
					'edition_date'   => '1972-11',
					'printed_date'   => '2014-02-30',
				),
			)
		);

		$this->assertSame( '1949', get_post_meta( $book_id, 'library_published_date', true ) );
		$this->assertSame( '1972-11', get_post_meta( $book_id, 'library_edition_date', true ) );
		$this->assertSame( '', get_post_meta( $book_id, 'library_printed_date', true ) );
	}

	/**
	 * The credits give the year of the edition, the year its copy was printed standing for it.
	 *
	 * @return void
	 */
	public function test_the_credits_give_the_edition_year_else_the_printing_year(): void {
		$edition = $this->add_book(
			array(
				'meta' => array(
					'edition_date' => '1972',
					'printed_date' => '2014-04-23',
				),
			)
		);
		$printed = $this->add_book( array( 'meta' => array( 'printed_date' => '2014-04-23' ) ) );

		$this->assertSame( '1972', Books::get_credits( $edition ) );
		$this->assertSame( '2014', Books::get_credits( $printed ) );
	}

	/**
	 * The date of a book is the one of its work, else of its edition, else of its copy.
	 *
	 * @return void
	 */
	public function test_the_date_of_a_book_is_the_oldest_record_it_has(): void {
		$work    = $this->add_book(
			array(
				'meta' => array(
					'published_date' => '1949',
					'edition_date'   => '1972',
					'printed_date'   => '2014',
				),
			)
		);
		$edition = $this->add_book(
			array(
				'meta' => array(
					'edition_date' => '1972',
					'printed_date' => '2014',
				),
			)
		);
		$printed = $this->add_book( array( 'meta' => array( 'printed_date' => '2014' ) ) );
		$none    = $this->add_book();
		$books   = Books::get_books();

		$this->assertSame( '1949', Books::get_date( $books[ $work ] ) );
		$this->assertSame( '1972', Books::get_date( $books[ $edition ] ) );
		$this->assertSame( '2014', Books::get_date( $books[ $printed ] ) );
		$this->assertSame( '', Books::get_date( $books[ $none ] ) );
	}

	/**
	 * A date shows as precise as it is known.
	 *
	 * @return void
	 */
	public function test_a_date_shows_as_precise_as_it_is_known(): void {
		update_option( 'date_format', 'F j, Y' );

		$this->assertSame( '1972', Books::format_partial_date( '1972' ) );
		$this->assertSame( 'April 2014', Books::format_partial_date( '2014-04' ) );
		$this->assertSame( 'April 23, 2014', Books::format_partial_date( '2014-04-23' ) );
	}
}
