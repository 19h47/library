<?php
/**
 * Tests of the days of reading of a year, which the calendar shows and the recap of a year reads.
 *
 * @package Library
 */

namespace Library\Tests;

use DateTimeImmutable;
use WP_REST_Request;

/**
 * The route of the calendar of a year, `library/v1/calendars/<year>`: the level of each day gone by, the pages finished
 * on it and the books finished on it.
 */
class Test_Calendar extends TestCase {

	/**
	 * A day counts on the end of a reading finished, its start left aside; the pages of the books finished that day give
	 * the levels, from the quarter of the days that finished the fewest pages; a day whose pages are unknown is at the
	 * first one.
	 *
	 * @return void
	 */
	public function test_days(): void {
		$first   = $this->add_book( $this->read_from( '2025-03-01/2025-03-03', 300, 7 ) );
		$second  = $this->add_book( $this->read_from( '2025-03-03/2025-03-03', 50, 9 ) );
		$third   = $this->add_book( $this->read_from( '/2025-03-10', 200, 9 ) );
		$fourth  = $this->add_book( $this->read_from( '2024-12-30/2025-01-02', 40 ) );
		$unpaged = $this->add_read( '2025-05-01' );

		$calendar = $this->get_calendar( 2025 );

		$this->assertCount( 365, $calendar['levels'] );
		// January 2nd, March 3rd and 10th, May 1st: 40, 350, 200 pages and an unknown number.
		$this->assertSame(
			array(
				1   => 1,
				61  => 4,
				68  => 2,
				120 => 1,
			),
			array_filter( $calendar['levels'] )
		);
		$this->assertSame(
			array(
				1  => 40,
				61 => 350,
				68 => 200,
			),
			array_filter( $calendar['pages'] )
		);
		$this->assertArrayNotHasKey( 'reads', $calendar );
		$this->assertSame( array( 1, 61, 68, 120 ), array_keys( (array) $calendar['ends'] ) );
		$this->assertSame( array( $fourth ), ( (array) $calendar['ends'] )[1] );
		$this->assertEqualsCanonicalizing( array( $first, $second ), ( (array) $calendar['ends'] )[61] );
		$this->assertSame( array( $third ), ( (array) $calendar['ends'] )[68] );
		$this->assertSame( array( $unpaged ), ( (array) $calendar['ends'] )[120] );
	}

	/**
	 * A reading abandoned finishes no day.
	 *
	 * @return void
	 */
	public function test_reading_abandoned(): void {
		$this->add_book( $this->read_from( '2025-04-01/2025-04-03#abandoned', 300 ) );

		$calendar = $this->get_calendar( 2025 );

		$this->assertSame( array(), array_filter( $calendar['levels'] ) );
		$this->assertSame( array(), array_filter( $calendar['pages'] ) );
		$this->assertSame( array(), (array) $calendar['ends'] );
	}

	/**
	 * A reading in progress finishes no day, today neither; no day after today is gone by.
	 *
	 * @return void
	 */
	public function test_reading_in_progress(): void {
		$today = new DateTimeImmutable( 'now', wp_timezone() );
		$year  = (int) $today->format( 'Y' );
		$days  = (int) $today->format( 'z' ) + 1;

		$read = $this->add_read( "$year-01-01" );
		$this->add_book( $this->read_from( '2022-09-04/..', 300 ) );

		$calendar = $this->get_calendar( $year );

		$this->assertCount( $days, $calendar['levels'] );
		$this->assertSame( array( 0 => 1 ), array_filter( $calendar['levels'] ) );
		$this->assertSame( array( 0 => array( $read ) ), (array) $calendar['ends'] );
	}

	/**
	 * The calendar of a year, as the route gives it.
	 *
	 * @param int $year Year.
	 * @return array<string,mixed>
	 */
	private function get_calendar( int $year ): array {
		return rest_do_request( new WP_REST_Request( 'GET', "/library/v1/calendars/$year" ) )->get_data();
	}

	/**
	 * A book read once, from its start to its end, with its pages and its rating.
	 *
	 * @param string     $reading Reading, start/end.
	 * @param int        $pages   Number of pages.
	 * @param float|null $rating  Rating, none when null.
	 * @return array<string,mixed>
	 */
	private function read_from( string $reading, int $pages, ?float $rating = null ): array {
		return array(
			'meta'  => array( 'pages' => $pages ),
			'shelf' => array_filter(
				array(
					'readings' => array( $reading ),
					'rating'   => $rating,
				)
			),
		);
	}
}
