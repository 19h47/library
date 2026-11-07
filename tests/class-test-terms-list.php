<?php
/**
 * Tests of the terms lists of the admin: the books of a term by status, and the terms without books.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Terms_List;
use Library\Posts;
use Library\Status;

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
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Status::Not_Kept->value ) );
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Status::Not_Kept->value ) );
		$this->add_book( $this->by( 'Stephen King' ) + array( 'status' => Status::To_Buy->value ) );

		$terms_list = new Terms_List();
		$king       = get_term_by( 'name', 'Stephen King', 'library_person' )->term_id;
		$not_kept   = $terms_list->render_status_column( '', 'library_not_kept', $king );

		$this->assertStringContainsString( '>2</a>', $not_kept );
		$this->assertStringContainsString( 'post_status=' . Status::Not_Kept->value, $not_kept );
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
		$this->add_book( $this->by( 'Richard Bachman' ) + array( 'status' => Status::To_Buy->value ) );

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
		$this->add_book( $this->by( 'Richard Bachman' ) + array( 'status' => Status::Not_Kept->value ) );
		$this->add_book( $this->by( 'Fred Vargas' ) + array( 'status' => Status::To_Buy->value ) );
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

	/**
	 * The view of the languages without books leaves out a language that is only the one of the text of a book, an
	 * edition language: deleting it would leave the book without it.
	 *
	 * @return void
	 */
	public function test_the_view_of_the_languages_leaves_out_the_edition_languages(): void {
		$english = $this->get_language( 'en', 'English' );
		$french  = $this->get_language( 'fr', 'French' );

		$this->get_language( 'de', 'German' );
		$this->add_book(
			array(
				'languages' => array( $english ),
				'meta'      => array( 'library_edition_languages' => array( $french ) ),
			)
		);

		$terms_list = new Terms_List();

		add_filter( 'get_terms_args', array( $terms_list, 'exclude_terms_with_books' ), 10, 2 );

		$names = get_terms(
			array(
				'taxonomy'   => 'library_language',
				'hide_empty' => false,
				'fields'     => 'names',
			)
		);

		remove_filter( 'get_terms_args', array( $terms_list, 'exclude_terms_with_books' ) );

		$this->assertSame( array( 'German' ), $names );
	}
}
