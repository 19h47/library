<?php
/**
 * Tests of the title a book shows, from its series, volume and subtitle.
 *
 * @package Library
 */

/**
 * "Series, vol. 3: Title: Subtitle", a volume whose title is its series named after its series only.
 */
class Test_Library_Books_Titles extends Library_TestCase {

	/**
	 * A volume shows its series, its volume and its title.
	 *
	 * @return void
	 */
	public function test_a_volume_shows_its_series_and_its_title(): void {
		$meta = array( 'library_volume_number' => 'Tome 03' );

		$this->assertSame( 'Earthsea, vol. 3: The Farthest Shore', Library_Books::build_full_title( 'The Farthest Shore', 'Earthsea', $meta ) );
	}

	/**
	 * A volume whose title is its series shows it once.
	 *
	 * @return void
	 */
	public function test_a_volume_named_after_its_series_shows_it_once(): void {
		$meta = array( 'library_volume_number' => 'Tome 01' );

		$this->assertSame( 'City Hunter, vol. 1', Library_Books::build_full_title( 'City Hunter', 'City Hunter', $meta ) );
		$this->assertSame( 'Star Wars, Young Jedi Knights, vol. 1', Library_Books::build_full_title( 'Young Jedi Knights', 'Star Wars, Young Jedi Knights', $meta ) );
	}

	/**
	 * So does a volume of a series whose name has a comma, after its cycle too.
	 *
	 * @return void
	 */
	public function test_a_volume_named_after_its_series_with_a_comma_shows_it_once(): void {
		$meta = array( 'library_volume_number' => 'Tome 01' );

		$this->assertSame( 'Talli, fille de la lune, vol. 1', Library_Books::build_full_title( 'Talli, fille de la lune', 'Talli, fille de la lune', $meta ) );
		$this->assertSame( 'Lune, Talli, fille de la lune, vol. 1', Library_Books::build_full_title( 'Talli, fille de la lune', 'Lune, Talli, fille de la lune', $meta ) );
	}
}
