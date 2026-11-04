<?php
/**
 * Tests of the reading idea: the kinds of ideas, each one with its reason, and the list of the week they make.
 *
 * @package Library
 */

/**
 * The books suggested by each kind of idea and their reasons, then the list of the week, which loops.
 *
 * The order of the kinds turns with the week and each kind is shuffled by week: the list is read as a set of books.
 */
class Test_Library_Reading_Idea extends Library_TestCase {

	/**
	 * Kinds of ideas, in the order the list takes them from the first kind of the week.
	 *
	 * @var string[]
	 */
	private const array KINDS = array( 'series', 'author', 'unread_author', 'translator', 'collection', 'anniversary', 'change', 'short', 'fresh', 'reread', 'pile', 'to_buy' );

	/**
	 * The first volume not read of a series with volumes read comes next, with the volumes read and the year of the last one.
	 *
	 * @return void
	 */
	public function test_series_suggests_the_first_volume_not_read(): void {
		$this->add_volume( 'Earthsea', 1, array( '2020-05-01' ) );
		$this->add_volume( 'Earthsea', 2, array( '2022-03-01' ) );
		$next = $this->add_volume( 'Earthsea', 3 );
		$this->add_volume( 'Earthsea', 4 );

		$this->assertSame(
			array( $next => array( 'Next in Earthsea: you have read 2 volumes, the last one in 2022.' ) ),
			$this->get_ideas_of( 'series' )
		);
	}

	/**
	 * A volume without series is one of the series named by its title: each title comes next on its own.
	 *
	 * @return void
	 */
	public function test_volumes_without_series_are_of_their_title(): void {
		$volume      = fn( string $title, int $number, array $meta = array() ) => $this->add_book(
			array(
				'title' => $title,
				'meta'  => array( 'library_volume_number' => sprintf( 'Tome %02d', $number ) ) + $meta,
			)
		);
		$city_hunter = $volume( 'City Hunter', 3 );
		$akira       = $volume( 'Akira', 1 );
		$volume( 'Akira', 2, array( 'library_read_date' => array( '2024-01-01' ) ) );

		$this->assertSame( array( $akira => array( 'Next in Akira: you have read 1 volume, in 2024.' ) ), $this->get_ideas_of( 'series' ) );
		$this->assertArrayHasKey( $city_hunter, $this->get_ideas_of( 'pile' ) );
	}

	/**
	 * One volume read says it in the singular, with the year it was read.
	 *
	 * @return void
	 */
	public function test_series_after_one_volume_read(): void {
		$this->add_volume( 'Earthsea', 1, array( '2021-07-14' ) );
		$next = $this->add_volume( 'Earthsea', 2 );

		$this->assertSame(
			array( $next => array( 'Next in Earthsea: you have read 1 volume, in 2021.' ) ),
			$this->get_ideas_of( 'series' )
		);
	}

	/**
	 * A volume read on an unknown date gives no year.
	 *
	 * @return void
	 */
	public function test_series_read_on_an_unknown_date(): void {
		$this->add_volume( 'Earthsea', 1, array( '' ) );
		$next = $this->add_volume( 'Earthsea', 2 );

		$this->assertSame(
			array( $next => array( 'Next in Earthsea: you have read 1 volume.' ) ),
			$this->get_ideas_of( 'series' )
		);
	}

	/**
	 * A series none of whose volumes is read is not one in progress.
	 *
	 * @return void
	 */
	public function test_series_without_volume_read(): void {
		$this->add_volume( 'Earthsea', 1 );
		$this->add_volume( 'Earthsea', 2 );

		$this->assertSame( array(), $this->get_ideas_of( 'series' ) );
	}

	/**
	 * The next volume to buy blocks the ones after it: none of them is at hand to read next.
	 *
	 * @return void
	 */
	public function test_series_waits_for_the_next_volume_to_buy(): void {
		$this->add_volume( 'Earthsea', 1, array( '2021-07-14' ) );
		$this->add_volume( 'Earthsea', 2, array(), array( 'status' => Library_Posts::TO_BUY ) );
		$this->add_volume( 'Earthsea', 3 );

		$this->assertSame( array(), $this->get_ideas_of( 'series' ) );
	}

