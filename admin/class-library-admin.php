<?php
/**
 * Admin scripts.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Enqueues admin scripts for the book post type.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}


	/**
	 * Enqueue admin scripts for the books list.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-book' !== $screen->id ) {
			return;
		}

		$read_handle = 'library-admin-read';
		wp_enqueue_script( $read_handle, plugin_dir_url( __FILE__ ) . 'js/library-admin-read.js', array( 'wp-api-fetch' ), LIBRARY_VERSION, true );
		wp_localize_script(
			$read_handle,
			'libraryAdminRead',
			array(
				/* translators: %s: reading percentage. */
				'readPercentFormat' => str_replace( '%%', '%', __( '%s%% read', 'library' ) ),
			)
		);
		wp_enqueue_script( 'library-admin-inline-edit', plugin_dir_url( __FILE__ ) . 'js/library-admin-inline-edit.js', array( 'inline-edit-post' ), LIBRARY_VERSION, true );
	}
}
