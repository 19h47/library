<?php
/**
 * Tests of the completions: authors, series and their cycles, collections read in full, and collections read by half.
 *
 * @package Library
 */

/**
 * Authors from three books, series from two and collections from three read in full, each kind one achievement at the
 * level of its number of subjects; those with two books left or fewer are close; big collections read by half.
 */
class Test_Library_Achievements_Completions extends Library_TestCase {

	/**
	 * An author whose three books are read earns it, dated by the latest reading, and leads to the author.
	 *
	 * @return void
	 */
	public function test_an_author_read_in_full(): void {
		$this->add_reads( array( '2024-01-10', '2024-03-05', '2023-12-01' ), $this->by( 'Ursula K. Le Guin' ) );

		$author = $this->get_earned()['author'];

		$this->assertCount( 1, $author );
		$this->assertSame( '1 complete author', $author[0]['title'] );
		$this->assertSame( 'Ursula K. Le Guin', $author[0]['subject'] );
		$this->assertSame( '3 books', $author[0]['detail'] );
		$this->assertSame( '2024-03-05', $author[0]['date'] );
		$this->assertSame( 'author', $author[0]['target']['type'] );
		$this->assertSame( $this->get_person_id( 'Ursula K. Le Guin' ), $author[0]['target']['id'] );
	}