	/**
	 * Once a part of a cycle is read in full, the first volume of the next part is next in the cycle.
	 *
	 * @return void
	 */
	public function test_series_goes_on_with_the_next_part_of_a_cycle(): void {
		$this->add_volume( 'Dune, Dune', 1, array( '2019-02-10' ), array( 'meta' => array( 'library_published_date' => '1965' ) ) );
		$this->add_volume( 'Dune, Dune', 2, array( '2020-08-30' ), array( 'meta' => array( 'library_published_date' => '1969' ) ) );
		$next = $this->add_volume( 'Dune, Prelude to Dune', 1, array(), array( 'meta' => array( 'library_published_date' => '1999' ) ) );
		$this->add_volume( 'Dune, Prelude to Dune', 2, array(), array( 'meta' => array( 'library_published_date' => '2000' ) ) );

		$this->assertSame(
			array( $next => array( 'Next in Dune: you have read 2 volumes, the last one in 2020.' ) ),
			$this->get_ideas_of( 'series' )
		);
	}

	/**
	 * A part of a cycle after one not read in full waits: the part in progress comes next.
	 *
	 * @return void
	 */
	public function test_series_keeps_to_the_part_in_progress(): void {
		$this->add_volume( 'Dune, Dune', 1, array( '2019-02-10' ), array( 'meta' => array( 'library_published_date' => '1965' ) ) );
		$next = $this->add_volume( 'Dune, Dune', 2, array(), array( 'meta' => array( 'library_published_date' => '1969' ) ) );
		$this->add_volume( 'Dune, Prelude to Dune', 1, array(), array( 'meta' => array( 'library_published_date' => '1999' ) ) );

		$this->assertSame(
			array( $next => array( 'Next in Dune, Dune: you have read 1 volume, in 2019.' ) ),
			$this->get_ideas_of( 'series' )
		);
	}

	/**
	 * A book to read by an author rated well, on average over two books rated at least.
	 *
	 * @return void
	 */
	public function test_author_rated_well(): void {
		$year = (int) wp_date( 'Y' ) - 1;

		$this->add_rated( 'Ursula K. Le Guin', 9, $year . '-03-01' );
		$this->add_rated( 'Ursula K. Le Guin', 8, $year . '-04-01' );
		$book = $this->add_by( 'Ursula K. Le Guin' );

		$this->assertSame(
			array( $book => array( 'You gave Ursula K. Le Guin 8.5/10 on average over 2 books.' ) ),
			$this->get_ideas_of( 'author' )
		);
	}

	/**
	 * An author rated well not read for years says when they were last read.
	 *
	 * @return void
	 */
	public function test_author_rated_well_not_read_for_years(): void {
		$year = (int) wp_date( 'Y' ) - 5;

		$this->add_rated( 'Ursula K. Le Guin', 9, '2001-03-01' );
		$this->add_rated( 'Ursula K. Le Guin', 9, $year . '-04-01' );
		$book = $this->add_by( 'Ursula K. Le Guin' );

		$this->assertSame(
			array( $book => array( 'You gave Ursula K. Le Guin 9/10 on average over 2 books, the last one read in ' . $year . '.' ) ),
			$this->get_ideas_of( 'author' )
		);
	}

	/**
	 * An average under 8, or a single book rated, does not make an author rated well.
	 *
	 * @return void
	 */
	public function test_author_not_rated_well(): void {
		$this->add_rated( 'Ursula K. Le Guin', 7, '2024-03-01' );
		$this->add_rated( 'Ursula K. Le Guin', 8, '2024-04-01' );
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_rated( 'Frank Herbert', 10, '2024-05-01' );
		$this->add_by( 'Frank Herbert' );

		$this->assertSame( array(), $this->get_ideas_of( 'author' ) );
	}

	/**
	 * Collective books count for none of their authors, and are not suggested for them.
	 *
	 * @return void
	 */
	public function test_author_leaves_out_collective_books(): void {
		$collective = array( 'meta' => array( 'library_collective' => true ) );

		$this->add_rated( 'Ursula K. Le Guin', 10, '2024-03-01', $collective );
		$this->add_rated( 'Ursula K. Le Guin', 10, '2024-04-01', $collective );
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_rated( 'Frank Herbert', 9, '2024-03-01' );
		$this->add_rated( 'Frank Herbert', 9, '2024-04-01' );
		$this->add_by( 'Frank Herbert', $collective );

		$this->assertSame( array(), $this->get_ideas_of( 'author' ) );
	}

