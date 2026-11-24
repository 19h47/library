<?php
/**
 * Tests of the diary, the readings by month.
 *
 * @package Library
 */

namespace Library\Tests;

use WP_REST_Request;
use WP_REST_Response;

/**
 * The route of the readings by month, `library/v1/readings`: of every form, or of the forms asked for, with how many
 * readings in all.
 */
class Test_Diary extends TestCase {

	/**
	 * Without forms, every reading comes; with forms, only those of the books of one of them, the months without any
	 * left out, and the readings of every page counted.
	 *
	 * @return void
	 */
	public function test_readings_of_the_forms_asked_for(): void {
		$novel = $this->get_form( 'Novel' );
		$comic = $this->get_form( 'Comic' );
		$essay = $this->get_form( 'Essay' );

		[ $first_novel, $second_novel ] = $this->add_reads( array( '2026-09-12', '2026-10-03' ), array( 'form' => $novel ) );
		$comic_book                     = $this->add_read( '2026-10-20', array( 'form' => $comic ) );
		$essay_book                     = $this->add_read( '2026-11-02', array( 'form' => $essay ) );

		$all = $this->get_readings( array() );

		$this->assertSame( array( '2026-11', '2026-10', '2026-09' ), array_column( $all->get_data(), 'month' ) );
		$this->assertSame( '4', $all->get_headers()['X-Library-Reading-Count'] );

		$novels_and_comics = $this->get_readings( array( 'forms' => "$novel,$comic" ) );
		$months            = $novels_and_comics->get_data();

		$this->assertSame( array( '2026-10', '2026-09' ), array_column( $months, 'month' ) );
		$this->assertSame( array( $comic_book, $second_novel ), array_column( $months[0]['readings'], 'book_id' ) );
		$this->assertSame( array( $first_novel ), array_column( $months[1]['readings'], 'book_id' ) );
		$this->assertSame( '3', $novels_and_comics->get_headers()['X-Library-Reading-Count'] );
		$this->assertSame( '2', $novels_and_comics->get_headers()['X-WP-Total'] );

		$essays = $this->get_readings( array( 'forms' => array( $essay ) ) );

		$this->assertSame( array( $essay_book ), array_column( $essays->get_data()[0]['readings'], 'book_id' ) );
		$this->assertSame( '1', $essays->get_headers()['X-Library-Reading-Count'] );
	}

	/**
	 * The readings by month.
	 *
	 * @param array<string,mixed> $params Parameters of the request.
	 * @return WP_REST_Response
	 */
	private function get_readings( array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', '/library/v1/readings' );

		$request->set_query_params( $params );

		return rest_do_request( $request );
	}
}
