<?php
/**
 * Tests of the route of the covers, which uploads the photo of a cover named after its book.
 *
 * @package Library
 */

namespace Library\Tests;

use WP_REST_Request;

/**
 * `POST library/v1/covers` names the photo after its book, its first author and its title as shown, keeps its extension,
 * titles the image after the book, and refuses a request without photo or a user who cannot upload.
 */
class Test_Covers extends TestCase {

	/**
	 * A request of the route, with a copy of a photo of the test data as its file.
	 *
	 * @param array<string,mixed> $book Fields of the book, as its form sends them.
	 * @param string              $name Name of the photo picked.
	 * @return WP_REST_Request
	 */
	private function cover_request( array $book, string $name = 'IMG_2041.JPG' ): WP_REST_Request {
		$photo = wp_tempnam( $name );

		copy( DIR_TESTDATA . '/images/canola.jpg', $photo );

		$request = new WP_REST_Request( 'POST', '/library/v1/covers' );
		$request->set_body_params( $book );
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => $name,
					'type'     => 'image/jpeg',
					'tmp_name' => $photo,
					'error'    => 0,
					'size'     => filesize( $photo ),
				),
			)
		);

		return $request;
	}

	/**
	 * The photo is named after the first author and the title as shown, its extension kept in lower case, and the image
	 * titled after the book.
	 *
	 * @return void
	 */
	public function test_the_photo_is_named_after_its_book(): void {
		$response = rest_do_request(
			$this->cover_request(
				array(
					'title'  => 'Blood for Blood',
					'people' => array(
						array(
							'name' => 'Robert Kirkman',
							'role' => 'author',
						),
						array(
							'name' => 'Charlie Adlard',
							'role' => 'author',
						),
						array(
							'name' => 'Cliff Rathburn',
							'role' => 'colorist',
						),
					),
					'series' => 'Walking Dead',
					'meta'   => array( 'volume_number' => '25' ),
				)
			)
		);
		$image    = $response->get_data();

		$this->assertSame( 201, $response->get_status(), wp_json_encode( $image ) );
		// The uploads outlive a test: a name taken takes a number.
		$this->assertMatchesRegularExpression( '/^charlie-adlard-walking-dead-no-25-blood-for-blood(-\d+)?\.jpg$/', wp_basename( get_attached_file( $image['id'] ) ) );
		$this->assertSame( 'Blood for Blood', get_the_title( $image['id'] ) );

		wp_delete_attachment( $image['id'], true );
	}

	/**
	 * A collective work is named after its title alone.
	 *
	 * @return void
	 */
	public function test_a_collective_work_is_named_after_its_title(): void {
		$response = rest_do_request(
			$this->cover_request(
				array(
					'title'  => 'White Dwarf',
					'people' => array(
						array(
							'name' => 'Danièle Gaudry',
							'role' => 'author',
						),
					),
					'meta'   => array(
						'volume_number' => '56',
						'collective'    => '1',
					),
				)
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$this->assertStringStartsWith( 'white-dwarf', wp_basename( get_attached_file( $response->get_data()['id'] ) ) );
		$this->assertStringNotContainsString( 'gaudry', wp_basename( get_attached_file( $response->get_data()['id'] ) ) );

		wp_delete_attachment( $response->get_data()['id'], true );
	}

	/**
	 * Without photo, the request is refused, 400; a user who cannot upload is refused, 403.
	 *
	 * @return void
	 */
	public function test_a_request_without_photo_or_right_is_refused(): void {
		$request = new WP_REST_Request( 'POST', '/library/v1/covers' );
		$request->set_body_params( array( 'title' => 'Dune' ) );

		$this->assertSame( 'library_no_photo', rest_do_request( $request )->get_data()['code'] );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 403, rest_do_request( $this->cover_request( array( 'title' => 'Dune' ) ) )->get_status() );
	}

	/**
	 * An ID no book has is not found, 404, before any right is checked; a book is found.
	 *
	 * @return void
	 */
	public function test_an_id_no_book_has_is_not_found(): void {
		$book_id = $this->add_book( array( 'title' => 'Dune' ) );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$get     = fn( int $id ) => rest_do_request( new WP_REST_Request( 'GET', '/library/v1/books/' . $id ) );

		$this->assertSame( 200, $get( $book_id )->get_status() );
		$this->assertSame( 404, $get( $page_id )->get_status() );
		$this->assertSame( 'library_no_book', $get( 999999 )->get_data()['code'] );
		$this->assertSame( 404, rest_do_request( new WP_REST_Request( 'POST', '/library/v1/books/999999' ) )->get_status() );

		wp_set_current_user( 0 );

		$this->assertSame( 404, $get( 999999 )->get_status() );
		$this->assertSame( 401, $get( $book_id )->get_status() );
	}
}
