<?php
/**
 * Tests of the pages of a person, a series and a collection: their wishes apart from their books.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Shelf;
use WP_REST_Request;

/**
 * A page lists its wishes, books to buy without a reading, apart from its books, which its counts keep to, and its
 * completion but for a series, where a wish is a volume left; a book to buy read elsewhere, or being read, is one of its
 * books.
 */
class Test_Term_Pages extends TestCase {

	/**
	 * The page of a person: their wishes apart, in the order of their books, out of their counts and of their completion.
	 *
	 * @return void
	 */
	public function test_the_wishes_of_a_person_are_apart(): void {
		$read   = $this->add_read( '2024-01-10', $this->by( 'Ursula K. Le Guin' ) + array( 'meta' => array( 'published_date' => '1969' ) ) );
		$at_end = $this->add_read( '2024-02-10', $this->by( 'Ursula K. Le Guin' ) + array( 'meta' => array( 'published_date' => '1974' ) ) );
		$bought = $this->add_read(
			'2024-03-05',
			$this->by( 'Ursula K. Le Guin' ) + array(
				'shelf' => array( 'status' => 'to-buy' ),
				'meta'  => array( 'published_date' => '1971' ),
			)
		);
		$later  = $this->add_book(
			$this->by( 'Ursula K. Le Guin' ) + array(
				'shelf' => array( 'status' => 'to-buy' ),
				'meta'  => array(
					'published_date' => '2008',
					'pages'          => 300,
				),
			)
		);
		$sooner = $this->add_book(
			$this->by( 'Ursula K. Le Guin' ) + array(
				'shelf' => array( 'status' => 'to-buy' ),
				'meta'  => array( 'published_date' => '1966' ),
			)
		);

		$person = $this->get( 'people/' . get_term_by( 'name', 'Ursula K. Le Guin', 'library_person' )->term_id );

		$this->assertSame( array( $read, $bought, $at_end ), $person['books'] );
		$this->assertSame( array( $sooner, $later ), $person['wishes'] );
		$this->assertSame( 3, $person['read_count'] );
		$this->assertSame( 0, $person['page_count'] );
		$this->assertArrayNotHasKey( 'total', $person['completion'] );
	}

	/**
	 * A book to buy being read is one of the books of a page, not a wish.
	 *
	 * @return void
	 */
	public function test_a_book_to_buy_being_read_is_no_wish(): void {
		$reading = $this->add_book(
			$this->by( 'Ursula K. Le Guin' ) + array(
				'shelf' => array(
					'status'   => 'to-buy',
					'readings' => array( '2024-10-20/..' ),
				),
			)
		);

		$person = $this->get( 'people/' . get_term_by( 'name', 'Ursula K. Le Guin', 'library_person' )->term_id );

		$this->assertSame( 'to-buy', get_post_meta( $reading, Shelf::key( Shelf::STATUS, get_current_user_id() ), true ) );
		$this->assertSame( array( $reading ), $person['books'] );
		$this->assertSame( array(), $person['wishes'] );
	}

	/**
	 * The page of a series: its wishes apart, in reading order, out of its counts, but a volume left of its completion.
	 *
	 * @return void
	 */
	public function test_the_wishes_of_a_series_are_apart(): void {
		$first = $this->add_read(
			'2024-01-10',
			array(
				'series' => 'Earthsea',
				'meta'   => array( 'volume_number' => '1' ),
			)
		);
		$wish  = $this->add_book(
			array(
				'series' => 'Earthsea',
				'meta'   => array( 'volume_number' => '2' ),
				'shelf'  => array( 'status' => 'to-buy' ),
			)
		);
		$third = $this->add_read(
			'2024-02-10',
			array(
				'series' => 'Earthsea',
				'meta'   => array( 'volume_number' => '3' ),
			)
		);

		$series = $this->get( 'series/' . get_term_by( 'name', 'Earthsea', 'library_series' )->term_id );

		$this->assertSame( array( $first, $third ), $series['books'] );
		$this->assertSame( array( $wish ), $series['wishes'] );
		$this->assertSame( 2, $series['read_count'] );
		$this->assertSame( 2, $series['completion']['done'] );
		$this->assertSame( 3, $series['completion']['total'] );
	}

	/**
	 * The page of a collection: its wishes apart.
	 *
	 * @return void
	 */
	public function test_the_wishes_of_a_collection_are_apart(): void {
		$collection = array(
			'publisher'  => 'Gallimard',
			'collection' => 'Folio',
		);
		$books      = array( $this->add_read( '2024-01-10', $collection ), $this->add_book( $collection ) );
		$wish       = $this->add_book( $collection + array( 'shelf' => array( 'status' => 'to-buy' ) ) );

		$page = $this->get( 'publishers/' . get_term_by( 'name', 'Folio', 'library_publisher' )->term_id );

		$this->assertEqualsCanonicalizing( $books, $page['books'] );
		$this->assertSame( array( $wish ), $page['wishes'] );
		$this->assertSame( 1, $page['read_count'] );
	}

	/**
	 * The response of a GET request of the plugin.
	 *
	 * @param string $route Route, under library/v1.
	 * @return array<string,mixed>
	 */
	private function get( string $route ): array {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/library/v1/' . $route ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		return $response->get_data();
	}
}
