<?php
/**
 * Tests of the names of a book in the REST API of core: each one a library-book-person, with its term ID and its role,
 * beside the term IDs of its people, and no role meta; the route of the book form gives and takes the same.
 *
 * @package Library
 */

namespace Library\Tests;

use WP_REST_Request;

/**
 * A book of wp/v2/books gives its names with their roles, as the route of the book form takes them.
 */
class Test_Rest_Names extends TestCase {

	/**
	 * The meta of the books are registered again: the suite unregisters every meta key after a test.
	 *
	 * @return void
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();

		new \Library\Meta()->register_meta();
	}

	/**
	 * Each name of a book comes with its term ID and its role, the term IDs of its people as core gives them, and the
	 * role meta not at all.
	 *
	 * @return void
	 */
	public function test_names_give_their_roles(): void {
		$book_id = $this->add_book(
			array(
				'title'  => 'Codex',
				'people' => array(
					array(
						'name' => 'Ann Writer',
						'role' => 'author',
					),
					array(
						'name' => 'Tom Translator',
						'role' => 'translator',
					),
				),
			)
		);
		$request = new WP_REST_Request( 'GET', '/wp/v2/books/' . $book_id );

		$request->set_query_params( array( '_fields' => 'names,people,meta' ) );

		$data       = rest_do_request( $request )->get_data();
		$author     = get_term_by( 'name', 'Ann Writer', 'library_person' );
		$translator = get_term_by( 'name', 'Tom Translator', 'library_person' );

		$this->assertEqualsCanonicalizing(
			array(
				array(
					'id'   => $author->term_id,
					'name' => 'Ann Writer',
					'role' => 'author',
				),
				array(
					'id'   => $translator->term_id,
					'name' => 'Tom Translator',
					'role' => 'translator',
				),
			),
			$data['names']
		);
		$this->assertEqualsCanonicalizing( array( $author->term_id, $translator->term_id ), $data['people'] );
		$this->assertArrayNotHasKey( 'library_translators', $data['meta'] );

		// The route of the book form gives the same people, and takes them back as they are given, their term IDs with them.
		$form = rest_do_request( new WP_REST_Request( 'GET', '/library/v1/books/' . $book_id ) )->get_data();

		$this->assertEqualsCanonicalizing( $data['names'], $form['people'] );

		$request = new WP_REST_Request( 'POST', '/library/v1/books/' . $book_id );

		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'people' => $data['names'] ) ) );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertEqualsCanonicalizing( array( $author->term_id, $translator->term_id ), wp_get_post_terms( $book_id, 'library_person', array( 'fields' => 'ids' ) ) );
	}
}
