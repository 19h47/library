<?php
/**
 * Tests of how names and codes are compared: a language by any of its codes, a series whatever its accents, a volume by
 * its first number.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Books;
use Library\Taxonomies;

/**
 * A language is written once, by its two-letter code; a series is found whatever its case and accents; a volume is
 * numbered by its first number.
 */
class Test_Taxonomies_Names extends TestCase {

	/**
	 * Any ISO 639 code of a language gives the code the library writes it with, bibliographic as the catalogues give it
	 * or terminological.
	 *
	 * @return void
	 */
	public function test_a_language_code_is_written_by_its_two_letters(): void {
		$this->assertSame( 'fr', Taxonomies::normalize_language_code( 'fre' ) );
		$this->assertSame( 'fr', Taxonomies::normalize_language_code( 'fra' ) );
		$this->assertSame( 'fr', Taxonomies::normalize_language_code( 'FR' ) );
		$this->assertSame( 'de', Taxonomies::normalize_language_code( 'ger' ) );
		$this->assertSame( '', Taxonomies::normalize_language_code( 'French' ) );
	}

	/**
	 * A language typed by another of its codes is the one the library has, and a slug typed by another code is refused.
	 *
	 * @return void
	 */
	public function test_a_language_is_added_once_whatever_its_code(): void {
		$french = $this->get_language( 'fr', 'Français' );

		$again = wp_insert_term( 'fra', 'library_language' );
		$slug  = wp_insert_term( 'Allemand', 'library_language', array( 'slug' => 'deu' ) );

		$this->assertWPError( $again );
		$this->assertSame( 'term_exists', $again->get_error_code() );
		$this->assertSame( $french, $again->get_error_data() );
		$this->assertWPError( $slug );
		$this->assertSame( 'library_language_code', $slug->get_error_code() );
	}

	/**
	 * A search for a series finds it whatever its case and accents, as the database compares the names of terms.
	 *
	 * @return void
	 */
	public function test_a_series_is_found_whatever_its_accents(): void {
		$book_id = $this->add_book( array( 'series' => 'Chroniques de l’Étrange' ) );

		$this->assertSame( array( Taxonomies::get_series( $book_id )->term_id ), Taxonomies::find_series_ids( 'chroniques de l’etrange' ) );
	}

	/**
	 * A volume is numbered by its first number, as "3-4" by 3, and one without number has none.
	 *
	 * @return void
	 */
	public function test_a_volume_is_numbered_by_its_first_number(): void {
		$this->assertSame( 8, Books::get_volume_number( 'Tome 08' ) );
		$this->assertSame( 3, Books::get_volume_number( '3-4' ) );
		$this->assertNull( Books::get_volume_number( 'Chant II' ) );
	}
}