	/**
	 * The best rated of the authors of a book gives the reason.
	 *
	 * @return void
	 */
	public function test_author_best_rated_gives_the_reason(): void {
		$this->add_rated( 'Frank Herbert', 8, '2024-03-01' );
		$this->add_rated( 'Frank Herbert', 8, '2024-04-01' );
		$this->add_rated( 'Brian Herbert', 10, '2024-05-01' );
		$this->add_rated( 'Brian Herbert', 10, '2024-06-01' );
		$book = $this->add_book(
			array(
				'people' => array(
					array(
						'name' => 'Frank Herbert',
						'role' => 'author',
					),
					array(
						'name' => 'Brian Herbert',
						'role' => 'author',
					),
				),
			)
		);

		$this->assertSame(
			array( $book => array( 'You gave Brian Herbert 10/10 on average over 2 books.' ) ),
			$this->get_ideas_of( 'author' )
		);
	}

	/**
	 * Three books at hand by an author and none read suggest each of them.
	 *
	 * @return void
	 */
	public function test_unread_author_with_three_books(): void {
		$books = array( $this->add_by( 'Ursula K. Le Guin' ), $this->add_by( 'Ursula K. Le Guin' ), $this->add_by( 'Ursula K. Le Guin' ) );

		$this->assertSame(
			array_fill_keys( $books, array( 'You have 3 books by Ursula K. Le Guin, and none read.' ) ),
			$this->get_ideas_of( 'unread_author' )
		);
	}

	/**
	 * Two books do not stand out, nor do books to buy or collective ones count.
	 *
	 * @return void
	 */
	public function test_unread_author_counts_the_books_at_hand(): void {
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_by( 'Ursula K. Le Guin', array( 'status' => Library_Posts::TO_BUY ) );
		$this->add_by( 'Ursula K. Le Guin', array( 'meta' => array( 'library_collective' => true ) ) );

		$this->assertSame( array(), $this->get_ideas_of( 'unread_author' ) );
	}

	/**
	 * An author with a book read is not unread.
	 *
	 * @return void
	 */
	public function test_unread_author_with_a_book_read(): void {
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_by( 'Ursula K. Le Guin' );
		$this->add_by( 'Ursula K. Le Guin', array( 'meta' => array( 'library_read_date' => array( '2024-03-01' ) ) ) );

		$this->assertSame( array(), $this->get_ideas_of( 'unread_author' ) );
	}

	/**
	 * A book to read translated by a translator rated well.
	 *
	 * @return void
	 */
	public function test_translator_rated_well(): void {
		foreach ( array( 8, 10, 7 ) as $rating ) {
			$this->add_book( $this->translated() + array( 'meta' => $this->rated( $rating, '2024-03-01' ) ) );
		}

		$book = $this->add_book( $this->translated() );
		$this->add_by( 'Pierre-Paul Durastanti' );

		$this->assertSame(
			array( $book => array( 'Translated by Pierre-Paul Durastanti: 8.3/10 on average over 3 books rated.' ) ),
			$this->get_ideas_of( 'translator' )
		);
	}

	/**
	 * A book to read in a collection rated well, or in a sub-collection of it.
	 *
	 * @return void
	 */
	public function test_collection_rated_well(): void {
		$in = fn( string $collection, array $meta = array() ) => array(
			'publisher'  => 'Gallimard',
			'collection' => $collection,
			'meta'       => $meta,
		);

		$this->add_book( $in( 'Folio', $this->rated( 9, '2024-03-01' ) ) );
		$this->add_book( $in( 'Folio', $this->rated( 9, '2024-04-01' ) ) );
		$book           = $this->add_book( $in( 'Folio' ) );
		$sub_collection = $this->add_book( $in( 'Folio, Science-fiction' ) );
		$this->add_book( $in( 'Blanche' ) );

		$this->assertSame(
			array(
				$book           => array( 'In Folio: 9/10 on average over 2 books rated.' ),
				$sub_collection => array( 'In Folio: 9/10 on average over 2 books rated.' ),
			),
			$this->get_ideas_of( 'collection' )
		);
	}

