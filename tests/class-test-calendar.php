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
 * The route of the calendar of a year, `library/v1/calendars/<year>`: the level of each day gone by and the pages read on
 * it, the books whose reading ended on each day and the books read on each day.
 */
class Test_Calendar extends TestCase {

	/**
	 * A day is read while a reading is open, on its end alone when its start is unknown; the pages of a book read from a
	 * known start spread over its days, which give the levels, from the quarter of the days that read the fewest pages; a
	 * day read whose pages are unknown is at the first one.
	 *
	 * @return void
	 */
	public function test_days(): void {
		$first  = $this->add_book( $this->read_from( '2025-03-01/2025-03-03', 300, 7 ) );
		$second = $this->add_book( $this->read_from( '2025-03-03/2025-03-03', 50, 9 ) );
		$third  = $this->add_book( $this->read_from( '/2025-03-10', 200, 9 ) );
		$fourth = $this->add_book( $this->read_from( '2024-12-30/2025-01-02', 40 ) );

		$calendar = $this->get_calendar( 2025 );

		$this->assertCount( 365, $calendar['levels'] );
		// January 1st and 2nd, March 1st to 3rd and 10th: 10, 10, 100, 100, 150 pages and an unknown number.
		$this->assertSame(
			array(
				0  => 1,
				1  => 1,
				59 => 2,
				60 => 2,
				61 => 4,
				68 => 1,
			),
			array_filter( $calendar['levels'] )
		);
		$this->assertSame( 150, $calendar['pages'][61] );
		$this->assertSame( 0, $calendar['pages'][68] );
		$this->assertSame( array( 1, 61, 68 ), array_keys( (array) $calendar['ends'] ) );
		$this->assertSame( array( $fourth ), ( (array) $calendar['ends'] )[1] );
		$this->assertEqualsCanonicalizing( array( $first, $second ), ( (array) $calendar['ends'] )[61] );
		$this->assertSame( array( $third ), ( (array) $calendar['ends'] )[68] );
	}

	/**
	 * A reading in progress, which may have been left for years, gives no day its level until it ends, and is read today
	 * alone; no day after today is gone by.
	 *
	 * @return void
	 */
	public function test_reading_in_progress(): void {
		$today = new DateTimeImmutable( 'now', wp_timezone() );
		$year  = (int) $today->format( 'Y' );
		$days  = (int) $today->format( 'z' ) + 1;

		$this->add_read( "$year-01-01" );
		$reading = $this->add_book( $this->read_from( '2022-09-04/..', 300 ) );

		$calendar = $this->get_calendar( $year );

		$this->assertCount( $days, $calendar['levels'] );
		$this->assertCount( 1, array_filter( $calendar['levels'] ) );
		// January 1st, a reading ended, and today, the one in progress.
		$this->assertSame( array_values( array_unique( array( 0, $days - 1 ) ) ), array_keys( (array) $calendar['reads'] ) );
		$this->assertContains( $reading, ( (array) $calendar['reads'] )[ $days - 1 ] );
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
