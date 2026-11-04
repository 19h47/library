<?php
/**
 * Tests of the Library widget of the dashboard: the books at hand, then the terms they count for.
 *
 * @package Library
 */

/**
 * The forms of the books, every one of them, then the authors, publishers, collections and languages most present.
 */
class Test_Library_Dashboard_Collection extends Library_TestCase {

	/**
	 * Every form is listed, more than the first five, by number of books, then of books read.
	 *
	 * @return void
	 */
	public function test_every_form_is_listed_by_number_of_books(): void {
		$books = array(
			'Novel'    => 7,
			'Comic'    => 6,
			'Essay'    => 5,
			'Magazine' => 4,
			'Poetry'   => 3,
			'Drama'    => 2,
		);

		foreach ( $books as $form => $count ) {
			foreach ( range( 1, $count ) as $book ) {
				$this->add_book( array( 'form' => $this->get_form( $form ) ) );
			}
		}

		// As many books as Poetry, one of them read: Drama comes first.
		$this->add_read( '2024-03-14', array( 'form' => $this->get_form( 'Drama' ) ) );

		$this->assertSame(
			array(
				'Novel 7 books, 0 read',
				'Comic 6 books, 0 read',
				'Essay 5 books, 0 read',
				'Magazine 4 books, 0 read',
				'Drama 3 books, 1 read',
				'Poetry 3 books, 0 read',
			),
			$this->get_ranking( 'Forms' )
		);
	}

	/**
	 * A book not kept, or to buy, is not at hand: it counts for no form.
	 *
	 * @return void
	 */
	public function test_a_book_not_at_hand_counts_for_no_form(): void {
		$this->add_book( array( 'form' => $this->get_form( 'Novel' ) ) );
		$this->add_book(
			array(
				'form'   => $this->get_form( 'Novel' ),
				'status' => Library_Posts::NOT_KEPT,
			)
		);
		$this->add_book(
			array(
				'form'   => $this->get_form( 'Comic' ),
				'status' => Library_Posts::TO_BUY,
			)
		);

		$this->assertSame( array( 'Novel 1 book, 0 read' ), $this->get_ranking( 'Forms' ) );
	}

	/**
	 * Entries of a ranking of the widget, as text: "Novel 7 books, 0 read".
	 *
	 * @param string $title Title of the ranking.
	 * @return string[]
	 */
	private function get_ranking( string $title ): array {
		ob_start();
		new Library_Dashboard()->render_collection();

		$document = new DOMDocument();
		$document->loadHTML( '<?xml encoding="utf-8"?>' . ob_get_clean(), LIBXML_NOERROR );
		$entries = new DOMXPath( $document )->query( "//section[h3='$title']//li" );

		return array_map( fn( DOMElement $entry ) => trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $document->saveHTML( $entry ) ) ) ), iterator_to_array( $entries ) );
	}
}