	/**
	 * A book first published, or else whose edition came out, this month a round number of years ago.
	 *
	 * @return void
	 */
	public function test_anniversary_of_the_work_or_the_edition(): void {
		$year  = (int) wp_date( 'Y' );
		$month = wp_date( 'm' );

		$work    = $this->add_book( array( 'meta' => array( 'library_published_date' => ( $year - 20 ) . "-$month-15" ) ) );
		$edition = $this->add_book( array( 'meta' => array( 'library_edition_date' => ( $year - 10 ) . "-$month-15" ) ) );
		$both    = $this->add_book(
			array(
				'meta' => array(
					'library_published_date' => ( $year - 30 ) . "-$month",
					'library_edition_date'   => ( $year - 10 ) . "-$month-15",
				),
			)
		);

		$this->assertSame(
			array(
				$work    => array( 'First published 20 years ago this month.' ),
				$edition => array( 'This edition came out 10 years ago this month.' ),
				$both    => array( 'First published 30 years ago this month.' ),
			),
			$this->get_ideas_of( 'anniversary' )
		);
	}

	/**
	 * No anniversary for a number of years not round, another month, a year without month, a first of January, or a book read.
	 *
	 * @return void
	 */
	public function test_anniversary_leaves_out_other_dates(): void {
		$year  = (int) wp_date( 'Y' );
		$month = wp_date( 'm' );
		$other = sprintf( '%02d', (int) $month % 12 + 1 );

		foreach ( array( ( $year - 15 ) . "-$month-15", ( $year - 10 ) . "-$other-15", "$year-$month-01", (string) ( $year - 10 ), ( $year - 10 ) . '-01-01' ) as $date ) {
			$this->add_book( array( 'meta' => array( 'library_published_date' => $date ) ) );
		}

		$this->add_book(
			array(
				'meta' => array(
					'library_published_date' => ( $year - 10 ) . "-$month-15",
					'library_read_date'      => array( '2024-03-01' ),
				),
			)
		);

		$this->assertSame( array(), $this->get_ideas_of( 'anniversary' ) );
	}

	/**
	 * A book first published in a decade with no book read, and not one of a decade read or without date.
	 *
	 * @return void
	 */
	public function test_change_to_a_decade_not_read(): void {
		$this->add_book(
			array(
				'meta' => array(
					'library_published_date' => '1994',
					'library_read_date'      => array( '2024-03-01' ),
				),
			)
		);
		$sixties = $this->add_book( array( 'meta' => array( 'library_published_date' => '1962-05' ) ) );
		$this->add_book( array( 'meta' => array( 'library_published_date' => '1997-10-02' ) ) );
		$this->add_book( array() );

		$this->assertSame(
			array( $sixties => array( 'You have not read any book first published in the 1960s yet.' ) ),
			$this->get_ideas_of( 'change' )
		);
	}

	/**
	 * After six reads all translated, a book not translated.
	 *
	 * @return void
	 */
	public function test_change_after_reads_all_translated(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10', '2024-03-10', '2024-04-10', '2024-05-10', '2024-06-10' ), $this->translated() );
		$book = $this->add_book( array() );
		$this->add_book( $this->translated() );

		$this->assertSame(
			array( $book => array( 'Your last 6 reads were all translated.' ) ),
			$this->get_ideas_of( 'change' )
		);
	}

	/**
	 * After six reads none translated and all volumes of a series, a book translated and one not a volume.
	 *
	 * @return void
	 */
	public function test_change_after_reads_none_translated_all_volumes(): void {
		foreach ( range( 1, 6 ) as $volume ) {
			$this->add_volume( 'Earthsea', $volume, array( "2024-0$volume-10" ) );
		}

		$this->add_volume( 'Earthsea', 7 );
		$single     = $this->add_book( array() );
		$translated = $this->add_book( $this->translated() );

		$this->assertSame(
			array(
				$single     => array( 'Your last 6 reads were all volumes of a series.' ),
				$translated => array( 'None of your last 6 reads was translated.', 'Your last 6 reads were all volumes of a series.' ),
			),
			$this->get_ideas_of( 'change' )
		);
	}

