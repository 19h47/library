<?php
/**
 * Tests of the discoveries: hot off the press, centenarian, archaeologist and the decades of the 20th century.
 *
 * @package Library
 */

/**
 * Books read the year they came out or a century after, the oldest of the pile read, and the tour of the 20th century.
 */
class Test_Library_Achievements_Discoveries extends Library_TestCase {

	/**
	 * A book read the year it first came out is hot off the press, dated by its reading.
	 *
	 * @return void
	 */
	public function test_a_book_read_the_year_it_came_out_is_hot_off_the_press(): void {
		$this->add_read_book( 'Fresh', '2024-12-31', array( 'library_published_date' => '2024-03' ) );

		$hot_off_the_press = $this->get_earned()['hot-off-the-press'];

		$this->assertCount( 1, $hot_off_the_press );
		$this->assertSame( 'Hot off the press', $hot_off_the_press[0]['title'] );
		$this->assertSame( 'Fresh', $hot_off_the_press[0]['subject'] );
		$this->assertSame( 'Read the year it came out', $hot_off_the_press[0]['detail'] );
		$this->assertSame( '2024-12-31', $hot_off_the_press[0]['date'] );
	}

	/**
	 * A book read the year after it came out is not hot off the press, even on the first day of the year.
	 *
	 * @return void
	 */
	public function test_a_book_read_the_year_after_is_not_hot_off_the_press(): void {
		$this->add_read_book( 'Last year', '2025-01-01', array( 'library_published_date' => '2024-12-31' ) );

		$this->assertArrayNotHasKey( 'hot-off-the-press', $this->get_earned() );
	}

	/**
	 * Without the date the work first came out, the edition date tells the year.
	 *
	 * @return void
	 */
	public function test_hot_off_the_press_falls_back_on_the_edition_date(): void {
		$this->add_read_book( 'New edition', '2024-06-01', array( 'library_edition_date' => '2024-02-15' ) );

		$this->assertSame( 'New edition', $this->get_earned()['hot-off-the-press'][0]['subject'] );
	}

	/**
	 * The year the work first came out wins over the year of its edition.
	 *
	 * @return void
	 */
	public function test_the_first_publication_wins_over_the_edition_date(): void {
		$this->add_read_book(
			'Reissue',
			'2024-06-01',
			array(
				'library_published_date' => '1950',
				'library_edition_date'   => '2024-02-15',
			)
		);

		$this->assertArrayNotHasKey( 'hot-off-the-press', $this->get_earned() );
	}

	/**
	 * A book read again is dated by its first reading.
	 *
	 * @return void
	 */
	public function test_hot_off_the_press_is_dated_by_the_first_reading(): void {
		$this->add_book(
			array(
				'title' => 'Read twice',
				'meta'  => array(
					'library_published_date' => '2020',
					'library_read_date'      => array( '2020-06-01', '2024-01-01' ),
				),
			)
		);

		$this->assertSame( '2020-06-01', $this->get_earned()['hot-off-the-press'][0]['date'] );
	}

	/**
	 * Several books show the latest one read, with how many did it.
	 *
	 * @return void
	 */
	public function test_hot_off_the_press_shows_the_latest_book_and_how_many(): void {
		$this->add_read_book( 'Later', '2024-09-01', array( 'library_published_date' => '2024' ) );
		$this->add_read_book( 'Earlier', '2023-05-01', array( 'library_published_date' => '2023' ) );
		$this->add_read_book( 'Not new', '2024-10-01', array( 'library_published_date' => '2001' ) );

		$hot_off_the_press = $this->get_earned()['hot-off-the-press'];

		$this->assertCount( 1, $hot_off_the_press );
		$this->assertSame( 'Later', $hot_off_the_press[0]['subject'] );
		$this->assertSame( 'Read the year it came out (2 books)', $hot_off_the_press[0]['detail'] );
		$this->assertSame( '2024-09-01', $hot_off_the_press[0]['date'] );
	}

	/**
	 * A work read a hundred years after it first came out is a centenarian.
	 *
	 * @return void
	 */
	public function test_a_book_read_a_century_after_is_a_centenarian(): void {
		$this->add_read_book( 'Old', '2024-01-01', array( 'library_published_date' => '1924-12-31' ) );

		$centenarian = $this->get_earned()['centenarian'];

		$this->assertCount( 1, $centenarian );
		$this->assertSame( 'Centenarian', $centenarian[0]['title'] );
		$this->assertSame( 'Old', $centenarian[0]['subject'] );
		$this->assertSame( 'Read a century or more after it first came out', $centenarian[0]['detail'] );
		$this->assertSame( '2024-01-01', $centenarian[0]['date'] );
	}

