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

namespace Library;

use Library\Admin\Achievements_Page;
use Library\Admin\Assets;
use Library\Admin\Books_List;
use Library\Admin\Dashboard;
use Library\Admin\Metaboxes;
use Library\Admin\Reading_Idea_Widget;
use Library\Admin\Term_Fields;
use Library\Admin\Terms_List;
use Library\App\Login;
use Library\App\Page;

/**
 * Lets each class of the plugin register its hooks, and gives the URL of its assets.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Plugin {

	/**
	 * URL and version of an asset of the plugin.
	 *
	 * The version is the file modification time, so a changed file is never served from cache.
	 *
	 * @param string $path Path relative to the plugin folder, e.g. admin/css/library-admin.css.
	 * @return array{0:non-empty-string,1:string}
	 */
	public static function get_asset( string $path ): array {
		$url     = plugins_url( $path, LIBRARY_FILE );
		$version = (string) filemtime( LIBRARY_DIR_PATH . $path );

		return array( $url, $version );
	}

	/**
	 * Register the hooks of the classes, which Composer loads as they are used.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		$plugin_file = plugin_basename( LIBRARY_FILE );

		new Cache()->hooks();
		new Posts()->hooks();
		new Taxonomies()->hooks();
		new Meta()->hooks();
		new Query()->hooks();
		new Rest_API()->hooks();
		new Rest_Locale()->hooks();
		new Assets()->hooks();
		new Metaboxes()->hooks();
		new Books_List()->hooks();
		new Terms_List()->hooks();
		new Term_Fields()->hooks();
		new Dashboard()->hooks();
		new Reading_Idea_Widget()->hooks();
		new Achievements_Page()->hooks();
		new Page()->hooks();
		new Login()->hooks();
		new Updater( $plugin_file )->hooks();
	}
}
