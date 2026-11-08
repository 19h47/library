<?php
/**
 * Tests of the statuses of a book: in the library, not kept or to buy.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Status;

/**
 * A book keeps its status through the trash.
 */
class Test_Statuses extends TestCase {

	/**
	 * A book restored from the trash comes back in its status, rather than as a draft.
	 *
	 * @return void
	 */
	public function test_a_book_restored_from_the_trash_keeps_its_status(): void {
		foreach ( Status::cases() as $status ) {
			$book_id = $this->add_book( array( 'status' => $status->value ) );

			wp_trash_post( $book_id );
			wp_untrash_post( $book_id );

			$this->assertSame( $status->value, get_post_status( $book_id ) );
		}
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
