<?php
/**
 * Tests of the readings of a book: one row each, from its start to its end, and whether it is read and being read, which
 * they tell.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Meta;
use WP_REST_Request;

/**
 * A reading in progress has its start and no end; a reading added ends it, from its start; a form gives days, which keep
 * the times saved.
 */
class Test_Readings extends TestCase {

	/**
	 * Save the meta of a book as its forms do, through the route of the book form.
	 *
	 * @param int                 $book_id Book ID.
	 * @param array<string,mixed> $meta    Meta, as the forms send them.
	 * @return void
	 */
	private function save( int $book_id, array $meta ): void {
		$request = new WP_REST_Request( 'POST', '/library/v1/books/' . $book_id );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'meta' => $meta ) ) );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
	}

	/**
	 * The IDs of the books a search of the API finds, in its order.
	 *
	 * @param array<string,string> $params Query parameters, such as search.
	 * @return int[]
	 */
	private function find( array $params ): array {
		$request = new WP_REST_Request( 'GET', '/wp/v2/books' );
		$request->set_query_params( $params + array( '_fields' => 'id' ) );

		return array_column( rest_do_request( $request )->get_data(), 'id' );
	}

	/**
	 * Being read starts a reading now, which ending keeps the start of.
	 *
	 * @return void
	 */
	public function test_a_reading_in_progress_keeps_its_start_once_ended(): void {
		$book_id = $this->add_book();

		$this->save( $book_id, array( 'in_progress' => true ) );

		[ $reading ] = Meta::get_readings( $book_id );
		[ $start ]   = Meta::split_reading( $reading );

		$this->assertSame( Meta::IN_PROGRESS, Meta::split_reading( $reading )[1] );
		$this->assertStringStartsWith( current_time( 'Y-m-d' ) . 'T', $start );
		$this->assertTrue( (bool) get_post_meta( $book_id, 'library_in_progress', true ) );
		$this->assertFalse( (bool) get_post_meta( $book_id, 'library_read', true ) );

		$this->save( $book_id, array( 'read' => true ) );

		[ $reading ] = Meta::get_readings( $book_id );

		$this->assertSame( $start, Meta::split_reading( $reading )[0] );
		$this->assertStringStartsWith( current_time( 'Y-m-d' ) . 'T', Meta::split_reading( $reading )[1] );
		$this->assertFalse( (bool) get_post_meta( $book_id, 'library_in_progress', true ) );
		$this->assertTrue( (bool) get_post_meta( $book_id, 'library_read', true ) );
	}

	/**
	 * A reading added ends the one in progress, which gives it its start; the readings before stay.
	 *
	 * @return void
	 */
	public function test_a_reading_added_ends_the_one_in_progress(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'reading' => array( '/2019-06-14', '2024-10-20/..' ) ) ) );

		$this->save( $book_id, array( 'reading' => array( '/2019-06-14', '/2024-11-02', '2024-10-20/..' ) ) );

		$this->assertSame( array( '/2019-06-14', '2024-10-20/2024-11-02' ), Meta::get_readings( $book_id ) );
	}

	/**
	 * Read again, a book read has a reading in progress beside its readings ended; marked as unread, it keeps it.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_keeps_its_readings(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'reading' => array( '2024-03-02/2024-03-20', '2026-11-01/..' ) ) ) );

		$this->assertSame( array( '2024-03-20' ), Meta::get_read_dates( $book_id ) );
		$this->assertTrue( (bool) get_post_meta( $book_id, 'library_read', true ) );
		$this->assertTrue( (bool) get_post_meta( $book_id, 'library_in_progress', true ) );

		$this->save( $book_id, array( 'read' => false ) );

		$this->assertSame( array( '2026-11-01/..' ), Meta::get_readings( $book_id ) );
	}

	/**
	 * A start or an end saved with its time keeps it when a form gives its day, as it shows it.
	 *
	 * @return void
	 */
	public function test_a_day_given_keeps_the_time_saved(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'reading' => array( '2026-11-01T09:15/2026-11-01T21:40' ) ) ) );

		$this->save( $book_id, array( 'reading' => array( '2026-11-01/2026-11-01' ) ) );

		$this->assertSame( array( '2026-11-01T09:15/2026-11-01T21:40' ), Meta::get_readings( $book_id ) );
	}

	/**
	 * A start after the end is left unknown, an invalid date too, and the readings come in order, the ones of an unknown
	 * end first.
	 *
	 * @return void
	 */
	public function test_readings_are_sanitized_and_in_order(): void {
		$this->assertSame( '/2024-03-01', Meta::sanitize_reading( '2024-04-01/2024-03-01' ) );
		$this->assertSame( '/2024-03-01', Meta::sanitize_reading( 'soon/2024-03-01' ) );
		$this->assertSame( '/', Meta::sanitize_reading( 'anything' ) );
		$this->assertSame( '2026-11-08T09:15/..', Meta::sanitize_reading( '2026-11-08T09:15/..' ) );

		$book_id = $this->add_book( array( 'meta' => array( 'reading' => array( '/2024-03-01', '2026-11-01/..', '/', '2023-01-01/2023-02-01' ) ) ) );

		$this->assertSame( array( '/', '2023-01-01/2023-02-01', '/2024-03-01', '2026-11-01/..' ), Meta::get_readings( $book_id ) );
		$this->assertSame( array( '', '2023-02-01', '2024-03-01' ), Meta::get_read_dates( $book_id ) );
	}

	/**
	 * Quick edit changes the end of the latest reading, its start kept.
	 *
	 * @return void
	 */
	public function test_quick_edit_keeps_the_start_of_the_latest_reading(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'reading' => array( '/2019-06-14', '2024-03-02/2024-03-20' ) ) ) );

		$this->assertSame( array( '/2019-06-14', '2024-03-02/2024-03-25' ), Meta::replace_latest_read_date( $book_id, '2024-03-25' ) );
	}

	/**
	 * The searches read the readings: read, read in a month, being read, the latest started first.
	 *
	 * @return void
	 */
	public function test_searches_read_the_readings(): void {
		$read        = $this->add_book( array( 'meta' => array( 'reading' => array( '2024-03-02/2024-03-20' ) ) ) );
		$started     = $this->add_book( array( 'meta' => array( 'reading' => array( '2026-10-01/..' ) ) ) );
		$started_now = $this->add_book( array( 'meta' => array( 'reading' => array( '2026-11-01/..' ) ) ) );
		$unread      = $this->add_book();

		$this->assertSame( array( $read ), $this->find( array( 'search' => 'read:yes' ) ) );
		$this->assertEqualsCanonicalizing( array( $started, $started_now, $unread ), $this->find( array( 'search' => 'read:no' ) ) );
		$this->assertSame( array( $read ), $this->find( array( 'search' => 'read:2024-03' ) ) );
		$this->assertSame(
			array( $started_now, $started ),
			$this->find(
				array(
					'search'  => 'progress:yes',
					'orderby' => 'started',
				)
			)
		);
	}
}
