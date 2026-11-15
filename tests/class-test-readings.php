<?php
/**
 * Tests of the readings of a book: one row each, from its start to its end, and whether it is read and being read, which
 * they tell.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Reading;
use Library\Readings;
use Library\Shelf;
use WP_REST_Request;

/**
 * A reading in progress has its start and no end; a reading added ends it, from its start; a form gives days, which keep
 * the times saved.
 */
class Test_Readings extends TestCase {

	/**
	 * Save the copy of the reader as their forms do, through the route of its shelf, its readings as intervals, as
	 * add_book() takes them.
	 *
	 * @param int                 $book_id Book ID.
	 * @param array<string,mixed> $copy    Copy, as the route takes it: readings, read, in_progress, rating.
	 * @return void
	 */
	private function save( int $book_id, array $copy ): void {
		if ( isset( $copy['readings'] ) ) {
			$copy['readings'] = self::readings( $copy['readings'] );
		}

		$request = new WP_REST_Request( 'POST', "/library/v1/books/$book_id/shelf" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $copy ) );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
	}

	/**
	 * The copy of the current reader, by meta key: see Shelf::get_values().
	 *
	 * @param int $book_id Book ID.
	 * @return array<string,mixed>
	 */
	private function copy( int $book_id ): array {
		return Shelf::get_values( $book_id, get_current_user_id() );
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

		[ $reading ] = Shelf::get_readings( $book_id, get_current_user_id() );
		$start       = $reading->start;

		$this->assertSame( Reading::IN_PROGRESS, $reading->end );
		$this->assertStringStartsWith( current_time( 'Y-m-d' ) . 'T', $start );
		$this->assertTrue( $this->copy( $book_id )['library_in_progress'] );
		$this->assertFalse( $this->copy( $book_id )['library_read'] );

		$this->save( $book_id, array( 'read' => true ) );

		[ $reading ] = Shelf::get_readings( $book_id, get_current_user_id() );

		$this->assertSame( $start, $reading->start );
		$this->assertStringStartsWith( current_time( 'Y-m-d' ) . 'T', $reading->end );
		$this->assertFalse( $this->copy( $book_id )['library_in_progress'] );
		$this->assertTrue( $this->copy( $book_id )['library_read'] );
	}

	/**
	 * A reading added ends the one in progress, which gives it its start; the readings before stay.
	 *
	 * @return void
	 */
	public function test_a_reading_added_ends_the_one_in_progress(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2019-06-14', '2024-10-20/..' ) ) ) );

		$this->save( $book_id, array( 'readings' => array( '/2019-06-14', '/2024-11-02', '2024-10-20/..' ) ) );

		$this->assertSame( array( '/2019-06-14', '2024-10-20/2024-11-02' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );
	}

	/**
	 * Read again, a book read has a reading in progress beside its readings ended; marked as unread, it keeps it.
	 *
	 * @return void
	 */
	public function test_a_book_read_again_keeps_its_readings(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '2024-03-02/2024-03-20', '2026-11-01/..' ) ) ) );

		$this->assertSame( array( '2024-03-20' ), Shelf::get_read_dates( $book_id, get_current_user_id() ) );
		$this->assertTrue( $this->copy( $book_id )['library_read'] );
		$this->assertTrue( $this->copy( $book_id )['library_in_progress'] );

		$this->save( $book_id, array( 'read' => false ) );

		$this->assertSame( array( '2026-11-01/..' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );
	}

	/**
	 * A start or an end saved with its time keeps it when a form gives its day, as it shows it.
	 *
	 * @return void
	 */
	public function test_a_day_given_keeps_the_time_saved(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '2026-11-01T09:15/2026-11-01T21:40' ) ) ) );

		$this->save( $book_id, array( 'readings' => array( '2026-11-01/2026-11-01' ) ) );

		$this->assertSame( array( '2026-11-01T09:15/2026-11-01T21:40' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );
	}

	/**
	 * A start after the end is left unknown, an invalid date too, and the readings come in order, the ones of an unknown
	 * end first.
	 *
	 * @return void
	 */
	public function test_readings_are_sanitized_and_in_order(): void {
		$sanitized = fn( $reading ) => self::intervals( array( Reading::from( Readings::sanitize( is_string( $reading ) ? self::reading( $reading ) : $reading ) ) ) )[0];

		$this->assertSame( '/2024-03-01', $sanitized( '2024-04-01/2024-03-01' ) );
		$this->assertSame( '/2024-03-01', $sanitized( 'soon/2024-03-01' ) );
		$this->assertSame( '/', $sanitized( array( 'anything' ) ) );
		$this->assertSame( '/', $sanitized( 'anything' ) );
		$this->assertSame( '2026-11-08T09:15/..', $sanitized( '2026-11-08T09:15/..' ) );
		$this->assertSame( '/2024-03-01#7.5', $sanitized( '/2024-03-01#7.4' ) );
		$this->assertSame( '/2024-03-01', $sanitized( '/2024-03-01#11' ) );
		$this->assertSame( '2026-11-08/..', $sanitized( '2026-11-08/..#8' ) );

		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2024-03-01', '2026-11-01/..', '/', '2023-01-01/2023-02-01' ) ) ) );

		$this->assertSame( array( '/', '2023-01-01/2023-02-01', '/2024-03-01', '2026-11-01/..' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );
		$this->assertSame( array( '', '2023-02-01', '2024-03-01' ), Shelf::get_read_dates( $book_id, get_current_user_id() ) );
	}

	/**
	 * The searches read the readings: read, read in a month, being read, the latest started first.
	 *
	 * @return void
	 */
	public function test_searches_read_the_readings(): void {
		$read        = $this->add_book( array( 'shelf' => array( 'readings' => array( '2024-03-02/2024-03-20' ) ) ) );
		$started     = $this->add_book( array( 'shelf' => array( 'readings' => array( '2026-10-01/..' ) ) ) );
		$started_now = $this->add_book( array( 'shelf' => array( 'readings' => array( '2026-11-01/..' ) ) ) );
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

	/**
	 * The rating of a book is the one of its latest reading rated: read again without rating, it keeps the one before;
	 * rated again, it takes the new one. A reading in progress has none.
	 *
	 * @return void
	 */
	public function test_the_rating_of_a_book_is_the_one_of_its_latest_reading_rated(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2019-06-14#6', '2024-03-02/2024-03-20' ) ) ) );

		$this->assertSame( 6.0, $this->copy( $book_id )['library_rating'] );

		$this->save( $book_id, array( 'readings' => array( '/2019-06-14#6', '2024-03-02/2024-03-20#9', '2026-11-01/..#7' ) ) );

		$this->assertSame( array( '/2019-06-14#6', '2024-03-02/2024-03-20#9', '2026-11-01/..' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );
		$this->assertSame( 9.0, $this->copy( $book_id )['library_rating'] );
		$this->assertNull( $this->copy( $this->add_book() )['library_rating'] );
	}

	/**
	 * The rating of a book given rates its latest reading ended, as quick edit does; null, it takes it away; a book not
	 * read has no reading to rate.
	 *
	 * @return void
	 */
	public function test_a_rating_given_rates_the_latest_reading(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2019-06-14#6', '2024-03-02/2024-03-20', '2026-11-01/..' ) ) ) );

		$this->save( $book_id, array( 'rating' => 8.5 ) );

		$this->assertSame( array( '/2019-06-14#6', '2024-03-02/2024-03-20#8.5', '2026-11-01/..' ), self::intervals( Shelf::get_readings( $book_id, get_current_user_id() ) ) );

		$this->save( $book_id, array( 'rating' => null ) );

		$this->assertSame( 6.0, $this->copy( $book_id )['library_rating'] );

		$unread = $this->add_book( array( 'shelf' => array( 'rating' => 8 ) ) );

		$this->assertSame( array(), Shelf::get_readings( $unread, get_current_user_id() ) );
		$this->assertFalse( $this->copy( $unread )['library_read'] );
	}

	/**
	 * The REST API gives the readings of the reader as objects, in the shelf of a book, a start or an end unknown and no
	 * rating null.
	 *
	 * @return void
	 */
	public function test_the_api_gives_the_readings_as_objects(): void {
		$book_id = $this->add_book( array( 'shelf' => array( 'readings' => array( '/', '2024-03-02/2024-03-20#8' ) ) ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/books/' . $book_id );

		$this->assertSame(
			array(
				array(
					'start'  => null,
					'end'    => null,
					'rating' => null,
				),
				array(
					'start'  => '2024-03-02',
					'end'    => '2024-03-20',
					'rating' => 8.0,
				),
			),
			rest_do_request( $request )->get_data()['shelf']['readings']
		);
	}

	/**
	 * The search of a rating and the order by rating read the latest reading rated of each book.
	 *
	 * @return void
	 */
	public function test_searches_read_the_rating_of_the_latest_reading(): void {
		$rated_again = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2019-06-14#9', '/2024-03-20#6' ) ) ) );
		$rated_once  = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2024-01-01#8' ) ) ) );
		$kept        = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2019-06-14#7', '/2024-03-20' ) ) ) );
		$unrated     = $this->add_book( array( 'shelf' => array( 'readings' => array( '/2024-01-01' ) ) ) );

		$this->assertEqualsCanonicalizing( array( $rated_once, $kept ), $this->find( array( 'search' => 'rating:7' ) ) );
		$this->assertSame(
			array( $rated_once, $kept, $rated_again, $unrated ),
			$this->find(
				array(
					'orderby'  => 'rating',
					'per_page' => 10,
				)
			)
		);
	}

	/**
	 * The route of a shelf checks the readings it takes against their schema, as the reference of the API shows them.
	 *
	 * @return void
	 */
	public function test_the_route_checks_the_readings_against_their_schema(): void {
		$book_id = $this->add_book();
		$request = new WP_REST_Request( 'POST', "/library/v1/books/$book_id/shelf" );

		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( array( 'readings' => array( self::reading( 'soon/2024-03-01#11' ) ) ) ) );

		$this->assertSame( 400, rest_do_request( $request )->get_status() );
		$this->assertSame( array(), Shelf::get_readings( $book_id, get_current_user_id() ) );

		$route = rest_get_server()->get_routes()['/library/v1/books/(?P<id>\\d+)/shelf'];
		$args  = array_merge( ...array_column( $route, 'args' ) );

		$this->assertSame( 'library-reading', $args['readings']['items']['title'] );
	}
}
