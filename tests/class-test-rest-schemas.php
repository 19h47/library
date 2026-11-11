<?php
/**
 * Tests of the schemas of the routes of the plugin: each one describes the fields its route gives, no more, no less.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Rest_Schemas;
use WP_REST_Request;

/**
 * The fields a route gives are those its schema describes, at the top of its response and in the parts they share.
 */
class Test_Rest_Schemas extends TestCase {

	/**
	 * The meta of the books are registered again: the suite unregisters every meta key after a test.
	 *
	 * @return void
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();

		new \Library\Meta()->register_meta();
	}

	/**
	 * The response of a GET request of the plugin.
	 *
	 * @param string              $route  Route, under library/v1.
	 * @param array<string,mixed> $params Parameters.
	 * @return mixed
	 */
	private function get( string $route, array $params = array() ): mixed {
		$request = new WP_REST_Request( 'GET', '/library/v1/' . $route );
		$request->set_query_params( $params );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		return $response->get_data();
	}

	/**
	 * The names of the properties of a schema, sorted.
	 *
	 * @param array<string,mixed> $schema Schema.
	 * @return string[]
	 */
	private static function names( array $schema ): array {
		$names = array_keys( $schema['properties'] ?? array() );

		sort( $names );

		return $names;
	}

	/**
	 * The names of the fields of a response, sorted.
	 *
	 * @param array<string,mixed> $data Response.
	 * @return string[]
	 */
	private static function fields( array $data ): array {
		$fields = array_keys( $data );

		sort( $fields );

		return $fields;
	}

	/**
	 * Every route of the plugin gives the fields its schema describes.
	 *
	 * @return void
	 */
	public function test_each_route_gives_what_its_schema_describes(): void {
		$book_id = $this->add_read(
			'2025-03-14',
			array(
				'title'  => 'Volume 1',
				'series' => 'A Cycle, A Series',
				'people' => array(
					array(
						'name' => 'Ann Writer',
						'role' => 'author',
					),
				),
				'meta'   => array(
					'pages'  => 120,
					'rating' => 8,
				),
			)
		);
		$this->add_book( array( 'title' => 'To Read' ) );

		$series_id = (int) wp_get_post_terms( $book_id, 'library_series', array( 'fields' => 'ids' ) )[0];
		$person_id = (int) wp_get_post_terms( $book_id, 'library_person', array( 'fields' => 'ids' ) )[0];
		$schemas   = Rest_Schemas::get_schemas();
		$responses = array(
			'books/(?P<id>\d+)'      => $this->get( 'books/' . $book_id ),
			'stats'                  => $this->get( 'stats' ),
			'readings'               => $this->get( 'readings' )[0],
			'idea'                   => $this->get( 'idea' ),
			'achievements'           => $this->get( 'achievements' ),
			'recaps/(?P<year>\d{4})' => $this->get( 'recaps/2025' ),
			'people/(?P<id>\d+)'     => $this->get( 'people/' . $person_id ),
			'series/(?P<id>\d+)'     => $this->get( 'series/' . $series_id ),
		);

		foreach ( $responses as $route => $data ) {
			$this->assertSame( self::names( $schemas[ $route ]() ), self::fields( $data ), $route );
		}

		// The parts they share: a reading of a month, the meta of a book form, the stats of this year, an achievement.
		$this->assertSame( self::names( Rest_Schemas::get_readings()['properties']['readings']['items'] ), self::fields( $responses['readings']['readings'][0] ) );
		$this->assertSame( self::names( Rest_Schemas::get_book_form()['properties']['meta'] ), self::fields( $responses['books/(?P<id>\d+)']['meta'] ) );
		$this->assertSame( self::names( Rest_Schemas::get_stats()['properties']['year_to_date'] ), self::fields( $responses['stats']['year_to_date'] ) );
		$this->assertEmpty( array_diff( self::fields( $responses['achievements']['earned'][0] ), self::names( Rest_Schemas::get_achievements()['properties']['earned']['items'] ) ) );
	}
}
