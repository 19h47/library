<?php
/**
 * Admin styles and scripts for the books list.
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}


	/**
	 * URL and version of an admin asset.
	 *
	 * The version is the file modification time, so a changed file is never served from cache.
	 *
	 * @param string $path Path relative to the admin folder.
	 * @return array{0:string,1:string}
	 */
	public static function asset( string $path ): array {
		$url     = plugin_dir_url( __FILE__ ) . $path;
		$version = (string) filemtime( plugin_dir_path( __FILE__ ) . $path );

		return array( $url, $version );
	}

	/**
	 * Enqueue the styles of the books, authors and publishers lists, and the books list scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_assets(): void {
		$screen  = get_current_screen();
		$screens = array( 'edit-book', 'edit-library-author', 'edit-library-publisher' );

		if ( ! $screen || ! in_array( $screen->id, $screens, true ) ) {
			return;
		}

		[ $style_url, $style_version ] = self::asset( 'css/library-admin.css' );
		wp_enqueue_style( 'library-admin', $style_url, array(), $style_version );

		if ( 'edit-book' !== $screen->id ) {
			return;
		}

		$read_handle                 = 'library-admin-read';
		[ $read_url, $read_version ] = self::asset( 'js/library-admin-read.js' );
		wp_enqueue_script( $read_handle, $read_url, array( 'wp-api-fetch' ), $read_version, true );
		wp_localize_script(
			$read_handle,
			'libraryAdminRead',
			array(
				/* translators: %s: reading percentage. */
				'readPercentFormat' => str_replace( '%%', '%', __( '%s%% read', 'library' ) ),
				'today'             => current_time( 'Y-m-d' ),
			)
		);

		[ $inline_edit_url, $inline_edit_version ] = self::asset( 'js/library-admin-inline-edit.js' );
		wp_enqueue_script( 'library-admin-inline-edit', $inline_edit_url, array( 'inline-edit-post' ), $inline_edit_version, true );
		wp_localize_script(
			'library-admin-inline-edit',
			'libraryInlineEdit',
			array(
				'notKeptStatus' => Library_Posts::NOT_KEPT,
				'notKeptLabel'  => _x( 'Not kept', 'book status', 'library' ),
			)
		);
	}
}