	/**
	 * Ninety-nine years are not a century, and a book without date is neither old nor new.
	 *
	 * @return void
	 */
	public function test_ninety_nine_years_are_not_a_century(): void {
		$this->add_read_book( 'Almost', '2024-12-31', array( 'library_published_date' => '1925' ) );
		$this->add_read_book( 'Undated', '2024-12-31' );

		$earned = $this->get_earned();

		$this->assertArrayNotHasKey( 'centenarian', $earned );
		$this->assertArrayNotHasKey( 'hot-off-the-press', $earned );
	}

	/**
	 * A book read once those added before it are read is the oldest of the pile; the latest one shows, with how many.
	 *
	 * @return void
	 */
	public function test_books_read_in_the_order_they_were_added_are_archaeologists(): void {
		$this->add_read_book( 'First', '2024-02-01', array(), '2024-01-01 10:00:00' );
		$this->add_read_book( 'Second', '2024-03-01', array(), '2024-01-02 10:00:00' );

		$archaeologist = $this->get_earned()['archaeologist'];

		$this->assertCount( 1, $archaeologist );
		$this->assertSame( 'Archaeologist', $archaeologist[0]['title'] );
		$this->assertSame( 'Second', $archaeologist[0]['subject'] );
		$this->assertSame( 'Read while the oldest book of the pile (2 books)', $archaeologist[0]['detail'] );
		$this->assertSame( '2024-03-01', $archaeologist[0]['date'] );
	}

	/**
	 * A book read before one added earlier is not the oldest of the pile.
	 *
	 * @return void
	 */
	public function test_a_book_read_before_one_added_earlier_is_not_an_archaeologist(): void {
		$this->add_read_book( 'First', '2024-03-01', array(), '2024-01-01 10:00:00' );
		$this->add_read_book( 'Second', '2024-02-01', array(), '2024-01-02 10:00:00' );

		$archaeologist = $this->get_earned()['archaeologist'];

		$this->assertSame( 'First', $archaeologist[0]['subject'] );
		$this->assertSame( 'Read while the oldest book of the pile', $archaeologist[0]['detail'] );
		$this->assertSame( '2024-03-01', $archaeologist[0]['date'] );
	}

	/**
	 * A book read on the day the one added before it is read is the oldest of the pile.
	 *
	 * @return void
	 */
	public function test_a_book_read_the_same_day_as_the_one_added_before_is_an_archaeologist(): void {
		$this->add_read_book( 'First', '2024-03-01', array(), '2024-01-01 10:00:00' );
		$this->add_read_book( 'Second', '2024-03-01', array(), '2024-01-02 10:00:00' );

		$this->assertSame( 'Read while the oldest book of the pile (2 books)', $this->get_earned()['archaeologist'][0]['detail'] );
	}

	/**
	 * A book still to read keeps every book added after it from being the oldest of the pile.
	 *
	 * @return void
	 */
	public function test_a_book_to_read_added_before_keeps_it(): void {
		$this->add_book( array( 'title' => 'On the pile' ), '2024-01-01 10:00:00' );
		$this->add_read_book( 'Second', '2024-03-01', array(), '2024-01-02 10:00:00' );

		$this->assertArrayNotHasKey( 'archaeologist', $this->get_earned() );
	}

	/**
	 * A book to buy is not on the pile.
	 *
	 * @return void
	 */
	public function test_a_book_to_buy_is_not_on_the_pile(): void {
		$this->add_book(
			array(
				'title'  => 'Wished',
				'status' => 'library-to-buy',
			),
			'2024-01-01 10:00:00'
		);
		$this->add_read_book( 'Second', '2024-03-01', array(), '2024-01-02 10:00:00' );

		$this->assertSame( 'Second', $this->get_earned()['archaeologist'][0]['subject'] );
	}

	/**
	 * Books added at the same time are not added before one another.
	 *
	 * @return void
	 */
	public function test_books_added_together_do_not_wait_for_each_other(): void {
		$this->add_book( array( 'title' => 'On the pile' ), '2024-01-01 10:00:00' );
		$this->add_read_book( 'Same time', '2024-03-01', array(), '2024-01-01 10:00:00' );

		$this->assertSame( 'Same time', $this->get_earned()['archaeologist'][0]['subject'] );
	}

