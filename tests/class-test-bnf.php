<?php
/**
 * Tests of the book of an ISBN in the catalogue of the BnF, as the form of a book to add takes it.
 *
 * @package Library
 */

namespace Library\Tests;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * The catalogue answers with records saved in tests/fixtures/bnf, by their ISBN: the names it gives are the ones of the
 * library when the library has them.
 */
class Test_BnF extends TestCase {

	/**
	 * Requests sent to the catalogue.
	 *
	 * @var int
	 */
	private int $requests = 0;

	/**
	 * The catalogue answers with the record saved of the ISBN asked, else with none.
	 *
	 * @return void
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->requests = 0;

		add_filter( 'pre_http_request', array( $this, 'answer' ), 10, 3 );
	}

	/**
	 * The answer of the catalogue to a search of an ISBN.
	 *
	 * @param false|array|WP_Error $response A response given before the request, false for none.
	 * @param array                $args     Arguments of the request.
	 * @param string               $url      URL of the request.
	 * @return false|array|WP_Error
	 */
	public function answer( $response, array $args, string $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed -- The signature of the filter.
		if ( false !== $response || ! str_starts_with( $url, 'https://catalogue.bnf.fr/' ) ) {
			return $response;
		}

		++$this->requests;

		preg_match( '/%22(97[89]\d{10})%22/', $url, $matches );

		$fixture = __DIR__ . '/fixtures/bnf/' . ( $matches[1] ?? '' ) . '.xml';

		return array(
			'headers'  => array(),
			'body'     => is_file( $fixture ) ? (string) file_get_contents( $fixture ) : '<srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/"/>', // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A file of the tests.
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * A volume of a series, as the library names it: its series, its people and its publisher, found by the ISBN of its
	 * other books, and the form of its series.
	 *
	 * @return void
	 */
	public function test_a_volume_takes_the_names_of_the_library(): void {
		$comics   = $this->get_form( 'Bande dessinée' );
		$japanese = $this->get_language( 'ja', 'Japonais' );

		$this->add_book(
			array(
				'title'     => 'City Hunter',
				'series'    => 'City Hunter',
				'publisher' => 'Panini Comics',
				'form'      => $comics,
				'meta'      => array( 'isbn' => '9782845380011' ),
			) + $this->by( 'Tsukasa Hojo' )
		);

		$book = $this->get_bnf_book( '978-2-84538-965-6' )->get_data();

		$this->assertSame( 'City Hunter', $book['title'] );
		$this->assertSame(
			array(
				array(
					'name' => 'Tsukasa Hojo',
					'role' => 'author',
				),
				array(
					'name' => 'Xavière Daumarie',
					'role' => 'translator',
				),
			),
			$book['people']
		);
		$this->assertSame( 'Panini Comics', $book['publisher'] );
		$this->assertSame( 'City Hunter', $book['series'] );
		$this->assertSame( 'Tome 10', $book['meta']['volume_number'] );
		$this->assertSame( array( $japanese ), $book['languages'] );
		$this->assertSame( $comics, $book['form'] );
		$this->assertSame( '226', $book['meta']['pages'] );
		// "DL 2007": the legal deposit stands for the date of publication.
		$this->assertSame( '2007', $book['meta']['edition_date'] );
	}

	/**
	 * An older book, found by its ISBN-10: the sub-collection of the library that ends with the one of the catalogue,
	 * its number in the link to its collection, and its series, which the catalogue gives as a collection too.
	 *
	 * @return void
	 */
	public function test_an_older_book_in_a_sub_collection(): void {
		$english = $this->get_language( 'en', 'Anglais' );

		$this->add_book(
			array(
				'publisher'  => 'Pocket',
				'collection' => 'Pocket Science-fiction',
				'meta'       => array( 'isbn' => '978-2-266-06657-0' ),
			)
		);

		$book = $this->get_bnf_book( '9782266089753' )->get_data();

		$this->assertSame( 'Sentinelles', $book['title'] );
		$this->assertSame( array( 'Margaret Weis', 'Tracy Hickman', 'Simone Hilling' ), array_column( $book['people'], 'name' ) );
		$this->assertSame( array( 'author', 'author', 'translator' ), array_column( $book['people'], 'role' ) );
		$this->assertSame( 'Pocket', $book['publisher'] );
		$this->assertSame( 'Pocket Science-fiction', $book['collection'] );
		$this->assertSame( '5702', $book['meta']['collection_number'] );
		$this->assertSame( 'Le bouclier des étoiles', $book['series'] );
		$this->assertSame( 'Tome 01', $book['meta']['volume_number'] );
		$this->assertSame( array( $english ), $book['languages'] );
		$this->assertSame( '2000', $book['meta']['edition_date'] );
	}

	/**
	 * A novel translated: its form told after its title, which is no subtitle, its translator, the language it is
	 * translated from and the one of its text; a collection the library does not have, by the name of the catalogue.
	 *
	 * @return void
	 */
	public function test_a_translated_novel(): void {
		$novel   = $this->get_form( 'Roman' );
		$italian = $this->get_language( 'it', 'Italien' );
		$french  = $this->get_language( 'fr', 'Français' );

		$book = $this->get_bnf_book( '978-2-07-278171-1' )->get_data();

		$this->assertSame( 'Tous, sauf moi', $book['title'] );
		$this->assertSame( '', $book['meta']['subtitle'] );
		$this->assertSame( $novel, $book['form'] );
		$this->assertSame( array( $italian ), $book['languages'] );
		$this->assertSame( array( $french ), $book['meta']['edition_languages'] );
		$this->assertSame( 'translator', $book['people'][1]['role'] );
		$this->assertSame( 'Gallimard', $book['publisher'] );
		$this->assertSame( 'Du monde entier', $book['collection'] );
		// From the publication statement of RDA, not the one of its printing.
		$this->assertSame( '2019', $book['meta']['edition_date'] );
	}

	/**
	 * A translation that an older record tells by its translator only has no original language, rather than the one of
	 * its text; a collection named after its publisher gives its number alone.
	 *
	 * @return void
	 */
	public function test_a_translation_without_its_language(): void {
		$this->get_language( 'fr', 'Français' );
		$this->add_book(
			array(
				'publisher' => '10/18',
				'meta'      => array( 'isbn' => '9782264000019' ),
			)
		);

		$book = $this->get_bnf_book( '978-2-264-01328-6' )->get_data();

		$this->assertSame( array(), $book['languages'] );
		$this->assertSame( '10/18', $book['publisher'] );
		$this->assertSame( '', $book['collection'] );
		$this->assertSame( '1999', $book['meta']['collection_number'] );
	}

	/**
	 * A book the catalogue records by its printings, each dated "impr. 2015" for want of a date of publication, has no
	 * edition date: the printing of the copy of the BnF is not the edition, nor the one in hand.
	 *
	 * @return void
	 */
	public function test_a_printing_is_no_edition_date(): void {
		$book = $this->get_bnf_book( '978-2-7560-0912-4' )->get_data();

		$this->assertSame( 'Passé décomposé', $book['title'] );
		$this->assertSame( '', $book['meta']['edition_date'] );
	}

	/**
	 * An ISBN the catalogue does not know answers 404, kept for a day: the catalogue is asked once.
	 *
	 * @return void
	 */
	public function test_an_isbn_the_catalogue_does_not_know(): void {
		$this->get_bnf_book( '9782845380011' );

		$response = $this->get_bnf_book( '9782845380011' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'library_not_in_bnf', $response->get_data()['code'] );
		$this->assertSame( 1, $this->requests );
	}

	/**
	 * A catalogue that does not answer says so, and is asked again the next time.
	 *
	 * @return void
	 */
	public function test_a_catalogue_that_does_not_answer(): void {
		$unreachable = fn() => new WP_Error( 'http_request_failed', 'Timeout' );

		add_filter( 'pre_http_request', $unreachable, 9 );

		$this->assertSame( 502, $this->get_bnf_book( '978-2-84538-965-6' )->get_status() );

		remove_filter( 'pre_http_request', $unreachable, 9 );

		$this->assertSame( 200, $this->get_bnf_book( '978-2-84538-965-6' )->get_status() );
	}

	/**
	 * What is not an ISBN is refused before the catalogue is asked.
	 *
	 * @return void
	 */
	public function test_what_is_not_an_isbn_is_refused(): void {
		$this->assertSame( 400, $this->get_bnf_book( '978-2-84538-965-7' )->get_status() );
		$this->assertSame( 0, $this->requests );
	}

	/**
	 * The response of `GET library/v1/books/bnf` for an ISBN.
	 *
	 * @param string $isbn ISBN.
	 * @return WP_REST_Response
	 */
	private function get_bnf_book( string $isbn ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', '/library/v1/books/bnf' );
		$request->set_param( 'isbn', $isbn );

		return rest_do_request( $request );
	}
}
