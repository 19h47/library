<?php
/**
 * Tests of the box of the shelf of the reader on the screen of a book.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Metaboxes;
use Library\Shelf;
use Library\Status;

/**
 * The box saves the shelf of the reader as the route of the shelf does: where the book goes, their copy, and off their
 * shelves when they take it off, unless they read it.
 */
class Test_Shelf_Box extends TestCase {

	/**
	 * Save the box of a book as its form sends it.
	 *
	 * @param int                 $book_id Book ID.
	 * @param array<string,mixed> $fields  Fields of the box, such as library_shelf_status.
	 * @return void
	 */
	private function save_box( int $book_id, array $fields ): void {
		$_POST = $fields + array( 'library_shelf_nonce' => wp_create_nonce( 'library_shelf' ) );

		new Metaboxes()->save_shelf( $book_id );

		$_POST = array();
	}

	/**
	 * Notes given to a book on none of the shelves of the reader, the box saying none, put it among the books not kept.
	 *
	 * @return void
	 */
	public function test_notes_put_a_book_on_a_shelf(): void {
		$book_id = $this->add_book();
		$reader  = $this->as_user( 'library_reader' );

		$this->save_box(
			$book_id,
			array(
				'library_shelf_status' => '',
				'library_notes'        => 'Lent by Ada.',
			)
		);

		$this->assertSame( Status::Not_Kept, Shelf::get_status( $book_id, $reader ) );
		$this->assertSame( 'Lent by Ada.', Shelf::get_values( $book_id, $reader )['library_notes'] );
	}

	/**
	 * A book taken off goes, unless the reader read it, which keeps it, and the screen says so.
	 *
	 * @return void
	 */
	public function test_a_book_taken_off_goes_unless_read(): void {
		$kept   = $this->add_book();
		$read   = $this->add_read( '2024-03-14' );
		$reader = get_current_user_id();

		$this->save_box( $kept, array( 'library_shelf_status' => '' ) );
		$this->save_box(
			$read,
			array(
				'library_shelf_status'   => '',
				'library_read'           => '1',
				'library_reading_start'  => array( '' ),
				'library_reading_end'    => array( '2024-03-14' ),
				'library_reading_rating' => array( '' ),
			)
		);

		$this->assertNull( Shelf::get_status( $kept, $reader ) );
		$this->assertSame( Status::In_Library, Shelf::get_status( $read, $reader ) );
		$this->assertStringContainsString( 'library_kept_book=1', apply_filters( 'redirect_post_location', 'post.php' ) );
	}
}
