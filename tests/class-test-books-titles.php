<?php
/**
 * Tests of the title a book shows, from its series, volume and subtitle.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Books;
use WP_REST_Request;

/**
 * "Series, vol. 3: Title: Subtitle", a volume whose title is its series named after its series only.
 */
class Test_Books_Titles extends TestCase {

	/**
	 * A volume shows its series, its volume and its title.
	 *
	 * @return void
	 */
	public function test_a_volume_shows_its_series_and_its_title(): void {
		$meta = array( 'library_volume_number' => 'Tome 03' );

		$this->assertSame( 'Earthsea, vol. 3: The Farthest Shore', Books::build_full_title( 'The Farthest Shore', 'Earthsea', $meta ) );
	}

	/**
	 * A volume whose title is its series shows it once.
	 *
	 * @return void
	 */
	public function test_a_volume_named_after_its_series_shows_it_once(): void {
		$meta = array( 'library_volume_number' => 'Tome 01' );

		$this->assertSame( 'City Hunter, vol. 1', Books::build_full_title( 'City Hunter', 'City Hunter', $meta ) );
		$this->assertSame( 'Star Wars, Young Jedi Knights, vol. 1', Books::build_full_title( 'Young Jedi Knights', 'Star Wars, Young Jedi Knights', $meta ) );
	}

	/**
	 * So does a volume of a series whose name has a comma, after its cycle too.
	 *
	 * @return void
	 */
	public function test_a_volume_named_after_its_series_with_a_comma_shows_it_once(): void {
		$meta = array( 'library_volume_number' => 'Tome 01' );

		$this->assertSame( 'Talli, fille de la lune, vol. 1', Books::build_full_title( 'Talli, fille de la lune', 'Talli, fille de la lune', $meta ) );
		$this->assertSame( 'Lune, Talli, fille de la lune, vol. 1', Books::build_full_title( 'Talli, fille de la lune', 'Lune, Talli, fille de la lune', $meta ) );
	}

	/**
	 * The main title of a book is its full title but its subtitle, with its series and its volume, as the API gives it.
	 *
	 * @return void
	 */
	public function test_the_main_title_leaves_the_subtitle_out(): void {
		$book_id = $this->add_book(
			array(
				'title'  => 'The Farthest Shore',
				'series' => 'Earthsea',
				'meta'   => array(
					'volume_number' => 'Tome 03',
					'subtitle'      => 'A Novel',
				),
			)
		);
		$request = new WP_REST_Request( 'GET', '/wp/v2/books/' . $book_id );

		$request->set_query_params( array( '_fields' => 'title,main_title' ) );

		$data = rest_do_request( $request )->get_data();

		$this->assertSame( 'Earthsea, vol. 3: The Farthest Shore: A Novel', $data['title']['rendered'] );
		$this->assertSame( 'Earthsea, vol. 3: The Farthest Shore', $data['main_title'] );
	}
}