	/**
	 * Reads mixed, some translated and some not, suggest nothing for translation.
	 *
	 * @return void
	 */
	public function test_change_after_reads_mixed(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10', '2024-03-10' ), $this->translated() );
		$this->add_reads( array( '2024-04-10', '2024-05-10', '2024-06-10' ) );
		$this->add_book( array() );

		$this->assertSame( array(), $this->get_ideas_of( 'change' ) );
	}

	/**
	 * Fewer than six reads are no trend.
	 *
	 * @return void
	 */
	public function test_change_needs_six_reads(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10', '2024-03-10', '2024-04-10', '2024-05-10' ), $this->translated() );
		$this->add_book( array() );

		$this->assertSame( array(), $this->get_ideas_of( 'change' ) );
	}

	/**
	 * Only the latest six readings count, a book read again counting once per reading.
	 *
	 * @return void
	 */
	public function test_change_counts_the_latest_six_readings(): void {
		$this->add_reads( array( '2010-01-10' ) );
		$this->add_book( $this->translated() + array( 'meta' => array( 'library_read_date' => array( '2024-01-10', '2024-02-10', '2024-03-10', '2024-04-10', '2024-05-10', '2024-06-10' ) ) ) );
		$book = $this->add_book( array() );

		$this->assertSame(
			array( $book => array( 'Your last 6 reads were all translated.' ) ),
			$this->get_ideas_of( 'change' )
		);
	}

	/**
	 * Fewer books read this year than by this date last year: the books to read of 200 pages at most, to catch up.
	 *
	 * The pace is kept for the process, once read: the test runs in a process of its own.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_short_books_to_catch_up(): void {
		$year = (int) wp_date( 'Y' );

		$this->add_reads( array( ( $year - 1 ) . '-01-01', ( $year - 1 ) . '-01-01', "$year-01-01" ) );
		$short   = $this->add_book( array( 'meta' => array( 'library_pages' => 150 ) ) );
		$at_most = $this->add_book( array( 'meta' => array( 'library_pages' => 200 ) ) );
		$this->add_book( array( 'meta' => array( 'library_pages' => 201 ) ) );
		$this->add_book( array() );

		$this->assertSame(
			array(
				$short   => array( '150 pages, to catch up: 1 book read this year against 2 by this date last year.' ),
				$at_most => array( '200 pages, to catch up: 1 book read this year against 2 by this date last year.' ),
			),
			$this->get_ideas_of( 'short' )
		);
	}

	/**
	 * As many books read this year as by this date last year: no need to catch up.
	 *
	 * The pace is kept for the process, once read: the test runs in a process of its own.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_short_books_when_not_behind(): void {
		$year = (int) wp_date( 'Y' );

		$this->add_reads( array( ( $year - 1 ) . '-01-01', "$year-01-01" ) );
		$this->add_book( array( 'meta' => array( 'library_pages' => 150 ) ) );

		$this->assertSame( array(), $this->get_ideas_of( 'short' ) );
	}

	/**
	 * A book to read added within 30 days, with the time since; not one added before, read or to buy.
	 *
	 * @return void
	 */
	public function test_fresh_books_added_lately(): void {
		$days_ago = fn( int $days ) => wp_date( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );

		$book = $this->add_book( array(), $days_ago( 5 ) );
		$this->add_book( array(), $days_ago( 40 ) );
		$this->add_book( array( 'meta' => array( 'library_read_date' => array( '2024-03-01' ) ) ), $days_ago( 2 ) );
		$this->add_book( array( 'status' => Library_Posts::TO_BUY ), $days_ago( 2 ) );

		$this->assertSame(
			array( $book => array( 'Added 5 days ago.' ) ),
			$this->get_ideas_of( 'fresh' )
		);
	}

