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
 * readings in all; and the forms of the calendar of a year, `library/v1/calendars/<year>`, as the diary opens it.
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
	 * The calendar of a year: a level for each day gone by, a day read without its pages known at the first one, and the
	 * books whose reading ended on each day; with forms, only those of the books of one of them, the readings of each form
	 * counted all the same.
	 *
	 * @return void
	 */
	public function test_calendar_of_the_forms_asked_for(): void {
		$novel = $this->get_form( 'Novel' );
		$comic = $this->get_form( 'Comic' );

		[ $first_novel, $second_novel ] = $this->add_reads( array( '2025-01-02', '2025-03-01' ), array( 'form' => $novel ) );
		$comic_book                     = $this->add_read( '2025-03-01', array( 'form' => $comic ) );

		$all  = $this->get_calendar( 2025, array() )->get_data();
		$ends = (array) $all['ends'];

		$this->assertSame( 2025, $all['year'] );
		$this->assertContains( 2025, $all['years'] );
		$this->assertCount( 365, $all['levels'] );
		$this->assertSame( 1, $all['levels'][1] );
		$this->assertSame( 0, $all['levels'][0] );
		$this->assertSame( array( 1, 59 ), array_keys( $ends ) );
		$this->assertSame( array( $first_novel ), $ends[1] );
		$this->assertEqualsCanonicalizing( array( $second_novel, $comic_book ), $ends[59] );

		$comics = $this->get_calendar( 2025, array( 'forms' => $comic ) )->get_data();

		$this->assertSame( array( 59 => array( $comic_book ) ), (array) $comics['ends'] );
		// The readings of each form ended on each day, of every form, which its filter counts for what it shows.
		$form_ends = (array) $comics['form_ends'];

		$this->assertSame( array( 1, 59 ), array_keys( $form_ends ) );
		$this->assertSame( array( $novel => 1 ), (array) $form_ends[1] );
		$this->assertEqualsCanonicalizing(
			array(
				$novel => 1,
				$comic => 1,
			),
			(array) $form_ends[59]
		);
	}

	/**
	 * The books read on each day of a year: a reading ended, from its start to its end, its pages spread over them; a
	 * reading in progress, of a year gone by, on none of its days, and without a level.
	 *
	 * @return void
	 */
	public function test_calendar_days_read(): void {
		$read    = $this->add_book(
			array(
				'meta'  => array( 'pages' => 300 ),
				'shelf' => array( 'readings' => array( '2025-02-20/2025-02-22' ) ),
			)
		);
		$reading = $this->add_book( array( 'shelf' => array( 'readings' => array( '2025-12-30/..' ) ) ) );

		$calendar = $this->get_calendar( 2025, array() )->get_data();
		$reads    = (array) $calendar['reads'];

		$this->assertSame( array( 50, 51, 52 ), array_keys( $reads ) );
		$this->assertSame( array( $read ), $reads[51] );
		$this->assertNotContains( $reading, array_merge( ...array_values( $reads ) ) );
		$this->assertSame( 100, $calendar['pages'][50] );
		$this->assertSame( 0, $calendar['levels'][364] );
		$this->assertSame( array( 52 ), array_keys( (array) $calendar['ends'] ) );
	}

	/**
	 * The calendar of a year.
	 *
	 * @param int                 $year   Year.
	 * @param array<string,mixed> $params Parameters of the request.
	 * @return WP_REST_Response
	 */
	private function get_calendar( int $year, array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', "/library/v1/calendars/$year" );

		$request->set_query_params( $params );

		return rest_do_request( $request );
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
