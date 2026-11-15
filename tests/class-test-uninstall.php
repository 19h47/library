<?php
/**
 * Tests of what deleting the plugin leaves out.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Shelf;

/**
 * Deleting the plugin deletes its roles and its caches, and keeps the books.
 */
class Test_Uninstall extends TestCase {

	/**
	 * Read the roles again from the database, as the test left it: uninstalling took them out of the roles of the request.
	 *
	 * @return void
	 */
	#[\Override]
	public function tear_down(): void {
		parent::tear_down();

		wp_cache_flush();
		wp_roles()->for_site();
	}

	/**
	 * The roles of the readers, the capabilities of the books, the generation of the cache, its transients, those of the BnF
	 * and of the latest release go; the books, their meta and their terms stay, and so do the transients of other plugins
	 * and the capabilities of WordPress.
	 *
	 * @return void
	 */
	public function test_deletes_the_roles_and_the_caches_and_keeps_the_books(): void {
		$book_id = $this->add_read( '2024-03-14', array( 'series' => 'Dune' ) );

		update_option( 'library_cache_generation', 3, true );
		set_transient( 'library_books_' . md5( '3' ), array( 'books' ), DAY_IN_SECONDS );
		set_transient( 'library_bnf_9782070360246', array( 'record' ), DAY_IN_SECONDS );
		set_site_transient( 'library_github_release', array( 'release' ), DAY_IN_SECONDS );
		set_transient( 'other_plugin', 'kept', DAY_IN_SECONDS );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'library/library.php' );
		}

		require LIBRARY_DIR_PATH . 'uninstall.php';

		wp_cache_flush();

		$this->assertFalse( get_option( 'library_cache_generation' ) );
		$this->assertFalse( get_transient( 'library_books_' . md5( '3' ) ) );
		$this->assertFalse( get_transient( 'library_bnf_9782070360246' ) );
		$this->assertFalse( get_site_transient( 'library_github_release' ) );
		$this->assertSame( 'kept', get_transient( 'other_plugin' ) );
		$this->assertNull( get_role( 'library_reader' ) );
		$this->assertNull( get_role( 'library_librarian' ) );
		$this->assertFalse( get_role( 'administrator' )->has_cap( 'edit_library_books' ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'read' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'upload_files' ) );
		$this->assertSame( 'library_book', get_post_type( $book_id ) );
		$this->assertNotEmpty( Shelf::get_readings( $book_id, get_current_user_id() ) );
		$this->assertNotEmpty( get_the_terms( $book_id, 'library_series' ) );
	}
}
