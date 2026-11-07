<?php
/**
 * Tests of the labels of the languages: the languages of the library, which are the original languages of a book.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Taxonomies;

/**
 * The screen of the languages names them all; the screens of a book name them as its original languages.
 */
class Test_Taxonomies_Labels extends TestCase {

	/**
	 * Load the screens of the admin, which the tests do not.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	/**
	 * Leave the screen, and name the languages again as the taxonomy registers them.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		set_current_screen( 'front' );
		new Taxonomies()->register_language();

		parent::tear_down();
	}

	/**
	 * The list of the languages, under the menu of the books, names them as languages.
	 *
	 * @return void
	 */
	public function test_the_screen_of_the_languages_names_languages(): void {
		$_REQUEST['post_type'] = 'library_book';
		set_current_screen( 'edit-library_language' );
		unset( $_REQUEST['post_type'] );

		$this->assertSame( 'library_book', get_current_screen()->post_type );
		$this->assertSame( 'Languages', get_taxonomy( 'library_language' )->labels->name );
	}

	/**
	 * The list of the books and the edit screen of a book name them as its original languages.
	 *
	 * @return void
	 */
	public function test_the_screens_of_a_book_name_original_languages(): void {
		foreach ( array( 'edit-library_book', 'library_book' ) as $screen ) {
			new Taxonomies()->register_language();
			set_current_screen( $screen );

			$this->assertSame( 'Original languages', get_taxonomy( 'library_language' )->labels->name, $screen );
		}
	}
}
