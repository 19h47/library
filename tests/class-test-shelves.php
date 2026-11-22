<?php
/**
 * Tests of the shelves of a reader: where each book of the catalogue is for them, and since when.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Shelf;
use Library\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Each reader has shelves of their own on the shared catalogue: a book is in the library of one, to buy for another, and
 * on none of the shelves of a third.
 */
class Test_Shelves extends TestCase {

	/**
	 * A request of the REST API, as the current user.
	 *
	 * @param string              $method Method.
	 * @param string              $route  Route, such as /library/v1/books/12/shelf.
	 * @param array<string,mixed> $params Parameters, as the query or the body takes them.
	 * @return WP_REST_Response
	 */
	private function request( string $method, string $route, array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $route );

		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} elseif ( $params ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $params ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * The shelf of a book for the current reader, as `wp/v2/books` gives it.
	 *
	 * @param int $book_id Book ID.
	 * @return array{status:string|null,added:string|null}
	 */
	private function get_shelf( int $book_id ): array {
		return $this->request( 'GET', "/wp/v2/books/$book_id" )->get_data()['shelf'];
	}

	/**
	 * A book added is published, in the library of the reader who added it unless they say otherwise, and on none of the
	 * shelves of another.
	 *
	 * @return void
	 */
	public function test_a_book_added_is_on_the_shelf_of_its_reader_only(): void {
		$book_id = $this->add_book();
		$to_buy  = $this->add_book( array( 'shelf' => array( 'status' => Status::To_Buy->value ) ) );

		$this->assertSame( 'publish', get_post_status( $book_id ) );
		$this->assertSame( Status::In_Library->value, $this->get_shelf( $book_id )['status'] );
		$this->assertSame( Status::To_Buy->value, $this->get_shelf( $to_buy )['status'] );

		$this->as_user( 'library_reader' );

		$shelf = $this->get_shelf( $book_id );

		$this->assertNull( $shelf['status'] );
		$this->assertNull( $shelf['added'] );
	}

	/**
	 * Two readers shelve the same book each their own way, though one of them cannot edit it.
	 *
	 * @return void
	 */
	public function test_two_readers_shelve_the_same_book_their_own_way(): void {
		$book_id = $this->add_book();
		$first   = get_current_user_id();

		$this->as_user( 'library_reader' );

		$response = $this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'status' => Status::Not_Kept->value ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( Status::Not_Kept->value, $response->get_data()['shelf']['status'] );

		wp_set_current_user( $first );

		$this->assertSame( Status::In_Library->value, $this->get_shelf( $book_id )['status'] );
	}

	/**
	 * A book taken off the shelves of a reader stays in the catalogue, and in the library of another.
	 *
	 * @return void
	 */
	public function test_a_book_taken_off_the_shelves_stays_in_the_catalogue(): void {
		$book_id = $this->add_book();
		$first   = get_current_user_id();

		$this->as_user( 'library_reader' );
		$this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'status' => Status::In_Library->value ) );

		$response = $this->request( 'DELETE', "/library/v1/books/$book_id/shelf" );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $response->get_data()['shelf']['status'] );
		$this->assertSame( 'publish', get_post_status( $book_id ) );

		wp_set_current_user( $first );

		$this->assertSame( Status::In_Library->value, $this->get_shelf( $book_id )['status'] );
	}

	/**
	 * A book is dated as it is added, and again as it leaves the books to buy: it came to the library then. Read and
	 * passed on, it keeps the date it came.
	 *
	 * @return void
	 */
	public function test_a_book_is_dated_as_it_comes_to_the_library(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'status' => Status::To_Buy->value ) ) );
		$reader  = get_current_user_id();

		update_post_meta( $book_id, Shelf::key( Shelf::ADDED, $reader ), '2020-01-01 10:00:00' );
		Shelf::set_status( $book_id, $reader, Status::In_Library );

		$this->assertNotSame( '2020-01-01 10:00:00', Shelf::get_added( $book_id, $reader ) );

		update_post_meta( $book_id, Shelf::key( Shelf::ADDED, $reader ), '2021-01-01 10:00:00' );
		Shelf::set_status( $book_id, $reader, Status::Not_Kept );

		$this->assertSame( '2021-01-01 10:00:00', Shelf::get_added( $book_id, $reader ) );
	}

	/**
	 * The books of the shelves asked for are those of the reader, latest added first; without, the whole catalogue.
	 *
	 * @return void
	 */
	public function test_the_shelves_list_the_books_of_the_reader_latest_added_first(): void {
		$earlier = $this->add_book( array(), '2024-01-01 10:00:00' );
		$later   = $this->add_book( array(), '2024-02-01 10:00:00' );
		$to_buy  = $this->add_book( array( 'shelf' => array( 'status' => Status::To_Buy->value ) ) );
		$reader  = get_current_user_id();

		// Added by the reader the other way round from the catalogue.
		update_post_meta( $earlier, Shelf::key( Shelf::ADDED, $reader ), '2025-02-01 10:00:00' );
		update_post_meta( $later, Shelf::key( Shelf::ADDED, $reader ), '2025-01-01 10:00:00' );

		$this->as_user( 'library_reader' );

		$other = $this->add_book();
		$ids   = fn( array $params ) => wp_list_pluck( $this->request( 'GET', '/wp/v2/books', $params + array( '_fields' => 'id' ) )->get_data(), 'id' );

		$this->assertSame( array( $other ), $ids( array( 'shelf' => Status::get_values() ) ) );

		wp_set_current_user( $reader );

		$this->assertSame( array( $earlier, $later ), $ids( array( 'shelf' => array( Status::In_Library->value ) ) ) );
		$this->assertSame( array( $to_buy, $earlier, $later ), $ids( array( 'shelf' => Status::get_values() ) ) );
		$this->assertEqualsCanonicalizing( array( $earlier, $later, $to_buy, $other ), $ids( array() ) );
	}

	/**
	 * The page of a person, of a series and of a collection lists the books on the shelves of the reader, those to buy
	 * apart, never a book of the catalogue on none of them, such as one another reader added.
	 *
	 * @return void
	 */
	public function test_the_pages_of_a_term_list_the_books_of_the_reader(): void {
		$book   = array(
			'people'    => array(
				array(
					'name' => 'Ada Palmer',
					'role' => 'author',
				),
			),
			'series'    => 'Terra Ignota',
			'publisher' => 'Tor',
		);
		$kept   = $this->add_book( $book );
		$to_buy = $this->add_book( $book + array( 'shelf' => array( 'status' => Status::To_Buy->value ) ) );
		$reader = get_current_user_id();
		$terms  = array(
			'people'     => get_term_by( 'name', 'Ada Palmer', 'library_person' )->term_id,
			'series'     => get_term_by( 'name', 'Terra Ignota', 'library_series' )->term_id,
			'publishers' => get_term_by( 'name', 'Tor', 'library_publisher' )->term_id,
		);

		$this->as_user( 'library_reader' );

		$other = $this->add_book( $book );

		foreach ( $terms as $route => $term_id ) {
			$this->assertSame( array( $other ), $this->request( 'GET', "/library/v1/$route/$term_id" )->get_data()['books'], $route );
		}

		wp_set_current_user( $reader );

		foreach ( $terms as $route => $term_id ) {
			$data = $this->request( 'GET', "/library/v1/$route/$term_id" )->get_data();

			$this->assertSame( array( $kept ), $data['books'], $route );
			$this->assertSame( array( $to_buy ), $data['wishes'], $route );
		}
	}

	/**
	 * The keyword title: finds a book of the catalogue by its whole title, whatever its case, its accents and its
	 * apostrophes, straight or curly; a part of it finds none.
	 *
	 * @return void
	 */
	public function test_a_title_finds_a_book_of_the_catalogue(): void {
		$book_id = $this->add_book( array( 'title' => 'L’Écume des jours' ) );

		$this->as_user( 'library_reader' );

		$ids = function ( string $title ): array {
			$params = array(
				'search'  => "title:\"$title\"",
				'_fields' => 'id',
			);

			return wp_list_pluck( $this->request( 'GET', '/wp/v2/books', $params )->get_data(), 'id' );
		};

		$this->assertSame( array( $book_id ), $ids( "l'ecume des jours" ) );
		$this->assertSame( array( $book_id ), $ids( 'L’Écume des jours' ) );
		$this->assertSame( array(), $ids( 'Écume' ) );
	}

	/**
	 * Two readers read the same book each their own way, their copies printed apart, though one of them cannot edit it:
	 * neither gets the readings, the rating or the copy of the other, nor finds the book among their books read.
	 *
	 * @return void
	 */
	public function test_two_readers_read_the_same_book_their_own_way(): void {
		$book_id = $this->add_book(
			array(
				'shelf' => array(
					'readings'     => array( '/2024-03-14#8' ),
					'printed_date' => '2014',
				),
			)
		);
		$first   = get_current_user_id();

		$this->as_user( 'library_reader' );

		$response = $this->request(
			'POST',
			"/library/v1/books/$book_id/shelf",
			array(
				'readings'     => array( self::reading( '2025-01-02/2025-01-20#6' ) ),
				'printed_date' => '2021-05',
			)
		);
		$shelf    = $response->get_data()['shelf'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 6.0, $shelf['rating'] );
		$this->assertSame( '2021-05', $shelf['printed_date'] );
		$this->assertSame( array( '2025-01-02/2025-01-20#6' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );

		wp_set_current_user( $first );

		$shelf = $this->get_shelf( $book_id );

		$this->assertSame( 8.0, $shelf['rating'] );
		$this->assertSame( '2014', $shelf['printed_date'] );
		$this->assertSame( '2024-03-14', $shelf['readings'][0]['end'] );

		$this->as_user( 'library_reader' );

		$found = $this->request( 'GET', '/wp/v2/books', array( 'search' => 'read:yes' ) )->get_data();

		$this->assertSame( array(), $found );
	}

	/**
	 * A book read on none of the shelves of the reader goes among the books not kept: a reading is on a shelf.
	 *
	 * @return void
	 */
	public function test_a_book_read_goes_on_a_shelf(): void {
		$book_id = $this->add_book();

		$this->as_user( 'library_reader' );
		$this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'read' => true ) );

		$this->assertSame( Status::Not_Kept->value, $this->get_shelf( $book_id )['status'] );
	}

	/**
	 * A copy is on a shelf: notes, a printed date or a photo of their copy put a book on none of the shelves of the reader
	 * among the books not kept, as a reading does.
	 *
	 * @return void
	 */
	public function test_a_copy_goes_on_a_shelf(): void {
		$noted   = $this->add_book();
		$printed = $this->add_book();

		$this->as_user( 'library_reader' );
		$this->request( 'POST', "/library/v1/books/$noted/shelf", array( 'notes' => 'Lent by Ada.' ) );
		$this->request( 'POST', "/library/v1/books/$printed/shelf", array( 'printed_date' => '2014' ) );

		$this->assertSame( Status::Not_Kept->value, $this->get_shelf( $noted )['status'] );
		$this->assertSame( Status::Not_Kept->value, $this->get_shelf( $printed )['status'] );
	}

	/**
	 * A book taken off the shelves loses every row of the reader, even one on none of them that kept some, as a copy of
	 * before.
	 *
	 * @return void
	 */
	public function test_a_book_taken_off_loses_every_row(): void {
		$book_id = $this->add_book();
		$reader  = $this->as_user( 'library_reader' );

		update_post_meta( $book_id, Shelf::key( 'library_notes', $reader ), 'Lent by Ada.' );

		$this->assertSame( 200, $this->request( 'DELETE', "/library/v1/books/$book_id/shelf" )->get_status() );
		$this->assertSame( '', $this->get_shelf( $book_id )['notes']['raw'] );
	}

	/**
	 * A book read stays on the shelves of the reader: taken off, it is refused, and stays where it was; unread, it goes.
	 *
	 * @return void
	 */
	public function test_a_book_read_stays_on_the_shelves(): void {
		$book_id = $this->add_read( '2024-03-14' );

		$this->assertSame( 409, $this->request( 'DELETE', "/library/v1/books/$book_id/shelf" )->get_status() );
		$this->assertSame( Status::In_Library->value, $this->get_shelf( $book_id )['status'] );

		$this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'read' => false ) );

		$this->assertSame( 200, $this->request( 'DELETE', "/library/v1/books/$book_id/shelf" )->get_status() );
		$this->assertNull( $this->get_shelf( $book_id )['status'] );
	}

	/**
	 * Each reader has their own notes on a book, which their search finds, and nobody else's.
	 *
	 * @return void
	 */
	public function test_each_reader_has_their_own_notes(): void {
		$book_id = $this->add_book( array( 'title' => 'Dune' ) );
		$first   = get_current_user_id();

		$this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'notes' => "Lent by Ada.\n\nRead in the train." ) );

		$notes = $this->get_shelf( $book_id )['notes'];

		$this->assertSame( "Lent by Ada.\n\nRead in the train.", $notes['raw'] );
		$this->assertStringContainsString( '<p>Read in the train.</p>', $notes['rendered'] );
		$this->assertSame( array( $book_id ), wp_list_pluck( $this->request( 'GET', '/wp/v2/books', array( 'search' => 'Ada' ) )->get_data(), 'id' ) );

		$this->as_user( 'library_reader' );

		$this->assertSame( '', $this->get_shelf( $book_id )['notes']['raw'] );
		$this->assertSame( array(), $this->request( 'GET', '/wp/v2/books', array( 'search' => 'Ada' ) )->get_data() );

		wp_set_current_user( $first );
	}

	/**
	 * A reader gives a book the photo of their copy, an image they uploaded, never one of another; the book keeps the
	 * cover of its edition for the others.
	 *
	 * @return void
	 */
	public function test_a_reader_gives_the_photo_of_their_copy(): void {
		$book_id = $this->add_book();
		$other   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$reader  = $this->as_user( 'library_reader' );
		$own     = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		wp_update_post(
			array(
				'ID'          => $own,
				'post_author' => $reader,
			)
		);

		$this->assertSame( 400, $this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'cover' => $other ) )->get_status() );
		$this->assertSame( $own, $this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'cover' => $own ) )->get_data()['shelf']['cover']['id'] );

		$this->request( 'POST', "/library/v1/books/$book_id/shelf", array( 'cover' => 0 ) );

		$this->assertNull( $this->get_shelf( $book_id )['cover'] );
	}

	/**
	 * A reader deleted leaves the shelves, and the books in the catalogue.
	 *
	 * @return void
	 */
	public function test_a_reader_deleted_leaves_the_shelves(): void {
		$reader  = $this->as_user( 'library_reader' );
		$book_id = $this->add_book();

		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $reader );

		$this->assertNull( Shelf::get_status( $book_id, $reader ) );
		$this->assertSame( '', Shelf::get_added( $book_id, $reader ) );
		$this->assertSame( 'publish', get_post_status( $book_id ) );
	}

	/**
	 * A book restored from the trash comes back in the catalogue, published, rather than as a draft, on the shelves it was
	 * on.
	 *
	 * @return void
	 */
	public function test_a_book_restored_from_the_trash_comes_back_in_the_catalogue(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'status' => Status::Not_Kept->value ) ) );

		wp_trash_post( $book_id );
		wp_untrash_post( $book_id );

		$this->assertSame( 'publish', get_post_status( $book_id ) );
		$this->assertSame( Status::Not_Kept, Shelf::get_status( $book_id, get_current_user_id() ) );
	}

	/**
	 * Another post type keeps the draft of core.
	 *
	 * @return void
	 */
	public function test_another_post_restored_from_the_trash_is_a_draft(): void {
		$post_id = self::factory()->post->create();

		wp_trash_post( $post_id );
		wp_untrash_post( $post_id );

		$this->assertSame( 'draft', get_post_status( $post_id ) );
	}
}
