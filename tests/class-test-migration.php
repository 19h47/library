<?php
/**
 * Tests of the migration to 10.0.0, as README.txt gives it.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Readings;
use Library\Shelf;
use Library\Status;

/**
 * The SQL of the entry 10.0.0 of README.txt, run as written on books of before, gives their library to a reader: the
 * books published, their statuses, dates added, readings, printed dates and notes theirs.
 */
class Test_Migration extends TestCase {

	/**
	 * The statements of the migration, as the entry 10.0.0 of README.txt writes them, in their order, with the table
	 * prefix of the tests and the user ID of the reader.
	 *
	 * @param int $reader User ID of the reader.
	 * @return string[]
	 */
	private static function get_statements( int $reader ): array {
		global $wpdb;

		$readme = (string) file_get_contents( LIBRARY_DIR_PATH . 'README.txt' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A file of the plugin.

		preg_match( '/^= 10\.0\.0 =$(.*?)^= /ms', $readme, $entry );
		preg_match_all( '/`((?:SET|INSERT|UPDATE|DELETE) [^`]+)`/', $entry[1] ?? '', $statements );

		return array_map(
			fn( string $statement ) => str_replace( '@reader = 1', "@reader = $reader", (string) preg_replace( '/\bwp_(posts|postmeta|options|term_taxonomy|term_relationships)\b/', $wpdb->prefix . '$1', $statement ) ),
			$statements[1]
		);
	}

	/**
	 * A book of before: its post in a status of before, its readings and its printed date under the keys of the book.
	 *
	 * @param string              $status Status of its post, such as library-not-kept.
	 * @param array<string,mixed> $post   More of its post, such as its content.
	 * @return int Book ID.
	 */
	private function add_book_of_before( string $status, array $post = array() ): int {
		global $wpdb;

		$book_id = self::factory()->post->create(
			array(
				'post_type'   => 'library_book',
				'post_status' => 'draft',
			) + $post
		);

		// The statuses of before are no longer registered.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'ID' => $book_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A book of before.
		clean_post_cache( $book_id );

		return $book_id;
	}

	/**
	 * The library of before becomes the one of the reader, the catalogue published.
	 *
	 * @return void
	 */
	public function test_the_library_becomes_the_one_of_its_reader(): void {
		global $wpdb;

		$reader   = get_current_user_id();
		$kept     = $this->add_book_of_before(
			'publish',
			array(
				'post_content' => 'Lent by Ada.',
				'post_date'    => '2020-05-01 10:00:00',
			)
		);
		$not_kept = $this->add_book_of_before( 'library-not-kept' );
		$to_buy   = $this->add_book_of_before( 'library-to-buy' );
		$trashed  = $this->add_book_of_before( 'trash' );
		$draft    = $this->add_book_of_before( 'draft' );

		add_post_meta( $kept, 'library_reading', '{"start":"2024-03-02","end":"2024-03-20","rating":8}' );
		add_post_meta( $kept, 'library_printed_date', '2014-04' );
		add_post_meta( $trashed, '_wp_trash_meta_status', 'library-not-kept' );
		wp_set_object_terms( $not_kept, array( 'Julien Gracq' ), 'library_person' );

		foreach ( self::get_statements( $reader ) as $statement ) {
			$this->assertNotFalse( $wpdb->query( $statement ), $wpdb->last_error ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The migration as README.txt writes it.
		}

		wp_cache_flush();

		$this->assertSame( Status::In_Library, Shelf::get_status( $kept, $reader ) );
		$this->assertSame( '2020-05-01 10:00:00', Shelf::get_added( $kept, $reader ) );
		$this->assertSame( 8.0, Readings::get_rating( Shelf::get_readings( $kept, $reader ) ) );
		$this->assertSame( '2014-04', Shelf::get_printed_date( $kept, $reader ) );
		$this->assertSame( 'Lent by Ada.', Shelf::get_values( $kept, $reader )['library_notes'] );
		$this->assertSame( '', get_post_field( 'post_content', $kept ) );
		$this->assertSame( array(), get_post_meta( $kept, 'library_reading', false ) );

		foreach ( array( $kept, $not_kept, $to_buy ) as $book_id ) {
			$this->assertSame( 'publish', get_post_status( $book_id ) );
		}

		$this->assertSame( Status::Not_Kept, Shelf::get_status( $not_kept, $reader ) );
		$this->assertSame( Status::To_Buy, Shelf::get_status( $to_buy, $reader ) );
		$this->assertSame( 1, get_term_by( 'name', 'Julien Gracq', 'library_person' )->count );

		$this->assertSame( Status::Not_Kept, Shelf::get_status( $trashed, $reader ) );
		$this->assertSame( 'publish', get_post_meta( $trashed, '_wp_trash_meta_status', true ) );

		$this->assertSame( 'draft', get_post_status( $draft ) );
		$this->assertNull( Shelf::get_status( $draft, $reader ) );
	}
}
