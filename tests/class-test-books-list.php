<?php
/**
 * Tests of the books list of the admin.
 *
 * @package Library
 */

namespace Library\Tests;

use Library\Admin\Books_List;
use Library\Status;
use RuntimeException;

/**
 * The books list opens on the books in the library of the reader, and its view All lists the whole catalogue.
 */
class Test_Books_List extends TestCase {

	/**
	 * Tell where a redirect leads rather than leaving.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter(
			'wp_redirect',
			function ( string $location ): never {
				throw new RuntimeException( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Read by the test.
			}
		);
	}

	/**
	 * Empty the request.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_GET = array();

		parent::tear_down();
	}

	/**
	 * The list opened from the menu leads to the books in the library.
	 *
	 * @return void
	 */
	public function test_the_list_opens_on_the_library(): void {
		$_GET = array( 'post_type' => 'library_book' );

		$this->expectExceptionMessage( Books_List::get_url( Status::In_Library->value ) );

		new Books_List()->open_on_library();
	}

	/**
	 * A view, a sort or a search of the list stays where it is.
	 *
	 * @return void
	 */
	public function test_a_view_stays(): void {
		foreach ( array( array( 'all_posts' => '1' ), array( 'orderby' => 'title' ), array( 's' => 'Gracq' ) ) as $view ) {
			$_GET = array( 'post_type' => 'library_book' ) + $view;

			new Books_List()->open_on_library();
		}

		$this->expectNotToPerformAssertions();
	}

	/**
	 * The view All links the whole catalogue, which the list opened from the menu is not.
	 *
	 * @return void
	 */
	public function test_all_links_the_catalogue(): void {
		$views = new Books_List()->add_list_table_views( array( 'all' => '<a href="edit.php?post_type=library_book">All</a>' ) );

		$this->assertStringContainsString( 'href="' . esc_url( Books_List::get_url() ) . '"', $views['all'] );
		$this->assertStringContainsString( 'all_posts=1', $views['all'] );
	}
}
