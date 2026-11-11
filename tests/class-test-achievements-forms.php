<?php
/**
 * Tests of the forms: books read of a form, a jack of all trades and the tour of the library.
 *
 * @package Library
 */

namespace Library\Tests;

/**
 * Numbers of books read of a form, several forms read in a month, and a book read of each form at hand.
 */
class Test_Achievements_Forms extends TestCase {

	/**
	 * Ten books read of a form earn it, by the tenth book read, with a search for the form read.
	 *
	 * @return void
	 */
	public function test_ten_books_read_of_a_form(): void {
		$this->add_form_reads( 'Novel', 11 );

		$form = $this->get_earned()['form'];

		$this->assertCount( 1, $form );
		$this->assertSame( '10 books read', $form[0]['title'] );
		$this->assertSame( 'Novel', $form[0]['subject'] );
		$this->assertSame( 'Novel 10', $form[0]['detail'] );
		$this->assertSame( '2024-01-10', $form[0]['date'] );
		$this->assertSame( 'form:"Novel" read:yes', $form[0]['target']['search'] );
	}

	/**
	 * The tenth book is the tenth one read, whatever the order the books were added in.
	 *
	 * @return void
	 */
	public function test_the_tenth_book_is_the_tenth_read(): void {
		$this->add_form_reads( 'Novel', 9, '2024-02-01' );
		$this->add_form_reads( 'Novel', 1, '2024-01-01', 'Read first' );

		$form = $this->get_earned()['form'];

		$this->assertSame( 'Novel 9', $form[0]['detail'] );
		$this->assertSame( '2024-02-09', $form[0]['date'] );
	}

	/**
	 * Nine books are close to ten, undated.
	 *
	 * @return void
	 */
	public function test_nine_books_are_close(): void {
		$this->add_form_reads( 'Novel', 9 );

		$this->assertArrayNotHasKey( 'form', $this->get_earned() );

		$form = $this->get_close()['form'];

		$this->assertCount( 1, $form );
		$this->assertSame( '10 books read', $form[0]['title'] );
		$this->assertSame( 'Novel', $form[0]['subject'] );
		$this->assertSame( '', $form[0]['detail'] );
		$this->assertSame( '', $form[0]['date'] );
		$this->assertSame( 9, $form[0]['done'] );
		$this->assertSame( 10, $form[0]['total'] );
	}

	/**
	 * Eight books are close, seven are not.
	 *
	 * @return void
	 */
	public function test_seven_books_are_not_close(): void {
		$this->add_form_reads( 'Novel', 8 );
		$this->add_form_reads( 'Comic', 7 );

		$form = $this->get_close()['form'];

		$this->assertCount( 1, $form );
		$this->assertSame( 'Novel', $form[0]['subject'] );
		$this->assertSame( 8, $form[0]['done'] );
	}

	/**
	 * A book read again counts once.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_counts_once(): void {
		$this->add_form_reads( 'Novel', 8 );
		$this->add_book(
			array(
				'title' => 'Read twice',
				'form'  => $this->get_form( 'Novel' ),
				'meta'  => array( 'reading' => array( '/2024-03-01', '/2024-04-01' ) ),
			)
		);

		$this->assertArrayNotHasKey( 'form', $this->get_earned() );
		$this->assertSame( 9, $this->get_close()['form'][0]['done'] );
	}

	/**
	 * Only the highest number reached is earned, and the next one is close.
	 *
	 * @return void
	 */
	public function test_the_highest_number_reached_and_the_next_one_close(): void {
		$this->add_form_reads( 'Novel', 23 );

		$earned = $this->get_earned()['form'];
		$close  = $this->get_close()['form'];

		$this->assertCount( 1, $earned );
		$this->assertSame( '10 books read', $earned[0]['title'] );
		$this->assertSame( '25 books read', $close[0]['title'] );
		$this->assertSame( 23, $close[0]['done'] );
		$this->assertSame( 25, $close[0]['total'] );
	}