	/**
	 * A book read without date counts as read before any other.
	 *
	 * @return void
	 */
	public function test_a_book_read_without_date_counts_as_read_first(): void {
		$this->add_book(
			array(
				'title' => 'Read long ago',
				'meta'  => array( 'library_read_date' => array( '' ) ),
			),
			'2024-01-01 10:00:00'
		);
		$this->add_read_book( 'Second', '2024-03-01', array(), '2024-01-02 10:00:00' );

		$archaeologist = $this->get_earned()['archaeologist'];

		$this->assertSame( 'Second', $archaeologist[0]['subject'] );
		$this->assertSame( 'Read while the oldest book of the pile', $archaeologist[0]['detail'] );
	}

	/**
	 * A book read of each decade of the 20th century, from 1900 to 1999, earns the tour, dated by the reading of the
	 * last decade; a second book of a decade read later changes nothing.
	 *
	 * @return void
	 */
	public function test_a_book_of_each_decade_of_the_20th_century(): void {
		$this->add_decades( array( 1900, 1912, 1925, 1938, 1941, 1956, 1963, 1977, 1984, 1999 ) );
		$this->add_read_book( 'Another of the twenties', '2025-06-01', array( 'library_published_date' => '1929' ) );

		$decades = $this->get_earned()['decades'];

		$this->assertCount( 1, $decades );
		$this->assertSame( 'Tour of the 20th century', $decades[0]['title'] );
		$this->assertSame( '', $decades[0]['subject'] );
		$this->assertSame( '10 decades of 10', $decades[0]['detail'] );
		$this->assertSame( '2024-01-10', $decades[0]['date'] );
		$this->assertArrayNotHasKey( 'decades', $this->get_close() );
	}

	/**
	 * Books of 1899 and 2000 are out of the 20th century: nine decades are close.
	 *
	 * @return void
	 */
	public function test_1899_and_2000_are_out_of_the_20th_century(): void {
		$this->add_decades( array( 1899, 1900, 1912, 1925, 1938, 1941, 1956, 1963, 1977, 1999, 2000 ) );

		$this->assertArrayNotHasKey( 'decades', $this->get_earned() );

		$decades = $this->get_close()['decades'];

		$this->assertSame( '9 decades of 10', $decades[0]['detail'] );
		$this->assertSame( 9, $decades[0]['done'] );
		$this->assertSame( 10, $decades[0]['total'] );
	}

	/**
	 * Seven decades are close, undated.
	 *
	 * @return void
	 */
	public function test_seven_decades_are_close(): void {
		$this->add_decades( array( 1905, 1915, 1925, 1935, 1945, 1955, 1965 ) );

		$decades = $this->get_close()['decades'];

		$this->assertCount( 1, $decades );
		$this->assertSame( 'Tour of the 20th century', $decades[0]['title'] );
		$this->assertSame( '7 decades of 10', $decades[0]['detail'] );
		$this->assertSame( '', $decades[0]['date'] );
		$this->assertSame( 7, $decades[0]['done'] );
		$this->assertSame( 10, $decades[0]['total'] );
	}

	/**
	 * Six decades are not close, however many books they have.
	 *
	 * @return void
	 */
	public function test_six_decades_are_not_close(): void {
		$this->add_decades( array( 1905, 1906, 1915, 1925, 1935, 1945, 1955 ) );

		$this->assertArrayNotHasKey( 'decades', $this->get_close() );
	}

	/**
	 * Add a book read once.
	 *
	 * @param string $title     Title.
	 * @param string $read_date Read date, Y-m-d.
	 * @param array  $meta      Other meta, such as library_published_date.
	 * @param string $added     Date added, Y-m-d H:i:s, or empty for now.
	 * @return int Book ID.
	 */
	private function add_read_book( string $title, string $read_date, array $meta = array(), string $added = '' ): int {
		return $this->add_book(
			array(
				'title' => $title,
				'meta'  => array( 'library_read_date' => array( $read_date ) ) + $meta,
			),
			$added
		);
	}

	/**
	 * Add a book read that first came out each year given, read one day after another from 1 January 2024.
	 *
	 * @param int[] $years Years the books first came out.
	 * @return void
	 */
	private function add_decades( array $years ): void {
		foreach ( array_values( $years ) as $index => $year ) {
			$read_date = gmdate( 'Y-m-d', strtotime( '2024-01-01 +' . $index . ' days' ) );

			$this->add_read_book( 'Book of ' . $year, $read_date, array( 'library_published_date' => (string) $year ) );
		}
	}
}