	/**
	 * A book rated 9 at least and last read 10 years ago at least, kept or not, to read again.
	 *
	 * @return void
	 */
	public function test_reread_books_rated_well_long_ago(): void {
		$year = (int) wp_date( 'Y' ) - 10;

		$kept     = $this->add_rated( 'Frank Herbert', 9, "$year-12-31" );
		$not_kept = $this->add_rated( 'Frank Herbert', 9.5, '2001-06-01', array( 'status' => Library_Posts::NOT_KEPT ) );
		$this->add_rated( 'Frank Herbert', 10, ( $year + 1 ) . '-01-01' );
		$this->add_rated( 'Frank Herbert', 8.5, '2001-06-01' );
		$this->add_book( array( 'meta' => array( 'library_read_date' => array( '2001-06-01' ) ) ) );
		$this->add_book(
			array(
				'meta' => array(
					'library_rating'    => 10,
					'library_read_date' => array( '2001-06-01', '2024-06-01' ),
				),
			)
		);

		$this->assertSame(
			array(
				$kept     => array( "Rated 9/10, read in $year: read it again?" ),
				$not_kept => array( 'Rated 9.5/10, read in 2001: read it again?' ),
			),
			$this->get_ideas_of( 'reread' )
		);
	}

	/**
	 * Every book to read, with the year it was added, or as borrowed when not kept; not a book read, to buy, or a volume after the next one.
	 *
	 * @return void
	 */
	public function test_pile_of_books_to_read(): void {
		$kept     = $this->add_book( array(), '2019-04-02 10:00:00' );
		$not_kept = $this->add_book( array( 'status' => Library_Posts::NOT_KEPT ) );
		$next     = $this->add_volume( 'Earthsea', 1, array(), array(), '2021-09-12 10:00:00' );
		$this->add_volume( 'Earthsea', 2 );
		$this->add_book( array( 'status' => Library_Posts::TO_BUY ) );
		$this->add_book( array( 'meta' => array( 'library_read_date' => array( '2024-03-01' ) ) ) );

		$this->assertSame(
			array(
				$kept     => array( 'Waiting on the reading pile since 2019.' ),
				$not_kept => array( 'Not kept, still to read: borrowed?' ),
				$next     => array( 'Waiting on the reading pile since 2021.' ),
			),
			$this->get_ideas_of( 'pile' )
		);
	}

	/**
	 * The next volume to buy of a series with a volume read, not the ones after it nor one of a series not started.
	 *
	 * @return void
	 */
	public function test_to_buy_next_volume(): void {
		$to_buy = array( 'status' => Library_Posts::TO_BUY );

		$this->add_volume( 'Earthsea', 1, array( '2024-03-01' ) );
		$this->add_volume( 'Earthsea', 2, array( '2024-04-01' ) );
		$next = $this->add_volume( 'Earthsea', 3, array(), $to_buy );
		$this->add_volume( 'Earthsea', 4, array(), $to_buy );
		$this->add_volume( 'Dune', 1, array(), $to_buy );

		$this->assertSame(
			array( $next => array( 'To buy, next in Earthsea: you have read 2 volumes.' ) ),
			$this->get_ideas_of( 'to_buy' )
		);
	}

	/**
	 * Once a part of a cycle is read in full, the first volume to buy of the next part is next in the cycle.
	 *
	 * @return void
	 */
	public function test_to_buy_the_next_part_of_a_cycle(): void {
		$to_buy = array(
			'status' => Library_Posts::TO_BUY,
			'meta'   => array( 'library_published_date' => '1999' ),
		);

		$this->add_volume( 'Dune, Dune', 1, array( '2019-02-10' ), array( 'meta' => array( 'library_published_date' => '1965' ) ) );
		$this->add_volume( 'Dune, Dune', 2, array( '2020-08-30' ), array( 'meta' => array( 'library_published_date' => '1969' ) ) );
		$next = $this->add_volume( 'Dune, Prelude to Dune', 1, array(), $to_buy );

		$this->assertSame(
			array( $next => array( 'To buy, next in Dune: you have read 2 volumes.' ) ),
			$this->get_ideas_of( 'to_buy' )
		);
	}