	/**
	 * Four forms read in a month make a jack of all trades, dated by the reading of the fourth one.
	 *
	 * @return void
	 */
	public function test_four_forms_read_in_a_month(): void {
		$this->add_form_reads( 'Novel', 1, '2024-03-02' );
		$this->add_form_reads( 'Comic', 1, '2024-03-10' );
		$this->add_form_reads( 'Essay', 1, '2024-03-15' );
		$this->add_form_reads( 'Magazine', 1, '2024-03-31' );
		$this->add_form_reads( 'Novel', 1, '2024-03-31', 'Another novel' );

		$jack_of_all_trades = $this->get_earned()['jack-of-all-trades'];

		$this->assertCount( 1, $jack_of_all_trades );
		$this->assertSame( 'Jack of all trades', $jack_of_all_trades[0]['title'] );
		$this->assertSame( 'March 2024', $jack_of_all_trades[0]['subject'] );
		$this->assertSame( '4 forms in a month', $jack_of_all_trades[0]['detail'] );
		$this->assertSame( '2024-03-31', $jack_of_all_trades[0]['date'] );
	}

	/**
	 * Three forms in a month are not enough, nor four across the end of a month.
	 *
	 * @return void
	 */
	public function test_four_forms_across_two_months_are_not_enough(): void {
		$this->add_form_reads( 'Novel', 1, '2024-03-31' );
		$this->add_form_reads( 'Comic', 1, '2024-04-01' );
		$this->add_form_reads( 'Essay', 1, '2024-04-02' );
		$this->add_form_reads( 'Magazine', 1, '2024-04-03' );

		$this->assertArrayNotHasKey( 'jack-of-all-trades', $this->get_earned() );
	}

	/**
	 * The latest month with four forms counts.
	 *
	 * @return void
	 */
	public function test_the_latest_month_counts(): void {
		foreach ( array( '2024-01', '2024-05' ) as $month ) {
			foreach ( array( 'Novel', 'Comic', 'Essay', 'Magazine' ) as $index => $form ) {
				$this->add_form_reads( $form, 1, $month . '-1' . $index, $form . ' of ' . $month );
			}
		}

		$jack_of_all_trades = $this->get_earned()['jack-of-all-trades'];

		$this->assertCount( 1, $jack_of_all_trades );
		$this->assertSame( 'May 2024', $jack_of_all_trades[0]['subject'] );
		$this->assertSame( '2024-05-13', $jack_of_all_trades[0]['date'] );
	}

	/**
	 * A book read again counts in the month of each reading.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_counts_in_the_month_of_each_reading(): void {
		$this->add_book(
			array(
				'title' => 'Read twice',
				'form'  => $this->get_form( 'Novel' ),
				'meta'  => array( 'reading' => array( '/2024-01-05', '/2024-03-05' ) ),
			)
		);
		$this->add_form_reads( 'Comic', 1, '2024-03-10' );
		$this->add_form_reads( 'Essay', 1, '2024-03-12' );
		$this->add_form_reads( 'Magazine', 1, '2024-03-14' );

		$jack_of_all_trades = $this->get_earned()['jack-of-all-trades'];

		$this->assertSame( 'March 2024', $jack_of_all_trades[0]['subject'] );
		$this->assertSame( '2024-03-14', $jack_of_all_trades[0]['date'] );
	}

	/**
	 * A book read of each form earns the tour, dated by the first reading of the last form.
	 *
	 * @return void
	 */
	public function test_a_book_read_of_each_form(): void {
		$this->add_form_reads( 'Novel', 1, '2024-01-01' );
		$this->add_form_reads( 'Comic', 1, '2024-03-01' );
		$this->add_form_reads( 'Novel', 1, '2024-06-01', 'Another novel' );

		$library_tour = $this->get_earned()['library-tour'];

		$this->assertCount( 1, $library_tour );
		$this->assertSame( 'Tour of the library', $library_tour[0]['title'] );
		$this->assertSame( '', $library_tour[0]['subject'] );
		$this->assertSame( '2 forms read', $library_tour[0]['detail'] );
		$this->assertSame( '2024-03-01', $library_tour[0]['date'] );
		$this->assertArrayNotHasKey( 'library-tour', $this->get_close() );
	}

