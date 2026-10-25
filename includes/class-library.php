<?php
/**
 * Core plugin class.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Loads the plugin classes and lets each one register its hooks.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library {

	/**
	 * Plugin class files, relative to the plugin folder.
	 *
	 * @var string[]
	 */
	private const FILES = array(
		'includes/class-library-readme.php',
		'includes/class-library-updater.php',
		'includes/class-library-books.php',
		'admin/class-library-admin.php',
		'admin/class-library-meta.php',
		'admin/class-library-metaboxes.php',
		'admin/class-library-rest-api.php',
		'admin/class-library-posts.php',
		'admin/class-library-dashboard.php',
		'admin/class-library-reading-idea.php',
		'admin/class-library-achievements.php',
		'admin/class-library-taxonomies.php',
		'admin/class-library-wp-query.php',
		'app/class-library-app.php',
	);

	/**
	 * URL and version of an asset of the plugin.
	 *
	 * The version is the file modification time, so a changed file is never served from cache.
	 *
	 * @param string $path Path relative to the plugin folder, e.g. admin/css/library-admin.css.
	 * @return array{0:string,1:string}
	 */
	public static function asset( string $path ): array {
		$url     = plugins_url( $path, LIBRARY_DIR_PATH . 'library.php' );
		$version = (string) filemtime( LIBRARY_DIR_PATH . $path );

		return array( $url, $version );
	}

	/**
	 * Load the classes and register their hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		foreach ( self::FILES as $file ) {
			require_once LIBRARY_DIR_PATH . $file;
		}

		$plugin_file = plugin_basename( LIBRARY_DIR_PATH . 'library.php' );

		( new Library_Posts() )->hooks();
		( new Library_Taxonomies() )->hooks();
		( new Library_Meta() )->hooks();
		( new Library_Metaboxes() )->hooks();
		( new Library_WP_Query() )->hooks();
		( new Library_Rest_API() )->hooks();
		( new Library_Admin() )->hooks();
		( new Library_Dashboard() )->hooks();
		( new Library_Reading_Idea() )->hooks();
		( new Library_Achievements() )->hooks();
		( new Library_App() )->hooks();
		( new Library_Updater( $plugin_file ) )->hooks();
	}
}