	/**
	 * A book to buy by an author rated well; the next volume of a series gives its reason first.
	 *
	 * @return void
	 */
	public function test_to_buy_by_an_author_rated_well(): void {
		$to_buy = array( 'status' => Library_Posts::TO_BUY );

		$this->add_rated( 'Ursula K. Le Guin', 9, '2024-03-01' );
		$this->add_rated( 'Ursula K. Le Guin', 9, '2024-04-01' );
		$this->add_volume( 'Earthsea', 1, array( '2024-05-01' ) );
		$book   = $this->add_by( 'Ursula K. Le Guin', $to_buy );
		$volume = $this->add_volume( 'Earthsea', 2, array(), $to_buy + $this->by( 'Ursula K. Le Guin' ) );
		$this->add_by( 'Frank Herbert', $to_buy );
		$this->add_by( 'Ursula K. Le Guin' );

		$this->assertSame(
			array(
				$book   => array( 'To buy, by Ursula K. Le Guin: you gave them 9/10 on average over 2 books.' ),
				$volume => array( 'To buy, next in Earthsea: you have read 1 volume.' ),
			),
			$this->get_ideas_of( 'to_buy' )
		);
	}

	/**
	 * Nothing to suggest gives no idea: no book, or only books read with nothing to read again.
	 *
	 * @return void
	 */
	public function test_no_idea_without_book_to_suggest(): void {
		$this->assertNull( new Library_Reading_Idea()->get_idea() );

		$this->add_reads( array( '2024-03-01', '2024-04-01' ) );

		$this->assertNull( new Library_Reading_Idea()->get_idea() );
	}

	/**
	 * The list loops: a position past its end starts it again, with the total and the week.
	 *
	 * @return void
	 */
	public function test_the_list_loops_by_position(): void {
		$books = array( $this->add_book( array() ), $this->add_book( array() ), $this->add_book( array() ) );

		$reading_idea = new Library_Reading_Idea();
		$first        = $reading_idea->get_idea();
		$again        = $reading_idea->get_idea( 4 );

		$this->assertContains( $first['id'], $books );
		$this->assertSame( 0, $first['position'] );
		$this->assertSame( 3, $first['total'] );
		$this->assertSame( wp_date( 'o-\WW' ), $first['week'] );
		$this->assertSame( $reading_idea->get_idea( 1 ), $again );
		$this->assertSame( 1, $again['position'] );
	}

	/**
	 * Each book suggested comes once in the list, however many kinds suggest it.
	 *
	 * @return void
	 */
	public function test_each_book_comes_once(): void {
		$this->add_volume( 'Earthsea', 1, array( '2024-03-01' ), $this->by( 'Ursula K. Le Guin' ) + array( 'meta' => array( 'library_rating' => 9 ) ) );
		$this->add_volume( 'Earthsea', 2, array( '2024-04-01' ), $this->by( 'Ursula K. Le Guin' ) + array( 'meta' => array( 'library_rating' => 9 ) ) );
		$next   = $this->add_volume( 'Earthsea', 3, array(), $this->by( 'Ursula K. Le Guin' ) );
		$book   = $this->add_by( 'Ursula K. Le Guin' );
		$to_buy = $this->add_volume( 'Earthsea', 4, array(), array( 'status' => Library_Posts::TO_BUY ) );
		$reread = $this->add_rated( 'Frank Herbert', 10, '2001-06-01' );

		$ideas = $this->get_all_ideas();

		$this->assertEqualsCanonicalizing( array( $next, $book, $reread ), array_keys( $ideas ) );
		$this->assertSame( array( 1, 1, 1 ), array_values( array_map( 'count', $ideas ) ) );
		$this->assertArrayNotHasKey( $to_buy, $ideas );
	}

	/**
	 * A book suggested by two kinds comes with the reason of the first one in this week's turn.
	 *
	 * @return void
	 */
	public function test_the_first_kind_of_the_week_gives_the_reason(): void {
		$year = (int) wp_date( 'Y' ) - 10;

		// A book read of the same decade, so that the decade is not one to change to.
		$this->add_book(
			array(
				'meta' => array(
					'library_published_date' => "$year-01-01",
					'library_read_date'      => array( '2024-03-01' ),
				),
			),
			'2015-03-10 10:00:00'
		);
		$book = $this->add_book( array( 'meta' => array( 'library_published_date' => "$year-" . wp_date( 'm' ) . '-15' ) ), '2015-03-10 10:00:00' );

		$first  = (int) wp_date( 'W' ) % count( self::KINDS );
		$turn   = fn( string $kind ) => ( array_search( $kind, self::KINDS, true ) - $first + count( self::KINDS ) ) % count( self::KINDS );
		$reason = $turn( 'anniversary' ) < $turn( 'pile' ) ? 'First published 10 years ago this month.' : 'Waiting on the reading pile since 2015.';

		$this->assertSame( array( $book => array( $reason ) ), $this->get_all_ideas() );
	}

