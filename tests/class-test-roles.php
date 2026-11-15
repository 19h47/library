<?php
/**
 * Tests of the roles of the readers and of the capabilities of the books.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Meta;
use WP_REST_Request;
use WP_REST_Response;

/**
 * A reader shelves the books, adds the ones the catalogue lacks and changes their own; a librarian changes every book and
 * every name; an editor deletes them; a visitor sees no reading.
 */
class Test_Roles extends TestCase {

	/**
	 * A request of the plugin, as the current user.
	 *
	 * @param string              $method Method.
	 * @param string              $route  Route, such as /library/v1/books/12.
	 * @param array<string,mixed> $body   Body, as JSON.
	 * @return WP_REST_Response
	 */
	private function request( string $method, string $route, array $body = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $route );

		if ( $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $body ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * A reader adds a book and changes it, but not the book of another reader, and deletes neither; the app and the routes
	 * of the reader are theirs.
	 *
	 * @return void
	 */
	public function test_a_reader_changes_their_books_only(): void {
		$other = $this->add_book( array( 'title' => 'Dune' ) );

		$this->as_user( 'library_reader' );

		$own = $this->add_book( array( 'title' => 'Le Rivage des Syrtes' ) );

		$this->assertSame( 200, $this->request( 'POST', "/library/v1/books/$own", array( 'title' => 'Un balcon en forêt' ) )->get_status() );
		$this->assertSame( 403, $this->request( 'POST', "/library/v1/books/$other", array( 'title' => 'Dune Messiah' ) )->get_status() );
		$this->assertFalse( current_user_can( 'delete_post', $own ) );
		$this->assertFalse( current_user_can( 'manage_library_terms' ) );
		$this->assertTrue( current_user_can( 'shelve_book', $other ) );
		$this->assertSame( 200, $this->request( 'GET', '/library/v1/stats' )->get_status() );
	}

	/**
	 * A reader gives a book the cover they uploaded, though they cannot edit the posts.
	 *
	 * @return void
	 */
	public function test_a_reader_gives_their_cover(): void {
		$user_id = $this->as_user( 'library_reader' );
		$book_id = $this->add_book();
		$cover   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		wp_update_post(
			array(
				'ID'          => $cover,
				'post_author' => $user_id,
			)
		);

		$this->request( 'POST', "/library/v1/books/$book_id", array( 'featured_media' => $cover ) );

		$this->assertSame( $cover, get_post_thumbnail_id( $book_id ) );
	}

	/**
	 * A librarian changes the book of another and the names of the catalogue, but deletes no book; an editor does.
	 *
	 * @return void
	 */
	public function test_a_librarian_refines_and_an_editor_deletes(): void {
		$book_id = $this->add_book( array( 'title' => 'Dune' ) + $this->by( 'Frank Herbert' ) );
		$term_id = get_the_terms( $book_id, 'library_person' )[0]->term_id;

		$this->as_user( 'library_librarian' );

		$this->assertSame( 200, $this->request( 'POST', "/library/v1/books/$book_id", array( 'title' => 'Dune Messiah' ) )->get_status() );
		$this->assertTrue( current_user_can( 'edit_term', $term_id ) );
		$this->assertTrue( current_user_can( 'delete_term', $term_id ) );
		$this->assertFalse( current_user_can( 'delete_post', $book_id ) );

		$this->as_user( 'editor' );

		$this->assertTrue( current_user_can( 'delete_post', $book_id ) );
	}

	/**
	 * A visitor gets a book without its shelf, its readings, its read state and its rating among them, and none of the
	 * routes of the readers.
	 *
	 * @return void
	 */
	public function test_a_visitor_sees_no_reading(): void {
		$book_id = $this->add_read( '2024-03-14' );

		$this->assertNotEmpty( $this->request( 'GET', "/wp/v2/books/$book_id" )->get_data()['shelf']['readings'] );

		wp_set_current_user( 0 );

		$book = $this->request( 'GET', "/wp/v2/books/$book_id" )->get_data();

		$this->assertArrayNotHasKey( 'shelf', $book );
		$this->assertSame( array(), array_intersect( array_keys( $book['meta'] ), Meta::SHELF_KEYS ) );
		$this->assertSame( 401, $this->request( 'GET', '/library/v1/stats' )->get_status() );
	}

	/**
	 * The roles of the plugin carry their names, translated as WordPress translates a user role.
	 *
	 * @return void
	 */
	public function test_the_roles_are_named(): void {
		$this->assertSame( 'Reader', translate_user_role( wp_roles()->role_names['library_reader'] ) );
		$this->assertSame( 'Librarian', translate_user_role( wp_roles()->role_names['library_librarian'] ) );

		// The French of the plugin, which the tests cannot switch to: WordPress is not installed in French.
		load_textdomain( 'library', LIBRARY_DIR_PATH . 'languages/library-fr_FR.mo', 'fr_FR' );

		$this->assertSame( 'Lecteur', translate_user_role( wp_roles()->role_names['library_reader'] ) );
		$this->assertSame( 'Bibliothécaire', translate_user_role( wp_roles()->role_names['library_librarian'] ) );

		unload_textdomain( 'library' );
	}
}
