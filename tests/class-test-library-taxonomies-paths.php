<?php
/**
 * Tests of the paths a book types: a series after its cycles, a collection after its parent collections.
 *
 * @package Library
 */

/**
 * Names separated with commas, each under the one before, but a name with a comma that a term has is one name.
 */
class Test_Library_Taxonomies_Paths extends Library_TestCase {

	/**
	 * A path without such a name is a series under its cycle.
	 *
	 * @return void
	 */
	public function test_a_path_is_a_series_under_its_cycle(): void {
		$book_id = $this->add_book( array( 'series' => 'Star Wars, Young Jedi Knights' ) );

		$series = Library_Taxonomies::get_series( $book_id );

		$this->assertSame( 'Young Jedi Knights', $series->name );
		$this->assertSame( 'Star Wars', get_term( $series->parent, 'library_series' )->name );
	}

	/**
	 * A series whose name has a comma is found whole, and no cycle is added.
	 *
	 * @return void
	 */
	public function test_a_series_with_a_comma_is_one_series(): void {
		$talli = wp_insert_term( 'Talli, fille de la lune', 'library_series' );

		$book_id = $this->add_book( array( 'series' => 'Talli, fille de la lune' ) );

		$this->assertSame( $talli['term_id'], Library_Taxonomies::get_series( $book_id )->term_id );
		$this->assertEmpty( term_exists( 'Talli', 'library_series' ) );
	}

	/**
	 * A book of that series saved again, as the book form sends it, keeps the series.
	 *
	 * @return void
	 */
	public function test_a_book_saved_again_keeps_its_series_with_a_comma(): void {
		$talli   = wp_insert_term( 'Talli, fille de la lune', 'library_series' );
		$book_id = $this->add_book( array( 'series' => 'Talli, fille de la lune' ) );
		$sent    = Library_Taxonomies::get_series_path( Library_Taxonomies::get_series( $book_id ) );

		$request = new WP_REST_Request( 'POST', '/library/v1/books/' . $book_id );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'series' => $sent ) ) );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( $talli['term_id'], Library_Taxonomies::get_series( $book_id )->term_id );
		$this->assertEmpty( term_exists( 'Talli', 'library_series' ) );
	}

	/**
	 * A series with a comma under a cycle is found after the cycle.
	 *
	 * @return void
	 */
	public function test_a_series_with_a_comma_under_its_cycle(): void {
		$cycle  = wp_insert_term( 'Lune', 'library_series' );
		$series = wp_insert_term( 'Talli, fille de la lune', 'library_series', array( 'parent' => $cycle['term_id'] ) );

		$book_id = $this->add_book( array( 'series' => 'Lune, Talli, fille de la lune' ) );

		$this->assertSame( $series['term_id'], Library_Taxonomies::get_series( $book_id )->term_id );
	}

	/**
	 * A collection whose name has a comma is found whole under its publisher.
	 *
	 * @return void
	 */
	public function test_a_collection_with_a_comma_is_one_collection(): void {
		$publisher  = wp_insert_term( 'Delcourt', 'library_publisher' );
		$collection = wp_insert_term( 'Humour, de rire', 'library_publisher', array( 'parent' => $publisher['term_id'] ) );

		$book_id = $this->add_book(
			array(
				'publisher'  => 'Delcourt',
				'collection' => 'Humour, de rire',
			)
		);

		$this->assertSame( array( $collection['term_id'] ), wp_get_object_terms( $book_id, 'library_publisher', array( 'fields' => 'ids' ) ) );
	}

	/**
	 * The series: search finds a series with a comma by its name, alone or after its cycle, case aside.
	 *
	 * @return void
	 */
	public function test_the_series_search_finds_a_series_with_a_comma(): void {
		$cycle  = wp_insert_term( 'Lune', 'library_series' );
		$series = wp_insert_term( 'Talli, fille de la lune', 'library_series', array( 'parent' => $cycle['term_id'] ) );

		$this->assertSame( array( $series['term_id'] ), Library_Taxonomies::find_series_ids( 'talli, fille de la lune' ) );
		$this->assertSame( array( $series['term_id'] ), Library_Taxonomies::find_series_ids( 'Lune, Talli, fille de la lune' ) );
		$this->assertSame( array( $cycle['term_id'], $series['term_id'] ), Library_Taxonomies::find_series_ids( 'Lune' ) );
		$this->assertSame( array(), Library_Taxonomies::find_series_ids( 'Soleil, Talli, fille de la lune' ) );
	}
}
