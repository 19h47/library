<?php
/**
 * Tests of the streak: months in a row with a reading.
 *
 * @package Library
 */

namespace Library\Tests;

/**
 * The longest run of months in a row with a reading, from six months.
 */
class Test_Achievements_Streak extends TestCase {

	/**
	 * Six months in a row earn it, dated by the last reading of the run, across a new year.
	 *
	 * @return void
	 */
	public function test_six_months_in_a_row(): void {
		$this->add_reads( array( '2024-09-03', '2024-10-12', '2024-11-30', '2024-12-01', '2025-01-15', '2025-02-01', '2025-02-20' ) );

		$streak = $this->get_earned()['streak'];

		$this->assertCount( 1, $streak );
		$this->assertSame( '6 months in a row', $streak[0]['title'] );
		$this->assertSame( 'September 2024 to February 2025', $streak[0]['detail'] );
		$this->assertSame( '2025-02-20', $streak[0]['date'] );
	}

	/**
	 * Five months in a row earn nothing, and a month without reading breaks the run.
	 *
	 * @return void
	 */
	public function test_a_month_missing_breaks_the_run(): void {
		$this->add_reads( array( '2024-01-10', '2024-02-10', '2024-03-10', '2024-05-10', '2024-06-10', '2024-07-10', '2024-08-10', '2024-09-10' ) );

		$this->assertArrayNotHasKey( 'streak', $this->get_earned() );
	}
}
