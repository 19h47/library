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
		'includes/class-library-meta.php',
		'includes/class-library-stats.php',
		'includes/class-library-wp-query.php',
		'includes/class-library-rest-api.php',
		'includes/class-library-posts.php',
		'includes/class-library-taxonomies.php',
		'includes/class-library-reading-idea.php',
		'includes/class-library-achievements.php',
		'admin/class-library-admin.php',
		'admin/class-library-metaboxes.php',
		'admin/class-library-books-list.php',
		'admin/class-library-terms-list.php',
		'admin/class-library-dashboard.php',
		'admin/class-library-reading-idea-widget.php',
		'admin/class-library-achievements-page.php',
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
		$url     = plugins_url( $path, LIBRARY_FILE );
		$version = (string) filemtime( LIBRARY_DIR_PATH . $path );

		return array( $url, $version );
	}

	/**
	 * Print a component of the app, from app/partials/components, with its arguments in `$args`.
	 *
	 * As load_template() does, without the variables of the query it brings: get_template_part() looks in the theme only.
	 *
	 * @param string               $name Name of the component, e.g. field.
	 * @param array<string, mixed> $args Arguments of the component, as its file describes them.
	 * @return void
	 */
	public static function component( string $name, array $args = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Read by the component it includes.
		require LIBRARY_DIR_PATH . "app/partials/components/$name.php";
	}

	/**
	 * Markup of a control with its attributes, set on its first tag: an attribute true is set alone, null is left out.
	 *
	 * @param string                          $markup     Markup of the control, such as <input> or a select with its options.
	 * @param array<string, string|bool|null> $attributes Attributes of the control.
	 * @return string
	 */
	public static function control( string $markup, array $attributes ): string {
		$control = new WP_HTML_Tag_Processor( $markup );

		$control->next_tag();

		foreach ( array_filter( $attributes, fn( $value ) => null !== $value ) as $name => $value ) {
			$control->set_attribute( $name, is_bool( $value ) ? $value : (string) $value );
		}

		return $control->get_updated_html();
	}

	/**
	 * Markup of a select with its options.
	 *
	 * @param array<string|int, string> $options Labels of the options, by value.
	 * @return string
	 */
	public static function select( array $options ): string {
		$markup = '';

		foreach ( $options as $value => $label ) {
			$markup .= sprintf( '<option value="%s">%s</option>', esc_attr( (string) $value ), esc_html( $label ) );
		}

		return "<select>$markup</select>";
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

		$plugin_file = plugin_basename( LIBRARY_FILE );

		( new Library_Posts() )->hooks();
		( new Library_Taxonomies() )->hooks();
		( new Library_Meta() )->hooks();
		( new Library_WP_Query() )->hooks();
		( new Library_Rest_API() )->hooks();
		( new Library_Admin() )->hooks();
		( new Library_Metaboxes() )->hooks();
		( new Library_Books_List() )->hooks();
		( new Library_Terms_List() )->hooks();
		( new Library_Dashboard() )->hooks();
		( new Library_Reading_Idea_Widget() )->hooks();
		( new Library_Achievements_Page() )->hooks();
		( new Library_App() )->hooks();
		( new Library_Updater( $plugin_file ) )->hooks();
	}
}
