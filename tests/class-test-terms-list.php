<?php
/**
 * Tests of the terms lists of the admin: the books of a term by status, and the terms without books.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Terms_List;
use Library\Posts;

/**
 * A term counts its books in each status, with those of the terms under it; a term without books has none, in any status.
 */
class Test_Terms_List extends TestCase {

	/**
	 * A column counts the books of its status, linked to them.
	 *
	 * @return void
	 */
	public function test_a_column_counts_the_books_of_its_status(): void {
		$this->add_book( $this->by( 'Stephen King' ) );
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Posts::NOT_KEPT ) );
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Posts::NOT_KEPT ) );
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Posts::TO_BUY ) );

		$terms_list = new Terms_List();
		$king       = get_term_by( 'name', 'Stephen King', 'library_person' )->term_id;
		$not_kept   = $terms_list->render_status_column( '', 'library_not_kept', $king );

		$this->assertStringContainsString( '>2</a>', $not_kept );
		$this->assertStringContainsString( 'post_status=' . Posts::NOT_KEPT, $not_kept );
		$this->assertStringContainsString( '>1</a>', $terms_list->render_status_column( '', 'library_to_buy', $king ) );
	}

	/**
	 * The name of a person counts the books of their other names.
	 *
	 * @return void
	 */
	public function test_a_name_counts_the_books_of_other_names(): void {
		$king = wp_insert_term( 'Stephen King', 'library_person' )['term_id'];

		wp_insert_term( 'Richard Bachman', 'library_person', array( 'parent' => $king ) );
		$this->add_book( $this->by( 'Richard Bachman' ) + array( 'status' => Posts::TO_BUY ) );

		$this->assertStringContainsString( '>1</a>', new Terms_List()->render_status_column( '', 'library_to_buy', $king ) );
	}

	/**
	 * The view of the terms without books leaves out a term with books, or whose other names have some, in any status;
	 * a book in the trash is not one.
	 *
	 * @return void
	 */
	public function test_the_view_lists_the_terms_without_books(): void {
		$king = wp_insert_term( 'Stephen King', 'library_person' )['term_id'];

		wp_insert_term( 'Richard Bachman', 'library_person', array( 'parent' => $king ) );
		wp_insert_term( 'Don Rosa', 'library_person' );
		$this->add_book( $this->by( 'Richard Bachman' ) + array( 'status' => Posts::NOT_KEPT ) );
		$this->add_book( $this->by( 'Fred Vargas' ) + array( 'status' => Posts::TO_BUY ) );
		wp_trash_post( $this->add_book( $this->by( 'Romano Scarpa' ) ) );

		$terms_list = new Terms_List();

		add_filter( 'get_terms_args', array( $terms_list, 'exclude_terms_with_books' ), 10, 2 );

		$names = get_terms(
			array(
				'taxonomy'   => 'library_person',
				'hide_empty' => false,
				'fields'     => 'names',
				'orderby'    => 'name',
			)
		);

		remove_filter( 'get_terms_args', array( $terms_list, 'exclude_terms_with_books' ) );

		$this->assertSame( array( 'Don Rosa', 'Romano Scarpa' ), $names );
	}
}