	/**
	 * Ideas of a kind, as the list of the week takes them before shuffling: the reasons of each book suggested, by book ID.
	 *
	 * @param string $kind Kind, such as series: its method is get_series_ideas().
	 * @return array<int,string[]>
	 */
	private function get_ideas_of( string $kind ): array {
		$books   = Library_Books::get_books();
		$to_read = new ReflectionMethod( Library_Reading_Idea::class, 'get_books_to_read' )->invoke( null, $books );
		$method  = new ReflectionMethod( Library_Reading_Idea::class, 'get_' . $kind . '_ideas' );
		$args    = array_map( fn( ReflectionParameter $parameter ) => 'books' === $parameter->getName() ? $books : $to_read, $method->getParameters() );
		$reasons = array();

		foreach ( $method->invokeArgs( new Library_Reading_Idea(), $args ) as $idea ) {
			$reasons[ $idea['id'] ][] = $idea['reason'];
		}

		ksort( $reasons );

		return $reasons;
	}

	/**
	 * Every idea of the week's list, from position 0 to its total: the reasons of each book, by book ID.
	 *
	 * @return array<int,string[]>
	 */
	private function get_all_ideas(): array {
		$reading_idea = new Library_Reading_Idea();
		$total        = $reading_idea->get_idea()['total'] ?? 0;
		$reasons      = array();

		for ( $position = 0; $position < $total; $position++ ) {
			$idea = $reading_idea->get_idea( $position );

			$reasons[ $idea['id'] ][] = $idea['reason'];
		}

		ksort( $reasons );

		return $reasons;
	}

	/**
	 * Add a volume of a series, read on the dates given.
	 *
	 * @param string   $series     Series, after its cycles.
	 * @param int      $volume     Volume number.
	 * @param string[] $read_dates Read dates, Y-m-d, or none when not read.
	 * @param array    $book       Other fields, as add_book() takes them.
	 * @param string   $added      Date added, Y-m-d H:i:s, or empty for now.
	 * @return int Book ID.
	 */
	private function add_volume( string $series, int $volume, array $read_dates = array(), array $book = array(), string $added = '' ): int {
		$volume_fields = array(
			'series' => $series,
			'meta'   => array( 'library_volume_number' => sprintf( 'Tome %02d', $volume ) ),
		);
		$read_fields   = $read_dates ? array( 'meta' => array( 'library_read_date' => $read_dates ) ) : array();

		return $this->add_book( array_replace_recursive( $volume_fields, $read_fields, $book ), $added );
	}

	/**
	 * Add a book by an author, rated and read on a date.
	 *
	 * @param string $author    Author.
	 * @param float  $rating    Rating, out of 10.
	 * @param string $read_date Read date, Y-m-d.
	 * @param array  $book      Other fields, as add_book() takes them.
	 * @return int Book ID.
	 */
	private function add_rated( string $author, float $rating, string $read_date, array $book = array() ): int {
		return $this->add_book( array_replace_recursive( $this->by( $author ), array( 'meta' => $this->rated( $rating, $read_date ) ), $book ) );
	}

	/**
	 * Meta of a book rated and read on a date.
	 *
	 * @param float  $rating    Rating, out of 10.
	 * @param string $read_date Read date, Y-m-d.
	 * @return array
	 */
	private function rated( float $rating, string $read_date ): array {
		return array(
			'library_rating'    => $rating,
			'library_read_date' => array( $read_date ),
		);
	}

	/**
	 * Add a book by an author, not read.
	 *
	 * @param string $author Author.
	 * @param array  $book   Other fields, as add_book() takes them.
	 * @return int Book ID.
	 */
	private function add_by( string $author, array $book = array() ): int {
		return $this->add_book( array_replace_recursive( $this->by( $author ), $book ) );
	}

	/**
	 * Fields of a book translated.
	 *
	 * @return array
	 */
	private function translated(): array {
		return array(
			'people' => array(
				array(
					'name' => 'Pierre-Paul Durastanti',
					'role' => 'translator',
				),
			),
		);
	}
}
