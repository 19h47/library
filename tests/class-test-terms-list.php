<?php
/**
 * Tests of the terms lists of the admin: the terms without books.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Terms_List;
use Library\Posts;
use Library\Status;

/**
 * A term without books has none, nor have the terms under it.
 */
class Test_Terms_List extends TestCase {

	/**
	 * The view of the terms without books leaves out a term with books, or whose other names have some, whatever their
	 * shelves; a book in the trash is not one.
	 *
	 * @return void
	 */
	public function test_the_view_lists_the_terms_without_books(): void {
		$king = wp_insert_term( 'Stephen King', 'library_person' )['term_id'];

		wp_insert_term( 'Richard Bachman', 'library_person', array( 'parent' => $king ) );
		wp_insert_term( 'Don Rosa', 'library_person' );
		$this->add_book( $this->by( 'Richard Bachman' ) + array( 'shelf' => array( 'status' => Status::Not_Kept->value ) ) );
		$this->add_book( $this->by( 'Fred Vargas' ) + array( 'shelf' => array( 'status' => Status::To_Buy->value ) ) );
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
				'meta'      => array( 'edition_languages' => array( $french ) ),
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
