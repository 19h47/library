<?php
/**
 * Base of the tests: a small library built by hand, book by book, through the route the book form sends them to.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Achievements;
use Library\Books;
use Library\Cache;
use ReflectionProperty;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Adds books as the reading app does, then reads them through Books, as the achievements and the reading idea do.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * Each test is a request of its own: the books and the cache kept for a request start again.
	 *
	 * @return void
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();

		self::forget_books();
		new ReflectionProperty( Cache::class, 'changed' )->setValue( null, false );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Add a book through `POST library/v1/books`, as its form sends it, with its date added.
	 *
	 * Readings go in `meta.library_reading`, which also makes it read: `array( '/2024-03-14' )`, or `array( '/' )` for a
	 * reading on an unknown date; `library_read` alone would date it today.
	 *
	 * @param array  $book  Book, as the route takes it: title, people, series, meta… A title is made up when missing.
	 * @param string $added Date added, Y-m-d H:i:s, or empty for now.
	 * @return int Book ID.
	 */
	protected function add_book( array $book = array(), string $added = '' ): int {
		$book += array( 'title' => 'Book ' . wp_generate_password( 6, false ) );

		$request = new WP_REST_Request( 'POST', '/library/v1/books' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $book ) );

		$response = rest_do_request( $request );

		$this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$book_id = $response->get_data()['id'];

		if ( $added ) {
			wp_update_post(
				array(
					'ID'            => $book_id,
					'post_date'     => $added,
					'post_date_gmt' => get_gmt_from_date( $added ),
				)
			);
		}

		self::forget_books();

		return $book_id;
	}

	/**
	 * Add a book read, with its fields.
	 *
	 * @param string $read_date Read date, Y-m-d, or empty when unknown.
	 * @param array  $book      Fields of the book, as add_book() takes them.
	 * @param string $added     Date added, Y-m-d H:i:s, or empty for now.
	 * @return int Book ID.
	 */
	protected function add_read( string $read_date, array $book = array(), string $added = '' ): int {
		return $this->add_book( array_replace_recursive( $book, array( 'meta' => array( 'library_reading' => array( "/$read_date" ) ) ) ), $added );
	}

	/**
	 * Add books read, one per read date, with the same fields.
	 *
	 * @param string[] $read_dates Read dates, Y-m-d.
	 * @param array    $book       Fields of each book, as add_book() takes them.
	 * @return int[] Book IDs.
	 */
	protected function add_reads( array $read_dates, array $book = array() ): array {
		return array_map( fn( string $read_date ) => $this->add_read( $read_date, $book ), $read_dates );
	}

	/**
	 * The people of a book by one author.
	 *
	 * @param string $name Name of the author.
	 * @return array{people:array<int,array{name:string,role:string}>}
	 */
	protected function by( string $name ): array {
		return array(
			'people' => array(
				array(
					'name' => $name,
					'role' => 'author',
				),
			),
		);
	}

	/**
	 * Days in a row.
	 *
	 * @param string $first First day, Y-m-d.
	 * @param int    $count Number of days.
	 * @return string[] Days, Y-m-d.
	 */
	protected static function get_days( string $first, int $count ): array {
		return array_map( fn( int $day ) => gmdate( 'Y-m-d', strtotime( "$first +$day days" ) ), range( 0, $count - 1 ) );
	}

	/**
	 * Term ID of an original language, added when missing.
	 *
	 * @param string $code Code, such as fr.
	 * @param string $name Name, such as Français.
	 * @return int
	 */
	protected function get_language( string $code, string $name = '' ): int {
		return $this->get_term_id( 'library_language', $name ? $name : $code, $code );
	}

	/**
	 * Term ID of a form, added when missing.
	 *
	 * @param string $name Name, such as Roman.
	 * @return int
	 */
	protected function get_form( string $name ): int {
		return $this->get_term_id( 'library_form', $name, sanitize_title( $name ) );
	}

	/**
	 * Term ID of a term found by its slug, added when missing.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $name     Name.
	 * @param string $slug     Slug.
	 * @return int
	 */
	private function get_term_id( string $taxonomy, string $name, string $slug ): int {
		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( $term ) {
			return $term->term_id;
		}

		$term = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

		$this->assertNotWPError( $term );

		return $term['term_id'];
	}

	/**
	 * Achievements earned, by kind: their subjects and details, latest first.
	 *
	 * @return array<string,array<int,array>>
	 */
	protected function get_earned(): array {
		return $this->group_by_kind( new Achievements()->get_achievements()['earned'] );
	}

	/**
	 * Achievements close to be earned, by kind.
	 *
	 * @return array<string,array<int,array>>
	 */
	protected function get_close(): array {
		return $this->group_by_kind( new Achievements()->get_achievements()['close'] );
	}

	/**
	 * Achievements by kind.
	 *
	 * @param array<int,array> $achievements Achievements.
	 * @return array<string,array<int,array>>
	 */
	private function group_by_kind( array $achievements ): array {
		$kinds = array();

		foreach ( $achievements as $achievement ) {
			$kinds[ $achievement['kind'] ][] = $achievement;
		}

		return $kinds;
	}

	/**
	 * Forget the books read once for the request, as a new request would.
	 *
	 * @return void
	 */
	private static function forget_books(): void {
		new ReflectionProperty( Books::class, 'books' )->setValue( null, null );
	}
}