	/**
	 * A single form is no tour.
	 *
	 * @return void
	 */
	public function test_a_single_form_is_no_tour(): void {
		$this->add_form_reads( 'Novel', 2 );

		$this->assertArrayNotHasKey( 'library-tour', $this->get_earned() );
		$this->assertArrayNotHasKey( 'library-tour', $this->get_close() );
	}

	/**
	 * A form left to read is close, with a search for its books to read.
	 *
	 * @return void
	 */
	public function test_a_form_left_to_read_is_close(): void {
		$this->add_form_reads( 'Novel', 1 );
		$this->add_form_reads( 'Comic', 1 );
		$this->add_book( array( 'form' => $this->get_form( 'Poetry' ) ) );

		$this->assertArrayNotHasKey( 'library-tour', $this->get_earned() );

		$library_tour = $this->get_close()['library-tour'];

		$this->assertCount( 1, $library_tour );
		$this->assertSame( 'Tour of the library', $library_tour[0]['title'] );
		$this->assertSame( 'Still to read: Poetry', $library_tour[0]['detail'] );
		$this->assertSame( '', $library_tour[0]['date'] );
		$this->assertSame( 2, $library_tour[0]['done'] );
		$this->assertSame( 3, $library_tour[0]['total'] );
		$this->assertSame( 'form:"Poetry" read:no', $library_tour[0]['target']['search'] );
		$this->assertFalse( $library_tour[0]['target']['not_kept'] );
	}

	/**
	 * Three forms left are close, four are not.
	 *
	 * @return void
	 */
	public function test_four_forms_left_are_not_close(): void {
		$this->add_form_reads( 'Novel', 1 );

		foreach ( array( 'Comic', 'Essay', 'Poetry' ) as $form ) {
			$this->add_book( array( 'form' => $this->get_form( $form ) ) );
		}

		$this->assertSame( 1, $this->get_close()['library-tour'][0]['done'] );
		$this->assertSame( 4, $this->get_close()['library-tour'][0]['total'] );

		$this->add_book( array( 'form' => $this->get_form( 'Drama' ) ) );

		$this->assertArrayNotHasKey( 'library-tour', $this->get_close() );
	}

	/**
	 * The form of a book not kept is in the tour, its books to read are found among those not kept.
	 *
	 * @return void
	 */
	public function test_a_form_of_books_not_kept_counts(): void {
		$this->add_form_reads( 'Novel', 1 );
		$this->add_form_reads( 'Comic', 1 );
		$this->add_book(
			array(
				'form'   => $this->get_form( 'Poetry' ),
				'status' => 'library-not-kept',
			)
		);

		$library_tour = $this->get_close()['library-tour'];

		$this->assertSame( 'Still to read: Poetry', $library_tour[0]['detail'] );
		$this->assertTrue( $library_tour[0]['target']['not_kept'] );
	}

	/**
	 * The form of a book to buy is not in the library yet.
	 *
	 * @return void
	 */
	public function test_a_form_of_books_to_buy_does_not_count(): void {
		$this->add_form_reads( 'Novel', 1 );
		$this->add_form_reads( 'Comic', 1 );
		$this->add_book(
			array(
				'form'   => $this->get_form( 'Poetry' ),
				'status' => 'library-to-buy',
			)
		);

		$this->assertSame( '2 forms read', $this->get_earned()['library-tour'][0]['detail'] );
	}

	/**
	 * Add books read of a form, one a day, titled after the form and their number: "Novel 1", "Novel 2"…
	 *
	 * @param string $form  Name of the form.
	 * @param int    $count Number of books.
	 * @param string $first Read date of the first one, Y-m-d.
	 * @param string $title Title, for a single book, or empty for the numbered titles.
	 * @return void
	 */
	private function add_form_reads( string $form, int $count, string $first = '2024-01-01', string $title = '' ): void {
		for ( $number = 1; $number <= $count; $number++ ) {
			$read_date = gmdate( 'Y-m-d', strtotime( $first . ' +' . ( $number - 1 ) . ' days' ) );

			$this->add_book(
				array(
					'title' => '' !== $title ? $title : $form . ' ' . $number,
					'form'  => $this->get_form( $form ),
					'meta'  => array( 'reading' => array( "/$read_date" ) ),
				)
			);
		}
	}
}
