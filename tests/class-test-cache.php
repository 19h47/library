<?php
/**
 * Tests of the cache of what is computed from every book.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Cache;

/**
 * Each reader has a cache of their own: what is computed for one is never given to another.
 */
class Test_Cache extends TestCase {

	/**
	 * A value kept for a reader is theirs: another reader computes their own, and the first one gets theirs back.
	 *
	 * @return void
	 */
	public function test_each_reader_has_their_own_cache(): void {
		$first = $this->as_user( 'library_reader' );

		$this->assertSame( 'first', Cache::remember( 'shelves', fn() => 'first' ) );

		$this->as_user( 'library_reader' );

		$this->assertSame( 'second', Cache::remember( 'shelves', fn() => 'second' ) );

		wp_set_current_user( $first );

		$this->assertSame( 'first', Cache::remember( 'shelves', fn() => 'computed again' ) );
	}
}
