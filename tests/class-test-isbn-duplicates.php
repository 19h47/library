<?php
/**
 * Tests of the ISBN of a book in the catalogue the readers share: one book per ISBN.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Meta;
use WP_REST_Request;
use WP_REST_Response;

/**
 * An ISBN another book of the catalogue has is refused, with that book, which a reader puts on their shelves instead;
 * a book saved twice before can still be edited, and a book in the trash has no ISBN of the catalogue.
 */
class Test_Isbn_Duplicates extends TestCase {

	/**
	 * A request of the route of the book form.
	 *
	 * @param string              $route Route, such as /library/v1/books/12.
	 * @param array<string,mixed> $book  Fields of the book, as the route takes them.
	 * @return WP_REST_Response
	 */
	private function send( string $route, array $book ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $book ) );

		return rest_do_request( $request );
	}

	/**
	 * A book added with the ISBN of a book of the catalogue, its hyphens as they are, is refused: 409, with that book.
	 *
	 * @return void
	 */
	public function test_a_book_added_with_an_isbn_taken_is_refused(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) );

		$this->as_user( 'library_reader' );

		$response = $this->send(
			'/library/v1/books',
			array(
				'title' => 'Le Rivage des Syrtes',
				'meta'  => array( 'isbn' => '9782070360246' ),
			)
		);

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'library_isbn_taken', $response->get_data()['code'] );
		$this->assertSame( $book_id, $response->get_data()['data']['book'] );
	}

	/**
	 * A book given the ISBN of another is refused, its own kept; a book saved twice before keeps its ISBN as it is edited.
	 *
	 * @return void
	 */
	public function test_a_book_edited_with_an_isbn_taken_is_refused(): void {
		$taken   = $this->add_book( array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) );
		$book_id = $this->add_book( array( 'meta' => array( 'isbn' => '978-2-07-036822-8' ) ) );

		$this->assertSame( 409, $this->send( "/library/v1/books/$book_id", array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) )->get_status() );
		$this->assertSame( '978-2-07-036822-8', get_post_meta( $book_id, 'library_isbn', true ) );

		// Saved twice before the catalogue was shared.
		update_post_meta( $book_id, 'library_isbn', '978-2-07-036024-6' );

		$this->assertSame( 200, $this->send( "/library/v1/books/$book_id", array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) )->get_status() );
		$this->assertNull( Meta::check_isbn( $taken, '978-2-07-036024-6' ) );
	}

	/**
	 * A book in the trash leaves its ISBN to another.
	 *
	 * @return void
	 */
	public function test_a_book_in_the_trash_leaves_its_isbn(): void {
		wp_trash_post( $this->add_book( array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) ) );

		$this->assertSame(
			201,
			$this->send(
				'/library/v1/books',
				array(
					'title' => 'Le Rivage des Syrtes',
					'meta'  => array( 'isbn' => '978-2-07-036024-6' ),
				)
			)->get_status()
		);
	}

	/**
	 * A draft is no book of the catalogue: its ISBN is free for a book added, which the readers may shelve.
	 *
	 * @return void
	 */
	public function test_a_draft_leaves_its_isbn(): void {
		$draft = $this->add_book( array( 'meta' => array( 'isbn' => '978-2-07-036024-6' ) ) );

		wp_update_post(
			array(
				'ID'          => $draft,
				'post_status' => 'draft',
			)
		);

		$this->assertSame(
			201,
			$this->send(
				'/library/v1/books',
				array(
					'title' => 'Le Rivage des Syrtes',
					'meta'  => array( 'isbn' => '978-2-07-036024-6' ),
				)
			)->get_status()
		);
	}

	/**
	 * A book says whether the reader may edit it, as core gives it, in the target hints of its link: the record they
	 * added, not the one of another.
	 *
	 * @return void
	 */
	public function test_a_book_says_whether_the_reader_may_edit_it(): void {
		$other = $this->add_book();

		$this->as_user( 'library_reader' );

		$own   = $this->add_book();
		$allow = function ( int $book_id ): array {
			$request = new WP_REST_Request( 'GET', "/wp/v2/books/$book_id" );
			$request->set_query_params( array( '_fields' => 'id,_links' ) );

			$data = rest_get_server()->response_to_data( rest_do_request( $request ), false );

			return $data['_links']['self'][0]['targetHints']['allow'];
		};

		$this->assertContains( 'POST', $allow( $own ) );
		$this->assertNotContains( 'POST', $allow( $other ) );
	}
}
