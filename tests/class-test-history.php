<?php
/**
 * Tests of the history of the record of a book, its revisions.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\History;
use Library\Shelf;
use Library\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * A book keeps its record as each reader saved it, its meta and its names among them, and restores it; the shelves of
 * the readers are theirs, never revisioned.
 */
class Test_History extends TestCase {

	/**
	 * Edit a book through the route of the book form.
	 *
	 * @param int                 $book_id Book ID.
	 * @param array<string,mixed> $book    Fields of the book, as the route takes them.
	 * @return WP_REST_Response
	 */
	private function edit( int $book_id, array $book ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', "/library/v1/books/$book_id" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $book ) );

		return rest_do_request( $request );
	}

	/**
	 * A book added has a revision at once, by the reader who added it; edited by another, one more, by them, with the meta
	 * written, not the ones of before.
	 *
	 * @return void
	 */
	public function test_each_save_keeps_the_record_by_its_reader(): void {
		$first   = $this->as_user( 'library_librarian' );
		$book_id = $this->add_book( array( 'meta' => array( 'pages' => 320 ) ) );

		$this->assertCount( 1, wp_get_post_revisions( $book_id ) );
		$this->assertSame( $first, (int) current( wp_get_post_revisions( $book_id ) )->post_author );

		$second = $this->as_user( 'library_librarian' );

		$this->edit( $book_id, array( 'meta' => array( 'pages' => 336 ) ) );

		$latest = current( wp_get_post_revisions( $book_id ) );

		$this->assertCount( 2, wp_get_post_revisions( $book_id ) );
		$this->assertSame( $second, (int) $latest->post_author );
		$this->assertSame( '336', get_metadata( 'post', $latest->ID, 'library_pages', true ) );
	}

	/**
	 * A change of names alone is one, which restoring the revision before takes back, with its meta.
	 *
	 * @return void
	 */
	public function test_a_revision_restores_the_names_and_the_meta(): void {
		$book_id = $this->add_book( array( 'meta' => array( 'pages' => 320 ) ) + $this->by( 'Julien Gracq' ) );
		$first   = current( wp_get_post_revisions( $book_id ) );

		$this->edit( $book_id, array( 'meta' => array( 'pages' => 336 ) ) + $this->by( 'Louis Poirier' ) );

		$this->assertCount( 2, wp_get_post_revisions( $book_id ) );

		wp_restore_post_revision( $first->ID );

		$this->assertSame( '320', get_post_meta( $book_id, 'library_pages', true ) );
		$this->assertSame( array( 'Julien Gracq' ), wp_get_object_terms( $book_id, 'library_person', array( 'fields' => 'names' ) ) );
	}

	/**
	 * A book saved as it was, or shelved, read or noted by a reader, keeps the revisions it had.
	 *
	 * @return void
	 */
	public function test_the_shelves_are_never_revisioned(): void {
		$book_id = $this->add_book( $this->by( 'Julien Gracq' ) );

		$this->edit( $book_id, $this->by( 'Julien Gracq' ) );
		Shelf::set_status( $book_id, get_current_user_id(), Status::Not_Kept );
		Shelf::save( $book_id, get_current_user_id(), array( 'library_notes' => 'Lent by Ada.' ) );

		$this->assertCount( 1, wp_get_post_revisions( $book_id ) );
	}

	/**
	 * The comparison of two revisions shows the meta and the names of each, by their labels.
	 *
	 * @return void
	 */
	public function test_the_comparison_shows_the_meta_and_the_names(): void {
		require_once ABSPATH . 'wp-admin/includes/revision.php';

		$book_id = $this->add_book( array( 'meta' => array( 'pages' => 320 ) ) + $this->by( 'Julien Gracq' ) );
		$first   = current( wp_get_post_revisions( $book_id ) );

		$this->edit( $book_id, array( 'meta' => array( 'pages' => 336 ) ) + $this->by( 'Louis Poirier' ) );

		$latest = current( wp_get_post_revisions( $book_id ) );

		new History()->show_fields();

		$diff = array_column( wp_get_revision_ui_diff( $book_id, $first->ID, $latest->ID ), 'diff', 'name' );

		$this->assertStringContainsString( '336', $diff['Pages'] );
		$this->assertStringContainsString( 'Louis Poirier', $diff['Names'] );
	}
}
