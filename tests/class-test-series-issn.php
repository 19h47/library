<?php
/**
 * Tests of the ISSN of a magazine, which its series holds rather than each of its issues, as the catalogue of the BnF does.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Term_Fields;
use Library\Meta;
use Library\Taxonomies;
use WP_REST_Request;

/**
 * The ISSN is a term meta of the series, edited from its screen or the REST API, found by the search and shown on the page
 * of the series; the issues of a magazine are slugged by their number.
 */
class Test_Series_Issn extends TestCase {

	/**
	 * The suite unregisters every meta key after a test: the meta of the books and of the series are registered again.
	 *
	 * @return void
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();

		new Meta()->register_meta();
		new Taxonomies()->register_series();
	}

	/**
	 * An issue of White Dwarf, and the term ID of its series.
	 *
	 * @param array $book More fields of the issue.
	 * @return array{0:int,1:int} Book ID and series ID.
	 */
	private function add_issue( array $book = array() ): array {
		$book_id = $this->add_book(
			$book + array(
				'title'  => 'White Dwarf',
				'series' => 'White Dwarf',
				'meta'   => array( 'library_volume_number' => '56' ),
			)
		);

		return array( $book_id, Taxonomies::get_series( $book_id )->term_id );
	}

	/**
	 * Update a series through `POST wp/v2/series/<id>`.
	 *
	 * @param int                 $series_id Term ID of the series.
	 * @param array<string,mixed> $meta      Meta sent.
	 * @return int Status of the response.
	 */
	private function update_series( int $series_id, array $meta ): int {
		$request = new WP_REST_Request( 'POST', '/wp/v2/series/' . $series_id );
		$request->set_param( 'meta', $meta );

		return rest_do_request( $request )->get_status();
	}

	/**
	 * A book has no ISSN of its own.
	 *
	 * @return void
	 */
	public function test_a_book_has_no_issn(): void {
		$this->assertArrayNotHasKey( 'library_issn', get_registered_meta_keys( 'post', 'library_book' ) );
	}

	/**
	 * The REST API gives a series its ISSN, as the BnF writes it, and refuses another value, such as a year.
	 *
	 * @return void
	 */
	public function test_the_rest_api_gives_a_series_its_issn(): void {
		[ , $series_id ] = $this->add_issue();

		$this->assertSame( 200, $this->update_series( $series_id, array( 'library_issn' => '1168-9188' ) ) );
		$this->assertSame( '1168-9188', Taxonomies::get_issn( $series_id ) );

		$this->assertSame( 400, $this->update_series( $series_id, array( 'library_issn' => '1994' ) ) );
		$this->assertSame( '1168-9188', Taxonomies::get_issn( $series_id ) );
	}

	/**
	 * The screen of a series saves its ISSN, removes it when emptied, and keeps it when the value is not an ISSN.
	 *
	 * @return void
	 */
	public function test_the_screen_of_a_series_saves_its_issn(): void {
		[ , $series_id ] = $this->add_issue();
		$fields          = new Term_Fields();
		$save            = function ( string $issn ) use ( $fields, $series_id ): void {
			$_POST = array(
				'_wpnonce'     => wp_create_nonce( 'update-tag_' . $series_id ),
				'library_issn' => $issn,
			);
			$fields->save_fields( $series_id );
		};

		$save( '0220-824x' );
		$this->assertSame( '0220-824X', Taxonomies::get_issn( $series_id ) );

		$save( '1994' );
		$this->assertSame( '0220-824X', Taxonomies::get_issn( $series_id ) );

		$save( '' );
		$this->assertSame( '', Taxonomies::get_issn( $series_id ) );

		$_POST = array();
	}

	/**
	 * Without a nonce of the screen, nothing is saved: a series edited elsewhere saves its meta itself.
	 *
	 * @return void
	 */
	public function test_the_screen_saves_nothing_without_its_nonce(): void {
		[ , $series_id ] = $this->add_issue();

		$_POST = array( 'library_issn' => '1168-9188' );
		new Term_Fields()->save_fields( $series_id );
		$_POST = array();

		$this->assertSame( '', Taxonomies::get_issn( $series_id ) );
	}

	/**
	 * An issue without ISBN is slugged by its title and number; a book with an ISBN, by its title and ISBN.
	 *
	 * @return void
	 */
	public function test_an_issue_is_slugged_by_its_number(): void {
		[ $issue_id ] = $this->add_issue();
		$book_id      = $this->add_book(
			array(
				'title' => 'Dune',
				'meta'  => array(
					'library_isbn'          => '9782266320481',
					'library_volume_number' => '1',
				),
			)
		);

		$this->assertSame( 'white-dwarf-56', get_post_field( 'post_name', $issue_id ) );
		$this->assertSame( 'dune-978-2-266-32048-1', get_post_field( 'post_name', $book_id ) );
	}

	/**
	 * A search for the ISSN finds the issues of its series.
	 *
	 * @return void
	 */
	public function test_a_search_for_the_issn_finds_the_issues(): void {
		[ $issue_id, $series_id ] = $this->add_issue();
		$this->add_book( array( 'title' => 'Dune' ) );
		update_term_meta( $series_id, 'library_issn', '1168-9188' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/books' );
		$request->set_param( 'search', '1168-9188' );

		$this->assertSame( array( $issue_id ), wp_list_pluck( rest_do_request( $request )->get_data(), 'id' ) );
	}

	/**
	 * The page of a series gives its ISSN, empty for a series that is no magazine.
	 *
	 * @return void
	 */
	public function test_the_page_of_a_series_gives_its_issn(): void {
		[ , $series_id ] = $this->add_issue();
		$page            = fn() => rest_do_request( new WP_REST_Request( 'GET', '/library/v1/series/' . $series_id ) )->get_data();

		$this->assertSame( '', $page()['issn'] );

		update_term_meta( $series_id, 'library_issn', '1168-9188' );

		$this->assertSame( '1168-9188', $page()['issn'] );
	}
}