	/**
	 * Two books of an author are too few: neither earned nor close.
	 *
	 * @return void
	 */
	public function test_two_books_of_an_author_are_too_few(): void {
		$this->add_reads( array( '2024-01-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );

		$this->assertArrayNotHasKey( 'author', $this->get_earned() );
		$this->assertArrayNotHasKey( 'author', $this->get_close() );
	}

	/**
	 * An author with a book left is close, with the books read and the total, and names the book left.
	 *
	 * @return void
	 */
	public function test_an_author_with_a_book_left_is_close(): void {
		$this->add_reads( array( '2024-01-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( array( 'title' => 'The Dispossessed' ) + $this->by( 'Ursula K. Le Guin' ) );

		$this->assertArrayNotHasKey( 'author', $this->get_earned() );

		$author = $this->get_close()['author'];

		$this->assertCount( 1, $author );
		$this->assertSame( 'Complete author', $author[0]['title'] );
		$this->assertSame( 'Ursula K. Le Guin', $author[0]['subject'] );
		$this->assertSame( '1 book left: The Dispossessed', $author[0]['detail'] );
		$this->assertSame( '', $author[0]['date'] );
		$this->assertSame( 2, $author[0]['done'] );
		$this->assertSame( 3, $author[0]['total'] );
	}

	/**
	 * An author with two books left is close, and names one of them.
	 *
	 * @return void
	 */
	public function test_an_author_with_two_books_left_is_close(): void {
		$this->add_reads( array( '2024-01-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( array( 'title' => 'The Dispossessed' ) + $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( array( 'title' => 'Lavinia' ) + $this->by( 'Ursula K. Le Guin' ) );

		$author = $this->get_close()['author'];

		$this->assertCount( 1, $author );
		$this->assertContains( $author[0]['detail'], array( '2 books left, such as The Dispossessed', '2 books left, such as Lavinia' ) );
		$this->assertSame( 2, $author[0]['done'] );
		$this->assertSame( 4, $author[0]['total'] );
	}

	/**
	 * An author with three books left is not close.
	 *
	 * @return void
	 */
	public function test_an_author_with_three_books_left_is_not_close(): void {
		$this->add_reads( array( '2024-01-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );

		$this->assertArrayNotHasKey( 'author', $this->get_close() );
	}

	/**
	 * A book to buy is a book to read: the author read in full is close again.
	 *
	 * @return void
	 */
	public function test_a_book_to_buy_undoes_the_completion(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book(
			array(
				'title'  => 'Lavinia',
				'status' => 'library-to-buy',
			) + $this->by( 'Ursula K. Le Guin' )
		);

		$this->assertArrayNotHasKey( 'author', $this->get_earned() );

		$author = $this->get_close()['author'];

		$this->assertSame( '1 book left: Lavinia', $author[0]['detail'] );
		$this->assertSame( 3, $author[0]['done'] );
		$this->assertSame( 4, $author[0]['total'] );
	}

	/**
	 * A book not kept is read, so it counts in a completion.
	 *
	 * @return void
	 */
	public function test_a_book_not_kept_counts_as_read(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2024-03-05' ), array( 'status' => 'library-not-kept' ) + $this->by( 'Ursula K. Le Guin' ) );

		$author = $this->get_earned()['author'];

		$this->assertSame( '3 books', $author[0]['detail'] );
		$this->assertSame( '2024-03-05', $author[0]['date'] );
	}

	/**
	 * A collective book is not part of the work of its authors.
	 *
	 * @return void
	 */
	public function test_a_collective_book_is_not_part_of_the_work_of_its_authors(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2024-03-05' ), array( 'meta' => array( 'library_collective' => true ) ) + $this->by( 'Ursula K. Le Guin' ) );

		$this->assertArrayNotHasKey( 'author', $this->get_earned() );
		$this->assertArrayNotHasKey( 'author', $this->get_close() );
	}

	/**
	 * The books of the other names of an author count for the name they are known by.
	 *
	 * @return void
	 */
	public function test_the_other_names_of_an_author_count_for_the_name_known_by(): void {
		$known_by = wp_insert_term( 'Stephen King', 'library_person' );

		$this->assertNotWPError( $known_by );
		$this->assertNotWPError( wp_insert_term( 'Richard Bachman', 'library_person', array( 'parent' => $known_by['term_id'] ) ) );

		$this->add_reads( array( '2024-01-10', '2024-02-10' ), $this->by( 'Stephen King' ) );
		$this->add_reads( array( '2024-03-05' ), $this->by( 'Richard Bachman' ) );

		$author = $this->get_earned()['author'];

		$this->assertSame( 'Stephen King', $author[0]['subject'] );
		$this->assertSame( '3 books', $author[0]['detail'] );
		$this->assertSame( $known_by['term_id'], $author[0]['target']['id'] );
	}

	/**
	 * Authors of the same books are one completion, named together.
	 *
	 * @return void
	 */
	public function test_authors_of_the_same_books_are_named_together(): void {
		$this->add_reads(
			array( '2024-01-10', '2024-02-10', '2024-03-05' ),
			array(
				'people' => array(
					array(
						'name' => 'Terry Pratchett',
						'role' => 'author',
					),
					array(
						'name' => 'Neil Gaiman',
						'role' => 'author',
					),
				),
			)
		);

		$author = $this->get_earned()['author'];

		$this->assertCount( 1, $author );
		$this->assertSame( '1 complete author', $author[0]['title'] );
		$this->assertSame( 'Neil Gaiman and Terry Pratchett', $author[0]['subject'] );
	}

	/**
	 * A translator is not an author.
	 *
	 * @return void
	 */
	public function test_a_translator_is_not_an_author(): void {
		$this->add_reads(
			array( '2024-01-10', '2024-02-10', '2024-03-05' ),
			array(
				'people' => array(
					array(
						'name' => 'Philippe Rouard',
						'role' => 'translator',
					),
				),
			)
		);

		$this->assertArrayNotHasKey( 'author', $this->get_earned() );
	}

	/**
	 * Each author read in full counts in the level, which shows the latest one to be read in full.
	 *
	 * @return void
	 */
	public function test_the_level_shows_the_latest_author_read_in_full(): void {
		$this->add_reads( array( '2024-01-10', '2024-05-10', '2024-03-05' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2023-01-10', '2023-02-10', '2024-04-01' ), $this->by( 'Octavia E. Butler' ) );
		$this->add_reads( array( '2024-06-01', '2024-07-01' ), $this->by( 'Philip K. Dick' ) );

		$author = $this->get_earned()['author'];

		$this->assertCount( 1, $author );
		$this->assertSame( '2 complete authors', $author[0]['title'] );
		$this->assertSame( 'Ursula K. Le Guin', $author[0]['subject'] );
		$this->assertSame( '2024-05-10', $author[0]['date'] );
	}

	/**
	 * A completion whose readings are all of unknown date is earned, undated.
	 *
	 * @return void
	 */
	public function test_a_completion_read_on_unknown_dates_is_undated(): void {
		$this->add_reads( array( '', '', '' ), $this->by( 'Ursula K. Le Guin' ) );

		$author = $this->get_earned()['author'];

		$this->assertSame( '3 books', $author[0]['detail'] );
		$this->assertSame( '', $author[0]['date'] );
	}

	/**
	 * A series of two books read earns it, named after its cycles, dated by its latest reading.
	 *
	 * @return void
	 */
	public function test_a_series_read_in_full(): void {
		$this->add_reads( array( '2024-02-01', '2024-01-01' ), array( 'series' => 'Earthsea' ) );

		$series = $this->get_earned()['series'];

		$this->assertCount( 1, $series );
		$this->assertSame( '1 series finished', $series[0]['title'] );
		$this->assertSame( 'Earthsea', $series[0]['subject'] );
		$this->assertSame( '2 books', $series[0]['detail'] );
		$this->assertSame( '2024-02-01', $series[0]['date'] );
		$this->assertSame( 'series', $series[0]['target']['type'] );
	}

	/**
	 * A series of one book is too few: neither earned nor close.
	 *
	 * @return void
	 */
	public function test_a_series_of_one_book_is_too_few(): void {
		$this->add_reads( array( '2024-01-01' ), array( 'series' => 'Earthsea' ) );
		$this->add_book( array( 'series' => 'Dune' ) );

		$this->assertArrayNotHasKey( 'series', $this->get_earned() );
		$this->assertArrayNotHasKey( 'series', $this->get_close() );
	}

	/**
	 * A series with a book left is close.
	 *
	 * @return void
	 */
	public function test_a_series_with_a_book_left_is_close(): void {
		$this->add_reads( array( '2024-01-01' ), array( 'series' => 'Earthsea' ) );
		$this->add_book(
			array(
				'title'  => 'Tehanu',
				'series' => 'Earthsea',
			)
		);

		$series = $this->get_close()['series'];

		$this->assertSame( 'Series finished', $series[0]['title'] );
		$this->assertSame( 'Earthsea', $series[0]['subject'] );
		$this->assertSame( '1 book left: Earthsea: Tehanu', $series[0]['detail'] );
		$this->assertSame( 1, $series[0]['done'] );
		$this->assertSame( 2, $series[0]['total'] );
	}

	/**
	 * The same books count once: for their series, not for their author nor their collection.
	 *
	 * @return void
	 */
	public function test_the_same_books_count_once_for_their_series(): void {
		$this->add_reads(
			array( '2024-01-01', '2024-02-01', '2024-03-01' ),
			array(
				'series'     => 'Earthsea',
				'publisher'  => 'Gallimard',
				'collection' => 'Folio',
			) + $this->by( 'Ursula K. Le Guin' )
		);

		$earned = $this->get_earned();

		$this->assertSame( '1 series finished', $earned['series'][0]['title'] );
		$this->assertArrayNotHasKey( 'author', $earned );
		$this->assertArrayNotHasKey( 'collection', $earned );
	}

	/**
	 * The same books count once, close too: for their series, not for their author.
	 *
	 * @return void
	 */
	public function test_the_same_books_are_close_once(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), array( 'series' => 'Earthsea' ) + $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( array( 'series' => 'Earthsea' ) + $this->by( 'Ursula K. Le Guin' ) );

		$close = $this->get_close();

		$this->assertCount( 1, $close['series'] );
		$this->assertArrayNotHasKey( 'author', $close );
	}

	/**
	 * An author with a book out of the series counts besides the series.
	 *
	 * @return void
	 */
	public function test_an_author_with_more_books_than_the_series_counts_too(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), array( 'series' => 'Earthsea' ) + $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2024-03-01' ), $this->by( 'Ursula K. Le Guin' ) );

		$earned = $this->get_earned();

		$this->assertSame( '2 books', $earned['series'][0]['detail'] );
		$this->assertSame( '2024-02-01', $earned['series'][0]['date'] );
		$this->assertSame( '3 books', $earned['author'][0]['detail'] );
		$this->assertSame( '2024-03-01', $earned['author'][0]['date'] );
	}

	/**
	 * A cycle with a single series is the same books: it counts once, for the series named after its cycle.
	 *
	 * @return void
	 */
	public function test_a_cycle_of_one_series_counts_once(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), array( 'series' => 'Hainish, Ekumen' ) );

		$series = $this->get_earned()['series'];

		$this->assertSame( '1 series finished', $series[0]['title'] );
		$this->assertSame( 'Hainish, Ekumen', $series[0]['subject'] );
	}

	/**
	 * A cycle of two series read in full counts besides them, dated by its latest reading.
	 *
	 * @return void
	 */
	public function test_a_cycle_counts_besides_its_series(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), array( 'series' => 'Hainish, Ekumen' ) );
		$this->add_reads( array( '2024-03-01', '2024-04-01' ), array( 'series' => 'Hainish, Rocannon' ) );

		$series = $this->get_earned()['series'];

		$this->assertCount( 1, $series );
		$this->assertSame( '3 series finished', $series[0]['title'] );
		$this->assertSame( '2024-04-01', $series[0]['date'] );
	}

	/**
	 * A collection of three books read earns it, named without its publisher.
	 *
	 * @return void
	 */
	public function test_a_collection_read_in_full(): void {
		$this->add_reads( array( '2024-01-01', '2024-03-01', '2024-02-01' ), $this->in( 'Folio' ) );

		$collection = $this->get_earned()['collection'];

		$this->assertCount( 1, $collection );
		$this->assertSame( '1 collection finished', $collection[0]['title'] );
		$this->assertSame( 'Folio', $collection[0]['subject'] );
		$this->assertSame( '3 books', $collection[0]['detail'] );
		$this->assertSame( '2024-03-01', $collection[0]['date'] );
		$this->assertSame( 'collection', $collection[0]['target']['type'] );
	}

	/**
	 * Two books of a collection are too few.
	 *
	 * @return void
	 */
	public function test_two_books_of_a_collection_are_too_few(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), $this->in( 'Folio' ) );

		$this->assertArrayNotHasKey( 'collection', $this->get_earned() );
		$this->assertArrayNotHasKey( 'collection', $this->get_close() );
	}

	/**
	 * A publisher is not a collection.
	 *
	 * @return void
	 */
	public function test_a_publisher_is_not_a_collection(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01' ), array( 'publisher' => 'Gallimard' ) );

		$this->assertArrayNotHasKey( 'collection', $this->get_earned() );
	}

	/**
	 * A sub-collection counts for the collection it is part of, which counts too with books of its own.
	 *
	 * @return void
	 */
	public function test_a_sub_collection_counts_for_its_collection(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01' ), $this->in( 'Folio, Science-fiction' ) );
		$this->add_reads( array( '2024-04-01' ), $this->in( 'Folio' ) );

		$collection = $this->get_earned()['collection'];

		$this->assertSame( '2 collections finished', $collection[0]['title'] );
		$this->assertSame( 'Folio', $collection[0]['subject'] );
		$this->assertSame( '4 books', $collection[0]['detail'] );
		$this->assertSame( '2024-04-01', $collection[0]['date'] );
	}

	/**
	 * A sub-collection read in full is named alone, as a series is, not with the collection of the same books.
	 *
	 * @return void
	 */
	public function test_a_sub_collection_is_named_alone(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01' ), $this->in( 'Folio, Science-fiction' ) );

		$collection = $this->get_earned()['collection'];

		$this->assertSame( '1 collection finished', $collection[0]['title'] );
		$this->assertSame( 'Folio, Science-fiction', $collection[0]['subject'] );
	}

	/**
	 * Close completions come fewest left first, then the furthest along.
	 *
	 * @return void
	 */
	public function test_close_completions_come_fewest_left_first(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2024-01-01' ), array( 'series' => 'Dune' ) );
		$this->add_book( array( 'series' => 'Dune' ) );
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01' ), $this->in( 'Folio' ) );
		$this->add_book( $this->in( 'Folio' ) );

		$close = array_values(
			array_filter(
				new Library_Achievements()->get_achievements()['close'],
				fn( array $achievement ) => isset( Library_Achievements::KINDS['completions'][ $achievement['kind'] ] )
			)
		);

		$this->assertSame( array( 'collection', 'series', 'author' ), array_column( $close, 'kind' ) );
	}

	/**
	 * The completion of a term is the one of its books, close however many are left.
	 *
	 * @return void
	 */
	public function test_the_completion_of_a_term(): void {
		$this->add_reads( array( '2024-01-01' ), $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );
		$this->add_book( $this->by( 'Ursula K. Le Guin' ) );

		$completion = new Library_Achievements()->get_completion( 'author', $this->get_person_id( 'Ursula K. Le Guin' ) );

		$this->assertSame( 'Complete author', $completion['title'] );
		$this->assertSame( 1, $completion['done'] );
		$this->assertSame( 4, $completion['total'] );
	}

	/**
	 * The completion of a term read in full is dated by its latest reading, as the achievement is.
	 *
	 * @return void
	 */
	public function test_the_completion_of_a_term_read_in_full_is_dated(): void {
		$this->add_reads( array( '2024-01-01', '2024-03-01', '2024-02-01' ), $this->by( 'Ursula K. Le Guin' ) );

		$completion = new Library_Achievements()->get_completion( 'author', $this->get_person_id( 'Ursula K. Le Guin' ) );

		$this->assertSame( '2024-03-01', $completion['date'] );
	}

	/**
	 * A term with too few books, or a publisher, has no completion.
	 *
	 * @return void
	 */
	public function test_no_completion_for_too_few_books_or_a_publisher(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01' ), array( 'publisher' => 'Gallimard' ) + $this->by( 'Ursula K. Le Guin' ) );
		$this->add_reads( array( '2024-03-01' ), array( 'publisher' => 'Gallimard' ) );

		$achievements = new Library_Achievements();
		$publisher    = get_term_by( 'name', 'Gallimard', 'library_publisher' );

		$this->assertNull( $achievements->get_completion( 'author', $this->get_person_id( 'Ursula K. Le Guin' ) ) );
		$this->assertNull( $achievements->get_completion( 'collection', $publisher->term_id ) );
	}

	/**
	 * A collection of ten books read by half earns it, dated by the reading that reached half.
	 *
	 * @return void
	 */
	public function test_a_big_collection_read_by_half(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01', '2024-05-01', '2024-06-01' ), $this->in( 'Folio' ) );
		$this->add_books( 4, $this->in( 'Folio' ) );

		$step = $this->get_earned()['collection-step'];

		$this->assertCount( 1, $step );
		$this->assertSame( '1 collection read by half or more', $step[0]['title'] );
		$this->assertSame( 'Folio', $step[0]['subject'] );
		$this->assertSame( '6 read of 10', $step[0]['detail'] );
		$this->assertSame( '2024-05-01', $step[0]['date'] );
		$this->assertSame( 'collection', $step[0]['target']['type'] );
	}

	/**
	 * A collection of ten books read short of half earns nothing.
	 *
	 * @return void
	 */
	public function test_a_big_collection_read_short_of_half(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01' ), $this->in( 'Folio' ) );
		$this->add_books( 6, $this->in( 'Folio' ) );

		$this->assertArrayNotHasKey( 'collection-step', $this->get_earned() );
	}

	/**
	 * A collection of nine books is too small, read by half or not.
	 *
	 * @return void
	 */
	public function test_a_collection_of_nine_books_is_too_small(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01', '2024-05-01', '2024-06-01' ), $this->in( 'Folio' ) );
		$this->add_books( 3, $this->in( 'Folio' ) );

		$this->assertArrayNotHasKey( 'collection-step', $this->get_earned() );
	}

	/**
	 * Half of an odd number of books is rounded up: five of eleven is short, six is half.
	 *
	 * @return void
	 */
	public function test_half_of_an_odd_number_of_books_is_rounded_up(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01', '2024-05-01' ), $this->in( 'Folio' ) );
		$book_ids = $this->add_books( 6, $this->in( 'Folio' ) );

		$this->assertArrayNotHasKey( 'collection-step', $this->get_earned() );

		$this->read( $book_ids[0], '2024-06-01' );

		$step = $this->get_earned()['collection-step'];

		$this->assertSame( '6 read of 11', $step[0]['detail'] );
		$this->assertSame( '2024-06-01', $step[0]['date'] );
	}

	/**
	 * A sub-collection read by half counts once, for itself, not for the collection of the same books.
	 *
	 * @return void
	 */
	public function test_a_sub_collection_read_by_half_counts_once(): void {
		$this->add_reads( array( '2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01', '2024-05-01' ), $this->in( 'Folio, Science-fiction' ) );
		$this->add_books( 5, $this->in( 'Folio, Science-fiction' ) );

		$step = $this->get_earned()['collection-step'];

		$this->assertSame( '1 collection read by half or more', $step[0]['title'] );
		$this->assertSame( 'Folio, Science-fiction', $step[0]['subject'] );
	}

	/**
	 * A collection read in full is a completion, not a step.
	 *
	 * @return void
	 */
	public function test_a_big_collection_read_in_full_is_not_a_step(): void {
		$this->add_reads( self::get_days( '2024-01-01', 10 ), $this->in( 'Folio' ) );

		$earned = $this->get_earned();

		$this->assertArrayNotHasKey( 'collection-step', $earned );
		$this->assertSame( '10 books', $earned['collection'][0]['detail'] );
	}

	/**
	 * Each collection read by half counts in the level, which shows the latest one to reach half.
	 *
	 * @return void
	 */
	public function test_the_level_shows_the_latest_collection_to_reach_half(): void {
		$this->add_reads( self::get_days( '2024-01-01', 5 ), $this->in( 'Folio' ) );
		$this->add_books( 5, $this->in( 'Folio' ) );
		$this->add_reads( self::get_days( '2023-01-01', 5 ), $this->in( 'Présence du futur', 'Denoël' ) );
		$this->add_books( 5, $this->in( 'Présence du futur', 'Denoël' ) );

		$step = $this->get_earned()['collection-step'];

		$this->assertSame( '2 collections read by half or more', $step[0]['title'] );
		$this->assertSame( 'Folio', $step[0]['subject'] );
		$this->assertSame( '2024-01-05', $step[0]['date'] );
	}

	/**
	 * A book in a collection, under its publisher.
	 *
	 * @param string $collection Collection, such as "Folio, Science-fiction".
	 * @param string $publisher  Publisher.
	 * @return array
	 */
	private function in( string $collection, string $publisher = 'Gallimard' ): array {
		return array(
			'publisher'  => $publisher,
			'collection' => $collection,
		);
	}

	/**
	 * Add books to read, with the same fields.
	 *
	 * @param int   $count Number of books.
	 * @param array $book  Fields of each book, as add_book() takes them.
	 * @return int[] Book IDs.
	 */
	private function add_books( int $count, array $book ): array {
		return array_map( fn() => $this->add_book( $book ), range( 1, $count ) );
	}

	/**
	 * Read a book on a date, through the route the book form sends it to.
	 *
	 * @param int    $book_id   Book ID.
	 * @param string $read_date Read date, Y-m-d.
	 * @return void
	 */
	private function read( int $book_id, string $read_date ): void {
		$request = new WP_REST_Request( 'POST', '/library/v1/books/' . $book_id );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta' => array(
						'library_read'      => true,
						'library_read_date' => array( $read_date ),
					),
				)
			)
		);

		$this->assertSame( 200, rest_do_request( $request )->get_status() );

		new ReflectionProperty( Library_Books::class, 'books' )->setValue( null, null );
	}

	/**
	 * Term ID of a person.
	 *
	 * @param string $name Name.
	 * @return int
	 */
	private function get_person_id( string $name ): int {
		return get_term_by( 'name', $name, 'library_person' )->term_id;
	}
}
